<?php

// Responsabilidad: guardar y leer los usuarios en Firebase.
//
//   usuarios/<uid>                 nombre, email, password_hash, rol, creado, dispositivos/<id>: true
//   usuarios_por_email/<clave>     uid   (índice para buscar a un usuario por su email)
//
// Firebase no admite puntos en las claves, así que el email se guarda en el índice
// con una clave calculada (sha1 del email en minúsculas).

require_once __DIR__ . "/firebase.persistence.php";

function clave_email_persistence(string $email): string
{
    return sha1(strtolower(trim($email)));
}

/**
 * Crea el usuario y su entrada en el índice por email. Devuelve el uid generado.
 */
function crear_usuario_persistence(array $usuario): string
{
    $respuesta = firebase_agregar_persistence("usuarios", $usuario);
    $uid = $respuesta["name"] ?? "";

    if ($uid === "") {
        throw new RuntimeException("Error de comunicación con Firebase");
    }

    firebase_escribir_persistence("usuarios_por_email/" . clave_email_persistence($usuario["email"]), $uid);

    return $uid;
}

function buscar_uid_por_email_persistence(string $email): ?string
{
    $uid = firebase_leer_persistence("usuarios_por_email/" . clave_email_persistence($email));

    return is_string($uid) && $uid !== "" ? $uid : null;
}

function obtener_usuario_persistence(string $uid): ?array
{
    $usuario = firebase_leer_persistence("usuarios/{$uid}");

    return is_array($usuario) ? $usuario : null;
}

/**
 * Cambia solo los campos enviados (por ejemplo el rol o el hash de la contraseña).
 */
function actualizar_usuario_persistence(string $uid, array $campos): void
{
    firebase_actualizar_persistence("usuarios/{$uid}", $campos);
}

/**
 * Todos los usuarios, indexados por uid. Solo lo usa la vista de administración.
 */
function listar_usuarios_persistence(): array
{
    $usuarios = firebase_leer_persistence("usuarios");

    return is_array($usuarios) ? $usuarios : [];
}

/**
 * Ids de los dispositivos vinculados a un usuario.
 */
function listar_ids_dispositivos_usuario_persistence(string $uid): array
{
    $ids = firebase_leer_persistence("usuarios/{$uid}/dispositivos");

    return is_array($ids) ? array_keys($ids) : [];
}

function agregar_dispositivo_a_usuario_persistence(string $uid, string $dispositivoId): void
{
    firebase_actualizar_persistence("usuarios/{$uid}/dispositivos", [$dispositivoId => true]);
}

function quitar_dispositivo_de_usuario_persistence(string $uid, string $dispositivoId): void
{
    firebase_borrar_persistence("usuarios/{$uid}/dispositivos/{$dispositivoId}");
}
