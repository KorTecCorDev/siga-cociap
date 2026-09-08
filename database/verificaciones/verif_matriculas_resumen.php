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

// A MANO, sin usar los helpers: si saliera de ellos no probaría nada.
//
// 🔴 EL ANCLA ES LA MATRÍCULA OFICIAL (02/09/2026): fuera la OPERATIVA de todo
// retorno, sin condición de estado (criterio DOCUMENTO). Antes esta consulta
// llevaba el criterio de EVALUACIÓN, que es el INVERSO —conservaba la operativa
// y ubicaba al estudiante en el grado inferior—, contradiciendo al cuadro del
// pie de la misma pantalla.
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
      AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado)
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
      AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado)
", [$anioId]);

// El aserto que muerde: contar FILAS y contar ESTUDIANTES tiene que dar lo mismo.
// Si alguien quita el ancla, un retorno suma 2 y esto se pone en rojo.
$chk('estudiantes distintos == filas contadas (sin duplicar por retorno)',
    $kpis['estudiantes'] === (int) $distintos['n'],
    $kpis['estudiantes'] . ' filas vs ' . (int) $distintos['n'] . " distintos · {$nRetornos} retorno(s) en el año");

// 🔴 Y EL QUE MUERDE MÁS: los chips ubican al estudiante en su sección OFICIAL.
// Con el ancla contraria (la de evaluación) salía su sección OPERATIVA, o sea el
// grado inferior, y la misma pantalla lo ponía en dos secciones distintas: los
// chips en una y el cuadro del pie en otra.
$anclaMal = $model->query("
    SELECT rg.id, rg.estado,
           mo.id AS of_id, mo.seccion_id AS of_sec,
           mp.id AS op_id, mp.seccion_id AS op_sec
    FROM retornos_grado rg
    INNER JOIN matriculas mo ON mo.id = rg.matricula_oficial_id
    INNER JOIN matriculas mp ON mp.id = rg.matricula_operativa_id
    WHERE mo.anio_id = ?
      -- Rojo si la que entra al universo de los chips es la OPERATIVA...
      AND (mp.id IN (
              SELECT m.id FROM matriculas m
              WHERE m.anio_id = ?
                AND m.tipo NOT IN ('trasladado', 'retirado')
                AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado)
          )
      -- ...o si la OFICIAL se queda fuera teniendo tipo vigente.
      OR (mo.tipo NOT IN ('trasladado', 'retirado') AND mo.id NOT IN (
              SELECT m.id FROM matriculas m
              WHERE m.anio_id = ?
                AND m.tipo NOT IN ('trasladado', 'retirado')
                AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado)
          )))
", [$anioId, $anioId, $anioId]);
$chk('cada retorno entra por su matrícula OFICIAL, nunca por la operativa',
    $anclaMal === [],
    $anclaMal === []
        ? "{$nRetornos} retorno(s) anclados en su sección oficial"
        : 'mal anclados: ' . implode(', ', array_column($anclaMal, 'id')));

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

// ---- 6. Los gráficos: universo propio, MISMA ancla de retorno -----------
// Siguen con estado='aprobada' a proposito: describen la foto oficial de la
// matricula, que es otra pregunta distinta de "cuantos estudiantes hay". Lo que
// desde el 02/09/2026 comparten con los chips y con el cuadro es el ANCLA del
// retorno (criterio DOCUMENTO), no el universo.
echo "\n=== 6. Los gráficos: universo propio, misma ancla ===\n";
$src = file_get_contents(ROOT_PATH . '/app/Models/MatriculaModel.php');
preg_match('/public function getResumen.*?\n    \}/s', $src, $mg);
$chk("getResumen sigue teniendo consultas con estado='aprobada' (los gráficos)",
    substr_count($mg[0] ?? '', "m.estado = 'aprobada'") >= 3,
    substr_count($mg[0] ?? '', "m.estado = 'aprobada'") . ' consultas');
$chk('el ancla del retorno sale del punto único, no escrita a mano',
    str_contains($mg[0] ?? '', 'matricula_documento(')
    && !str_contains($mg[0] ?? '', 'retornos_grado'));
$chk('los KPIs siguen sin filtrar por estado (universo = estudiantes del colegio)',
    str_contains($mg[0] ?? '', 'matriculas_vigentes('));

// 🔴 EL ASERTO QUE MUERDE: las cuatro series tienen que sumar lo MISMO, y ese
// mismo tiene que ser el chip `aprobadas`. Hasta el 02/09/2026 los gráficos no
// aplicaban NINGUNA exclusión de retorno y sumaban uno de más (521 contra 520):
// el estudiante en retorno aparecía dos veces, y en dos grados distintos.
$res    = $model->getResumen($anioId);
$sumas  = [
    'por_grado'   => array_sum(array_column($res['por_grado'], 'n')),
    'por_seccion' => array_sum(array_column($res['por_seccion'], 'n')),
    'por_tipo'    => array_sum(array_column($res['por_tipo'], 'n')),
    'por_genero'  => (int) $res['por_genero']['m'] + (int) $res['por_genero']['f']
                     + (int) $res['por_genero']['sin_dato'],
];
foreach ($sumas as $serie => $n) {
    $chk("$serie suma lo mismo que el chip `aprobadas`",
        $n === $kpis['aprobadas'], "{$n} vs {$kpis['aprobadas']}");
}
// El desglose de sexo del gráfico apilado tiene que cerrar sección a sección: si
// no, las barras apiladas no llegarían a la altura de la barra de al lado.
$malSeccion = [];
foreach ($res['por_seccion'] as $s) {
    if ($s['m'] + $s['f'] + $s['sin_dato'] !== $s['n']) {
        $malSeccion[] = $s['grado_numero'] . $s['seccion_nombre'];
    }
}
$chk('en cada sección M + F + sin dato = el total de la barra',
    $malSeccion === [],
    $malSeccion === [] ? count($res['por_seccion']) . ' secciones' : implode(' · ', $malSeccion));

// Y el contraste contra una consulta de control escrita A MANO, sección a
// sección: es lo que detecta que una sola de las cuatro consultas pierda el ancla.
$ctrlSeccion = $model->query("
    SELECT s.id AS seccion_id, COUNT(*) AS n
    FROM matriculas m
    INNER JOIN secciones s ON s.id = m.seccion_id
    WHERE m.anio_id = ? AND m.estado = 'aprobada'
      AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado)
    GROUP BY s.id
", [$anioId]);
$chk('el gráfico por sección cuadra con la consulta de control, sección a sección',
    count($ctrlSeccion) === count($res['por_seccion'])
    && array_sum(array_column($ctrlSeccion, 'n')) === $sumas['por_seccion'],
    count($res['por_seccion']) . ' secciones · ' . $sumas['por_seccion'] . ' matrículas');

// ═════════════════════════════════════════════════════════════════════════
// EL CUADRO DE MATRÍCULA POR GRADO (la tabla final de la pantalla).
// Hasta el 02/09/2026 no tenía NI UN SOLO aserto: este verificador cubría los
// KPIs de arriba y no la tabla de abajo, que es justo la que se imprime y se
// lleva al comité directivo.
// ═════════════════════════════════════════════════════════════════════════

$cuadro = $model->getCuadroMatricula($anioId);

echo "\n=== 7. El cuadro CUADRA: las columnas suman su total ===\n";
$chk('el cuadro devuelve filas', $cuadro !== [], count($cuadro) . ' grados');

// 🔴 EL ASERTO QUE FALTABA. Las columnas de tipo sumaban `nuevo + continuador +
// trasladado` y el enum tiene CUATRO valores desde la migración 045: la fila de
// Primaria 3.º daba 48 sobre un total de 49. Se comprueba la PROPIEDAD —que las
// columnas de un grupo sumen su total— y no una lista de tipos, para que un
// quinto valor del enum también se ponga en rojo aquí.
$malTipo = $malEstado = [];
foreach ($cuadro as $r) {
    $etq = $r['nivel_nombre'] . ' ' . $r['grado_nombre'];
    if ($r['t_nuevo'] + $r['t_cont'] + $r['t_tras'] + $r['t_retir'] !== $r['total']) {
        $malTipo[] = $etq . ' (' . ($r['t_nuevo'] + $r['t_cont'] + $r['t_tras'] + $r['t_retir'])
                   . ' vs ' . $r['total'] . ')';
    }
    if ($r['e_aprob'] + $r['e_pend'] + $r['e_desact'] !== $r['total']) {
        $malEstado[] = $etq;
    }
}
$chk('las CUATRO columnas de tipo suman el total, fila a fila',
    $malTipo === [], $malTipo === [] ? count($cuadro) . ' filas cuadradas' : implode(' · ', $malTipo));
$chk('las TRES columnas de estado suman el total, fila a fila',
    $malEstado === [], $malEstado === [] ? count($cuadro) . ' filas cuadradas' : implode(' · ', $malEstado));

// El gran total es lo que el comité lee primero.
$g = ['t' => 0, 'e' => 0, 'tot' => 0];
foreach ($cuadro as $r) {
    $g['t']   += $r['t_nuevo'] + $r['t_cont'] + $r['t_tras'] + $r['t_retir'];
    $g['e']   += $r['e_aprob'] + $r['e_pend'] + $r['e_desact'];
    $g['tot'] += $r['total'];
}
$chk('el TOTAL GENERAL cuadra por tipo y por estado',
    $g['t'] === $g['tot'] && $g['e'] === $g['tot'],
    "tipo {$g['t']} · estado {$g['e']} · total {$g['tot']}");

// La columna nueva tiene que contener de verdad a los retirados del año.
$retDb = $model->queryOne("
    SELECT COUNT(*) n FROM matriculas m
    WHERE m.anio_id = ? AND m.tipo = 'retirado'
      AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado)
", [$anioId]);
$chk('la columna Retirados trae los retirados reales del año',
    array_sum(array_column($cuadro, 't_retir')) === (int) $retDb['n'],
    array_sum(array_column($cuadro, 't_retir')) . ' vs ' . (int) $retDb['n'] . ' en la base');

echo "\n=== 8. Retorno de grado: criterio DOCUMENTO, y nadie cuenta dos veces ===\n";

// El cuadro es un DOCUMENTO: excluye TODA operativa, sin condición de estado.
// Desde el 02/09/2026 la línea NO se escribe aquí ni en boletas: sale de
// `matricula_documento()`, el punto único. Quien vigila el texto emitido y que
// nadie vuelva a copiarlo es `verif_matricula_documento.php`; aquí solo se
// comprueba que este cuadro es uno de sus consumidores.
$srcCuadro = '';
if (preg_match('/public function getCuadroMatricula.*?\n    \}/s', $src, $mc)) { $srcCuadro = $mc[0]; }
$chk('el cuadro excluye la operativa usando el punto único (criterio documento)',
    str_contains($srcCuadro, "matricula_documento('m')"));
// 🔴 El aserto que muerde de verdad: que NO haya vuelto el `WHERE estado`, que es
// del criterio de EVALUACIÓN. Mezclarlos es el error más fácil de este módulo.
$chk('NO se le ha vuelto a pegar un `WHERE estado` al subselect del retorno',
    preg_match('/matricula_operativa_id FROM retornos_grado\s+WHERE/i', $srcCuadro) !== 1
    && stripos(matricula_documento(), 'WHERE estado') === false);
$srcBoleta = file_get_contents(ROOT_PATH . '/app/Models/BoletaPublicaModel.php');
$chk('comparte el punto único con BoletaPublicaModel (que es el criterio canónico)',
    str_contains($srcBoleta, 'matricula_documento('));

$filas  = array_sum(array_column($cuadro, 'total'));
$distDb = $model->queryOne("
    SELECT COUNT(DISTINCT m.estudiante_id) n FROM matriculas m
    INNER JOIN estudiantes e ON e.id = m.estudiante_id
    INNER JOIN personas p    ON p.id = e.persona_id
    INNER JOIN secciones s   ON s.id = m.seccion_id
    INNER JOIN grados g      ON g.id = s.grado_id
    INNER JOIN niveles n     ON n.id = g.nivel_id
    WHERE m.anio_id = ?
      AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado)
", [$anioId]);
$chk('cada estudiante aparece UNA vez en el cuadro',
    $filas === (int) $distDb['n'], $filas . ' filas vs ' . (int) $distDb['n'] . ' estudiantes');

// 🔴 LA RAMA QUE NO EXISTE EN LA BASE. Todos los retornos reales están `activo`,
// así que el caso `revertido` —el que producía el doble conteo— no se probaría
// nunca. Se simula DENTRO DE UNA TRANSACCIÓN CON ROLLBACK: CLAUDE.md prohíbe que
// un script de `database/` limpie con DELETE lo que no creó, y ya pasó una vez
// que una verificación borró el oficial de B1.
$retorno = $model->queryOne("
    SELECT rg.id FROM retornos_grado rg
    INNER JOIN matriculas m ON m.id = rg.matricula_oficial_id
    WHERE m.anio_id = ? AND rg.estado = 'activo' LIMIT 1
", [$anioId]);

if ($retorno === null) {
    echo "  [--] sin retornos activos en el año: la rama `revertido` no se pudo simular\n";
} else {
    $pdo = Core\Database::get();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("UPDATE retornos_grado SET estado = 'revertido' WHERE id = ?");
        $st->execute([$retorno['id']]);

        $simulado = $model->getCuadroMatricula($anioId);
        $filasSim = array_sum(array_column($simulado, 'total'));

        // Y el control: lo que HABRÍA contado el filtro viejo, escrito a mano.
        $viejo = $model->queryOne("
            SELECT COUNT(*) n FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            INNER JOIN secciones s   ON s.id = m.seccion_id
            INNER JOIN grados g      ON g.id = s.grado_id
            INNER JOIN niveles n     ON n.id = g.nivel_id
            WHERE m.anio_id = ?
              AND m.id NOT IN (
                  SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'activo'
              )
        ", [$anioId]);

        $chk('con el retorno REVERTIDO el cuadro sigue contando lo mismo',
            $filasSim === $filas, "{$filasSim} vs {$filas} con el retorno activo");
        // Prueba de que el aserto no es vacuo: el filtro viejo SÍ se descuadraba.
        $chk('y el filtro VIEJO sí duplicaba (prueba de que el aserto no es vacuo)',
            (int) $viejo['n'] === $filas + 1,
            'el viejo habría contado ' . (int) $viejo['n'] . ' en vez de ' . $filas);
    } finally {
        $pdo->rollBack();
    }
    $tras = $model->queryOne("SELECT estado FROM retornos_grado WHERE id = ?", [$retorno['id']]);
    $chk('el ROLLBACK dejó el retorno como estaba',
        ($tras['estado'] ?? '') === 'activo', 'estado = ' . ($tras['estado'] ?? '?'));
}

echo "\n=== 9. La tabla renderizada tiene columnas consistentes ===\n";
// El parcial deriva TODAS las celdas de una sola lista, así que ya no se puede
// olvidar una. Este aserto congela esa propiedad: si alguien vuelve a escribir
// las celdas a mano y se deja una, las filas dejan de tener el mismo ancho.
if (!defined('VIEW_PATH')) { define('VIEW_PATH', ROOT_PATH . '/resources/views'); }
ob_start();
require VIEW_PATH . '/matriculas/_cuadro-matricula.php';
$html = ob_get_clean();

preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $html, $mt);
// ⚠️ Solo las filas de DATOS (`<td>`), y sin la banda de nivel, que es una sola
// celda con colspan. Mezclar aquí las dos filas de la cabecera da 5/9/11 —que es
// la estructura CORRECTA de un `<thead>` con `rowspan`— y el aserto acusa a la
// tabla de un descuadre que no existe. Pasó al escribirlo.
$anchos = [];
foreach ($mt[1] as $fila) {
    if (!str_contains($fila, '<td')) { continue; }        // descarta la cabecera
    $n = substr_count($fila, '<td');
    if ($n > 1) { $anchos[] = $n; }                       // descarta la banda de nivel
}
$esperado = 1 + 4 + 3 + 2 + 1;   // grado + tipos + estados + genero + total
$chk('todas las filas de datos tienen el mismo número de celdas',
    count(array_unique($anchos)) === 1 && ($anchos[0] ?? 0) === $esperado,
    implode('/', array_unique($anchos)) . " celdas (esperado {$esperado})");
$chk('hay fila por grado, subtotal por nivel y total general',
    count($anchos) === count($cuadro) + 3, count($anchos) . ' filas de datos');
$chk('la cabecera declara los cuatro tipos',
    str_contains($html, '<th>Retir.</th>') && str_contains($html, 'colspan="4">Tipo'));
$chk('la banda de nivel abarca toda la tabla',
    str_contains($html, 'colspan="' . $esperado . '"'), 'colspan ' . $esperado);
$chk('la nota al pie da la CIFRA de sin-sexo, no solo la advertencia',
    str_contains($html, 'sin sexo registrado')
    && preg_match('/<strong>\d+ estudiantes?<\/strong>/', $html) === 1);

echo "\nRESULTADO: " . ($ok ? 'OK - los KPIs y el cuadro cuadran, y nadie se cuenta dos veces.' : 'HAY FALLOS') . "\n";
exit($ok ? 0 : 1);
