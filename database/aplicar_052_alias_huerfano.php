<?php

/**
 * Aplicador de la migración 052 — quita el alias huérfano «(Ética y Valores)»
 * del área Ed. Religiosa de SECUNDARIA.
 *
 * Uso:
 *   php database/aplicar_052_alias_huerfano.php              → SIMULA (ensayo real + ROLLBACK)
 *   php database/aplicar_052_alias_huerfano.php --confirmar  → aplica y hace COMMIT
 *
 * SÍ se puede correr en producción: por eso simula por defecto y solo escribe
 * con --confirmar. En modo simulación ejecuta el UPDATE de verdad dentro de una
 * transacción, muestra el efecto y hace ROLLBACK — el "ensayo en la propia
 * producción" que funcionó con la migración 050.
 *
 * POR QUÉ EXISTE ESTE SCRIPT Y NO SE PEGA EL .sql EN phpMyAdmin. Las tres
 * trampas que este repo ya documentó, todas evitadas aquí:
 *
 *   1. TRAZABILIDAD (lección de la 048): la salida del PASO 1 es IDÉNTICA en
 *      local y en prod, así que no dice contra qué entorno se ejecutó. El 06/08
 *      una migración se dio por aplicada en prod cuando había caído en local.
 *      → El PASO 0 imprime la HUELLA DEL SERVIDOR y la salida hay que
 *        capturarla: es lo único que prueba el entorno.
 *   2. `SELECT ROW_COUNT()` DEVUELVE 0 EN phpMyAdmin porque ejecuta las
 *      sentencias por separado. → Aquí el contador sale del propio UPDATE.
 *   3. phpMyAdmin IGNORA `USE` y reselecciona la base según la página; si la
 *      pestaña cuelga de `information_schema`, todo falla con #1109.
 *      → Aquí la conexión la resuelve `config/database.php`.
 *
 *   Y una cuarta: el .sql tiene PASO 1 (veredicto) y PASO 2 (UPDATE) como
 *   sentencias sueltas, así que pegar el archivo entero ejecuta el cambio
 *   AUNQUE el veredicto salga en rojo. Aquí el veredicto ABORTA de verdad.
 *
 * QUÉ HACE, EN ORDEN:
 *   PASO 0  huella del servidor (¿dónde estoy?)
 *   PASO 1  veredicto — aborta si no es PUEDE_LIMPIARSE
 *   PASO 2  control: primaria y Tutoría (TOE) antes del cambio
 *   PASO 3  el UPDATE (en transacción; ROLLBACK si no hay --confirmar)
 *   PASO 4  verificación EN CONEXIÓN NUEVA — lo único que prueba el COMMIT
 *
 * Reversión: PASO 4 del propio .sql (devuelve el alias).
 * Ver database/migrations/052_alias_huerfano_etica_secundaria.sql y docs/ESTADO.md.
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
            $p[] = $c . '=' . ($r[$c] === null ? 'NULL' : $r[$c]);
        }
        echo '   ' . implode('  ', $p) . "\n";
    }
}

// ── Consultas (idénticas a las del .sql) ─────────────────────────────
const SQL_VEREDICTO = "
    SELECT a.id AS area_id, a.nombre AS area, n.nombre AS nivel, a.nombre_boleta,
           a.alias_boleta AS alias_actual, a.codigo_siagie, a.tipo, a.activa,
           (SELECT COUNT(*) FROM cargas_academicas ca WHERE ca.area_id = a.id) AS cargas,
           (SELECT COUNT(*) FROM competencias c WHERE c.area_id = a.id)        AS competencias,
           CASE
               WHEN a.alias_boleta IS NULL THEN 'YA_LIMPIO'
               WHEN (SELECT COUNT(*) FROM cargas_academicas ca WHERE ca.area_id = a.id) > 0
                    THEN 'NO_LIMPIAR_TIENE_CARGAS'
               ELSE 'PUEDE_LIMPIARSE'
           END AS veredicto
      FROM areas a
      INNER JOIN niveles n ON n.id = a.nivel_id
     WHERE a.nombre LIKE 'Educaci_n Religiosa' AND n.codigo = 'sec'";

const SQL_CONTROL = "
    SELECT a.id, a.nombre, n.nombre AS nivel, a.nombre_boleta, a.alias_boleta,
           (SELECT COUNT(*) FROM cargas_academicas ca WHERE ca.area_id = a.id) AS cargas
      FROM areas a
      INNER JOIN niveles n ON n.id = a.nivel_id
     WHERE (a.nombre LIKE 'Educaci_n Religiosa' AND n.codigo = 'prim')
        OR  a.nombre_boleta LIKE '_tica y Valores'
     ORDER BY a.id";

const SQL_UPDATE = "
    UPDATE areas a
    INNER JOIN niveles n ON n.id = a.nivel_id
       SET a.alias_boleta = NULL
     WHERE a.nombre LIKE 'Educaci_n Religiosa'
       AND n.codigo = 'sec'
       AND a.alias_boleta IS NOT NULL
       AND NOT EXISTS (SELECT 1 FROM cargas_academicas ca WHERE ca.area_id = a.id)";

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
echo "\n   >> ENTORNO DETECTADO: " . ($esProd ? '*** PRODUCCIÓN ***' : 'local / no-producción') . "\n";
echo "   >> Fecha y hora del script: " . date('Y-m-d H:i:s') . " (America/Lima)\n";

// ── PASO 1 — VEREDICTO ───────────────────────────────────────────────
titulo('PASO 1 — VEREDICTO');
$v = $pdo->query(SQL_VEREDICTO)->fetchAll();
filas($v, ['area_id', 'area', 'nivel', 'nombre_boleta', 'alias_actual',
           'codigo_siagie', 'tipo', 'activa', 'cargas', 'competencias', 'veredicto']);

if (count($v) !== 1) {
    echo "\nABORTA: se esperaba EXACTAMENTE 1 fila y llegaron " . count($v) . ".\n";
    echo "  0 filas  = el anclaje no resolvió (¿el área se renombró?). NO es 'ya aplicada'.\n";
    echo "  >1 filas = hay más de un área que encaja. Detente y revisa.\n";
    exit(1);
}
$fila = $v[0];

if ($fila['veredicto'] === 'YA_LIMPIO') {
    echo "\nNADA QUE HACER: el alias ya es NULL en este entorno. La migración es idempotente.\n";
    exit(0);
}
if ($fila['veredicto'] !== 'PUEDE_LIMPIARSE') {
    echo "\nABORTA: veredicto '{$fila['veredicto']}'.\n";
    echo "  El área tiene {$fila['cargas']} carga(s). Su alias YA NO es huérfano y además\n";
    echo "  el invariante de CLAUDE.md exige que esa área siga SIN cargas: si recibiera\n";
    echo "  notas, el mismo curso contaría dos veces en el orden de mérito. Detente.\n";
    exit(1);
}
echo "\n   >> Veredicto PUEDE_LIMPIARSE. Se puede continuar.\n";

// ── PASO 2 — CONTROL (lo que NO se debe mover) ───────────────────────
titulo('PASO 2 — CONTROL ANTES DEL CAMBIO (primaria + Tutoría TOE)');
$antesControl = $pdo->query(SQL_CONTROL)->fetchAll();
filas($antesControl, ['id', 'nombre', 'nivel', 'nombre_boleta', 'alias_boleta', 'cargas']);
$antesAlias = (int) $pdo->query("SELECT COUNT(*) FROM areas WHERE alias_boleta IS NOT NULL")->fetchColumn();
echo "\n   áreas con alias ANTES: $antesAlias\n";

// ── PASO 3 — EL CAMBIO ───────────────────────────────────────────────
titulo('PASO 3 — ' . ($confirmar ? 'APLICANDO EL CAMBIO' : 'ENSAYO (se revertirá)'));
$pdo->beginTransaction();
$afectadas = $pdo->exec(SQL_UPDATE);
echo "   filas afectadas por el UPDATE: $afectadas   (se espera 1)\n";

if ($afectadas !== 1) {
    $pdo->rollBack();
    echo "\nABORTA con ROLLBACK: se esperaba 1 fila y el UPDATE afectó $afectadas.\n";
    exit(1);
}

$reintento = $pdo->exec(SQL_UPDATE);
echo "   idempotencia — 2.ª corrida afecta: $reintento   (se espera 0)\n";

$despuesAlias = (int) $pdo->query("SELECT COUNT(*) FROM areas WHERE alias_boleta IS NOT NULL")->fetchColumn();
echo "   áreas con alias DESPUÉS: $despuesAlias   (se espera " . ($antesAlias - 1) . ")\n";

echo "\n   Control DENTRO de la transacción (primaria y TOE deben seguir igual):\n";
filas($pdo->query(SQL_CONTROL)->fetchAll(), ['id', 'nombre', 'nivel', 'nombre_boleta', 'alias_boleta']);

$sano = ($reintento === 0 && $despuesAlias === $antesAlias - 1);
if (!$sano) {
    $pdo->rollBack();
    echo "\nABORTA con ROLLBACK: las comprobaciones internas no cuadran.\n";
    exit(1);
}

if ($confirmar) {
    $pdo->commit();
    echo "\n   >> COMMIT ejecutado.\n";
} else {
    $pdo->rollBack();
    echo "\n   >> ROLLBACK ejecutado (era un ensayo). Nada quedó escrito.\n";
}

// ── PASO 4 — VERIFICACIÓN EN CONEXIÓN NUEVA ──────────────────────────
titulo('PASO 4 — VERIFICACIÓN EN CONEXIÓN NUEVA');
echo "   (las SELECT de dentro de la transacción no prueban que el COMMIT persistió)\n\n";
$fresca = conexionFresca();
$final  = $fresca->query(SQL_VEREDICTO)->fetchAll();
filas($final, ['area_id', 'area', 'nivel', 'nombre_boleta', 'alias_actual',
               'codigo_siagie', 'tipo', 'activa', 'cargas', 'veredicto']);
echo "\n   Control final (primaria + TOE, deben estar intactas):\n";
filas($fresca->query(SQL_CONTROL)->fetchAll(), ['id', 'nombre', 'nivel', 'nombre_boleta', 'alias_boleta']);
$finalAlias = (int) $fresca->query("SELECT COUNT(*) FROM areas WHERE alias_boleta IS NOT NULL")->fetchColumn();
echo "\n   áreas con alias AHORA: $finalAlias\n";

$esperado = $confirmar ? $antesAlias - 1 : $antesAlias;
$ok = ($finalAlias === $esperado)
   && ($final[0]['alias_actual'] === ($confirmar ? null : '(Ética y Valores)'))
   && ($final[0]['codigo_siagie'] === $fila['codigo_siagie'])
   && ((int) $final[0]['activa'] === (int) $fila['activa']);

titulo($ok
    ? ($confirmar ? 'RESULTADO: MIGRACIÓN 052 APLICADA Y VERIFICADA' : 'RESULTADO: ENSAYO EN VERDE — vuelve a correr con --confirmar')
    : 'RESULTADO: *** REVISAR — la verificación final no cuadra ***');
exit($ok ? 0 : 1);
