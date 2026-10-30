<?php

// Responsabilidad: pantallas y acciones de "Mis dispositivos": lista, detalle,
// vincular, renombrar, desvincular y actividad.

require_once __DIR__ . "/../services/auth.service.php";
require_once __DIR__ . "/../services/dispositivos.service.php";
require_once __DIR__ . "/../services/reles.service.php";
require_once __DIR__ . "/../utils/render.php";
require_once __DIR__ . "/../utils/config.php";

/**
 * Lee los campos toma_1, toma_2 y toma_3 del formulario: [1 => "Heladera", 2 => "", 3 => "Tele"].
 */
function leer_nombres_tomas_controller(array $datos): array
{
    $nombres = [];
    for ($numero = 1; $numero <= CANTIDAD_TOMAS; $numero++) {
        $nombres[$numero] = (string) ($datos["toma_{$numero}"] ?? "");
    }

    return $nombres;
}

/**
 * GET /panel - Lista de los equipos del usuario.
 */
function panel_dispositivos_controller($renderer, $request, $response)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return redirigir_util($response, "/login?volver=/panel");
    }

    try {
        $dispositivos = listar_mis_dispositivos_service($usuario);
        $error = null;
    } catch (RuntimeException $e) {
        $dispositivos = [];
        $error = "No pudimos conectarnos con el servidor. Probá de nuevo en unos segundos.";
    }

    return view($renderer, $response, "dispositivos/index.php", [
        "titulo" => "Mis dispositivos — Energhost",
        "usuario" => $usuario,
        "dispositivos" => $dispositivos,
        "error" => $error,
        "aviso" => tomar_aviso_util(),
    ]);
}

/**
 * GET /panel/{id} - Panel de un equipo: métricas, relés, historial y actividad.
 */
function panel_detalle_controller($renderer, $response, string $dispositivoId)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return redirigir_util($response, "/login?volver=/panel/" . rawurlencode($dispositivoId));
    }

    try {
        $info = verificar_acceso_dispositivo_service($usuario, $dispositivoId);
    } catch (DomainException $e) {
        guardar_aviso_util("Ese equipo no existe o no está en tu cuenta.", "error");
        return redirigir_util($response, "/panel");
    } catch (RuntimeException $e) {
        guardar_aviso_util("No pudimos conectarnos con el servidor.", "error");
        return redirigir_util($response, "/panel");
    }

    try {
        $cantidadReles = cantidad_reles_service($info);
        $nombresTomas = nombres_tomas_service($dispositivoId, $cantidadReles);
    } catch (RuntimeException $e) {
        guardar_aviso_util("No pudimos conectarnos con el servidor.", "error");
        return redirigir_util($response, "/panel");
    }

    return view($renderer, $response, "dispositivos/detalle.php", [
        "titulo" => ($info["nombre"] ?? $dispositivoId) . " — Energhost",
        "usuario" => $usuario,
        "dispositivoId" => $dispositivoId,
        "nombre" => $info["nombre"] ?? $dispositivoId,
        "cantidadReles" => $cantidadReles,
        "nombresTomas" => $nombresTomas,
        "esDuenio" => ($info["duenio"] ?? null) === $usuario["uid"],
        "umbralSobreconsumo" => leer_config_numero_util("UMBRAL_SOBRECONSUMO_W", 2500),
        "aviso" => tomar_aviso_util(),
    ]);
}

/**
 * Lee el lugar elegido con los botones (Cocina, Living...). Si eligió "Otro", usa lo que escribió.
 * También acepta el campo "nombre", que es el que usa la API.
 */
function leer_lugar_controller(array $datos): string
{
    $lugar = trim((string) ($datos["lugar"] ?? ""));
    if ($lugar === "otro") {
        return trim((string) ($datos["lugar_otro"] ?? ""));
    }

    return $lugar !== "" ? $lugar : trim((string) ($datos["nombre"] ?? ""));
}

/**
 * Datos que necesitan las dos pantallas de vinculación.
 */
function datos_vista_vincular_controller(array $usuario, array $extra): array
{
    return array_merge([
        "titulo" => "Agregar equipo — Energhost",
        "usuario" => $usuario,
        "equipo" => "",
        "codigo" => "",
        "lugar" => "",
        "lugarOtro" => "",
        "lugares" => LUGARES_SUGERIDOS,
        "nombresTomas" => leer_nombres_tomas_controller([]),
        "error" => null,
    ], $extra);
}

/**
 * GET /vincular  (acepta ?equipo=A1B2C3&codigo=123456, que vienen del QR de la etiqueta)
 * Si el QR trae datos correctos, muestra directamente "Encontramos tu equipo" y solo falta
 * elegir el lugar. Si no, muestra el formulario para escribir los datos a mano.
 */
function vincular_formulario_controller($renderer, $request, $response)
{
    $usuario = usuario_actual_service();
    $parametros = $request->getQueryParams();
    $equipo = substr(preg_replace("/[^A-Za-z0-9_:\-]/", "", (string) ($parametros["equipo"] ?? "")), 0, 30);
    $codigo = substr(preg_replace("/[^0-9]/", "", (string) ($parametros["codigo"] ?? "")), 0, 6);

    if ($usuario === null) {
        // Si escanearon el QR sin cuenta, primero se registran o ingresan y después vuelven acá
        $volver = "/vincular?equipo=" . rawurlencode($equipo) . "&codigo={$codigo}";
        guardar_aviso_util("Para vincular tu equipo, ingresá o creá tu cuenta. Es un minuto.");
        return redirigir_util($response, "/login?volver=" . rawurlencode($volver));
    }

    if ($equipo !== "" && $codigo !== "") {
        try {
            $dispositivoId = revisar_vinculacion_service($usuario, $equipo, $codigo);
            return view($renderer, $response, "dispositivos/vincular-confirmar.php", datos_vista_vincular_controller($usuario, [
                "equipo" => $equipo,
                "codigo" => $codigo,
                "dispositivoId" => $dispositivoId,
            ]));
        } catch (InvalidArgumentException $e) {
            return view($renderer, $response, "dispositivos/vincular.php", datos_vista_vincular_controller($usuario, [
                "equipo" => $equipo,
                "error" => $e->getMessage(),
            ]));
        } catch (RuntimeException $e) {
            return view($renderer, $response, "dispositivos/vincular.php", datos_vista_vincular_controller($usuario, [
                "equipo" => $equipo,
                "error" => "No pudimos conectarnos con el servidor. Probá de nuevo en unos segundos.",
            ]));
        }
    }

    return view($renderer, $response, "dispositivos/vincular.php", datos_vista_vincular_controller($usuario, [
        "equipo" => $equipo,
    ]));
}

/**
 * POST /vincular - Lo envían las dos pantallas (la del QR y la manual).
 */
function vincular_dispositivo_controller($renderer, $request, $response)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return redirigir_util($response, "/login?volver=/vincular");
    }

    $datos = $request->getParsedBody() ?? [];
    $equipo = (string) ($datos["equipo"] ?? "");
    $codigo = (string) ($datos["codigo"] ?? "");
    $lugar = leer_lugar_controller($datos);
    $nombresTomas = leer_nombres_tomas_controller($datos);
    $desdeQr = ($datos["paso"] ?? "") === "confirmar";

    try {
        $dispositivoId = vincular_dispositivo_service($usuario, $equipo, $codigo, $lugar, $nombresTomas);
        guardar_aviso_util("¡Listo! Tu equipo de \"{$lugar}\" ya está en tu cuenta. Tocá cada toma para prenderla o apagarla.");
        return redirigir_util($response, "/panel/" . rawurlencode($dispositivoId));
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (RuntimeException $e) {
        $error = "No pudimos conectarnos con el servidor. Probá de nuevo en unos segundos.";
    }

    $vista = [
        "equipo" => $equipo,
        "lugar" => trim((string) ($datos["lugar"] ?? "")),
        "lugarOtro" => trim((string) ($datos["lugar_otro"] ?? "")),
        "nombresTomas" => $nombresTomas,
        "error" => $error,
    ];

    // Si venía del QR y el problema es solo el lugar, se queda en la pantalla simple
    if ($desdeQr && $lugar === "") {
        $vista["codigo"] = $codigo;
        $vista["dispositivoId"] = (string) ($datos["dispositivo"] ?? "");
        return view($renderer, $response->withStatus(422), "dispositivos/vincular-confirmar.php", datos_vista_vincular_controller($usuario, $vista));
    }

    return view($renderer, $response->withStatus(422), "dispositivos/vincular.php", datos_vista_vincular_controller($usuario, $vista));
}

/**
 * POST /panel/{id}/nombre - Nombre del equipo y de los artefactos de cada toma.
 */
function renombrar_dispositivo_controller($request, $response, string $dispositivoId)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return redirigir_util($response, "/login");
    }

    try {
        $datos = $request->getParsedBody() ?? [];
        renombrar_dispositivo_service($usuario, $dispositivoId, (string) ($datos["nombre"] ?? ""), leer_nombres_tomas_controller($datos));
        guardar_aviso_util("Nombres actualizados.");
    } catch (InvalidArgumentException | DomainException $e) {
        guardar_aviso_util($e->getMessage(), "error");
    } catch (RuntimeException $e) {
        guardar_aviso_util("No pudimos conectarnos con el servidor.", "error");
    }

    return redirigir_util($response, "/panel/" . rawurlencode($dispositivoId));
}

/**
 * POST /panel/{id}/desvincular
 */
function desvincular_dispositivo_controller($response, string $dispositivoId)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return redirigir_util($response, "/login");
    }

    try {
        desvincular_dispositivo_service($usuario, $dispositivoId);
        guardar_aviso_util("El equipo se quitó de la cuenta. Se puede volver a vincular con su código.");
    } catch (DomainException $e) {
        guardar_aviso_util($e->getMessage(), "error");
    } catch (RuntimeException $e) {
        guardar_aviso_util("No pudimos conectarnos con el servidor.", "error");
    }

    return redirigir_util($response, "/panel");
}

/**
 * GET /api/dispositivos - Resumen de los equipos del usuario (para refrescar la lista).
 */
function api_listar_dispositivos_controller($response)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return json_response_util($response, ["ok" => false, "error" => "Iniciá sesión para continuar"], 401);
    }

    try {
        return json_response_util($response, ["ok" => true, "dispositivos" => listar_mis_dispositivos_service($usuario)]);
    } catch (RuntimeException $e) {
        return json_response_util($response, ["ok" => false, "error" => "No se pudieron obtener los dispositivos"], 503);
    }
}

/**
 * GET /api/dispositivos/{id}/eventos - Actividad del equipo.
 */
function api_eventos_controller($response, string $dispositivoId)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return json_response_util($response, ["ok" => false, "error" => "Iniciá sesión para continuar"], 401);
    }

    try {
        return json_response_util($response, ["ok" => true, "eventos" => listar_eventos_service($usuario, $dispositivoId)]);
    } catch (DomainException $e) {
        return json_response_util($response, ["ok" => false, "error" => $e->getMessage()], 404);
    } catch (RuntimeException $e) {
        return json_response_util($response, ["ok" => false, "error" => "No se pudo obtener la actividad"], 503);
    }
}

/**
 * POST /api/dispositivos/{id}/vincular - Vincula el equipo a la cuenta del usuario que inició sesión.
 * {id} puede ser el identificador (A1B2C3, EcoSmart_A1B2C3) o la MAC completa (24:6F:28:A1:B2:C3).
 * Cuerpo: {"codigo": "483920", "nombre": "Cocina", "tomas": {"1": "Heladera", "2": "Microondas"}}
 * Respuestas: 201 vinculado · 400 JSON inválido · 401 sin sesión · 422 datos incorrectos · 429 bloqueado · 503 sin Firebase
 */
function api_vincular_dispositivo_controller($request, $response, string $textoId)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return json_response_util($response, ["ok" => false, "error" => "Iniciá sesión para continuar"], 401);
    }

    $datos = $request->getParsedBody();
    if (!is_array($datos)) {
        return json_response_util($response, ["ok" => false, "error" => "Formato JSON inválido en el cuerpo de la solicitud"], 400);
    }

    $tomas = is_array($datos["tomas"] ?? null) ? $datos["tomas"] : [];
    $nombresTomas = [];
    for ($numero = 1; $numero <= CANTIDAD_TOMAS; $numero++) {
        $nombresTomas[$numero] = (string) ($tomas[(string) $numero] ?? $tomas[$numero] ?? "");
    }

    try {
        $dispositivoId = vincular_dispositivo_service($usuario, rawurldecode($textoId), (string) ($datos["codigo"] ?? ""), (string) ($datos["nombre"] ?? ""), $nombresTomas);
        return json_response_util($response, ["ok" => true, "dispositivo" => $dispositivoId], 201);
    } catch (InvalidArgumentException $e) {
        $codigoHttp = $e->getMessage() === MENSAJE_BLOQUEO ? 429 : 422;
        return json_response_util($response, ["ok" => false, "error" => $e->getMessage()], $codigoHttp);
    } catch (RuntimeException $e) {
        return json_response_util($response, ["ok" => false, "error" => "No se pudo vincular el equipo"], 503);
    }
}
