<?php
declare(strict_types=1);

/**
 * Database — Singleton de conexión PDO.
 * Lee credenciales de config/database.php y entrega una única instancia PDO.
 */
final class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}
    public function __wakeup(): void
    {
        throw new RuntimeException('Database singleton no puede deserializarse.');
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $cfg = require ROOT . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['dbname'],
                $cfg['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $cfg['user'],
                    $cfg['password'],
                    $cfg['options']
                );
            } catch (PDOException $e) {
                if (defined('DEBUG') && DEBUG) {
                    throw new RuntimeException('Error de conexión: ' . $e->getMessage(), 0, $e);
                }
                throw new RuntimeException('No fue posible conectar con la base de datos.', 0, $e);
            }
        }

        return self::$instance;
    }
}
