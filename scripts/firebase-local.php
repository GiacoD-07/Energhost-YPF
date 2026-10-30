<?php

// FIREBASE DE PRUEBA: imita la API REST de Firebase Realtime Database en tu PC.
// Sirve para probar el panel y el simulador sin tener todavía la base real.
// NO es para el ESP32 ni para la entrega: es solo una herramienta de desarrollo.
//
// Uso (en una terminal aparte, desde la carpeta del proyecto):
//   php -S 127.0.0.1:9100 scripts/firebase-local.php
//
// Y en el .env:
//   FIREBASE_URL=http://127.0.0.1:9100
//   FIREBASE_AUTH=local
//
// Los datos se guardan en scripts/firebase-local.json (borralo para empezar de cero).

const ARCHIVO_DATOS = __DIR__ . "/firebase-local.json";

$datos = is_file(ARCHIVO_DATOS) ? (json_decode(file_get_contents(ARCHIVO_DATOS), true) ?? []) : [];
$ruta = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) ?: "/";
parse_str(parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY) ?? "", $parametros);
$metodo = $_SERVER["REQUEST_METHOD"];
$cuerpo = json_decode(file_get_contents("php://input"), true);

header("Content-Type: application/json");

if (!str_ends_with($ruta, ".json")) {
    http_response_code(400);
    echo json_encode(["error" => "La ruta tiene que terminar en .json"]);
    return true;
}

$claves = array_values(array_filter(explode("/", substr($ruta, 0, -5)), "strlen"));

/** Reemplaza {".sv": "timestamp"} por la hora actual en milisegundos, como Firebase. */
function local_hora_servidor($valor)
{
    if (is_array($valor)) {
        if (($valor[".sv"] ?? null) === "timestamp") {
            return (int) round(microtime(true) * 1000);
        }
        foreach ($valor as $clave => $interno) {
            $valor[$clave] = local_hora_servidor($interno);
        }
    }

    return $valor;
}

/** Lee una rama sin modificar nada. */
function local_leer(array $datos, array $claves)
{
    foreach ($claves as $clave) {
        if (!is_array($datos) || !array_key_exists($clave, $datos)) {
            return null;
        }
        $datos = $datos[$clave];
    }

    return $datos;
}

/** Devuelve una referencia a la rama, creándola si no existe. */
function &local_rama(array &$datos, array $claves)
{
    $rama = &$datos;
    foreach ($claves as $clave) {
        if (!is_array($rama)) {
            $rama = [];
        }
        if (!array_key_exists($clave, $rama)) {
            $rama[$clave] = null;
        }
        $rama = &$rama[$clave];
    }

    return $rama;
}

if ($metodo === "GET") {
    $valor = local_leer($datos, $claves);
    if (is_array($valor) && isset($parametros["limitToLast"])) {
        ksort($valor, SORT_STRING);
        $valor = array_slice($valor, -(int) $parametros["limitToLast"], null, true);
    }
    echo json_encode($valor);
    return true;
}

$rama = &local_rama($datos, $claves);

if ($metodo === "PATCH") {
    if (!is_array($rama)) {
        $rama = [];
    }
    foreach (local_hora_servidor($cuerpo ?? []) as $clave => $valor) {
        $rama[$clave] = $valor;
    }
    $respuesta = local_hora_servidor($cuerpo);
} elseif ($metodo === "PUT") {
    $rama = local_hora_servidor($cuerpo);
    $respuesta = $rama;
} elseif ($metodo === "DELETE") {
    $rama = null;
    $respuesta = null;
} elseif ($metodo === "POST") {
    if (!is_array($rama)) {
        $rama = [];
    }
    // Clave ordenada por tiempo, como las de Firebase
    $nueva = "-N" . sprintf("%013d", (int) round(microtime(true) * 1000)) . bin2hex(random_bytes(2));
    $rama[$nueva] = local_hora_servidor($cuerpo);
    $respuesta = ["name" => $nueva];
} else {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    return true;
}

unset($rama);
file_put_contents(ARCHIVO_DATOS, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode($respuesta);
return true;
