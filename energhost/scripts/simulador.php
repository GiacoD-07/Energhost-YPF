<?php

// ESP32 FALSO: se comporta como un equipo real, con la misma forma de datos que el firmware.
// Sirve para programar y probar el panel sin tener el hardware conectado.
//
// Uso:
//   php scripts/simulador.php                               equipo EcoSmart_SIM001, consumo normal
//   php scripts/simulador.php --equipo=SIM002               otro equipo (se pueden correr varios a la vez)
//   php scripts/simulador.php --modo=sobreconsumo           supera el umbral y provoca el corte
//   php scripts/simulador.php --modo=fantasma               artefacto en espera (0,5 a 15 W)
//   php scripts/simulador.php --modo=desconectado           deja de escribir (el panel muestra "desconectado")
//   php scripts/simulador.php --ciclos=10                   corre 10 ciclos y termina (0 = sin fin)
//
// Igual que el ESP32: tiene 3 tomas (un relé por toma), se presenta en Firebase con su
// código de vinculación, escribe la lectura TOTAL cada 2 s, el estado cada 10 s y el
// historial cada 1 min, y respeta los relés y el corte por sobreconsumo. Cortar con Ctrl+C.

require __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../src/persistence/firebase.persistence.php";
require_once __DIR__ . "/../src/utils/electricidad.php";

Dotenv\Dotenv::createImmutable(__DIR__ . "/..")->safeLoad();

const MODOS_VALIDOS = ["normal", "sobreconsumo", "fantasma", "desconectado"];
const CANTIDAD_RELES = 3;

$opciones = getopt("", ["modo::", "ciclos::", "intervalo::", "equipo::"]);
$modo = $opciones["modo"] ?? "normal";
$ciclosMaximos = (int) ($opciones["ciclos"] ?? 0);
$intervalo = (float) ($opciones["intervalo"] ?? 2);
$sufijo = strtoupper(preg_replace("/[^A-Za-z0-9]/", "", $opciones["equipo"] ?? "SIM001"));

if (!in_array($modo, MODOS_VALIDOS, true)) {
    fwrite(STDERR, "Modo de simulación no válido. Opciones: " . implode(", ", MODOS_VALIDOS) . PHP_EOL);
    exit(1);
}
if ($sufijo === "") {
    fwrite(STDERR, "El identificador del equipo no es válido." . PHP_EOL);
    exit(1);
}

$dispositivoId = "EcoSmart_" . $sufijo;
$umbral = leer_config_numero_util("UMBRAL_SOBRECONSUMO_W", 2500);
$ruta = "dispositivos/{$dispositivoId}";

// 1. Identidad: como el ESP32 al arrancar, se presenta en Firebase con su código.
try {
    $codigo = firebase_leer_persistence("{$ruta}/vinculacion/codigo");
    if ($codigo === null) {
        $codigo = sprintf("%06d", random_int(0, 999999));
        firebase_escribir_persistence("{$ruta}/vinculacion/codigo", $codigo);
    }
    firebase_actualizar_persistence("{$ruta}/info", [
        "mac" => "SIMULADOR",
        "cantidad_reles" => CANTIDAD_RELES,
        "ultimo_arranque" => firebase_hora_servidor_persistence(),
    ]);

    $anterior = firebase_leer_persistence("{$ruta}/lectura_actual");
    $energiaKwh = (float) ($anterior["energia_kwh"] ?? 0);
} catch (RuntimeException $e) {
    fwrite(STDERR, "No se pudo conectar con Firebase: revisá FIREBASE_URL y FIREBASE_AUTH en .env" . PHP_EOL);
    exit(1);
}

$panelUrl = rtrim(leer_config_util("PANEL_URL", "http://localhost:8000"), "/");
echo "==============================================" . PHP_EOL;
echo "  Simulador del equipo {$dispositivoId} (modo '{$modo}')" . PHP_EOL;
echo "  Identificador: {$sufijo}" . PHP_EOL;
echo "  Código:        {$codigo}" . PHP_EOL;
echo "  Link del QR:   {$panelUrl}/vincular?equipo={$sufijo}&codigo={$codigo}" . PHP_EOL;
echo "==============================================" . PHP_EOL;

$corteActivo = false;
$ciclo = 0;

while ($ciclosMaximos === 0 || $ciclo < $ciclosMaximos) {
    $ciclo++;

    if ($modo === "desconectado") {
        echo "[{$ciclo}] Sin conexión simulada: no se escribe nada." . PHP_EOL;
        usleep((int) ($intervalo * 1000000));
        continue;
    }

    try {
        // 2. Relé: si hay un corte, se espera a que el panel lo rearme
        if ($corteActivo) {
            $estado = firebase_leer_persistence("{$ruta}/estado");
            $corteActivo = !empty($estado["sobreconsumo"]);
        }
        $reles = $corteActivo ? [] : (firebase_leer_persistence("{$ruta}/reles") ?? []);
        $encendidos = [];
        for ($numero = 1; $numero <= CANTIDAD_RELES; $numero++) {
            $encendidos[$numero] = (int) ($reles["rele_{$numero}"] ?? 0) === 1;
        }

        // 3. Medición inventada (un solo sensor: suma de las tomas encendidas)
        $lectura = simulador_generar_lectura($modo, $encendidos);
        $energiaKwh += $lectura["potencia"] * ($intervalo / 3600) / 1000;

        // 4. Protección por sobreconsumo (igual que el firmware)
        if ($lectura["potencia"] > $umbral && !$corteActivo) {
            $corteActivo = true;
            $apagados = [];
            for ($numero = 1; $numero <= CANTIDAD_RELES; $numero++) {
                $apagados["rele_{$numero}"] = 0;
            }
            firebase_actualizar_persistence("{$ruta}/reles", $apagados);
            firebase_actualizar_persistence("{$ruta}/estado", ["sobreconsumo" => true]);
            echo "[{$ciclo}] Corte por sobreconsumo: {$lectura['potencia']} W" . PHP_EOL;
        }

        // 5. Lectura actual (cada ciclo)
        firebase_actualizar_persistence("{$ruta}/lectura_actual", [
            "voltaje" => $lectura["voltaje"],
            "corriente" => $lectura["corriente"],
            "potencia" => $lectura["potencia"],
            "energia_kwh" => round($energiaKwh, 4),
            "consumo_fantasma" => es_consumo_fantasma_util($lectura["potencia"]),
            "timestamp" => firebase_hora_servidor_persistence(),
        ]);

        // 6. Estado (cada 10 s) e historial (cada 1 min)
        if ($ciclo === 1 || fmod($ciclo * $intervalo, 10) < $intervalo) {
            firebase_actualizar_persistence("{$ruta}/estado", [
                "ultima_conexion" => firebase_hora_servidor_persistence(),
                "ip" => "simulador",
                "error_sensor" => false,
            ]);
        }
        if ($ciclo === 1 || fmod($ciclo * $intervalo, 60) < $intervalo) {
            firebase_agregar_persistence("historial/{$dispositivoId}", [
                "potencia" => $lectura["potencia"],
                "energia_kwh" => round($energiaKwh, 4),
                "timestamp" => firebase_hora_servidor_persistence(),
            ]);
        }

        $tomas = implode(" ", array_map(fn ($n, $on) => "T{$n}:" . ($on ? "ON" : "--"), array_keys($encendidos), $encendidos));
        echo "[{$ciclo}] {$tomas}  {$lectura['voltaje']} V  {$lectura['corriente']} A  {$lectura['potencia']} W" . PHP_EOL;
    } catch (RuntimeException $e) {
        fwrite(STDERR, "[{$ciclo}] Error al escribir en Firebase" . PHP_EOL);
    }

    usleep((int) ($intervalo * 1000000));
}

/**
 * Inventa una lectura según el modo y qué tomas están encendidas.
 * Cada toma simula un artefacto distinto y el sensor mide la suma, como el PZEM real.
 *   normal:       toma 1 ~150 W (heladera), toma 2 ~90 W (tele), toma 3 ~400 W (computadora)
 *   fantasma:     cada toma encendida consume 1 a 4 W (aparatos en espera)
 *   sobreconsumo: la toma 3 consume 2600 a 3000 W (estufa) y supera el umbral sola
 */
function simulador_generar_lectura(string $modo, array $encendidos): array
{
    $voltaje = rand(2150, 2250) / 10;
    $rangos = $modo === "fantasma"
        ? [1 => [10, 40], 2 => [10, 40], 3 => [10, 40]]           // décimas de W
        : [1 => [1200, 1800], 2 => [700, 1100], 3 => $modo === "sobreconsumo" ? [26000, 30000] : [3000, 5000]];

    $potencia = 0.0;                                               // relé abierto: no pasa corriente
    foreach ($encendidos as $numero => $encendido) {
        if ($encendido) {
            [$minimo, $maximo] = $rangos[$numero] ?? [500, 1000];
            $potencia += rand($minimo, $maximo) / 10;
        }
    }

    return [
        "voltaje" => round($voltaje, 1),
        "corriente" => round($potencia / $voltaje, 2),
        "potencia" => round($potencia, 1),
    ];
}
