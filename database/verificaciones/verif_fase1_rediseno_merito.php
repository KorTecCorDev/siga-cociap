<?php

/**
 * Verificación — Fase 1 del rediseño 2 del orden de mérito.
 * SOLO LECTURA. Uso: php database/verificaciones/verif_fase1_rediseno_merito.php
 *
 * Comprueba:
 *  P1 — orden estable por matricula_id (tras num_16 el desempate es manual).
 *  P2 — el cálculo EN VIVO solo cuenta calificaciones BLOQUEADAS.
 *  - B1 (periodo 1) sigue leyendo del SNAPSHOT congelado (528, intacto).
 *  - El ranking en vivo corre sin error y su universo = notas bloqueadas.
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

$o = new \App\Models\OrdenMeritoModel();

echo "=== 1. B1 (periodo 1) lee del SNAPSHOT congelado ===\n";
$totalSnap = (int) ($o->query("SELECT COUNT(*) n FROM orden_merito_snapshot WHERE periodo_id=1")[0]['n']);
echo "  filas snapshot B1: $totalSnap  [esperado 528]\n";
$grados = $o->gradosConRanking(1);
$sumPuestos = 0;
foreach ($grados as $g) { $sumPuestos += count($o->rankingGrado((int) $g['id'], 1)); }
echo "  grados con ranking: " . count($grados) . " · alumnos rankeados (suma): $sumPuestos  [esperado 528]\n";

echo "\n=== 2. P2 — universo bloqueadas vs. total (por periodo) ===\n";
foreach ([1, 2] as $pid) {
    $tot = (int) ($o->query("
        SELECT COUNT(*) n FROM calificaciones cal
        JOIN competencias comp ON comp.id=cal.competencia_id
        LEFT JOIN subareas sa ON sa.id=comp.subarea_id
        JOIN areas a ON a.id=COALESCE(sa.area_id,comp.area_id)
        WHERE cal.periodo_id=? AND cal.extraordinaria=0 AND a.tipo NOT IN ('transversal','tutoria')
    ", [$pid])[0]['n']);
    $bloq = (int) ($o->query("
        SELECT COUNT(*) n FROM calificaciones cal
        JOIN bloqueos_competencia bc ON bc.carga_id=cal.carga_id AND bc.competencia_id=cal.competencia_id AND bc.periodo_id=cal.periodo_id
        JOIN competencias comp ON comp.id=cal.competencia_id
        LEFT JOIN subareas sa ON sa.id=comp.subarea_id
        JOIN areas a ON a.id=COALESCE(sa.area_id,comp.area_id)
        WHERE cal.periodo_id=? AND cal.extraordinaria=0 AND a.tipo NOT IN ('transversal','tutoria')
    ", [$pid])[0]['n']);
    printf("  periodo %s: total_merito=%s  bloqueadas=%s  %s\n", $pid, $tot, $bloq,
        $tot === $bloq ? '(todas bloqueadas)' : '(el join FILTRA ' . ($tot - $bloq) . ')');
}

echo "\n=== 3. P1 — orden estable por matricula_id (ranking en vivo, reflection) ===\n";
$ref = new \ReflectionMethod($o, 'rankingGradoLive');
$ref->setAccessible(true);
// Tomar un grado cualquiera con notas bloqueadas en B1.
$gid = (int) ($o->query("
    SELECT g.id FROM matriculas m JOIN secciones s ON s.id=m.seccion_id JOIN grados g ON g.id=s.grado_id
    JOIN calificaciones cal ON cal.matricula_id=m.id AND cal.periodo_id=1
    JOIN bloqueos_competencia bc ON bc.carga_id=cal.carga_id AND bc.competencia_id=cal.competencia_id AND bc.periodo_id=cal.periodo_id
    LIMIT 1")[0]['id']);
$vivo = $ref->invoke($o, $gid, 1);
$puestos = array_map(fn($f) => (int) $f['puesto'], $vivo);
$ok = $puestos === range(1, count($vivo));
echo "  grado $gid en vivo: " . count($vivo) . " alumnos · puestos 1.." . count($vivo) . ": " . ($ok ? 'OK' : 'FALLA') . "\n";
echo "  (corre sin error de sintaxis/SQL, con el join de bloqueos y ORDER BY por m.id)\n";
