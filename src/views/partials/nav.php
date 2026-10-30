<header class="barra">
    <div class="barra-interior">
        <a class="brand" href="<?= $base ?>/">
            <svg class="mark" viewBox="0 0 64 64" aria-hidden="true"><circle class="logo-fondo" cx="32" cy="32" r="30"/><path class="logo-onda" d="M10 32 C16 12, 26 12, 32 32"/><path class="logo-ahorro" d="M32 32 C38 52, 48 52, 54 32"/></svg>
            <div>ENERGHOST<small>Voltec Ergon · Eco Smart Grid</small></div>
        </a>
        <nav class="menu">
            <?php if (!empty($usuario)): ?>
                <a href="<?= $base ?>/panel">Mis dispositivos</a>
                <a href="<?= $base ?>/vincular">Agregar equipo</a>
                <?php if (($usuario["rol"] ?? "") === "admin"): ?>
                    <a href="<?= $base ?>/admin/usuarios">Usuarios</a>
                <?php endif; ?>
                <span class="usuario-nombre"><?= htmlspecialchars($usuario["nombre"]) ?></span>
                <form method="post" action="<?= $base ?>/logout">
                    <button type="submit" class="btn-link">Salir</button>
                </form>
            <?php else: ?>
                <a href="<?= $base ?>/">Inicio</a>
                <a href="<?= $base ?>/login">Ingresar</a>
                <a href="<?= $base ?>/registro" class="btn btn-primary btn-chico" style="color:#fff">Crear cuenta</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
