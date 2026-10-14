<?php

// Responsabilidad: leer y escribir en Firebase el estado de los relés de un dispositivo.

require_once __DIR__ . "/firebase.persistence.php";

/**
 * Devuelve ["rele_1" => 0|1, "rele_2" => 0|1, "rele_3" => 0|1] o null si la rama no existe.
 */
function obtener_reles_persistence(string $dispositivoId): ?array
{
    $reles = firebase_leer_persistence("dispositivos/{$dispositivoId}/reles");

    return is_array($reles) ? $reles : null;
}

/**
 * Cambia un solo relé sin tocar los otros (PATCH).
 */
function actualizar_rele_persistence(string $dispositivoId, int $numero, int $estado): void
{
    firebase_actualizar_persistence("dispositivos/{$dispositivoId}/reles", [
        "rele_{$numero}" => $estado,
    ]);
}

/**
 * Sale del bloqueo por sobreconsumo: el ESP32 vuelve a obedecer la rama "reles".
 */
function rearmar_sobreconsumo_persistence(string $dispositivoId): void
{
    firebase_actualizar_persistence("dispositivos/{$dispositivoId}/estado", [
        "sobreconsumo" => false,
    ]);
}
