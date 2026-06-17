<?php

/**
 * Backfill del snapshot del orden de mérito para bimestres YA cerrados.
 *
 * Se ejecuta UNA VEZ por entorno, DESPUÉS de correr la migración
 * 023_orden_merito_snapshot.sql. Es idempotente: regenera (DELETE + INSERT) el
 * snapshot de cada bimestre cerrado, así que puede correrse varias veces.
 *
 * Salta —sin grabar— los bimestres con empates sin resolver: congelar un empate
 * pendiente petrificaría un orden arbitrario. Resuélvelos en el orden de mérito
 * y vuelve a correr el script.
 *
 * Uso:  php database/backfill_orden_merito.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH',  ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

spl_autoload_register(function (string $class): void {
    $map = [
        'Core\\'        => CORE_PATH . '/',
        'App\\Models\\' => APP_PATH . '/Models/',
    ];
    foreach ($map as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

require_once CONFIG_PATH . '/app.php';
require_once APP_PATH    . '/Helpers/helpers.php';
date_default_timezone_set(config('timezone'));

$anioModel  = new \App\Models\AnioAcademicoModel();
$ordenModel = new \App\Models\OrdenMeritoModel();

$periodos = $anioModel->query("
    SELECT id, nombre_display
    FROM periodos
    WHERE estado = 'cerrado'
    ORDER BY anio_id, numero
");

if (!$periodos) {
    echo "No hay bimestres cerrados. Nada que hacer.\n";
    exit(0);
}

$generados = 0;
$saltados  = 0;

foreach ($periodos as $p) {
    $pid    = (int) $p['id'];
    $nombre = $p['nombre_display'];

    $empates = $ordenModel->gradosConEmpatesPendientes($pid);
    if (!empty($empates)) {
        echo "SALTADO  periodo {$pid} ({$nombre}): empates sin resolver en "
            . implode('; ', array_unique($empates)) . ".\n";
        $saltados++;
        continue;
    }

    $ordenModel->generarSnapshot($pid, null);
    echo "OK       periodo {$pid} ({$nombre}): snapshot generado.\n";
    $generados++;
}

echo "\nBackfill completo: {$generados} generado(s), {$saltados} saltado(s).\n";
if ($saltados > 0) {
    echo "Resuelve los empates pendientes y vuelve a correr el script.\n";
}
