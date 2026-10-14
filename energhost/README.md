# Energhost — Voltec Ergon (Eco Smart Grid)

**Descripción:**
Sistema IoT de monitoreo y control de energía en tiempo real. Un ESP32 con sensor PZEM-004T mide el consumo y controla tres relés; los datos viajan a Firebase Realtime Database y un panel web en PHP (Slim 4) los muestra y permite controlar las cargas.

**Integrantes:**

- {Apellido, Nombre} | [@username](https://github.com/username)
- {Apellido, Nombre} | [@username](https://github.com/username)
- {Apellido, Nombre} | [@username](https://github.com/username)
- {Apellido, Nombre} | [@username](https://github.com/username)

Proyecto institucional **E.E.S.T. Nº 4 de Berazategui**, en articulación con **Fundación YPF**.

---

## Elevator's Pitch

- **Para** instituciones, industrias y hogares interesados en el control energético
- **Quienes** necesitan conocer su consumo eléctrico en tiempo real, prevenir sobrecargas y reducir su huella de carbono
- **Energhost** es un sistema de gestión energética IoT (hardware + plataforma web)
- **Que** muestra tensión, corriente y potencia, corta cargas automáticamente ante un sobreconsumo y calcula costo e impacto ambiental
- **Diferente a** los medidores comerciales cerrados y sin integraciones
- **Nuestro proyecto** es abierto, modular y de bajo costo

---

## Cómo funciona

```
ESP32 + PZEM-004T ──internet──► Firebase Realtime Database ◄──internet── Panel PHP (Slim)
 (mide y corta 3 relés)          (datos en tiempo real)                   (cada usuario ve sus equipos)
```

- **Un equipo = 3 tomas.** Cada ESP32 tiene 3 relés, uno por toma, y en cada toma se enchufa un artefacto. Se prende y apaga cada toma por separado.
- Hay **un solo sensor PZEM-004T**: la medición (potencia, energía, costo, CO2) es el **total de las 3 tomas**, no de cada artefacto.
- El **ESP32** se identifica solo (su ID sale de la MAC), escribe las lecturas en Firebase y lee desde ahí la orden de cada relé. No necesita estar en la misma red que el panel: solo internet.
- La **presentación** (`/`) es pública. Para ver el panel hay que **crear una cuenta**.
- Cada usuario **vincula** sus equipos con el código de la etiqueta y ve solo los suyos. Queda registrado quién prende o apaga cada artefacto.

### Recorrido del usuario

```
/  presentación  →  /registro  →  /panel (Mis dispositivos)  →  /vincular  →  /panel/<id> (panel del equipo)
```

### Vinculación de un equipo

1. La primera vez que arranca, el ESP32 arma su identificador con la MAC (`EcoSmart_A1B2C3`) y genera un código de 6 cifras que guarda en su memoria.
2. Se presenta en Firebase **sin dueño** y muestra los datos en el monitor serie (y en la pantalla OLED cuando se agregue).
3. El usuario entra a **Vincular equipo**, escribe el identificador (`A1B2C3`), el código, el nombre del equipo (ej: "Cocina") y, si quiere, qué artefacto hay en cada toma ("Heladera", "Microondas"…). Una toma sin nombre aparece como "Toma 1", "Toma 2"… También puede escanear el QR de la etiqueta, que completa los datos solo.
4. El panel comprueba que el equipo exista, que no tenga dueño y que el código coincida. Si está todo bien, lo asigna a la cuenta.
5. Después de 5 códigos incorrectos, la vinculación se bloquea 10 minutos.

---

## Estructura del proyecto

```text
public/                     ← única carpeta accesible desde la web
    index.php               ← entrada única
    router.php              ← solo para el servidor de desarrollo de PHP
    css/landing.css         ← diseño de la presentación
    css/app.css             ← diseño del panel (misma paleta)
    js/landing.js           ← calculadora de la presentación
    js/panel.js             ← refresca "Mis dispositivos"
    js/dispositivo.js       ← panel de un equipo (cada 2 s)
    manifest.json, sw.js    ← PWA (instalable en el celular)
src/
    bootstrap.php           ← crea Slim, las vistas y carga las rutas
    routes/                 ← web.routes.php (páginas) y api.routes.php (JSON)
    controllers/            ← home, auth, dispositivos, mediciones, reles, admin
    services/               ← auth, dispositivos, mediciones, reles, usuarios
    persistence/            ← firebase (cURL), usuarios, dispositivos, mediciones, reles, eventos
    utils/                  ← electricidad, config y render
    views/                  ← layouts (landing y main), partials, home, auth, dispositivos, admin
scripts/
    simulador.php           ← ESP32 falso para trabajar sin hardware
    crear_admin.php         ← convierte una cuenta en administrador
    firebase-reglas.json    ← reglas de seguridad de la base
    start-local.ps1         ← arranque rápido en Windows
firmware/
    main.ino                ← programa del ESP32
    secrets.example.h       ← plantilla de claves (copiar como secrets.h)
platformio.ini
```

### Dependencia entre capas

Cada capa solo llama a la de abajo, y cada archivo carga lo que usa con `require_once`:

```
routes → controllers → services → persistence → Firebase
                  ↘ utils (render)   ↘ utils (electricidad)
```

Las funciones siguen el patrón `accion_entidad_capa()`, por ejemplo `vincular_dispositivo_service()` o `obtener_reles_persistence()`. No hay clases propias.

---

## Contrato de datos (Firebase)

```text
usuarios/<uid>                  nombre, email, password_hash, rol (usuario | admin), creado, dispositivos/<id>: true
usuarios_por_email/<sha1>       uid   (índice para buscar por email)
dispositivos/<id>/
    info/                       mac, cantidad_reles (los escribe el ESP32) · duenio, nombre, vinculado (los escribe el panel)
    vinculacion/codigo          código de 6 cifras (lo genera el ESP32)
    lectura_actual/             voltaje (V), corriente (A), potencia (W), energia_kwh, consumo_fantasma, timestamp
    tomas/                      toma_1, toma_2, toma_3: nombre del artefacto de cada toma (los escribe el panel)
    reles/                      rele_1, rele_2, rele_3 (0 = apagado, 1 = encendido; rele_N controla la toma N)
    estado/                     ultima_conexion, ip, sobreconsumo, error_sensor
historial/<id>/<clave>          potencia, energia_kwh, timestamp   (una entrada por minuto)
eventos/<id>/<clave>            usuario, nombre_usuario, accion, detalle, timestamp
```

| Rama | Escribe | Lee | Frecuencia |
| --- | --- | --- | --- |
| `lectura_actual` | ESP32 | Panel | cada 2 s |
| `estado` | ESP32 | Panel | cada 10 s |
| `reles` | Panel (y ESP32 al cortar por sobreconsumo) | ESP32 | al tocar el interruptor |
| `historial` | ESP32 | Panel | cada 1 min |
| `tomas` | Panel | Panel | al vincular / al renombrar |
| `info`, `vinculacion` | ESP32 al arrancar · Panel al vincular | Panel | al arrancar / al vincular |
| `usuarios`, `eventos` | Panel | Panel | al registrarse / en cada acción |

Reglas: un equipo está **desconectado** si pasan más de 30 s sin señal. Si la potencia supera **2500 W**, el ESP32 corta **los 3 relés**, marca `estado/sobreconsumo = true` y no vuelve a encender hasta que el usuario lo haga desde el panel.

---

## Requisitos

- PHP 8.1 o superior con la extensión `curl` habilitada
- Composer
- Una cuenta de Google para Firebase (plan gratuito)
- PlatformIO o Arduino IDE para el firmware

No hace falta MySQL ni XAMPP.

---

## Puesta en marcha

### 1. Firebase

1. Crear un proyecto en la [consola de Firebase](https://console.firebase.google.com/).
2. **Realtime Database** → Crear base de datos. No hace falta cargar nada: los equipos y los usuarios se crean solos.
3. En la pestaña **Reglas**, pegar el contenido de `scripts/firebase-reglas.json` y publicar.
4. **Authentication** → habilitar **Correo electrónico/contraseña** y crear un usuario para los ESP32 (por ejemplo `esp32@voltec.com`). Es la cuenta de los equipos, no la de las personas.
5. Para el panel PHP: **Configuración del proyecto → Cuentas de servicio → Secretos de la base de datos** y copiar el secreto. Google marca este método como antiguo, pero sigue funcionando y es el más simple para el proyecto.

### 2. Panel PHP

```powershell
composer install
copy .env.example .env
```

Completar el `.env` con `FIREBASE_URL`, `FIREBASE_AUTH` (el secreto) y el precio del kWh de una factura real. Después:

```powershell
composer serve
```

Abrir http://localhost:8000, crear una cuenta y convertirla en administrador:

```powershell
php scripts/crear_admin.php tu-email@ejemplo.com
```

### 3. Simulador (sin hardware)

El simulador se comporta como un ESP32: se presenta en Firebase y muestra su identificador, su código y el link del QR para vincularlo.

```powershell
php scripts/simulador.php                          # equipo SIM001, consumo normal
php scripts/simulador.php --equipo=SIM002          # otro equipo (se pueden correr varios)
php scripts/simulador.php --modo=sobreconsumo      # la toma 3 simula una estufa: al encenderla corta todo
php scripts/simulador.php --modo=fantasma          # aparatos en espera (0,5 a 15 W)
php scripts/simulador.php --modo=desconectado      # deja de escribir
```

### 4. Firmware

1. Copiar `firmware/secrets.example.h` como `firmware/secrets.h` y completar la API key, la URL, el usuario de los equipos y la dirección del panel.
2. Cargar con PlatformIO (`pio run -t upload`) y abrir el monitor serie a 115200 baudios: ahí aparecen el identificador, el código y el link para el QR. Anotarlos en la etiqueta del gabinete.
3. La primera vez, el ESP32 crea la red **Energhost-Config**: conectarse desde el celular y elegir la red Wi-Fi del lugar.
4. Conexión de los relés: `rele_1` → GPIO18, `rele_2` → GPIO19, `rele_3` → GPIO21.
5. Si los relés funcionan al revés, cambiar `NIVEL_RELE_ENCENDIDO` a `LOW` en `main.ino`.

---

## API del panel

Todas las rutas exigen sesión y verifican que el equipo sea del usuario (o que sea administrador).

| Método y ruta | Devuelve |
| --- | --- |
| `GET /api/dispositivos` | Equipos del usuario con su estado resumido |
| `GET /api/dispositivos/{id}/ultima` | Última lectura, `en_linea`, alertas, costo y CO2 |
| `GET /api/dispositivos/{id}/historial?limite=120` | Lecturas por minuto y análisis de consumo vampiro |
| `GET /api/dispositivos/{id}/reles` | Estado de los 3 relés |
| `POST /api/dispositivos/{id}/reles` | Cambia un relé: `{"rele": 2, "estado": 0}` (`rele` de 1 a 3) |
| `GET /api/dispositivos/{id}/eventos` | Actividad: quién hizo qué y cuándo |

---

## Cálculos

- **Costo:** kWh × `PRECIO_KWH_PESOS` + `CARGO_FIJO_PESOS`
- **Huella de carbono:** kWh × `CO2_EMISSION_FACTOR_KG_PER_KWH`. Se usa 0,283 kg CO2/kWh, el factor oficial del consumo de electricidad de la red publicado por el [Inventario Nacional de GEI](https://inventariogei.ambiente.gob.ar/files/Factores_de_Emision_SNIGEIAR_para_HC_2025.pdf) (dato 2022).
- **Alertas:** sobretensión (> 242 V), baja tensión (< 198 V), sobreconsumo (> 2500 W)
- **Consumo fantasma:** entre 0,5 y 15 W. **Consumo vampiro:** consumo fantasma sostenido más de 2 horas.

La presentación y el panel usan los mismos valores del `.env`.

---

## Licencia

Este proyecto está licenciado bajo la Licencia MIT.
