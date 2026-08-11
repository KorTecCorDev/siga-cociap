<?php

/**
 * Verificación — el roster del snapshot solo lo mueve un traslado/retiro, nunca
 * una rectificación de notas.
 * Uso: php database/verificaciones/verif_roster_snapshot_traslado.php
 *
 * ESCRIBE sobre `matriculas` y `orden_merito_snapshot` para recorrer los
 * escenarios, pero TODO corre en una TRANSACCIÓN con ROLLBACK y el paso final
 * comprueba que el estado volvió. Lleva el guard de secretos: NO en producción.
 *
 * POR QUÉ EXISTE (11/08/2026). `escribirOficial` borra y reinserta el periodo
 * ENTERO, así que cualquier regeneración arrastraba los cambios de roster
 * acumulados desde el cierre. Pasó de verdad: al rectificar tres notas de
 * Educación Física de 4.º de PRIMARIA desapareció del oficial de B2 una alumna
 * de 1.º de SECUNDARIA (trasladada 38 min después del cierre) y 42 compañeros
 * cambiaron de puesto, sin un solo aviso.
 *
 * QUÉ COMPRUEBA:
 *   1. Una rectificación con el roster intacto SÍ regenera ('oficial').
 *   2. Con el roster cambiado NO regenera: devuelve 'roster_cambiado' y el
 *      snapshot queda byte a byte como estaba.
 *   3. `sincronizarRosterPorMatricula` SÍ saca al trasladado y renumera, e
 *      informa del puesto que ocupaba y de cuántos compañeros se movieron.
 *   4. La reversión (vuelve a continuador) lo REINTEGRA en su puesto.
 *   5. Un periodo YA PUBLICADO no se toca (candado 046) — es lo que protege a B1.
 *   6. El ROLLBACK dejó todo como estaba.
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

$pdo = Core\Database::connect();
$om  = new App\Models\OrdenMeritoModel();
$pub = new App\Models\PublicacionBoletaModel();

$fallos = 0;
$ok = function (bool $cond, string $etiqueta, string $detalle = '') use (&$fallos): void {
    printf("  %-5s %-56s %s\n", $cond ? 'OK' : '***', $etiqueta, $detalle);
    if (!$cond) { $fallos++; }
};

/** Firma del snapshot de un periodo: filas + puestos, para detectar cualquier cambio. */
$firma = function (int $periodoId) use ($pdo): string {
    $st = $pdo->prepare("SELECT matricula_id, puesto_grado, puesto_seccion
                         FROM orden_merito_snapshot WHERE periodo_id = ?
                         ORDER BY matricula_id");
    $st->execute([$periodoId]);
    return md5(json_encode($st->fetchAll(PDO::FETCH_NUM)));
};
$cuenta = function (int $periodoId) use ($pdo): int {
    $st = $pdo->prepare("SELECT COUNT(*) FROM orden_merito_snapshot WHERE periodo_id = ?");
    $st->execute([$periodoId]);
    return (int) $st->fetchColumn();
};

// ── Elegir un periodo cerrado, con snapshot y NO publicado ──────────────────
$candidato = null;
foreach ($pdo->query("SELECT p.id, p.numero, p.nombre_display FROM periodos p
                      WHERE p.estado = 'cerrado'
                        AND EXISTS (SELECT 1 FROM orden_merito_snapshot s WHERE s.periodo_id = p.id)
                      ORDER BY p.numero DESC")->fetchAll(PDO::FETCH_ASSOC) as $p) {
    if (!$pub->fuePublicado((int) $p['id'])) { $candidato = $p; break; }
}

if (!$candidato) {
    echo "SIN ESCENARIO: no hay ningún bimestre cerrado, con snapshot y sin publicar.\n";
    echo "(Es el estado normal una vez publicado todo. Nada que verificar.)\n";
    exit(0);
}
$periodoId = (int) $candidato['id'];
printf("Periodo de prueba: %s (id %d) — %d filas de snapshot\n\n",
    $candidato['nombre_display'], $periodoId, $cuenta($periodoId));

// Cobaya: un alumno con puesto intermedio, para que su salida mueva a otros.
$cobaya = $pdo->query("SELECT s.matricula_id, s.puesto_grado, s.grado_id
                       FROM orden_merito_snapshot s
                       INNER JOIN matriculas m ON m.id = s.matricula_id
                       WHERE s.periodo_id = $periodoId
                         AND m.tipo IN ('continuador','nuevo')
                         AND s.puesto_grado > 3
                       ORDER BY s.puesto_grado LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$cobaya) {
    echo "SIN ESCENARIO: no hay una matrícula apta para la prueba.\n";
    exit(0);
}
$matriculaId = (int) $cobaya['matricula_id'];
$puestoOrig  = (int) $cobaya['puesto_grado'];
printf("Matrícula de prueba: #%d (puesto %d° de su grado)\n\n", $matriculaId, $puestoOrig);

$firmaInicial  = $firma($periodoId);
$cuentaInicial = $cuenta($periodoId);
$tipoOriginal  = $pdo->query("SELECT tipo FROM matriculas WHERE id = $matriculaId")->fetchColumn();

$pdo->beginTransaction();

try {
    // ── 1. Rectificación con el roster INTACTO → sí regenera ────────────────
    echo "1) Rectificación con el roster intacto\n";
    $tipo = $om->registrarRanking($periodoId, null, 'verificación', true);
    $ok($tipo === 'oficial', 'registrarRanking devuelve "oficial"', "devolvió '$tipo'");
    $ok($firma($periodoId) === $firmaInicial,
        'el snapshot se regeneró idéntico', 'sin cambios de roster no debe moverse nada');

    // ── 2. Rectificación con el roster CAMBIADO → NO regenera ───────────────
    echo "\n2) Rectificación con un traslado sin sincronizar (la regresión real)\n";
    $pdo->prepare("UPDATE matriculas SET tipo = 'trasladado', tipo_anterior = ? WHERE id = ?")
        ->execute([$tipoOriginal, $matriculaId]);

    $firmaAntes = $firma($periodoId);
    $tipo = $om->registrarRanking($periodoId, null, 'verificación', true);
    $ok($tipo === 'roster_cambiado', 'registrarRanking ABORTA con "roster_cambiado"', "devolvió '$tipo'");
    $ok($firma($periodoId) === $firmaAntes,
        'el snapshot NO se tocó', 'una rectificación no puede cambiar quién pertenece');
    $ok($cuenta($periodoId) === $cuentaInicial,
        'sigue con las mismas filas', $cuenta($periodoId) . " de $cuentaInicial");

    // ── 3. La sincronización SÍ lo saca y renumera ──────────────────────────
    echo "\n3) sincronizarRosterPorMatricula tras el traslado\n";
    $efectos = $om->sincronizarRosterPorMatricula($matriculaId, null);
    $ok(count($efectos) === 1, 'informa exactamente 1 efecto', count($efectos) . ' efecto(s)');
    $ok(($efectos[0]['accion'] ?? '') === 'salio', 'la acción es "salio"', $efectos[0]['accion'] ?? '-');
    $ok((int) ($efectos[0]['puesto'] ?? 0) === $puestoOrig,
        'informa el puesto que ocupaba', ($efectos[0]['puesto'] ?? '-') . " (esperado $puestoOrig)");
    $ok($cuenta($periodoId) === $cuentaInicial - 1,
        'el snapshot pierde exactamente una fila', $cuenta($periodoId) . " de $cuentaInicial");

    $sigue = $pdo->query("SELECT COUNT(*) FROM orden_merito_snapshot
                          WHERE periodo_id = $periodoId AND matricula_id = $matriculaId")->fetchColumn();
    $ok((int) $sigue === 0, 'el trasladado ya no está en el snapshot');
    printf("        (mensaje al usuario: \"%s\")\n", trim(
        App\Models\OrdenMeritoModel::describirEfectosRoster($efectos)));

    // Sin huecos de puesto en el grado afectado.
    $gradoId = (int) $cobaya['grado_id'];
    $seq = $pdo->query("SELECT COUNT(*) n, MAX(puesto_grado) mx FROM orden_merito_snapshot
                        WHERE periodo_id = $periodoId AND grado_id = $gradoId")->fetch(PDO::FETCH_ASSOC);
    $ok((int) $seq['n'] === (int) $seq['mx'],
        'los puestos del grado quedan sin huecos', "{$seq['n']} filas, puesto máx {$seq['mx']}");

    // ── 4. La reversión lo REINTEGRA ────────────────────────────────────────
    echo "\n4) Reversión: vuelve a continuador\n";
    $pdo->prepare("UPDATE matriculas SET tipo = ?, tipo_anterior = NULL WHERE id = ?")
        ->execute([$tipoOriginal, $matriculaId]);

    $efectos = $om->sincronizarRosterPorMatricula($matriculaId, null);
    $ok(count($efectos) === 1 && ($efectos[0]['accion'] ?? '') === 'reintegrado',
        'informa "reintegrado"', $efectos[0]['accion'] ?? '-');
    $ok($cuenta($periodoId) === $cuentaInicial,
        'el snapshot recupera la fila', $cuenta($periodoId) . " de $cuentaInicial");
    $ok($firma($periodoId) === $firmaInicial,
        'el snapshot vuelve a ser IDÉNTICO al original',
        'la reversión tiene que deshacer de verdad');

    // ── 5. Un periodo YA PUBLICADO no se toca ───────────────────────────────
    echo "\n5) Un bimestre ya publicado es intocable (candado 046)\n";
    $publicado = null;
    foreach ($pdo->query("SELECT p.id, p.nombre_display FROM periodos p
                          WHERE EXISTS (SELECT 1 FROM orden_merito_snapshot s WHERE s.periodo_id = p.id)
                          ")->fetchAll(PDO::FETCH_ASSOC) as $p) {
        if ($pub->fuePublicado((int) $p['id'])) { $publicado = $p; break; }
    }
    if ($publicado) {
        $pid = (int) $publicado['id'];
        $firmaPub = $firma($pid);
        $mat = $pdo->query("SELECT matricula_id FROM orden_merito_snapshot
                            WHERE periodo_id = $pid ORDER BY puesto_grado DESC LIMIT 1")->fetchColumn();
        $pdo->prepare("UPDATE matriculas SET tipo = 'trasladado' WHERE id = ?")->execute([$mat]);
        $om->sincronizarRosterPorMatricula((int) $mat, null);
        $ok($firma($pid) === $firmaPub,
            $publicado['nombre_display'] . ' quedó intacto', 'es lo que protege a B1');
    } else {
        echo "  --    (no hay ningún periodo publicado con snapshot en esta base)\n";
    }

    $pdo->rollBack();
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nEXCEPCION: " . $e->getMessage() . "\n");
    exit(1);
}

// ── 6. El rollback dejó todo como estaba ────────────────────────────────────
echo "\n6) Estado tras el ROLLBACK\n";
$ok($firma($periodoId) === $firmaInicial, 'el snapshot volvió a su firma original');
$ok($cuenta($periodoId) === $cuentaInicial, 'volvió al número de filas original');
$ok($pdo->query("SELECT tipo FROM matriculas WHERE id = $matriculaId")->fetchColumn() === $tipoOriginal,
    'la matrícula volvió a su tipo original');

echo "\n", $fallos === 0 ? "TODO OK\n" : "*** $fallos COMPROBACION(ES) FALLIDA(S)\n";
exit($fallos === 0 ? 0 : 1);
