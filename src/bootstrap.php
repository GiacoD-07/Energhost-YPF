<?php

// Responsabilidad: crear la aplicación Slim, configurar las vistas y cargar las rutas.
// No contiene lógica de negocio: eso vive en services/.

use Dotenv\Dotenv;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;

require __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/utils/config.php";
require_once __DIR__ . "/utils/render.php";

// 1. Variables de entorno (.env). safeLoad no falla si el archivo no existe.
$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->safeLoad();

// 2. Sesión para el login de los usuarios.
//    La cookie no es accesible desde JavaScript y no viaja en peticiones de otros sitios.
session_set_cookie_params([
    "httponly" => true,
    "samesite" => "Lax",
]);
session_start();

// 3. Aplicación Slim.
$app = AppFactory::create();

// Calcula la subcarpeta para que funcione en localhost:8000 y también en XAMPP (/energhost/public).
$scriptName = $_SERVER["SCRIPT_NAME"] ?? "";
$basePath = rtrim(preg_replace("#/index\.php$#", "", $scriptName) ?: "", "/");
$app->setBasePath($basePath);

// Convierte automáticamente el JSON recibido en un array de PHP.
$app->addBodyParsingMiddleware();

// 4. Vistas: carpeta src/views con el layout principal, como en el Repaso Slim.
$renderer = new PhpRenderer(__DIR__ . "/views");
$renderer->setLayout("layouts/main.php");
$renderer->setAttributes([
    "base" => $basePath,
    "usuario" => null,
    "aviso" => null,
]);

// 5. Rutas, cada grupo en su archivo dentro de src/routes/.
$webRoutes = require __DIR__ . "/routes/web.routes.php";
$webRoutes($app, $renderer);

$apiRoutes = require __DIR__ . "/routes/api.routes.php";
$apiRoutes($app);

// 6. Errores. En desarrollo se muestran los detalles; en producción no.
$mostrarErrores = leer_config_util("APP_ENV", "prod") !== "prod";
$errores = $app->addErrorMiddleware($mostrarErrores, true, true);

// Ruta inexistente: JSON para la API y una página para el navegador.
$errores->setErrorHandler(HttpNotFoundException::class, function ($request) use ($app, $renderer) {
    $response = $app->getResponseFactory()->createResponse(404);

    if (str_contains($request->getUri()->getPath(), "/api/")) {
        return json_response_util($response, ["ok" => false, "error" => "Error 404: Endpoint no encontrado"], 404);
    }

    return view($renderer, $response, "errores/404.php", ["titulo" => "Página no encontrada"]);
});

return $app;
