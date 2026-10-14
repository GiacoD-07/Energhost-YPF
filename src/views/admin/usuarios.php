<section class="encabezado">
    <div>
        <span class="eyebrow">Administración</span>
        <h1>Usuarios</h1>
        <p class="texto-suave">Las cuentas nuevas entran como "usuario". Un administrador puede ver todos los equipos y gestionar cuentas.</p>
    </div>
</section>

<section class="tarjeta">
    <div class="tabla-scroll">
        <table>
            <thead>
                <tr><th>Nombre</th><th>Email</th><th>Equipos</th><th>Rol</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $fila): ?>
                <tr>
                    <td><?= htmlspecialchars($fila["nombre"]) ?></td>
                    <td class="mono"><?= htmlspecialchars($fila["email"]) ?></td>
                    <td class="mono"><?= (int) $fila["dispositivos"] ?></td>
                    <td>
                        <span class="etiqueta <?= $fila["rol"] === "admin" ? "etiqueta-admin" : "" ?>"><?= htmlspecialchars($fila["rol"]) ?></span>
                    </td>
                    <td>
                        <?php if ($fila["uid"] !== $usuario["uid"]): ?>
                            <div class="form-linea">
                                <form method="post" action="<?= $base ?>/admin/usuarios/<?= rawurlencode($fila["uid"]) ?>/rol">
                                    <input type="hidden" name="rol" value="<?= $fila["rol"] === "admin" ? "usuario" : "admin" ?>">
                                    <button type="submit" class="btn btn-ghost btn-chico">
                                        <?= $fila["rol"] === "admin" ? "Quitar admin" : "Hacer admin" ?>
                                    </button>
                                </form>
                                <form method="post" action="<?= $base ?>/admin/usuarios/<?= rawurlencode($fila["uid"]) ?>/clave"
                                      onsubmit="return confirm('¿Generar una contraseña temporal para <?= htmlspecialchars($fila["nombre"], ENT_QUOTES) ?>?');">
                                    <button type="submit" class="btn btn-ghost btn-chico">Nueva contraseña</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span class="texto-suave">Vos</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
