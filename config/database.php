<?php

/**
 * Configuración de base de datos — SIGA-COCIAP
 *
 * Las credenciales de PRODUCCIÓN viven FUERA del repositorio y del docroot
 * (el auto-deploy de Hostinger hace un checkout limpio y borra todo lo que no
 * esté versionado). Si existe el archivo de secretos externo, tiene prioridad.
 *
 * En desarrollo local (XAMPP) ese archivo no existe → usa el fallback de abajo.
 * Este archivo NO contiene secretos de producción, por eso sí se versiona.
 */

$secretosExternos = [
    '/home/u761410128/siga_secrets/database.php', // Hostinger (producción)
];

foreach ($secretosExternos as $ruta) {
    if (is_file($ruta)) {
        return require $ruta;
    }
}

// Fallback para desarrollo local (XAMPP) — sin secretos reales.
return [
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => '3306',
    'database' => 'siga_cociap',
    'username' => 'root',
    'password' => '',              // XAMPP por defecto no tiene contraseña
    'charset'  => 'utf8mb4',
    'prefix'   => '',
];
