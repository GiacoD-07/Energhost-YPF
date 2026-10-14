<section class="caja-form tarjeta">
    <span class="eyebrow">Energhost</span>
    <h1>Crear cuenta</h1>
    <p class="texto-suave">Con tu cuenta vas a ver solo tus equipos y quedará registrado quién controla cada uno.</p>

    <?php if (!empty($error)): ?>
        <p class="aviso aviso-error" style="margin-top:14px"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= $base ?>/registro" class="formulario">
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" type="text" autocomplete="name" maxlength="60" required value="<?= htmlspecialchars($nombre) ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" required value="<?= htmlspecialchars($email) ?>">

        <label for="clave">Contraseña</label>
        <input id="clave" name="clave" type="password" autocomplete="new-password" minlength="8" required>
        <span class="ayuda">Al menos 8 caracteres.</span>

        <label for="confirmacion">Repetir contraseña</label>
        <input id="confirmacion" name="confirmacion" type="password" autocomplete="new-password" minlength="8" required>

        <button type="submit" class="btn btn-primary">Crear cuenta</button>
    </form>

    <p class="texto-suave" style="margin-top:16px">
        ¿Ya tenés cuenta? <a href="<?= $base ?>/login">Ingresá</a>.
        Solo guardamos tu nombre, tu email y la contraseña cifrada.
    </p>
</section>
