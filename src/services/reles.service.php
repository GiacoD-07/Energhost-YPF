<?php

// Responsabilidad: reglas de los relés: validar órdenes, rearmar el corte por
// sobreconsumo y dejar registrado quién hizo cada cambio.
// Cada equipo tiene 3 tomas y un relé por toma: rele_1 controla la toma 1, y así.

require_once __DIR__ . "/../persistence/reles.persistence.php";
require_once __DIR__ . "/../persistence/eventos.persistence.php";
require_once __DIR__ . "/dispositivos.service.php";

/**
 * Cantidad de relés (tomas) que informó el equipo. Si no lo informó, se asumen las 3 tomas.
 */
function cantidad_reles_service(array $info): int
{
    $cantidad = (int) ($info["cantidad_reles"] ?? CANTIDAD_TOMAS);

    return max(1, min(3, $cantidad));
}

/**
 * Estado de los relés del equipo. Si no hay datos, se informan apagados (estado seguro).
 */
function obtener_reles_service(array $usuario, string $dispositivoId): array
{
    $info = verificar_acceso_dispositivo_service($usuario, $dispositivoId);
    $guardados = obtener_reles_persistence($dispositivoId) ?? [];

    $reles = [];
    for ($numero = 1; $numero <= cantidad_reles_service($info); $numero++) {
        $reles["rele_{$numero}"] = (int) ($guardados["rele_{$numero}"] ?? 0) === 1 ? 1 : 0;
    }

    return $reles;
}

/**
 * Valida y guarda la orden de un relé, y la registra en la actividad del equipo.
 * Encender también rearma el corte por sobreconsumo.
 */
function cambiar_rele_service(array $usuario, string $dispositivoId, $numero, $estado): void
{
    $info = verificar_acceso_dispositivo_service($usuario, $dispositivoId);
    $cantidad = cantidad_reles_service($info);

    if (!is_int($numero) || $numero < 1 || $numero > $cantidad || !in_array($estado, [0, 1], true)) {
        $rango = $cantidad === 1 ? "El relé debe ser 1" : "El relé debe estar entre 1 y {$cantidad}";
        throw new InvalidArgumentException("{$rango} y el estado debe ser 0 o 1");
    }

    actualizar_rele_persistence($dispositivoId, $numero, $estado);

    if ($estado === 1) {
        rearmar_sobreconsumo_persistence($dispositivoId);
    }

    // Ej: "Apagó Heladera (toma 1)"
    $nombreArtefacto = nombres_tomas_service($dispositivoId, $cantidad)[$numero];

    registrar_evento_persistence($dispositivoId, [
        "usuario" => $usuario["uid"],
        "nombre_usuario" => $usuario["nombre"],
        "accion" => $estado === 1 ? "encendido" : "apagado",
        "detalle" => ($estado === 1 ? "Encendió " : "Apagó ") . "{$nombreArtefacto} (toma {$numero})",
    ]);
}
