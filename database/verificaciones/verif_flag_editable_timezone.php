<?php

/**
 * Verificación — el flag `editable` de asistencia y conducta NO depende de NOW().
 * Uso: php database/verificaciones/verif_flag_editable_timezone.php
 *
 * ESCRIBE (mueve `limite_notas` para recorrer las fronteras), pero TODO corre
 * dentro de una TRANSACCIÓN con ROLLBACK y el paso final comprueba que el valor
 * original volvió. Por eso lleva el guard de secretos: NO correr en producción.
 *
 * QUÉ COMPRUEBA
 *   1. Con el motor en hora local (caso XAMPP), el flag de los dos modelos
 *      coincide con el guard REAL de escritura (`ConductaModel::periodoEditable`,
 *      que resuelve el "ahora" en PHP) en las cuatro fronteras: límite futuro,
 *      límite pasado, límite dentro de la franja crítica y límite NULL.
 *   2. LA PRUEBA DURA — con la sesión MySQL forzada a **UTC**, que es como corre
 *      producción (Hostinger, +5 h sobre Lima), la consulta VIEJA con `NOW()`
 *      contradice al guard real y la nueva no. Sin este paso la verificación no
 *      probaría nada: en local el desfase es 0 y el bug no se manifiesta.
 *
 * CONTEXTO: hasta el 10/08/2026 `AsistenciaModel::listarPeriodosActivos` y
 * `ConductaModel::listarPeriodosActivos` calculaban `editable` con
 * `NOW() <= p.limite_notas` en SQL, mientras los guards de escritura comparaban
 * con `time()` en PHP. En producción eso apagaba la UI **5 horas antes** de que
 * el sistema dejara de aceptar escrituras. El docblock de `PublicacionBoletaModel`
 * ya advertía la trampa: el "ahora" se resuelve en PHP y viaja como parámetro.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH',  ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Guarda: nunca contra produccion (este script ESCRIBE, aunque haga rollback).
if (is_file('/home/u761410128/siga_secrets/database.php')) {
    fwrite(STDERR, "ABORTA: detectado el archivo de secretos de PRODUCCION.\n");
    exit(1);
}

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

$pdo  = Core\Database::connect();
$asis = new App\Models\AsistenciaModel();
$cond = new App\Models\ConductaModel();

$fallos = 0;

// Periodo del año activo en curso: es el único con el flag vivo.
$pid = (int) $pdo->query("
    SELECT p.id FROM periodos p
    INNER JOIN anios_academicos a ON a.id = p.anio_id
    WHERE a.estado = 'activo' AND p.estado = 'activo'
    ORDER BY p.numero LIMIT 1
")->fetchColumn();

if (!$pid) {
    fwrite(STDERR, "ABORTA: no hay ningun periodo 'activo' en el anio activo.\n");
    exit(1);
}

/** El flag tal como lo lee la vista, para el periodo bajo prueba. */
$flag = function (array $filas) use ($pid): ?bool {
    foreach ($filas as $f) {
        if ((int) $f['id'] === $pid) return (bool) $f['editable'];
    }
    return null;
};
$txt = fn(?bool $v): string => $v === null ? '(ausente)' : ($v ? 'editable' : 'NO editable');

echo "=== Periodo bajo prueba: id {$pid} ===\n";
echo "PHP (" . config('timezone') . "): " . date('Y-m-d H:i:s') . "\n";
echo "NOW() del motor:      " . $pdo->query("SELECT NOW()")->fetchColumn() . "\n\n";

$pdo->beginTransaction();
$original = $pdo->query("SELECT limite_notas FROM periodos WHERE id = {$pid}")->fetchColumn();
$fijar    = fn(?string $v) => $pdo->prepare("UPDATE periodos SET limite_notas = ? WHERE id = ?")
                                  ->execute([$v, $pid]);

// ── PASO 1: las cuatro fronteras, con el motor tal como esté configurado ──
echo "--- PASO 1: el flag sigue al guard real en las cuatro fronteras ---\n";
$fronteras = [
    'limite 2 h en el FUTURO' => date('Y-m-d H:i:s', time() + 7200),
    'limite 2 h en el PASADO' => date('Y-m-d H:i:s', time() - 7200),
    'limite dentro de 3 h'    => date('Y-m-d H:i:s', time() + 10800),
    'limite NULL'             => null,
];

foreach ($fronteras as $etiqueta => $valor) {
    $fijar($valor);
    $real = $cond->periodoEditable($pid);
    $fa   = $flag($asis->listarPeriodosActivos());
    $fc   = $flag($cond->listarPeriodosActivos());
    $ok   = ($fa === $real && $fc === $real);
    if (!$ok) $fallos++;

    printf("  %-26s real=%-12s asistencia=%-12s conducta=%-12s %s\n",
        $etiqueta, $txt($real), $txt($fa), $txt($fc), $ok ? 'OK' : '*** FALLA ***');
}

// ── PASO 2: la prueba dura — motor en UTC, como produccion ──
echo "\n--- PASO 2: con el motor en UTC (como prod), el NOW() viejo miente ---\n";
$pdo->exec("SET time_zone = '+00:00'");
printf("  NOW() del motor ahora: %s (PHP sigue en %s)\n",
    $pdo->query("SELECT NOW()")->fetchColumn(), date('Y-m-d H:i:s'));

// La franja del bug: el limite ya paso en hora del MOTOR pero no en la de Lima.
$limite = date('Y-m-d H:i:s', time() + 10800);
$fijar($limite);

$st = $pdo->prepare("
    SELECT (p.estado = 'activo'
            AND (p.limite_notas IS NULL OR NOW() <= p.limite_notas)) AS editable
    FROM periodos p WHERE p.id = ?");
$st->execute([$pid]);
$viejo = (bool) $st->fetchColumn();

$real = $cond->periodoEditable($pid);
$fa   = $flag($asis->listarPeriodosActivos());
$fc   = $flag($cond->listarPeriodosActivos());

printf("  limite_notas = %s (dentro de 3 h en hora de Lima)\n", $limite);
printf("  guard REAL (PHP)      : %s\n", $txt($real));
printf("  flag VIEJO con NOW()  : %s\n", $txt($viejo));
printf("  flag NUEVO asistencia : %s\n", $txt($fa));
printf("  flag NUEVO conducta   : %s\n", $txt($fc));

// El paso solo prueba algo si el motor quedo realmente desfasado.
if ($viejo === $real) {
    echo "  *** CONTROL: el NOW() viejo NO llego a diferir; el paso no prueba nada.\n";
    echo "      (motor sin desfase real, revisar SET time_zone)\n";
    $fallos++;
} elseif ($fa === $real && $fc === $real) {
    echo "  OK — el flag nuevo sigue al guard real donde el viejo se equivocaba.\n";
} else {
    echo "  *** FALLA: el flag nuevo tampoco coincide con el guard real.\n";
    $fallos++;
}

// ── PASO 3: el ROLLBACK devolvio el valor original ──
$pdo->rollBack();
$vuelta = $pdo->query("SELECT limite_notas FROM periodos WHERE id = {$pid}")->fetchColumn();
echo "\n--- PASO 3: rollback ---\n";
printf("  limite_notas = %s (original: %s) %s\n",
    var_export($vuelta, true), var_export($original, true),
    $vuelta === $original ? 'OK' : '*** NO RESTAURADO ***');
if ($vuelta !== $original) $fallos++;

echo "\n" . ($fallos === 0 ? "TODO OK\n" : "{$fallos} FALLO(S)\n");
exit($fallos === 0 ? 0 : 1);
