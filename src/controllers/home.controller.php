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

/**
 * GET /estado - Comprobación rápida del TP 6: si se ve "200 OK", el servidor de Slim
 * y el motor de plantillas funcionan. También avisa si falta configurar Firebase.
 */
function home_estado_controller($renderer, $response)
{
    return view($renderer, $response, "home/estado.php", [
        "titulo" => "Estado — Energhost",
        "firebaseConfigurado" => leer_config_util("FIREBASE_URL") !== "",
    ]);
}
