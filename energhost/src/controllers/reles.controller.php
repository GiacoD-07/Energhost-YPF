<?php

// Responsabilidad: respuestas JSON para consultar y cambiar los relés de un equipo.

require_once __DIR__ . "/../services/reles.service.php";
require_once __DIR__ . "/../services/auth.service.php";
require_once __DIR__ . "/../utils/render.php";

/**
 * GET /api/dispositivos/{id}/reles
 */
function api_obtener_reles_controller($response, string $dispositivoId)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return json_response_util($response, ["ok" => false, "error" => "Iniciá sesión para continuar"], 401);
    }

    try {
        return json_response_util($response, ["ok" => true, "reles" => obtener_reles_service($usuario, $dispositivoId)]);
    } catch (DomainException $e) {
        return json_response_util($response, ["ok" => false, "error" => $e->getMessage()], 404);
    } catch (RuntimeException $e) {
        return json_response_util($response, ["ok" => false, "error" => "No se pudo obtener el estado de los relés"], 503);
    }
}

/**
 * POST /api/dispositivos/{id}/reles - Cuerpo: {"rele": 1, "estado": 0}
 */
function api_cambiar_rele_controller($request, $response, string $dispositivoId)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return json_response_util($response, ["ok" => false, "error" => "Iniciá sesión para continuar"], 401);
    }

    $datos = $request->getParsedBody();
    if (!is_array($datos)) {
        return json_response_util($response, ["ok" => false, "error" => "Formato JSON inválido en el cuerpo de la solicitud"], 400);
    }

    try {
        cambiar_rele_service($usuario, $dispositivoId, $datos["rele"] ?? null, $datos["estado"] ?? null);
        return json_response_util($response, ["ok" => true]);
    } catch (InvalidArgumentException $e) {
        return json_response_util($response, ["ok" => false, "error" => $e->getMessage()], 422);
    } catch (DomainException $e) {
        return json_response_util($response, ["ok" => false, "error" => $e->getMessage()], 404);
    } catch (RuntimeException $e) {
        return json_response_util($response, ["ok" => false, "error" => "No se pudo cambiar el relé"], 503);
    }
}
