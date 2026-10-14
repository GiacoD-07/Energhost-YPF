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
 * GET /vincular  (acepta ?equipo=A1B2C3&codigo=123456, que vienen del QR de la etiqueta)
 */
function vincular_formulario_controller($renderer, $request, $response)
{
    $usuario = usuario_actual_service();
    $parametros = $request->getQueryParams();
    $equipo = substr(preg_replace("/[^A-Za-z0-9_]/", "", (string) ($parametros["equipo"] ?? "")), 0, 30);
    $codigo = substr(preg_replace("/[^0-9]/", "", (string) ($parametros["codigo"] ?? "")), 0, 6);

    if ($usuario === null) {
        // Si escanearon el QR sin cuenta, primero se registran o ingresan y después vuelven acá
        $volver = "/vincular?equipo={$equipo}&codigo={$codigo}";
        guardar_aviso_util("Ingresá o creá tu cuenta para vincular el equipo.");
        return redirigir_util($response, "/login?volver=" . rawurlencode($volver));
    }

    return view($renderer, $response, "dispositivos/vincular.php", [
        "titulo" => "Vincular equipo — Energhost",
        "usuario" => $usuario,
        "equipo" => $equipo,
        "codigo" => $codigo,
        "nombre" => "",
        "nombresTomas" => leer_nombres_tomas_controller([]),
        "error" => null,
    ]);
}

/**
 * POST /vincular
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
    $nombre = (string) ($datos["nombre"] ?? "");
    $nombresTomas = leer_nombres_tomas_controller($datos);

    try {
        $dispositivoId = vincular_dispositivo_service($usuario, $equipo, $codigo, $nombre, $nombresTomas);
        guardar_aviso_util("¡Listo! \"" . trim($nombre) . "\" ya está en tu cuenta.");
        return redirigir_util($response, "/panel/" . rawurlencode($dispositivoId));
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (RuntimeException $e) {
        $error = "No pudimos conectarnos con el servidor. Probá de nuevo en unos segundos.";
    }

    return view($renderer, $response->withStatus(422), "dispositivos/vincular.php", [
        "titulo" => "Vincular equipo — Energhost",
        "usuario" => $usuario,
        "equipo" => $equipo,
        "codigo" => "",
        "nombre" => $nombre,
        "nombresTomas" => $nombresTomas,
        "error" => $error,
    ]);
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
