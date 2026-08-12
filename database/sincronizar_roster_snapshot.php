<?php

/**
 * Reconcilia el ROSTER de los snapshots del orden de mérito con el estado real
 * de las matrículas.
 *
 * Uso:
 *   php database/sincronizar_roster_snapshot.php              → SIMULA (no escribe)
 *   php database/sincronizar_roster_snapshot.php --confirmar  → aplica
 *
 * SÍ se puede correr en producción: por eso simula por defecto y solo escribe
 * con --confirmar.
 *
 * POR QUÉ EXISTE. Desde el 11/08/2026 la salida de un alumno del orden de mérito
 * la hace `OrdenMeritoModel::sincronizarRosterPorMatricula()`, disparada por el
 * cambio de `matriculas.tipo` (traslado, retiro y sus reversiones) y, desde el
 * 12/08/2026, también por el de `matriculas.estado` (el roster exige
 * 'aprobada'). Pero las matrículas que YA estaban del otro lado antes de que ese
 * código existiera quedan huérfanas: su snapshot las incluye, el roster las
 * excluye, y ningún acto futuro va a volver a moverlas. Mientras esa divergencia
 * exista, la guarda de `registrarRanking` ABORTA toda rectificación del periodo
 * ('roster_cambiado'), que es correcto pero deja el bimestre bloqueado.
 *
 * Es el camino previsto para aplicar el cambio de regla del 12/08/2026 sobre los
 * bimestres ya cerrados y aún no publicados (en prod, B2).
 *
 * Este script es el que recoge esas divergencias previas. También sirve como
 * diagnóstico permanente: sin --confirmar solo informa.
 *
 * ALCANCE: periodos CERRADOS, con snapshot y **NO publicados**. Un bimestre ya
 * publicado es inmutable (candado 046) y no se toca — por eso B1 nunca aparece.
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

$pdo = Core\Database::connect();
$om  = new App\Models\OrdenMeritoModel();
$pub = new App\Models\PublicacionBoletaModel();

echo $confirmar
    ? "MODO APLICAR (--confirmar): se escribirán los snapshots divergentes.\n\n"
    : "MODO SIMULACIÓN: no se escribe nada. Añade --confirmar para aplicar.\n\n";

$periodos = $pdo->query("
    SELECT p.id, p.numero, p.nombre_display
    FROM periodos p
    WHERE p.estado = 'cerrado'
      AND EXISTS (SELECT 1 FROM orden_merito_snapshot s WHERE s.periodo_id = p.id)
    ORDER BY p.numero
")->fetchAll(PDO::FETCH_ASSOC);

$totalDivergencias = 0;
$totalAplicadas    = 0;

foreach ($periodos as $per) {
    $periodoId = (int) $per['id'];

    if ($pub->fuePublicado($periodoId)) {
        printf("%-14s  PUBLICADO → intocable (candado 046). Se salta.\n", $per['nombre_display']);
        continue;
    }

    // Roster guardado vs. roster que produciría el motor con los datos de hoy.
    $guardadas = [];
    foreach ($pdo->query("SELECT matricula_id, puesto_grado, grado_id
                          FROM orden_merito_snapshot WHERE periodo_id = $periodoId") as $r) {
        $guardadas[(int) $r['matricula_id']] = $r;
    }

    $ref = new ReflectionMethod(App\Models\OrdenMeritoModel::class, 'calcularFilasRanking');
    $ref->setAccessible(true);
    $frescas = [];
    foreach ($ref->invoke($om, $periodoId) as $f) {
        $frescas[(int) $f['matricula_id']] = $f;
    }

    $sobran = array_diff_key($guardadas, $frescas);   // están y no deberían
    $faltan = array_diff_key($frescas, $guardadas);   // deberían y no están

    printf("%-14s  snapshot: %d filas · el motor daría: %d\n",
        $per['nombre_display'], count($guardadas), count($frescas));

    if (!$sobran && !$faltan) {
        echo "                coherente, nada que hacer.\n\n";
        continue;
    }

    foreach (['sobran' => $sobran, 'faltan' => $faltan] as $clase => $lista) {
        foreach ($lista as $mid => $_) {
            $totalDivergencias++;
            $d = $pdo->query("
                SELECT CONCAT(pe.apellido_paterno,' ',pe.apellido_materno,', ',pe.nombres) alumno,
                       m.tipo, m.estado, m.updated_at, ni.nombre nivel, g.nombre_display grado
                FROM matriculas m
                INNER JOIN estudiantes e ON e.id = m.estudiante_id
                INNER JOIN personas pe   ON pe.id = e.persona_id
                INNER JOIN secciones s   ON s.id = m.seccion_id
                INNER JOIN grados g      ON g.id = s.grado_id
                INNER JOIN niveles ni    ON ni.id = g.nivel_id
                WHERE m.id = $mid")->fetch(PDO::FETCH_ASSOC);

            printf("   %-7s #%-5d %-34s %-11s %-3s tipo=%-12s estado=%-12s (cambio %s)\n",
                $clase === 'sobran' ? 'SOBRA' : 'FALTA', $mid,
                mb_substr($d['alumno'] ?? '?', 0, 34), $d['nivel'] ?? '?', $d['grado'] ?? '?',
                $d['tipo'] ?? '?', $d['estado'] ?? '?', $d['updated_at'] ?? '?');
        }
    }

    if (!$confirmar) {
        echo "                (simulación: no se aplicó)\n\n";
        continue;
    }

    // Se sincroniza por matrícula para que cada salida/reingreso quede en el log
    // con su puesto y su arrastre, igual que si lo hubiera hecho el acto normal.
    //
    // ⚠️ Con VARIAS divergencias a la vez, la primera llamada las resuelve TODAS:
    // `escribirOficial` reescribe el periodo entero con las filas frescas, así que
    // las siguientes ya no ven diferencia y no devuelven efectos. Sin la cuenta de
    // abajo, esas salidas quedarían fuera del registro — y de quién sale del
    // documento oficial siempre tiene que quedar traza.
    $pdo->beginTransaction();
    try {
        $reescritaPor = null;
        foreach (array_keys($sobran + $faltan) as $mid) {
            $efectos = $om->sincronizarRosterPorMatricula((int) $mid, null);
            foreach ($efectos as $e) {
                printf("   APLICADO  #%-5d %s\n", $mid,
                    trim(App\Models\OrdenMeritoModel::describirEfectosRoster([$e])));
                $reescritaPor ??= (int) $mid;
                $totalAplicadas++;
            }
            if (!$efectos) {
                $puesto = isset($guardadas[$mid])
                    ? sprintf('ocupaba el puesto %d°', (int) $guardadas[$mid]['puesto_grado'])
                    : 'no figuraba en el snapshot';
                printf("   APLICADO  #%-5d %s en %s (%s) — resuelto en la misma reescritura%s.\n",
                    $mid, isset($guardadas[$mid]) ? 'Salió' : 'Volvió',
                    $per['nombre_display'], $puesto,
                    $reescritaPor !== null ? ' que #' . $reescritaPor : '');
                log_error('Orden de mérito: roster reconciliado en bloque', [
                    'matricula' => (int) $mid,
                    'periodo'   => $periodoId,
                    'accion'    => isset($guardadas[$mid]) ? 'salio' : 'reintegrado',
                    'puesto'    => isset($guardadas[$mid]) ? (int) $guardadas[$mid]['puesto_grado'] : null,
                    'junto_con' => $reescritaPor,
                ]);
                $totalAplicadas++;
            }
        }
        $pdo->commit();
        printf("                snapshot final: %d filas\n\n",
            $pdo->query("SELECT COUNT(*) FROM orden_merito_snapshot WHERE periodo_id = $periodoId")->fetchColumn());
    } catch (\Throwable $e) {
        $pdo->rollBack();
        fwrite(STDERR, "   ERROR en {$per['nombre_display']}: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo str_repeat('-', 72), "\n";
printf("divergencias encontradas: %d\n", $totalDivergencias);
if ($confirmar) {
    printf("ajustes aplicados       : %d\n", $totalAplicadas);
} elseif ($totalDivergencias > 0) {
    echo "Vuelve a correrlo con --confirmar para aplicarlos.\n";
}
