<?php
// Panel de un equipo. La página llega vacía y public/js/dispositivo.js la completa
// consultando la API cada 2 segundos.
?>
<section class="encabezado" id="panel-dispositivo" data-id="<?= htmlspecialchars($dispositivoId) ?>">
    <div>
        <a href="<?= $base ?>/panel" class="texto-suave">← Mis dispositivos</a>
        <h1 style="margin-top:6px"><?= htmlspecialchars($nombre) ?></h1>
        <p class="texto-suave mono"><?= htmlspecialchars($dispositivoId) ?></p>
    </div>
    <div id="estado-conexion" class="estado" role="status">
        <span class="punto"></span><span id="estado-texto">Conectando…</span>
    </div>
</section>

<!-- Avisos: se muestran u ocultan desde dispositivo.js -->
<div id="aviso-sobreconsumo" class="aviso aviso-rojo oculto">
    <strong>Corte por sobreconsumo.</strong>
    El equipo cortó la corriente porque el consumo superó <?= (int) $umbralSobreconsumo ?> W.
    Se apagaron las <?= (int) $cantidadReles ?> tomas. Revisá qué estaba enchufado antes de volver a encender.
</div>
<div id="aviso-sensor" class="aviso aviso-rojo oculto">
    <strong>Error en el sensor.</strong> El PZEM-004T no responde: los valores no se están actualizando.
</div>
<div id="aviso-alerta" class="aviso aviso-amarillo oculto"></div>
<div id="aviso-fantasma" class="aviso aviso-amarillo oculto">
    <strong>Consumo fantasma.</strong> Lo enchufado está en espera consumiendo poca energía de forma continua.
</div>
<div id="aviso-vampiro" class="aviso aviso-amarillo oculto"></div>
<div id="aviso-general" class="aviso aviso-gris oculto"></div>

<p class="texto-suave" style="margin:0 0 8px">Medición total de las <?= (int) $cantidadReles ?> tomas del equipo.</p>
<section id="metricas" class="grilla grilla-4">
    <article class="tarjeta" id="tarjeta-potencia">
        <h2>Potencia</h2>
        <p><span id="val-potencia" class="valor">--</span> <span class="unidad">W</span></p>
    </article>
    <article class="tarjeta">
        <h2>Voltaje</h2>
        <p><span id="val-voltaje" class="valor">--</span> <span class="unidad">V</span></p>
    </article>
    <article class="tarjeta">
        <h2>Corriente</h2>
        <p><span id="val-corriente" class="valor">--</span> <span class="unidad">A</span></p>
    </article>
    <article class="tarjeta">
        <h2>Energía</h2>
        <p><span id="val-energia" class="valor">--</span> <span class="unidad">kWh</span></p>
    </article>
</section>

<section class="grilla grilla-2">
    <article class="tarjeta">
        <h2>Costo estimado</h2>
        <p><span class="unidad">$</span> <span id="val-costo" class="valor valor-chico">--</span></p>
        <p class="texto-suave">Sobre la energía acumulada del medidor</p>
    </article>
    <article class="tarjeta">
        <h2>Huella de carbono</h2>
        <p><span id="val-co2" class="valor valor-chico">--</span> <span class="unidad">kg CO₂</span></p>
        <p class="texto-suave">Sobre la energía acumulada del medidor</p>
    </article>
</section>

<section class="tarjeta">
    <h2>Tomas</h2>
    <div class="reles <?= $cantidadReles > 1 ? "reles-3" : "" ?>">
        <?php for ($numero = 1; $numero <= $cantidadReles; $numero++): ?>
            <label class="rele">
                <span>
                    <span class="rele-nombre"><?= htmlspecialchars($nombresTomas[$numero]) ?></span>
                    <?php if ($nombresTomas[$numero] !== "Toma {$numero}"): ?>
                        <span class="rele-toma">Toma <?= $numero ?></span>
                    <?php endif; ?>
                </span>
                <span id="rele-estado-<?= $numero ?>" class="rele-estado">--</span>
                <input type="checkbox" id="rele-<?= $numero ?>" data-rele="<?= $numero ?>" class="interruptor">
            </label>
        <?php endfor; ?>
    </div>
    <p id="reles-mensaje" class="texto-suave" role="status" style="margin-top:10px"></p>
</section>

<section class="tarjeta">
    <h2>Potencia de las últimas 2 horas</h2>
    <div id="grafico" class="grafico">
        <p class="texto-suave">Cargando historial…</p>
    </div>
</section>

<section class="tarjeta">
    <h2>Actividad</h2>
    <div class="tabla-scroll">
        <table>
            <thead><tr><th>Cuándo</th><th>Quién</th><th>Qué</th></tr></thead>
            <tbody id="tabla-eventos"><tr><td colspan="3" class="texto-suave">Cargando…</td></tr></tbody>
        </table>
    </div>
</section>

<?php if ($esDuenio): ?>
<section class="tarjeta">
    <h2>Configuración del equipo</h2>
    <form method="post" action="<?= $base ?>/panel/<?= rawurlencode($dispositivoId) ?>/nombre" class="formulario" style="max-width:420px">
        <label for="nuevo-nombre" class="texto-suave">Nombre del equipo</label>
        <input id="nuevo-nombre" name="nombre" type="text" maxlength="40" required value="<?= htmlspecialchars($nombre) ?>">
        <?php foreach ($nombresTomas as $numero => $nombreToma): ?>
            <label for="nombre-toma-<?= $numero ?>" class="texto-suave">Toma <?= $numero ?></label>
            <input id="nombre-toma-<?= $numero ?>" name="toma_<?= $numero ?>" type="text" maxlength="40" value="<?= htmlspecialchars($nombreToma) ?>">
        <?php endforeach; ?>
        <button type="submit" class="btn btn-ghost btn-chico">Guardar nombres</button>
    </form>
    <form method="post" action="<?= $base ?>/panel/<?= rawurlencode($dispositivoId) ?>/desvincular" style="margin-top:16px"
          onsubmit="return confirm('¿Quitar este equipo de tu cuenta? Se puede volver a vincular con su código.');">
        <button type="submit" class="btn btn-peligro btn-chico">Quitar equipo de mi cuenta</button>
    </form>
</section>
<?php endif; ?>

<script src="<?= $base ?>/js/dispositivo.js"></script>
