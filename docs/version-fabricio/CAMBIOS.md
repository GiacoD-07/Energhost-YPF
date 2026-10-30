# Energhost – Versión Fabricio

Cambios hechos a partir de la devolución de los profesores sobre la vinculación de equipos.

## Lo que pidieron los profesores

> Un endpoint al que se le pase por parámetro la ID (o la MAC) del dispositivo, protegido por
> autenticación: si no iniciaste sesión, no lo podés usar. Con la sesión y la ID ya se sabe que ese
> usuario es el propietario, y se hace la consulta a Firebase para vincularlos.
> Tiene que ser intuitivo: que lo pueda usar "Susana", de sesenta y pico, porque le viene cara la luz.

## Qué se cambió

| # | Cambio | Dónde |
|---|---|---|
| 1 | **La MAC completa sirve como ID.** `24:6F:28:A1:B2:C3`, `24-6f-28-a1-b2-c3` o `246F28A1B2C3` se convierten en `EcoSmart_A1B2C3`, igual que en el firmware (últimos 6 caracteres) | `normalizar_id_dispositivo_service()` en `src/services/dispositivos.service.php` |
| 2 | **Endpoint de la API** `POST /api/dispositivos/{id}/vincular`, protegido por sesión. Responde 201, o 400 / 401 / 422 / 429 / 503 | `src/routes/api.routes.php` y `api_vincular_dispositivo_controller()` |
| 3 | **Vinculación en 3 toques con el QR:** escanear → "¡Encontramos tu equipo!" → elegir el lugar con botones grandes → "Vincular mi equipo". Los nombres de las tomas quedan para después | `src/views/dispositivos/vincular-confirmar.php`, `src/views/partials/lugares.php`, `vincular_formulario_controller()` |
| 4 | **Formulario manual en 3 pasos numerados**, con el consejo de usar el QR arriba y las tomas como opcional plegado | `src/views/dispositivos/vincular.php` |
| 5 | **Mensajes en lenguaje simple** (ver tabla de abajo) y "Agregar equipo" en lugar de "Vincular" en menús y botones | constantes `MENSAJE_*` en `dispositivos.service.php`, vistas |
| 6 | **"Mis dispositivos" vacío** explica 3 pasos: enchufar, conectar al Wi-Fi, escanear el QR | `src/views/dispositivos/index.php` |
| 7 | **Lectura cómoda:** texto de 17 px, grises más claros, títulos de grosor 400 (no tan finos), botones y campos de 48–56 px de alto, interruptores más grandes | final de `public/css/app.css` y `public/css/landing.css` |
| 8 | Se separó el control (`revisar_vinculacion_service()`) de la escritura (`vincular_dispositivo_service()`), así el QR se revisa antes de mostrar la pantalla de confirmación | `src/services/dispositivos.service.php` |

### Por qué se mantiene el código de 6 cifras

Los profesores hablan solo de la ID. Se mantiene el código porque la ID sale de la MAC, y la MAC
**no es secreta** (se ve desde el Wi-Fi y se puede adivinar). Solo con la ID, cualquiera con una
cuenta podría adueñarse del equipo de otra persona y apagarle la heladera. Para el usuario no
cambia nada: con el QR no escribe ni la ID ni el código.

> La ID dice **qué** equipo es; el código prueba que **lo tenés vos**.

### Mensajes nuevos

| Situación | Mensaje |
|---|---|
| ID mal escrita | Revisá el identificador: son las 6 letras y números de la etiqueta del equipo (por ejemplo A1B2C3). |
| El equipo nunca se conectó | No encontramos ese equipo. Fijate que esté enchufado y conectado al Wi-Fi, esperá un minuto y probá de nuevo. |
| Código incorrecto | El código no coincide. Revisá los 6 números de la etiqueta del equipo. |
| Ya es suyo | Este equipo ya está en tu cuenta. |
| Es de otra persona | Este equipo ya está en la cuenta de otra persona. Si te lo dieron usado, pedile que lo quite de su cuenta. |
| 5 intentos fallidos | Probaste muchas veces. Esperá 10 minutos y volvé a intentar. |
| Falta el lugar | Elegí o escribí el lugar del equipo. |
| Vinculado | ¡Listo! Tu equipo de "Cocina" ya está en tu cuenta. Tocá cada toma para prenderla o apagarla. |

## Cómo probarlo

```powershell
composer install
copy .env.example .env      # FIREBASE_URL=http://127.0.0.1:9100 y FIREBASE_AUTH=local para probar sin Firebase real
composer firebase-local     # terminal 1
composer serve              # terminal 2
php scripts/simulador.php --equipo=A1B2C3   # terminal 3: muestra el link del QR
```

1. Abrir el link del QR que imprime el simulador → pide ingresar → crear cuenta → aparece "¡Encontramos tu equipo!".
2. Elegir "Cocina" y tocar **Vincular mi equipo**.
3. Probar a mano en **Agregar equipo** con la MAC `24:6F:28:A1:B2:C3` (otro equipo simulado) y un código incorrecto para ver los mensajes.
4. API (con la sesión iniciada en el navegador o con la cookie):
   `POST /api/dispositivos/24:6F:28:A1:B2:C3/vincular` con `{"codigo": "...", "nombre": "Cocina"}`.

## Pruebas hechas (simulador + Firebase de prueba)

18 comprobaciones automáticas, todas correctas: QR sin sesión manda a ingresar y vuelve; QR válido
muestra la confirmación; sin lugar se queda en la misma pantalla; vincula y llega al panel; "Otro
lugar" muestra el campo; código incorrecto con mensaje simple y sin perder lo escrito; vinculación con
MAC completa; equipo de otra cuenta; API 401 / 400 / 422 / 201 / repetido; bloqueo 429 tras 5 intentos.
Falta probar con el ESP32 real.

## Backlog

En `docs/version-fabricio/issues/`: **US-41** (modificada: API, MAC y mensajes), **US-42** (modificada:
"Agregar mi primer equipo") y **US-48** (nueva: vinculación en 3 toques y lectura cómoda), con rama
`feature/h8-us-48-vinculacion-simple`. El Milestone 8 pasa a ser "US-39 a US-45, US-48".

El backlog completo para el repo nuevo (48 historias, milestones, etiquetas y ramas) está en `docs/backlog/`: empezar por `docs/backlog/00-INDICE.md`.
