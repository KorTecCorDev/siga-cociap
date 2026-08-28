<?php

/**
 * Verificación de la CASCADA de filtros del explorador de criterios
 * (/consulta-notas/{periodo}/criterios). Solo lectura sobre la BD.
 *
 * Ejercita el metodo REAL `ConsultaNotasController::arbolCriterios()` por
 * reflexion — no reescribe su logica aqui. Las expectativas NO son numeros
 * magicos: se calculan con SQL independiente contra la misma BD, para que un
 * cambio de datos no convierta el verificador en un aserto que se acusa solo.
 *
 * Que se comprueba:
 *   1. El catalogo de grados se indexa por grado_id y NO colisiona entre niveles.
 *   2. Cada opcion lleva el dato que la cascada del cliente necesita.
 *   3. El filtro por grado ya no mezcla niveles (las DOS ramas: primaria y
 *      secundaria del mismo numero de grado).
 *   4. El dato del docente sostiene la pertenencia real (los que dictan en los
 *      dos niveles no se pierden en ninguno).
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('CONFIG_PATH', ROOT_PATH . '/config');

require ROOT_PATH . '/app/Helpers/helpers.php';

spl_autoload_register(function (string $c): void {
    foreach ([
        'Core\\'            => '/core/',
        'App\\Models\\'     => '/app/Models/',
        'App\\Controllers\\' => '/app/Controllers/',
    ] as $pre => $base) {
        if (str_starts_with($c, $pre)) {
            $f = ROOT_PATH . $base . str_replace('\\', '/', substr($c, strlen($pre))) . '.php';
            if (is_file($f)) { require $f; }
        }
    }
});

$ok  = true;
$chk = function (string $t, bool $c) use (&$ok) {
    printf("  [%s] %s\n", $c ? 'OK ' : 'FAIL', $t);
    $ok = $ok && $c;
};

// ── Arranque del controlador sin pasar por requireRole() ──────────────────
// El constructor exige sesion con rol; aqui solo se quiere la capa de datos.
$rc  = new ReflectionClass(App\Controllers\Consulta\ConsultaNotasController::class);
$ctl = $rc->newInstanceWithoutConstructor();
foreach ([
    'calModel'      => App\Models\CalificacionModel::class,
    'criterioModel' => App\Models\CriterioModel::class,
] as $prop => $clase) {
    $p = $rc->getProperty($prop);
    $p->setAccessible(true);
    $p->setValue($ctl, new $clase());
}
$arbolM = $rc->getMethod('arbolCriterios');
$arbolM->setAccessible(true);
$arbol = fn(int $periodo, array $filtros = []) => $arbolM->invoke($ctl, $periodo, $filtros);

$db = new App\Models\CalificacionModel();

// ── Periodo de trabajo: el que mas bloqueos tiene (universo completo) ─────
$fila = $db->query("
    SELECT bc.periodo_id, COUNT(*) AS n
    FROM bloqueos_competencia bc
    INNER JOIN cargas_academicas ca ON ca.id = bc.carga_id AND ca.estado = 'activa'
    GROUP BY bc.periodo_id ORDER BY n DESC LIMIT 1
");
if (!$fila) {
    echo "SIN DATOS: no hay bloqueos en ningun periodo. Nada que verificar.\n";
    exit(0);
}
$periodoId = (int) $fila[0]['periodo_id'];
echo "Periodo de prueba: {$periodoId} ({$fila[0]['n']} bloqueos)\n\n";

$base = $arbol($periodoId);

// Universo real segun SQL independiente, para contrastar.
$univ = $db->query("
    SELECT DISTINCT n.id AS nivel_id, g.id AS grado_id, g.numero, s.id AS seccion_id,
           ca.docente_id
    FROM bloqueos_competencia bc
    INNER JOIN cargas_academicas ca ON ca.id = bc.carga_id AND ca.estado = 'activa'
    INNER JOIN secciones s ON s.id = ca.seccion_id
    INNER JOIN grados    g ON g.id = s.grado_id
    INNER JOIN niveles   n ON n.id = g.nivel_id
    WHERE bc.periodo_id = ?
", [$periodoId]);

$gradosSql   = array_unique(array_column($univ, 'grado_id'));
$numerosSql  = array_unique(array_column($univ, 'numero'));
$seccionSql  = array_unique(array_column($univ, 'seccion_id'));
$docenteSql  = array_unique(array_column($univ, 'docente_id'));

echo "1) El catalogo de grados ya no colisiona entre niveles\n";
$chk(
    sprintf('hay %d grados en el catalogo (los reales del periodo)', count($gradosSql)),
    count($base['grados']) === count($gradosSql)
);
// La rama que fallaba: indexar por `numero` fundia grados de distinto nivel.
// Si el periodo no tuviera numeros repetidos, este aserto no probaria nada:
// se declara explicitamente en vez de asumirlo.
if (count($numerosSql) < count($gradosSql)) {
    $chk(
        sprintf('el catalogo NO se colapsa a %d entradas (numeros distintos)', count($numerosSql)),
        count($base['grados']) > count($numerosSql)
    );
} else {
    echo "  [--] este periodo no tiene numeros de grado repetidos: colision no observable\n";
}
$chk('las claves del catalogo son grado_id reales',
    array_diff(array_keys($base['grados']), $gradosSql) === []);

echo "\n2) Cada opcion lleva el dato que la cascada necesita\n";
$gradosOk = true;
foreach ($base['grados'] as $gid => $g) {
    $esperado = null;
    foreach ($univ as $u) { if ((int) $u['grado_id'] === $gid) { $esperado = (int) $u['nivel_id']; break; } }
    if (!isset($g['nivel_id'], $g['etiqueta']) || $g['nivel_id'] !== $esperado || trim($g['etiqueta']) === '') {
        $gradosOk = false;
    }
}
$chk('cada grado trae nivel_id correcto y etiqueta no vacia', $gradosOk);

// La etiqueta tiene que distinguir dos grados del mismo numero, o el imprimible
// vuelve a estampar "1°" sin decir de que nivel.
$etiquetas = array_column($base['grados'], 'etiqueta');
$chk('las etiquetas de grado son todas distintas entre si',
    count(array_unique($etiquetas)) === count($etiquetas));

$seccOk = true;
foreach ($base['seccionesCat'] as $sid => $s) {
    $esperado = null;
    foreach ($univ as $u) { if ((int) $u['seccion_id'] === $sid) { $esperado = $u; break; } }
    if (!$esperado
        || ($s['nivel_id'] ?? null) !== (int) $esperado['nivel_id']
        || ($s['grado_id'] ?? null) !== (int) $esperado['grado_id']) {
        $seccOk = false;
    }
}
$chk('cada seccion trae nivel_id y grado_id coherentes con la BD', $seccOk);
$chk(sprintf('el catalogo tiene las %d secciones del periodo', count($seccionSql)),
    count($base['seccionesCat']) === count($seccionSql));

echo "\n3) El filtro por grado ya no mezcla niveles (las DOS ramas)\n";
// Se busca un numero de grado que exista en mas de un nivel y se comprueban
// AMBOS grados por separado: bloquear el ajeno Y dejar pasar el propio.
$porNumero = [];
foreach ($univ as $u) { $porNumero[(int) $u['numero']][(int) $u['grado_id']] = (int) $u['nivel_id']; }
$numeroAmbiguo = null;
foreach ($porNumero as $num => $gs) { if (count($gs) > 1) { $numeroAmbiguo = $num; break; } }

if ($numeroAmbiguo === null) {
    echo "  [--] ningun numero de grado se repite entre niveles en este periodo\n";
} else {
    foreach ($porNumero[$numeroAmbiguo] as $gid => $nivelId) {
        $esperadas = [];
        foreach ($univ as $u) {
            if ((int) $u['grado_id'] === $gid) { $esperadas[(int) $u['seccion_id']] = true; }
        }
        $res  = $arbol($periodoId, ['grado' => $gid]);
        $sids = array_column($res['secciones'], 'seccion_id');
        sort($sids);
        $esp = array_keys($esperadas);
        sort($esp);
        $chk(
            sprintf('grado_id %d (%d° del nivel %d) devuelve sus %d seccion(es) y ninguna mas',
                $gid, $numeroAmbiguo, $nivelId, count($esp)),
            $sids === $esp
        );
    }
}

echo "\n4) El dato del docente sostiene la pertenencia real\n";
$chk(sprintf('el catalogo tiene los %d docentes del periodo', count($docenteSql)),
    count($base['docentes']) === count($docenteSql));

$docOk = true;
foreach ($base['docentes'] as $did => $d) {
    if (!isset($d['nombre'], $d['secciones']) || !is_array($d['secciones'])) { $docOk = false; continue; }
    $esperadas = [];
    foreach ($univ as $u) {
        if ((int) $u['docente_id'] === $did) { $esperadas[(int) $u['seccion_id']] = true; }
    }
    $a = $d['secciones']; sort($a);
    $b = array_keys($esperadas); sort($b);
    if ($a !== $b) { $docOk = false; }
}
$chk('cada docente trae su conjunto EXACTO de secciones', $docOk);

// El caso que un "nivel del docente" romperia en silencio.
$multi = $db->query("
    SELECT ca.docente_id, COUNT(DISTINCT g.nivel_id) AS niveles
    FROM bloqueos_competencia bc
    INNER JOIN cargas_academicas ca ON ca.id = bc.carga_id AND ca.estado = 'activa'
    INNER JOIN secciones s ON s.id = ca.seccion_id
    INNER JOIN grados    g ON g.id = s.grado_id
    WHERE bc.periodo_id = ?
    GROUP BY ca.docente_id HAVING niveles > 1
", [$periodoId]);

if (!$multi) {
    echo "  [--] ningun docente dicta en dos niveles en este periodo\n";
} else {
    printf("  (%d docente(s) dictan en mas de un nivel)\n", count($multi));
    $multiOk = true;
    foreach ($multi as $m) {
        $did = (int) $m['docente_id'];
        $nivelesDelDocente = [];
        foreach ($base['docentes'][$did]['secciones'] ?? [] as $sid) {
            $nivelesDelDocente[$base['seccionesCat'][$sid]['nivel_id']] = true;
        }
        if (count($nivelesDelDocente) !== (int) $m['niveles']) { $multiOk = false; }
    }
    $chk('sus secciones cubren TODOS sus niveles (no se pierde ninguno)', $multiOk);
}

echo "\n5) Los filtros siguen combinando entre si\n";
// Un docente concreto acotado por nivel: el AND del servidor no debe romperse.
$algunDocente = (int) $univ[0]['docente_id'];
$algunNivel   = (int) $univ[0]['nivel_id'];
$esperadas = [];
foreach ($univ as $u) {
    if ((int) $u['docente_id'] === $algunDocente && (int) $u['nivel_id'] === $algunNivel) {
        $esperadas[(int) $u['seccion_id']] = true;
    }
}
$res  = $arbol($periodoId, ['nivel' => $algunNivel, 'docente' => $algunDocente]);
$sids = array_column($res['secciones'], 'seccion_id');
sort($sids);
$esp = array_keys($esperadas); sort($esp);
$chk(sprintf('nivel %d + docente %d devuelve sus %d seccion(es)', $algunNivel, $algunDocente, count($esp)),
    $sids === $esp);

// Y el catalogo NO se recorta al filtrar: si se vaciara, no habria como volver.
$chk('los catalogos siguen completos aunque haya filtro (no se autovacian)',
    count($res['grados']) === count($base['grados'])
    && count($res['docentes']) === count($base['docentes']));

echo "\n6) Toda competencia del arbol lleva su codigo MINEDU\n";
// 🔴 LAS DOS RAMAS POR SEPARADO: las academicas salen de
// `getCompetenciasPorPeriodo` y las transversales de `transversalesConContenido`,
// que es OTRA consulta. Contar el total en bloque dejaria pasar que una rama
// entera venga sin codigo — y las transversales son el 27 % de los criterios.
//
// ⚠️ SE RECORREN TODOS LOS PERIODOS, no solo el de arriba: en B1 las
// transversales cuelgan de 23 cargas `estado='inactiva'`, que el explorador
// excluye a proposito, asi que ese periodo NO ejercita la rama transversal.
// Anclar la comprobacion a un solo periodo la dejaba en "no observable" —
// verde sin haber probado nada.
$codigosBd = array_column(
    $db->query("SELECT id, codigo_minedu FROM competencias"),
    'codigo_minedu',
    'id'
);
$periodos = array_column($db->query("
    SELECT DISTINCT bc.periodo_id
    FROM bloqueos_competencia bc
    INNER JOIN cargas_academicas ca ON ca.id = bc.carga_id AND ca.estado = 'activa'
    ORDER BY bc.periodo_id
"), 'periodo_id');

$vistas    = ['academica' => 0, 'transversal' => 0];
$sinCodigo = ['academica' => 0, 'transversal' => 0];
$malCodigo = 0;
foreach ($periodos as $pid) {
    foreach ($arbol((int) $pid)['secciones'] as $s) {
        foreach ($s['cargas'] as $c) {
            foreach ($c['competencias'] as $comp) {
                $rama = !empty($comp['es_transversal']) ? 'transversal' : 'academica';
                $vistas[$rama]++;
                $cod = $comp['codigo'] ?? null;
                if ($cod === null || $cod === '') {
                    $sinCodigo[$rama]++;
                    continue;
                }
                // No basta con que venga ALGO: tiene que ser el codigo de ESA
                // competencia. Un mapeo cruzado se veria perfectamente normal.
                if (($codigosBd[$comp['competencia_id']] ?? null) !== $cod) {
                    $malCodigo++;
                }
            }
        }
    }
}
printf("  (en %d periodo(s): %d competencias academicas · %d transversales)\n",
    count($periodos), $vistas['academica'], $vistas['transversal']);
foreach (['academica', 'transversal'] as $rama) {
    // Que la rama se haya OBSERVADO es en si mismo un aserto: si un dia deja de
    // haber transversales en ningun periodo, esto pasa a FAIL y avisa de que la
    // comprobacion se quedo sin objeto, en vez de fingir que paso.
    $chk("la rama {$rama} es observable (hay competencias que comprobar)", $vistas[$rama] > 0);
    $chk("ninguna competencia {$rama} llega sin codigo", $sinCodigo[$rama] === 0);
}
$chk('el codigo mostrado es el de SU competencia (sin cruces)', $malCodigo === 0);

echo "\n7) La guarda de filtro inexistente, en sus DOS ramas\n";
// Una guarda solo esta probada si se comprueban las dos: que DESCARTE el valor
// ajeno al periodo Y que DEJE PASAR el que si existe. Probar solo la primera
// deja viva una guarda que lo tira todo.
// Rama A — DEJA PASAR: una seccion que si existe en este periodo sobrevive.
$seccionPropia = (int) $univ[0]['seccion_id'];
$res = $arbol($periodoId, ['seccion' => $seccionPropia]);
$chk("deja pasar la seccion {$seccionPropia}, que SI existe en el periodo {$periodoId}",
    (int) ($res['filtros']['seccion'] ?? 0) === $seccionPropia);
$chk('y el arbol queda acotado a esa seccion',
    array_column($res['secciones'], 'seccion_id') === [$seccionPropia]);

// Rama B — DESCARTA: una seccion de OTRO periodo que en el de destino no existe.
// ⚠️ NO se ancla al periodo de prueba de arriba: ese es el que MAS bloqueos
// tiene, y los catalogos de los demas suelen estar contenidos en el suyo, asi
// que desde ahi no hay ninguna seccion ajena y la rama quedaba "no observable"
// —verde sin probar nada—. Se busca el par (destino, origen) que si la ejercita.
$arboles = [];
foreach ($periodos as $pid) { $arboles[(int) $pid] = $arbol((int) $pid); }

$par = null;
foreach ($arboles as $destino => $aDestino) {
    foreach ($arboles as $origen => $aOrigen) {
        if ($destino === $origen) { continue; }
        $ajenas = array_diff(array_keys($aOrigen['seccionesCat']), array_keys($aDestino['seccionesCat']));
        if ($ajenas) { $par = [$destino, $origen, (int) reset($ajenas)]; break 2; }
    }
}

if ($par === null) {
    echo "  [--] los catalogos de todos los periodos coinciden:\n";
    echo "       la rama 'descarta' no es observable con estos datos\n";
} else {
    [$destino, $origen, $ajena] = $par;
    $res = $arbol($destino, ['seccion' => $ajena]);
    $chk("descarta la seccion {$ajena}, que existe en el periodo {$origen} pero no en el {$destino}",
        (int) ($res['filtros']['seccion'] ?? 0) === 0);
    // Lo que de verdad se estaba arreglando: al descartarlo, la pantalla vuelve
    // a mostrar TODO en vez de quedarse vacia filtrando por algo invisible.
    $chk('y el arbol NO queda vacio: muestra el periodo entero',
        count($res['secciones']) === count($arboles[$destino]['secciones'])
        && count($arboles[$destino]['secciones']) > 0);
}

echo "\n8) Cambiar de bimestre no arrastra la consulta anterior\n";
// El redirect vive en el controlador (`criterios()`), que no se puede invocar
// sin sesion. Se comprueba sobre el CODIGO: que la construccion de la query
// con los filtros ya no exista en ese salto.
//
// ⚠️ ESTOS DOS ASERTOS SE REESCRIBIERON EL 28/08/2026, y la leccion importa.
// Buscaban cadenas LITERALES en dos archivos concretos, y el conmutador de 3
// ejes (commit 550e5ff) movio las dos piezas sin tocar el comportamiento:
//   · el `onchange` se fue de `criterios.php` al partial `_nav.php`, comun a
//     los tres ejes;
//   · el redirect inline se extrajo a `saltarDePeriodo()`, compartido por
//     Docentes y Criterios.
// Resultado: el verificador llevaba dias en rojo por un REFACTOR QUE MEJORABA
// el codigo —una copia en vez de dos—, y un verificador cronicamente rojo
// ensena a ignorar la suite entera. Ahora se comprueba la MECANICA donde vive,
// no su ubicacion de aquel dia.
$fuente = file_get_contents(ROOT_PATH . '/app/Controllers/Consulta/ConsultaNotasController.php');

// La mecanica del salto: al pedir otro periodo, redirige a la ruta del eje SIN
// arrastrar la query. Se ancla al helper compartido, no a la copia inline.
$saltoOk = (bool) preg_match(
    '/private function saltarDePeriodo\(int \$periodoId, string \$ruta\).*?'
    . '\$destino > 0 && \$destino !== \$periodoId\s*\)\s*\{\s*'
    . 'redirect\(url\(\s*\'consulta-notas\/\'\s*\.\s*\$destino\s*\.\s*\'\/\'\s*\.\s*\$ruta\s*\)\);/s',
    $fuente
);
$chk('el salto de bimestre redirige a la ruta LIMPIA, sin http_build_query', $saltoOk);

// Y que el eje de Criterios lo USE: el helper existe pero podria no llamarse.
$chk('criterios() usa saltarDePeriodo() (no reimplementa el salto)',
    (bool) preg_match(
        '/public function criterios\(string \$periodoId\).*?\$this->saltarDePeriodo\(\$periodoId, \'criterios\'\)/s',
        $fuente
    ));

// El selector vive en el partial de navegacion, comun a los tres ejes. Se
// comprueba por ATRIBUTOS y no por la cadena entera: el orden de los atributos
// no es una regla de negocio y ya rompio este aserto una vez.
$nav = file_get_contents(ROOT_PATH . '/resources/views/consulta-notas/_nav.php');
$chk('el selector de bimestre auto-aplica (onchange en _nav.php)',
    (bool) preg_match('/<select[^>]*name="periodo_id"[^>]*onchange="this\.form\.submit\(\)"/', $nav));

echo "\n9) El chip de codigo reusa el estilo del proyecto\n";
// El codigo de competencia se pinta con `.competencia-card__codigo`, el chip que
// ya usan otras 5 vistas. Un chip propio para esta pantalla se veria bien el dia
// que se escribe y divergiria en el siguiente retoque del sistema.
$vistaPantalla = file_get_contents(ROOT_PATH . '/resources/views/consulta-notas/criterios.php');
$vistaPapel    = file_get_contents(ROOT_PATH . '/resources/views/consulta-notas/criterios-imprimir.php');
$scss          = file_get_contents(ROOT_PATH . '/resources/sass/pages/_consulta-notas.scss');

foreach (['pantalla' => $vistaPantalla, 'imprimible' => $vistaPapel] as $donde => $html) {
    $chk("el {$donde} usa competencia-card__codigo",
        str_contains($html, 'class="competencia-card__codigo"'));
}
// Las dos ramas: que use la del proyecto Y que no haya resucitado una propia.
// ⚠️ Se mide sobre el CSS COMPILADO, no sobre el SCSS: el fuente menciona
// `criterios-chip--codigo` en un comentario de advertencia, y buscar la cadena
// ahi hacia que el aserto se acusara a si mismo. El CSS servido no tiene
// comentarios, y ademas es lo que de verdad llega al navegador.
$css = file_get_contents(ROOT_PATH . '/public/css/app.css');
$chk('el CSS servido no tiene un chip de codigo propio de esta pagina',
    !str_contains($css, 'criterios-chip--codigo')
    && !str_contains($css, 'criterios-print__codigo'));
$chk('y si conserva el ajuste de metrica del imprimible sobre el chip comun',
    str_contains($css, '.criterios-print .competencia-card__codigo'));
unset($scss);

// El estilo tiene que EXISTIR donde se dice que existe, o la clase seria un
// nombre sin CSS detras (que es lo que se creyo al principio).
$dashboard = file_get_contents(ROOT_PATH . '/resources/sass/pages/_dashboard.scss');
$chk('el chip esta definido en pages/_dashboard.scss (el que SI se importa)',
    (bool) preg_match('/&__codigo\s*\{[^}]*border-radius:\s*20px/s', $dashboard));
$chk('y pages/_dashboard.scss se importa en app.scss',
    str_contains(file_get_contents(ROOT_PATH . '/resources/sass/app.scss'), "pages/dashboard"));

echo "\n", $ok ? "TODO OK\n" : "HAY FALLOS\n";
exit($ok ? 0 : 1);
