<?php

// Responsabilidad: administración de usuarios (solo para el rol "admin").

require_once __DIR__ . "/auth.service.php";
require_once __DIR__ . "/../persistence/usuarios.persistence.php";

function exigir_admin_service(?array $usuario): void
{
    if (!es_admin_service($usuario)) {
        throw new DomainException("Acceso solo para administradores.");
    }
}

/**
 * Lista de usuarios para la tabla de administración (sin hashes).
 */
function listar_usuarios_service(?array $admin): array
{
    exigir_admin_service($admin);

    $lista = [];
    foreach (listar_usuarios_persistence() as $uid => $usuario) {
        $lista[] = [
            "uid" => $uid,
            "nombre" => $usuario["nombre"] ?? "",
            "email" => $usuario["email"] ?? "",
            "rol" => $usuario["rol"] ?? "usuario",
            "dispositivos" => is_array($usuario["dispositivos"] ?? null) ? count($usuario["dispositivos"]) : 0,
            "creado" => (int) ($usuario["creado"] ?? 0),
        ];
    }

    usort($lista, fn ($a, $b) => strcasecmp($a["nombre"], $b["nombre"]));

    return $lista;
}

function cambiar_rol_service(?array $admin, string $uid, string $rol): void
{
    exigir_admin_service($admin);

    if (!in_array($rol, ROLES_VALIDOS, true)) {
        throw new InvalidArgumentException("Rol no válido.");
    }
    if ($uid === $admin["uid"]) {
        throw new InvalidArgumentException("No podés cambiar tu propio rol.");
    }
    if (obtener_usuario_persistence($uid) === null) {
        throw new InvalidArgumentException("El usuario no existe.");
    }

    actualizar_usuario_persistence($uid, ["rol" => $rol]);
}

/**
 * Genera una contraseña temporal para un usuario que olvidó la suya.
 * Se muestra una sola vez al administrador, que se la pasa al usuario.
 */
function resetear_clave_service(?array $admin, string $uid): string
{
    exigir_admin_service($admin);

    if (obtener_usuario_persistence($uid) === null) {
        throw new InvalidArgumentException("El usuario no existe.");
    }

    $temporal = substr(str_replace(["+", "/", "="], "", base64_encode(random_bytes(9))), 0, 10);
    actualizar_usuario_persistence($uid, ["password_hash" => password_hash($temporal, PASSWORD_DEFAULT)]);

    return $temporal;
}
