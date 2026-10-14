<?php
// Layout de las páginas de la aplicación (cuenta, panel, administración).
// $content es el HTML de la vista; $base es la subcarpeta de la aplicación.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0A2E6B">
    <title><?= htmlspecialchars($titulo ?? "Energhost") ?></title>
    <link rel="icon" href="<?= $base ?>/favicon.ico">
    <link rel="manifest" href="<?= $base ?>/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/css/app.css">
</head>
<body data-base="<?= htmlspecialchars($base) ?>">

    <?php include __DIR__ . "/../partials/nav.php"; ?>

    <main class="contenedor">
        <?php if (!empty($aviso)): ?>
            <div class="aviso aviso-<?= $aviso["tipo"] === "error" ? "error" : "ok" ?>" role="status">
                <?= htmlspecialchars($aviso["texto"]) ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <?php include __DIR__ . "/../partials/footer.php"; ?>

    <script>
        // Registra el Service Worker para poder instalar la app (PWA)
        if ("serviceWorker" in navigator) {
            navigator.serviceWorker.register("<?= $base ?>/sw.js").catch(() => {});
        }
    </script>
</body>
</html>
