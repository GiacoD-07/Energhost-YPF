<?php

// Responsabilidad: reglas de los dispositivos de cada usuario: vincular, listar,
// renombrar, desvincular y verificar que un usuario solo acceda a lo suyo.

require_once __DIR__ . "/../persistence/dispositivos.persistence.php";
require_once __DIR__ . "/../persistence/usuarios.persistence.php";
require_once __DIR__ . "/../persistence/eventos.persistence.php";
require_once __DIR__ . "/../persistence/reles.persistence.php";
require_once __DIR__ . "/mediciones.service.php";

const PREFIJO_DISPOSITIVO = "EcoSmart_";
const MAX_INTENTOS_VINCULACION = 5;
const MINUTOS_BLOQUEO_VINCULACION = 10;
const CANTIDAD_TOMAS = 3;

/**
 * Valida un nombre (del equipo o de un artefacto): obligatorio y de hasta 40 caracteres.
 */
function validar_nombre_service(string $nombre, string $que): string
{
    $nombre = trim($nombre);
    if ($nombre === "" || mb_strlen($nombre) > 40) {
        throw new InvalidArgumentException("Poné un nombre para {$que} (hasta 40 caracteres).");
    }

    return $nombre;
}

/**
 * Valida los nombres de los artefactos de las 3 tomas. Una toma sin nombre queda como "Toma N".
 * Recibe [1 => "Heladera", 2 => "", 3 => "Microondas"] y devuelve ["toma_1" => "Heladera", "toma_2" => "Toma 2", ...].
 */
function validar_tomas_service(array $nombres): array
{
    $tomas = [];
    for ($numero = 1; $numero <= CANTIDAD_TOMAS; $numero++) {
        $nombre = trim((string) ($nombres[$numero] ?? ""));
        $tomas["toma_{$numero}"] = $nombre === "" ? "Toma {$numero}" : validar_nombre_service($nombre, "el artefacto de la toma {$numero}");
    }

    return $tomas;
}

/**
 * Nombre del artefacto de cada toma, con "Toma N" si no se cargó: [1 => "Heladera", 2 => "Toma 2", 3 => ...].
 */
function nombres_tomas_service(string $dispositivoId, int $cantidad): array
{
    $guardadas = obtener_tomas_persistence($dispositivoId) ?? [];

    $nombres = [];
    for ($numero = 1; $numero <= $cantidad; $numero++) {
        $nombre = trim((string) ($guardadas["toma_{$numero}"] ?? ""));
        $nombres[$numero] = $nombre === "" ? "Toma {$numero}" : $nombre;
    }

    return $nombres;
}

/**
 * Convierte lo que escribe el usuario ("a1b2c3", "EcoSmart_A1B2C3") en el id del dispositivo.
 */
function normalizar_id_dispositivo_service(string $texto): string
{
    $texto = strtoupper(trim($texto));
    $texto = preg_replace("/^ECOSMART_/", "", $texto);

    if (!preg_match("/^[A-Z0-9]{3,20}$/", $texto)) {
        throw new InvalidArgumentException("El identificador del equipo no es válido.");
    }

    return PREFIJO_DISPOSITIVO . $texto;
}

/**
 * Comprueba que el dispositivo exista y que el usuario sea su dueño (o administrador).
 * Si no, responde como si no existiera, para no revelar equipos ajenos.
 */
function verificar_acceso_dispositivo_service(array $usuario, string $dispositivoId): array
{
    if (!preg_match("/^[A-Za-z0-9_]{3,40}$/", $dispositivoId)) {
        throw new DomainException("Dispositivo no encontrado.");
    }

    $info = obtener_info_dispositivo_persistence($dispositivoId);
    $esDuenio = $info !== null && ($info["duenio"] ?? null) === $usuario["uid"];

    if ($info === null || (!$esDuenio && !es_admin_service($usuario))) {
        throw new DomainException("Dispositivo no encontrado.");
    }

    return $info;
}

/**
 * Dispositivos del usuario con un resumen de su estado para la lista "Mis dispositivos".
 */
function listar_mis_dispositivos_service(array $usuario): array
{
    $lista = [];

    foreach (listar_ids_dispositivos_usuario_persistence($usuario["uid"]) as $id) {
        $info = obtener_info_dispositivo_persistence($id);
        if ($info === null || ($info["duenio"] ?? null) !== $usuario["uid"]) {
            continue; // quedó un vínculo viejo: se ignora
        }

        $resumen = obtener_resumen_actual_service($id);
        $reles = obtener_reles_persistence($id) ?? [];
        $cantidad = max(1, min(CANTIDAD_TOMAS, (int) ($info["cantidad_reles"] ?? CANTIDAD_TOMAS)));
        $encendidas = 0;
        for ($numero = 1; $numero <= $cantidad; $numero++) {
            $encendidas += (int) ($reles["rele_{$numero}"] ?? 0) === 1 ? 1 : 0;
        }

        $lista[] = [
            "id" => $id,
            "nombre" => $info["nombre"] ?? $id,
            "en_linea" => $resumen["en_linea"],
            "potencia" => $resumen["lectura"]["potencia"] ?? null,
            "alerta" => $resumen["alerta"] ?? null,
            "sobreconsumo" => $resumen["sobreconsumo"],
            "tomas" => $cantidad,
            "tomas_encendidas" => $encendidas,
        ];
    }

    usort($lista, fn ($a, $b) => strcasecmp($a["nombre"], $b["nombre"]));

    return $lista;
}

/**
 * Vincula un equipo a la cuenta. Pasos:
 *   1. el equipo existe (ya se conectó alguna vez a Firebase)
 *   2. no tiene dueño
 *   3. el código coincide con el que generó el equipo
 * Después de 5 intentos fallidos, se bloquea 10 minutos para evitar que adivinen códigos.
 */
function vincular_dispositivo_service(array $usuario, string $textoId, string $codigo, string $nombre, array $nombresTomas = []): string
{
    verificar_intentos_vinculacion_service();

    $dispositivoId = normalizar_id_dispositivo_service($textoId);
    $nombre = validar_nombre_service($nombre, "el equipo");
    $tomas = validar_tomas_service($nombresTomas);
    $codigo = trim($codigo);

    $info = obtener_info_dispositivo_persistence($dispositivoId);
    $codigoGuardado = obtener_codigo_vinculacion_persistence($dispositivoId);

    if ($info === null || $codigoGuardado === null) {
        sumar_intento_fallido_service();
        throw new InvalidArgumentException("No encontramos ese equipo. Revisá que esté enchufado y conectado a internet.");
    }

    if (!empty($info["duenio"])) {
        $mensaje = $info["duenio"] === $usuario["uid"]
            ? "Ese equipo ya está en tu cuenta."
            : "Ese equipo ya está vinculado a otra cuenta.";
        throw new InvalidArgumentException($mensaje);
    }

    if (!hash_equals($codigoGuardado, $codigo)) {
        sumar_intento_fallido_service();
        throw new InvalidArgumentException("El código de vinculación es incorrecto.");
    }

    asignar_duenio_persistence($dispositivoId, $usuario["uid"], $nombre);
    guardar_tomas_persistence($dispositivoId, $tomas);
    agregar_dispositivo_a_usuario_persistence($usuario["uid"], $dispositivoId);
    registrar_evento_persistence($dispositivoId, [
        "usuario" => $usuario["uid"],
        "nombre_usuario" => $usuario["nombre"],
        "accion" => "vinculado",
        "detalle" => "Vinculó el equipo como \"{$nombre}\"",
    ]);

    unset($_SESSION["vinculacion"]);

    return $dispositivoId;
}

/**
 * Cambia el nombre del equipo y el de los artefactos enchufados en cada toma.
 */
function renombrar_dispositivo_service(array $usuario, string $dispositivoId, string $nombre, array $nombresTomas): void
{
    verificar_acceso_dispositivo_service($usuario, $dispositivoId);

    $nombre = validar_nombre_service($nombre, "el equipo");
    $tomas = validar_tomas_service($nombresTomas);

    renombrar_dispositivo_persistence($dispositivoId, $nombre);
    guardar_tomas_persistence($dispositivoId, $tomas);
}

/**
 * Quita el equipo de la cuenta. Queda libre para que otra persona lo vincule con su código.
 */
function desvincular_dispositivo_service(array $usuario, string $dispositivoId): void
{
    $info = verificar_acceso_dispositivo_service($usuario, $dispositivoId);
    $duenio = $info["duenio"] ?? null;

    quitar_duenio_persistence($dispositivoId);
    borrar_tomas_persistence($dispositivoId);
    if ($duenio !== null) {
        quitar_dispositivo_de_usuario_persistence($duenio, $dispositivoId);
    }

    registrar_evento_persistence($dispositivoId, [
        "usuario" => $usuario["uid"],
        "nombre_usuario" => $usuario["nombre"],
        "accion" => "desvinculado",
        "detalle" => "Quitó el equipo de la cuenta",
    ]);
}

function listar_eventos_service(array $usuario, string $dispositivoId, int $limite = 20): array
{
    verificar_acceso_dispositivo_service($usuario, $dispositivoId);

    $eventos = obtener_eventos_persistence($dispositivoId, max(1, min(50, $limite)));
    usort($eventos, fn ($a, $b) => ($b["timestamp"] ?? 0) <=> ($a["timestamp"] ?? 0));

    return array_map(fn ($e) => [
        "timestamp" => (int) ($e["timestamp"] ?? 0),
        "nombre_usuario" => (string) ($e["nombre_usuario"] ?? "Sistema"),
        "accion" => (string) ($e["accion"] ?? ""),
        "detalle" => (string) ($e["detalle"] ?? ""),
    ], $eventos);
}

// ---------- Límite de intentos (se guarda en la sesión) ----------

function verificar_intentos_vinculacion_service(): void
{
    $datos = $_SESSION["vinculacion"] ?? ["intentos" => 0, "desde" => time()];

    if (time() - $datos["desde"] > MINUTOS_BLOQUEO_VINCULACION * 60) {
        unset($_SESSION["vinculacion"]);
        return;
    }

    if ($datos["intentos"] >= MAX_INTENTOS_VINCULACION) {
        throw new InvalidArgumentException("Demasiados intentos fallidos. Esperá 10 minutos y probá de nuevo.");
    }
}

function sumar_intento_fallido_service(): void
{
    $datos = $_SESSION["vinculacion"] ?? ["intentos" => 0, "desde" => time()];
    $datos["intentos"]++;
    $_SESSION["vinculacion"] = $datos;
}
