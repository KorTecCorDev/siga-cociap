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
 * ESCRIBE, PERO NO DEJA RASTRO: todo lo que toca `orden_merito_snapshot` y
 * `orden_merito_rectificado` corre dentro de una TRANSACCIÓN con ROLLBACK (ambas
 * tablas son InnoDB). El paso 4 comprueba que los conteos volvieron a su valor previo.
 *
 * HISTORIA — por qué así: la primera versión "se autolimpiaba" con un DELETE ciego del
 * snapshot y el rectificado de B1. Se escribió el 24/07/2026, cuando B1 aún no tenía
 * snapshot; al día siguiente la Fase C reconstruyó el oficial de B1 (528 filas) y el
 * script pasó a BORRAR un documento oficial ya publicado. Nunca se vuelve a limpiar con
 * DELETE: el rollback restaura exactamente lo que hubiera, lo haya creado esta prueba
 * o no. Además, el escenario "sin oficial" se reproduce DENTRO de la transacción, así
 * que la prueba vuelve a verificar lo que promete (con un oficial ya presente, la
 * primera llamada devolvía 'rectificado' y la aserción quedaba en letra muerta).
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

// ── Guard de entorno ────────────────────────────────────────────────
// Esta verificación escribe. En CLI no hay HTTP_HOST para distinguir el entorno,
// así que se usa el mismo criterio que config/database.php: si existe el archivo
// de secretos externo, estamos en PRODUCCIÓN y no se ejecuta.
$secretosProd = '/home/u761410128/siga_secrets/database.php';
if (is_file($secretosProd)) {
    fwrite(STDERR,
        "ABORTADO: esta verificacion escribe en orden_merito_snapshot y no debe correr\n" .
        "en PRODUCCION. Se detecto el archivo de secretos externo ({$secretosProd}).\n");
    exit(1);
}

$m   = new \App\Models\AnioAcademicoModel();
$o   = new \App\Models\OrdenMeritoModel();
$pub = new \App\Models\PublicacionBoletaModel();

$contar = static fn(string $tabla): int => (int) (
    (new \App\Models\AnioAcademicoModel())
        ->query("SELECT COUNT(*) n FROM {$tabla} WHERE periodo_id = 1")[0]['n']
);

echo "=== 1. Estructura migración 046 ===\n";
$col = $m->query("SHOW COLUMNS FROM periodos_publicacion LIKE 'primera_publicacion_en'");
echo "  columna primera_publicacion_en: " . (!empty($col) ? "OK" : "FALTA") . "\n";
$tab = $m->query("SHOW TABLES LIKE 'orden_merito_rectificado'");
echo "  tabla orden_merito_rectificado: " . (!empty($tab) ? "OK" : "FALTA") . "\n";

echo "\n=== 2. fuePublicado por periodo ===\n";
foreach ([1, 2, 3] as $pid) {
    echo "  periodo $pid: " . ($pub->fuePublicado($pid) ? 'PUBLICADO' : 'no publicado') . "\n";
}

// Estado previo: lo que debe seguir intacto al terminar.
$oficialPrevio     = $contar('orden_merito_snapshot');
$rectificadoPrevio = $contar('orden_merito_rectificado');

echo "\n=== 3. Candado registrarRanking (B1 = periodo 1) ===\n";
echo "  estado previo: oficial={$oficialPrevio}  rectificado={$rectificadoPrevio}\n";

$pdo = \Core\Database::connect();
$pdo->beginTransaction();
try {
    // Escenario A: publicado SIN oficial. Se retira el oficial DENTRO de la
    // transaccion (el rollback lo devuelve) para que la prueba sea real.
    $pdo->exec("DELETE FROM orden_merito_snapshot WHERE periodo_id = 1");
    $pdo->exec("DELETE FROM orden_merito_rectificado WHERE periodo_id = 1");

    $t1  = $o->registrarRanking(1, null, 'VERIF inicial');
    $of1 = $contar('orden_merito_snapshot');
    printf("  1a llamada (publicado, sin oficial): '%s' | oficial=%d  [esperado: oficial, >0]  %s\n",
        $t1, $of1, ($t1 === 'oficial' && $of1 > 0) ? 'OK' : 'FALLO');

    // Escenario B: publicado CON oficial -> va al rectificado y el oficial no cambia.
    $t2  = $o->registrarRanking(1, null, 'VERIF rectificacion');
    $of2 = $contar('orden_merito_snapshot');
    $rec = $contar('orden_merito_rectificado');
    printf("  2a llamada (publicado, con oficial): '%s' | oficial=%d (intacto) | rectificado=%d  [esperado: rectificado, oficial==%d, rectificado>0]  %s\n",
        $t2, $of2, $rec, $of1,
        ($t2 === 'rectificado' && $of2 === $of1 && $rec > 0) ? 'OK' : 'FALLO');

    $info = $o->infoRectificado(1);
    echo "  infoRectificado: " . ($info ? "motivo='{$info['motivo']}' num_alumnos={$info['num_alumnos']}" : "null") . "\n";
} finally {
    $pdo->rollBack();
}

echo "\n=== 4. ROLLBACK (la prueba no deja rastro) ===\n";
$oficialFinal     = $contar('orden_merito_snapshot');
$rectificadoFinal = $contar('orden_merito_rectificado');
printf("  oficial B1:     %d  [previo %d]  %s\n", $oficialFinal, $oficialPrevio,
    $oficialFinal === $oficialPrevio ? 'OK' : 'FALLO: el oficial NO quedo como estaba');
printf("  rectificado B1: %d  [previo %d]  %s\n", $rectificadoFinal, $rectificadoPrevio,
    $rectificadoFinal === $rectificadoPrevio ? 'OK' : 'FALLO: el rectificado NO quedo como estaba');
