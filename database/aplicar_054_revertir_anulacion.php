<?php

/**
 * Aplicador de la migración 054 — devuelve a VIGENTE la constancia de traslado
 * N° 052-2026-CAVVG-DA (4.º A de secundaria → IEP LAS AMERICAS SCHOOL).
 *
 * Uso:
 *   php database/aplicar_054_revertir_anulacion.php              → SIMULA (ensayo real + ROLLBACK)
 *   php database/aplicar_054_revertir_anulacion.php --confirmar  → aplica y hace COMMIT
 *
 * SÍ se puede correr en producción: por eso simula por defecto y solo escribe
 * con --confirmar. En modo simulación ejecuta el UPDATE de verdad dentro de una
 * transacción, muestra el efecto y hace ROLLBACK — el "ensayo en la propia
 * producción" que funcionó con las migraciones 050 y 052.
 *
 * POR QUÉ ESTE SCRIPT Y NO PEGAR EL .sql EN phpMyAdmin — las cuatro trampas que
 * este repo ya documentó, todas evitadas aquí:
 *   1. TRAZABILIDAD (lección de la 048): el PASO 0 imprime la HUELLA DEL
 *      SERVIDOR. Capturarla es lo único que prueba el entorno.
 *   2. `SELECT ROW_COUNT()` devuelve 0 en phpMyAdmin. Aquí el contador sale del
 *      propio UPDATE.
 *   3. phpMyAdmin ignora `USE`. Aquí la conexión la resuelve config/database.php.
 *   4. El .sql tiene veredicto y UPDATE como sentencias sueltas: pegarlo entero
 *      aplica el cambio AUNQUE el veredicto salga en rojo. Aquí ABORTA.
 *
 * QUÉ HACE, EN ORDEN:
 *   PASO 0  huella del servidor (¿dónde estoy?)
 *   PASO 1  veredicto — aborta si no es PUEDE_REACTIVARSE
 *   PASO 2  control: el libro de constancias del año, antes del cambio
 *   PASO 3  el UPDATE (en transacción; ROLLBACK si no hay --confirmar)
 *   PASO 4  verificación EN CONEXIÓN NUEVA — lo único que prueba el COMMIT
 *
 * NO TOCA la matrícula: el traslado es real y debe seguir consumado. El PASO 4
 * lo verifica explícitamente.
 *
 * Reversión: PASO 4 del propio .sql (devuelve el estado 'anulado' y su motivo).
 * Ver database/migrations/054_revertir_anulacion_constancia_traslado.sql.
 */

define('ROOT_PATH', dirname(__DIR__));
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
date_default_timezone_set(config('timezone'));

$confirmar = in_array('--confirmar', $argv, true);

/** DNI del estudiante y correlativo: el anclaje ASCII de la migración. */
const DNI_ANCLA         = '78313569';
const CORRELATIVO_ANCLA = 52;

/** Conexión NUEVA e independiente del singleton (para el PASO 4). */
function conexionFresca(): PDO
{
    $c = require CONFIG_PATH . '/database.php';
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $c['host'], $c['port'], $c['database'], $c['charset']);
    return new PDO($dsn, $c['username'], $c['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]);
}

function titulo(string $t): void {
    echo "\n" . str_repeat('=', 74) . "\n  $t\n" . str_repeat('=', 74) . "\n";
}

function filas(array $rows, array $cols): void {
    if (!$rows) { echo "   (0 filas)\n"; return; }
    foreach ($rows as $r) {
        $p = [];
        foreach ($cols as $c) {
            $p[] = $c . '=' . (($r[$c] ?? null) === null ? 'NULL' : $r[$c]);
        }
        echo '   ' . implode('  ', $p) . "\n";
    }
}

// ── Consultas (idénticas a las del .sql) ─────────────────────────────
const SQL_VEREDICTO = "
    SELECT t.id AS traslado_id, p.dni,
           CONCAT(p.apellido_paterno,' ',p.apellido_materno,', ',p.nombres) AS estudiante,
           t.correlativo, t.numero_constancia, t.estado AS estado_actual,
           t.fecha_constancia, t.ie_destino_nombre, t.veces_impresa, t.anulado_en,
           m.id AS matricula_id, m.estado AS matricula_estado, m.tipo AS matricula_tipo,
           COALESCE(viv.n_vigentes, 0) AS otras_vigentes_con_correlativo,
           CASE
               WHEN t.estado = 'vigente'            THEN 'YA_VIGENTE'
               WHEN COALESCE(viv.n_vigentes, 0) > 0 THEN 'NO_TOCAR_CORRELATIVO_EN_USO'
               WHEN m.estado <> 'desactivado'
                 OR m.tipo   <> 'trasladado'        THEN 'NO_TOCAR_MATRICULA_NO_TRASLADADA'
               ELSE 'PUEDE_REACTIVARSE'
           END AS veredicto
      FROM traslados t
      INNER JOIN matriculas       m ON m.id = t.matricula_id
      INNER JOIN estudiantes      e ON e.id = m.estudiante_id
      INNER JOIN personas         p ON p.id = e.persona_id
      INNER JOIN anios_academicos a ON a.id = t.anio_id AND a.estado = 'activo'
      LEFT  JOIN (
              SELECT anio_id, correlativo, COUNT(*) AS n_vigentes
                FROM traslados WHERE estado = 'vigente'
               GROUP BY anio_id, correlativo
           ) viv ON viv.anio_id = t.anio_id AND viv.correlativo = t.correlativo
     WHERE p.dni = ? AND t.correlativo = ?";

const SQL_LIBRO = "
    SELECT t.id, t.correlativo, t.numero_constancia, t.estado,
           t.fecha_constancia, t.ie_destino_nombre
      FROM traslados t
      INNER JOIN anios_academicos a ON a.id = t.anio_id AND a.estado = 'activo'
     ORDER BY t.correlativo, t.id";

const SQL_UPDATE = "
    UPDATE traslados t
    INNER JOIN matriculas       m ON m.id = t.matricula_id
    INNER JOIN estudiantes      e ON e.id = m.estudiante_id
    INNER JOIN personas         p ON p.id = e.persona_id
    INNER JOIN anios_academicos a ON a.id = t.anio_id AND a.estado = 'activo'
    LEFT  JOIN (
            SELECT anio_id, correlativo, COUNT(*) AS n_vigentes
              FROM traslados WHERE estado = 'vigente'
             GROUP BY anio_id, correlativo
         ) viv ON viv.anio_id = t.anio_id AND viv.correlativo = t.correlativo
       SET t.estado = 'vigente', t.anulado_motivo = NULL,
           t.anulado_en = NULL,  t.anulado_por = NULL
     WHERE p.dni = ? AND t.correlativo = ? AND t.estado = 'anulado'
       AND COALESCE(viv.n_vigentes, 0) = 0
       AND m.estado = 'desactivado' AND m.tipo = 'trasladado'";

const SQL_MATRICULA = "
    SELECT m.id, m.estado, m.tipo, m.tipo_anterior, m.motivo_estado
      FROM matriculas m
      INNER JOIN estudiantes e ON e.id = m.estudiante_id
      INNER JOIN personas    p ON p.id = e.persona_id
     WHERE p.dni = ?";

const SQL_DUPLICADOS = "
    SELECT t.anio_id, t.correlativo, COUNT(*) AS n_vigentes
      FROM traslados t
      INNER JOIN anios_academicos a ON a.id = t.anio_id AND a.estado = 'activo'
     WHERE t.estado = 'vigente'
     GROUP BY t.anio_id, t.correlativo
    HAVING COUNT(*) > 1";

$pdo = Core\Database::connect();

echo $confirmar
    ? "\n*** MODO APLICAR (--confirmar): se hará COMMIT del cambio. ***\n"
    : "\nMODO SIMULACIÓN: el UPDATE se ejecuta y se revierte. Añade --confirmar para aplicar.\n";

// ── PASO 0 — HUELLA DEL SERVIDOR ─────────────────────────────────────
titulo('PASO 0 — HUELLA DEL SERVIDOR (capturar esta salida: prueba el entorno)');
$h = $pdo->query("SELECT DATABASE() db, USER() usr, @@hostname host,
                         VERSION() ver, @@version_compile_os so, @@datadir datadir")->fetch();
foreach ($h as $k => $v) printf("   %-9s %s\n", $k, $v);
$esProd = str_contains((string) $h['db'], 'u761410128');
echo "   entorno   " . ($esProd ? '>>> PRODUCCION <<<' : 'local / copia') . "\n";

// ── PASO 1 — VEREDICTO (aborta de verdad) ────────────────────────────
titulo('PASO 1 — VEREDICTO');
$st = $pdo->prepare(SQL_VEREDICTO);
$st->execute([DNI_ANCLA, CORRELATIVO_ANCLA]);
$ver = $st->fetchAll();

if (count($ver) !== 1) {
    echo "   Filas resueltas por el anclaje: " . count($ver) . "\n";
    fwrite(STDERR, "\nABORTA: el anclaje (DNI " . DNI_ANCLA . " + correlativo "
        . CORRELATIVO_ANCLA . ") debe resolver EXACTAMENTE 1 fila.\n"
        . "  0 filas  -> el DNI no existe en este entorno, o el correlativo difiere.\n"
        . "  >1 filas -> hay mas de una constancia con ese numero: revisar a mano.\n");
    exit(1);
}

$v = $ver[0];
foreach ($v as $k => $val) printf("   %-32s %s\n", $k, $val === null ? 'NULL' : $val);

if ($v['veredicto'] !== 'PUEDE_REACTIVARSE') {
    fwrite(STDERR, "\nABORTA: veredicto = {$v['veredicto']}.\n" . match ($v['veredicto']) {
        'YA_VIGENTE' =>
            "  La constancia ya esta vigente: la migracion ya se aplico. Nada que hacer.\n",
        'NO_TOCAR_CORRELATIVO_EN_USO' =>
            "  Otra constancia VIGENTE usa el correlativo " . CORRELATIVO_ANCLA . " en este anio.\n"
          . "  Reactivar esta crearia DOS documentos oficiales con el mismo numero.\n"
          . "  Hay que decidir antes que numero lleva cada una. NO forzar.\n",
        'NO_TOCAR_MATRICULA_NO_TRASLADADA' =>
            "  La matricula ya no esta 'desactivado'+'trasladado' (esta "
          . "'{$v['matricula_estado']}'+'{$v['matricula_tipo']}').\n"
          . "  Una constancia vigente contradiria a la matricula. Revisar el caso.\n",
        default => "  Veredicto no contemplado.\n",
    });
    exit(1);
}

// ── PASO 2 — CONTROL: el libro antes del cambio ──────────────────────
titulo('PASO 2 — CONTROL: libro de constancias del anio (ANTES)');
filas($pdo->query(SQL_LIBRO)->fetchAll(),
      ['id', 'correlativo', 'numero_constancia', 'estado', 'fecha_constancia']);

// ── PASO 3 — EL CAMBIO (en transaccion) ──────────────────────────────
titulo('PASO 3 — UPDATE' . ($confirmar ? ' (se confirmara)' : ' (se revertira)'));
$pdo->beginTransaction();
try {
    $up = $pdo->prepare(SQL_UPDATE);
    $up->execute([DNI_ANCLA, CORRELATIVO_ANCLA]);
    $n = $up->rowCount();
    echo "   filas afectadas por el UPDATE: $n\n";

    if ($n !== 1) {
        $pdo->rollBack();
        fwrite(STDERR, "\nABORTA: se esperaba 1 fila afectada y fueron $n. ROLLBACK hecho.\n");
        exit(1);
    }

    // Efecto dentro de la transaccion.
    $st2 = $pdo->prepare(SQL_VEREDICTO);
    $st2->execute([DNI_ANCLA, CORRELATIVO_ANCLA]);
    $post = $st2->fetch();
    echo "   estado tras el UPDATE: {$post['estado_actual']}"
       . "  (veredicto ahora: {$post['veredicto']})\n";

    // Guard: ningun correlativo duplicado entre vigentes.
    $dup = $pdo->query(SQL_DUPLICADOS)->fetchAll();
    if ($dup) {
        $pdo->rollBack();
        fwrite(STDERR, "\nABORTA: el cambio dejaria correlativos duplicados. ROLLBACK hecho.\n");
        filas($dup, ['anio_id', 'correlativo', 'n_vigentes']);
        exit(1);
    }
    echo "   correlativos duplicados entre vigentes: 0\n";

    if ($confirmar) {
        $pdo->commit();
        echo "\n   >>> COMMIT hecho.\n";
    } else {
        $pdo->rollBack();
        echo "\n   (ROLLBACK: nada quedo escrito. Anade --confirmar para aplicar.)\n";
    }
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    fwrite(STDERR, "\nABORTA por excepcion: " . $e->getMessage() . "\nROLLBACK hecho.\n");
    exit(1);
}

// ── PASO 4 — VERIFICACION EN CONEXION NUEVA ──────────────────────────
titulo('PASO 4 — VERIFICACION EN CONEXION NUEVA (lo unico que prueba el COMMIT)');
$fresh = conexionFresca();

$st3 = $fresh->prepare(SQL_VEREDICTO);
$st3->execute([DNI_ANCLA, CORRELATIVO_ANCLA]);
$final = $st3->fetch();

echo "  4.a  La constancia\n";
printf("       estado=%s  anulado_en=%s  veces_impresa=%s  destino=%s\n",
    $final['estado_actual'],
    $final['anulado_en'] === null ? 'NULL' : $final['anulado_en'],
    $final['veces_impresa'],
    $final['ie_destino_nombre']);

echo "  4.b  CONTROL DURO — la matricula NO se movio\n";
$st4 = $fresh->prepare(SQL_MATRICULA);
$st4->execute([DNI_ANCLA]);
filas($st4->fetchAll(), ['id', 'estado', 'tipo', 'tipo_anterior', 'motivo_estado']);

echo "  4.c  CONTROL — correlativos duplicados entre vigentes (debe dar 0 filas)\n";
filas($fresh->query(SQL_DUPLICADOS)->fetchAll(), ['anio_id', 'correlativo', 'n_vigentes']);

echo "  4.d  Libro de constancias del anio (DESPUES)\n";
filas($fresh->query(SQL_LIBRO)->fetchAll(),
      ['id', 'correlativo', 'numero_constancia', 'estado', 'fecha_constancia']);

$esperado = $confirmar ? 'vigente' : 'anulado';
$ok = $final['estado_actual'] === $esperado
   && $final['matricula_estado'] === 'desactivado'
   && $final['matricula_tipo']   === 'trasladado';

titulo($ok
    ? ($confirmar ? 'RESULTADO: APLICADA. La constancia esta VIGENTE y la matricula intacta.'
                  : 'RESULTADO: SIMULACION CORRECTA. Nada escrito; el UPDATE afecta 1 fila.')
    : 'RESULTADO: *** REVISAR *** el estado final no es el esperado.');

exit($ok ? 0 : 1);
