<?php

// Responsabilidad: armar las respuestas que devuelven los controllers (HTML o JSON).

/**
 * Devuelve una vista de src/views dentro del layout principal.
 * Uso: return view($renderer, $response, "dashboard/index.php", ["titulo" => "Panel"]);
 */
function view($renderer, $response, string $vista, array $datos = [])
{
    return $renderer->render($response, $vista, $datos);
}

/**
 * Devuelve un JSON con su encabezado y código de estado HTTP.
 */
function json_response_util($response, array $datos, int $status = 200)
{
    $response->getBody()->write(json_encode($datos, JSON_UNESCAPED_UNICODE));

    return $response
        ->withHeader("Content-Type", "application/json")
        ->withHeader("Cache-Control", "no-store")
        ->withStatus($status);
}

/**
 * Guarda un aviso para mostrarlo en la próxima página (ej: "Equipo vinculado").
 * $tipo: "ok" o "error".
 */
function guardar_aviso_util(string $texto, string $tipo = "ok"): void
{
    $_SESSION["aviso"] = ["texto" => $texto, "tipo" => $tipo];
}

/**
 * Devuelve el aviso guardado y lo borra, para que se muestre una sola vez.
 */
function tomar_aviso_util(): ?array
{
    $aviso = $_SESSION["aviso"] ?? null;
    unset($_SESSION["aviso"]);

    return $aviso;
}

/**
 * Redirige a otra página de la aplicación (ej: después del login).
 */
function redirigir_util($response, string $ruta)
{
    $base = rtrim(preg_replace("#/index\.php$#", "", $_SERVER["SCRIPT_NAME"] ?? "") ?: "", "/");

    return $response->withHeader("Location", $base . $ruta)->withStatus(302);
}
