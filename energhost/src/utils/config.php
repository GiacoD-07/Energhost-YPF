<?php

// Responsabilidad: leer la configuración del archivo .env en un solo lugar.

/**
 * Devuelve el valor de una variable del .env, o el valor por defecto si no existe o está vacía.
 */
function leer_config_util(string $clave, string $porDefecto = ""): string
{
    $valor = $_ENV[$clave] ?? getenv($clave);

    if ($valor === false || $valor === null || trim((string) $valor) === "") {
        return $porDefecto;
    }

    return trim((string) $valor);
}

/**
 * Igual que leer_config_util pero devuelve un número.
 */
function leer_config_numero_util(string $clave, float $porDefecto): float
{
    $valor = leer_config_util($clave);

    return is_numeric($valor) ? (float) $valor : $porDefecto;
}
