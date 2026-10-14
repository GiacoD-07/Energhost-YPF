<?php

// Responsabilidad: leer y cambiar los datos de identidad de cada dispositivo en Firebase.
//
//   dispositivos/<id>/info           nombre, duenio, mac, cantidad_reles, creado   (el ESP32 crea mac y cantidad_reles)
//   dispositivos/<id>/tomas          toma_1, toma_2, toma_3: qué artefacto está enchufado en cada toma (lo escribe el panel)
//   dispositivos/<id>/vinculacion    codigo   (lo genera el ESP32 la primera vez que arranca)

require_once __DIR__ . "/firebase.persistence.php";

/**
 * Datos de identidad del dispositivo, o null si nunca se conectó.
 */
function obtener_info_dispositivo_persistence(string $dispositivoId): ?array
{
    $info = firebase_leer_persistence("dispositivos/{$dispositivoId}/info");

    return is_array($info) ? $info : null;
}

function obtener_codigo_vinculacion_persistence(string $dispositivoId): ?string
{
    $codigo = firebase_leer_persistence("dispositivos/{$dispositivoId}/vinculacion/codigo");

    return $codigo === null ? null : (string) $codigo;
}

/**
 * Asigna el dueño y el nombre del equipo (ej: "Cocina").
 */
function asignar_duenio_persistence(string $dispositivoId, string $uid, string $nombre): void
{
    firebase_actualizar_persistence("dispositivos/{$dispositivoId}/info", [
        "duenio" => $uid,
        "nombre" => $nombre,
        "vinculado" => firebase_hora_servidor_persistence(),
    ]);
}

function quitar_duenio_persistence(string $dispositivoId): void
{
    firebase_borrar_persistence("dispositivos/{$dispositivoId}/info/duenio");
}

function renombrar_dispositivo_persistence(string $dispositivoId, string $nombre): void
{
    firebase_actualizar_persistence("dispositivos/{$dispositivoId}/info", ["nombre" => $nombre]);
}

/**
 * Nombres de los artefactos de cada toma: ["toma_1" => "Heladera", ...] o null si no hay.
 */
function obtener_tomas_persistence(string $dispositivoId): ?array
{
    $tomas = firebase_leer_persistence("dispositivos/{$dispositivoId}/tomas");

    return is_array($tomas) ? $tomas : null;
}

/**
 * Guarda los nombres de los artefactos. Recibe ["toma_1" => "Heladera", "toma_2" => "Tele", ...].
 */
function guardar_tomas_persistence(string $dispositivoId, array $tomas): void
{
    firebase_actualizar_persistence("dispositivos/{$dispositivoId}/tomas", $tomas);
}

/**
 * Borra los nombres de las tomas (al desvincular, para que el próximo dueño empiece de cero).
 */
function borrar_tomas_persistence(string $dispositivoId): void
{
    firebase_borrar_persistence("dispositivos/{$dispositivoId}/tomas");
}
