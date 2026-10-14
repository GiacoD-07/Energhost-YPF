<?php

// Responsabilidad: rutas JSON que usa el panel (public/js/panel.js y dispositivo.js).
// Todas exigen sesión y verifican que el equipo sea del usuario.
// El ESP32 no usa ninguna: escribe directo en Firebase.
require_once __DIR__ . "/../controllers/dispositivos.controller.php";
require_once __DIR__ . "/../controllers/mediciones.controller.php";
require_once __DIR__ . "/../controllers/reles.controller.php";

return function ($app) {
    // Equipos del usuario con su estado resumido
    $app->get("/api/dispositivos", function ($request, $response) {
        return api_listar_dispositivos_controller($response);
    });

    // Última lectura, conexión y alertas de un equipo
    $app->get("/api/dispositivos/{id}/ultima", function ($request, $response, $args) {
        return api_obtener_ultima_medicion_controller($response, $args["id"]);
    });

    // Lecturas por minuto para el gráfico (?limite=120)
    $app->get("/api/dispositivos/{id}/historial", function ($request, $response, $args) {
        return api_obtener_historial_controller($request, $response, $args["id"]);
    });

    // Relés: consultar y cambiar ({"rele": 1, "estado": 0})
    $app->get("/api/dispositivos/{id}/reles", function ($request, $response, $args) {
        return api_obtener_reles_controller($response, $args["id"]);
    });
    $app->post("/api/dispositivos/{id}/reles", function ($request, $response, $args) {
        return api_cambiar_rele_controller($request, $response, $args["id"]);
    });

    // Actividad: quién hizo qué y cuándo
    $app->get("/api/dispositivos/{id}/eventos", function ($request, $response, $args) {
        return api_eventos_controller($response, $args["id"]);
    });
};
