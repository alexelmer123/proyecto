<?php
/**
 * database/install.php
 *
 * Crea (o restablece) el usuario administrador demo con un hash bcrypt válido
 * generado por PHP. Ejecutar UNA vez después de cargar database/schema.sql:
 *
 *    php database/install.php
 *
 * Credenciales sembradas:
 *    email:    admin@vitralia.co
 *    password: vidrio123
 *
 * Cámbialas tras el primer login.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script sólo puede ejecutarse desde la línea de comandos.\n");
}

define('ROOT', dirname(__DIR__));

require ROOT . '/config/config.php';
$cfg = require ROOT . '/config/database.php';

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $cfg['host'], $cfg['port'], $cfg['dbname'], $cfg['charset']);

try {
    $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], $cfg['options']);
} catch (PDOException $e) {
    fwrite(STDERR, "ERROR de conexión: " . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, "¿Cargaste primero database/schema.sql?\n");
    exit(1);
}

$email    = 'admin@vitralia.co';
$password = 'vidrio123';
$hash     = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "INSERT INTO usuarios (nombre, email, password, rol, activo)
     VALUES (:n, :e, :p, 'admin', 1)
     ON DUPLICATE KEY UPDATE password = VALUES(password), activo = 1"
);
$stmt->execute([
    ':n' => 'Administrador del taller',
    ':e' => $email,
    ':p' => $hash,
]);

echo "✓ Usuario administrador listo.\n";
echo "  email:    {$email}\n";
echo "  password: {$password}\n";
echo "  hash:     {$hash}\n";
echo "Recuerda cambiarla tras iniciar sesión.\n";
