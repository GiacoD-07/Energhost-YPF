<!-- ISSUE: US-01 | Milestone 1: Definición de Datos & Firebase (US-01 a US-04) -->
# US-01: [DAT-01] Contrato de Datos de Firebase Acordado por el Equipo

**Narrativa de Usuario**
> **Como** equipo de desarrollo (firmware y software)
> **Quiero** definir por escrito qué ramas, campos, tipos de datos y permisos se usan en Firebase Realtime Database
> **Para** que el ESP32 y el panel PHP puedan desarrollarse por separado y conectarse sin cambios

**Descripción / Contexto**
Documento "Contrato de datos Firebase", versión 2: `usuarios/`, `usuarios_por_email/`, `dispositivos/<id>/` (info, vinculacion, tomas, lectura_actual, reles, estado), `historial/<id>` y `eventos/<id>`. Cada equipo se identifica por su MAC (`EcoSmart_A1B2C3`) y cada equipo tiene 3 tomas con un relé por toma (`rele_1` a `rele_3`); la medición es el total de las 3.

## Criterios de Aceptación
1. **Escenario: Contrato aprobado por todo el equipo**
    * **Dado que** el documento del contrato versión 2 está compartido con el grupo
    * **Cuando** los integrantes de firmware y software lo revisan en la reunión de equipo
    * **Entonces** se registra la aprobación de todos en el documento
    * **Y** cualquier cambio posterior se acuerda antes de modificar el código

2. **Escenario: Campo usado que no figura en el contrato**
    * **Dado que** una parte del sistema escribe o lee un campo que no está definido en el contrato
    * **Cuando** se revisa el código en un Pull Request
    * **Entonces** el revisor debe rechazar el cambio con el comentario: "Campo fuera del contrato de datos: actualizar el contrato primero"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Nombres de campos en minúscula, sin tildes ni espacios. Los `timestamp` usan la hora del servidor de Firebase. Valores únicos del proyecto: umbral 2500 W, consumo fantasma de 0,5 a 15 W, factor de CO2 0,283 kg/kWh.
* Dependencias con otras historias de usuario: Ninguna.
* Referencias a documentación externa: Documento "Contrato de datos Firebase – EcoSmart Grid" y README.md.

<!-- ISSUE: US-02 | Milestone 1: Definición de Datos & Firebase (US-01 a US-04) -->
# US-02: [DAT-02] Creación del Proyecto Firebase y Cuenta de los Equipos

**Narrativa de Usuario**
> **Como** equipo de desarrollo
> **Quiero** crear el proyecto en Firebase con una Realtime Database y una cuenta de Authentication para los ESP32
> **Para** que los equipos, el simulador y el panel trabajen sobre la misma base desde el primer día

**Descripción / Contexto**
Creación del proyecto en la consola de Firebase y de la Realtime Database (vacía: los equipos y los usuarios se crean solos). En Authentication se habilita "Correo electrónico/contraseña" y se crea un usuario para los ESP32 (ej: `esp32@voltec.com`). Para el panel se copia el secreto de la base en `FIREBASE_AUTH` del `.env`.

## Criterios de Aceptación
1. **Escenario: Proyecto listo para conectar**
    * **Dado que** la Realtime Database y la cuenta de los equipos están creadas
    * **Cuando** se ejecuta `php scripts/simulador.php` con el `.env` completo
    * **Entonces** aparece en la base la rama `dispositivos/EcoSmart_SIM001` con `info` y `vinculacion`
    * **Y** el acceso al proyecto queda compartido con los mails de todos los integrantes

2. **Escenario: Credenciales mal configuradas**
    * **Dado que** `FIREBASE_URL` o `FIREBASE_AUTH` están vacíos o son incorrectos
    * **Cuando** se ejecuta el simulador o se abre el panel
    * **Entonces** el sistema debe mostrar el mensaje de error: "No se pudo conectar con Firebase: revisá FIREBASE_URL y FIREBASE_AUTH en .env"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Usar el plan gratuito de Firebase. El secreto de la base es un método antiguo de Google pero sigue funcionando; nunca se sube al repositorio.
* Dependencias con otras historias de usuario: US-01.
* Referencias a documentación externa: README.md (sección Puesta en marcha) y consola de Firebase.

<!-- ISSUE: US-03 | Milestone 1: Definición de Datos & Firebase (US-01 a US-04) -->
# US-03: [DAT-03] Reglas de Seguridad de Firebase Realtime Database

**Narrativa de Usuario**
> **Como** administrador de seguridad del sistema
> **Quiero** que cada parte del sistema solo pueda tocar las ramas que le corresponden en Firebase
> **Para** impedir lecturas falsas, cambios de dueño de un equipo y acceso a los datos de los usuarios

**Descripción / Contexto**
Reglas en `scripts/firebase-reglas.json`. Los ESP32 entran con la cuenta de Authentication y pueden escribir `info` (sin cambiar `duenio` ni `nombre`), crear su código de vinculación (no cambiarlo), `lectura_actual`, `estado`, `reles` e `historial`. Las ramas `usuarios`, `usuarios_por_email` y `eventos` quedan cerradas: solo las maneja el panel con la clave secreta.

## Criterios de Aceptación
1. **Escenario: El equipo escribe lo que le corresponde**
    * **Dado que** un ESP32 está autenticado con la cuenta de los equipos
    * **Cuando** escribe su `lectura_actual` o su `estado`
    * **Entonces** Firebase acepta la escritura

2. **Escenario: Intento de cambiar el dueño o leer usuarios**
    * **Dado que** un equipo o un cliente no autorizado intenta escribir `info/duenio` o leer `usuarios`
    * **Cuando** Firebase evalúa las reglas
    * **Entonces** el sistema debe mostrar el mensaje de error: "Permission denied" y rechaza la operación

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Probar las reglas con el simulador de reglas de la consola antes de publicarlas. Como todos los ESP32 comparten una cuenta, un equipo podría leer datos de otro; para el proyecto se acepta.
* Dependencias con otras historias de usuario: US-02.
* Referencias a documentación externa: Archivo scripts/firebase-reglas.json.

<!-- ISSUE: US-04 | Milestone 1: Definición de Datos & Firebase (US-01 a US-04) -->
# US-04: [DAT-04] Simulador de Equipos para Desarrollo sin Hardware

**Narrativa de Usuario**
> **Como** desarrollador del panel web
> **Quiero** un script que se comporte como uno o varios ESP32
> **Para** programar y probar el panel, la vinculación y las alertas mientras el firmware se sigue desarrollando

**Descripción / Contexto**
Script `scripts/simulador.php`. Igual que el firmware: se presenta en Firebase con `info` y un código de vinculación, muestra el identificador, el código y el link del QR, escribe `lectura_actual` cada 2 s, `estado` cada 10 s e `historial` cada 1 min, y respeta los 3 relés y el corte por sobreconsumo. Cada toma simula un artefacto distinto y la lectura es la suma de las tomas encendidas, como el sensor real. Con `--equipo=SIM002` se simula otro equipo.

## Criterios de Aceptación
1. **Escenario: Equipo simulado listo para vincular**
    * **Dado que** se ejecuta `php scripts/simulador.php`
    * **Cuando** arranca
    * **Entonces** muestra el identificador `SIM001`, el código y el link `/vincular?equipo=SIM001&codigo=...`
    * **Y** una vez vinculado, el panel muestra sus lecturas sin cambios en el código

2. **Escenario: Simulación de casos especiales**
    * **Dado que** el simulador se ejecuta con `--modo=sobreconsumo`, `--modo=fantasma` o `--modo=desconectado`
    * **Cuando** se generan las lecturas
    * **Entonces** la toma 3 provoca el corte, simula aparatos en espera o deja de escribir, según el modo
    * **Y** si se indica un modo inexistente, el sistema debe mostrar el mensaje de error: "Modo de simulación no válido"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Escrito con funciones, sin clases. Apagarlo cuando el equipo real esté conectado.
* Dependencias con otras historias de usuario: US-01, US-02, US-14.
* Referencias a documentación externa: Archivo scripts/simulador.php.

<!-- ISSUE: US-05 | Milestone 2: Firmware IoT & Firebase (US-05 a US-12) -->
# US-05: [FW-01] Captura de Variables Eléctricas mediante PZEM-004T

**Narrativa de Usuario**
> **Como** firmware del microcontrolador ESP32
> **Quiero** leer voltaje, corriente, potencia activa y energía desde el sensor PZEM-004T vía UART Modbus
> **Para** disponer de telemetría de consumo eléctrico precisa en tiempo real

**Descripción / Contexto**
Lectura del sensor con la librería PZEM004Tv30 en el entorno PlatformIO (`platformio.ini`), conectado a `Serial2` (pines 16 y 17). Si el sensor no responde, el firmware no inventa valores: informa el error en Firebase.

## Criterios de Aceptación
1. **Escenario: Lectura correcta de magnitudes eléctricas**
    * **Dado que** el sensor PZEM-004T está conectado al puerto serie secundario del ESP32 y energizado con 220V AC
    * **Cuando** el ciclo principal `loop()` ejecuta la función de muestreo cada 2 segundos
    * **Entonces** el sistema obtiene Voltaje (V), Corriente (A), Potencia (W) y Energía (kWh)
    * **Y** marca `estado/error_sensor` en `false`

2. **Escenario: Fallo de lectura por desconexión o ruido en la línea UART**
    * **Dado que** la línea RX/TX del sensor sufre una desconexión física
    * **Cuando** la función de muestreo recibe valores inválidos (NaN)
    * **Entonces** el sistema debe mostrar el mensaje de error: "Error de lectura en sensor PZEM-004T: Modbus Timeout" en el monitor serie
    * **Y** escribe `estado/error_sensor` en `true` sin actualizar `lectura_actual`

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: El refresco de lectura debe realizarse a intervalos no inferiores a 1 segundo. Se elimina el reemplazo por valores fijos (220 V) que tiene el firmware actual.
* Dependencias con otras historias de usuario: US-01.
* Referencias a documentación externa: Archivos platformio.ini y firmware/main.ino.

<!-- ISSUE: US-06 | Milestone 2: Firmware IoT & Firebase (US-05 a US-12) -->
# US-06: [FW-02] Conmutación Física de los 3 Relés según Firebase

**Narrativa de Usuario**
> **Como** sistema de control de hardware
> **Quiero** leer la rama `reles` de mi equipo en Firebase y accionar el relé de cada toma
> **Para** cortar o restablecer el flujo eléctrico según lo que indique su dueño desde el panel

**Descripción / Contexto**
Un equipo = 3 tomas, un relé por toma: `CANTIDAD_RELES = 3` en el firmware, con `rele_1` en GPIO18, `rele_2` en GPIO19 y `rele_3` en GPIO21. El ESP32 lee `dispositivos/<id>/reles` en cada ciclo y aplica cada relé por separado (0 = apagado, 1 = encendido). El nivel de activación del módulo se configura en `NIVEL_RELE_ENCENDIDO`.

## Criterios de Aceptación
1. **Escenario: Conmutación por cambio en Firebase**
    * **Dado que** el dueño encendió la toma 2 desde el panel
    * **Cuando** el ESP32 lee la rama `reles` en su próximo ciclo
    * **Entonces** solo el relé de la toma 2 cambia de estado y su carga se enciende en menos de 3 segundos, sin afectar a las otras tomas

2. **Escenario: Arranque del ESP32 o equipo sin vincular**
    * **Dado que** el ESP32 se reinicia, no puede leer Firebase o todavía no existe la rama `reles`
    * **Cuando** se ejecuta la rutina `setup()` y el primer ciclo
    * **Entonces** los 3 relés quedan apagados por seguridad hasta recibir una orden

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Verificar si el módulo de relé se activa con nivel alto o bajo. Aislar ópticamente la etapa de 3,3 V de la red de 220V AC.
* Dependencias con otras historias de usuario: US-01, US-08, US-39.
* Referencias a documentación externa: Archivo firmware/main.ino.

<!-- ISSUE: US-07 | Milestone 2: Firmware IoT & Firebase (US-05 a US-12) -->
# US-07: [FW-03] Corte por Sobreconsumo con Bloqueo hasta Rearme

**Narrativa de Usuario**
> **Como** sistema de protección eléctrica
> **Quiero** cortar los 3 relés cuando la potencia total supere el umbral y mantenerlo apagado hasta que el dueño lo vuelva a encender
> **Para** proteger la instalación sin que los artefactos se prendan y apaguen en ciclo

**Descripción / Contexto**
Corrige el error del firmware anterior, que volvía a encender el relé con el estado viejo del servidor. Ahora el corte apaga las 3 tomas (hay un solo sensor y no se sabe cuál causó el exceso), escribe `rele_1`, `rele_2` y `rele_3` en 0 y `estado/sobreconsumo = true`, y el equipo solo vuelve a obedecer cuando el panel pone `estado/sobreconsumo = false` al encender.

## Criterios de Aceptación
1. **Escenario: Corte local por sobreconsumo**
    * **Dado que** la potencia total de las 3 tomas supera 2500 W
    * **Cuando** el firmware procesa la lectura
    * **Entonces** abre los 3 relés en ese momento, sin esperar a internet
    * **Y** escribe `rele_1`, `rele_2` y `rele_3` en 0 y `estado/sobreconsumo = true`

2. **Escenario: Intento de reconexión automática después del corte**
    * **Dado que** se produjo un corte y la potencia bajó a 0 W
    * **Cuando** el ESP32 vuelve a consultar Firebase
    * **Entonces** las 3 tomas siguen apagadas hasta que el dueño encienda alguna desde el panel
    * **Y** el sistema debe mostrar el mensaje de error: "Corte por sobreconsumo activo: esperando rearme del usuario" en el monitor serie

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: El corte se ejecuta antes de cualquier comunicación. El umbral debe coincidir con el del `.env` del panel.
* Dependencias con otras historias de usuario: US-05, US-06, US-18.
* Referencias a documentación externa: Documento "Contrato de datos Firebase", sección Reglas de funcionamiento.

<!-- ISSUE: US-08 | Milestone 2: Firmware IoT & Firebase (US-05 a US-12) -->
# US-08: [FW-04] Publicación de Lecturas, Estado e Historial en Firebase

**Narrativa de Usuario**
> **Como** firmware del ESP32
> **Quiero** escribir en Firebase, bajo mi propio identificador, la lectura actual, mi estado de conexión y el historial
> **Para** que el dueño vea los datos en tiempo real sin depender de que el equipo y la PC estén en la misma red

**Descripción / Contexto**
El ESP32 escribe en `dispositivos/<id>/lectura_actual` cada 2 s, en `dispositivos/<id>/estado` cada 10 s y agrega una entrada en `historial/<id>` cada 1 min, donde `<id>` es el que arma con su MAC (US-39). Usa una librería de Firebase para ESP32 declarada en `platformio.ini`.

## Criterios de Aceptación
1. **Escenario: Publicación periódica exitosa**
    * **Dado que** el ESP32 está conectado a internet y autenticado en Firebase
    * **Cuando** se cumple cada intervalo (2 s, 10 s y 1 min)
    * **Entonces** Firebase muestra los datos actualizados en las ramas de su identificador
    * **Y** los `timestamp` usan la hora del servidor de Firebase

2. **Escenario: Error al escribir en Firebase**
    * **Dado que** Firebase rechaza la escritura por credenciales inválidas o falta de red
    * **Cuando** el ESP32 intenta publicar
    * **Entonces** el sistema debe mostrar el mensaje de error: "Error al escribir en Firebase" con el motivo en el monitor serie
    * **Y** el firmware sigue midiendo y protegiendo el relé sin bloquearse

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Las credenciales van en `firmware/secrets.h`, que está en `.gitignore`. Cambiar la clave que quedó escrita en el `main.ino` viejo del repositorio.
* Dependencias con otras historias de usuario: US-02, US-03, US-05, US-39.
* Referencias a documentación externa: Documento "Contrato de datos Firebase" y archivo platformio.ini.

<!-- ISSUE: US-09 | Milestone 2: Firmware IoT & Firebase (US-05 a US-12) -->
# US-09: [FW-05] Portal de Configuración Wi-Fi Dinámica (Wi-Fi Manager)

**Narrativa de Usuario**
> **Como** técnico de instalación
> **Quiero** disponer de un portal cautivo web al encender el equipo por primera vez
> **Para** ingresar las credenciales de la red Wi-Fi escolar o doméstica sin recompilar el código

**Descripción / Contexto**
Librería WiFiManager: el ESP32 crea el Access Point "Energhost-Config" cuando no encuentra una red conocida. Como los datos viajan a Firebase por internet, el equipo funciona en cualquier red con salida a internet.

## Criterios de Aceptación
1. **Escenario: Conexión exitosa a una nueva red Wi-Fi**
    * **Dado que** el ESP32 no encuentra redes guardadas en su memoria
    * **Cuando** crea el AP "Energhost-Config" y el usuario ingresa SSID y clave
    * **Entonces** el equipo guarda las credenciales en NVS y se conecta a la red Wi-Fi

2. **Escenario: Ingreso de contraseña incorrecta**
    * **Dado que** el usuario ingresa una clave de Wi-Fi errónea en el portal cautivo
    * **Cuando** el ESP32 intenta asociarse a la red
    * **Entonces** el sistema debe mostrar el mensaje de error: "Fallo de autenticación Wi-Fi, intente nuevamente"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Tiempo de expiración del portal cautivo fijado en 180 segundos. Mientras el portal está abierto, el corte por sobreconsumo debe seguir funcionando.
* Dependencias con otras historias de usuario: Ninguna.
* Referencias a documentación externa: Configuración de red en firmware/main.ino.

<!-- ISSUE: US-10 | Milestone 2: Firmware IoT & Firebase (US-05 a US-12) -->
# US-10: [FW-06] Pantalla OLED con Métricas y Datos de Vinculación

**Narrativa de Usuario**
> **Como** usuario presente frente al equipo físico
> **Quiero** ver en una pantalla OLED las métricas, el estado de conexión y los datos para vincular el equipo
> **Para** auditar el consumo localmente y poder vincularlo aunque se pierda la etiqueta

**Descripción / Contexto**
Display OLED SSD1306 (128x64) por bus I2C. Mientras el equipo no tiene dueño muestra el identificador y el código de vinculación; después muestra potencia, energía y si hay conexión con Firebase.

## Criterios de Aceptación
1. **Escenario: Renderizado de datos en pantalla**
    * **Dado que** la pantalla OLED está inicializada en el bus I2C
    * **Cuando** el equipo arranca o procesa una nueva lectura
    * **Entonces** el display muestra el identificador y el código si el equipo no está vinculado, o la potencia, la energía y el estado de conexión si ya lo está

2. **Escenario: Pérdida de comunicación en el bus I2C**
    * **Dado que** la pantalla se desconecta del bus I2C
    * **Cuando** el microcontrolador intenta refrescar la pantalla
    * **Entonces** el sistema debe mostrar el mensaje de error: "Error I2C: Pantalla OLED no detectada" en el monitor serie

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Limitar el refresco gráfico para no bloquear la medición ni la protección.
* Dependencias con otras historias de usuario: US-05, US-39.
* Referencias a documentación externa: Librería Adafruit_SSD1306 en platformio.ini.

<!-- ISSUE: US-11 | Milestone 2: Firmware IoT & Firebase (US-05 a US-12) -->
# US-11: [FW-07] Contingencia y Almacenamiento Local en Flash (LittleFS)

**Narrativa de Usuario**
> **Como** sistema de telemetría IoT
> **Quiero** guardar las entradas del historial en la memoria Flash del ESP32 cuando se pierda internet
> **Para** garantizar que no se pierda información durante micro cortes de red

**Descripción / Contexto**
Búfer circular en LittleFS para almacenar hasta 500 entradas de historial (una por minuto) mientras no se puede escribir en Firebase.

## Criterios de Aceptación
1. **Escenario: Guardado local ante desconexión**
    * **Dado que** el ESP32 no puede escribir en Firebase
    * **Cuando** se cumple el minuto de una nueva entrada de historial
    * **Entonces** el sistema guarda la entrada con su hora en el archivo local de contingencia

2. **Escenario: Memoria Flash llena**
    * **Dado que** el archivo de contingencia alcanza las 500 entradas
    * **Cuando** se intenta guardar una nueva
    * **Entonces** el sistema sobrescribe la entrada más antigua (FIFO)
    * **Y** el sistema debe mostrar el mensaje de error: "Contingencia llena: se descarta la entrada más antigua" en el monitor serie

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Evitar desgaste prematuro de la memoria Flash: solo se guarda el historial por minuto, no las lecturas cada 2 s. La hora se obtiene por NTP al conectarse.
* Dependencias con otras historias de usuario: US-08, US-09.
* Referencias a documentación externa: Sistema de archivos LittleFS en PlatformIO.

<!-- ISSUE: US-12 | Milestone 2: Firmware IoT & Firebase (US-05 a US-12) -->
# US-12: [FW-08] Sincronización del Historial Pendiente tras la Reconexión

**Narrativa de Usuario**
> **Como** sistema de telemetría IoT
> **Quiero** subir a Firebase las entradas guardadas en la Flash cuando vuelve internet
> **Para** recomponer el historial de consumo sin saltos temporales

**Descripción / Contexto**
Rutina que lee el archivo de contingencia y agrega cada entrada en `historial/<id>` del propio equipo (ej: `historial/EcoSmart_A1B2C3`) con su hora original, en lotes.

## Criterios de Aceptación
1. **Escenario: Vaciado exitoso de datos retenidos**
    * **Dado que** la conexión se restableció y hay entradas pendientes en LittleFS
    * **Cuando** se activa la rutina de sincronización
    * **Entonces** el ESP32 escribe las entradas en `historial/<id>` con su `timestamp` original
    * **Y** borra del archivo local solo las entradas confirmadas por Firebase

2. **Escenario: Interrupción del envío durante la sincronización**
    * **Dado que** la red vuelve a caer en medio de la subida
    * **Cuando** falla una escritura en Firebase
    * **Entonces** el sistema debe mostrar el mensaje de error: "Sincronización interrumpida: se reintentará" en el monitor serie
    * **Y** conserva las entradas no enviadas para el siguiente intento

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Enviar lotes de máximo 50 entradas por ciclo para no bloquear la medición ni la protección del relé. Las entradas guardadas sin conexión llevan la hora obtenida por NTP, no la del servidor.
* Dependencias con otras historias de usuario: US-08, US-11, US-39.
* Referencias a documentación externa: Archivo firmware/main.ino.

<!-- ISSUE: US-13 | Milestone 3: Backend PHP sin Clases & Capas (US-13 a US-21) -->
# US-13: [API-01] Estructura Base: Bootstrap y Rutas en src/routes/

**Narrativa de Usuario**
> **Como** arquitectura de software backend
> **Quiero** separar las rutas en archivos dentro de `src/routes/` y cargarlas desde `src/bootstrap.php`
> **Para** mantener una separación limpia entre las URLs y las funciones que las atienden, como indica la plantilla del profesor

**Descripción / Contexto**
`public/index.php` carga `src/bootstrap.php`, que lee el `.env`, inicia la sesión, crea la app Slim y el renderer de vistas (PhpRenderer) y carga `src/routes/web.routes.php` y `src/routes/api.routes.php`. Cada archivo de rutas devuelve una función que recibe `$app` y registra rutas con funciones anónimas que llaman a funciones de controller. Las rutas con parámetros reciben el id del equipo en `$args["id"]`.

## Criterios de Aceptación
1. **Escenario: Derivación correcta de ruta solicitada**
    * **Dado que** el navegador realiza una petición GET /api/dispositivos/EcoSmart_A1B2C3/ultima
    * **Cuando** Slim busca la ruta registrada en `api.routes.php`
    * **Entonces** ejecuta `api_obtener_ultima_medicion_controller($response, "EcoSmart_A1B2C3")`
    * **Y** devuelve la respuesta del controller

2. **Escenario: Solicitud a una ruta no definida**
    * **Dado que** un usuario intenta acceder a /api/ruta-inexistente o a /pagina-inexistente
    * **Cuando** Slim no encuentra coincidencia
    * **Entonces** el sistema debe mostrar el mensaje de error en JSON: "Error 404: Endpoint no encontrado" para la API, o la página "Página no encontrada" para el navegador

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Sin clases propias ni autoload PSR-4. Cada archivo carga sus dependencias con `require_once`. Toda función de ruta devuelve un `$response`, como exige Slim. La presentación usa el layout `layouts/landing.php` y el resto `layouts/main.php`.
* Dependencias con otras historias de usuario: Ninguna.
* Referencias a documentación externa: Archivos public/index.php, src/bootstrap.php, src/routes/ y "Repaso Slim" en docs/.

<!-- ISSUE: US-14 | Milestone 3: Backend PHP sin Clases & Capas (US-13 a US-21) -->
# US-14: [API-02] Persistencia Base de Firebase con cURL en firebase.persistence.php

**Narrativa de Usuario**
> **Como** capa de acceso a datos
> **Quiero** funciones simples que lean y escriban en Firebase Realtime Database mediante su API REST
> **Para** que el resto de la persistencia use Firebase sin librerías con clases

**Descripción / Contexto**
Archivo `src/persistence/firebase.persistence.php`, el único del proyecto que habla con Firebase. Funciones: `firebase_leer_persistence($ruta, $parametros)` (GET), `firebase_actualizar_persistence($ruta, $datos)` (PATCH), `firebase_agregar_persistence($ruta, $datos)` (POST), `firebase_escribir_persistence($ruta, $valor)` (PUT), `firebase_borrar_persistence($ruta)` (DELETE) y `firebase_hora_servidor_persistence()` (hora del servidor). Usan cURL, `FIREBASE_URL` y `FIREBASE_AUTH` del `.env`.

## Criterios de Aceptación
1. **Escenario: Lectura exitosa de una rama**
    * **Dado que** `FIREBASE_URL` y `FIREBASE_AUTH` están configurados en `.env`
    * **Cuando** se llama a `firebase_leer_persistence('dispositivos/EcoSmart_A1B2C3/reles')`
    * **Entonces** la función devuelve un array PHP con `rele_1`

2. **Escenario: Firebase no responde o rechaza la petición**
    * **Dado que** Firebase tarda más de 5 segundos o responde con un código de error
    * **Cuando** la función ejecuta la petición cURL
    * **Entonces** el sistema debe mostrar el mensaje de error: "Error de comunicación con Firebase" lanzando una excepción para que la capa de servicio la maneje

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Timeout de cURL de 5 segundos. La credencial nunca se escribe en el código. Las consultas de historial y eventos usan `orderBy` y `limitToLast` para no descargar toda la rama.
* Dependencias con otras historias de usuario: US-02, US-03.
* Referencias a documentación externa: Archivo src/persistence/firebase.persistence.php y API REST de Firebase Realtime Database.

<!-- ISSUE: US-15 | Milestone 3: Backend PHP sin Clases & Capas (US-13 a US-21) -->
# US-15: [API-03] Persistencia de Mediciones en mediciones.persistence.php

**Narrativa de Usuario**
> **Como** capa de acceso a datos
> **Quiero** funciones que lean la lectura actual, el estado y el historial de un equipo desde Firebase
> **Para** entregar a los servicios los datos de consumo sin que conozcan cómo está organizada la base

**Descripción / Contexto**
Archivo `src/persistence/mediciones.persistence.php` con `obtener_lectura_actual_persistence($dispositivoId)`, `obtener_estado_dispositivo_persistence($dispositivoId)` y `obtener_historial_persistence($dispositivoId, $limite)`. Reciben el id del equipo que pidió el usuario; el servicio ya verificó que sea suyo.

## Criterios de Aceptación
1. **Escenario: Lectura del historial reciente**
    * **Dado que** existen entradas en `historial/EcoSmart_A1B2C3`
    * **Cuando** se llama a `obtener_historial_persistence('EcoSmart_A1B2C3', 60)`
    * **Entonces** devuelve como máximo las 60 entradas más recientes

2. **Escenario: Equipo sin datos todavía**
    * **Dado que** el equipo no escribió ninguna lectura en Firebase
    * **Cuando** se llama a `obtener_lectura_actual_persistence('EcoSmart_A1B2C3')`
    * **Entonces** la función devuelve `null` para que el servicio informe "Sin lecturas disponibles"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Para el historial usar consultas ordenadas con límite, en lugar de descargar toda la rama.
* Dependencias con otras historias de usuario: US-01, US-14.
* Referencias a documentación externa: Archivo src/persistence/mediciones.persistence.php.

<!-- ISSUE: US-16 | Milestone 3: Backend PHP sin Clases & Capas (US-13 a US-21) -->
# US-16: [API-04] Persistencia de los Relés en reles.persistence.php

**Narrativa de Usuario**
> **Como** capa de acceso a datos
> **Quiero** funciones que lean y actualicen el estado de los 3 relés de un equipo en Firebase
> **Para** que el panel pueda consultar y cambiar el encendido de cada toma

**Descripción / Contexto**
Archivo `src/persistence/reles.persistence.php` con `obtener_reles_persistence($dispositivoId)`, `actualizar_rele_persistence($dispositivoId, $numero, $estado)` y `rearmar_sobreconsumo_persistence($dispositivoId)`. Cada equipo tiene `rele_1`, `rele_2` y `rele_3`, uno por toma.

## Criterios de Aceptación
1. **Escenario: Actualización del estado del relé**
    * **Dado que** se requiere apagar el artefacto de la toma 2
    * **Cuando** se llama a `actualizar_rele_persistence('EcoSmart_A1B2C3', 2, 0)`
    * **Entonces** Firebase guarda `reles/rele_2 = 0` sin modificar `rele_1`, `rele_3` ni otros campos del equipo

2. **Escenario: Rama de relés inexistente**
    * **Dado que** el equipo recién se vinculó y la rama `reles` no existe en Firebase
    * **Cuando** se llama a `obtener_reles_persistence('EcoSmart_A1B2C3')`
    * **Entonces** la función devuelve `null` para que el servicio responda con los 3 relés apagados

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Usar PATCH para cambiar solo el campo indicado y no pisar los demás.
* Dependencias con otras historias de usuario: US-01, US-14.
* Referencias a documentación externa: Archivo src/persistence/reles.persistence.php.

<!-- ISSUE: US-17 | Milestone 3: Backend PHP sin Clases & Capas (US-13 a US-21) -->
# US-17: [API-05] Servicio de Mediciones: Estado de Conexión y Resumen por Equipo

**Narrativa de Usuario**
> **Como** dueño de un equipo
> **Quiero** saber si mi equipo está conectado y ver su última lectura con sus alertas
> **Para** confiar en que los datos que veo son actuales

**Descripción / Contexto**
Archivo `src/services/mediciones.service.php` con `obtener_resumen_actual_service($dispositivoId)`, `calcular_estado_conexion_service($ultimaConexion)`, `obtener_historial_service($dispositivoId, $limite)` y `detectar_consumo_vampiro_service($historial)`. Antes de usarlo, el controller verifica que el equipo sea del usuario con `verificar_acceso_dispositivo_service()`.

## Criterios de Aceptación
1. **Escenario: Equipo en línea**
    * **Dado que** `estado/ultima_conexion` del equipo es de hace 8 segundos
    * **Cuando** se ejecuta `obtener_resumen_actual_service('EcoSmart_A1B2C3')`
    * **Entonces** devuelve `en_linea: true` junto con la lectura, la alerta, el costo y el CO2

2. **Escenario: Equipo desconectado**
    * **Dado que** `estado/ultima_conexion` es de hace más de 30 segundos
    * **Cuando** se arma el resumen
    * **Entonces** devuelve `en_linea: false`
    * **Y** el sistema debe mostrar el mensaje de error: "Dispositivo desconectado: la última lectura puede no estar actualizada"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: El límite de segundos sale de `SEGUNDOS_PARA_DESCONECTADO` en `.env`. El servicio solo usa la capa de persistencia.
* Dependencias con otras historias de usuario: US-15, US-31, US-41.
* Referencias a documentación externa: Archivo src/services/mediciones.service.php.

<!-- ISSUE: US-18 | Milestone 3: Backend PHP sin Clases & Capas (US-13 a US-21) -->
# US-18: [API-06] Servicio de Relés: Validación, Rearme y Registro de Actividad

**Narrativa de Usuario**
> **Como** capa de servicio backend
> **Quiero** validar cada orden del relé, rearmar el corte por sobreconsumo y registrar quién hizo el cambio
> **Para** que solo lleguen a Firebase órdenes válidas del dueño y quede trazabilidad de cada acción

**Descripción / Contexto**
Archivo `src/services/reles.service.php` con `obtener_reles_service($usuario, $dispositivoId)` y `cambiar_rele_service($usuario, $dispositivoId, $numero, $estado)`. Verifica que el equipo sea del usuario, valida el relé según `info/cantidad_reles` (3), toma el nombre del artefacto de `tomas/toma_N`, al encender rearma el sobreconsumo y guarda un evento en `eventos/<id>`.

## Criterios de Aceptación
1. **Escenario: Orden válida de encendido**
    * **Dado que** el dueño enciende la "Heladera", enchufada en la toma 1
    * **Cuando** se ejecuta `cambiar_rele_service($usuario, 'EcoSmart_A1B2C3', 1, 1)`
    * **Entonces** se guarda `reles/rele_1 = 1` y `estado/sobreconsumo = false`
    * **Y** se registra el evento "Encendió Heladera (toma 1)" con su nombre y la hora

2. **Escenario: Orden con datos inválidos o de un equipo ajeno**
    * **Dado que** se recibe un relé fuera de rango, un estado distinto de 0 o 1, o un equipo que no es del usuario
    * **Cuando** el servicio valida los datos
    * **Entonces** el sistema debe mostrar el mensaje de error: "El relé debe estar entre 1 y 3 y el estado debe ser 0 o 1", o responder "Dispositivo no encontrado." si el equipo es ajeno

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Un equipo ajeno se responde como inexistente, para no revelar qué equipos existen.
* Dependencias con otras historias de usuario: US-07, US-16, US-43.
* Referencias a documentación externa: Archivo src/services/reles.service.php.

<!-- ISSUE: US-19 | Milestone 3: Backend PHP sin Clases & Capas (US-13 a US-21) -->
# US-19: [API-07] Controller y Rutas API de Mediciones por Equipo

**Narrativa de Usuario**
> **Como** panel web
> **Quiero** consultar por API la última lectura y el historial de un equipo del usuario
> **Para** actualizar los indicadores y gráficos sin recargar la página

**Descripción / Contexto**
Archivo `src/controllers/mediciones.controller.php`, rutas en `api.routes.php`: `GET /api/dispositivos/{id}/ultima` y `GET /api/dispositivos/{id}/historial?limite=120`. Ambas exigen sesión y verifican que el equipo sea del usuario o que sea administrador.

## Criterios de Aceptación
1. **Escenario: Consulta exitosa de la última lectura**
    * **Dado que** el usuario tiene sesión y el equipo es suyo
    * **Cuando** el panel hace GET /api/dispositivos/EcoSmart_A1B2C3/ultima
    * **Entonces** responde con código 200 y un JSON con la lectura, `en_linea` y la alerta

2. **Escenario: Sin sesión, equipo ajeno o Firebase caído**
    * **Dado que** no hay sesión, el equipo es de otro usuario o Firebase no responde
    * **Cuando** el controller procesa la petición
    * **Entonces** responde 401 "Iniciá sesión para continuar", 404 "Dispositivo no encontrado." o 503 "No se pudieron obtener las mediciones", según el caso

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: El controller no contiene reglas de negocio ni accesos a Firebase. Usa `json_response_util()`.
* Dependencias con otras historias de usuario: US-13, US-17, US-40.
* Referencias a documentación externa: Archivos src/controllers/mediciones.controller.php y src/routes/api.routes.php.

<!-- ISSUE: US-20 | Milestone 3: Backend PHP sin Clases & Capas (US-13 a US-21) -->
# US-20: [API-08] Controller y Rutas API de los Relés por Equipo

**Narrativa de Usuario**
> **Como** dueño de un equipo
> **Quiero** consultar y cambiar el estado de cada relé por la API
> **Para** encender o apagar cada artefacto desde el panel

**Descripción / Contexto**
Archivo `src/controllers/reles.controller.php`, rutas `GET /api/dispositivos/{id}/reles` y `POST /api/dispositivos/{id}/reles` con cuerpo `{"rele": 2, "estado": 0}` (`rele` de 1 a 3). El GET devuelve `rele_1`, `rele_2` y `rele_3`. Exigen sesión y que el equipo sea del usuario.

## Criterios de Aceptación
1. **Escenario: Orden de conmutación válida**
    * **Dado que** el dueño envía `{"rele": 2, "estado": 0}` para su equipo
    * **Cuando** el controller llama a `cambiar_rele_service()`
    * **Entonces** responde con código 200 y `{"ok": true}`

2. **Escenario: Orden inválida o de otra persona**
    * **Dado que** el cuerpo es inválido o el equipo no es del usuario
    * **Cuando** se procesa la petición
    * **Entonces** responde 400 "Formato JSON inválido en el cuerpo de la solicitud", 422 con el error de validación o 404 "Dispositivo no encontrado.", según el caso

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: La cookie de sesión es `SameSite=Lax`, así otros sitios no pueden enviar órdenes en nombre del usuario.
* Dependencias con otras historias de usuario: US-13, US-18, US-40.
* Referencias a documentación externa: Archivos src/controllers/reles.controller.php y src/routes/api.routes.php.

<!-- ISSUE: US-21 | Milestone 3: Backend PHP sin Clases & Capas (US-13 a US-21) -->
# US-21: [API-09] Refactor: Eliminar Clases, MySQL y Middlewares del Código Actual

**Narrativa de Usuario**
> **Como** equipo de desarrollo
> **Quiero** reemplazar el código con clases por funciones y quitar todo lo relacionado con MySQL
> **Para** tener un código que entendamos y podamos corregir con lo visto en clase

**Descripción / Contexto**
Se eliminan `src/models/`, `src/Middleware/ApiKeyMiddleware.php`, `src/database/`, `scripts/setup.sql`, `scripts/migrate.php` y las clases de `src/controllers/`, `src/services/` y `src/persistence/`. Se quita el autoload PSR-4 de `composer.json`. El `.env.example` pasa a tener las variables de Firebase en lugar de las de MySQL.

## Criterios de Aceptación
1. **Escenario: Proyecto sin clases propias**
    * **Dado que** se completó el refactor
    * **Cuando** se busca la palabra `class` dentro de `src/`
    * **Entonces** no aparece ninguna clase propia del proyecto
    * **Y** la aplicación arranca con `php -S localhost:8000 -t public public/router.php`

2. **Escenario: Archivo que todavía depende de MySQL**
    * **Dado que** queda algún archivo que usa PDO o variables `DB_*`
    * **Cuando** se ejecuta la aplicación sin MySQL instalado
    * **Entonces** el sistema debe mostrar el mensaje de error: "Error de conexión a la Base de Datos" y el refactor no se considera terminado

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Hacer el refactor en una rama propia y unirla con un Pull Request revisado por el equipo.
* Dependencias con otras historias de usuario: US-13 a US-20.
* Referencias a documentación externa: Documento "Estructura final del proyecto – EcoSmart Grid".

<!-- ISSUE: US-22 | Milestone 4: Dashboard Web Responsivo & PWA (US-22 a US-27) -->
# US-22: [UI-01] Vistas con Layout y Diseño Unificado

**Narrativa de Usuario**
> **Como** usuario del sistema Energhost
> **Quiero** que la presentación y el panel tengan el mismo diseño y se adapten a cualquier pantalla
> **Para** usar el sistema cómodo desde el celular o la PC y que se vea como un solo producto

**Descripción / Contexto**
Dos layouts en `src/views/layouts/`: `landing.php` para la presentación y `main.php` para la aplicación, con la paleta y tipografías de la presentación del equipo (`public/css/landing.css` y `public/css/app.css`). Vistas en `home/`, `auth/`, `dispositivos/` y `admin/`, renderizadas con PhpRenderer.

## Criterios de Aceptación
1. **Escenario: Renderizado responsivo**
    * **Dado que** el usuario abre el panel de un equipo desde un celular o una PC
    * **Cuando** carga la página
    * **Entonces** las tarjetas de potencia, voltaje, corriente y energía se acomodan al ancho de la pantalla

2. **Escenario: Página inexistente**
    * **Dado que** se pide una dirección que no existe
    * **Cuando** Slim no encuentra la ruta
    * **Entonces** el sistema debe mostrar la página "Página no encontrada" con un botón para volver al inicio

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Diseñar bajo la filosofía Mobile-First. Las tipografías se cargan de Google Fonts y tienen alternativa del sistema.
* Dependencias con otras historias de usuario: US-13, US-45.
* Referencias a documentación externa: Carpeta src/views/ y "Repaso Slim" en docs/.

<!-- ISSUE: US-23 | Milestone 4: Dashboard Web Responsivo & PWA (US-22 a US-27) -->
# US-23: [UI-02] Actualización Asíncrona y Aviso de Equipo Desconectado

**Narrativa de Usuario**
> **Como** dueño de un equipo
> **Quiero** que el panel se actualice solo y me avise si el equipo o el servidor están desconectados
> **Para** saber en todo momento si los datos que veo son reales y actuales

**Descripción / Contexto**
`public/js/dispositivo.js` consulta `/api/dispositivos/{id}/ultima` cada 2 s, los relés cada 3 s, la actividad cada 15 s y el historial cada 60 s. `public/js/panel.js` refresca la lista de "Mis dispositivos" cada 5 s. Si la sesión vence, lleva al login.

## Criterios de Aceptación
1. **Escenario: Actualización dinámica de datos en pantalla**
    * **Dado que** el panel del equipo está abierto y el equipo está en línea
    * **Cuando** el temporizador ejecuta la consulta
    * **Entonces** actualiza Watts, Volts, Amperes y kWh y muestra "EN LÍNEA" en verde

2. **Escenario: Equipo o servidor desconectado**
    * **Dado que** la API responde `en_linea: false` o el pedido falla
    * **Cuando** se procesa la respuesta
    * **Entonces** el sistema debe mostrar "DESCONECTADO" o "SIN SERVIDOR", según el caso
    * **Y** los valores quedan en gris con la fecha y hora de la última lectura

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: No acumular pedidos si el anterior sigue pendiente.
* Dependencias con otras historias de usuario: US-17, US-19, US-22.
* Referencias a documentación externa: Archivos public/js/dispositivo.js y public/js/panel.js.

<!-- ISSUE: US-24 | Milestone 4: Dashboard Web Responsivo & PWA (US-22 a US-27) -->
# US-24: [UI-03] Encendido y Apagado de cada Toma desde el Panel

**Narrativa de Usuario**
> **Como** dueño de un equipo
> **Quiero** un interruptor por toma en el panel del equipo, con el nombre del artefacto enchufado
> **Para** encender o apagar cada artefacto por separado en tiempo real

**Descripción / Contexto**
Tres interruptores en `dispositivos/detalle.php` ("Heladera · Toma 1", etc.), cada uno que envía POST /api/dispositivos/{id}/reles desde `dispositivo.js`. Si hay un corte por sobreconsumo activo, el panel lo muestra y encender funciona como rearme, previa confirmación.

## Criterios de Aceptación
1. **Escenario: Envío exitoso de la orden**
    * **Dado que** el dueño toca el interruptor de la "Heladera" (toma 1)
    * **Cuando** la API responde `{"ok": true}`
    * **Entonces** el interruptor muestra el nuevo estado y la acción aparece en la actividad
    * **Y** solo el relé de la toma 1 cambia en el siguiente ciclo del ESP32

2. **Escenario: Rearme después de un corte por sobreconsumo**
    * **Dado que** `estado/sobreconsumo` está en `true`
    * **Cuando** el dueño intenta encender cualquiera de las 3 tomas
    * **Entonces** la interfaz debe solicitar una confirmación con el mensaje: "Hubo un corte por sobreconsumo. ¿Confirmás que querés volver a encender?" antes de enviar la orden

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Deshabilitar el interruptor durante la petición. Si la API responde con error, vuelve a su estado anterior.
* Dependencias con otras historias de usuario: US-20, US-23.
* Referencias a documentación externa: Archivos public/js/dispositivo.js y src/views/dispositivos/detalle.php.

<!-- ISSUE: US-25 | Milestone 4: Dashboard Web Responsivo & PWA (US-22 a US-27) -->
# US-25: [UI-04] Gráfico del Historial de Consumo del Equipo

**Narrativa de Usuario**
> **Como** dueño de un equipo
> **Quiero** ver un gráfico de la potencia total de mi equipo (las 3 tomas) en las últimas horas
> **Para** identificar en qué momentos consume más energía

**Descripción / Contexto**
Gráfico de líneas en el panel del equipo, dibujado en SVG por `dispositivo.js` (sin librerías), alimentado por GET /api/dispositivos/{id}/historial.

## Criterios de Aceptación
1. **Escenario: Visualización del historial**
    * **Dado que** existen entradas de historial de las últimas 2 horas
    * **Cuando** el dueño abre el panel del equipo
    * **Entonces** se dibuja un gráfico con la potencia (W) en el eje vertical y la hora en el horizontal
    * **Y** se actualiza una vez por minuto

2. **Escenario: Historial vacío**
    * **Dado que** el equipo todavía no guardó al menos 2 entradas de historial
    * **Cuando** se carga el gráfico
    * **Entonces** el sistema debe mostrar el mensaje de error: "Todavía no hay datos de historial para mostrar"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Pedir como máximo 120 entradas por consulta para cuidar la cuota gratuita de Firebase.
* Dependencias con otras historias de usuario: US-15, US-19, US-22.
* Referencias a documentación externa: Archivo public/js/dispositivo.js.

<!-- ISSUE: US-26 | Milestone 4: Dashboard Web Responsivo & PWA (US-22 a US-27) -->
# US-26: [UI-05] Instalación PWA y Soporte Offline (manifest.json / sw.js)

**Narrativa de Usuario**
> **Como** usuario de dispositivos móviles
> **Quiero** instalar la aplicación web en mi pantalla de inicio y abrirla aunque no tenga internet
> **Para** utilizar la herramienta con apariencia de aplicación nativa

**Descripción / Contexto**
Configuración de la PWA con `public/manifest.json` (abre en "Mis dispositivos") y el Service Worker `public/sw.js`, que guarda en caché solo los archivos estáticos y la presentación.

## Criterios de Aceptación
1. **Escenario: Instalación de PWA en el sistema operativo**
    * **Dado que** el sitio cuenta con un `manifest.json` válido e íconos de 192 y 512 px
    * **Cuando** el usuario ingresa desde un navegador compatible
    * **Entonces** el sistema operativo muestra el aviso "Agregar a la pantalla principal"

2. **Escenario: Apertura sin conexión a internet**
    * **Dado que** el Service Worker guardó los archivos estáticos y la presentación
    * **Cuando** el usuario abre la PWA sin conexión
    * **Entonces** se muestra la presentación guardada
    * **Y** las páginas con datos personales y las respuestas de la API no se guardan en caché

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Cambiar `VERSION` en `sw.js` cada vez que se modifiquen los archivos estáticos.
* Dependencias con otras historias de usuario: US-22.
* Referencias a documentación externa: Archivos public/manifest.json y public/sw.js.

<!-- ISSUE: US-27 | Milestone 4: Dashboard Web Responsivo & PWA (US-22 a US-27) -->
# US-27: [UI-06] Inicio de Sesión y Roles

**Narrativa de Usuario**
> **Como** usuario registrado
> **Quiero** iniciar sesión con mi email y contraseña
> **Para** acceder a mis equipos, y que solo los administradores puedan gestionar cuentas

**Descripción / Contexto**
Rutas GET/POST /login y POST /logout en `web.routes.php`, con `auth.service.php`. La contraseña se verifica con `password_verify` contra el hash guardado en `usuarios/<uid>`. Roles: `usuario` (por defecto) y `admin`. La sesión vence a los 60 minutos sin uso. Si se pidió una página protegida, después del login se vuelve a ella.

## Criterios de Aceptación
1. **Escenario: Inicio de sesión correcto**
    * **Dado que** el usuario ingresa un email y contraseña válidos
    * **Cuando** el servidor verifica las credenciales
    * **Entonces** se inicia la sesión y se muestra "Mis dispositivos", o la página que había pedido

2. **Escenario: Credenciales incorrectas o página protegida sin sesión**
    * **Dado que** la persona ingresa datos incorrectos o entra a /panel sin sesión
    * **Cuando** se procesa la petición
    * **Entonces** el sistema debe mostrar el mensaje de error: "Email o contraseña incorrectos." o redirigir al login, según el caso

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Solo se permite volver a rutas internas después del login. Un cambio de rol se aplica cuando la persona vuelve a ingresar.
* Dependencias con otras historias de usuario: US-40.
* Referencias a documentación externa: Archivos src/services/auth.service.php y src/controllers/auth.controller.php.

<!-- ISSUE: US-28 | Milestone 5: Inteligencia de Datos & Cálculos (US-28 a US-31) -->
# US-28: [SRV-01] Cálculo de Huella de Carbono con el Factor Oficial

**Narrativa de Usuario**
> **Como** usuario del sistema
> **Quiero** ver cuántos kilogramos de CO2 equivalen a la energía consumida
> **Para** conocer el impacto ambiental de mi consumo

**Descripción / Contexto**
Función `calcular_huella_co2_util($kwh)` en `src/utils/electricidad.php` con el factor `CO2_EMISSION_FACTOR_KG_PER_KWH` del `.env`: 0,283 kg CO2/kWh, factor oficial del consumo de electricidad de la red publicado por el Inventario Nacional de GEI (dato 2022). La presentación usa el mismo valor y cita la fuente.

## Criterios de Aceptación
1. **Escenario: Conversión de kWh a emisiones de CO2**
    * **Dado que** el consumo acumulado es de 10 kWh y el factor es 0,283
    * **Cuando** se ejecuta `calcular_huella_co2_util(10)`
    * **Entonces** devuelve 2,83 kg de CO2

2. **Escenario: Consumo cero o factor sin configurar**
    * **Dado que** no hay consumo o el factor no está en `.env`
    * **Cuando** se ejecuta la función
    * **Entonces** devuelve 0,00 kg de CO2 sin errores, o el panel muestra "--" si falta el factor

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Actualizar el factor cuando se publique uno más nuevo; está en un solo lugar.
* Dependencias con otras historias de usuario: US-17, US-45.
* Referencias a documentación externa: Factores de Emisión SNIGEI-AR (inventariogei.ambiente.gob.ar).

<!-- ISSUE: US-29 | Milestone 5: Inteligencia de Datos & Cálculos (US-28 a US-31) -->
# US-29: [SRV-02] Estimación de Costo en Pesos con Tarifa Real

**Narrativa de Usuario**
> **Como** usuario del sistema
> **Quiero** ver el costo estimado en pesos de la energía consumida
> **Para** prever el importe de la factura eléctrica

**Descripción / Contexto**
Función `calcular_costo_pesos_util($kwh)` en `src/utils/electricidad.php`: kWh × `PRECIO_KWH_PESOS` + `CARGO_FIJO_PESOS`. El precio se toma de una factura real reciente. La calculadora de la presentación usa el mismo valor.

## Criterios de Aceptación
1. **Escenario: Cálculo de costo estimado en pesos**
    * **Dado que** se consumieron 150 kWh, el precio es $50 por kWh y el cargo fijo es $1000
    * **Cuando** se ejecuta `calcular_costo_pesos_util(150)`
    * **Entonces** devuelve $8500

2. **Escenario: Tarifa no configurada**
    * **Dado que** `PRECIO_KWH_PESOS` no está definido en `.env`
    * **Cuando** se solicita la estimación
    * **Entonces** el sistema debe mostrar el mensaje de error: "Tarifa sin configurar" en la presentación y "--" en el panel

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Un solo precio por kWh para todo el proyecto.
* Dependencias con otras historias de usuario: US-17, US-45.
* Referencias a documentación externa: Archivo src/utils/electricidad.php.

<!-- ISSUE: US-30 | Milestone 5: Inteligencia de Datos & Cálculos (US-28 a US-31) -->
# US-30: [SRV-03] Detección de Consumo Vampiro Sostenido

**Narrativa de Usuario**
> **Como** usuario del sistema
> **Quiero** que se detecten aparatos que quedan en modo de espera durante horas
> **Para** apagarlos y evitar consumo innecesario

**Descripción / Contexto**
El ESP32 marca `consumo_fantasma: true` en cada lectura con potencia entre 0,5 y 15 W. La función `detectar_consumo_vampiro_service()` en `mediciones.service.php` revisa el historial y avisa si esa condición se sostuvo más de 2 horas seguidas.

## Criterios de Aceptación
1. **Escenario: Consumo en espera sostenido**
    * **Dado que** el historial muestra potencias entre 0,5 y 15 W durante más de 2 horas seguidas
    * **Cuando** se ejecuta la detección
    * **Entonces** el panel muestra el aviso "Consumo vampiro detectado" con la energía desperdiciada en kWh

2. **Escenario: Retorno a consumo activo o apagado total**
    * **Dado que** la potencia supera 15 W o cae a 0 W
    * **Cuando** se actualiza la evaluación
    * **Entonces** se desactiva el aviso de consumo vampiro
    * **Y** si no hay historial suficiente, el sistema debe mostrar el mensaje de error: "Historial insuficiente para analizar consumo vampiro"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Ignorar picos breves de un solo minuto causados por ruido eléctrico en el sensor.
* Dependencias con otras historias de usuario: US-08, US-15, US-17.
* Referencias a documentación externa: Archivo src/services/mediciones.service.php.

<!-- ISSUE: US-31 | Milestone 5: Inteligencia de Datos & Cálculos (US-28 a US-31) -->
# US-31: [SRV-04] Alertas de Sobreconsumo y Tensión Fuera de Rango

**Narrativa de Usuario**
> **Como** usuario del sistema
> **Quiero** recibir advertencias visuales destacadas en el Dashboard
> **Para** tomar acciones inmediatas ante picos de consumo o problemas de tensión

**Descripción / Contexto**
Función `determinar_estado_alerta_util($voltaje, $potencia)` en `src/utils/electricidad.php`. Devuelve "Sobretensión" (mayor a 242 V), "Baja tensión" (menor a 198 V), "Sobreconsumo" (mayor al umbral del contrato) o "Normal".

## Criterios de Aceptación
1. **Escenario: Superación de límite máximo de potencia**
    * **Dado que** la potencia medida supera 2500 W
    * **Cuando** el servicio arma el resumen actual
    * **Entonces** adjunta `alerta: "Sobreconsumo"` en la respuesta JSON
    * **Y** la web resalta el indicador en color rojo

2. **Escenario: Restablecimiento a valores normales**
    * **Dado que** la potencia y la tensión vuelven a rangos seguros
    * **Cuando** llega la nueva lectura
    * **Entonces** el indicador vuelve al color verde habitual con el estado "Normal"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: El umbral de potencia debe ser el mismo en el firmware, el contrato y el backend. Configurar según la capacidad de los conductores del lugar.
* Dependencias con otras historias de usuario: US-17, US-23.
* Referencias a documentación externa: Archivo src/utils/electricidad.php.

<!-- ISSUE: US-32 | Milestone 6: Integraciones & DevOps (US-32 a US-35) -->
# US-32: [INT-01] Respaldo del Historial en Google Sheets

**Narrativa de Usuario**
> **Como** auditor del proyecto
> **Quiero** que el historial de consumo de los equipos se copie en una hoja de Google Sheets
> **Para** disponer de un registro secundario fácil de consultar

**Descripción / Contexto**
Un script de Google Apps Script (`scripts/google_script.js`) con un activador por tiempo lee cada 10 minutos las entradas nuevas de `historial/<id>` de cada equipo mediante la API REST de Firebase y agrega una fila por entrada con el identificador del equipo. El panel PHP no participa.

## Criterios de Aceptación
1. **Escenario: Copia exitosa del historial**
    * **Dado que** hay entradas nuevas en el historial desde la última copia
    * **Cuando** se ejecuta el activador de Apps Script
    * **Entonces** la hoja agrega una fila por entrada con equipo, fecha, hora, potencia y energía

2. **Escenario: Firebase no responde**
    * **Dado que** la petición a Firebase falla o tarda demasiado
    * **Cuando** se ejecuta el activador
    * **Entonces** el sistema debe mostrar el mensaje de error: "No se pudo leer el historial de Firebase" en el registro de ejecuciones de Apps Script
    * **Y** reintenta en la siguiente ejecución sin duplicar filas

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Pendiente de decisión del equipo; si el historial de Firebase alcanza, esta historia se cierra. La clave de Firebase se guarda en las Propiedades del script, nunca en el código. La hoja no incluye datos de los usuarios.
* Dependencias con otras historias de usuario: US-08.
* Referencias a documentación externa: Archivo scripts/google_script.js.

<!-- ISSUE: US-33 | Milestone 6: Integraciones & DevOps (US-32 a US-35) -->
# US-33: [INT-02] Etiqueta con QR de Vinculación

**Narrativa de Usuario**
> **Como** persona que recibe un equipo Eco Smart Grid
> **Quiero** escanear un código QR impreso en el gabinete
> **Para** vincular el equipo a mi cuenta sin escribir el identificador ni el código

**Descripción / Contexto**
El firmware muestra en el monitor serie el link `PANEL_URL/vincular?equipo=A1B2C3&codigo=483920`. Con ese link se genera un QR que se imprime en la etiqueta del gabinete, junto al identificador y el código escritos.

## Criterios de Aceptación
1. **Escenario: Vinculación por QR**
    * **Dado que** una persona con sesión escanea el QR de la etiqueta
    * **Cuando** se abre el link
    * **Entonces** el formulario de vinculación aparece con el identificador y el código ya completos

2. **Escenario: Escaneo sin cuenta**
    * **Dado que** la persona todavía no inició sesión
    * **Cuando** abre el link del QR
    * **Entonces** el sistema debe mostrar el mensaje: "Ingresá o creá tu cuenta para vincular el equipo." y después del login vuelve al formulario completo

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: El QR debe ser legible impreso en 3x3 cm. Si cambia la dirección del panel, hay que reimprimir las etiquetas.
* Dependencias con otras historias de usuario: US-39, US-41.
* Referencias a documentación externa: Archivo firmware/main.ino (función mostrarDatosDeVinculacion).

<!-- ISSUE: US-34 | Milestone 6: Integraciones & DevOps (US-32 a US-35) -->
# US-34: [DEV-01] Despliegue Continuo (CI/CD) con GitHub Actions (deploy.yml)

**Narrativa de Usuario**
> **Como** equipo de desarrollo de software
> **Quiero** ejecutar un flujo automatizado de despliegue en `.github/workflows/deploy.yml` al hacer merge en main
> **Para** actualizar el servidor web sin subir archivos por FTP manualmente

**Descripción / Contexto**
Configuración del pipeline en `.github/workflows/deploy.yml` (hoy el archivo está vacío) para transferir los archivos al hosting.

## Criterios de Aceptación
1. **Escenario: Ejecución de pipeline de despliegue automático**
    * **Dado que** se aprueba un Pull Request hacia la rama main
    * **Cuando** GitHub Actions detecta el evento push
    * **Entonces** ejecuta `deploy.yml` subiendo los cambios al servidor web
    * **Y** marca el estado exitoso en el repositorio

2. **Escenario: Fallo de autenticación en credenciales FTP/SSH**
    * **Dado que** el secreto del servidor en GitHub es incorrecto
    * **Cuando** el workflow intenta conectar con el host
    * **Entonces** el pipeline cancela el proceso y marca la tarea en rojo dentro del repositorio

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Excluir `.git`, `firmware/`, `docs/`, `.env` y `firmware/secrets.h` de la transferencia. Las credenciales del hosting se guardan como secretos de GitHub.
* Dependencias con otras historias de usuario: US-21.
* Referencias a documentación externa: Archivo .github/workflows/deploy.yml.

<!-- ISSUE: US-35 | Milestone 6: Integraciones & DevOps (US-32 a US-35) -->
# US-35: [DEV-02] Limpieza del Repositorio y README Completo

**Narrativa de Usuario**
> **Como** equipo de desarrollo
> **Quiero** quitar del repositorio los archivos que no pertenecen al proyecto y completar el README
> **Para** que cualquier integrante o profesor encuentre rápido lo que necesita

**Descripción / Contexto**
Se eliminan `zip-voltec/`, `slim-template-2026-main.zip`, `vendor/`, `desktop.ini`, `docs/manual-tecnico.pdf` (vacío), la imagen duplicada del cronograma y los restos de Flutter. Se completa el README con nombre, integrantes, pitch, instalación con Firebase y uso del simulador.

## Criterios de Aceptación
1. **Escenario: Repositorio limpio**
    * **Dado que** se completó la limpieza
    * **Cuando** se clona el repositorio
    * **Entonces** no contiene archivos comprimidos, `vendor/` ni archivos duplicados
    * **Y** `composer install` genera las dependencias correctamente

2. **Escenario: Archivo con credenciales en el repositorio**
    * **Dado que** se detecta una clave o un `.env` subido
    * **Cuando** se revisa el Pull Request
    * **Entonces** el revisor debe rechazar el cambio con el comentario: "Credenciales en el repositorio: quitar el archivo y cambiar la clave"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Borrar una clave en un commit nuevo no la elimina del historial: además hay que cambiarla.
* Dependencias con otras historias de usuario: US-21.
* Referencias a documentación externa: Archivos README.md y .gitignore.

<!-- ISSUE: US-36 | Milestone 7: Diseño Mecánico & Impresión 3D (US-36 a US-38) -->
# US-36: [3D-01] Modelado CAD de Gabinete Estanco para Protección Eléctrica 220V

**Narrativa de Usuario**
> **Como** proyectista de hardware y diseño 3D
> **Quiero** diseñar un gabinete modular estanco mediante software CAD
> **Para** alojar la placa ESP32, el módulo PZEM-004T y los relés aislando las conexiones expuestas de 220V

**Descripción / Contexto**
Creación del modelo tridimensional en formato STL/STEP con fijaciones internas, ventilación por convección y calces para bornes.

## Criterios de Aceptación
1. **Escenario: Ensamble y calce correcto de componentes internos**
    * **Dado que** las tolerancias del modelo CAD consideran un margen de 0,4 mm
    * **Cuando** se ubican las placas electrónicas dentro de los soportes
    * **Entonces** la placa ESP32 y el sensor PZEM se fijan sin requerir adhesivos externos

2. **Escenario: Verificación de aislamiento de seguridad de bornes**
    * **Dado que** los bornes de 220V quedan dentro del gabinete
    * **Cuando** se cierra la tapa superior
    * **Entonces** ningún elemento conductor de alta tensión queda al alcance del contacto manual

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Respetar las dimensiones máximas de la cama de impresión (200 x 200 mm). Prever el espacio de la pantalla OLED si se incorpora.
* Dependencias con otras historias de usuario: US-05, US-06, US-10.
* Referencias a documentación externa: Archivos de diseño en docs/.

<!-- ISSUE: US-37 | Milestone 7: Diseño Mecánico & Impresión 3D (US-36 a US-38) -->
# US-37: [3D-02] Generación de GCODE y Parámetros de Slicing para Impresión FDM

**Narrativa de Usuario**
> **Como** técnico de fabricación digital
> **Quiero** procesar el archivo STL en el programa de laminado (Slicer)
> **Para** generar el archivo GCODE optimizando el tiempo de impresión y la resistencia mecánica del gabinete

**Descripción / Contexto**
Configuración de parámetros de impresión: relleno (infill) al 20%, altura de capa 0,2 mm, temperatura de extrusor a 210°C para filamento PLA.

## Criterios de Aceptación
1. **Escenario: Generación válida de archivo de impresión GCODE**
    * **Dado que** se ingresa el STL del gabinete al software de laminado
    * **Cuando** se aplica el perfil de impresión optimizado para PLA
    * **Entonces** se exporta el archivo GCODE con el tiempo estimado de impresión y el consumo de filamento en gramos

2. **Escenario: Colapso por falta de soportes en geometrías voladizas**
    * **Dado que** el modelo presenta voladizos con ángulos superiores a 45°
    * **Cuando** se simula el laminado
    * **Entonces** el operador debe activar los soportes tipo "Árbol" antes de exportar el GCODE

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Verificar que la velocidad de impresión no supere los 50 mm/s para mantener la precisión geométrica.
* Dependencias con otras historias de usuario: US-36.
* Referencias a documentación externa: Archivos del repositorio en docs/.

<!-- ISSUE: US-38 | Milestone 7: Diseño Mecánico & Impresión 3D (US-36 a US-38) -->
# US-38: [3D-03] Pausa Programada (M600) para Rotulado y Etiquetado Bicolor

**Narrativa de Usuario**
> **Como** diseñador de producto
> **Quiero** insertar el comando GCODE M600 en la capa de relieve del isotipo "Voltec Ergon"
> **Para** cambiar manualmente el filamento e imprimir el texto e íconos en un color contrastante

**Descripción / Contexto**
Uso del cambio de filamento por capas para resaltar en color blanco el texto y advertencias sobre la base de color negro.

## Criterios de Aceptación
1. **Escenario: Pausa programada e intercambio de filamento exitoso**
    * **Dado que** la impresora 3D llega a la capa de relieve (ej: capa 15)
    * **Cuando** el controlador lee el comando M600
    * **Entonces** la máquina retrae el filamento, retira el cabezal del área de trabajo y emite una señal sonora de espera
    * **Y** reanuda la impresión en el color secundario al confirmar la carga

2. **Escenario: Purgado insuficiente del nuevo color**
    * **Dado que** se cambia de filamento de color negro a blanco
    * **Cuando** la máquina reanuda la impresión sin la purga adecuada
    * **Entonces** se debe extruir filamento de forma manual hasta obtener un flujo de color puro antes de continuar

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: La impresora 3D debe contar con el parámetro de pausa M600 habilitado en su firmware Marlin.
* Dependencias con otras historias de usuario: US-37.
* Referencias a documentación externa: Documentación y manuales del proyecto en docs/.

<!-- ISSUE: US-39 | Milestone 8: Cuentas, Equipos & Vinculación (US-39 a US-45, US-48) -->
# US-39: [FW-09] Identidad del Equipo: ID por MAC y Código de Vinculación

**Narrativa de Usuario**
> **Como** firmware del ESP32
> **Quiero** generar mi identificador a partir de mi MAC y un código de vinculación que no cambie
> **Para** que cada equipo sea único y su dueño pueda reclamarlo de forma segura

**Descripción / Contexto**
El ID es `EcoSmart_` + los últimos 6 caracteres de la MAC (ej: `EcoSmart_A1B2C3`). El código de 6 cifras se sortea en el primer arranque y se guarda con `Preferences`. Al conectarse, el equipo escribe `info` (mac, cantidad_reles) y `vinculacion/codigo` en Firebase, y muestra el identificador, el código y el link del QR en el monitor serie.

## Criterios de Aceptación
1. **Escenario: Primer arranque**
    * **Dado que** el ESP32 arranca por primera vez y tiene internet
    * **Cuando** termina el `setup()`
    * **Entonces** aparece en Firebase `dispositivos/EcoSmart_A1B2C3` con `info` y `vinculacion/codigo`, sin dueño
    * **Y** el monitor serie muestra "DATOS PARA VINCULAR ESTE EQUIPO" con el identificador, el código y el link

2. **Escenario: Reinicio o corte de luz**
    * **Dado que** el equipo ya tenía código y se reinicia
    * **Cuando** vuelve a arrancar
    * **Entonces** conserva el mismo identificador y el mismo código que figuran en la etiqueta

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Las reglas de Firebase impiden que el equipo cambie su código o su dueño.
* Dependencias con otras historias de usuario: US-02, US-03.
* Referencias a documentación externa: Archivo firmware/main.ino y documento "Contrato de datos Firebase", sección Identidad y vinculación.

<!-- ISSUE: US-40 | Milestone 8: Cuentas, Equipos & Vinculación (US-39 a US-45, US-48) -->
# US-40: [ACC-01] Registro de Usuarios

**Narrativa de Usuario**
> **Como** consumidor que quiere monitorear sus artefactos
> **Quiero** crear una cuenta con mi nombre, email y contraseña
> **Para** acceder al panel y que quede registrado quién controla cada equipo

**Descripción / Contexto**
Rutas GET/POST /registro. `registrar_usuario_service()` valida los datos, verifica que el email no exista (índice `usuarios_por_email`), guarda el usuario con `password_hash` y rol `usuario`, e inicia la sesión.

## Criterios de Aceptación
1. **Escenario: Registro exitoso**
    * **Dado que** la persona completa nombre, email válido y una contraseña de al menos 8 caracteres repetida igual
    * **Cuando** envía el formulario
    * **Entonces** se crea la cuenta con rol `usuario`, se inicia la sesión y se muestra "Mis dispositivos" con el mensaje de bienvenida

2. **Escenario: Datos inválidos o email repetido**
    * **Dado que** el email ya está registrado, las contraseñas no coinciden o la contraseña es corta
    * **Cuando** envía el formulario
    * **Entonces** el sistema debe mostrar el mensaje de error correspondiente, por ejemplo: "Ya existe una cuenta con ese email."

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Solo se guardan nombre, email y el hash de la contraseña. El email se compara en minúsculas.
* Dependencias con otras historias de usuario: US-02, US-14.
* Referencias a documentación externa: Archivos src/services/auth.service.php y src/persistence/usuarios.persistence.php.

<!-- ISSUE: US-41 | Milestone 8: Cuentas, Equipos & Vinculación (US-39 a US-45, US-48) -->
# US-41: [ACC-02] Vinculación de Equipos con Código (web y API)

**Narrativa de Usuario**
> **Como** usuario que inició sesión
> **Quiero** agregar un equipo a mi cuenta con su identificador o su MAC y el código de la etiqueta
> **Para** quedar como su dueño y controlar solo mis artefactos, sin que otra persona pueda apropiarse de ellos

**Descripción / Contexto**
Rutas GET/POST /vincular y endpoint `POST /api/dispositivos/{id}/vincular`, todos protegidos por sesión: el usuario que inició sesión queda como dueño. `{id}` acepta el identificador (`A1B2C3`, `EcoSmart_A1B2C3`) o la MAC completa (`24:6F:28:A1:B2:C3`, con dos puntos, guiones o sin separadores): igual que el firmware, el id son los últimos 6 caracteres de la MAC. `revisar_vinculacion_service()` controla en orden que el equipo exista, que no tenga dueño y que el código coincida; `vincular_dispositivo_service()` guarda `info/duenio`, `info/nombre` (lugar, ej: "Cocina"), `tomas/toma_1` a `toma_3` (opcionales), la marca en `usuarios/<uid>/dispositivos` y el evento "vinculado". El código se mantiene aunque la ID ya identifique al equipo: la MAC no es secreta, y el código prueba que la persona tiene el equipo en la mano. Después de 5 intentos fallidos se bloquea 10 minutos.

## Criterios de Aceptación
1. **Escenario: Vinculación exitosa con la MAC**
    * **Dado que** el equipo `EcoSmart_A1B2C3` está conectado y libre, y el usuario inició sesión
    * **Cuando** envía `POST /api/dispositivos/24:6F:28:A1:B2:C3/vincular` con `{"codigo": "<correcto>", "nombre": "Cocina"}`
    * **Entonces** responde 201 con `{"ok": true, "dispositivo": "EcoSmart_A1B2C3"}` y el equipo aparece en "Mis dispositivos"

2. **Escenario: Sin sesión, código incorrecto, equipo inexistente o de otra cuenta**
    * **Dado que** alguno de los controles falla
    * **Cuando** se envía la petición o el formulario
    * **Entonces** el sistema debe mostrar el mensaje de error: "El código no coincide. Revisá los 6 números de la etiqueta del equipo.", "No encontramos ese equipo. Fijate que esté enchufado y conectado al Wi-Fi, esperá un minuto y probá de nuevo." o "Este equipo ya está en la cuenta de otra persona. Si te lo dieron usado, pedile que lo quite de su cuenta.", según el caso
    * **Y** la API responde 401 sin sesión, 422 con datos incorrectos y 429 tras 5 intentos fallidos ("Probaste muchas veces. Esperá 10 minutos y volvé a intentar.")

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: El código se compara con `hash_equals`. El límite de intentos se guarda en la sesión. Un equipo que ya tiene dueño se rechaza antes de revisar el código.
* Dependencias con otras historias de usuario: US-39, US-40, US-48.
* Referencias a documentación externa: Archivos src/services/dispositivos.service.php, src/controllers/dispositivos.controller.php y src/routes/api.routes.php.

<!-- ISSUE: US-42 | Milestone 8: Cuentas, Equipos & Vinculación (US-39 a US-45, US-48) -->
# US-42: [ACC-03] Mis Dispositivos: Lista, Nombre y Desvinculación

**Narrativa de Usuario**
> **Como** usuario con varios equipos
> **Quiero** ver todos mis equipos en una lista, cambiarles el nombre y quitarlos de mi cuenta
> **Para** organizar mis artefactos y poder darle un equipo a otra persona

**Descripción / Contexto**
Página /panel con una tarjeta por equipo (nombre, identificador, estado de conexión, potencia total, "2 de 3 tomas encendidas" y alerta), refrescada cada 5 s. En el panel de cada equipo, el dueño puede cambiar el nombre del equipo y el de cada toma (POST /panel/{id}/nombre, campos `nombre`, `toma_1`, `toma_2`, `toma_3`) o quitarlo de la cuenta (POST /panel/{id}/desvincular).

## Criterios de Aceptación
1. **Escenario: Lista de equipos**
    * **Dado que** el usuario tiene dos equipos vinculados
    * **Cuando** entra a "Mis dispositivos"
    * **Entonces** ve las dos tarjetas con su estado y potencia actualizados
    * **Y** si no tiene equipos, ve 3 pasos simples (enchufar, conectar al Wi-Fi, escanear el QR) y el botón "Agregar mi primer equipo"

2. **Escenario: Quitar un equipo**
    * **Dado que** el dueño confirma "Quitar equipo de mi cuenta"
    * **Cuando** se procesa la acción
    * **Entonces** el equipo desaparece de su lista, queda libre, se borran los nombres de las tomas y se registra el evento "desvinculado"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: El nombre del equipo y el de cada artefacto admiten hasta 40 caracteres.
* Dependencias con otras historias de usuario: US-41.
* Referencias a documentación externa: Archivos src/views/dispositivos/index.php y src/controllers/dispositivos.controller.php.

<!-- ISSUE: US-43 | Milestone 8: Cuentas, Equipos & Vinculación (US-39 a US-45, US-48) -->
# US-43: [ACC-04] Registro de Actividad de cada Equipo

**Narrativa de Usuario**
> **Como** dueño de un equipo o administrador
> **Quiero** ver quién vinculó, encendió o apagó el artefacto y cuándo
> **Para** auditar el uso de la instalación

**Descripción / Contexto**
Cada acción del panel guarda un evento en `eventos/<id>` (usuario, nombre, acción, detalle y hora). El panel del equipo muestra los últimos 20 en la tabla "Actividad", que se actualiza cada 15 s (GET /api/dispositivos/{id}/eventos).

## Criterios de Aceptación
1. **Escenario: Acción registrada**
    * **Dado que** Dylan apaga "Heladera de la cocina"
    * **Cuando** se refresca la tabla de actividad
    * **Entonces** aparece una fila con la fecha y hora, "Dylan" y "Apagó Heladera de la cocina"

2. **Escenario: Equipo sin actividad o ajeno**
    * **Dado que** el equipo no tiene eventos o no es del usuario
    * **Cuando** se pide la actividad
    * **Entonces** el sistema debe mostrar "Todavía no hay actividad." o responder "Dispositivo no encontrado.", según el caso

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: El corte automático por sobreconsumo no se registra como evento porque el ESP32 no puede escribir en `eventos`; se ve como aviso en el panel.
* Dependencias con otras historias de usuario: US-18, US-41.
* Referencias a documentación externa: Archivo src/persistence/eventos.persistence.php.

<!-- ISSUE: US-44 | Milestone 8: Cuentas, Equipos & Vinculación (US-39 a US-45, US-48) -->
# US-44: [ACC-05] Administración de Usuarios

**Narrativa de Usuario**
> **Como** administrador del proyecto
> **Quiero** ver la lista de usuarios, dar o quitar el rol de administrador y generar contraseñas temporales
> **Para** gestionar las cuentas sin acceder a Firebase a mano

**Descripción / Contexto**
Página /admin/usuarios (solo rol `admin`) con nombre, email, cantidad de equipos y rol de cada usuario. El primer administrador se crea con `php scripts/crear_admin.php email`. Un administrador puede ver el panel de cualquier equipo.

## Criterios de Aceptación
1. **Escenario: Contraseña temporal**
    * **Dado que** un usuario olvidó su contraseña
    * **Cuando** el administrador toca "Nueva contraseña" y confirma
    * **Entonces** se muestra una contraseña temporal una sola vez y la persona puede ingresar con ella

2. **Escenario: Acceso sin permisos**
    * **Dado que** un usuario sin rol de administrador entra a /admin/usuarios
    * **Cuando** se procesa la petición
    * **Entonces** el sistema debe mostrar el mensaje de error: "Acceso solo para administradores." y vuelve a "Mis dispositivos"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Un administrador no puede cambiar su propio rol.
* Dependencias con otras historias de usuario: US-27, US-40.
* Referencias a documentación externa: Archivos src/services/usuarios.service.php y scripts/crear_admin.php.

<!-- ISSUE: US-45 | Milestone 8: Cuentas, Equipos & Vinculación (US-39 a US-45, US-48) -->
# US-45: [UI-07] Presentación Pública como Página de Entrada

**Narrativa de Usuario**
> **Como** visitante que no conoce el proyecto
> **Quiero** una página que explique qué es Eco Smart Grid y Energhost
> **Para** entender el producto y decidir crear mi cuenta

**Descripción / Contexto**
La presentación del equipo es la página `/`, con el problema, la arquitectura, el producto, la huella de carbono con calculadora, "Cómo empezar" en 3 pasos, el equipo y el cronograma. El factor de CO2, la tarifa y el umbral se leen del `.env`, así coinciden con el panel. Los botones llevan a "Crear cuenta", "Ingresar" o "Mi panel" si ya hay sesión.

## Criterios de Aceptación
1. **Escenario: Visitante sin cuenta**
    * **Dado que** una persona entra a la dirección del sistema
    * **Cuando** carga la presentación
    * **Entonces** ve el factor de 0,283 kg CO2/kWh con su fuente, la calculadora con la tarifa del `.env` y el botón "Crear mi cuenta"

2. **Escenario: Tarifa sin configurar**
    * **Dado que** `PRECIO_KWH_PESOS` no está en `.env`
    * **Cuando** se usa la calculadora
    * **Entonces** el sistema debe mostrar "Tarifa sin configurar" en el costo estimado

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: La presentación no menciona tecnologías descartadas (Blynk, Flutter) ni promete medir artefactos por separado dentro de un mismo equipo.
* Dependencias con otras historias de usuario: US-22, US-28, US-29.
* Referencias a documentación externa: Archivos src/views/home/index.php, public/css/landing.css y public/js/landing.js.

<!-- ISSUE: US-46 | Milestone 9: Documentación & Defensa (US-46 a US-47) -->
# US-46: [DOC-01] Carpeta Final del Proyecto

**Narrativa de Usuario**
> **Como** equipo de Voltec Ergon
> **Quiero** completar la carpeta final con los 5 bloques de la guía y sus anexos
> **Para** demostrar el análisis, la arquitectura, los datos y las pruebas del sistema aunque la demo en vivo falle

**Descripción / Contexto**
Documento "Carpeta Final – Energhost (Voltec Ergon)": empresa y presupuesto, UI/UX, arquitectura y modelado (DFD, UML), persistencia (DER y diccionario de datos), control de versiones, matriz de pruebas, código destacado, cursograma y Scrum. Se exporta a PDF para imprimir.

## Criterios de Aceptación
1. **Escenario: Carpeta completa para la entrega**
    * **Dado que** el presupuesto, los nombres de los integrantes, los QR y el historial de commits están completos
    * **Cuando** el equipo revisa la carpeta con la guía de presentación
    * **Entonces** cada bloque tiene su contenido y los datos técnicos están en tablas
    * **Y** se exporta a PDF con tipografía legible para imprimir

2. **Escenario: Dato desactualizado en la carpeta**
    * **Dado que** cambió algo del sistema (una ruta, un campo o un valor del `.env`)
    * **Cuando** se revisa la carpeta antes de imprimir
    * **Entonces** el revisor debe marcar el bloque con el comentario: "Dato desactualizado: corregir antes de imprimir"

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Las pruebas P24 a P28 de la matriz necesitan el equipo real. Los diagramas se exportan desde el documento, sin capturas borrosas.
* Dependencias con otras historias de usuario: US-05, US-07, US-35, US-39.
* Referencias a documentación externa: Guía de Presentación de la Carpeta Final y documento "Carpeta Final – Energhost".

<!-- ISSUE: US-47 | Milestone 9: Documentación & Defensa (US-46 a US-47) -->
# US-47: [DOC-02] Defensa Oral y Demostración en Vivo

**Narrativa de Usuario**
> **Como** equipo de Voltec Ergon
> **Quiero** preparar la exposición de "Arquitectura en Capas y Scrum" y una demo del sistema
> **Para** explicar el proyecto con claridad y mostrarlo funcionando ante el jurado

**Descripción / Contexto**
La exposición tiene 18 diapositivas repartidas entre 4 integrantes (Persona 1 a 4), cada uno con un ejemplo real de Energhost. La demo muestra el recorrido completo: crear cuenta, vincular un equipo con el QR, ver las mediciones, apagar el artefacto y ver la actividad.

## Criterios de Aceptación
1. **Escenario: Ensayo completo**
    * **Dado que** cada integrante tiene asignada su parte
    * **Cuando** el equipo hace un simulacro de la defensa
    * **Entonces** la exposición y la demo se completan en el tiempo asignado por los profesores

2. **Escenario: Falla del equipo físico durante la demo**
    * **Dado que** el ESP32 no se conecta durante la exposición
    * **Cuando** el equipo lo detecta
    * **Entonces** continúa la demo con el simulador (`php scripts/simulador.php`) y la explica como plan de contingencia

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: Llevar el panel funcionando en una computadora propia y una red móvil de respaldo.
* Dependencias con otras historias de usuario: US-04, US-46.
* Referencias a documentación externa: Presentación "Arquitectura en Capas y Scrum" y documento "Carpeta Final – Energhost", Anexo B.

<!-- ISSUE: US-48 | Milestone 8: Cuentas, Equipos & Vinculación (US-39 a US-45, US-48) -->
# US-48: [UI-08] Vinculación en 3 Toques y Lectura Cómoda para Todas las Edades

**Narrativa de Usuario**
> **Como** persona mayor sin conocimientos técnicos a la que le llega cara la factura de luz
> **Quiero** agregar mi equipo escaneando el QR y eligiendo dónde lo enchufé, y leer el panel sin esfuerzo
> **Para** controlar mi consumo sola, sin pedirle ayuda a nadie

**Descripción / Contexto**
Pedido de los profesores: el sistema tiene que ser intuitivo para alguien como "Susana, de sesenta y pico". El QR de la etiqueta abre `/vincular?equipo=...&codigo=...`; si los datos son correctos se muestra `dispositivos/vincular-confirmar.php` ("¡Encontramos tu equipo!") con botones grandes de lugar (Cocina, Living, Dormitorio, Lavadero, Oficina, Otro lugar) y un único botón "Vincular mi equipo". Los nombres de las tomas quedan para después. El formulario manual (`dispositivos/vincular.php`) se ordena en 3 pasos numerados. Los textos dicen "Agregar equipo" en lugar de "Vincular". El diseño usa texto de 17 px, grises más claros, títulos de grosor 400 y zonas táctiles de 48 px o más.

## Criterios de Aceptación
1. **Escenario: Vinculación con el QR en 3 toques**
    * **Dado que** la persona escanea el QR de un equipo libre con su celular
    * **Cuando** inicia sesión (si no la tenía), elige "Cocina" y toca "Vincular mi equipo"
    * **Entonces** llega al panel del equipo con el mensaje "¡Listo! Tu equipo de "Cocina" ya está en tu cuenta. Tocá cada toma para prenderla o apagarla."
    * **Y** en ningún momento tuvo que escribir el identificador ni el código

2. **Escenario: Toca "Vincular" sin elegir el lugar**
    * **Dado que** está en la pantalla "¡Encontramos tu equipo!"
    * **Cuando** toca "Vincular mi equipo" sin elegir un lugar
    * **Entonces** el sistema debe mostrar el mensaje de error: "Elegí o escribí el lugar del equipo." en la misma pantalla, sin volver al formulario largo

## **Comentarios Adicionales**
* Notas sobre limitaciones técnicas: La opción "Otro lugar" muestra un campo de texto con CSS (`:has`), sin JavaScript. Los botones de lugar son `input type="radio"` accesibles con teclado. Probar en un celular real con brillo bajo y en el proyector del aula.
* Dependencias con otras historias de usuario: US-22, US-33, US-41, US-42.
* Referencias a documentación externa: Archivos src/views/dispositivos/vincular-confirmar.php, src/views/partials/lugares.php y public/css/app.css. Rama `feature/h8-us-48-vinculacion-simple`.
