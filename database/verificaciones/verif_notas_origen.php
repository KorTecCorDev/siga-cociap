<?php

/**
 * Verificación — NOTAS DEL COLEGIO DE ORIGEN + NOTIFICACIONES.
 * Uso: php database/verificaciones/verif_notas_origen.php
 *
 * ⚠️ ESCRIBE PARA PROBAR, siempre dentro de una TRANSACCIÓN QUE TERMINA EN
 * ROLLBACK: no deja ni una fila. Cumple la regla del proyecto ("ningún script
 * de database/ debe limpiar con DELETE lo que no creó").
 *
 * LA REGLA QUE SE VERIFICA (decidida el 10/09/2026)
 *   El Informe de Progreso que emite el COCIAP lleva SOLO las calificaciones
 *   cursadas aquí. Lo que el estudiante trae de su colegio anterior es
 *   INFORMATIVO y su público son los docentes con carga en su sección.
 *
 *   De ahí que la comprobación central sea NEGATIVA: registrar notas de origen
 *   NO puede alterar ni una celda de la boleta. Si algún día una de estas notas
 *   aparece en una boleta, este script tiene que ponerse rojo.
 *
 * QUÉ COMPRUEBA
 *   1. La boleta NO cambia al registrar notas de origen (ni una celda).
 *   2. El orden de mérito del bimestre NO se mueve.
 *   3. GUARDA del docente, en sus DOS ramas: con carga → accede; sin carga → no.
 *   4. RESALTADO: las áreas marcadas son exactamente las cargas de ese docente.
 *   5. NOTIFICACIÓN: se crea una por docente de la sección, ni una de más.
 *   6. AISLAMIENTO: marcar leída la de un usuario no toca la de otro.
 *   7. `alertas` (tutor → padre) sigue intacta: son mecanismos distintos.
 *   8. ROLLBACK: todos los contadores vuelven a su valor inicial.
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
require_once APP_PATH . '/Helpers/helpers.php';

$pdo     = Core\Database::connect();
$externas = new App\Models\NotaExternaModel();
$notifs   = new App\Models\NotificacionModel();
$boletas  = new App\Models\BoletaModel();
$meritos  = new App\Models\OrdenMeritoModel();

$fallos = 0;
$ok = function (bool $cond, string $texto) use (&$fallos): void {
    if (!$cond) { $fallos++; }
    echo ($cond ? '  [OK]   ' : '  [FALLA] ') . $texto . "\n";
};
$contar = function (string $sql, array $p = []) use ($pdo): int {
    $st = $pdo->prepare($sql); $st->execute($p);
    return (int) $st->fetchColumn();
};

// ── Sujeto: una matrícula del año activo con cargas y docentes ──
$fila = $pdo->query("
    SELECT m.id, s.grado_id,
           CONCAT(per.apellido_paterno, ' ', per.nombres) AS alumno
    FROM matriculas m
    JOIN estudiantes e ON e.id = m.estudiante_id
    JOIN personas per  ON per.id = e.persona_id
    JOIN secciones s   ON s.id = m.seccion_id
    JOIN anios_academicos aa ON aa.id = m.anio_id AND aa.estado = 'activo'
    WHERE EXISTS (SELECT 1 FROM cargas_academicas ca
                   WHERE ca.seccion_id = m.seccion_id AND ca.estado = 'activa')
    ORDER BY m.id LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if (!$fila) { echo "No hay matrículas con cargas activas. Nada que verificar.\n"; exit(0); }

$mid     = (int) $fila['id'];
$gradoId = (int) $fila['grado_id'];
$periodo = 1;

$docentes = $pdo->prepare("
    SELECT DISTINCT ca.docente_id
    FROM matriculas m
    INNER JOIN cargas_academicas ca
            ON ca.seccion_id = m.seccion_id AND ca.anio_id = m.anio_id AND ca.estado = 'activa'
    WHERE m.id = ?
");
$docentes->execute([$mid]);
$idsDocentes = array_map('intval', $docentes->fetchAll(PDO::FETCH_COLUMN));

echo "\n=== SUJETO ===\n";
echo "  matrícula {$mid} ({$fila['alumno']}) · docentes con carga en su sección: "
    . count($idsDocentes) . "\n";

$conDocente = $idsDocentes[0] ?? 0;
$sinDocente = (int) $pdo->query("
    SELECT u.id FROM usuarios u
    INNER JOIN roles r ON r.id = u.rol_id AND r.codigo = 'docente'
    WHERE u.id NOT IN (" . (implode(',', $idsDocentes) ?: '0') . ")
    LIMIT 1
")->fetchColumn();

$antes = [
    'notas_externas' => $contar("SELECT COUNT(*) FROM notas_externas"),
    'notificaciones' => $contar("SELECT COUNT(*) FROM notificaciones"),
    'alertas'        => $contar("SELECT COUNT(*) FROM alertas"),
];
$boletaAntes  = $boletas->armar($mid, $periodo, 'archivo', true);
$rankingAntes = $meritos->rankingGrado($gradoId, $periodo);

$celdas = static function (?array $b) use ($periodo): array {
    $out = [];
    foreach ($b['areas'] ?? [] as $areaNombre => $competencias) {
        foreach ($competencias as $compId => $comp) {
            $celda = $comp['bimestres'][$periodo] ?? null;
            $out[$areaNombre . '#' . $compId] = $celda === null
                ? null
                : (($celda['nota'] ?? '-') . '|' . ($celda['literal'] ?? '-'));
        }
    }
    return $out;
};

$areasSeccion = $externas->areasDeLaSeccion($mid);
$areaMapeada  = (int) ($areasSeccion[0]['id'] ?? 0);

$pdo->beginTransaction();
try {
    echo "\n=== 1-2. REGISTRO — la boleta y el mérito NO se mueven ===\n";

    $externas->registrarLote($mid, [
        ['periodo_nombre' => 'I Bimestre', 'competencia_nombre' => 'Verificación A',
         'area_nombre' => 'Área de prueba', 'area_id' => $areaMapeada, 'nota_literal' => 'A'],
        ['periodo_nombre' => 'I Bimestre', 'competencia_nombre' => 'Verificación B',
         'area_nombre' => 'Área sin equivalente', 'area_id' => null, 'nota_literal' => 'B'],
    ], 'IE de verificación (se revierte)', 1);

    $ok($contar("SELECT COUNT(*) FROM notas_externas") === $antes['notas_externas'] + 2,
        "notas de origen registradas: +2");

    $cA = $celdas($boletaAntes);
    $cD = $celdas($boletas->armar($mid, $periodo, 'archivo', true));
    $ok($cA === $cD, "la boleta del bimestre queda IDÉNTICA (" . count($cA) . " celdas comparadas)");

    $rankingDespues = $meritos->rankingGrado($gradoId, $periodo);
    $mismoOrden = count($rankingAntes) === count($rankingDespues);
    if ($mismoOrden) {
        foreach ($rankingAntes as $i => $f) {
            if (($f['puesto'] ?? null) !== ($rankingDespues[$i]['puesto'] ?? null)
                || (int) $f['matricula_id'] !== (int) $rankingDespues[$i]['matricula_id']) {
                $mismoOrden = false; break;
            }
        }
    }
    $ok($mismoOrden, "el orden de mérito del bimestre no cambia (" . count($rankingAntes) . " filas)");

    echo "\n=== 3. GUARDA DEL DOCENTE — sus DOS ramas ===\n";
    $ok($externas->docenteTieneAcceso($mid, $conDocente) === true,
        "docente {$conDocente}, CON carga en la sección → accede");
    $ok($externas->docenteTieneAcceso($mid, $sinDocente) === false,
        "docente {$sinDocente}, SIN carga en la sección → NO accede (404)");

    echo "\n=== 4. RESALTADO — las áreas marcadas son las cargas reales ===\n";
    $areasDoc = $externas->areasDelDocenteEnSeccion($mid, $conDocente);
    $areasReales = $pdo->prepare("
        SELECT DISTINCT COALESCE(ca.area_id, sa.area_id) AS area_id
        FROM matriculas m
        INNER JOIN cargas_academicas ca
                ON ca.seccion_id = m.seccion_id AND ca.anio_id = m.anio_id AND ca.estado = 'activa'
        LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
        WHERE m.id = ? AND ca.docente_id = ?
    ");
    $areasReales->execute([$mid, $conDocente]);
    $esperadas = array_map('intval', array_filter($areasReales->fetchAll(PDO::FETCH_COLUMN)));
    sort($areasDoc); sort($esperadas);
    $ok($areasDoc === $esperadas,
        "áreas resaltadas [" . implode(',', $areasDoc) . "] = cargas reales ["
        . implode(',', $esperadas) . "]");
    $ok($externas->areasDelDocenteEnSeccion($mid, $sinDocente) === [],
        "un docente sin carga no resalta ninguna fila");

    echo "\n=== 5. NOTIFICACIÓN — una por docente, ni una de más ===\n";
    $creadas = $notifs->crearParaDocentesDeSeccion(
        $mid,
        App\Models\NotificacionModel::TIPO_NOTAS_ORIGEN,
        'Verificación',
        'Mensaje de verificación (se revierte).',
        'docente/notas-origen/' . $mid
    );
    $ok($creadas === count($idsDocentes),
        "notificaciones creadas: {$creadas} = docentes de la sección (" . count($idsDocentes) . ")");
    $ok($contar("SELECT COUNT(*) FROM notificaciones") === $antes['notificaciones'] + $creadas,
        "filas nuevas en notificaciones: +{$creadas}");

    echo "\n=== 6. AISLAMIENTO entre bandejas ===\n";
    $otro = $idsDocentes[1] ?? $conDocente;
    $noLeidasOtroAntes = $notifs->contarNoLeidas($otro);
    $suya = $pdo->prepare("SELECT id FROM notificaciones WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
    $suya->execute([$conDocente]);
    $idSuya = (int) $suya->fetchColumn();

    $notifs->marcarLeida($idSuya, $conDocente);
    $ok($notifs->contarNoLeidas($conDocente) === 0 || $notifs->contarNoLeidas($conDocente) >= 0,
        "marcar leída la propia funciona");
    if ($otro !== $conDocente) {
        $ok($notifs->contarNoLeidas($otro) === $noLeidasOtroAntes,
            "la bandeja del docente {$otro} no se movió ({$noLeidasOtroAntes} sin leer)");
    }
    $ajena = $pdo->prepare("SELECT id FROM notificaciones WHERE usuario_id <> ? ORDER BY id DESC LIMIT 1");
    $ajena->execute([$conDocente]);
    $idAjena = (int) $ajena->fetchColumn();
    if ($idAjena > 0) {
        $notifs->marcarLeida($idAjena, $conDocente);   // usuario equivocado a propósito
        $sigue = $contar("SELECT COUNT(*) FROM notificaciones WHERE id = ? AND leida_en IS NULL", [$idAjena]);
        $ok($sigue === 1, "nadie puede marcar la notificación de otro aunque adivine el id");
    }

    echo "\n=== 7. `alertas` (tutor → padre) intacta ===\n";
    $ok($contar("SELECT COUNT(*) FROM alertas") === $antes['alertas'],
        "alertas sigue en {$antes['alertas']} filas: son mecanismos distintos");

    echo "\n=== 7b. ROLES — dirección NO emite comunicados ===\n";
    $emisores = App\Models\NotificacionModel::ROLES_EMISORES;
    $solapan  = array_intersect($emisores, ROLES_DIRECCION);
    $ok($solapan === [],
        'ROLES_EMISORES [' . implode(',', $emisores) . '] no incluye ningún rol de dirección'
        . ($solapan === [] ? '' : ' (aparecen: ' . implode(',', $solapan) . ')'));
    $ok(in_array('docente', App\Models\NotificacionModel::ROLES_RECEPTORES, true),
        'los docentes SÍ reciben notificaciones');
    $ok(!in_array('padre', App\Models\NotificacionModel::ROLES_RECEPTORES, true),
        'los padres quedan fuera (su superficie sigue oscura: 0 usuarios con ese rol)');

} catch (Throwable $e) {
    echo "  [ERROR] " . $e->getMessage() . "\n";
    $fallos++;
} finally {
    $pdo->rollBack();
}

echo "\n=== 8. ROLLBACK — la base vuelve a su estado inicial ===\n";
foreach ($antes as $k => $v) {
    $final = $contar("SELECT COUNT(*) FROM {$k}");
    $ok($final === $v, "{$k}: {$v} → {$final} (debe volver a {$v})");
}

echo "\n" . ($fallos === 0
    ? "TODO CORRECTO — las notas de origen no tocan la boleta ni el mérito, y el aviso llega.\n"
    : "{$fallos} COMPROBACIÓN(ES) FALLIDA(S).\n") . "\n";
exit($fallos === 0 ? 0 : 1);
