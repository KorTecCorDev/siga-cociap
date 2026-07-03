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
 * Reglas completas: docs/modulos/export-siagie.md
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
require_once __DIR__ . '/lib/XlsxQuirurgico.php';
require_once __DIR__ . '/lib/MatcherEstudiantes.php';

date_default_timezone_set(config('timezone'));

use App\Models\SiagieExportModel;
use Siagie\MatcherEstudiantes;
use Siagie\XlsxQuirurgico;

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

$modelo = new SiagieExportModel();
$modo   = $simular ? 'SIMULACIÓN (sin escritura)' : 'ESCRITURA REAL';
echo "Llenado SIAGIE — {$modo} — " . count($archivos) . " archivo(s)\n\n";

$HOJAS_META = ['Generalidades', 'Parametros'];

foreach ($archivos as $rutaArchivo) {
    $nombreArchivo = basename($rutaArchivo);
    echo "── {$nombreArchivo}\n";
    $reporte   = [];
    $reporte[] = str_repeat('=', 70);
    $reporte[] = "LLENADO SIAGIE — {$nombreArchivo}";
    $reporte[] = date('d/m/Y H:i:s') . " — {$modo}";
    $reporte[] = str_repeat('=', 70);

    try {
        $xlsx = new XlsxQuirurgico($rutaArchivo);

        // 1. Parámetros del archivo (hoja oculta) → destino en SIGA
        if (!in_array('Parametros', $xlsx->nombresDeHojas(), true)) {
            throw new RuntimeException('El archivo no tiene la hoja "Parametros" — no es una plantilla SIAGIE');
        }
        $par = $xlsx->leerCeldas('Parametros');
        $destino = $modelo->resolverDestino([
            'anio'           => $par[4]['B'] ?? '',
            'nivel_nombre'   => $par[3]['C'] ?? '',
            'periodo_codigo' => $par[6]['B'] ?? '',
            'seccion_texto'  => $par[8]['C'] ?? '',
        ]);
        if (isset($destino['error'])) {
            throw new RuntimeException($destino['error']);
        }
        $etiqueta = "{$destino['nivel_nombre']} {$destino['grado_numero']}{$destino['seccion_nombre']} — {$destino['periodo_nombre']}";
        $reporte[] = "Destino: {$etiqueta} (periodo {$destino['periodo_estado']})";

        // Regla 2: SOLO bimestres cerrados por Registro académico
        if ($destino['periodo_estado'] !== 'cerrado') {
            throw new RuntimeException("El {$destino['periodo_nombre']} NO está cerrado en SIGA — archivo rechazado completo");
        }

        // 2. Universo SIGA y catálogo de competencias del nivel
        $estudiantes = $modelo->estudiantesDeSeccion($destino['seccion_id']);
        $catalogo    = [];
        foreach ($modelo->competenciasDelNivel($destino['nivel_id']) as $c) {
            $catalogo[MatcherEstudiantes::normalizar($c['nombre_completo'])][] = $c;
        }

        // 3. Recorrer las hojas de área
        $escriturasLog   = [];   // [hoja, ref, texto] para la verificación
        $matchesBase     = null; // matching de la primera hoja (se reusa si las filas coinciden)
        $firmaBase       = null;
        $codigosPersistir = [];  // estudiante_id => codigo (dedupe entre hojas)
        $notasCache      = [];   // matricula_id => notas por competencia
        $exoCache        = [];   // matricula_id => set exoneradas
        $advertencias    = [];
        $blancos         = [];
        $totNl = $totConc = 0;

        foreach ($xlsx->nombresDeHojas() as $hoja) {
            if (in_array($hoja, $HOJAS_META, true)) {
                continue;
            }
            $celdas = $xlsx->leerCeldas($hoja);

            // 3a. Layout: fila 2 marca las columnas NL; fila 1 trae el número
            $columnas = []; // numero => ['nl'=>col, 'conc'=>col]
            foreach ($celdas[2] ?? [] as $col => $v) {
                if (trim((string) $v) === 'NL') {
                    $numero = (int) ltrim((string) ($celdas[1][$col] ?? ''), '0');
                    if ($numero > 0) {
                        $columnas[$numero] = ['nl' => $col, 'conc' => siguienteColumna($col)];
                    }
                }
            }
            if ($columnas === []) {
                $reporte[] = "HOJA {$hoja}: sin columnas NL reconocibles — omitida";
                continue;
            }

            // 3b. Filas de estudiantes (desde la 3, mientras haya nombre)
            $filasExcel = [];
            for ($f = 3; isset($celdas[$f]['C']); $f++) {
                $filasExcel[] = [
                    'fila'      => $f,
                    'id_siagie' => trim((string) ($celdas[$f]['A'] ?? '')),
                    'codigo'    => trim((string) ($celdas[$f]['B'] ?? '')),
                    'nombre'    => trim((string) $celdas[$f]['C']),
                ];
            }
            if ($filasExcel === []) {
                $reporte[] = "HOJA {$hoja}: sin estudiantes — omitida";
                continue;
            }
            $ultimaFila = end($filasExcel)['fila'];

            // 3c. Leyenda al pie → mapear cada columna a la competencia SIGA
            $leyenda = []; // numero => texto
            foreach ($celdas as $f => $cols) {
                if ($f <= $ultimaFila) {
                    continue;
                }
                foreach ($cols as $v) {
                    if (preg_match('/^\s*(\d{1,2})\s*=\s*(.+)$/u', (string) $v, $m)) {
                        $leyenda[(int) $m[1]] = trim($m[2]);
                    }
                }
            }

            $mapa = []; // numero => competencia (fila del catálogo)
            $sinEquivalente = [];
            foreach ($columnas as $numero => $cc) {
                $texto = $leyenda[$numero] ?? null;
                if ($texto === null) {
                    $reporte[] = "HOJA {$hoja}: la columna {$numero} no aparece en la leyenda — omitida";
                    continue;
                }
                $clave      = MatcherEstudiantes::normalizar($texto);
                $candidatas = $catalogo[$clave] ?? [];
                if (count($candidatas) === 1) {
                    $mapa[$numero] = $candidatas[0];
                } elseif (count($candidatas) === 0) {
                    $sinEquivalente[] = "{$numero} ({$texto})";
                } else {
                    $reporte[] = "HOJA {$hoja}: la competencia {$numero} matchea " . count($candidatas) . ' competencias de SIGA — omitida por ambigüedad';
                }
            }
            if ($mapa === []) {
                $detalle = $sinEquivalente !== [] ? ' (sin equivalente en SIGA: ' . implode('; ', $sinEquivalente) . ')' : '';
                $reporte[] = "HOJA {$hoja}: en blanco{$detalle}";
                continue;
            }
            if ($sinEquivalente !== []) {
                $reporte[] = "HOJA {$hoja}: columnas sin equivalente en SIGA (en blanco): " . implode('; ', $sinEquivalente);
            }

            // 3d. Matching (se reusa el de la primera hoja si las filas son idénticas)
            $firma = md5(serialize(array_map(fn($r) => [$r['fila'], $r['codigo'], $r['nombre']], $filasExcel)));
            if ($matchesBase === null || $firma !== $firmaBase) {
                $resultado = MatcherEstudiantes::matchear($filasExcel, $estudiantes);
                if ($matchesBase === null) {
                    $matchesBase = $resultado;
                    $firmaBase   = $firma;
                    // El detalle de matching se reporta UNA vez (las hojas comparten nómina)
                    $porCodigo = $porNombre = 0;
                    foreach ($resultado['matches'] as $mm) {
                        if ($mm['estado'] === 'match_codigo') $porCodigo++;
                        if ($mm['estado'] === 'match_nombre') $porNombre++;
                    }
                    $reporte[] = '';
                    $reporte[] = 'MATCHING DE ESTUDIANTES: Excel ' . count($filasExcel)
                        . ' | SIGA ' . count($estudiantes)
                        . " | matcheados " . ($porCodigo + $porNombre)
                        . " (por código {$porCodigo}, por nombre {$porNombre})";
                    foreach ($resultado['matches'] as $mm) {
                        if ($mm['estado'] !== 'match_codigo' && $mm['estado'] !== 'match_nombre') {
                            $reporte[] = "  ✗ fila {$mm['fila']} [{$mm['estado']}] {$mm['nombre']}"
                                . ($mm['detalle'] !== '' ? " — {$mm['detalle']}" : '');
                        } elseif ($mm['detalle'] !== '') {
                            $reporte[] = "  ⚠ fila {$mm['fila']} {$mm['nombre']} — {$mm['detalle']}";
                        }
                    }
                    foreach ($resultado['siga_sin_fila'] as $e) {
                        $reporte[] = "  ✗ en SIGA pero sin fila en el Excel: {$e['apellido_paterno']} {$e['apellido_materno']}, {$e['nombres']} (DNI {$e['dni']})";
                    }
                    $reporte[] = '';
                } else {
                    $reporte[] = "HOJA {$hoja}: la nómina difiere de las demás hojas — matching recalculado";
                }
            } else {
                $resultado = $matchesBase;
            }

            // 3e. Volcado
            $celdasNl = $celdasConc = 0;
            foreach ($resultado['matches'] as $mm) {
                if ($mm['estado'] !== 'match_codigo' && $mm['estado'] !== 'match_nombre') {
                    continue;
                }
                $e    = $mm['estudiante'];
                $mid  = (int) $e['matricula_id'];
                $fila = $mm['fila'];

                if (!isset($notasCache[$mid])) {
                    $notasCache[$mid] = $modelo->notasOficiales($mid, $destino['periodo_id']);
                    $exoCache[$mid]   = $modelo->competenciasExoneradas($mid, $destino['anio_id']);
                }
                // Código SIAGIE a persistir tras match por nombre (una sola vez)
                if ($mm['estado'] === 'match_nombre' && $mm['codigo'] !== '' && trim((string) $e['codigo_estudiante']) === '') {
                    $codigosPersistir[(int) $e['estudiante_id']] = $mm['codigo'];
                }

                foreach ($mapa as $numero => $cc) {
                    $compId = (int) $cc['competencia_id'];
                    $cols   = $columnas[$numero];
                    $refNl  = $cols['nl'] . $fila;
                    $refCo  = $cols['conc'] . $fila;

                    if (isset($exoCache[$mid][$compId])) {
                        $blancos[] = "{$hoja} fila {$fila}: EXONERADO — celda omitida ({$mm['nombre']})";
                        continue;
                    }
                    $nota = $notasCache[$mid][$compId] ?? null;
                    if ($nota === null) {
                        $blancos[] = "{$hoja} fila {$fila} col {$cols['nl']}: sin nota oficial (sin bloqueo o no evaluada) — {$mm['nombre']}";
                        continue;
                    }
                    // Nunca sobreescribir un valor ya presente en el Excel
                    if (isset($celdas[$fila][$cols['nl']])) {
                        $advertencias[] = "{$hoja} {$refNl}: la celda YA tiene valor '{$celdas[$fila][$cols['nl']]}' — no se toca";
                    } else {
                        $literal = nota_a_literal((int) $nota['nota_numerica']);
                        $xlsx->escribir($hoja, $refNl, $literal);
                        $escriturasLog[] = [$hoja, $refNl, $literal];
                        $celdasNl++;
                    }
                    // Regla 3: TODAS las conclusiones existentes
                    $conclusion = $nota['conclusion'];
                    if ($conclusion !== null && $conclusion !== '') {
                        $len = mb_strlen($conclusion);
                        if ($len < 10 || $len > 500) {
                            $advertencias[] = "{$hoja} {$refCo}: conclusión de {$len} caracteres — el SIAGIE valida 10–500 ({$mm['nombre']})";
                        }
                        if (isset($celdas[$fila][$cols['conc']])) {
                            $advertencias[] = "{$hoja} {$refCo}: la celda YA tiene valor — no se toca";
                        } else {
                            $xlsx->escribir($hoja, $refCo, $conclusion);
                            $escriturasLog[] = [$hoja, $refCo, $conclusion];
                            $celdasConc++;
                        }
                    }
                }
            }
            $totNl   += $celdasNl;
            $totConc += $celdasConc;
            $areaSiga = implode(', ', array_unique(array_column($mapa, 'area_nombre')));
            $reporte[] = "HOJA {$hoja} → {$areaSiga}: " . count($mapa) . " competencia(s) mapeada(s); {$celdasNl} NL, {$celdasConc} conclusiones";
        }

        // 4. Advertencias y celdas en blanco
        if ($advertencias !== []) {
            $reporte[] = '';
            $reporte[] = 'ADVERTENCIAS (' . count($advertencias) . '):';
            foreach ($advertencias as $a) {
                $reporte[] = "  ⚠ {$a}";
            }
        }
        if ($blancos !== []) {
            $reporte[] = '';
            $reporte[] = 'CELDAS EN BLANCO (' . count($blancos) . '):';
            foreach ($blancos as $b) {
                $reporte[] = "  · {$b}";
            }
        }

        // 5. Escritura real: temporal → verificación → backup → reemplazo
        $reporte[] = '';
        if ($simular) {
            $reporte[] = 'RESULTADO (simulación): se habrían escrito ' . count($escriturasLog)
                . " celdas ({$totNl} NL, {$totConc} conclusiones) y persistido "
                . count($codigosPersistir) . ' código(s) SIAGIE. Nada fue modificado.';
        } elseif ($escriturasLog === []) {
            $reporte[] = 'RESULTADO: no hay nada que escribir — archivo sin cambios.';
        } else {
            $tmp = $xlsx->guardarEnTemporal();

            // Verificación: releer la copia y confirmar cada celda escrita
            $ver    = new XlsxQuirurgico($tmp);
            $cache  = [];
            foreach ($escriturasLog as [$hoja, $ref, $texto]) {
                $cache[$hoja] ??= $ver->leerCeldas($hoja);
                preg_match('/^([A-Z]+)(\d+)$/', $ref, $mr);
                $leido = $cache[$hoja][(int) $mr[2]][$mr[1]] ?? null;
                if ($leido !== $texto) {
                    @unlink($tmp);
                    throw new RuntimeException("Verificación FALLIDA en {$hoja}!{$ref}: se esperaba '{$texto}' y se leyó '" . var_export($leido, true) . "' — el original NO fue tocado");
                }
            }

            // Backup del original y reemplazo in-place (decisión del usuario:
            // el archivo llenado ES el original, para que el SIAGIE no lo rebote)
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

            // Persistir códigos SIAGIE (regla: solo si el campo está vacío)
            $persistidos = 0;
            foreach ($codigosPersistir as $estudianteId => $codigo) {
                if ($modelo->guardarCodigoSiagie($estudianteId, $codigo)) {
                    $persistidos++;
                }
            }

            $reporte[] = 'RESULTADO: ' . count($escriturasLog) . " celdas escritas ({$totNl} NL, {$totConc} conclusiones), verificadas una a una.";
            $reporte[] = "Backup del original: {$dirBackup}/{$nombreArchivo}";
            $reporte[] = "Códigos SIAGIE persistidos en SIGA: {$persistidos}";
        }
        echo '   ' . end($reporte) . "\n";
    } catch (Throwable $ex) {
        $reporte[] = '';
        $reporte[] = 'ERROR — ARCHIVO NO MODIFICADO: ' . $ex->getMessage();
        echo "   ERROR: {$ex->getMessage()}\n";
    }

    // 6. Reporte a disco, junto al archivo
    $rutaReporte = dirname($rutaArchivo) . '/' . pathinfo($nombreArchivo, PATHINFO_FILENAME)
        . '_reporte_' . date('Ymd_His') . '.txt';
    file_put_contents($rutaReporte, implode("\n", $reporte) . "\n");
    echo "   Reporte: {$rutaReporte}\n\n";
}

/** Columna siguiente en notación Excel (D→E, Z→AA). */
function siguienteColumna(string $col): string
{
    $n = 0;
    foreach (str_split($col) as $ch) {
        $n = $n * 26 + (ord($ch) - 64);
    }
    $n++;
    $s = '';
    while ($n > 0) {
        $r = ($n - 1) % 26;
        $s = chr(65 + $r) . $s;
        $n = intdiv($n - 1, 26);
    }
    return $s;
}
