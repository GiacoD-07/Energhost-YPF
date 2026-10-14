// Energhost - Firmware del ESP32 (Eco Smart Grid)
//
// Un equipo = 3 enchufes, un relé por enchufe (un artefacto en cada uno). Responsabilidades:
//   - medir el consumo TOTAL de los 3 enchufes con el PZEM-004T (hay un solo sensor)
//   - proteger la instalación cortando los 3 relés ante un sobreconsumo
//   - identificarse con un ID único (sacado de su MAC) y un código de vinculación
//   - comunicarse con Firebase según el contrato de datos
//
//   Escribe:  dispositivos/<id>/info            mac, cantidad_reles      (al arrancar)
//             dispositivos/<id>/vinculacion     codigo                   (al arrancar)
//             dispositivos/<id>/lectura_actual  cada 2 s
//             dispositivos/<id>/estado          cada 10 s
//             historial/<id>                    cada 1 min
//   Lee:      dispositivos/<id>/reles           cada 2 s   (rele_1, rele_2, rele_3)
//
// Las claves van en firmware/secrets.h (copiar secrets.example.h). No se sube al repositorio.

#include <WiFi.h>
#include <WiFiManager.h>
#include <Preferences.h>
#include <PZEM004Tv30.h>
#include <Firebase_ESP_Client.h>
#include "addons/TokenHelper.h"
#include "secrets.h"

// ---------- Configuración (debe coincidir con el contrato de datos) ----------
const float UMBRAL_SOBRECONSUMO_W = 2500.0;

// Un relé por enchufe: rele_1 -> GPIO18, rele_2 -> GPIO19, rele_3 -> GPIO21.
const int CANTIDAD_RELES = 3;
const int PIN_RELES[3] = {18, 19, 21};
// Muchos módulos de relé se activan con LOW. Si el de ustedes es así, cambiar a LOW.
const int NIVEL_RELE_ENCENDIDO = HIGH;
const int NIVEL_RELE_APAGADO = (NIVEL_RELE_ENCENDIDO == HIGH) ? LOW : HIGH;

// Intervalos en milisegundos
const unsigned long INTERVALO_LECTURA = 2000;
const unsigned long INTERVALO_ESTADO = 10000;
const unsigned long INTERVALO_HISTORIAL = 60000;

// ---------- Objetos y variables ----------
PZEM004Tv30 pzem(&Serial2, 16, 17);
Preferences memoria;   // memoria no volátil: guarda el código aunque se corte la luz

FirebaseData fbdo;
FirebaseAuth auth;
FirebaseConfig config;

String dispositivoId;     // ej: EcoSmart_A1B2C3
String codigoVinculacion; // ej: 483920
String rutaDispositivo;
String rutaHistorial;

unsigned long ultimaLectura = 0;
unsigned long ultimoEstado = 0;
unsigned long ultimoHistorial = 0;

bool identidadPublicada = false;
bool corteActivo = false;      // true después de un corte por sobreconsumo, hasta el rearme
bool errorSensor = false;
float ultimaPotencia = 0;
float ultimaEnergia = 0;

// ---------- Identidad del equipo ----------

// El ID sale de los últimos 3 bytes de la MAC: es único para cada ESP32 y no cambia nunca.
String calcularIdDispositivo() {
  uint64_t mac = ESP.getEfuseMac();
  char id[7];
  snprintf(id, sizeof(id), "%02X%02X%02X",
           (uint8_t)(mac >> 24), (uint8_t)(mac >> 32), (uint8_t)(mac >> 40));
  return String("EcoSmart_") + id;
}

String macComoTexto() {
  uint64_t mac = ESP.getEfuseMac();
  char texto[18];
  snprintf(texto, sizeof(texto), "%02X:%02X:%02X:%02X:%02X:%02X",
           (uint8_t)(mac), (uint8_t)(mac >> 8), (uint8_t)(mac >> 16),
           (uint8_t)(mac >> 24), (uint8_t)(mac >> 32), (uint8_t)(mac >> 40));
  return String(texto);
}

// El código de 6 cifras se genera una sola vez y se guarda en la memoria del ESP32.
// Así sigue siendo el mismo que figura en la etiqueta aunque el equipo se reinicie.
String obtenerCodigoVinculacion() {
  memoria.begin("energhost", false);
  String codigo = memoria.getString("codigo", "");

  if (codigo.length() != 6) {
    char nuevo[7];
    snprintf(nuevo, sizeof(nuevo), "%06u", (unsigned) (esp_random() % 1000000));
    codigo = String(nuevo);
    memoria.putString("codigo", codigo);
  }

  memoria.end();
  return codigo;
}

void mostrarDatosDeVinculacion() {
  String sufijo = dispositivoId.substring(String("EcoSmart_").length());

  Serial.println();
  Serial.println("==============================================");
  Serial.println("  DATOS PARA VINCULAR ESTE EQUIPO");
  Serial.print("  Identificador: ");  Serial.println(sufijo);
  Serial.print("  Código:        ");  Serial.println(codigoVinculacion);
  Serial.print("  Link para el QR: ");
  Serial.print(PANEL_URL);
  Serial.print("/vincular?equipo=");
  Serial.print(sufijo);
  Serial.print("&codigo=");
  Serial.println(codigoVinculacion);
  Serial.println("==============================================");
  Serial.println();
  // Pendiente (US-10): mostrar estos datos también en la pantalla OLED.
}

// Se publica una vez por arranque: datos del equipo y su código.
// El dueño y el nombre los asigna el panel cuando alguien lo vincula.
void publicarIdentidad() {
  FirebaseJson info;
  info.set("mac", macComoTexto());
  info.set("cantidad_reles", CANTIDAD_RELES);
  info.set("ultimo_arranque/.sv", "timestamp");

  bool ok = Firebase.RTDB.updateNode(&fbdo, rutaDispositivo + "/info", &info);
  ok = ok && Firebase.RTDB.setString(&fbdo, rutaDispositivo + "/vinculacion/codigo", codigoVinculacion);

  if (ok) {
    identidadPublicada = true;
    Serial.println("Equipo registrado en Firebase");
  } else {
    informarErrorFirebase();
  }
}

// ---------- Relés ----------

void aplicarRele(int indice, bool encendido) {
  digitalWrite(PIN_RELES[indice], encendido ? NIVEL_RELE_ENCENDIDO : NIVEL_RELE_APAGADO);
}

void apagarTodosLosReles() {
  for (int i = 0; i < CANTIDAD_RELES; i++) {
    aplicarRele(i, false);
  }
}

// ---------- Wi-Fi ----------

void conectarWiFiInicial() {
  // Usa la red guardada. Si no puede, abre la red "Energhost-Config" para configurarla
  // desde el celular durante 180 segundos.
  WiFiManager wifiManager;
  wifiManager.setConfigPortalTimeout(180);

  if (!wifiManager.autoConnect("Energhost-Config")) {
    Serial.println("Fallo de autenticación Wi-Fi, intente nuevamente");
    ESP.restart();
  }

  Serial.print("Wi-Fi conectado. IP: ");
  Serial.println(WiFi.localIP());
}

// ---------- Firebase ----------

void conectarFirebase() {
  config.api_key = FIREBASE_API_KEY;
  config.database_url = FIREBASE_URL;
  auth.user.email = FIREBASE_USER_EMAIL;
  auth.user.password = FIREBASE_USER_PASSWORD;
  config.token_status_callback = tokenStatusCallback;

  Firebase.begin(&config, &auth);
  Firebase.reconnectWiFi(true);
}

bool firebaseListo() {
  return WiFi.status() == WL_CONNECTED && Firebase.ready();
}

void informarErrorFirebase() {
  Serial.print("Error al escribir en Firebase: ");
  Serial.println(fbdo.errorReason());
}

// ---------- Medición ----------

// Devuelve false si el sensor no respondió (valores NaN).
bool leerSensor(float &voltaje, float &corriente, float &potencia, float &energia) {
  voltaje = pzem.voltage();
  corriente = pzem.current();
  potencia = pzem.power();
  energia = pzem.energy();

  return !(isnan(voltaje) || isnan(corriente) || isnan(potencia) || isnan(energia));
}

bool esConsumoFantasma(float potencia) {
  return potencia > 0.5 && potencia <= 15.0;
}

// ---------- Protección por sobreconsumo ----------

void cortarPorSobreconsumo(float potencia) {
  // 1. Primero se corta en el equipo, sin depender de internet
  apagarTodosLosReles();
  corteActivo = true;
  Serial.printf("Corte por sobreconsumo: %.1f W\n", potencia);

  // 2. Después se avisa a Firebase para que el panel no vuelva a prender nada solo
  if (firebaseListo()) {
    FirebaseJson reles;
    for (int i = 0; i < CANTIDAD_RELES; i++) {
      reles.set("rele_" + String(i + 1), 0);
    }
    if (!Firebase.RTDB.updateNode(&fbdo, rutaDispositivo + "/reles", &reles)) {
      informarErrorFirebase();
    }
    if (!Firebase.RTDB.setBool(&fbdo, rutaDispositivo + "/estado/sobreconsumo", true)) {
      informarErrorFirebase();
    }
  }
}

// Mientras hay un corte, solo se espera a que el panel lo rearme (estado/sobreconsumo = false).
void revisarRearme() {
  if (!firebaseListo()) {
    return;
  }
  if (Firebase.RTDB.getBool(&fbdo, rutaDispositivo + "/estado/sobreconsumo") && !fbdo.boolData()) {
    corteActivo = false;
    Serial.println("Corte rearmado desde el panel");
  } else {
    Serial.println("Corte por sobreconsumo activo: esperando rearme del usuario");
  }
}

// ---------- Lectura de relés ----------

void aplicarRelesDesdeFirebase() {
  if (corteActivo || !firebaseListo()) {
    return;
  }

  if (!Firebase.RTDB.getJSON(&fbdo, rutaDispositivo + "/reles")) {
    // La rama puede no existir todavía (equipo recién vinculado): queda apagado
    return;
  }

  FirebaseJson &json = fbdo.to<FirebaseJson>();
  FirebaseJsonData dato;

  for (int i = 0; i < CANTIDAD_RELES; i++) {
    json.get(dato, "rele_" + String(i + 1));
    if (dato.success) {
      aplicarRele(i, dato.to<int>() == 1);
    }
  }
}

// ---------- Publicación ----------

void publicarLectura(float voltaje, float corriente, float potencia, float energia) {
  FirebaseJson json;
  json.set("voltaje", voltaje);
  json.set("corriente", corriente);
  json.set("potencia", potencia);
  json.set("energia_kwh", energia);
  json.set("consumo_fantasma", esConsumoFantasma(potencia));
  json.set("timestamp/.sv", "timestamp"); // hora del servidor de Firebase

  if (!Firebase.RTDB.updateNode(&fbdo, rutaDispositivo + "/lectura_actual", &json)) {
    informarErrorFirebase();
  }
}

void publicarEstado() {
  FirebaseJson json;
  json.set("ultima_conexion/.sv", "timestamp");
  json.set("ip", WiFi.localIP().toString());
  json.set("error_sensor", errorSensor);

  if (!Firebase.RTDB.updateNode(&fbdo, rutaDispositivo + "/estado", &json)) {
    informarErrorFirebase();
  }
}

void publicarHistorial() {
  FirebaseJson json;
  json.set("potencia", ultimaPotencia);
  json.set("energia_kwh", ultimaEnergia);
  json.set("timestamp/.sv", "timestamp");

  // pushJSON agrega una entrada nueva con clave generada por Firebase.
  // Pendiente (US-11 y US-12): guardar en la Flash si no hay conexión y subir después.
  if (!Firebase.RTDB.pushJSON(&fbdo, rutaHistorial, &json)) {
    informarErrorFirebase();
  }
}

// ---------- Programa principal ----------

void setup() {
  Serial.begin(115200);

  // Arranque seguro: relés apagados hasta saber qué dice Firebase
  for (int i = 0; i < CANTIDAD_RELES; i++) {
    pinMode(PIN_RELES[i], OUTPUT);
  }
  apagarTodosLosReles();

  dispositivoId = calcularIdDispositivo();
  codigoVinculacion = obtenerCodigoVinculacion();
  rutaDispositivo = "/dispositivos/" + dispositivoId;
  rutaHistorial = "/historial/" + dispositivoId;
  mostrarDatosDeVinculacion();

  conectarWiFiInicial();
  conectarFirebase();
}

void loop() {
  unsigned long ahora = millis();

  // Si se cae el Wi-Fi, se reintenta sin bloquear: la protección sigue funcionando.
  if (WiFi.status() != WL_CONNECTED) {
    WiFi.reconnect();
  }

  // La primera vez que hay conexión, el equipo se presenta en Firebase
  if (!identidadPublicada && firebaseListo()) {
    publicarIdentidad();
  }

  if (ahora - ultimaLectura >= INTERVALO_LECTURA) {
    ultimaLectura = ahora;

    float voltaje, corriente, potencia, energia;
    errorSensor = !leerSensor(voltaje, corriente, potencia, energia);

    if (errorSensor) {
      // No se inventan valores: se informa el error y no se actualiza la lectura
      Serial.println("Error de lectura en sensor PZEM-004T: Modbus Timeout");
    } else {
      ultimaPotencia = potencia;
      ultimaEnergia = energia;

      if (potencia > UMBRAL_SOBRECONSUMO_W && !corteActivo) {
        cortarPorSobreconsumo(potencia);
      }

      if (firebaseListo()) {
        publicarLectura(voltaje, corriente, potencia, energia);
      }
    }

    if (corteActivo) {
      revisarRearme();
    }
    aplicarRelesDesdeFirebase();
  }

  if (ahora - ultimoEstado >= INTERVALO_ESTADO) {
    ultimoEstado = ahora;
    if (firebaseListo()) {
      publicarEstado();
    }
  }

  if (ahora - ultimoHistorial >= INTERVALO_HISTORIAL) {
    ultimoHistorial = ahora;
    if (firebaseListo() && !errorSensor) {
      publicarHistorial();
    }
  }
}
