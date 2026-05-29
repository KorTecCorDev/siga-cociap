<?php

namespace Core;

use PDO;
use PDOException;

/**
 * Database
 * Conexión PDO singleton. Un solo punto de acceso a la BD en toda la app.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = require CONFIG_PATH . '/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
            self::$instance->exec("SET time_zone = '-05:00'");
        } catch (PDOException $e) {
            // Muestra el error real — cambiar en producción
            throw new \RuntimeException(
                '[Database] Error de conexión: ' . $e->getMessage()
            );
        }

        return self::$instance;
    }

    public static function get(): PDO
    {
        return self::connect();
    }
}
