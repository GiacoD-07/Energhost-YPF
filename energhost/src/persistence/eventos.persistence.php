<?php

// Responsabilidad: guardar y leer la actividad de cada dispositivo (quién hizo qué y cuándo).
//
//   eventos/<id>/<clave>   usuario, nombre_usuario, accion, detalle, timestamp

require_once __DIR__ . "/firebase.persistence.php";

function registrar_evento_persistence(string $dispositivoId, array $evento): void
{
    $evento["timestamp"] = firebase_hora_servidor_persistence();
    firebase_agregar_persistence("eventos/{$dispositivoId}", $evento);
}

/**
 * Últimos eventos de un dispositivo (las claves de Firebase quedan en orden de llegada).
 */
function obtener_eventos_persistence(string $dispositivoId, int $limite): array
{
    $eventos = firebase_leer_persistence("eventos/{$dispositivoId}", [
        "orderBy" => '"$key"',
        "limitToLast" => $limite,
    ]);

    return is_array($eventos) ? array_values($eventos) : [];
}
