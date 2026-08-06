<?php

/**
 * Verificación — la boleta muestra TODAS las competencias del plan de la sección,
 * con guion donde no hay dato.
 * Uso: php database/verificaciones/verif_plan_completo_boleta.php
 *
 * SOLO LECTURA: no escribe nada. Se puede correr en PRODUCCION.
 *
 * CONTEXTO (05/08/2026). Hasta ahora la boleta construia sus filas A PARTIR DE LAS
 * NOTAS: una competencia sin calificacion no existia en el documento, y el numero
 * de filas variaba entre alumnos de la MISMA seccion. Ahora `BoletaModel::armar()`
 * siembra el esqueleto del plan (`CalificacionModel::estructuraCompetenciasSeccion`)
 * y las notas se superponen.
 *
 * EL UNIVERSO SON LAS CARGAS ACTIVAS DE LA SECCION. De ahi salen solas, sin una
 * sola excepcion escrita a mano, las exclusiones que pide el colegio:
 *   - Ed. Religiosa NO se muestra en secundaria (0 cargas); si en primaria.
 *   - 5.º de secundaria no lleva Arte y Cultura ni Educacion para el Trabajo.
 *   - El Taller de Pre-Calculo solo se dicta en 5.º.
 *
 * QUE COMPRUEBA:
 *   1. EXCLUSIONES CURRICULARES: las areas del catalogo sin carga por grado son
 *      exactamente las esperadas (y sirve de alarma si manana falta una carga por
 *      olvido: al ser el universo, un area sin carga desaparece del documento).
 *   2. UNIFORMIDAD: todos los alumnos de una seccion tienen EL MISMO conjunto de
 *      competencias en su boleta. Era el sintoma de que las filas salian de las notas.
 *   3. EQUIVALENCIA: ninguna competencia CON NOTA desaparecio del documento
 *      (incluye la red de seguridad: notas de una carga ya desactivada, y las de la
 *      otra matricula en un retorno de grado).
 *   4. EXONERADOS: siguen mostrando EXO, no guion. Con el esqueleto sembrado la
 *      entrada YA existe cuando corre `ExoneracionModel::inyectarEnAreas`, asi que
 *      esta es la regresion que hay que vigilar.
 *   5. TRANSVERSALES: el bloque aparece SIEMPRE, aunque el tutor no haya cerrado.
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

$pdo     = Core\Database::connect();
$boletas = new App\Models\BoletaModel();
$calMod  = new App\Models\CalificacionModel();
$fallos  = 0;

$ok = static function (bool $cond, string $caso, string $detalle = '') use (&$fallos): void {
    if ($cond) { printf("  OK    %-58s %s\n", $caso, $detalle); return; }
    $fallos++;
    printf("  ***   %-58s %s\n", $caso, $detalle);
};

$anio = $pdo->query("SELECT id FROM anios_academicos WHERE estado = 'activo' LIMIT 1")->fetch();
if (!$anio) { fwrite(STDERR, "ABORTA: no hay anio academico activo.\n"); exit(1); }
$anioId = (int) $anio['id'];

$periodoRef = $pdo->query("
    SELECT id FROM periodos WHERE anio_id = {$anioId} ORDER BY numero LIMIT 1
")->fetch();
if (!$periodoRef) { fwrite(STDERR, "ABORTA: el anio activo no tiene periodos.\n"); exit(1); }
$periodoRefId = (int) $periodoRef['id'];

/** Nombres de area tal como los rotula la boleta, para una matricula. */
$areasDe = static function (array $boleta): array {
    return array_keys($boleta['areas'] ?? []);
};

/** [competencia_id => nombre_area] de una boleta ya armada. */
$compsDe = static function (array $boleta): array {
    $out = [];
    foreach ($boleta['areas'] ?? [] as $areaNombre => $comps) {
        foreach ($comps as $compId => $_) { $out[(int) $compId] = $areaNombre; }
    }
    ksort($out);
    return $out;
};

echo "\n=== 1. EXCLUSIONES CURRICULARES (catalogo del nivel vs cargas activas) ===\n";
echo "    Un area del catalogo SIN carga no aparece en la boleta. Deben ser solo estas:\n\n";

$sinCarga = $pdo->query("
    SELECT n.nombre AS nivel, g.numero AS grado, a.id AS area_id, a.nombre AS area
    FROM niveles n
    INNER JOIN grados g   ON g.nivel_id = n.id
    INNER JOIN areas a    ON a.nivel_id = n.id AND a.activa = 1 AND a.tipo <> 'transversal'
    INNER JOIN secciones s ON s.grado_id = g.id AND s.anio_id = {$anioId}
    LEFT  JOIN subareas sb ON sb.area_id = a.id
    LEFT  JOIN cargas_academicas ca ON ca.seccion_id = s.id AND ca.estado = 'activa'
                                   AND (ca.area_id = a.id OR ca.subarea_id = sb.id)
    GROUP BY n.nombre, g.numero, a.id
    HAVING COUNT(DISTINCT ca.id) = 0
    ORDER BY n.nombre, g.numero, a.id
")->fetchAll();

$esperadas = [];   // "nivel|grado|area" de las exclusiones acordadas con el colegio
foreach ([1, 2, 3, 4, 5] as $gr) {
    $esperadas["Secundaria|{$gr}|Educación Religiosa"] = true;   // la evalua Etica y Valores (TOE)
}
foreach ([1, 2, 3, 4] as $gr) {
    $esperadas["Secundaria|{$gr}|Taller de Pre-Cálculo"] = true; // solo se dicta en 5.º
}
$esperadas['Secundaria|5|Arte y Cultura']            = true;     // 5.º no lo lleva
$esperadas['Secundaria|5|Educación para el Trabajo'] = true;     // 5.º no lo lleva

$inesperadas = [];
foreach ($sinCarga as $f) {
    $clave = $f['nivel'] . '|' . $f['grado'] . '|' . $f['area'];
    printf("      %-11s %sº  %-32s %s\n", $f['nivel'], $f['grado'], $f['area'],
        isset($esperadas[$clave]) ? 'esperada' : '<-- NO ESPERADA');
    if (!isset($esperadas[$clave])) { $inesperadas[] = $clave; }
    unset($esperadas[$clave]);
}
echo "\n";
$ok($inesperadas === [], 'sin areas del plan desaparecidas por falta de carga',
    $inesperadas ? implode(' · ', $inesperadas) : '');
$ok($esperadas === [], 'las exclusiones acordadas siguen vigentes',
    $esperadas ? 'ya no se cumplen: ' . implode(' · ', array_keys($esperadas)) : '');

echo "\n=== 2. UNIFORMIDAD Y EXCLUSIONES EN LAS BOLETAS REALES ===\n";
echo "    (umbral 'todos' + estructura completa; hasta 3 alumnos por seccion)\n\n";

$secciones = $pdo->query("
    SELECT s.id, s.nombre, g.numero AS grado, g.nombre_display AS grado_nombre, n.nombre AS nivel
    FROM secciones s
    INNER JOIN grados g  ON g.id = s.grado_id
    INNER JOIN niveles n ON n.id = g.nivel_id
    WHERE s.anio_id = {$anioId}
    ORDER BY n.nombre, g.numero, s.nombre
")->fetchAll();

$stAlumnos = $pdo->prepare("
    SELECT m.id
    FROM matriculas m
    WHERE m.seccion_id = ?
      AND m.tipo NOT IN ('trasladado', 'retirado')
      AND m.id NOT IN (SELECT matricula_oficial_id FROM retornos_grado WHERE estado = 'activo')
    ORDER BY m.id
    LIMIT 3
");

foreach ($secciones as $sec) {
    $stAlumnos->execute([(int) $sec['id']]);
    $alumnos = array_column($stAlumnos->fetchAll(), 'id');
    if (!$alumnos) { continue; }

    $etiqueta   = sprintf('%s %s "%s"', $sec['nivel'], $sec['grado_nombre'], $sec['nombre']);
    $referencia = null;
    $uniforme   = true;

    foreach ($alumnos as $mid) {
        $b = $boletas->armar((int) $mid, $periodoRefId, 'todos', true);
        if (!$b) { continue; }
        $comps = $compsDe($b);
        if ($referencia === null) { $referencia = $comps; $areasRef = $areasDe($b); continue; }
        if (array_keys($comps) !== array_keys($referencia)) { $uniforme = false; }
    }
    if ($referencia === null) { continue; }

    $ok($uniforme, "uniformidad · {$etiqueta}",
        sprintf('%d competencias · %d areas', count($referencia), count($areasRef)));

    // Exclusiones vistas desde el documento, no desde la BD.
    $tieneReligionSuelta = in_array('Educación Religiosa', $areasRef, true);
    if ($sec['nivel'] === 'Secundaria') {
        $ok(!$tieneReligionSuelta, "sin Ed. Religiosa suelta · {$etiqueta}");
        if ((int) $sec['grado'] === 5) {
            $ok(!in_array('Arte y Cultura', $areasRef, true), "5.º sin Arte y Cultura · {$etiqueta}");
            $ok(!in_array('Educación para el Trabajo', $areasRef, true), "5.º sin EPT · {$etiqueta}");
        }
        $etica = array_filter($areasRef, static fn($a) => str_contains($a, 'Ética y Valores'));
        $ok($etica !== [], "Etica y Valores presente · {$etiqueta}", implode('', $etica));
    } else {
        $ok($tieneReligionSuelta, "primaria SI muestra Ed. Religiosa · {$etiqueta}");
    }

    // 5. Transversales: el bloque existe aunque el tutor no haya cerrado.
    //    Se comprueba por ID de competencia, NO por el rotulo del area: en
    //    secundaria se llama "Comp. Transv." y buscar la palabra "transversal"
    //    daria un falso negativo (es el mismo criterio fragil que las vistas
    //    tenian y que se corrigio a "transv").
    $stTransv = $pdo->prepare("
        SELECT comp.id
        FROM secciones s
        INNER JOIN grados g          ON g.id = s.grado_id
        INNER JOIN areas a           ON a.nivel_id = g.nivel_id AND a.tipo = 'transversal' AND a.activa = 1
        INNER JOIN competencias comp ON comp.area_id = a.id
        WHERE s.id = ?
    ");
    $stTransv->execute([(int) $sec['id']]);
    $idsTransv = array_map('intval', array_column($stTransv->fetchAll(), 'id'));
    $faltan    = array_diff($idsTransv, array_keys($referencia));
    $ok($idsTransv !== [] && $faltan === [], "transversales presentes · {$etiqueta}",
        sprintf('%d competencias transversales', count($idsTransv)));
}

echo "\n=== 3. EQUIVALENCIA: ninguna competencia CON NOTA desaparecio ===\n\n";

// Universo real de competencias calificadas (todas las fuentes, todos los periodos)
// contra lo que el documento muestra hoy.
$conNotas = $pdo->query("
    SELECT DISTINCT cal.matricula_id
    FROM calificaciones cal
    INNER JOIN matriculas m ON m.id = cal.matricula_id
    WHERE m.anio_id = {$anioId}
    ORDER BY cal.matricula_id
    LIMIT 40
")->fetchAll();

$periodosAnio = $pdo->query("SELECT id FROM periodos WHERE anio_id = {$anioId} ORDER BY numero")->fetchAll();
$perdidas = 0;
$revisadas = 0;

foreach ($conNotas as $fila) {
    $mid = (int) $fila['matricula_id'];
    $b   = $boletas->armar($mid, $periodoRefId, 'todos', true);
    if (!$b) { continue; }
    $enDocumento = $compsDe($b);

    $ctx = $calMod->boletaContexto($mid);
    foreach ($periodosAnio as $p) {
        foreach ($ctx['fuentes'] as $fuente) {
            foreach ($calMod->getBoletaAlumno((int) $fuente, (int) $p['id']) as $nota) {
                $revisadas++;
                if (!isset($enDocumento[(int) $nota['competencia_id']])) {
                    $perdidas++;
                    printf("  ***   matricula %d · competencia %d (%s) TIENE NOTA y no esta en la boleta\n",
                        $mid, (int) $nota['competencia_id'], $nota['nombre_corto'] ?? '?');
                }
            }
        }
    }
}
$ok($perdidas === 0, 'ninguna nota quedo fuera del documento',
    sprintf('%d filas de nota revisadas en %d matriculas', $revisadas, count($conNotas)));

echo "\n=== 4. EXONERADOS: EXO, no guion ===\n\n";

$exos = $pdo->query("
    SELECT e.matricula_id, e.area_id, a.nombre AS area
    FROM exoneraciones e
    INNER JOIN areas a      ON a.id = e.area_id
    INNER JOIN matriculas m ON m.id = e.matricula_id
    WHERE e.revocado_en IS NULL AND m.anio_id = {$anioId}
    ORDER BY e.id
")->fetchAll();

if (!$exos) {
    echo "  (no hay exoneraciones vigentes en este entorno: no se puede probar aqui)\n";
} else {
    foreach ($exos as $exo) {
        $b = $boletas->armar((int) $exo['matricula_id'], $periodoRefId, 'todos', true);
        if (!$b) { continue; }
        $conExo = 0;
        $literalesExo = 0;
        foreach ($b['areas'] as $comps) {
            foreach ($comps as $comp) {
                if (empty($comp['es_exonerado'])) { continue; }
                $conExo++;
                if (($comp['literal_final'] ?? null) === 'EXO') { $literalesExo++; }
            }
        }
        $ok($conExo > 0 && $conExo === $literalesExo,
            sprintf('matricula %d exonerada de %s', (int) $exo['matricula_id'], $exo['area']),
            sprintf('%d competencias marcadas · %d con literal EXO', $conExo, $literalesExo));
    }
}

echo "\n" . str_repeat('-', 78) . "\n";
if ($fallos === 0) {
    echo "RESULTADO: OK — el documento muestra el plan completo sin perder ninguna nota.\n\n";
    exit(0);
}
printf("RESULTADO: %d COMPROBACION(ES) FALLIDA(S).\n\n", $fallos);
exit(1);
