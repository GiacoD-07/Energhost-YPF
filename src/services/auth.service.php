<?php

// Responsabilidad: registro, inicio y cierre de sesión de los usuarios.
// Las contraseñas nunca se guardan: solo su hash (password_hash).

require_once __DIR__ . "/../persistence/usuarios.persistence.php";

const MINUTOS_SESION = 60;
const ROLES_VALIDOS = ["usuario", "admin"];

/**
 * Crea una cuenta nueva con rol "usuario" e inicia la sesión.
 */
function registrar_usuario_service(string $nombre, string $email, string $clave, string $confirmacion): array
{
    $nombre = trim($nombre);
    $email = strtolower(trim($email));

    if ($nombre === "" || mb_strlen($nombre) > 60) {
        throw new InvalidArgumentException("Ingresá tu nombre (hasta 60 caracteres).");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("El email no es válido.");
    }
    if (mb_strlen($clave) < 8) {
        throw new InvalidArgumentException("La contraseña debe tener al menos 8 caracteres.");
    }
    if ($clave !== $confirmacion) {
        throw new InvalidArgumentException("Las contraseñas no coinciden.");
    }
    if (buscar_uid_por_email_persistence($email) !== null) {
        throw new InvalidArgumentException("Ya existe una cuenta con ese email.");
    }

    $usuario = [
        "nombre" => $nombre,
        "email" => $email,
        "password_hash" => password_hash($clave, PASSWORD_DEFAULT),
        "rol" => "usuario",
        "creado" => firebase_hora_servidor_persistence(),
    ];

    $uid = crear_usuario_persistence($usuario);
    guardar_sesion_service($uid, $usuario);

    return ["uid" => $uid, "nombre" => $nombre];
}

/**
 * Verifica email y contraseña. Si son correctos, inicia la sesión.
 */
function iniciar_sesion_service(string $email, string $clave): bool
{
    $uid = buscar_uid_por_email_persistence($email);
    $usuario = $uid !== null ? obtener_usuario_persistence($uid) : null;

    if ($usuario === null || !password_verify($clave, (string) ($usuario["password_hash"] ?? ""))) {
        return false;
    }

    guardar_sesion_service($uid, $usuario);

    return true;
}

/**
 * Usuario con sesión activa: ["uid", "nombre", "email", "rol"], o null.
 * La sesión vence después de 60 minutos sin uso.
 */
function usuario_actual_service(): ?array
{
    if (empty($_SESSION["usuario"]["uid"])) {
        return null;
    }

    if (time() - (int) ($_SESSION["ultimo_uso"] ?? 0) > MINUTOS_SESION * 60) {
        cerrar_sesion_service();
        return null;
    }

    $_SESSION["ultimo_uso"] = time();

    return $_SESSION["usuario"];
}

function es_admin_service(?array $usuario): bool
{
    return $usuario !== null && ($usuario["rol"] ?? "") === "admin";
}

function cerrar_sesion_service(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Guarda en la sesión los datos mínimos del usuario (nunca el hash).
 */
function guardar_sesion_service(string $uid, array $usuario): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $_SESSION["usuario"] = [
        "uid" => $uid,
        "nombre" => $usuario["nombre"] ?? "",
        "email" => $usuario["email"] ?? "",
        "rol" => in_array($usuario["rol"] ?? "", ROLES_VALIDOS, true) ? $usuario["rol"] : "usuario",
    ];
    $_SESSION["ultimo_uso"] = time();
}
