<?php

// Solo para el servidor de desarrollo de PHP:
//   php -S localhost:8000 -t public public/router.php
// Si el archivo pedido existe (CSS, JS, imágenes) se entrega tal cual.
// Todo lo demás pasa a Slim por index.php.
$ruta = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
$archivo = __DIR__ . $ruta;

if ($ruta !== "/" && is_file($archivo)) {
    return false;
}

$_SERVER["SCRIPT_NAME"] = "/index.php";
require __DIR__ . "/index.php";
