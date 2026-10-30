<?php
// Botones grandes para elegir dónde está el equipo (Cocina, Living...) y la opción "Otro".
// Usa: $lugares (lista), $lugar (el elegido) y $lugarOtro (lo escrito en "Otro").
$elegido = $lugar ?? "";
$esOtro = $elegido === "otro";
?>
<fieldset class="lugares">
    <legend>¿Dónde lo enchufaste?</legend>
    <div class="lugares-opciones">
        <?php foreach ($lugares as $i => $opcion): ?>
            <input type="radio" name="lugar" id="lugar-<?= $i ?>" value="<?= htmlspecialchars($opcion) ?>" <?= $elegido === $opcion ? "checked" : "" ?>>
            <label for="lugar-<?= $i ?>" class="lugar"><?= htmlspecialchars($opcion) ?></label>
        <?php endforeach; ?>
        <input type="radio" name="lugar" id="lugar-otro" value="otro" <?= $esOtro ? "checked" : "" ?>>
        <label for="lugar-otro" class="lugar">Otro lugar</label>
    </div>
    <div class="lugar-otro-campo">
        <label for="lugar_otro">Escribí el lugar</label>
        <input id="lugar_otro" name="lugar_otro" type="text" maxlength="40" placeholder="Ej: Habitación de los chicos"
               value="<?= htmlspecialchars($lugarOtro ?? "") ?>">
    </div>
</fieldset>
