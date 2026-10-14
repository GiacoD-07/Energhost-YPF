<header class="barra">
    <div class="barra-interior">
        <a class="brand" href="<?= $base ?>/">
            <div class="mark"></div>
            <div>ENERGHOST<small>Voltec Ergon · Eco Smart Grid</small></div>
        </a>
        <nav class="menu">
            <?php if (!empty($usuario)): ?>
                <a href="<?= $base ?>/panel">Mis dispositivos</a>
                <a href="<?= $base ?>/vincular">Vincular equipo</a>
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
