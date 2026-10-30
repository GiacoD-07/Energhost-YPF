<?php
// Vinculación escribiendo los datos a mano (cuando no se usa el QR, o el QR trajo un error).
?>
<section class="caja-form tarjeta vincular-manual">
    <span class="eyebrow">Nuevo equipo</span>
    <h1>Agregar un equipo</h1>

    <div class="aviso aviso-gris consejo-qr">
        <strong>Lo más fácil:</strong> escaneá con la cámara del celular el código QR de la etiqueta del equipo.
        Los datos se completan solos.
    </div>

    <?php if (!empty($error)): ?>
        <p class="aviso aviso-error" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= $base ?>/vincular" class="formulario">
        <input type="hidden" name="paso" value="manual">

        <div class="paso">
            <span class="paso-numero">1</span>
            <div class="paso-campos">
                <label for="equipo">Identificador del equipo</label>
                <input id="equipo" name="equipo" type="text" required maxlength="30" placeholder="Ej: A1B2C3"
                       autocapitalize="characters" autocomplete="off" value="<?= htmlspecialchars($equipo) ?>">
                <span class="ayuda">Son 6 letras y números de la etiqueta. También sirve la MAC completa (24:6F:28:A1:B2:C3).</span>
            </div>
        </div>

        <div class="paso">
            <span class="paso-numero">2</span>
            <div class="paso-campos">
                <label for="codigo">Código de la etiqueta</label>
                <input id="codigo" name="codigo" type="text" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                       placeholder="6 números" autocomplete="off" value="<?= htmlspecialchars($codigo) ?>">
                <span class="ayuda">Prueba que el equipo está en tus manos: así nadie más lo puede agregar a su cuenta.</span>
            </div>
        </div>

        <div class="paso">
            <span class="paso-numero">3</span>
            <div class="paso-campos">
                <?php include __DIR__ . "/../partials/lugares.php"; ?>
            </div>
        </div>

        <details class="tomas-opcionales">
            <summary>¿Qué hay enchufado en cada toma? (opcional)</summary>
            <div class="tomas-form">
                <?php $ejemplos = [1 => "Heladera", 2 => "Microondas", 3 => "Pava eléctrica"]; ?>
                <?php foreach ($nombresTomas as $numero => $nombreToma): ?>
                    <label for="toma_<?= $numero ?>">Toma <?= $numero ?></label>
                    <input id="toma_<?= $numero ?>" name="toma_<?= $numero ?>" type="text" maxlength="40"
                           placeholder="Ej: <?= $ejemplos[$numero] ?? "Artefacto" ?>" value="<?= htmlspecialchars($nombreToma) ?>">
                <?php endforeach; ?>
            </div>
        </details>

        <button type="submit" class="btn btn-primary btn-grande">Agregar equipo</button>
    </form>
</section>
