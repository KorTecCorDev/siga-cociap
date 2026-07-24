<?php

/**
 * Verificación — Fase B del rediseño del orden de mérito (inmutabilidad + rectificado).
 * Uso: php database/verificaciones/verif_fase_b_orden_merito.php
 *
 * Comprueba la migración 046 y el candado `registrarRanking`:
 *  - estructura (columna primera_publicacion_en + tabla orden_merito_rectificado),
 *  - fuePublicado() por periodo,
 *  - candado: publicado SIN oficial -> 'oficial'; publicado CON oficial -> 'rectificado'
 *    (el oficial NO cambia).
 *
 * SE AUTOLIMPIA: borra el snapshot y el rectificado de B1 (periodo 1) que crea para
 * la prueba, dejando la BD como estaba. Asume la migración 046 aplicada y B1 con sus
 * filas de periodos_publicacion (backfill de la 044).
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH',  ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

spl_autoload_register(function (string $class): void {
    $map = ['Core\\' => CORE_PATH . '/', 'App\\Models\\' => APP_PATH . '/Models/'];
    foreach ($map as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) { require_once $file; return; }
        }
    }
});
require_once CONFIG_PATH . '/app.php';
require_once APP_PATH . '/Helpers/helpers.php';
date_default_timezone_set(config('timezone'));

$m   = new \App\Models\AnioAcademicoModel();
$o   = new \App\Models\OrdenMeritoModel();
$pub = new \App\Models\PublicacionBoletaModel();

echo "=== 1. Estructura migración 046 ===\n";
$col = $m->query("SHOW COLUMNS FROM periodos_publicacion LIKE 'primera_publicacion_en'");
echo "  columna primera_publicacion_en: " . (!empty($col) ? "OK" : "FALTA") . "\n";
$tab = $m->query("SHOW TABLES LIKE 'orden_merito_rectificado'");
echo "  tabla orden_merito_rectificado: " . (!empty($tab) ? "OK" : "FALTA") . "\n";

echo "\n=== 2. fuePublicado por periodo ===\n";
foreach ([1, 2, 3] as $pid) {
    echo "  periodo $pid: " . ($pub->fuePublicado($pid) ? 'PUBLICADO' : 'no publicado') . "\n";
}

echo "\n=== 3. Candado registrarRanking (B1 = periodo 1) ===\n";
$snapAntes = (int) ($m->query("SELECT COUNT(*) n FROM orden_merito_snapshot WHERE periodo_id=1")[0]['n']);
echo "  snapshot oficial B1 antes: $snapAntes\n";

$t1  = $o->registrarRanking(1, null, 'VERIF inicial');
$of1 = (int) ($m->query("SELECT COUNT(*) n FROM orden_merito_snapshot WHERE periodo_id=1")[0]['n']);
echo "  1a llamada (publicado, sin oficial): '$t1' | oficial=$of1  [esperado: oficial, >0]\n";

$t2   = $o->registrarRanking(1, null, 'VERIF rectificacion');
$of2  = (int) ($m->query("SELECT COUNT(*) n FROM orden_merito_snapshot WHERE periodo_id=1")[0]['n']);
$rec  = (int) ($m->query("SELECT COUNT(*) n FROM orden_merito_rectificado WHERE periodo_id=1")[0]['n']);
echo "  2a llamada (publicado, con oficial): '$t2' | oficial=$of2 (intacto) | rectificado=$rec  [esperado: rectificado, oficial==$of1, rectificado>0]\n";

$info = $o->infoRectificado(1);
echo "  infoRectificado: " . ($info ? "motivo='{$info['motivo']}' num_alumnos={$info['num_alumnos']}" : "null") . "\n";

echo "\n=== 4. LIMPIEZA (restaurar B1 a snapshot/rectificado vacío) ===\n";
$m->execute("DELETE FROM orden_merito_snapshot WHERE periodo_id=1");
$m->execute("DELETE FROM orden_merito_rectificado WHERE periodo_id=1");
$c1 = (int) ($m->query("SELECT COUNT(*) n FROM orden_merito_snapshot WHERE periodo_id=1")[0]['n']);
$c2 = (int) ($m->query("SELECT COUNT(*) n FROM orden_merito_rectificado WHERE periodo_id=1")[0]['n']);
echo "  snapshot B1=$c1  rectificado B1=$c2  (ambos deben ser 0)\n";
