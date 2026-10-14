<section class="caja-form tarjeta">
    <span class="eyebrow">Energhost</span>
    <h1>Ingresar</h1>
    <p class="texto-suave">Entrá para ver y controlar tus equipos.</p>

    <?php if (!empty($error)): ?>
        <p class="aviso aviso-error" style="margin-top:14px"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= $base ?>/login" class="formulario">
        <input type="hidden" name="volver" value="<?= htmlspecialchars($volver) ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" required value="<?= htmlspecialchars($email) ?>">

        <label for="clave">Contraseña</label>
        <input id="clave" name="clave" type="password" autocomplete="current-password" required>

        <button type="submit" class="btn btn-primary">Ingresar</button>
    </form>

    <p class="texto-suave" style="margin-top:16px">
        ¿No tenés cuenta? <a href="<?= $base ?>/registro">Creá una</a>.
        ¿Olvidaste la contraseña? Pedile una nueva a un administrador del proyecto.
    </p>
</section>
