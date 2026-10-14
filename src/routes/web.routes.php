<?php

// Responsabilidad: páginas HTML que ve el usuario en el navegador.
require_once __DIR__ . "/../controllers/home.controller.php";
require_once __DIR__ . "/../controllers/auth.controller.php";
require_once __DIR__ . "/../controllers/dispositivos.controller.php";
require_once __DIR__ . "/../controllers/admin.controller.php";

return function ($app, $renderer) {
    // Presentación (pública)
    $app->get("/", function ($request, $response) use ($renderer) {
        return home_index_controller($renderer, $response);
    });

    // Cuenta: registro, ingreso y salida
    $app->get("/registro", function ($request, $response) use ($renderer) {
        return auth_mostrar_registro_controller($renderer, $response);
    });
    $app->post("/registro", function ($request, $response) use ($renderer) {
        return auth_registrar_controller($renderer, $request, $response);
    });
    $app->get("/login", function ($request, $response) use ($renderer) {
        return auth_mostrar_login_controller($renderer, $request, $response);
    });
    $app->post("/login", function ($request, $response) use ($renderer) {
        return auth_iniciar_sesion_controller($renderer, $request, $response);
    });
    $app->post("/logout", function ($request, $response) {
        return auth_cerrar_sesion_controller($response);
    });

    // Mis dispositivos
    $app->get("/panel", function ($request, $response) use ($renderer) {
        return panel_dispositivos_controller($renderer, $request, $response);
    });
    $app->get("/panel/{id}", function ($request, $response, $args) use ($renderer) {
        return panel_detalle_controller($renderer, $response, $args["id"]);
    });
    $app->post("/panel/{id}/nombre", function ($request, $response, $args) {
        return renombrar_dispositivo_controller($request, $response, $args["id"]);
    });
    $app->post("/panel/{id}/desvincular", function ($request, $response, $args) {
        return desvincular_dispositivo_controller($response, $args["id"]);
    });

    // Vincular un equipo nuevo (el QR de la etiqueta trae ?equipo=...&codigo=...)
    $app->get("/vincular", function ($request, $response) use ($renderer) {
        return vincular_formulario_controller($renderer, $request, $response);
    });
    $app->post("/vincular", function ($request, $response) use ($renderer) {
        return vincular_dispositivo_controller($renderer, $request, $response);
    });

    // Administración de usuarios (solo rol admin)
    $app->get("/admin/usuarios", function ($request, $response) use ($renderer) {
        return admin_usuarios_controller($renderer, $response);
    });
    $app->post("/admin/usuarios/{uid}/rol", function ($request, $response, $args) {
        return admin_cambiar_rol_controller($request, $response, $args["uid"]);
    });
    $app->post("/admin/usuarios/{uid}/clave", function ($request, $response, $args) {
        return admin_resetear_clave_controller($response, $args["uid"]);
    });
};
