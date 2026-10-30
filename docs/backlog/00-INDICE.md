# Backlog EcoSmart Grid – Versión 2.3 (para el repositorio nuevo)

48 historias de usuario listas para cargar en el repo nuevo. Cada `US-XX.md` tiene el texto para pegar en GitHub con la plantilla de issues del repo (la primera línea, el comentario `<!-- ISSUE ... -->`, dice el número y el milestone; no hace falta pegarla).

## Cómo cargarlo en el repo nuevo

1. **Milestones:** crear los 9 de la tabla de abajo.
2. **Etiquetas:** crear `datos`, `firmware`, `backend`, `frontend`, `hardware`, `devops`, `3d` y `docs`.
3. **Issues:** crear una por cada `US-XX.md`, en orden (así el número de issue de GitHub coincide con el de la historia), con su milestone y su etiqueta. Asignar cada una a quien la va a hacer.
4. **Ramas:** con `main` ya subido, correr `crear-ramas.ps1` (crea las 48 de `ramas.txt`).
5. **Al terminar una issue:** Pull Request de su rama a `main` con `Closes #<número>` en la descripción.

## Milestones

| Milestone | Historias |
| --- | --- |
| Milestone 1: Definición de Datos & Firebase | US-01 a US-04 |
| Milestone 2: Firmware IoT & Firebase | US-05 a US-12 |
| Milestone 3: Backend PHP sin Clases & Capas | US-13 a US-21 |
| Milestone 4: Dashboard Web Responsivo & PWA | US-22 a US-27 |
| Milestone 5: Inteligencia de Datos & Cálculos | US-28 a US-31 |
| Milestone 6: Integraciones & DevOps | US-32 a US-35 |
| Milestone 7: Diseño Mecánico & Impresión 3D | US-36 a US-38 |
| Milestone 8: Cuentas, Equipos & Vinculación | US-39 a US-45, US-48 |
| Milestone 9: Documentación & Defensa | US-46 a US-47 |

## Las 48 historias

| Issue | Título | Milestone | Etiqueta | Rama |
| --- | --- | --- | --- | --- |
| US-01 | [DAT-01] Contrato de Datos de Firebase Acordado por el Equipo | 1 | `datos` | `feature/h1-us-01-contrato-datos` |
| US-02 | [DAT-02] Creación del Proyecto Firebase y Cuenta de los Equipos | 1 | `datos` | `feature/h1-us-02-proyecto-firebase` |
| US-03 | [DAT-03] Reglas de Seguridad de Firebase Realtime Database | 1 | `datos` | `feature/h1-us-03-reglas-firebase` |
| US-04 | [DAT-04] Simulador de Equipos para Desarrollo sin Hardware | 1 | `datos` | `feature/h1-us-04-simulador` |
| US-05 | [FW-01] Captura de Variables Eléctricas mediante PZEM-004T | 2 | `firmware` | `feature/h2-us-05-lectura-pzem` |
| US-06 | [FW-02] Conmutación Física de los 3 Relés según Firebase | 2 | `firmware` | `feature/h2-us-06-reles-tomas` |
| US-07 | [FW-03] Corte por Sobreconsumo con Bloqueo hasta Rearme | 2 | `firmware` | `feature/h2-us-07-corte-sobreconsumo` |
| US-08 | [FW-04] Publicación de Lecturas, Estado e Historial en Firebase | 2 | `firmware` | `feature/h2-us-08-publicacion-firebase` |
| US-09 | [FW-05] Portal de Configuración Wi-Fi Dinámica (Wi-Fi Manager) | 2 | `firmware` | `feature/h2-us-09-wifi-manager` |
| US-10 | [FW-06] Pantalla OLED con Métricas y Datos de Vinculación | 2 | `firmware` | `feature/h2-us-10-pantalla-oled` |
| US-11 | [FW-07] Contingencia y Almacenamiento Local en Flash (LittleFS) | 2 | `firmware` | `feature/h2-us-11-respaldo-flash` |
| US-12 | [FW-08] Sincronización del Historial Pendiente tras la Reconexión | 2 | `firmware` | `feature/h2-us-12-sincronizacion-historial` |
| US-13 | [API-01] Estructura Base: Bootstrap y Rutas en src/routes/ | 3 | `backend` | `feature/h3-us-13-bootstrap-rutas` |
| US-14 | [API-02] Persistencia Base de Firebase con cURL en firebase.persistence.php | 3 | `backend` | `feature/h3-us-14-firebase-persistence` |
| US-15 | [API-03] Persistencia de Mediciones en mediciones.persistence.php | 3 | `backend` | `feature/h3-us-15-mediciones-persistence` |
| US-16 | [API-04] Persistencia de los Relés en reles.persistence.php | 3 | `backend` | `feature/h3-us-16-reles-persistence` |
| US-17 | [API-05] Servicio de Mediciones: Estado de Conexión y Resumen por Equipo | 3 | `backend` | `feature/h3-us-17-mediciones-service` |
| US-18 | [API-06] Servicio de Relés: Validación, Rearme y Registro de Actividad | 3 | `backend` | `feature/h3-us-18-reles-service` |
| US-19 | [API-07] Controller y Rutas API de Mediciones por Equipo | 3 | `backend` | `feature/h3-us-19-api-mediciones` |
| US-20 | [API-08] Controller y Rutas API de los Relés por Equipo | 3 | `backend` | `feature/h3-us-20-api-reles` |
| US-21 | [API-09] Refactor: Eliminar Clases, MySQL y Middlewares del Código Actual | 3 | `backend` | `feature/h3-us-21-refactor-sin-clases` |
| US-22 | [UI-01] Vistas con Layout y Diseño Unificado | 4 | `frontend` | `feature/h4-us-22-layout-vistas` |
| US-23 | [UI-02] Actualización Asíncrona y Aviso de Equipo Desconectado | 4 | `frontend` | `feature/h4-us-23-actualizacion-asincrona` |
| US-24 | [UI-03] Encendido y Apagado de cada Toma desde el Panel | 4 | `frontend` | `feature/h4-us-24-encendido-tomas` |
| US-25 | [UI-04] Gráfico del Historial de Consumo del Equipo | 4 | `frontend` | `feature/h4-us-25-grafico-historial` |
| US-26 | [UI-05] Instalación PWA y Soporte Offline (manifest.json / sw.js) | 4 | `frontend` | `feature/h4-us-26-pwa` |
| US-27 | [UI-06] Inicio de Sesión y Roles | 4 | `frontend` | `feature/h4-us-27-login-roles` |
| US-28 | [SRV-01] Cálculo de Huella de Carbono con el Factor Oficial | 5 | `backend` | `feature/h5-us-28-huella-carbono` |
| US-29 | [SRV-02] Estimación de Costo en Pesos con Tarifa Real | 5 | `backend` | `feature/h5-us-29-costo-pesos` |
| US-30 | [SRV-03] Detección de Consumo Vampiro Sostenido | 5 | `backend` | `feature/h5-us-30-consumo-vampiro` |
| US-31 | [SRV-04] Alertas de Sobreconsumo y Tensión Fuera de Rango | 5 | `backend` | `feature/h5-us-31-alertas` |
| US-32 | [INT-01] Respaldo del Historial en Google Sheets | 6 | `backend` | `feature/h6-us-32-google-sheets` |
| US-33 | [INT-02] Etiqueta con QR de Vinculación | 6 | `hardware` | `feature/h6-us-33-etiqueta-qr` |
| US-34 | [DEV-01] Despliegue Continuo (CI/CD) con GitHub Actions (deploy.yml) | 6 | `devops` | `feature/h6-us-34-ci-cd` |
| US-35 | [DEV-02] Limpieza del Repositorio y README Completo | 6 | `devops` | `feature/h6-us-35-readme` |
| US-36 | [3D-01] Modelado CAD de Gabinete Estanco para Protección Eléctrica 220V | 7 | `3d` | `feature/h7-us-36-gabinete-cad` |
| US-37 | [3D-02] Generación de GCODE y Parámetros de Slicing para Impresión FDM | 7 | `3d` | `feature/h7-us-37-gcode-slicing` |
| US-38 | [3D-03] Pausa Programada (M600) para Rotulado y Etiquetado Bicolor | 7 | `3d` | `feature/h7-us-38-pausa-m600` |
| US-39 | [FW-09] Identidad del Equipo: ID por MAC y Código de Vinculación | 8 | `firmware` | `feature/h8-us-39-identidad-equipo` |
| US-40 | [ACC-01] Registro de Usuarios | 8 | `backend` | `feature/h8-us-40-registro-usuarios` |
| US-41 | [ACC-02] Vinculación de Equipos con Código (web y API) | 8 | `backend` | `feature/h8-us-41-vinculacion` |
| US-42 | [ACC-03] Mis Dispositivos: Lista, Nombre y Desvinculación | 8 | `frontend` | `feature/h8-us-42-mis-dispositivos` |
| US-43 | [ACC-04] Registro de Actividad de cada Equipo | 8 | `backend` | `feature/h8-us-43-actividad` |
| US-44 | [ACC-05] Administración de Usuarios | 8 | `backend` | `feature/h8-us-44-admin-usuarios` |
| US-45 | [UI-07] Presentación Pública como Página de Entrada | 8 | `frontend` | `feature/h8-us-45-presentacion` |
| US-46 | [DOC-01] Carpeta Final del Proyecto | 9 | `docs` | `feature/h9-us-46-carpeta-final` |
| US-47 | [DOC-02] Defensa Oral y Demostración en Vivo | 9 | `docs` | `feature/h9-us-47-defensa` |
| US-48 | [UI-08] Vinculación en 3 Toques y Lectura Cómoda para Todas las Edades | 8 | `frontend` | `feature/h8-us-48-vinculacion-simple` |

## Qué cambió en la versión 2.3 (versión Fabricio)

A partir de la devolución de los profesores sobre la vinculación ("un endpoint protegido por sesión que reciba la ID o la MAC" y "que lo pueda usar Susana, de sesenta y pico").

| Issue | Qué cambió |
| --- | --- |
| US-41 | Endpoint `POST /api/dispositivos/{id}/vincular` protegido por sesión; acepta la MAC completa; mensajes en lenguaje simple |
| US-42 | Lista vacía con 3 pasos y botón "Agregar mi primer equipo" |
| US-48 | **Nueva:** vinculación en 3 toques con el QR y lectura cómoda para todas las edades |

La versión 2.2 había pasado a 3 tomas por equipo (un relé por toma) y la 2.1 sumó la carpeta final y la defensa (US-46 y US-47).

## Valores que usan las historias

| Dato | Valor |
| --- | --- |
| Identificador del equipo | `EcoSmart_` + últimos 6 caracteres de la MAC |
| Código de vinculación | 6 cifras, generado por el equipo |
| Relés por equipo | 3 (uno por toma: `rele_1` GPIO18, `rele_2` GPIO19, `rele_3` GPIO21) |
| Medición | Un PZEM-004T: total de las 3 tomas |
| Umbral de sobreconsumo | 2500 W |
| Consumo fantasma | 0,5 a 15 W |
| Consumo vampiro | fantasma sostenido más de 2 h |
| Equipo desconectado | más de 30 s sin señal |
| Factor de CO2 | 0,283 kg/kWh (SNIGEI-AR, dato 2022) |
| Intentos de vinculación | 5, después 10 minutos de bloqueo |
| Sesión | vence a los 60 minutos sin uso |
