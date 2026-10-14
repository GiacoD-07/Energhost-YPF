<?php

// Responsabilidad: página de presentación (pública).

require_once __DIR__ . "/../services/auth.service.php";
require_once __DIR__ . "/../utils/render.php";
require_once __DIR__ . "/../utils/config.php";

function home_index_controller($renderer, $response)
{
    $renderer->setLayout("layouts/landing.php");

    // Los mismos valores que usa el panel, para que la presentación no muestre otros números
    return view($renderer, $response, "home/index.php", [
        "titulo" => "Voltec Ergon — Eco Smart Grid & Energhost",
        "usuario" => usuario_actual_service(),
        "tarifaKwh" => leer_config_numero_util("PRECIO_KWH_PESOS", 0),
        "factorCo2" => leer_config_numero_util("CO2_EMISSION_FACTOR_KG_PER_KWH", 0.283),
        "umbralSobreconsumo" => leer_config_numero_util("UMBRAL_SOBRECONSUMO_W", 2500),
    ]);
}
