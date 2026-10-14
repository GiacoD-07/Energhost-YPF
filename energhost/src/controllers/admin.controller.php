<?php

// Responsabilidad: pantalla de administración de usuarios (rol "admin").

require_once __DIR__ . "/../services/usuarios.service.php";
require_once __DIR__ . "/../utils/render.php";

/**
 * GET /admin/usuarios
 */
function admin_usuarios_controller($renderer, $response)
{
    $usuario = usuario_actual_service();
    if ($usuario === null) {
        return redirigir_util($response, "/login?volver=/admin/usuarios");
    }

    try {
        $usuarios = listar_usuarios_service($usuario);
    } catch (DomainException $e) {
        guardar_aviso_util($e->getMessage(), "error");
        return redirigir_util($response, "/panel");
    } catch (RuntimeException $e) {
        guardar_aviso_util("No pudimos conectarnos con el servidor.", "error");
        return redirigir_util($response, "/panel");
    }

    return view($renderer, $response, "admin/usuarios.php", [
        "titulo" => "Usuarios — Energhost",
        "usuario" => $usuario,
        "usuarios" => $usuarios,
        "aviso" => tomar_aviso_util(),
    ]);
}

/**
 * POST /admin/usuarios/{uid}/rol
 */
function admin_cambiar_rol_controller($request, $response, string $uid)
{
    try {
        cambiar_rol_service(usuario_actual_service(), $uid, (string) (($request->getParsedBody() ?? [])["rol"] ?? ""));
        guardar_aviso_util("Rol actualizado. El cambio se aplica cuando la persona vuelva a ingresar.");
    } catch (InvalidArgumentException | DomainException $e) {
        guardar_aviso_util($e->getMessage(), "error");
    } catch (RuntimeException $e) {
        guardar_aviso_util("No pudimos conectarnos con el servidor.", "error");
    }

    return redirigir_util($response, "/admin/usuarios");
}

/**
 * POST /admin/usuarios/{uid}/clave - Genera una contraseña temporal.
 */
function admin_resetear_clave_controller($response, string $uid)
{
    try {
        $temporal = resetear_clave_service(usuario_actual_service(), $uid);
        guardar_aviso_util("Contraseña temporal generada: {$temporal} — pasásela a la persona; se muestra una sola vez.");
    } catch (InvalidArgumentException | DomainException $e) {
        guardar_aviso_util($e->getMessage(), "error");
    } catch (RuntimeException $e) {
        guardar_aviso_util("No pudimos conectarnos con el servidor.", "error");
    }

    return redirigir_util($response, "/admin/usuarios");
}
