<?php

/**
 * Verificación — el puesto de mérito de la nómina docente respeta la compuerta 044.
 * Uso: php database/verificaciones/verif_merito_nomina_compuerta.php
 *
 * ESCRIBE sobre `periodos_publicacion` para recorrer los escenarios, pero TODO
 * corre en una TRANSACCIÓN con ROLLBACK y el paso final comprueba que el estado
 * volvió. Lleva el guard de secretos: NO correr en producción.
 *
 * QUÉ COMPRUEBA — `PublicacionBoletaModel::ultimoPeriodoPublicadoPorNivel()`, que
 * es la fuente del puesto que ve el docente en `/docente/nomina`:
 *   1. ESTADO REAL DE HOY: qué bimestre da por nivel.
 *   2. CERRADO Y NO PUBLICADO NO CUENTA — el caso que motivó el arreglo: con la
 *      publicación del último bimestre en el futuro, debe devolver el ANTERIOR.
 *      Antes del fix la nómina mostraba el puesto y el nombre del bimestre que
 *      /docente/orden-merito le ocultaba.
 *   3. PUBLICACIÓN ESCALONADA POR NIVEL: si primaria ya venció y secundaria no,
 *      cada nivel devuelve un bimestre DISTINTO. Es la razón de que la respuesta
 *      sea por nivel y no global.
 *   4. SUSPENDIDA / DESPUBLICADA no cuentan (hereda el criterio de
 *      `nivelesPublicados`, que este método reutiliza en vez de reescribir).
 *   5. El ROLLBACK dejó `periodos_publicacion` como estaba.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH',  ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

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

$pdo   = Core\Database::connect();
$model = new App\Models\PublicacionBoletaModel();

$fallos = 0;
$ok = function (bool $cond, string $etiqueta, string $detalle = '') use (&$fallos): void {
    printf("  %-5s %-52s %s\n", $cond ? 'OK' : '***', $etiqueta, $detalle);
    if (!$cond) { $fallos++; }
};

$anioId = (int) $pdo->query("SELECT id FROM anios_academicos WHERE estado='activo' ORDER BY anio DESC LIMIT 1")->fetchColumn();
$niveles = $pdo->query("SELECT id, nombre FROM niveles ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);

/** Pinta el mapa nivel → bimestre que vería la nómina. */
$mostrar = function (array $mapa) use ($niveles): string {
    if (!$mapa) { return '(ningun nivel con merito publicado)'; }
    $out = [];
    foreach ($mapa as $nid => $p) {
        $out[] = ($niveles[$nid] ?? "nivel {$nid}") . ' → ' . $p['nombre_display'];
    }
    return implode(' · ', $out);
};

echo "== 1. Estado REAL de hoy ==\n";
$hoy = $model->ultimoPeriodoPublicadoPorNivel($anioId);
printf("  %s\n\n", $mostrar($hoy));

$filasPrevias = (int) $pdo->query("SELECT COUNT(*) FROM periodos_publicacion")->fetchColumn();
$pdo->beginTransaction();

// Referencias: los dos ultimos bimestres cerrados del anio.
$cerrados = $pdo->query("
    SELECT id, numero, nombre_display FROM periodos
    WHERE anio_id = {$anioId} AND estado = 'cerrado'
    ORDER BY numero DESC
")->fetchAll();

if (count($cerrados) < 2) {
    echo "  -- Hacen falta 2 bimestres cerrados para los escenarios; hay " . count($cerrados) . ".\n";
    $pdo->rollBack();
    exit(0);
}
$ultimo   = $cerrados[0];
$anterior = $cerrados[1];
printf("  (ultimo cerrado = %s · anterior = %s)\n\n", $ultimo['nombre_display'], $anterior['nombre_display']);

$fijar = function (int $periodoId, string $publicaEn, ?string $sello) use ($pdo): void {
    $pdo->prepare("
        UPDATE periodos_publicacion
           SET publica_en = ?, primera_publicacion_en = ?,
               suspendida_en = NULL, despublicada_en = NULL
         WHERE periodo_id = ?
    ")->execute([$publicaEn, $sello, $periodoId]);
};

// El anterior, publicado de verdad; el ultimo, aun sin publicar.
$fijar((int) $anterior['id'], date('Y-m-d H:i:s', time() - 864000), date('Y-m-d H:i:s', time() - 864000));

echo "== 2. Cerrado y NO publicado no cuenta ==\n";
$fijar((int) $ultimo['id'], date('Y-m-d H:i:s', time() + 86400), null);
$mapa = $model->ultimoPeriodoPublicadoPorNivel($anioId);
$todosAlAnterior = $mapa !== [] && !array_filter(
    $mapa,
    static fn(array $p): bool => (int) $p['id'] !== (int) $anterior['id']
);
$ok($todosAlAnterior, 'con el ultimo sin publicar, devuelve el ANTERIOR', $mostrar($mapa));

echo "\n== 3. Publicacion escalonada por NIVEL ==\n";
$nivelIds = array_keys($niveles);
if (count($nivelIds) >= 2) {
    // Primaria (primer nivel) ya vencio; el resto sigue programado.
    $pdo->prepare("
        UPDATE periodos_publicacion SET publica_en = ?, primera_publicacion_en = ?
         WHERE periodo_id = ? AND nivel_id = ?
    ")->execute([date('Y-m-d H:i:s', time() - 3600), date('Y-m-d H:i:s', time() - 3600),
                 (int) $ultimo['id'], $nivelIds[0]]);

    $mapa = $model->ultimoPeriodoPublicadoPorNivel($anioId);
    $ok(($mapa[$nivelIds[0]]['id'] ?? null) === (int) $ultimo['id']
        && ($mapa[$nivelIds[1]]['id'] ?? null) === (int) $anterior['id'],
        'cada nivel devuelve SU bimestre, no uno global', $mostrar($mapa));
} else {
    echo "  -- Hace falta mas de un nivel.\n";
}

echo "\n== 4. Suspendida y despublicada no cuentan ==\n";
$pdo->prepare("UPDATE periodos_publicacion SET suspendida_en = NOW() WHERE periodo_id = ?")
    ->execute([(int) $ultimo['id']]);
$mapa = $model->ultimoPeriodoPublicadoPorNivel($anioId);
$sinUltimo = !array_filter($mapa, static fn(array $p): bool => (int) $p['id'] === (int) $ultimo['id']);
$ok($sinUltimo, 'una publicacion SUSPENDIDA deja de contar', $mostrar($mapa));

$pdo->prepare("UPDATE periodos_publicacion SET suspendida_en = NULL, despublicada_en = NOW() WHERE periodo_id = ?")
    ->execute([(int) $ultimo['id']]);
$mapa = $model->ultimoPeriodoPublicadoPorNivel($anioId);
$sinUltimo = !array_filter($mapa, static fn(array $p): bool => (int) $p['id'] === (int) $ultimo['id']);
$ok($sinUltimo, 'una publicacion DESPUBLICADA deja de contar', $mostrar($mapa));

$pdo->rollBack();

echo "\n== 5. Rollback ==\n";
$filasAhora = (int) $pdo->query("SELECT COUNT(*) FROM periodos_publicacion")->fetchColumn();
$vuelta     = $model->ultimoPeriodoPublicadoPorNivel($anioId);
$ok($filasAhora === $filasPrevias, 'periodos_publicacion sin filas de mas ni de menos',
    "{$filasAhora} (antes {$filasPrevias})");
$ok($vuelta == $hoy, 'el mapa por nivel volvio al estado inicial', $mostrar($vuelta));

echo "\n" . ($fallos === 0 ? "RESULTADO: OK\n" : "RESULTADO: {$fallos} FALLO(S)\n");
exit($fallos === 0 ? 0 : 1);
