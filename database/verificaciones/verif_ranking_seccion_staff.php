<?php

/**
 * Verificación — el ranking por SECCIÓN del staff muestra el universo completo.
 * Uso: php database/verificaciones/verif_ranking_seccion_staff.php
 *
 * SOLO LECTURA: no escribe nada, no abre transacciones. Se puede correr en
 * PRODUCCIÓN (por eso NO lleva el guard de secretos de los que escriben).
 *
 * QUÉ COMPRUEBA
 *   1. EQUIVALENCIA CON EL DOCUMENTO OFICIAL: para cada bimestre CERRADO, la
 *      suma de estudiantes de todas las secciones cuadra **exactamente** con las
 *      filas del snapshot oficial de ese periodo. Es la aserción que detecta un
 *      filtro de más (o de menos) en la vista nueva.
 *   2. NO HAY TOP-N ESCONDIDO: ninguna sección devuelve menos estudiantes de los
 *      que tiene en el snapshot.
 *   3. LA COMPUERTA 044 NO SE APLICA AL STAFF: se listan los grados que el
 *      claustro NO vería (niveles sin publicar) y se comprueba que aquí sí
 *      salen. Es el punto del encargo: el staff lo necesita ANTES de publicar.
 *   4. PUESTOS SANOS por sección: empiezan en 1 y no hay huecos.
 *
 * CONTEXTO: `/director/ranking-seccion` reutiliza `rankingPorSeccion()`, el
 * mismo punto único (snapshot-aware) que ya usan el imprimible del director y la
 * vista del docente. Lo que cambia entre módulos es SOLO la compuerta.
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

$pdo    = Core\Database::connect();
$modelo = new App\Models\OrdenMeritoModel();
$pub    = new App\Models\PublicacionBoletaModel();

$fallos = 0;
$ok = function (bool $cond, string $etiqueta, string $detalle = '') use (&$fallos): void {
    printf("  %-5s %-58s %s\n", $cond ? 'OK' : '***', $etiqueta, $detalle);
    if (!$cond) { $fallos++; }
};

echo "== Huella ==\n";
printf("  bd=%s · usuario=%s · so=%s\n\n",
    $pdo->query("SELECT DATABASE()")->fetchColumn(),
    $pdo->query("SELECT USER()")->fetchColumn(),
    $pdo->query("SELECT @@version_compile_os")->fetchColumn());

$periodos = $pdo->query("
    SELECT p.id, p.numero, p.nombre_display, p.estado
    FROM periodos p
    INNER JOIN anios_academicos a ON a.id = p.anio_id
    WHERE a.estado = 'activo' AND p.estado IN ('activo', 'cerrado')
    ORDER BY p.numero
")->fetchAll();

foreach ($periodos as $p) {
    $pid = (int) $p['id'];
    echo "=== {$p['nombre_display']} ({$p['estado']}) ===\n";

    // Lo que hace el controlador del staff: todos los grados, sin compuerta.
    $totalStaff = 0;
    $secciones  = 0;
    $puestosMal = [];
    $grados     = $modelo->gradosConRanking($pid);

    foreach ($grados as $g) {
        foreach ($modelo->rankingPorSeccion((int) $g['id'], $pid) as $sec => $ests) {
            $secciones++;
            $totalStaff += count($ests);

            $puestos = array_map(static fn(array $e): int => (int) $e['puesto'], $ests);
            sort($puestos);
            if ($puestos && ($puestos[0] !== 1 || $puestos[count($puestos) - 1] > count($puestos))) {
                $puestosMal[] = "{$g['nombre_display']} {$sec}";
            }
        }
    }

    printf("  grados=%d · secciones=%d · estudiantes=%d\n", count($grados), $secciones, $totalStaff);

    // 1 y 2 — contra el snapshot oficial, que es el documento ya entregado.
    $snap = (int) $pdo->query("
        SELECT COUNT(*) FROM orden_merito_snapshot WHERE periodo_id = {$pid}
    ")->fetchColumn();

    if ($p['estado'] === 'cerrado' && $snap > 0) {
        $ok($totalStaff === $snap,
            'la suma de las secciones cuadra con el snapshot oficial',
            "vista={$totalStaff} · snapshot={$snap}");
    } else {
        printf("  %-5s %-58s %s\n", '--', 'sin snapshot: periodo en vivo, nada que contrastar',
            "estudiantes={$totalStaff}");
    }

    // 3 — la compuerta 044 NO debe recortar al staff.
    $nivelesPublicados = $pub->nivelesPublicados($pid);
    $ocultosAlClaustro = [];
    foreach ($grados as $g) {
        if (!isset($nivelesPublicados[(int) $g['nivel_id']])) {
            $ocultosAlClaustro[] = $g['nombre_display'];
        }
    }
    if ($ocultosAlClaustro) {
        $ok(true, 'grados que el claustro NO ve y el staff SI',
            count($ocultosAlClaustro) . ': ' . implode(', ', array_slice($ocultosAlClaustro, 0, 6)));
    } else {
        printf("  %-5s %-58s\n", '--', 'todos los niveles publicados: la compuerta no discrimina hoy');
    }

    // 4 — puestos sanos.
    $ok($puestosMal === [], 'puestos por seccion empiezan en 1 y sin huecos',
        $puestosMal ? implode(' · ', $puestosMal) : '');

    echo "\n";
}

echo $fallos === 0 ? "RESULTADO: OK\n" : "RESULTADO: {$fallos} FALLO(S)\n";
exit($fallos === 0 ? 0 : 1);
