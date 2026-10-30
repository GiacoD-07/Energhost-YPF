<section class="encabezado">
    <div>
        <span class="eyebrow">Hola, <?= htmlspecialchars($usuario["nombre"]) ?></span>
        <h1>Mis dispositivos</h1>
    </div>
    <?php if (!empty($dispositivos)): ?>
        <a href="<?= $base ?>/vincular" class="btn btn-primary">+ Agregar equipo</a>
    <?php endif; ?>
</section>

<?php if (!empty($error)): ?>
    <div class="aviso aviso-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (empty($dispositivos) && empty($error)): ?>
    <section class="tarjeta vacio">
        <h2>Todavía no tenés equipos</h2>
        <ol class="pasos-inicio">
            <li><strong>Enchufá el equipo</strong> en la pared.</li>
            <li><strong>Conectalo a tu Wi-Fi.</strong> La primera vez aparece la red "Energhost-Config": entrá desde el celular y elegí tu Wi-Fi.</li>
            <li><strong>Escaneá el QR</strong> de la etiqueta con la cámara del celular.</li>
        </ol>
        <a href="<?= $base ?>/vincular" class="btn btn-primary">Agregar mi primer equipo</a>
    </section>
<?php else: ?>
    <section class="grilla grilla-3" id="lista-equipos">
        <?php foreach ($dispositivos as $equipo): ?>
            <a class="tarjeta equipo" href="<?= $base ?>/panel/<?= rawurlencode($equipo["id"]) ?>" data-id="<?= htmlspecialchars($equipo["id"]) ?>">
                <div class="equipo-cabecera">
                    <div>
                        <div class="equipo-nombre"><?= htmlspecialchars($equipo["nombre"]) ?></div>
                        <div class="equipo-id"><?= htmlspecialchars($equipo["id"]) ?></div>
                    </div>
                    <span class="estado <?= $equipo["en_linea"] ? "estado-verde" : "estado-rojo" ?>" data-campo="estado">
                        <span class="punto"></span><span data-campo="estado-texto"><?= $equipo["en_linea"] ? "EN LÍNEA" : "DESCONECTADO" ?></span>
                    </span>
                </div>
                <p>
                    <span class="valor valor-chico" data-campo="potencia"><?= $equipo["potencia"] !== null ? number_format($equipo["potencia"], 1, ",", ".") : "--" ?></span>
                    <span class="unidad">W</span>
                    <span class="texto-suave equipo-tomas" data-campo="tomas"><?= (int) $equipo["tomas_encendidas"] ?> de <?= (int) $equipo["tomas"] ?> tomas encendidas</span>
                </p>
                <p class="texto-suave" data-campo="alerta">
                    <?= $equipo["sobreconsumo"] ? "Corte por sobreconsumo" : htmlspecialchars($equipo["alerta"] ?? "Sin lecturas") ?>
                </p>
            </a>
        <?php endforeach; ?>
    </section>
    <script src="<?= $base ?>/js/panel.js"></script>
<?php endif; ?>
