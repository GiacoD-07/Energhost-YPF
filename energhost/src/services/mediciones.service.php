<?php

// Responsabilidad: reglas de negocio de las mediciones.
// Junta lo que viene de Firebase (persistence) con los cálculos de utils/electricidad.php.

require_once __DIR__ . "/../persistence/mediciones.persistence.php";
require_once __DIR__ . "/../utils/electricidad.php";
require_once __DIR__ . "/../utils/config.php";

/**
 * Resumen para el panel: última lectura, si el ESP32 está en línea, alertas, costo y CO2.
 */
function obtener_resumen_actual_service(string $dispositivoId): array
{
    $lectura = obtener_lectura_actual_persistence($dispositivoId);
    $estado = obtener_estado_dispositivo_persistence($dispositivoId) ?? [];

    $enLinea = calcular_estado_conexion_service($estado["ultima_conexion"] ?? null);

    $resumen = [
        "dispositivo_id" => $dispositivoId,
        "en_linea" => $enLinea,
        "ultima_conexion" => $estado["ultima_conexion"] ?? null,
        "sobreconsumo" => !empty($estado["sobreconsumo"]),
        "error_sensor" => !empty($estado["error_sensor"]),
        "hay_lectura" => $lectura !== null,
        "mensaje" => null,
    ];

    if (!$enLinea) {
        $resumen["mensaje"] = "Dispositivo desconectado: la última lectura puede no estar actualizada";
    }

    if ($lectura === null) {
        $resumen["mensaje"] = "Sin lecturas disponibles";
        return $resumen;
    }

    $voltaje = (float) ($lectura["voltaje"] ?? 0);
    $corriente = (float) ($lectura["corriente"] ?? 0);
    $potencia = (float) ($lectura["potencia"] ?? 0);
    $energiaKwh = (float) ($lectura["energia_kwh"] ?? 0);

    $resumen["lectura"] = [
        "voltaje" => round($voltaje, 1),
        "corriente" => round($corriente, 2),
        "potencia" => round($potencia, 1),
        "energia_kwh" => round($energiaKwh, 3),
        "consumo_fantasma" => es_consumo_fantasma_util($potencia),
        "timestamp" => $lectura["timestamp"] ?? null,
    ];
    $resumen["alerta"] = determinar_estado_alerta_util($voltaje, $potencia);
    $resumen["costo_pesos"] = calcular_costo_pesos_util($energiaKwh);
    $resumen["co2_kg"] = calcular_huella_co2_util($energiaKwh);

    return $resumen;
}

/**
 * true si la última señal del ESP32 llegó hace menos de SEGUNDOS_PARA_DESCONECTADO (30 s por defecto).
 * $ultimaConexion está en milisegundos, como la guarda Firebase.
 */
function calcular_estado_conexion_service($ultimaConexion): bool
{
    if (!is_numeric($ultimaConexion)) {
        return false;
    }

    $limiteSegundos = leer_config_numero_util("SEGUNDOS_PARA_DESCONECTADO", 30);
    $ahoraMs = (int) round(microtime(true) * 1000);

    return ($ahoraMs - (int) $ultimaConexion) <= $limiteSegundos * 1000;
}

/**
 * Historial ordenado por hora, de la entrada más vieja a la más nueva.
 * El límite se acota entre 1 y 120 para no gastar la cuota gratuita de Firebase.
 */
function obtener_historial_service(string $dispositivoId, int $limite = 120): array
{
    $limite = max(1, min(120, $limite));
    $entradas = obtener_historial_persistence($dispositivoId, $limite);

    $historial = [];
    foreach ($entradas as $entrada) {
        if (!is_array($entrada) || !is_numeric($entrada["timestamp"] ?? null)) {
            continue; // se ignoran entradas incompletas
        }
        $historial[] = [
            "timestamp" => (int) $entrada["timestamp"],
            "potencia" => round((float) ($entrada["potencia"] ?? 0), 1),
            "energia_kwh" => round((float) ($entrada["energia_kwh"] ?? 0), 3),
        ];
    }

    usort($historial, fn ($a, $b) => $a["timestamp"] <=> $b["timestamp"]);

    return $historial;
}

/**
 * Consumo vampiro: consumo fantasma (0,5 a 15 W) sostenido durante más de 2 horas.
 * Recorre el historial desde la entrada más nueva hacia atrás mientras siga en rango.
 */
function detectar_consumo_vampiro_service(array $historial): array
{
    if (count($historial) < 2) {
        return [
            "detectado" => false,
            "mensaje" => "Historial insuficiente para analizar consumo vampiro",
        ];
    }

    $minutosMinimos = 120;
    $racha = [];

    for ($i = count($historial) - 1; $i >= 0; $i--) {
        if (!es_consumo_fantasma_util($historial[$i]["potencia"])) {
            break;
        }
        $racha[] = $historial[$i];
    }

    if (count($racha) < 2) {
        return ["detectado" => false, "mensaje" => null];
    }

    // Cada entrada cubre su propio minuto: 120 entradas seguidas = 120 minutos
    $minutos = (reset($racha)["timestamp"] - end($racha)["timestamp"]) / 60000 + 1;
    if ($minutos < $minutosMinimos) {
        return ["detectado" => false, "mensaje" => null];
    }

    // Cada entrada representa un minuto: W × (1/60) h / 1000 = kWh
    $kwhDesperdiciados = 0.0;
    foreach ($racha as $entrada) {
        $kwhDesperdiciados += $entrada["potencia"] / 60 / 1000;
    }

    return [
        "detectado" => true,
        "minutos" => (int) round($minutos),
        "kwh_desperdiciados" => round($kwhDesperdiciados, 4),
        "mensaje" => "Consumo vampiro detectado",
    ];
}
