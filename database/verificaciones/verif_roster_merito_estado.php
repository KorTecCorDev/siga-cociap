<?php

/**
 * Verificación — al orden de mérito solo entran las matrículas APROBADAS
 * (regla del 12/08/2026), y la excepción del retorno revertido se respeta.
 * Uso: php database/verificaciones/verif_roster_merito_estado.php
 *
 * ESCRIBE sobre `matriculas` y `orden_merito_snapshot` para recorrer los
 * escenarios, pero TODO corre en una TRANSACCIÓN con ROLLBACK y el paso final
 * comprueba que el estado volvió. Lleva el guard de secretos: NO en producción.
 *
 * POR QUÉ EXISTE. La regla cambió el criterio del roster de `tipo` a
 * `estado='aprobada'` (+ la excepción del retorno revertido), y eso toca a la
 * vez el cálculo en vivo, el snapshot y tres actos de matrícula. El 11/08 se
 * desplegó una guarda de roster que bloqueaba justo el caso que debía permitir
 * y ninguna rectificación de B1 se registraba, en silencio: el verificador de
 * entonces estaba en verde porque probaba la función vecina. Por eso aquí cada
 * regla se prueba en SUS DOS RAMAS — que excluya a quien debe excluir Y que
 * siga dejando pasar a quien debe competir.
 *
 * QUÉ COMPRUEBA:
 *   1. Una matrícula APROBADA compite (rama "deja pasar").
 *   2. Degradada a 'pendiente' deja de competir (rama "bloquea").
 *   3. `sincronizarRosterPorMatricula` la SACA del snapshot cerrado no publicado,
 *      renumera e informa del puesto y del arrastre.
 *   4. Al volver a 'aprobada' la REINTEGRA y la firma del snapshot vuelve a ser
 *      la original (reversión simétrica, decisión del usuario).
 *   5. 'desactivado' tampoco compite, y su reversión también es simétrica.
 *   6. EXCEPCIÓN: la operativa de un retorno REVERTIDO (que queda 'desactivado')
 *      SÍ sigue compitiendo en los bimestres que cursó, y su oficial sigue fuera.
 *   7. Un periodo YA PUBLICADO no se toca (candado 046) — es lo que protege a B1.
 *   8. El ROLLBACK dejó todo como estaba.
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
    printf("  %-5s %-58s %s\n", $cond ? 'OK' : '***', $etiqueta, $detalle);
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

// El motor real, sin pasar por el snapshot: ¿esta matrícula compite hoy?
$calcular = new ReflectionMethod(App\Models\OrdenMeritoModel::class, 'calcularFilasRanking');
$calcular->setAccessible(true);
$compite = function (int $periodoId, int $matriculaId) use ($calcular, $om): bool {
    foreach ($calcular->invoke($om, $periodoId) as $f) {
        if ((int) $f['matricula_id'] === $matriculaId) { return true; }
    }
    return false;
};
$estadoA = function (int $matriculaId, string $estado) use ($pdo): void {
    $pdo->prepare("UPDATE matriculas SET estado = ? WHERE id = ?")->execute([$estado, $matriculaId]);
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

// Cobaya: una matrícula APROBADA con puesto intermedio, para que su salida
// mueva a otros y el arrastre sea observable.
$cobaya = $pdo->query("SELECT s.matricula_id, s.puesto_grado
                       FROM orden_merito_snapshot s
                       INNER JOIN matriculas m ON m.id = s.matricula_id
                       WHERE s.periodo_id = $periodoId
                         AND m.estado = 'aprobada'
                         AND m.tipo IN ('continuador','nuevo')
                         AND s.puesto_grado > 3
                       ORDER BY s.puesto_grado LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$cobaya) {
    echo "SIN ESCENARIO: no hay una matrícula aprobada apta para la prueba.\n";
    exit(0);
}
$matriculaId = (int) $cobaya['matricula_id'];
$puestoOrig  = (int) $cobaya['puesto_grado'];

printf("Periodo de prueba : %s (id %d) — %d filas de snapshot\n",
    $candidato['nombre_display'], $periodoId, $cuenta($periodoId));
printf("Matrícula cobaya  : #%d (puesto %d° de su grado)\n\n", $matriculaId, $puestoOrig);

// Periodo PUBLICADO de control (si lo hay): no debe moverse en toda la prueba.
$publicado = null;
foreach ($pdo->query("SELECT p.id, p.nombre_display FROM periodos p
                      WHERE p.estado = 'cerrado'
                        AND EXISTS (SELECT 1 FROM orden_merito_snapshot s WHERE s.periodo_id = p.id)
                      ORDER BY p.numero")->fetchAll(PDO::FETCH_ASSOC) as $p) {
    if ($pub->fuePublicado((int) $p['id'])) { $publicado = $p; break; }
}
$firmaPublicado = $publicado ? $firma((int) $publicado['id']) : null;

// Estado REAL en disco antes de tocar nada: es contra esto que se comprueba el
// ROLLBACK (la línea base del paso 0 es una normalización interna a la prueba).
$firmaReal  = $firma($periodoId);
$cuentaReal = $cuenta($periodoId);

$pdo->beginTransaction();
try {
    // ── 0. Línea base: snapshot reconciliado con el motor ───────────────────
    // `escribirOficial` reescribe el periodo ENTERO, así que si el snapshot
    // arrastra divergencias previas (las que resuelve
    // `database/sincronizar_roster_snapshot.php`) la primera sincronización las
    // aplicaría de paso y ninguna firma volvería a cuadrar. Se normaliza aquí
    // DENTRO de la transacción para medir el efecto de cada acto, no el estado
    // pendiente del entorno. Con el snapshot ya reconciliado esto no cambia nada.
    $escribir = new ReflectionMethod(App\Models\OrdenMeritoModel::class, 'escribirOficial');
    $escribir->setAccessible(true);
    $antesDeNormalizar = $cuenta($periodoId);
    $escribir->invoke($om, $periodoId, $calcular->invoke($om, $periodoId), null);

    $firmaInicial  = $firma($periodoId);
    $cuentaInicial = $cuenta($periodoId);
    printf("0. Línea base normalizada: %d filas%s\n\n", $cuentaInicial,
        $antesDeNormalizar === $cuentaInicial
            ? ' (el snapshot ya estaba reconciliado)'
            : sprintf(' — el snapshot guardado tenía %d: hay %d divergencia(s) sin reconciliar',
                $antesDeNormalizar, abs($antesDeNormalizar - $cuentaInicial)));

    // El puesto de la cobaya puede haberse movido al normalizar.
    $st = $pdo->prepare("SELECT puesto_grado FROM orden_merito_snapshot
                         WHERE periodo_id = ? AND matricula_id = ?");
    $st->execute([$periodoId, $matriculaId]);
    $puestoOrig = (int) $st->fetchColumn();

    // ── 1. Rama "deja pasar": una matrícula aprobada compite ────────────────
    echo "1. Matrícula APROBADA (rama que debe DEJAR PASAR)\n";
    $ok($compite($periodoId, $matriculaId), 'la aprobada compite en el cálculo en vivo');

    // ── 2. Rama "bloquea": pendiente fuera ──────────────────────────────────
    echo "\n2. Degradada a PENDIENTE (rama que debe BLOQUEAR)\n";
    $estadoA($matriculaId, 'pendiente');
    $ok(!$compite($periodoId, $matriculaId), 'la pendiente ya NO compite');

    // ── 3. El snapshot la suelta, renumerando ───────────────────────────────
    echo "\n3. Sincronización del snapshot\n";
    $efectos = $om->sincronizarRosterPorMatricula($matriculaId, null);
    $ok(count($efectos) === 1, 'informa 1 efecto', count($efectos) . ' efecto(s)');
    $ok(($efectos[0]['accion'] ?? '') === 'salio', "el efecto es 'salio'");
    $ok((int) ($efectos[0]['puesto'] ?? 0) === $puestoOrig,
        'informa el puesto que ocupaba', $puestoOrig . '°');
    $ok((int) ($efectos[0]['companeros'] ?? -1) > 0,
        'informa el arrastre de compañeros', ($efectos[0]['companeros'] ?? '?') . ' movidos');
    $ok($cuenta($periodoId) === $cuentaInicial - 1,
        'el snapshot tiene una fila menos', $cuenta($periodoId) . ' filas');
    $huecos = (int) $pdo->query("SELECT COUNT(*) FROM (
            SELECT grado_id, COUNT(*) n, MAX(puesto_grado) m
            FROM orden_merito_snapshot WHERE periodo_id = $periodoId
            GROUP BY grado_id HAVING m > n) x")->fetchColumn();
    $ok($huecos === 0, 'la renumeración no dejó huecos');

    // ── 4. Reversión simétrica al aprobar ───────────────────────────────────
    echo "\n4. Vuelve a APROBADA (reversión simétrica)\n";
    $estadoA($matriculaId, 'aprobada');
    $efectos = $om->sincronizarRosterPorMatricula($matriculaId, null);
    $ok(($efectos[0]['accion'] ?? '') === 'reintegrado', "el efecto es 'reintegrado'");
    $ok($cuenta($periodoId) === $cuentaInicial, 'el snapshot recuperó su tamaño');
    $ok($firma($periodoId) === $firmaInicial, 'la firma del snapshot volvió a ser la original');

    // ── 5. Desactivada tampoco compite ──────────────────────────────────────
    echo "\n5. DESACTIVADA (baja por deuda: tampoco compite)\n";
    $estadoA($matriculaId, 'desactivado');
    $ok(!$compite($periodoId, $matriculaId), 'la desactivada NO compite');
    $om->sincronizarRosterPorMatricula($matriculaId, null);
    $ok($cuenta($periodoId) === $cuentaInicial - 1, 'salió del snapshot');
    $estadoA($matriculaId, 'aprobada');
    $om->sincronizarRosterPorMatricula($matriculaId, null);
    $ok($firma($periodoId) === $firmaInicial, 'reintegrada, firma original de nuevo');

    // ── 6. EXCEPCIÓN: operativa de un retorno REVERTIDO ─────────────────────
    echo "\n6. Excepción — operativa de un retorno REVERTIDO\n";
    $retorno = $pdo->query("
        SELECT r.id, r.matricula_oficial_id, r.matricula_operativa_id
        FROM retornos_grado r
        WHERE EXISTS (SELECT 1 FROM calificaciones c
                      WHERE c.matricula_id = r.matricula_operativa_id
                        AND c.periodo_id   = $periodoId)
        LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if (!$retorno) {
        echo "  --    sin retornos con notas en este periodo: escenario no aplicable\n";
    } else {
        $operativaId = (int) $retorno['matricula_operativa_id'];
        $oficialId   = (int) $retorno['matricula_oficial_id'];

        $ok($compite($periodoId, $operativaId), 'la operativa competía antes de revertir');

        // Reproduce lo que hace RetornoGradoController::revertir.
        $pdo->exec("UPDATE retornos_grado SET estado = 'revertido' WHERE id = " . (int) $retorno['id']);
        $estadoA($operativaId, 'desactivado');

        $ok($compite($periodoId, $operativaId),
            'la operativa DESACTIVADA sigue compitiendo (excepción)');
        $ok(!$compite($periodoId, $oficialId),
            'la oficial sigue fuera (anclaje por bimestre intacto)');

        // Y la excepción es ESTRECHA: si además se la marca retirada, sale.
        $pdo->prepare("UPDATE matriculas SET tipo = 'retirado' WHERE id = ?")->execute([$operativaId]);
        $ok(!$compite($periodoId, $operativaId),
            'si esa misma operativa se marca RETIRADA, sale');
    }

    // ── 7. El periodo publicado, intacto ────────────────────────────────────
    echo "\n7. Candado 046\n";
    if ($publicado) {
        $ok($firma((int) $publicado['id']) === $firmaPublicado,
            'el periodo publicado no se tocó', $publicado['nombre_display']);
    } else {
        echo "  --    no hay ningún periodo publicado con snapshot\n";
    }

    $pdo->rollBack();
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    fwrite(STDERR, "\nERROR: " . $e->getMessage() . "\n");
    exit(1);
}

// ── 8. El ROLLBACK dejó todo como estaba ────────────────────────────────────
echo "\n8. Después del ROLLBACK\n";
$ok($firma($periodoId) === $firmaReal, 'el snapshot del periodo de prueba está intacto');
$ok($cuenta($periodoId) === $cuentaReal, 'mismo número de filas', $cuentaReal . ' filas');
if ($publicado) {
    $ok($firma((int) $publicado['id']) === $firmaPublicado, 'el periodo publicado está intacto');
}

echo "\n", str_repeat('-', 78), "\n";
echo $fallos === 0
    ? "TODO OK — el roster del orden de mérito exige matrícula aprobada.\n"
    : "FALLOS: {$fallos}\n";
exit($fallos === 0 ? 0 : 1);
