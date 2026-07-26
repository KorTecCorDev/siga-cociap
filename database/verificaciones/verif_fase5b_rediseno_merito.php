<?php

/**
 * Verificación — Fase 5b del rediseño 2 del orden de mérito (candado de reapertura).
 * SOLO LECTURA sobre los datos: lo que escribe corre dentro de una TRANSACCIÓN con
 * ROLLBACK, así que la BD queda exactamente como estaba.
 * Uso: php database/verificaciones/verif_fase5b_rediseno_merito.php
 *
 * Comprueba:
 *  0. CONTROL — el cálculo en vivo de B1 difiere del snapshot (si fueran iguales,
 *     los pasos siguientes no probarían nada).
 *  1. Con el bimestre CERRADO y publicado, el ranking sale del snapshot (528 en B1).
 *  2. Simulando la REAPERTURA (estado='activo'), el ranking SIGUE saliendo del
 *     snapshot porque el bimestre ya fue publicado (candado 046). Antes de esta
 *     fase caía al cálculo en vivo y el claustro veía otro documento.
 *  3. gradosConEmpatesPendientes usa el cálculo EN VIVO incluso en ese estado: se
 *     borran los desempates resueltos dentro de la transacción para hacer aflorar
 *     empates que el snapshot NO tiene. Si el método los ve, mira el vivo.
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

use App\Models\OrdenMeritoModel;
use Core\Database;

$PERIODO = 1; // B1: cerrado, publicado en ambos niveles y con snapshot congelado.

/** Total de alumnos rankeados + firma de puestos, para comparar documentos. */
$firma = static function (OrdenMeritoModel $o, int $periodoId, ?ReflectionMethod $live = null): array {
    $total = 0;
    $hash  = '';
    foreach ($o->gradosConRanking($periodoId) as $g) {
        $filas = $live
            ? $live->invoke($o, (int) $g['id'], $periodoId)
            : $o->rankingGrado((int) $g['id'], $periodoId);
        foreach ($filas as $f) {
            $total++;
            $hash .= $f['matricula_id'] . ':' . $f['puesto'] . '|';
        }
    }
    return ['total' => $total, 'hash' => md5($hash)];
};

$live = new ReflectionMethod(OrdenMeritoModel::class, 'rankingGradoLive');
$live->setAccessible(true);

$pdo = Database::connect();
$o   = new OrdenMeritoModel();

echo "=== 0. CONTROL: el vivo debe diferir del snapshot ===\n";
$vivo     = $firma($o, $PERIODO, $live);
$snapRows = (int) ($o->query("SELECT COUNT(*) n FROM orden_merito_snapshot WHERE periodo_id = ?", [$PERIODO])[0]['n']);
$cerrado  = $firma($o, $PERIODO);
printf("  calculo en vivo:   %d alumnos (firma %s)\n", $vivo['total'], substr($vivo['hash'], 0, 8));
printf("  snapshot oficial:  %d alumnos (firma %s)\n", $snapRows, substr($cerrado['hash'], 0, 8));
printf("  %s\n", $vivo['hash'] !== $cerrado['hash']
    ? 'OK: difieren, la prueba de reapertura discrimina'
    : 'INUTIL: son identicos, los pasos 2 y 3 no prueban nada en este dataset');

echo "\n=== 1. Estado actual (B1 cerrado + publicado) ===\n";
printf("  ranking leido: %d alumnos\n", $cerrado['total']);
printf("  %s\n", $cerrado['total'] === $snapRows
    ? 'OK: coincide con el snapshot congelado'
    : 'REVISAR: no coincide con el snapshot');

$pdo->beginTransaction();
try {
    echo "\n=== 2. Simulacion de REAPERTURA (transaccion + rollback) ===\n";
    $pdo->prepare("UPDATE periodos SET estado = 'activo' WHERE id = ?")->execute([$PERIODO]);
    printf("  estado del periodo durante la prueba: %s\n",
        $pdo->query("SELECT estado FROM periodos WHERE id = {$PERIODO}")->fetchColumn());

    // Instancia NUEVA: debeUsarSnapshot memoiza por request.
    $oReabierto = new OrdenMeritoModel();
    $reabierto  = $firma($oReabierto, $PERIODO);
    printf("  ranking leido: %d alumnos (firma %s)\n", $reabierto['total'], substr($reabierto['hash'], 0, 8));
    printf("  %s\n", $reabierto['hash'] === $cerrado['hash']
        ? 'OK: sigue leyendo el snapshot oficial (candado 046 respetado)'
        : 'FALLO: el documento cambio al reabrir (se cayo al calculo en vivo)');

    echo "\n=== 3. gradosConEmpatesPendientes usa el calculo EN VIVO ===\n";
    $resueltos = (int) $pdo->query("SELECT COUNT(*) FROM desempates_merito WHERE periodo_id = {$PERIODO}")->fetchColumn();
    $pdo->prepare("DELETE FROM desempates_merito WHERE periodo_id = ?")->execute([$PERIODO]);
    printf("  desempates resueltos borrados (solo en la transaccion): %d\n", $resueltos);

    $oSinDesempates = new OrdenMeritoModel();
    $reportados     = $oSinDesempates->gradosConEmpatesPendientes($PERIODO);
    printf("  grados con empate pendiente que reporta el metodo: %d\n", count($reportados));
    foreach (array_slice($reportados, 0, 3) as $g) {
        printf("    - %s\n", $g);
    }
    printf("  %s\n", count($reportados) > 0
        ? 'OK: ve los empates del calculo en vivo (el snapshot no los tiene -> reportaria 0)'
        : 'FALLO: reporta 0, esta leyendo el snapshot en vez del vivo');
} finally {
    $pdo->rollBack();
}

echo "\n=== 4. Rollback ===\n";
printf("  estado del periodo: %s\n",
    $pdo->query("SELECT estado FROM periodos WHERE id = {$PERIODO}")->fetchColumn());
printf("  desempates de B1:   %d\n",
    (int) $pdo->query("SELECT COUNT(*) FROM desempates_merito WHERE periodo_id = {$PERIODO}")->fetchColumn());
