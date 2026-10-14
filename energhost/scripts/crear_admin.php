<?php

// Convierte en administrador a un usuario que ya se registró en el panel.
// Uso: php scripts/crear_admin.php email@ejemplo.com
//
// El primer administrador se crea así; después, los administradores pueden
// dar ese rol a otros desde la página "Usuarios" del panel.

require __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../src/persistence/usuarios.persistence.php";

Dotenv\Dotenv::createImmutable(__DIR__ . "/..")->safeLoad();

$email = trim($argv[1] ?? "");
if ($email === "") {
    fwrite(STDERR, "Uso: php scripts/crear_admin.php email@ejemplo.com" . PHP_EOL);
    exit(1);
}

try {
    $uid = buscar_uid_por_email_persistence($email);
    if ($uid === null) {
        fwrite(STDERR, "No hay ninguna cuenta con ese email. Registrate primero en /registro." . PHP_EOL);
        exit(1);
    }

    actualizar_usuario_persistence($uid, ["rol" => "admin"]);
    echo "Listo: {$email} ahora es administrador. Tiene que volver a ingresar para ver el cambio." . PHP_EOL;
} catch (RuntimeException $e) {
    fwrite(STDERR, "No se pudo conectar con Firebase: revisá FIREBASE_URL y FIREBASE_AUTH en .env" . PHP_EOL);
    exit(1);
}
