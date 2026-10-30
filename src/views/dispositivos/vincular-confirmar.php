<?php
// Vinculación en 3 toques: la persona escaneó el QR, el equipo existe, está libre y el código
// es correcto. Solo falta elegir dónde está y tocar "Vincular".
?>
<section class="caja-form tarjeta vincular-qr">
    <div class="equipo-encontrado">
        <svg class="equipo-dibujo" viewBox="0 0 120 64" aria-hidden="true">
            <rect x="2" y="10" width="116" height="44" rx="14" class="dibujo-cuerpo"/>
            <circle cx="32" cy="32" r="10" class="dibujo-toma"/>
            <circle cx="60" cy="32" r="10" class="dibujo-toma"/>
            <circle cx="88" cy="32" r="10" class="dibujo-toma"/>
            <circle cx="106" cy="20" r="3" class="dibujo-luz"/>
        </svg>
        <div>
            <span class="eyebrow">Paso 2 de 2</span>
            <h1>¡Encontramos tu equipo!</h1>
            <p class="texto-suave mono"><?= htmlspecialchars($dispositivoId) ?> · 3 tomas</p>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <p class="aviso aviso-error" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= $base ?>/vincular" class="formulario">
        <input type="hidden" name="paso" value="confirmar">
        <input type="hidden" name="equipo" value="<?= htmlspecialchars($equipo) ?>">
        <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
        <input type="hidden" name="dispositivo" value="<?= htmlspecialchars($dispositivoId) ?>">

        <?php include __DIR__ . "/../partials/lugares.php"; ?>

        <button type="submit" class="btn btn-primary btn-grande">Vincular mi equipo</button>
        <p class="ayuda centrado">Después vas a poder ponerle nombre a cada toma (por ejemplo "Heladera").</p>
    </form>

    <p class="texto-suave centrado" style="margin-top:18px">
        ¿No es tu equipo? <a href="<?= $base ?>/vincular">Escribir los datos a mano</a>
    </p>
</section>
