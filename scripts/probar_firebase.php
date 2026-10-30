<?php

// Comprueba que el .env apunte a una base de Firebase que funciona.
// Escribe, lee y borra una rama de prueba (_prueba_conexion). No toca ningún otro dato.
//
// Uso: php scripts/probar_firebase.php

require __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../src/persistence/firebase.persistence.php";

Dotenv\Dotenv::createImmutable(__DIR__ . "/..")->safeLoad();

$url = leer_config_util("FIREBASE_URL");
$clave = leer_config_util("FIREBASE_AUTH");

echo "URL:   " . ($url !== "" ? $url : "(vacía)") . PHP_EOL;
echo "Clave: " . ($clave !== "" ? "cargada (" . strlen($clave) . " caracteres)" : "(vacía)") . PHP_EOL . PHP_EOL;

if ($url === "" || $clave === "") {
    echo "ERROR: completá FIREBASE_URL y FIREBASE_AUTH en el archivo .env" . PHP_EOL;
    exit(1);
}

if (!function_exists("curl_init")) {
    echo "ERROR: PHP no tiene la extensión curl. En php.ini sacale el ; a la línea extension=curl" . PHP_EOL;
    exit(1);
}

try {
    $valor = "prueba-" . date("His");
    firebase_escribir_persistence("_prueba_conexion", $valor);
    echo "1. Escritura ... OK" . PHP_EOL;

    $leido = firebase_leer_persistence("_prueba_conexion");
    if ($leido !== $valor) {
        throw new RuntimeException("se leyó un valor distinto al escrito");
    }
    echo "2. Lectura ..... OK" . PHP_EOL;

    firebase_borrar_persistence("_prueba_conexion");
    echo "3. Borrado ..... OK" . PHP_EOL . PHP_EOL;

    echo "Conexión con Firebase lista." . PHP_EOL;
} catch (RuntimeException $e) {
    echo PHP_EOL . "ERROR: no se pudo usar Firebase (" . $e->getMessage() . ")." . PHP_EOL;
    echo "Revisá que la URL sea la de Realtime Database (pestaña Datos) y que la clave sea el secreto de la base." . PHP_EOL;
    exit(1);
}
