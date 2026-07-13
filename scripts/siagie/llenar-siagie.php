<?php

/**
 * Llenado de los Excel oficiales del SIAGIE con las notas de SIGA-COCIAP.
 *
 * Toma los archivos que el SIAGIE exporta por sección+bimestre (réplica de lo
 * que Registro académico llenaba a mano) y vuelca el literal (NL) del promedio
 * final de cada competencia BLOQUEADA de un bimestre CERRADO, más su
 * conclusión descriptiva. El archivo llenado es EL ORIGINAL (se re-sube al
 * SIAGIE ante UGEL-MINEDU); internamente se escribe a un temporal, se
 * verifica, se respalda el original y recién entonces se reemplaza.
 *
 * Toda la lógica vive en App\Siagie\LlenadorSiagie (compartida con el módulo
 * web); este CLI solo aporta su política de disposición: backup + reemplazo
 * in-place del original. Reglas completas: docs/modulos/export-siagie.md
 *
 * Uso:
 *   php scripts/siagie/llenar-siagie.php [--simular] <archivo.xlsx|carpeta> [...]
 *
 *   --simular  hace todo el análisis y genera el reporte SIN tocar el archivo
 *              ni la base de datos. Recomendado como primer paso SIEMPRE.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

spl_autoload_register(function (string $class): void {
    $map = [
        'Core\\'        => CORE_PATH . '/',
        'App\\Models\\' => APP_PATH . '/Models/',
        'App\\Siagie\\' => APP_PATH . '/Siagie/',
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
require_once APP_PATH . '/Helpers/helpers.php';

date_default_timezone_set(config('timezone'));

use App\Siagie\LlenadorSiagie;

// ── Argumentos ───────────────────────────────────────────────
$args    = array_slice($argv, 1);
$simular = in_array('--simular', $args, true);
$rutas   = array_values(array_filter($args, fn($a) => $a !== '--simular'));

if ($rutas === []) {
    fwrite(STDERR, "Uso: php scripts/siagie/llenar-siagie.php [--simular] <archivo.xlsx|carpeta> [...]\n");
    exit(1);
}

$archivos = [];
foreach ($rutas as $ruta) {
    if (is_dir($ruta)) {
        foreach (glob(rtrim($ruta, '/\\') . '/*.xlsx') as $f) {
            if (!str_starts_with(basename($f), '~$')) {
                $archivos[] = $f;
            }
        }
    } elseif (is_file($ruta)) {
        $archivos[] = $ruta;
    } else {
        fwrite(STDERR, "No existe: {$ruta}\n");
        exit(1);
    }
}
if ($archivos === []) {
    fwrite(STDERR, "No se encontraron .xlsx en las rutas indicadas.\n");
    exit(1);
}

$llenador = new LlenadorSiagie();
$modo     = $simular ? 'SIMULACIÓN (sin escritura)' : 'ESCRITURA REAL';
echo "Llenado SIAGIE — {$modo} — " . count($archivos) . " archivo(s)\n\n";

foreach ($archivos as $rutaArchivo) {
    $nombreArchivo = basename($rutaArchivo);
    echo "── {$nombreArchivo}\n";
    $reporte   = [];
    $reporte[] = str_repeat('=', 70);
    $reporte[] = "LLENADO SIAGIE — {$nombreArchivo}";
    $reporte[] = date('d/m/Y H:i:s') . " — {$modo}";
    $reporte[] = str_repeat('=', 70);

    try {
        $r = $llenador->analizar($rutaArchivo);
        $reporte = array_merge($reporte, $r['reporte']);

        $escrituras = $r['escrituras'];
        $totNl      = $r['resumen']['nl'];
        $totConc    = $r['resumen']['conc'];

        $reporte[] = '';
        if ($simular) {
            $reporte[] = 'RESULTADO (simulación): se habrían escrito ' . count($escrituras)
                . " celdas ({$totNl} NL, {$totConc} conclusiones) y persistido "
                . count($r['codigos']) . ' código(s) SIAGIE. Nada fue modificado.';
        } elseif ($escrituras === []) {
            $reporte[] = 'RESULTADO: no hay nada que escribir — archivo sin cambios.';
        } else {
            // Escritura real: temporal → verificación → backup → reemplazo.
            $tmp = $llenador->escribirVerificado($r['xlsx'], $escrituras);

            // Backup del original y reemplazo in-place (decisión del usuario:
            // el archivo llenado ES el original, para que el SIAGIE no lo rebote).
            $dirBackup = __DIR__ . '/backup/' . date('Ymd_His');
            if (!is_dir($dirBackup) && !mkdir($dirBackup, 0775, true)) {
                @unlink($tmp);
                throw new RuntimeException("No se pudo crear el directorio de backup {$dirBackup} — el original NO fue tocado");
            }
            if (!copy($rutaArchivo, $dirBackup . '/' . $nombreArchivo)) {
                @unlink($tmp);
                throw new RuntimeException('No se pudo respaldar el original — NO fue tocado');
            }
            if (!rename($tmp, $rutaArchivo)) {
                @unlink($tmp);
                throw new RuntimeException('No se pudo reemplazar el original (el backup sí quedó guardado)');
            }

            $persistidos = $llenador->persistirCodigos($r['codigos']);

            $reporte[] = 'RESULTADO: ' . count($escrituras) . " celdas escritas ({$totNl} NL, {$totConc} conclusiones), verificadas una a una.";
            $reporte[] = "Backup del original: {$dirBackup}/{$nombreArchivo}";
            $reporte[] = "Códigos SIAGIE persistidos en SIGA: {$persistidos}";
        }
        echo '   ' . end($reporte) . "\n";
    } catch (Throwable $ex) {
        $reporte[] = '';
        $reporte[] = 'ERROR — ARCHIVO NO MODIFICADO: ' . $ex->getMessage();
        echo "   ERROR: {$ex->getMessage()}\n";
    }

    // Reporte a disco, junto al archivo
    $rutaReporte = dirname($rutaArchivo) . '/' . pathinfo($nombreArchivo, PATHINFO_FILENAME)
        . '_reporte_' . date('Ymd_His') . '.txt';
    file_put_contents($rutaReporte, implode("\n", $reporte) . "\n");
    echo "   Reporte: {$rutaReporte}\n\n";
}
