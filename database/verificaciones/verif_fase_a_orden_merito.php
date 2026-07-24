<?php

/**
 * Verificación — Fase A del rediseño del orden de mérito (filtro por tipo).
 * SOLO LECTURA. Uso: php database/verificaciones/verif_fase_a_orden_merito.php
 *
 * Comprueba que el ranking (OrdenMeritoModel) filtra por
 * `tipo NOT IN ('trasladado','retirado')` y NO por `estado='aprobada'`:
 *  - matrículas `pendiente` ENTRAN al ranking,
 *  - `trasladado` / `retirado` quedan FUERA,
 *  - el retorno de grado sigue anclado (operativa compite, oficial excluida),
 *  - B1 (periodo 1) no tiene empates pendientes con el roster nuevo.
 *
 * Asume el dataset de referencia (mismos IDs que producción).
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

$m = new \App\Models\AnioAcademicoModel();
$o = new \App\Models\OrdenMeritoModel();

// Casos de control: matrícula esperada dentro/fuera del ranking de B1.
$casos = [
    ['mat' => 220, 'esperado' => 'DENTRO (pendiente)'],
    ['mat' => 666, 'esperado' => 'DENTRO (pendiente)'],
    ['mat' => 541, 'esperado' => 'FUERA (retirado)'],
    ['mat' => 308, 'esperado' => 'FUERA (trasladado)'],
    ['mat' => 692, 'esperado' => 'DENTRO (retorno operativa)'],
    ['mat' => 190, 'esperado' => 'FUERA (retorno oficial, anclaje)'],
];

echo "=== Fase A — filtro por tipo (PERIODO 1 / B1) ===\n";
foreach ($casos as $c) {
    $g = $m->query("
        SELECT g.id FROM matriculas mm
        JOIN secciones s ON s.id = mm.seccion_id
        JOIN grados g    ON g.id = s.grado_id
        WHERE mm.id = ?", [$c['mat']]);
    $gradoId = (int) ($g[0]['id'] ?? 0);

    $enRanking = false; $puesto = null;
    foreach ($o->rankingGrado($gradoId, 1) as $f) {
        if ((int) $f['matricula_id'] === $c['mat']) { $enRanking = true; $puesto = $f['puesto']; }
    }
    printf("  mat=%-4s grado=%-3s => %-10s (puesto %s) | esperado: %s\n",
        $c['mat'], $gradoId, $enRanking ? 'EN RANKING' : 'excluida', $puesto ?? '-', $c['esperado']);
}

echo "\n=== Empates pendientes de B1 con el roster nuevo ===\n";
$emp = $o->gradosConEmpatesPendientes(1);
echo empty($emp) ? "  SIN empates pendientes (OK)\n"
                  : ("  PENDIENTES: " . implode(' | ', array_unique($emp)) . "\n");
