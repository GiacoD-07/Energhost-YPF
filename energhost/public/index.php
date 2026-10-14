<?php

// Entrada única: todas las peticiones pasan por acá.
// Carga la aplicación armada en bootstrap.php y la ejecuta.
$app = require __DIR__ . "/../src/bootstrap.php";

$app->run();
