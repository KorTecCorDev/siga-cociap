<?php

/**
 * Verificación — qué áreas aportan al promedio del ORDEN DE MÉRITO, por grado.
 * Uso: php database/verificaciones/verif_universo_merito.php
 *
 * SOLO LECTURA: no escribe nada. Se puede correr en PRODUCCIÓN.
 *
 * PARA QUÉ SIRVE. El universo del mérito no está declarado en ninguna tabla: se
 * DERIVA de las notas existentes (`calificaciones`) filtradas por tipo de área. Es
 * decir, un área entra al promedio en cuanto alguien le crea una carga y registra
 * notas. Este script hace visible ese universo real y —lo importante— falla si
 * aparece una nota de un área que NO debe contar en un grado.
 *
 * REGLA DEL COLEGIO (usuario, 04/08/2026): en 5° de SECUNDARIA jamás deben entrar
 * Arte y Cultura, Educación para el Trabajo, Ética y Valores, Educación Religiosa
 * ni las Competencias Transversales. Al escribirse este script, las cinco ya se
 * cumplían: las tres primeras y Ed. Religiosa no tienen NI UNA nota en 5°, y Ética
 * y las transversales quedan fuera por el filtro de `tipo`. El script existe para
 * que se sepa el día que eso cambie, en vez de descubrirlo en el acta.
 *
 * NOTA sobre por qué esto NO está codificado como excepción en el SQL del mérito:
 * el plan de estudios se deriva de las cargas académicas, así que hardcodear
 * "grado 5 no lleva Arte" en la query duplicaría esa fuente de verdad y quedaría
 * desincronizado el día que el plan cambie. La red de seguridad es esta
 * verificación, no una excepción en el motor.
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

$pdo    = Core\Database::connect();
$fallos = 0;

/**
 * Áreas PROHIBIDAS por (nivel, grado). Se comparan por `nombre_boleta` y por `nombre`.
 *
 * ÉTICA Y VALORES SALIÓ DE ESTA LISTA EL 05/08/2026: por decisión del usuario cuenta
 * en el mérito en TODA secundaria, 5.º incluido. La regla del 04/08 que la prohibía en
 * 5.º listaba «Ética y Valores» y «Educación Religiosa» como áreas distintas, siendo la
 * misma (en secundaria Ed. Religiosa es un cascarón sin cargas y su nota la produce la
 * carga de Ética). Ver `docs/modulos/orden-merito.md`.
 *
 * «Educación Religiosa» SE QUEDA, y cambia de significado: ya no es un veto curricular
 * sino un GUARD ANTI-DUPLICADO. Si esa área llegara a tener cargas, el mismo curso
 * contaría DOS VECES (por Ética y por ella). El chequeo dedicado de más abajo lo
 * extiende a los 5 grados.
 */
$prohibidas = [
    'Secundaria' => [
        5 => ['Arte y Cultura', 'Educación para el Trabajo',
              'Educación Religiosa', 'Competencias Transversales', 'Comp. Transv.'],
    ],
];

// Universo REAL: replica el filtro de OrdenMeritoModel::rankingGradoLive, INCLUIDA la
// excepción de Ética. Si aquí no se replica, el script informa «correctamente fuera»
// de un área que en realidad ya aporta — miente en vez de fallar.
$sql = "
    SELECT n.nombre AS nivel, g.numero AS grado, a.tipo, a.nombre, a.nombre_boleta,
           COUNT(*) AS notas, COUNT(DISTINCT cal.matricula_id) AS alumnos,
           (a.tipo NOT IN ('transversal','tutoria')
            OR a.nombre_boleta = '" . AREA_ETICA_NOMBRE_BOLETA . "') AS aporta
    FROM calificaciones cal
    INNER JOIN matriculas m       ON m.id = cal.matricula_id
    INNER JOIN secciones s        ON s.id = m.seccion_id
    INNER JOIN grados g           ON g.id = s.grado_id
    INNER JOIN niveles n          ON n.id = g.nivel_id
    INNER JOIN bloqueos_competencia bc
            ON bc.carga_id       = cal.carga_id
           AND bc.competencia_id = cal.competencia_id
           AND bc.periodo_id     = cal.periodo_id
    INNER JOIN competencias comp  ON comp.id = cal.competencia_id
    LEFT  JOIN subareas sa        ON sa.id   = comp.subarea_id
    INNER JOIN areas a            ON a.id    = COALESCE(sa.area_id, comp.area_id)
    WHERE cal.periodo_id  = ?
      AND cal.extraordinaria = 0
      AND m.tipo NOT IN ('trasladado', 'retirado')
    GROUP BY n.id, g.numero, a.id
    ORDER BY n.id, g.numero, aporta DESC, a.nombre_boleta
";

$periodos = $pdo->query("
    SELECT id, numero, nombre_display FROM periodos
    WHERE anio_id = (SELECT id FROM anios_academicos WHERE estado='activo' LIMIT 1)
      AND estado <> 'pendiente'
    ORDER BY numero
")->fetchAll();

foreach ($periodos as $per) {
    $st = $pdo->prepare($sql);
    $st->execute([(int) $per['id']]);
    $filas = $st->fetchAll();

    echo "\n=== {$per['nombre_display']} — áreas con notas bloqueadas ===\n";
    $gradoActual = null;
    $suma = 0;

    foreach ($filas as $f) {
        $clave = $f['nivel'] . ' ' . $f['grado'] . '°';
        if ($clave !== $gradoActual) {
            if ($gradoActual !== null) { printf("      %s aporta %d nota(s) al promedio\n", $gradoActual, $suma); }
            $gradoActual = $clave;
            $suma = 0;
            echo "\n  {$clave}\n";
        }
        if ((int) $f['aporta'] === 1) { $suma += (int) $f['notas']; }

        $lista = $prohibidas[$f['nivel']][(int) $f['grado']] ?? [];
        $esProhibida = in_array($f['nombre_boleta'], $lista, true)
                    || in_array($f['nombre'], $lista, true);

        $marca = (int) $f['aporta'] === 1 ? 'APORTA  ' : 'excluida';
        $alerta = '';
        if ($esProhibida && (int) $f['aporta'] === 1) {
            $fallos++;
            $alerta = '   *** PROHIBIDA EN ESTE GRADO Y ESTÁ APORTANDO ***';
        } elseif ($esProhibida) {
            $alerta = '   (prohibida, correctamente fuera)';
        }

        printf("    %-8s %-12s %-34s %5d notas · %3d alumnos%s\n",
            $marca, $f['tipo'], $f['nombre_boleta'] ?? $f['nombre'],
            $f['notas'], $f['alumnos'], $alerta);
    }
    if ($gradoActual !== null) { printf("      %s aporta %d nota(s) al promedio\n", $gradoActual, $suma); }
}

echo "\n=== Estado de cada área PROHIBIDA en su grado ===\n";
echo "    (dos formas válidas de estar fuera: sin notas, o excluida por su `tipo`)\n";
foreach ($prohibidas as $nivel => $porGrado) {
    foreach ($porGrado as $grado => $nombres) {
        foreach (array_unique($nombres) as $nombre) {
            $st = $pdo->prepare("
                SELECT COUNT(*) AS notas,
                       MAX(a.tipo NOT IN ('transversal','tutoria')
                           OR a.nombre_boleta = '" . AREA_ETICA_NOMBRE_BOLETA . "') AS aporta,
                       MAX(a.tipo) AS tipo
                FROM calificaciones cal
                INNER JOIN matriculas m ON m.id = cal.matricula_id
                INNER JOIN secciones s  ON s.id = m.seccion_id
                INNER JOIN grados g     ON g.id = s.grado_id AND g.numero = ?
                INNER JOIN niveles n    ON n.id = g.nivel_id AND n.nombre = ?
                INNER JOIN competencias comp ON comp.id = cal.competencia_id
                LEFT  JOIN subareas sa  ON sa.id = comp.subarea_id
                INNER JOIN areas a      ON a.id  = COALESCE(sa.area_id, comp.area_id)
                WHERE a.nombre_boleta = ? OR a.nombre = ?
            ");
            $st->execute([$grado, $nivel, $nombre, $nombre]);
            $r = $st->fetch();
            $n = (int) ($r['notas'] ?? 0);

            if ($n === 0) {
                $estado = 'sin notas en este grado — OK';
            } elseif ((int) $r['aporta'] === 0) {
                $estado = sprintf('%d nota(s), fuera por tipo=%s — OK', $n, $r['tipo']);
            } else {
                $fallos++;
                $estado = sprintf('%d nota(s) y APORTA — *** REVISAR ***', $n);
            }
            printf("  %-12s %d° · %-30s %s\n", $nivel, $grado, $nombre, $estado);
        }
    }
}

// ── Guard anti-duplicado: Ed. Religiosa de SECUNDARIA no debe tener notas ──────
// Desde el 05/08/2026 Ética y Valores aporta al mérito en los 5 grados. Ética ES la
// Ed. Religiosa de secundaria: si el área homónima (un cascarón sin cargas) llegara a
// tener notas propias, el MISMO curso contaría dos veces en el promedio. No hay
// filtro que lo impida —es un área-curso normal—, así que se vigila aquí, y en TODOS
// los grados, no solo en 5.º.
echo "\n=== Guard anti-duplicado — Ed. Religiosa de secundaria (cascarón) ===\n";
// OJO: COUNT(cal.id), no COUNT(*) — con LEFT JOIN, COUNT(*) cuenta las filas de
// competencias aunque no tengan ninguna calificación y da un falso positivo.
$dup = $pdo->query("
    SELECT g.numero AS grado, COUNT(cal.id) AS notas,
           (SELECT COUNT(*) FROM cargas_academicas ca WHERE ca.area_id = a.id) AS cargas
    FROM areas a
    INNER JOIN niveles n ON n.id = a.nivel_id AND n.nombre = 'Secundaria'
    LEFT  JOIN competencias comp ON comp.area_id = a.id
    LEFT  JOIN calificaciones cal ON cal.competencia_id = comp.id
    LEFT  JOIN matriculas m ON m.id = cal.matricula_id
    LEFT  JOIN secciones s  ON s.id = m.seccion_id
    LEFT  JOIN grados g     ON g.id = s.grado_id
    WHERE a.nombre = 'Educación Religiosa'
    GROUP BY a.id, g.numero
    HAVING notas > 0
")->fetchAll(PDO::FETCH_ASSOC);

if (!$dup) {
    echo "  OK   sin notas propias en ningún grado (su nota la produce Ética y Valores)\n";
} else {
    foreach ($dup as $d) {
        $fallos++;
        printf("  FALLA %d° · %d nota(s) propias y %d carga(s) — DOBLE CONTEO con Ética\n",
            (int) $d['grado'], (int) $d['notas'], (int) $d['cargas']);
    }
}

echo "\n" . ($fallos === 0
    ? "RESULTADO: OK — ninguna área prohibida aporta al mérito.\n"
    : "RESULTADO: {$fallos} ÁREA(S) PROHIBIDA(S) APORTANDO AL MÉRITO.\n");

exit($fallos === 0 ? 0 : 1);
