<?php
// Layout de la presentación pública. Usa el diseño original del equipo (public/css/landing.css).
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0A2E6B">
<title><?= htmlspecialchars($titulo ?? "Voltec Ergon") ?></title>
<link rel="icon" href="<?= $base ?>/favicon.ico">
<link rel="manifest" href="<?= $base ?>/manifest.json">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/css/landing.css">
</head>
<body>
<?= $content ?>
</body>
</html>
