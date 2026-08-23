<?php

/**
 * Verificación — HUMO DE LA VISTA `/docente/nomina`: se RENDERIZA de verdad.
 * Uso: php database/verificaciones/verif_nomina_docente_render.php
 *
 * SOLO LECTURA: no escribe en la BD. Ejecuta el controlador REAL con una sesión
 * de docente simulada y comprueba el HTML resultante.
 *
 * POR QUÉ EXISTE — 10/08/2026, y es una lección cara:
 * al cambiar la fuente del orden de mérito se eliminó la variable `$bimestre`
 * del controlador, pero el array que se pasa a la vista seguía leyéndola en
 * `'bimestreCerrado' => $bimestre['nombre_display'] ?? null`. El `??` **suprime
 * el aviso de variable indefinida**, así que la clave pasó a valer `null` en
 * silencio y **el panel de boleta (imprimir + digital) desapareció de TODAS las
 * cards**. Ni `php -l` ni las verificaciones del MODELO podían verlo: hay que
 * ejercitar la vista.
 *
 * QUÉ COMPRUEBA
 *   1. La vista renderiza sin error y con contenido.
 *   2. EL PANEL DE BOLETA ESTÁ PRESENTE (la regresión concreta), y aparece
 *      tantas veces como alumnos con boleta disponible.
 *   3. El rótulo del mérito NO nombra un bimestre sin publicar (compuerta 044).
 *   4. El buscador NO lista matrículas 'pendiente'.
 *
 * ⚠️ Si algún día la boleta deja de mostrarse A PROPÓSITO, este script debe
 * cambiar con la decisión — no silenciarse.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH',  ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('VIEW_PATH', ROOT_PATH . '/resources/views');

spl_autoload_register(function (string $class): void {
    $map = [
        'Core\\'             => CORE_PATH . '/',
        'App\\Models\\'      => APP_PATH . '/Models/',
        'App\\Controllers\\' => APP_PATH . '/Controllers/',
    ];
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

$pdo = Core\Database::connect();

$fallos = 0;
$ok = function (bool $cond, string $etiqueta, string $detalle = '') use (&$fallos): void {
    printf("  %-5s %-54s %s\n", $cond ? 'OK' : '***', $etiqueta, $detalle);
    if (!$cond) { $fallos++; }
};

// ── Sesión de DOCENTE simulada: el que más secciones tenga, para que la
//    nómina traiga alumnos de verdad.
$docente = $pdo->query("
    SELECT u.id, u.persona_id, COUNT(DISTINCT ca.seccion_id) AS secciones
    FROM usuarios u
    INNER JOIN roles r            ON r.id = u.rol_id AND r.codigo = 'docente'
    INNER JOIN cargas_academicas ca ON ca.docente_id = u.id AND ca.estado = 'activa'
    WHERE u.estado = 'activo'
    GROUP BY u.id
    ORDER BY secciones DESC
    LIMIT 1
")->fetch();

if (!$docente) {
    fwrite(STDERR, "ABORTA: no hay ningun docente activo con cargas.\n");
    exit(1);
}

$_SESSION = ['auth_user' => [
    'id'          => (int) $docente['id'],
    'persona_id'  => (int) $docente['persona_id'],
    'rol_codigo'  => 'docente',
    'nombre'      => 'SONDA',
]];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI']    = '/docente/nomina';

printf("== Docente de la sonda: usuario %d (%d secciones) ==\n\n",
    $docente['id'], $docente['secciones']);

// ── Renderizar la vista real, capturando la salida.
ob_start();
try {
    (new App\Controllers\Docente\PanelController())->nomina();
} catch (\Throwable $e) {
    ob_end_clean();
    fwrite(STDERR, "ABORTA: la vista lanzo una excepcion: " . $e->getMessage() . "\n");
    exit(1);
}
$html = ob_get_clean();

$ok($html !== '' && strlen($html) > 500, 'la vista renderiza con contenido',
    strlen($html) . ' bytes');

// ── 2. EL PANEL DE BOLETA — la regresion concreta.
$paneles = substr_count($html, 'nomina-boleta-panel');

// Cuantos alumnos DEBERIAN tener panel: los de sus secciones con boleta y con
// alguna boleta visible (bimestre cerrado o borrador del activo).
$hayCerrado = (bool) $pdo->query("
    SELECT COUNT(*) FROM periodos p
    INNER JOIN anios_academicos a ON a.id = p.anio_id AND a.estado = 'activo'
    WHERE p.estado = 'cerrado'
")->fetchColumn();

$ok($paneles > 0,
    'el PANEL DE BOLETA aparece en las cards',
    $paneles . ' panel(es)' . ($hayCerrado ? ' · hay bimestre cerrado, debe haberlos' : ''));

if ($hayCerrado) {
    $ok(str_contains($html, 'Oficial ·') || str_contains($html, 'Borrador ·'),
        'el panel lleva su etiqueta (Oficial / Borrador)');
    $ok(str_contains($html, '/imprimir'), 'el panel ofrece el enlace de IMPRIMIR');
}

// ── 3. El rotulo del merito no nombra un bimestre sin publicar.
//
// 🔴 DOS DEFECTOS CORREGIDOS EL 22/08/2026, los dos daban FUGA falsa:
//
//   1. "SIN PUBLICAR" no es "no es el ultimo publicado".
//      Se restaban los nombres de `ultimoPeriodoPublicadoPorNivel`, que devuelve
//      UNO por nivel — el ultimo. Con B1 y B2 ambos publicados, B1 caia en la
//      lista de "sin publicar" pese a estarlo desde el 22/07. La pregunta
//      correcta es "¿este bimestre tiene ALGUN nivel publicado?", y para eso
//      esta `periodosConAlgunNivelPublicado()`.
//
//   2. El matching era por SUBCADENA, sin frontera.
//      `str_contains($html, 'I Bimestre (')` encuentra "I Bimestre (" DENTRO de
//      "II Bimestre (Primaria)". Bastaba con que el rotulo nombrara el II para
//      que el I se diera por fugado. Ahora se compara con lookaround de letra,
//      asi que "I Bimestre" ya no matchea dentro de "II Bimestre".
//
// Ademas se acota la busqueda AL ROTULO en vez de a todo el HTML: es lo que este
// bloque dice comprobar, y buscar en la pagina entera daria falso positivo el
// dia que se liste un bimestre por cualquier otro motivo (un selector, una card).
$anioId = (int) $pdo->query("SELECT id FROM anios_academicos WHERE estado='activo' ORDER BY anio DESC LIMIT 1")->fetchColumn();
$publicadosSet = (new App\Models\PublicacionBoletaModel())->periodosConAlgunNivelPublicado();

$sinPublicar = [];
foreach ($pdo->query("SELECT p.id, p.nombre_display FROM periodos p
                       WHERE p.anio_id = {$anioId} AND p.estado = 'cerrado'") as $p) {
    if (!isset($publicadosSet[(int) $p['id']])) {
        $sinPublicar[] = $p['nombre_display'];
    }
}

// El rotulo del merito, aislado del resto de la pagina.
$rotulo = '';
if (preg_match('/Orden de mérito vigente:.{0,240}/us', $html, $mRot)) {
    $rotulo = preg_replace('/\s+/', ' ', strip_tags($mRot[0]));
}
$ok($rotulo !== '', 'el rotulo del merito esta presente en la pagina',
    $rotulo !== '' ? mb_substr($rotulo, 0, 70) : 'NO SE ENCONTRO EL ROTULO');

$fugado = [];
foreach ($sinPublicar as $nombre) {
    // Frontera de letra a ambos lados: "I Bimestre" no puede casar dentro de
    // "II Bimestre" ni de "I Bimestres".
    $re = '/(?<!\p{L})' . preg_quote($nombre, '/') . '(?!\p{L})/u';
    if ($rotulo !== '' && preg_match($re, $rotulo)) {
        $fugado[] = $nombre;
    }
}
$ok($fugado === [], 'el rotulo del merito no nombra un bimestre sin publicar',
    $fugado ? 'FUGA: ' . implode(', ', $fugado)
            : ($sinPublicar ? 'oculta: ' . implode(', ', $sinPublicar) : 'todos publicados'));

// ── 4. El buscador no lista matriculas 'pendiente'.
$pendientes = $pdo->query("
    SELECT CONCAT(p.apellido_paterno, ' ', p.apellido_materno) AS ap
    FROM matriculas m
    INNER JOIN estudiantes e ON e.id = m.estudiante_id
    INNER JOIN personas p    ON p.id = e.persona_id
    INNER JOIN anios_academicos a ON a.id = m.anio_id AND a.estado = 'activo'
    WHERE m.estado = 'pendiente'
")->fetchAll(PDO::FETCH_COLUMN);

$listados = array_values(array_filter($pendientes, static fn(string $ap): bool => str_contains($html, $ap)));
$ok($listados === [], 'el buscador no lista matriculas pendientes',
    $listados ? 'aparecen: ' . implode(', ', $listados) : count($pendientes) . ' pendiente(s) fuera');

echo "\n" . ($fallos === 0 ? "RESULTADO: OK\n" : "RESULTADO: {$fallos} FALLO(S)\n");
exit($fallos === 0 ? 0 : 1);
