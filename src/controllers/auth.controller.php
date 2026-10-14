<?php

// Responsabilidad: pantallas y acciones de registro, login y logout.

require_once __DIR__ . "/../services/auth.service.php";
require_once __DIR__ . "/../utils/render.php";

/**
 * GET /login  (acepta ?volver=/ruta para regresar después de ingresar)
 */
function auth_mostrar_login_controller($renderer, $request, $response)
{
    if (usuario_actual_service() !== null) {
        return redirigir_util($response, "/panel");
    }

    return view($renderer, $response, "auth/login.php", [
        "titulo" => "Ingresar — Energhost",
        "error" => null,
        "email" => "",
        "volver" => ruta_volver_controller($request->getQueryParams()["volver"] ?? ""),
        "aviso" => tomar_aviso_util(),
    ]);
}

/**
 * POST /login
 */
function auth_iniciar_sesion_controller($renderer, $request, $response)
{
    $datos = $request->getParsedBody() ?? [];
    $email = trim((string) ($datos["email"] ?? ""));
    $volver = ruta_volver_controller((string) ($datos["volver"] ?? ""));

    try {
        if (iniciar_sesion_service($email, (string) ($datos["clave"] ?? ""))) {
            return redirigir_util($response, $volver !== "" ? $volver : "/panel");
        }
        $error = "Email o contraseña incorrectos.";
    } catch (RuntimeException $e) {
        $error = "No pudimos conectarnos con el servidor. Probá de nuevo en unos segundos.";
    }

    return view($renderer, $response->withStatus(401), "auth/login.php", [
        "titulo" => "Ingresar — Energhost",
        "error" => $error,
        "email" => $email,
        "volver" => $volver,
        "aviso" => null,
    ]);
}

/**
 * GET /registro
 */
function auth_mostrar_registro_controller($renderer, $response)
{
    if (usuario_actual_service() !== null) {
        return redirigir_util($response, "/panel");
    }

    return view($renderer, $response, "auth/registro.php", [
        "titulo" => "Crear cuenta — Energhost",
        "error" => null,
        "nombre" => "",
        "email" => "",
    ]);
}

/**
 * POST /registro
 */
function auth_registrar_controller($renderer, $request, $response)
{
    $datos = $request->getParsedBody() ?? [];
    $nombre = (string) ($datos["nombre"] ?? "");
    $email = (string) ($datos["email"] ?? "");

    try {
        registrar_usuario_service($nombre, $email, (string) ($datos["clave"] ?? ""), (string) ($datos["confirmacion"] ?? ""));
        guardar_aviso_util("¡Bienvenido/a! Ahora vinculá tu primer equipo con el código de su etiqueta.");
        return redirigir_util($response, "/panel");
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (RuntimeException $e) {
        $error = "No pudimos conectarnos con el servidor. Probá de nuevo en unos segundos.";
    }

    return view($renderer, $response->withStatus(422), "auth/registro.php", [
        "titulo" => "Crear cuenta — Energhost",
        "error" => $error,
        "nombre" => $nombre,
        "email" => $email,
    ]);
}

/**
 * POST /logout
 */
function auth_cerrar_sesion_controller($response)
{
    cerrar_sesion_service();

    return redirigir_util($response, "/");
}

/**
 * Solo se permite volver a rutas internas (evita redirigir a otros sitios).
 */
function ruta_volver_controller(string $ruta): string
{
    return preg_match("#^/[A-Za-z0-9/_\-?=&]*$#", $ruta) && !str_starts_with($ruta, "//") ? $ruta : "";
}
