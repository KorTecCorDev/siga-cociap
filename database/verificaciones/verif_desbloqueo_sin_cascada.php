<?php

/**
 * Verificación — desbloquear una competencia ACADÉMICA no arrastra las TRANSVERSALES.
 * Uso: php database/verificaciones/verif_desbloqueo_sin_cascada.php
 *
 * 🔴 ESTE SCRIPT ESCRIBE, pero TODO corre dentro de una TRANSACCIÓN con ROLLBACK:
 * reproduce el desbloqueo real del director y comprueba el estado resultante. Por eso
 * lleva el guard de secretos de PRODUCCIÓN y aborta si los detecta.
 *
 * QUÉ CAMBIÓ (07/08/2026)
 *   `BloqueoController::desbloquear` hacía TRES cosas: quitaba el bloqueo pedido,
 *   borraba los bloqueos TIC/GAMA de la carga (`liberarTransversalesDeCarga`) y anulaba
 *   el cierre del tutor. La segunda se retiró: su motivo —que las transversales no eran
 *   filas del panel y quedarían inalcanzables— murió el 06/08 con el desbloqueo granular
 *   por competencia. Mantenerla obligaba al docente a re-aprobar TIC/GAMA que nadie tocó
 *   y, sobre todo, bajaba el numerador de `estadoCargasSeccion`, con lo que el TUTOR no
 *   podía re-cerrar hasta que el docente actuara.
 *
 * QUÉ COMPRUEBA
 *   1. Los bloqueos TRANSVERSALES de la carga quedan INTACTOS.
 *   2. El gate del tutor (`estadoCargasSeccion`) NO se mueve → puede re-cerrar solo.
 *   3. El cierre del tutor SÍ queda anulado (decisión: la conclusión descriptiva puede
 *      dejar de ser precisa aunque el promedio no cambie).
 *   4. Solo desaparece la competencia pedida; las demás académicas de la carga siguen.
 *   5. Re-bloquear funciona y NO duplica (protege `uq_bloqueo`).
 *   6. El ROLLBACK deja todo como estaba.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH',  ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Guarda: nunca contra produccion (este script ESCRIBE, aunque haga rollback).
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

$pdo   = Core\Database::connect();
$cal   = new App\Models\CalificacionModel();
$trans = new App\Models\TransversalModel();
$fallos = 0;

$ok = function (bool $cond, string $msg, string $detalle = '') use (&$fallos): void {
    echo($cond ? "  OK    " : "  FALLO ") . str_pad($msg, 62) . $detalle . "\n";
    if (!$cond) { $fallos++; }
};

$contarTransversales = function (int $cargaId, int $periodoId) use ($pdo): int {
    $st = $pdo->prepare("
        SELECT COUNT(*) FROM bloqueos_competencia bc
        JOIN competencias c ON c.id = bc.competencia_id
        JOIN areas a ON a.id = c.area_id AND a.tipo = 'transversal'
        WHERE bc.carga_id = ? AND bc.periodo_id = ?
    ");
    $st->execute([$cargaId, $periodoId]);
    return (int) $st->fetchColumn();
};

$contarAcademicas = function (int $cargaId, int $periodoId) use ($pdo): int {
    $st = $pdo->prepare("
        SELECT COUNT(*) FROM bloqueos_competencia bc
        JOIN competencias c ON c.id = bc.competencia_id
        JOIN areas a ON a.id = c.area_id AND a.tipo <> 'transversal'
        WHERE bc.carga_id = ? AND bc.periodo_id = ?
    ");
    $st->execute([$cargaId, $periodoId]);
    return (int) $st->fetchColumn();
};

// ── Elegir un caso real: carga con academicas Y transversales bloqueadas,
//    en una seccion con cierre transversal VIGENTE, en un periodo 'activo'.
$caso = $pdo->query("
    SELECT bc.id AS bloqueo_id, bc.carga_id, bc.periodo_id, bc.competencia_id,
           ca.seccion_id, comp.nombre_corto AS competencia
    FROM bloqueos_competencia bc
    JOIN competencias comp ON comp.id = bc.competencia_id
    JOIN areas a           ON a.id = comp.area_id AND a.tipo <> 'transversal'
    JOIN cargas_academicas ca ON ca.id = bc.carga_id AND ca.estado = 'activa'
    JOIN periodos p ON p.id = bc.periodo_id AND p.estado = 'activo'
    WHERE EXISTS (SELECT 1 FROM bloqueos_competencia b2
                  JOIN competencias c2 ON c2.id = b2.competencia_id
                  JOIN areas a2 ON a2.id = c2.area_id AND a2.tipo = 'transversal'
                  WHERE b2.carga_id = bc.carga_id AND b2.periodo_id = bc.periodo_id)
      AND EXISTS (SELECT 1 FROM cierres_transversales ct
                  WHERE ct.seccion_id = ca.seccion_id AND ct.periodo_id = bc.periodo_id
                    AND ct.anulado_en IS NULL)
    ORDER BY bc.id LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if (!$caso) {
    echo "SIN CASO APLICABLE: no hay carga con academicas y transversales bloqueadas\n"
       . "en una seccion con cierre vigente y bimestre activo. Nada que verificar.\n";
    exit(0);
}

$cargaId   = (int) $caso['carga_id'];
$periodoId = (int) $caso['periodo_id'];
$seccionId = (int) $caso['seccion_id'];

echo "=== Verificacion: desbloqueo academico SIN cascada transversal ===\n";
echo "Caso: carga {$cargaId} · seccion {$seccionId} · periodo {$periodoId}\n";
echo "Competencia a desbloquear: {$caso['competencia']} (bloqueo {$caso['bloqueo_id']})\n\n";

// ── ESTADO PREVIO
$transAntes = $contarTransversales($cargaId, $periodoId);
$acadAntes  = $contarAcademicas($cargaId, $periodoId);
$gateAntes  = $trans->estadoCargasSeccion($seccionId, $periodoId);
$cierreAntes = $trans->getCierreVigente($seccionId, $periodoId);

printf("ANTES  transversales_bloqueadas=%d  academicas_bloqueadas=%d  gate=%d/%d  cierre_vigente=%s\n\n",
    $transAntes, $acadAntes, $gateAntes['bloqueadas'], $gateAntes['total'],
    $cierreAntes ? 'si' : 'no');

$pdo->beginTransaction();

try {
    // ── Reproduce EXACTAMENTE lo que hace hoy BloqueoController::desbloquear
    $cal->desbloquearCompetencia((int) $caso['bloqueo_id']);
    $trans->anularCierreVigente($seccionId, $periodoId, 1, 'VERIFICACION (rollback)');

    $transDespues = $contarTransversales($cargaId, $periodoId);
    $acadDespues  = $contarAcademicas($cargaId, $periodoId);
    $gateDespues  = $trans->estadoCargasSeccion($seccionId, $periodoId);
    $cierreDespues = $trans->getCierreVigente($seccionId, $periodoId);

    echo "--- 1. Las TRANSVERSALES de la carga quedan intactas ---\n";
    $ok($transDespues === $transAntes,
        'bloqueos transversales sin cambios',
        "antes={$transAntes} despues={$transDespues}");

    echo "\n--- 2. El gate del tutor NO se mueve (puede re-cerrar solo) ---\n";
    $ok($gateDespues['total'] === $gateAntes['total']
        && $gateDespues['bloqueadas'] === $gateAntes['bloqueadas'],
        'estadoCargasSeccion identico',
        "antes={$gateAntes['bloqueadas']}/{$gateAntes['total']} "
        . "despues={$gateDespues['bloqueadas']}/{$gateDespues['total']}");
    $ok($gateDespues['bloqueadas'] === $gateDespues['total'],
        'el gate sigue satisfecho -> el tutor puede cerrar',
        "{$gateDespues['bloqueadas']}/{$gateDespues['total']}");

    echo "\n--- 3. El cierre del tutor SI queda anulado (decision del usuario) ---\n";
    $ok($cierreAntes !== null && $cierreDespues === null,
        'cierre vigente anulado');

    echo "\n--- 4. Solo desaparece la competencia pedida ---\n";
    $ok($acadDespues === $acadAntes - 1,
        'exactamente una academica menos',
        "antes={$acadAntes} despues={$acadDespues}");
    $ok(!$cal->competenciaBloqueada($cargaId, (int) $caso['competencia_id'], $periodoId),
        'la competencia pedida quedo editable para el docente');

    echo "\n--- 5. Re-bloquear funciona y no duplica (uq_bloqueo) ---\n";
    $cal->bloquearCompetencia($cargaId, (int) $caso['competencia_id'], $periodoId, 1);
    $cal->bloquearCompetencia($cargaId, (int) $caso['competencia_id'], $periodoId, 1); // 2.a vez
    $st = $pdo->prepare("SELECT COUNT(*) FROM bloqueos_competencia
                         WHERE carga_id=? AND competencia_id=? AND periodo_id=?");
    $st->execute([$cargaId, (int) $caso['competencia_id'], $periodoId]);
    $ok((int) $st->fetchColumn() === 1,
        'tras dos re-bloqueos hay UNA sola fila');
    $ok($contarTransversales($cargaId, $periodoId) === $transAntes,
        'las transversales siguen igual tras el re-bloqueo',
        "={$transAntes}");

    $pdo->rollBack();
} catch (\Throwable $e) {
    $pdo->rollBack();
    echo "\nEXCEPCION: " . $e->getMessage() . "\n";
    $fallos++;
}

// ── 6. El rollback restauro todo
echo "\n--- 6. El ROLLBACK dejo la BD como estaba ---\n";
$gateFinal = $trans->estadoCargasSeccion($seccionId, $periodoId);
$ok($contarTransversales($cargaId, $periodoId) === $transAntes, 'transversales restauradas');
$ok($contarAcademicas($cargaId, $periodoId) === $acadAntes, 'academicas restauradas');
$ok($trans->getCierreVigente($seccionId, $periodoId) !== null, 'cierre del tutor restaurado');
$ok($gateFinal['bloqueadas'] === $gateAntes['bloqueadas']
    && $gateFinal['total'] === $gateAntes['total'], 'gate restaurado');

// ── 7. CONTRASTE: el comportamiento VIEJO sí rompía el gate del tutor.
//    Reproduce la cascada retirada (`liberarTransversalesDeCarga`) en otra
//    transacción con ROLLBACK. Si este bloque dejara de mostrar la caída, sería
//    que la cascada ya no hacía daño y el cambio perdería su justificación.
echo "\n--- 7. CONTRASTE: lo que hacia el comportamiento VIEJO (con cascada) ---\n";
$pdo->beginTransaction();
try {
    $cal->desbloquearCompetencia((int) $caso['bloqueo_id']);
    $liberadas = $trans->liberarTransversalesDeCarga($cargaId, $periodoId);
    $trans->anularCierreVigente($seccionId, $periodoId, 1, 'VERIFICACION (rollback)');

    $gateViejo  = $trans->estadoCargasSeccion($seccionId, $periodoId);
    $transViejo = $contarTransversales($cargaId, $periodoId);

    $ok($liberadas === $transAntes,
        'la cascada borraba las transversales de la carga',
        "borradas={$liberadas}");
    $ok($transViejo === 0,
        'la carga quedaba con CERO transversales bloqueadas');
    $ok($gateViejo['bloqueadas'] < $gateAntes['bloqueadas'],
        'y el gate del tutor CAIA -> no podia re-cerrar',
        "antes={$gateAntes['bloqueadas']}/{$gateAntes['total']} "
        . "viejo={$gateViejo['bloqueadas']}/{$gateViejo['total']}");

    $pdo->rollBack();
} catch (\Throwable $e) {
    $pdo->rollBack();
    echo "  EXCEPCION en el contraste: " . $e->getMessage() . "\n";
    $fallos++;
}
$ok($contarTransversales($cargaId, $periodoId) === $transAntes,
    'rollback del contraste restauro las transversales');

echo "\n=== RESULTADO: " . ($fallos === 0 ? 'TODO OK' : "{$fallos} FALLO(S)") . " ===\n";
exit($fallos === 0 ? 0 : 1);
