<section class="caja-form tarjeta" style="max-width:520px">
    <span class="eyebrow">Nuevo equipo</span>
    <h1>Vincular un equipo</h1>
    <p class="texto-suave">
        El equipo tiene que estar enchufado y conectado a internet. El identificador y el código
        están en la etiqueta del gabinete y en la pantalla del equipo. Si escaneaste el QR, ya están completos.
    </p>

    <?php if (!empty($error)): ?>
        <p class="aviso aviso-error" style="margin-top:14px"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= $base ?>/vincular" class="formulario">
        <label for="equipo">Identificador del equipo</label>
        <input id="equipo" name="equipo" type="text" required maxlength="30" placeholder="Ej: A1B2C3"
               autocapitalize="characters" value="<?= htmlspecialchars($equipo) ?>">
        <span class="ayuda">Las 6 letras y números después de "EcoSmart_".</span>

        <label for="codigo">Código de vinculación</label>
        <input id="codigo" name="codigo" type="text" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
               placeholder="6 números" autocomplete="off" value="<?= htmlspecialchars($codigo) ?>">

        <label for="nombre">¿Dónde está el equipo?</label>
        <input id="nombre" name="nombre" type="text" required maxlength="40" placeholder="Ej: Cocina"
               value="<?= htmlspecialchars($nombre) ?>">

        <fieldset class="tomas-form">
            <legend>¿Qué artefacto está enchufado en cada toma?</legend>
            <span class="ayuda">Opcional: una toma sin nombre aparece como "Toma 1", "Toma 2"… Se puede cambiar después.</span>
            <?php $ejemplos = [1 => "Heladera", 2 => "Microondas", 3 => "Pava eléctrica"]; ?>
            <?php foreach ($nombresTomas as $numero => $nombreToma): ?>
                <label for="toma_<?= $numero ?>">Toma <?= $numero ?></label>
                <input id="toma_<?= $numero ?>" name="toma_<?= $numero ?>" type="text" maxlength="40"
                       placeholder="Ej: <?= $ejemplos[$numero] ?? "Artefacto" ?>" value="<?= htmlspecialchars($nombreToma) ?>">
            <?php endforeach; ?>
        </fieldset>

        <button type="submit" class="btn btn-primary">Vincular</button>
    </form>
</section>
