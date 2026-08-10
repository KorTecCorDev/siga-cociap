<?php

/**
 * SEEDER DEL PEOR CASO DE LA BOLETA — solo para calibrar el ajuste del modelo.
 *
 * Construye el documento MAS ALTO que la boleta puede llegar a producir, para
 * medir en papel si entra en una hoja A4 y calibrar el recorte de la conclusion
 * descriptiva. Escribe sobre matriculas REALES de local.
 *
 * Peor caso = por cada competencia del plan de la seccion, en LOS 4 BIMESTRES:
 *   · nota C (la unica que obliga conclusion en secundaria; en primaria obligan B y C)
 *   · conclusion descriptiva de longitud maxima real (500 caracteres)
 *   · bloqueo, porque la boleta solo muestra competencias BLOQUEADAS
 * Ademas cierra B3 y B4 para que sus columnas pinten y se active el LOGRO ANUAL
 * (que sale del ULTIMO periodo del anio y solo si esta cerrado).
 *
 * 🔴 ES DESTRUCTIVO A PROPOSITO. Pisa las notas reales de las dos matriculas
 * elegidas y cambia el estado de dos periodos. Antes de escribir vuelca TODO lo
 * que va a tocar a un JSON, y `--revertir` lo restaura tal cual estaba.
 *
 * Uso:
 *   php database/seeds/seed_peor_caso_boleta.php              (simula, no escribe)
 *   php database/seeds/seed_peor_caso_boleta.php --aplicar
 *   php database/seeds/seed_peor_caso_boleta.php --revertir
 *
 * Guardas: aborta si detecta el archivo de secretos de PRODUCCION.
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

use Core\Database;

// ── Guarda de produccion ──────────────────────────────────────────────
$secretosProd = '/home/u761410128/siga_secrets/database.php';
if (is_file($secretosProd)) {
    fwrite(STDERR, "\nABORTADO: este seeder es de DESARROLLO y pisa notas reales.\n"
        . "Se detecto el archivo de secretos de PRODUCCION ({$secretosProd}).\n");
    exit(1);
}

$args     = array_slice($argv, 1);
$aplicar  = in_array('--aplicar', $args, true);
$revertir = in_array('--revertir', $args, true);

$db      = Database::get();
$backup  = STORAGE_PATH . '/seed_peor_caso_boleta_backup.json';

// Secciones conejillo: la mas densa de cada nivel.
//  · Secundaria 4° A -> 29 competencias / 12 areas, y lleva columna de NOTA.
//  · Primaria   3° B -> 27 competencias /  9 areas, sin nota y conclusion mas ancha;
//    ademas es el nivel donde la conclusion es obligatoria en B y C, no solo en C.
$SECCIONES = [
    ['nivel' => 'Secundaria', 'grado' => 4, 'seccion' => 'A'],
    ['nivel' => 'Primaria',   'grado' => 3, 'seccion' => 'B'],
];

// Conclusion de longitud MAXIMA real medida en la BD (500 caracteres).
$TEXTO = 'Demuestra progreso en el desarrollo de la competencia, aunque requiere '
    . 'acompanamiento sostenido para consolidar los desempenos esperados del grado. '
    . 'Participa en las actividades propuestas y muestra disposicion para revisar sus '
    . 'producciones, incorporando parte de las sugerencias recibidas. Se recomienda '
    . 'reforzar la comprension de los conceptos trabajados mediante practica guiada en '
    . 'casa y consulta oportuna al docente, priorizando la lectura atenta de consignas '
    . 'y la revision de sus procedimientos antes de entregar cada trabajo asignado.';
$TEXTO = mb_substr($TEXTO, 0, 500);

function q(\PDO $db, string $sql, array $p = []): array {
    $st = $db->prepare($sql); $st->execute($p); return $st->fetchAll(PDO::FETCH_ASSOC);
}

// ══════════════════════════════════════════════════════════════════════
// REVERTIR
// ══════════════════════════════════════════════════════════════════════
if ($revertir) {
    if (!is_file($backup)) {
        fwrite(STDERR, "ABORTADO: no existe {$backup}. Nada que revertir.\n");
        exit(1);
    }
    $snap = json_decode((string) file_get_contents($backup), true);
    if (!is_array($snap)) {
        fwrite(STDERR, "ABORTADO: el respaldo esta corrupto.\n");
        exit(1);
    }

    $db->beginTransaction();
    try {
        foreach ($snap['matriculas'] as $mid) {
            $db->prepare("DELETE FROM calificaciones WHERE matricula_id = ?")->execute([$mid]);
        }
        foreach ($snap['calificaciones'] as $r) {
            $db->prepare("INSERT INTO calificaciones
                (id, matricula_id, carga_id, periodo_id, competencia_id, nota_numerica,
                 conclusion_descriptiva, extraordinaria, registrado_en, modificado_en, registrado_por)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)")->execute([
                $r['id'], $r['matricula_id'], $r['carga_id'], $r['periodo_id'], $r['competencia_id'],
                $r['nota_numerica'], $r['conclusion_descriptiva'], $r['extraordinaria'],
                $r['registrado_en'], $r['modificado_en'], $r['registrado_por'],
            ]);
        }
        foreach ($snap['bloqueos_creados'] as $id) {
            $db->prepare("DELETE FROM bloqueos_competencia WHERE id = ?")->execute([$id]);
        }
        foreach ($snap['cierres_transv_creados'] as $id) {
            $db->prepare("DELETE FROM cierres_transversales WHERE id = ?")->execute([$id]);
        }
        foreach ($snap['periodos'] as $p) {
            $db->prepare("UPDATE periodos SET estado = ? WHERE id = ?")->execute([$p['estado'], $p['id']]);
        }
        $db->commit();
    } catch (\Throwable $e) {
        $db->rollBack();
        fwrite(STDERR, "ABORTADO al revertir: {$e->getMessage()}\n");
        exit(1);
    }

    echo "REVERTIDO.\n";
    echo "  calificaciones restauradas : " . count($snap['calificaciones']) . "\n";
    echo "  bloqueos eliminados        : " . count($snap['bloqueos_creados']) . "\n";
    echo "  cierres transv. eliminados : " . count($snap['cierres_transv_creados']) . "\n";
    echo "  periodos restaurados       : " . count($snap['periodos']) . "\n";
    unlink($backup);
    echo "  respaldo borrado           : {$backup}\n";
    exit(0);
}

// ══════════════════════════════════════════════════════════════════════
// PLANIFICAR
// ══════════════════════════════════════════════════════════════════════
$anio = q($db, "SELECT id FROM anios_academicos WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1");
if (!$anio) { fwrite(STDERR, "ABORTADO: no hay anio academico activo.\n"); exit(1); }
$anioId = (int) $anio[0]['id'];

$periodos = q($db, "SELECT id, numero, estado FROM periodos WHERE anio_id = ? ORDER BY numero", [$anioId]);
if (count($periodos) < 4) { fwrite(STDERR, "ABORTADO: el anio no tiene 4 periodos.\n"); exit(1); }

$autor = q($db, "SELECT id FROM usuarios WHERE estado = 'activo' ORDER BY id LIMIT 1");
$autorId = (int) ($autor[0]['id'] ?? 1);

$objetivos = [];
foreach ($SECCIONES as $sel) {
    $sec = q($db, "
        SELECT s.id, s.nombre, s.es_unidocente, g.nombre_display grado, n.nombre nivel
        FROM secciones s
        JOIN grados g ON g.id = s.grado_id
        JOIN niveles n ON n.id = g.nivel_id
        WHERE s.anio_id = ? AND n.nombre = ? AND g.numero = ? AND s.nombre = ?
        LIMIT 1
    ", [$anioId, $sel['nivel'], $sel['grado'], $sel['seccion']]);
    if (!$sec) {
        fwrite(STDERR, "AVISO: no existe {$sel['nivel']} {$sel['grado']}° {$sel['seccion']}, se omite.\n");
        continue;
    }
    $sec = $sec[0];

    // Matricula conejillo: la primera del roster canonico (excluye trasladado/retirado).
    $mat = q($db, "
        SELECT m.id, CONCAT(p.apellido_paterno,' ',p.apellido_materno,', ',p.nombres) alumno
        FROM matriculas m
        JOIN estudiantes e ON e.id = m.estudiante_id
        JOIN personas p ON p.id = e.persona_id
        WHERE m.seccion_id = ? AND m.tipo NOT IN ('trasladado','retirado')
        ORDER BY m.id LIMIT 1
    ", [$sec['id']]);
    if (!$mat) {
        fwrite(STDERR, "AVISO: {$sec['nivel']} {$sec['grado']} {$sec['nombre']} sin matriculas, se omite.\n");
        continue;
    }

    // Universo = el MISMO que usa el cierre forzado: competencias propias de cada
    // carga activa + transversales del nivel en la carga dueña. Asi se llena cada
    // fila que el documento va a dibujar, incluido el bloque transversal.
    $pares = q($db, "
        SELECT ca.id carga_id, comp.id competencia_id
        FROM cargas_academicas ca
        JOIN competencias comp ON (
            (ca.subarea_id IS NOT NULL AND comp.subarea_id = ca.subarea_id)
            OR (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL AND comp.area_id = ca.area_id))
        WHERE ca.estado = 'activa' AND ca.seccion_id = ?
        UNION
        SELECT ca.id, comp.id
        FROM cargas_academicas ca
        JOIN secciones s ON s.id = ca.seccion_id
        JOIN grados g ON g.id = s.grado_id
        JOIN areas a ON a.tipo = 'transversal' AND a.nivel_id = g.nivel_id
        JOIN competencias comp ON comp.area_id = a.id
        LEFT JOIN subareas sa ON sa.id = ca.subarea_id
        LEFT JOIN areas ar ON ar.id = COALESCE(ca.area_id, sa.area_id)
        WHERE ca.estado = 'activa' AND ca.seccion_id = ?
          AND (ar.tipo IS NULL OR ar.tipo <> 'tutoria')
          AND (s.es_unidocente = 0 OR ca.id = (
                SELECT cad.id FROM cargas_academicas cad
                LEFT JOIN subareas sad ON sad.id = cad.subarea_id
                WHERE cad.seccion_id = ca.seccion_id AND cad.estado = 'activa'
                  AND COALESCE(cad.area_id, sad.area_id) = COALESCE(ca.area_id, sa.area_id)
                ORDER BY COALESCE(sad.orden, 0), cad.id LIMIT 1))
    ", [$sec['id'], $sec['id']]);

    $objetivos[] = [
        'seccion'    => $sec,
        'matricula'  => (int) $mat[0]['id'],
        'alumno'     => $mat[0]['alumno'],
        'pares'      => $pares,
    ];
}

if (!$objetivos) { fwrite(STDERR, "ABORTADO: ninguna seccion objetivo resuelta.\n"); exit(1); }

echo "=== SEEDER DEL PEOR CASO DE LA BOLETA ===\n";
echo "Anio academico id={$anioId} · autor de las escrituras: usuario {$autorId}\n";
echo "Conclusion: " . mb_strlen($TEXTO) . " caracteres (el maximo real medido en la BD)\n\n";
foreach ($objetivos as $o) {
    $s = $o['seccion'];
    printf("  %-11s %s %-2s | matricula %-4d | %-38s | %2d filas x 4 bimestres = %d notas\n",
        $s['nivel'], $s['grado'], $s['nombre'], $o['matricula'],
        mb_substr($o['alumno'], 0, 38), count($o['pares']), count($o['pares']) * 4);
}
echo "\nPeriodos que se CERRARAN para que pinten sus columnas y el logro anual:\n";
foreach ($periodos as $p) {
    if ((int) $p['numero'] >= 3) {
        echo "  · periodo {$p['id']} (bimestre {$p['numero']}): {$p['estado']} -> cerrado\n";
    }
}

if (!$aplicar) {
    echo "\nSIMULACRO: no se escribio nada. Repite con --aplicar para ejecutarlo.\n";
    exit(0);
}

// ══════════════════════════════════════════════════════════════════════
// APLICAR
// ══════════════════════════════════════════════════════════════════════
if (is_file($backup)) {
    fwrite(STDERR, "\nABORTADO: ya existe {$backup}.\n"
        . "Hay un seed aplicado sin revertir. Corre --revertir antes de volver a aplicar.\n");
    exit(1);
}

$snap = ['matriculas' => [], 'calificaciones' => [], 'bloqueos_creados' => [],
         'cierres_transv_creados' => [], 'periodos' => []];

$db->beginTransaction();
try {
    // 1) Respaldo de TODO lo que se va a pisar.
    foreach ($objetivos as $o) {
        $snap['matriculas'][] = $o['matricula'];
        foreach (q($db, "SELECT * FROM calificaciones WHERE matricula_id = ?", [$o['matricula']]) as $r) {
            $snap['calificaciones'][] = $r;
        }
    }
    foreach ($periodos as $p) {
        if ((int) $p['numero'] >= 3) {
            $snap['periodos'][] = ['id' => (int) $p['id'], 'estado' => $p['estado']];
        }
    }

    // 2) Reescribir las notas de las matriculas objetivo.
    $nota = 8;   // literal C — la que obliga conclusion en ambos niveles
    $totalNotas = 0;
    foreach ($objetivos as $o) {
        $db->prepare("DELETE FROM calificaciones WHERE matricula_id = ?")->execute([$o['matricula']]);

        foreach ($periodos as $p) {
            foreach ($o['pares'] as $par) {
                $db->prepare("INSERT INTO calificaciones
                    (matricula_id, carga_id, periodo_id, competencia_id, nota_numerica,
                     conclusion_descriptiva, extraordinaria, registrado_por)
                    VALUES (?,?,?,?,?,?,0,?)")->execute([
                    $o['matricula'], $par['carga_id'], $p['id'], $par['competencia_id'],
                    $nota, $TEXTO, $autorId,
                ]);
                $totalNotas++;

                // La boleta solo muestra competencias BLOQUEADAS.
                $ya = q($db, "SELECT id FROM bloqueos_competencia
                              WHERE carga_id=? AND competencia_id=? AND periodo_id=? LIMIT 1",
                        [$par['carga_id'], $par['competencia_id'], $p['id']]);
                if (!$ya) {
                    $db->prepare("INSERT INTO bloqueos_competencia
                        (carga_id, competencia_id, periodo_id, bloqueado_por, origen)
                        VALUES (?,?,?,?,'cierre')")->execute([
                        $par['carga_id'], $par['competencia_id'], $p['id'], $autorId,
                    ]);
                    $snap['bloqueos_creados'][] = (int) $db->lastInsertId();
                }
            }
        }
    }

    // 3) Cierre transversal por seccion en los periodos sin uno vigente: el
    //    bloque transversal de la boleta se AGREGA desde el cierre del tutor.
    foreach ($objetivos as $o) {
        foreach ($periodos as $p) {
            $vig = q($db, "SELECT id FROM cierres_transversales
                           WHERE seccion_id=? AND periodo_id=? AND anulado_en IS NULL LIMIT 1",
                     [$o['seccion']['id'], $p['id']]);
            if (!$vig) {
                $db->prepare("INSERT INTO cierres_transversales (seccion_id, periodo_id, cerrado_por)
                              VALUES (?,?,?)")->execute([$o['seccion']['id'], $p['id'], $autorId]);
                $snap['cierres_transv_creados'][] = (int) $db->lastInsertId();
            }
        }
    }

    // 4) Cerrar B3 y B4 para que sus columnas pinten y se active el logro anual.
    foreach ($periodos as $p) {
        if ((int) $p['numero'] >= 3) {
            $db->prepare("UPDATE periodos SET estado='cerrado' WHERE id = ?")->execute([$p['id']]);
        }
    }

    file_put_contents($backup, json_encode($snap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();
    if (is_file($backup)) { unlink($backup); }
    fwrite(STDERR, "\nABORTADO: {$e->getMessage()}\n");
    exit(1);
}

echo "\nAPLICADO.\n";
echo "  notas escritas             : {$totalNotas}\n";
echo "  bloqueos creados           : " . count($snap['bloqueos_creados']) . "\n";
echo "  cierres transv. creados    : " . count($snap['cierres_transv_creados']) . "\n";
echo "  calificaciones respaldadas : " . count($snap['calificaciones']) . "\n";
echo "  respaldo                   : {$backup}\n\n";
echo "URLs para imprimir (admin / registro academico):\n";
foreach ($objetivos as $o) {
    echo "  " . url('matriculas/' . $o['matricula'] . '/boleta/imprimir') . "\n";
}
echo "\nPara deshacerlo TODO: php database/seeds/seed_peor_caso_boleta.php --revertir\n";
