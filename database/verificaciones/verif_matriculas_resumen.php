<?php

/**
 * Verificación — los KPIs de /matriculas/resumen (02/09/2026).
 * Uso: php database/verificaciones/verif_matriculas_resumen.php
 *
 * SOLO LECTURA: no escribe nada, no abre transacciones. Corre en PRODUCCIÓN.
 *
 * QUÉ CAMBIÓ Y POR QUÉ HAY QUE VIGILARLO
 *   Los KPIs contaban `estado='aprobada'`, o sea MATRÍCULAS OFICIALES. Ahora
 *   cuentan ESTUDIANTES DEL COLEGIO: todas las matrículas del año pasadas por
 *   `roster_evaluacion()`, que NO filtra por estado —`pendiente` es el estado en
 *   que nace toda matrícula y `desactivado` es una baja por deuda de alguien que
 *   sigue asistiendo— y excluye a quien ya no está (`trasladado`/`retirado`).
 *
 * LOS TRES RIESGOS QUE ESTO ANCLA
 *   1. `kpis` lo consume TAMBIÉN `/admin/cuadros`. Renombrar o quitar una clave
 *      histórica rompe ese tablero SIN error: la vista pinta un hueco.
 *   2. Doble conteo por RETORNO DE GRADO. Era un defecto real: `getResumen()`
 *      contaba las dos matrículas del retorno mientras `getCuadroMatricula()`
 *      —en la MISMA página— excluye la operativa, así que las dos mitades daban
 *      totales distintos del mismo año. Lo resuelve el helper; el aserto 4 lo
 *      comprueba contra la tabla `retornos_grado`, no contra el propio helper.
 *   3. El contraste se escribe A MANO aquí. Si la consulta de control saliera del
 *      mismo helper que el código medido, el aserto no probaría nada.
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
            if (is_file($file)) { require_once $file; }
        }
    }
});
require_once APP_PATH . '/Helpers/helpers.php';

$ok  = true;
$chk = function (string $t, bool $c, string $detalle = '') use (&$ok) {
    printf("  [%s] %s%s\n", $c ? 'OK ' : 'FAIL', $t, $detalle !== '' ? "  ->  $detalle" : '');
    $ok = $ok && $c;
};

$model = new App\Models\MatriculaModel();

$anio = $model->queryOne("
    SELECT id, anio FROM anios_academicos
    WHERE estado IN ('activo','planificado') ORDER BY anio DESC LIMIT 1
");
if (!$anio) {
    fwrite(STDERR, "ABORTA: no hay año académico activo ni planificado.\n");
    exit(1);
}
$anioId = (int) $anio['id'];
echo "Año medido: {$anio['anio']} (id {$anioId})\n";

$kpis = $model->getResumen($anioId)['kpis'];

// ---- 1. Las claves históricas siguen ahí (contrato con /admin/cuadros) ----
echo "\n=== 1. Contrato con /admin/cuadros ===\n";
foreach (['aprobadas', 'pendientes', 'desactivadas', 'secciones', 'promedio_seccion'] as $k) {
    $chk("kpis['$k'] sigue existiendo", array_key_exists($k, $kpis));
}
$vista = file_get_contents(ROOT_PATH . '/resources/views/admin/cuadros/index.php');
preg_match_all("/\\\$k\['(\w+)'\]/", $vista, $mm);
$usadas = array_unique($mm[1] ?? []);
$faltan = array_diff($usadas, array_keys($kpis));
$chk('todas las claves que pinta /admin/cuadros existen en kpis',
    $faltan === [], $faltan ? 'FALTAN: ' . implode(', ', $faltan) : implode(', ', $usadas));

// ---- 2. El universo: contraste contra un COUNT escrito a mano -------------
echo "\n=== 2. El universo, contra una consulta de control ===\n";

// A MANO, sin usar roster_evaluacion(): si saliera del helper no probaría nada.
$control = $model->queryOne("
    SELECT
        COUNT(*)                      AS total,
        SUM(m.estado = 'aprobada')    AS aprobadas,
        SUM(m.estado = 'pendiente')   AS pendientes,
        SUM(m.estado = 'desactivado') AS desactivadas,
        COUNT(DISTINCT m.seccion_id)  AS secciones
    FROM matriculas m
    WHERE m.anio_id = ?
      AND m.tipo NOT IN ('trasladado', 'retirado')
      AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
      AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
", [$anioId]);

$chk('estudiantes = el universo completo del roster',
    $kpis['estudiantes'] === (int) $control['total'],
    $kpis['estudiantes'] . ' vs ' . (int) $control['total']);
foreach (['aprobadas', 'pendientes', 'desactivadas', 'secciones'] as $k) {
    $chk("$k cuadra con la consulta de control",
        $kpis[$k] === (int) $control[$k], $kpis[$k] . ' vs ' . (int) $control[$k]);
}
$chk('estudiantes = aprobadas + pendientes + desactivadas (el enum tiene 3 valores)',
    $kpis['estudiantes'] === $kpis['aprobadas'] + $kpis['pendientes'] + $kpis['desactivadas'],
    $kpis['aprobadas'] . ' + ' . $kpis['pendientes'] . ' + ' . $kpis['desactivadas']);

// ---- 3. El promedio es ENTERO y es el correcto ---------------------------
echo "\n=== 3. Promedio por sección ===\n";
$chk('promedio_seccion es un entero, no un decimal',
    is_int($kpis['promedio_seccion']), var_export($kpis['promedio_seccion'], true));
$esperado = $kpis['secciones'] > 0 ? (int) round($kpis['estudiantes'] / $kpis['secciones']) : 0;
$chk('promedio_seccion = estudiantes / secciones, redondeado',
    $kpis['promedio_seccion'] === $esperado, "{$kpis['promedio_seccion']} (esperado {$esperado})");
$chk('el divisor son secciones CON estudiantes (nunca 0 con estudiantes > 0)',
    $kpis['estudiantes'] === 0 || $kpis['secciones'] > 0);

// ---- 4. Nadie se cuenta DOS VECES por retorno de grado -------------------
echo "\n=== 4. Retorno de grado: un estudiante, una vez ===\n";
$retornos = $model->queryOne("
    SELECT COUNT(*) AS n FROM retornos_grado rg
    INNER JOIN matriculas m ON m.id = rg.matricula_oficial_id
    WHERE m.anio_id = ?
", [$anioId]);
$nRetornos = (int) ($retornos['n'] ?? 0);

$distintos = $model->queryOne("
    SELECT COUNT(DISTINCT m.estudiante_id) AS n
    FROM matriculas m
    WHERE m.anio_id = ?
      AND m.tipo NOT IN ('trasladado', 'retirado')
      AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
      AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
", [$anioId]);

// El aserto que muerde: contar FILAS y contar ESTUDIANTES tiene que dar lo mismo.
// Si alguien quita el helper, un retorno activo suma 2 y esto se pone en rojo.
$chk('estudiantes distintos == filas contadas (sin duplicar por retorno)',
    $kpis['estudiantes'] === (int) $distintos['n'],
    $kpis['estudiantes'] . ' filas vs ' . (int) $distintos['n'] . " distintos · {$nRetornos} retorno(s) en el año");

// ---- 5. Los excluidos se publican ---------------------------------------
echo "\n=== 5. Los que quedan fuera del conteo ===\n";
$fuera = $model->queryOne("
    SELECT SUM(tipo = 'trasladado') AS t, SUM(tipo = 'retirado') AS r
    FROM matriculas WHERE anio_id = ?
", [$anioId]);
$chk('trasladados y retirados se publican para poder explicarlos en pantalla',
    $kpis['trasladados'] === (int) ($fuera['t'] ?? 0)
    && $kpis['retirados'] === (int) ($fuera['r'] ?? 0),
    "{$kpis['trasladados']} trasladados · {$kpis['retirados']} retirados");

// ---- 6. Los gráficos NO cambiaron de universo ---------------------------
// Siguen con estado='aprobada' a proposito: describen la foto oficial de la
// matricula, que es otra pregunta distinta de "cuantos estudiantes hay".
echo "\n=== 6. Los gráficos conservan su universo ===\n";
$src = file_get_contents(ROOT_PATH . '/app/Models/MatriculaModel.php');
preg_match('/public function getResumen.*?\n    \}/s', $src, $mg);
$chk("getResumen sigue teniendo consultas con estado='aprobada' (los gráficos)",
    substr_count($mg[0] ?? '', "m.estado = 'aprobada'") >= 3,
    substr_count($mg[0] ?? '', "m.estado = 'aprobada'") . ' consultas');
$chk('los KPIs ya NO filtran por estado (usan roster_evaluacion)',
    str_contains($mg[0] ?? '', 'roster_evaluacion('));

echo "\nRESULTADO: " . ($ok ? 'OK - los KPIs cuentan estudiantes del colegio, sin duplicar.' : 'HAY FALLOS') . "\n";
exit($ok ? 0 : 1);
