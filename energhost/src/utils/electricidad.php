<?php

// Responsabilidad: cálculos eléctricos, de costo y ambientales.
// Son funciones "puras": reciben números y devuelven números o textos, sin tocar Firebase.

require_once __DIR__ . "/config.php";

/**
 * Consumo fantasma: un aparato en espera consume poco pero no cero.
 * Rango del contrato de datos: mayor a 0,5 W y hasta 15 W.
 */
function es_consumo_fantasma_util(float $potenciaWatts): bool
{
    return $potenciaWatts > 0.5 && $potenciaWatts <= 15.0;
}

/**
 * Evalúa tensión y potencia y devuelve el estado de alerta.
 * Rangos para la red de Argentina (220 V nominal, ±10%).
 */
function determinar_estado_alerta_util(float $voltaje, float $potencia): string
{
    $umbral = leer_config_numero_util("UMBRAL_SOBRECONSUMO_W", 2500);

    if ($potencia > $umbral) {
        return "Sobreconsumo";
    }

    if ($voltaje > 242.0) {
        return "Sobretensión";
    }

    if ($voltaje > 0 && $voltaje < 198.0) {
        return "Baja tensión";
    }

    return "Normal";
}

/**
 * Costo estimado en pesos: kWh × precio por kWh + cargo fijo (valores del .env).
 * Devuelve null si la tarifa no está configurada.
 */
function calcular_costo_pesos_util(float $kwh): ?float
{
    $precio = leer_config_util("PRECIO_KWH_PESOS");
    if (!is_numeric($precio)) {
        return null; // "Cuadro tarifario no configurado"
    }

    $cargoFijo = leer_config_numero_util("CARGO_FIJO_PESOS", 0);
    $kwh = max(0.0, $kwh);

    return round($kwh * (float) $precio + $cargoFijo, 2);
}

/**
 * Huella de carbono: kWh × factor de emisión (kg de CO2 por kWh).
 * Devuelve null si el factor no está configurado.
 */
function calcular_huella_co2_util(float $kwh): ?float
{
    $factor = leer_config_util("CO2_EMISSION_FACTOR_KG_PER_KWH");
    if (!is_numeric($factor)) {
        return null; // "Factor de emisión de CO2 no configurado"
    }

    return round(max(0.0, $kwh) * (float) $factor, 2);
}
