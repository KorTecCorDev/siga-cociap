<?php

/**
 * Verificación — la card de empates del Centro de Control usa el motor real.
 * Uso: php database/verificaciones/verif_empates_card_control.php
 *
 * Comprueba que `ControlOperativoModel::empatesPendientes` (card de
 * /admin/control) y `OrdenMeritoModel::gradosConEmpatesPendientes` (pantalla de
 * resolución + guard del cierre) reportan EXACTAMENTE los mismos grados.
 *
 * Antes del 04/08/2026 el Centro de Control tenía su propia copia de la cascada,
 * congelada en la tupla de 3 conteos (num_c|num_b|num_ad) sin los criterios de
 * regularidad alta (num_alto, num_16). Inventaba empates que el motor real
 * deshace solo y que la pantalla de resolución nunca ofrecía → la card no se
 * limpiaba jamás. Esta verificación existe para que no vuelva a divergir.
 *
 * ESCRIBE, pero TODO corre dentro de una TRANSACCIÓN con ROLLBACK: para tener un
 * escenario con empates de verdad hay que retirar temporalmente las resoluciones
 * humanas. El paso 3 comprueba que volvieron.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH',  ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Guarda: nunca contra producción.
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

$orden   = new App\Models\OrdenMeritoModel();
$control = new App\Models\ControlOperativoModel();

/** Firma comparable de lo que reporta la card. */
$firmaCard = static function (array $items): array {
    $out = [];
    foreach ($items as $i) {
        $out[$i['nivel_nombre'] . ' — ' . $i['grado_nombre']] = (int) $i['n_grupos'];
    }
    ksort($out);
    return $out;
};

$periodos = [1, 2];

echo "=== 1. Estado actual (con las resoluciones humanas vigentes) ===\n";
foreach ($periodos as $p) {
    $guard = $orden->gradosConEmpatesPendientes($p);
    $card  = $firmaCard($control->empatesPendientes($p));
    sort($guard);
    $mismos = $guard === array_keys($card);
    printf("  periodo %d: guard=%d grado(s) · card=%d grado(s)  %s\n",
        $p, count($guard), count($card), $mismos ? 'OK (coinciden)' : '*** DIFIEREN ***');
    if (!$mismos) {
        echo "     guard: " . json_encode($guard, JSON_UNESCAPED_UNICODE) . "\n";
        echo "     card : " . json_encode(array_keys($card), JSON_UNESCAPED_UNICODE) . "\n";
    }
}

echo "\n=== 2. Escenario CON empates: se retiran las resoluciones (transacción) ===\n";
$orden->beginTransaction();
try {
    $antes = [];
    foreach ($periodos as $p) {
        $row = $orden->queryOne("SELECT COUNT(*) n FROM desempates_merito WHERE periodo_id = ?", [$p]);
        $antes[$p] = (int) ($row['n'] ?? 0);
    }

    $orden->execute("DELETE FROM desempates_merito_orden WHERE desempate_id IN
                     (SELECT id FROM desempates_merito)");
    $orden->execute("DELETE FROM desempates_merito");

    // Instancias nuevas: los modelos no cachean, pero así se descarta cualquier duda.
    $orden2   = new App\Models\OrdenMeritoModel();
    $control2 = new App\Models\ControlOperativoModel();

    $todoOk = true;
    foreach ($periodos as $p) {
        $guard = $orden2->gradosConEmpatesPendientes($p);
        $card  = $firmaCard($control2->empatesPendientes($p));
        sort($guard);
        $mismos = $guard === array_keys($card);
        $todoOk = $todoOk && $mismos;

        printf("  periodo %d (tenia %d resolucion(es)): guard=%d grado(s) · card=%d grado(s) · grupos=%d  %s\n",
            $p, $antes[$p], count($guard), count($card), array_sum($card),
            $mismos ? 'OK (mismos grados)' : '*** DIFIEREN ***');

        foreach ($card as $nombre => $n) {
            echo "       - {$nombre}: {$n} grupo(s) sin resolver\n";
        }
        if (!$mismos) {
            echo "     guard: " . json_encode($guard, JSON_UNESCAPED_UNICODE) . "\n";
            echo "     card : " . json_encode(array_keys($card), JSON_UNESCAPED_UNICODE) . "\n";
        }
    }

    echo "\n  " . ($todoOk
        ? 'OK: card y motor real coinciden tambien con empates presentes'
        : '*** FALLA: siguen divergiendo ***') . "\n";

    $orden->rollback();
} catch (\Throwable $e) {
    $orden->rollback();
    fwrite(STDERR, "ERROR: {$e->getMessage()}\n");
    exit(1);
}

echo "\n=== 3. ROLLBACK (la prueba no deja rastro) ===\n";
foreach ($periodos as $p) {
    $row = $orden->queryOne("SELECT COUNT(*) n FROM desempates_merito WHERE periodo_id = ?", [$p]);
    $n   = (int) ($row['n'] ?? 0);
    printf("  periodo %d: %d resolucion(es)  [previo %d]  %s\n",
        $p, $n, $antes[$p], $n === $antes[$p] ? 'OK' : '*** NO SE RESTAURO ***');
}
$row = $orden->queryOne("SELECT COUNT(*) n FROM desempates_merito_orden");
echo "  filas de detalle (desempates_merito_orden): " . (int) $row['n'] . "\n";
