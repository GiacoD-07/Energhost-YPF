<?php

// Responsabilidad: respuestas JSON con las mediciones de un equipo.
// Antes de responder, verifica que el equipo sea del usuario con sesión.

require_once __DIR__ . "/../services/mediciones.service.php";
require_once __DIR__ . "/../services/dispositivos.service.php";
require_once __DIR__ . "/../services/auth.service.php";
require_once __DIR__ . "/../utils/render.php";

/**
 * GET /api/dispositivos/{id}/ultima - Última lectura, estado de conexión y alertas.
 */
function api_obtener_ultima_medicion_controller($response, string $dispositivoId)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return json_response_util($response, ["ok" => false, "error" => "Iniciá sesión para continuar"], 401);
    }

    try {
        verificar_acceso_dispositivo_service($usuario, $dispositivoId);
        return json_response_util($response, ["ok" => true] + obtener_resumen_actual_service($dispositivoId));
    } catch (DomainException $e) {
        return json_response_util($response, ["ok" => false, "error" => $e->getMessage()], 404);
    } catch (RuntimeException $e) {
        return json_response_util($response, ["ok" => false, "error" => "No se pudieron obtener las mediciones"], 503);
    }
}

/**
 * GET /api/dispositivos/{id}/historial?limite=120 - Lecturas por minuto y consumo vampiro.
 */
function api_obtener_historial_controller($request, $response, string $dispositivoId)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return json_response_util($response, ["ok" => false, "error" => "Iniciá sesión para continuar"], 401);
    }

    $limite = filter_var($request->getQueryParams()["limite"] ?? 120, FILTER_VALIDATE_INT) ?: 120;

    try {
        verificar_acceso_dispositivo_service($usuario, $dispositivoId);
        $historial = obtener_historial_service($dispositivoId, $limite);

        return json_response_util($response, [
            "ok" => true,
            "mediciones" => $historial,
            "consumo_vampiro" => detectar_consumo_vampiro_service($historial),
            "mensaje" => $historial === [] ? "Todavía no hay datos de historial para mostrar" : null,
        ]);
    } catch (DomainException $e) {
        return json_response_util($response, ["ok" => false, "error" => $e->getMessage()], 404);
    } catch (RuntimeException $e) {
        return json_response_util($response, ["ok" => false, "error" => "No se pudieron obtener las mediciones"], 503);
    }
}
