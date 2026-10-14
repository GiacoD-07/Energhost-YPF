<?php

// Responsabilidad: leer de Firebase las mediciones, el estado y el historial de un dispositivo.
// Las rutas siguen el contrato de datos:
//   dispositivos/<id>/lectura_actual   dispositivos/<id>/estado   historial/<id>

require_once __DIR__ . "/firebase.persistence.php";

/**
 * Última lectura del sensor, o null si el dispositivo todavía no escribió ninguna.
 */
function obtener_lectura_actual_persistence(string $dispositivoId): ?array
{
    $lectura = firebase_leer_persistence("dispositivos/{$dispositivoId}/lectura_actual");

    return is_array($lectura) ? $lectura : null;
}

/**
 * Estado del dispositivo: ultima_conexion, ip, sobreconsumo, error_sensor.
 */
function obtener_estado_dispositivo_persistence(string $dispositivoId): ?array
{
    $estado = firebase_leer_persistence("dispositivos/{$dispositivoId}/estado");

    return is_array($estado) ? $estado : null;
}

/**
 * Últimas entradas del historial (una por minuto).
 * Se piden ordenadas por clave: Firebase genera claves en orden de llegada.
 */
function obtener_historial_persistence(string $dispositivoId, int $limite): array
{
    $entradas = firebase_leer_persistence("historial/{$dispositivoId}", [
        "orderBy" => '"$key"',
        "limitToLast" => $limite,
    ]);

    return is_array($entradas) ? array_values($entradas) : [];
}
