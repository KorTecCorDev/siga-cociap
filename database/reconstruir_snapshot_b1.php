<?php

/**
 * Reconstrucción del snapshot OFICIAL del orden de mérito de B1 (periodo 1).
 *
 * Uso:  php database/reconstruir_snapshot_b1.php            (SIMULA, no escribe)
 *       php database/reconstruir_snapshot_b1.php --confirmar (escribe)
 *
 * ── POR QUÉ EXISTE ──────────────────────────────────────────────────────────
 * El oficial de B1 es un CASO ESPECIAL. Se reconstruyó a mano en prod el
 * 25/07/2026 con una regla distinta de la del código: el roster son TODOS los
 * estudiantes con calificaciones bloqueadas en B1, SIN el filtro
 * `m.tipo NOT IN ('trasladado','retirado')` de la Fase A. Decisión del usuario:
 * quien fue evaluado en B1 figura en el documento de B1, aunque después se haya
 * trasladado o retirado. La regla GENERAL del código NO cambió (sigue filtrando
 * por tipo) y por eso reproduce ~518, no 528. `backfill_orden_merito.php` NO
 * sirve para este periodo.
 *
 * En LOCAL ese snapshot se perdió: `verif_fase_b_orden_merito.php` lo borró con
 * un DELETE ciego el 26/07/2026 (ver su cabecera). Sin filas, `debeUsarSnapshot`
 * cae limpiamente al cálculo en vivo — no se rompe nada — pero local muestra 518
 * alumnos en B1 en vez de 528 y, sobre todo, las pruebas del mérito dejan de
 * probar lo que dicen (`verif_fase5b` compara el vivo contra sí mismo y da un OK
 * falso en su paso 2). Este script devuelve local al estado de prod.
 *
 * ── POR QUÉ DUPLICA EL SQL DEL ROSTER ───────────────────────────────────────
 * La regla Fase C no existe en `OrdenMeritoModel` a propósito: meterla ahí como
 * parámetro abriría la puerta a generar rankings sin filtro de tipo por accidente.
 * Se duplica AQUÍ, aislada, y solo la cascada de desempate se reutiliza del modelo
 * (vía Reflection) para que el criterio que decide los puestos siga teniendo un
 * único dueño. Si `rankingGradoLive` cambia, este script debe revisarse: la
 * verificación de firma de abajo lo delata (abortaría antes de escribir).
 *
 * ── GUARDAS ─────────────────────────────────────────────────────────────────
 *  1. Aborta si detecta el archivo de secretos de PRODUCCIÓN.
 *  2. Sin `--confirmar` solo simula: calcula, verifica y no escribe.
 *  3. Todo el DELETE + INSERT corre en una TRANSACCIÓN.
 *  4. Antes del COMMIT verifica la FIRMA del documento (528 filas, puestos 1..72,
 *     0 empates pendientes, 0 alumnos sin puesto de sección). Si algo no cuadra
 *     hace ROLLBACK y aborta: prefiere dejar local sin snapshot antes que grabar
 *     un documento distinto del que está en producción.
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH',  ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

spl_autoload_register(function (string $class): void {
    $map = [
        'Core\\'        => CORE_PATH . '/',
        'App\\Models\\' => APP_PATH . '/Models/',
    ];
    foreach ($map as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

require_once CONFIG_PATH . '/app.php';
require_once APP_PATH    . '/Helpers/helpers.php';
date_default_timezone_set(config('timezone'));

use App\Models\OrdenMeritoModel;
use Core\Database;

// ── Guard de entorno ────────────────────────────────────────────────────────
// Mismo criterio que config/database.php: en CLI no hay HTTP_HOST, así que la
// existencia del archivo de secretos externo identifica a PRODUCCIÓN. Allí el
// oficial de B1 está intacto y es inmutable (candado 046): nada que reconstruir.
$secretosProd = '/home/u761410128/siga_secrets/database.php';
if (is_file($secretosProd)) {
    fwrite(STDERR,
        "ABORTADO: este script reescribe el snapshot OFICIAL del orden de merito y\n" .
        "no debe correr en PRODUCCION (alli B1 ya tiene sus 528 filas y son inmutables).\n" .
        "Se detecto el archivo de secretos externo ({$secretosProd}).\n");
    exit(1);
}

// ── Firma esperada del documento de B1 (la de prod, 25/07/2026) ─────────────
const PERIODO_B1        = 1;
const FIRMA_FILAS       = 528;
const FIRMA_PUESTO_MIN  = 1;
const FIRMA_PUESTO_MAX  = 72;

$confirmar = in_array('--confirmar', $argv ?? [], true);

$pdo   = Database::get();
$orden = new OrdenMeritoModel();

// La cascada de desempate tiene un único dueño: el modelo. Se reutiliza tal cual.
$cascada = new ReflectionMethod(OrdenMeritoModel::class, 'aplicarDesempate');
$cascada->setAccessible(true);

echo "=== Reconstruccion del snapshot oficial de B1 (periodo " . PERIODO_B1 . ") ===\n";
echo $confirmar
    ? "MODO: --confirmar (ESCRIBE en orden_merito_snapshot)\n\n"
    : "MODO: simulacion (no escribe). Agrega --confirmar para grabar.\n\n";

// ── Piezas SQL de la regla FASE C ───────────────────────────────────────────
// Copia de rankingGradoLive / rankingPorSeccionLive SIN el filtro de tipo.
// Todo lo demás es idéntico: solo competencias BLOQUEADAS (P2), extraordinarias
// fuera, anclaje por bimestre del retorno de grado, y el universo de áreas del
// mérito (transversal/tutoría fuera, con la excepción de Ética y Valores).
$metricas = "
    COUNT(cal.nota_numerica)            AS num_competencias,
    SUM(cal.nota_numerica)              AS total_notas,
    ROUND(AVG(cal.nota_numerica), 2)    AS promedio_general,
    AVG(cal.nota_numerica)              AS promedio_exacto,
    SUM(cal.nota_numerica <= 10)                AS num_c,
    SUM(cal.nota_numerica BETWEEN 11 AND 13)    AS num_b,
    SUM(cal.nota_numerica >= " . NOTA_MIN_AD . ")                AS num_ad,
    SUM(cal.nota_numerica IN (15, 16))          AS num_alto,
    SUM(cal.nota_numerica = 16)                 AS num_16
";

$joins = "
    FROM matriculas m
    INNER JOIN estudiantes e      ON e.id  = m.estudiante_id
    INNER JOIN personas p         ON p.id  = e.persona_id
    INNER JOIN secciones s        ON s.id  = m.seccion_id
    INNER JOIN grados g           ON g.id  = s.grado_id
    INNER JOIN calificaciones cal ON cal.matricula_id = m.id
    INNER JOIN bloqueos_competencia bc
            ON bc.carga_id       = cal.carga_id
           AND bc.competencia_id = cal.competencia_id
           AND bc.periodo_id     = cal.periodo_id
    INNER JOIN competencias comp  ON comp.id = cal.competencia_id
    LEFT  JOIN subareas sa        ON sa.id   = comp.subarea_id
    INNER JOIN areas a            ON a.id    = COALESCE(sa.area_id, comp.area_id)
";

$filtro = "
    WHERE g.id           = ?
      AND cal.periodo_id = ?
      AND cal.extraordinaria = 0
      -- REGLA FASE C: aqui NO va el filtro por m.tipo (ver cabecera).
      AND m.id NOT IN (
          SELECT matricula_oficial_id FROM retornos_grado WHERE estado = 'activo'
          UNION
          SELECT r.matricula_oficial_id
          FROM retornos_grado r
          INNER JOIN calificaciones c2
              ON c2.matricula_id = r.matricula_operativa_id
             AND c2.periodo_id   = ?
          WHERE r.estado = 'revertido'
      )
      AND (a.tipo NOT IN ('transversal', 'tutoria')
           OR a.nombre_boleta = '" . AREA_ETICA_NOMBRE_BOLETA . "')
";

$colas = "
    ORDER BY promedio_exacto DESC, num_c ASC, num_b ASC, num_ad DESC,
             num_alto DESC, num_16 DESC, m.id
";

// Grados con notas bloqueadas en B1 (sin filtro de tipo, misma regla).
$grados = $pdo->query("
    SELECT DISTINCT g.id, g.nombre_display, n.nombre AS nivel, n.id AS nivel_id, g.numero
    FROM matriculas m
    INNER JOIN secciones s        ON s.id  = m.seccion_id
    INNER JOIN grados g           ON g.id  = s.grado_id
    INNER JOIN niveles n          ON n.id  = g.nivel_id
    INNER JOIN calificaciones cal ON cal.matricula_id = m.id
                                 AND cal.periodo_id   = " . PERIODO_B1 . "
    INNER JOIN bloqueos_competencia bc
            ON bc.carga_id       = cal.carga_id
           AND bc.competencia_id = cal.competencia_id
           AND bc.periodo_id     = cal.periodo_id
    ORDER BY n.id, g.numero
")->fetchAll(PDO::FETCH_ASSOC);

echo "Grados con notas bloqueadas en B1: " . count($grados) . "\n\n";

// ── Cálculo de las filas ────────────────────────────────────────────────────
$stGrado = $pdo->prepare("
    SELECT m.id AS matricula_id, p.apellido_paterno, p.apellido_materno, p.nombres,
           s.nombre AS seccion_nombre, {$metricas}
    {$joins} {$filtro}
    GROUP BY m.id, p.apellido_paterno, p.apellido_materno, p.nombres, s.nombre
    {$colas}
");
$stSeccion = $pdo->prepare("
    SELECT m.id AS matricula_id, p.apellido_paterno, p.apellido_materno, p.nombres,
           s.id AS seccion_id, s.nombre AS seccion_nombre, {$metricas}
    {$joins} {$filtro}
    GROUP BY m.id, p.apellido_paterno, p.apellido_materno, p.nombres, s.id, s.nombre
    ORDER BY s.nombre, promedio_exacto DESC, num_c ASC, num_b ASC, num_ad DESC,
             num_alto DESC, num_16 DESC, m.id
");

$filas          = [];
$empatesPend    = 0;
$sinPuestoSec   = 0;

foreach ($grados as $g) {
    $gradoId = (int) $g['id'];

    $stGrado->execute([$gradoId, PERIODO_B1, PERIODO_B1]);
    $general = $cascada->invoke($orden, $stGrado->fetchAll(PDO::FETCH_ASSOC), PERIODO_B1);
    if (!$general) {
        continue;
    }

    // Puesto de sección: la cascada se aplica DENTRO de cada sección, igual que
    // en rankingPorSeccionLive.
    $stSeccion->execute([$gradoId, PERIODO_B1, PERIODO_B1]);
    $porSeccion = [];
    foreach ($stSeccion->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $porSeccion[$f['seccion_nombre']][] = $f;
    }
    $mapaSeccion = [];
    foreach ($porSeccion as $filasSec) {
        foreach ($cascada->invoke($orden, $filasSec, PERIODO_B1) as $f) {
            $mapaSeccion[(int) $f['matricula_id']] = [
                'seccion_id'     => isset($f['seccion_id']) ? (int) $f['seccion_id'] : null,
                'puesto_seccion' => (int) $f['puesto'],
            ];
            if (!empty($f['empate_pendiente'])) {
                $empatesPend++;
            }
        }
    }

    foreach ($general as $f) {
        $mid = (int) $f['matricula_id'];
        $sec = $mapaSeccion[$mid] ?? ['seccion_id' => null, 'puesto_seccion' => null];
        if ($sec['puesto_seccion'] === null) {
            $sinPuestoSec++;
        }
        if (!empty($f['empate_pendiente'])) {
            $empatesPend++;
        }
        $filas[] = [
            PERIODO_B1, $mid, $gradoId, $sec['seccion_id'],
            (int) $f['puesto'], $sec['puesto_seccion'],
            (int) $f['num_competencias'], (int) $f['total_notas'],
            $f['promedio_general'], $f['promedio_exacto'],
            (int) $f['num_c'], (int) $f['num_b'], (int) $f['num_ad'],
            (int) $f['num_alto'], (int) $f['num_16'],
        ];
    }

    printf("  %-10s %-6s  %3d alumnos\n", $g['nivel'], $g['nombre_display'], count($general));
}

// ── Verificación de la FIRMA (antes de escribir nada) ───────────────────────
$puestos = array_map(static fn(array $f): int => $f[4], $filas);
$total   = count($filas);
$min     = $puestos ? min($puestos) : 0;
$max     = $puestos ? max($puestos) : 0;

echo "\n--- Firma del documento calculado ---\n";
printf("  filas                        : %d   (esperado %d)\n", $total, FIRMA_FILAS);
printf("  puesto minimo / maximo       : %d / %d   (esperado %d / %d)\n",
    $min, $max, FIRMA_PUESTO_MIN, FIRMA_PUESTO_MAX);
printf("  empates pendientes           : %d   (esperado 0)\n", $empatesPend);
printf("  alumnos sin puesto de seccion: %d   (esperado 0)\n", $sinPuestoSec);

$errores = [];
if ($total !== FIRMA_FILAS)          { $errores[] = "filas {$total} != " . FIRMA_FILAS; }
if ($min   !== FIRMA_PUESTO_MIN)     { $errores[] = "puesto minimo {$min} != " . FIRMA_PUESTO_MIN; }
if ($max   !== FIRMA_PUESTO_MAX)     { $errores[] = "puesto maximo {$max} != " . FIRMA_PUESTO_MAX; }
if ($empatesPend !== 0)              { $errores[] = "{$empatesPend} empates sin resolver"; }
if ($sinPuestoSec !== 0)             { $errores[] = "{$sinPuestoSec} sin puesto de seccion"; }

if ($errores) {
    fwrite(STDERR,
        "\nABORTADO: el documento calculado NO coincide con el de produccion.\n  - " .
        implode("\n  - ", $errores) . "\n" .
        "No se escribio nada. Revisa si cambiaron los datos de B1 o la regla del ranking\n" .
        "antes de tocar el snapshot.\n");
    exit(1);
}
echo "  => FIRMA OK: coincide con el oficial de produccion.\n";

if (!$confirmar) {
    echo "\nSimulacion terminada. No se escribio nada.\n";
    echo "Para grabar: php database/reconstruir_snapshot_b1.php --confirmar\n";
    exit(0);
}

// ── Escritura (transaccional) ───────────────────────────────────────────────
$previas = (int) $pdo->query(
    "SELECT COUNT(*) FROM orden_merito_snapshot WHERE periodo_id = " . PERIODO_B1
)->fetchColumn();
echo "\nFilas previas en el snapshot de B1: {$previas}\n";

$pdo->beginTransaction();
try {
    $del = $pdo->prepare("DELETE FROM orden_merito_snapshot WHERE periodo_id = ?");
    $del->execute([PERIODO_B1]);

    // generado_por queda NULL: la reconstruccion no la firma un usuario de la app.
    $ins = $pdo->prepare("
        INSERT INTO orden_merito_snapshot
            (periodo_id, matricula_id, grado_id, seccion_id,
             puesto_grado, puesto_seccion,
             num_competencias, total_notas, promedio_general, promedio_exacto,
             num_c, num_b, num_ad, num_alto, num_16)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    foreach ($filas as $f) {
        $ins->execute($f);
    }

    $grabadas = (int) $pdo->query(
        "SELECT COUNT(*) FROM orden_merito_snapshot WHERE periodo_id = " . PERIODO_B1
    )->fetchColumn();

    if ($grabadas !== FIRMA_FILAS) {
        throw new RuntimeException("se grabaron {$grabadas} filas, se esperaban " . FIRMA_FILAS);
    }

    $pdo->commit();
    echo "OK: snapshot oficial de B1 reconstruido con {$grabadas} filas.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "ABORTADO (rollback): " . $e->getMessage() . "\n");
    exit(1);
}
