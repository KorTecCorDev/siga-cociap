<?php

namespace Core;

use PDO;
use PDOException;

/**
 * Database
 * Conexión PDO singleton. Un solo punto de acceso a la BD en toda la app.
 * Equivalente al DB facade de Laravel.
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
        } catch (PDOException $e) {
            // En producción: loguear y mostrar página de error genérica
            if (config('app.debug')) {
                throw $e;
            }
            http_response_code(500);
            exit('Error de conexión a la base de datos.');
        }

        return self::$instance;
    }

    /** Shortcut para obtener la conexión */
    public static function get(): PDO
    {
        return self::connect();
    }
}
