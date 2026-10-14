<?php

// Responsabilidad: hablar con Firebase Realtime Database usando su API REST y cURL.
// Es el ÚNICO archivo del proyecto que se comunica con Firebase.
//
// Cómo funciona la API REST de Firebase:
//   cada ruta de la base se convierte en una URL terminada en .json
//   dispositivos/EcoSmart_A1B2C3/reles  ->  https://<proyecto>.firebaseio.com/dispositivos/EcoSmart_A1B2C3/reles.json
//   GET lee, PATCH actualiza solo los campos enviados, POST agrega una entrada nueva.

require_once __DIR__ . "/../utils/config.php";

/**
 * Lee una rama de la base. Devuelve un array, un valor simple o null si la rama no existe.
 * $parametros sirve para consultas, por ejemplo ["orderBy" => '"$key"', "limitToLast" => 60].
 */
function firebase_leer_persistence(string $ruta, array $parametros = [])
{
    return firebase_peticion_persistence("GET", $ruta, null, $parametros);
}

/**
 * Actualiza solo los campos enviados, sin borrar los demás (PATCH).
 */
function firebase_actualizar_persistence(string $ruta, array $datos)
{
    return firebase_peticion_persistence("PATCH", $ruta, $datos);
}

/**
 * Agrega una entrada nueva con una clave generada por Firebase (POST). Se usa para el historial.
 */
function firebase_agregar_persistence(string $ruta, array $datos)
{
    return firebase_peticion_persistence("POST", $ruta, $datos);
}

/**
 * Escribe un valor reemplazando lo que hubiera en esa ruta (PUT).
 */
function firebase_escribir_persistence(string $ruta, $valor)
{
    return firebase_peticion_persistence("PUT", $ruta, $valor);
}

/**
 * Borra una rama completa (DELETE).
 */
function firebase_borrar_persistence(string $ruta): void
{
    firebase_peticion_persistence("DELETE", $ruta);
}

/**
 * Valor especial que Firebase reemplaza por su propia hora (milisegundos).
 * Así el ESP32, el simulador y el panel usan el mismo reloj.
 */
function firebase_hora_servidor_persistence(): array
{
    return [".sv" => "timestamp"];
}

/**
 * Arma la URL completa de una ruta, agregando la clave de acceso si está configurada.
 */
function firebase_url_persistence(string $ruta, array $parametros = []): string
{
    $base = rtrim(leer_config_util("FIREBASE_URL"), "/");
    if ($base === "") {
        throw new RuntimeException("FIREBASE_URL no está configurado en el archivo .env");
    }

    $clave = leer_config_util("FIREBASE_AUTH");
    if ($clave !== "") {
        $parametros["auth"] = $clave;
    }

    $url = $base . "/" . trim($ruta, "/") . ".json";
    if ($parametros !== []) {
        $url .= "?" . http_build_query($parametros);
    }

    return $url;
}

/**
 * Ejecuta la petición HTTP contra Firebase. Si algo falla, lanza una excepción
 * para que la capa de servicio o el controller decidan qué responder.
 */
function firebase_peticion_persistence(string $metodo, string $ruta, $datos = null, array $parametros = [])
{
    $ch = curl_init(firebase_url_persistence($ruta, $parametros));

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    if ($datos !== null || $metodo === "PUT") {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    }

    $cuerpo = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errorCurl = curl_error($ch);
    curl_close($ch);

    if ($cuerpo === false || $codigo < 200 || $codigo >= 300) {
        $detalle = $errorCurl !== "" ? $errorCurl : "HTTP " . $codigo;
        error_log("Firebase {$metodo} {$ruta}: {$detalle}");
        throw new RuntimeException("Error de comunicación con Firebase");
    }

    return json_decode($cuerpo, true);
}
