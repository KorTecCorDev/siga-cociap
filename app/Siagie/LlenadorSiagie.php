<?php

namespace App\Siagie;

use App\Models\SiagieExportModel;
use RuntimeException;

/**
 * LlenadorSiagie
 *
 * Orquestación del volcado de notas SIGA → Excel oficial del SIAGIE. Es la
 * capa compartida por el CLI (scripts/siagie/llenar-siagie.php) y el módulo
 * web (Admin\ActasSiagieController): parsea las hojas de área, mapea cada
 * columna a su competencia SIGA por la leyenda, cruza los estudiantes contra
 * las matrículas y decide qué literal + conclusión escribir en cada celda.
 *
 * Está partida en operaciones puras:
 *   - analizar()          → decide TODO sin tocar disco ni BD (es el preview).
 *   - escribirVerificado()→ genera el temporal y lo verifica celda por celda.
 *   - persistirCodigos()  → guarda los códigos SIAGIE emparejados.
 * Cada llamador aplica su propia política de disposición del temporal (el CLI
 * respalda y reemplaza el original in-place; el web lo streamea para descargar).
 *
 * Reglas completas: docs/modulos/export-siagie.md
 */
class LlenadorSiagie
{
    /** Hojas de metadata del libro que no llevan notas. */
    private const HOJAS_META = ['Generalidades', 'Parametros'];

    private SiagieExportModel $modelo;

    public function __construct(?SiagieExportModel $modelo = null)
    {
        $this->modelo = $modelo ?? new SiagieExportModel();
    }

    /**
     * Analiza un archivo SIAGIE y decide qué escribir, SIN tocar disco ni BD.
     * Las escrituras quedan ENCOLADAS en el XlsxQuirurgico devuelto; el llamador
     * decide si materializarlas (escribirVerificado) o descartarlas (preview).
     *
     * @param string $rutaArchivo  ruta al .xlsx a analizar.
     * @param array  $resoluciones fila(int) => estudiante_id(int). Resolución
     *               manual de identidad para filas sin_match/ambiguo; 0 = dejar
     *               en blanco. Vacío ⇒ comportamiento automático puro (CLI).
     * @return array {destino, etiqueta, xlsx, escrituras, codigos, reporte,
     *                resumen, matching, roster}
     * @throws RuntimeException en rechazo (sin hoja Parametros, destino inválido,
     *                          periodo no cerrado en SIGA).
     */
    public function analizar(string $rutaArchivo, array $resoluciones = []): array
    {
        $reporte = [];
        $xlsx    = new XlsxQuirurgico($rutaArchivo);

        // 1. Parámetros del archivo (hoja oculta) → destino en SIGA
        if (!in_array('Parametros', $xlsx->nombresDeHojas(), true)) {
            throw new RuntimeException('El archivo no tiene la hoja "Parametros" — no es una plantilla SIAGIE');
        }
        $par = $xlsx->leerCeldas('Parametros');
        $destino = $this->modelo->resolverDestino([
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
        $estudiantes = $this->modelo->estudiantesDeSeccion($destino['seccion_id']);
        $rosterPorId = [];
        foreach ($estudiantes as $e) {
            $rosterPorId[(int) $e['estudiante_id']] = $e;
        }
        $catalogo = [];
        foreach ($this->modelo->competenciasDelNivel($destino['nivel_id']) as $c) {
            $catalogo[MatcherEstudiantes::normalizar($c['nombre_completo'])][] = $c;
        }

        // 3. Recorrer las hojas de área
        $escriturasLog    = [];   // [hoja, ref, texto] para la verificación
        $matchesBase      = null; // matching de la primera hoja (se reusa si las filas coinciden)
        $firmaBase        = null;
        $codigosPersistir = [];   // estudiante_id => codigo (dedupe entre hojas)
        $notasCache       = [];   // matricula_id => notas por competencia
        $exoCache         = [];   // matricula_id => set exoneradas
        $advertencias     = [];
        $blancos          = [];
        $filasBaseCount   = 0;
        $totNl = $totConc = 0;
        $porCodigo = $porNombre = $porManual = 0;

        foreach ($xlsx->nombresDeHojas() as $hoja) {
            if (in_array($hoja, self::HOJAS_META, true)) {
                continue;
            }
            $celdas = $xlsx->leerCeldas($hoja);

            // 3a. Layout: fila 2 marca las columnas NL; fila 1 trae el número
            $columnas = []; // numero => ['nl'=>col, 'conc'=>col]
            foreach ($celdas[2] ?? [] as $col => $v) {
                if (trim((string) $v) === 'NL') {
                    $numero = (int) ltrim((string) ($celdas[1][$col] ?? ''), '0');
                    if ($numero > 0) {
                        $columnas[$numero] = ['nl' => $col, 'conc' => $this->siguienteColumna($col)];
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
                    // Resolución manual de identidad (solo la base; el detalle y las
                    // rejections se reportan una vez, como el matching).
                    $resultado = $this->aplicarResoluciones($resultado, $rosterPorId, $resoluciones, $reporte);
                    $matchesBase = $resultado;
                    $firmaBase   = $firma;
                    $filasBaseCount = count($filasExcel);
                    // El detalle de matching se reporta UNA vez (las hojas comparten nómina)
                    foreach ($resultado['matches'] as $mm) {
                        if ($mm['estado'] === 'match_codigo') $porCodigo++;
                        if ($mm['estado'] === 'match_nombre') $porNombre++;
                        if ($mm['estado'] === 'match_manual') $porManual++;
                    }
                    $manualTxt = $porManual > 0 ? ", por resolucion {$porManual}" : '';
                    $reporte[] = '';
                    $reporte[] = 'MATCHING DE ESTUDIANTES: Excel ' . count($filasExcel)
                        . ' | SIGA ' . count($estudiantes)
                        . " | matcheados " . ($porCodigo + $porNombre + $porManual)
                        . " (por código {$porCodigo}, por nombre {$porNombre}{$manualTxt})";
                    foreach ($resultado['matches'] as $mm) {
                        if (!$this->esMatch($mm['estado'])) {
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
                    // Hoja con nómina distinta: aplicar resoluciones en silencio.
                    $descartar = [];
                    $resultado = $this->aplicarResoluciones($resultado, $rosterPorId, $resoluciones, $descartar);
                    $reporte[] = "HOJA {$hoja}: la nómina difiere de las demás hojas — matching recalculado";
                }
            } else {
                $resultado = $matchesBase;
            }

            // 3e. Volcado
            $celdasNl = $celdasConc = 0;
            foreach ($resultado['matches'] as $mm) {
                if (!$this->esMatch($mm['estado'])) {
                    continue;
                }
                $e    = $mm['estudiante'];
                $mid  = (int) $e['matricula_id'];
                $fila = $mm['fila'];

                if (!isset($notasCache[$mid])) {
                    $notasCache[$mid] = $this->modelo->notasOficiales($mid, $destino['periodo_id']);
                    $exoCache[$mid]   = $this->modelo->competenciasExoneradas($mid, $destino['anio_id']);
                }
                // Código SIAGIE a persistir tras match por nombre o resolución (una sola vez)
                if ($this->persisteCodigo($mm['estado']) && $mm['codigo'] !== '' && trim((string) $e['codigo_estudiante']) === '') {
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

        return [
            'destino'    => $destino,
            'etiqueta'   => $etiqueta,
            'xlsx'       => $xlsx,
            'escrituras' => $escriturasLog,
            'codigos'    => $codigosPersistir,
            'reporte'    => $reporte,
            'resumen'    => [
                'nl'                => $totNl,
                'conc'              => $totConc,
                'match_codigo'      => $porCodigo,
                'match_nombre'      => $porNombre,
                'match_manual'      => $porManual,
                'advertencias'      => count($advertencias),
                'blancos'           => count($blancos),
                'estudiantes_excel' => $filasBaseCount,
                'estudiantes_siga'  => count($estudiantes),
            ],
            'matching'   => $matchesBase,
            'roster'     => $estudiantes,
        ];
    }

    /**
     * Genera el temporal con las escrituras encoladas y lo verifica celda por
     * celda releyendo la copia. El original NO se toca. Devuelve la ruta del
     * temporal verificado (el llamador lo respalda/reemplaza o lo streamea).
     *
     * @param array $escrituras [[hoja, ref, texto], …] las mismas que se encolaron.
     * @throws RuntimeException si alguna celda no quedó como se esperaba.
     */
    public function escribirVerificado(XlsxQuirurgico $xlsx, array $escrituras): string
    {
        $tmp   = $xlsx->guardarEnTemporal();
        $ver   = new XlsxQuirurgico($tmp);
        $cache = [];
        foreach ($escrituras as [$hoja, $ref, $texto]) {
            $cache[$hoja] ??= $ver->leerCeldas($hoja);
            preg_match('/^([A-Z]+)(\d+)$/', $ref, $mr);
            $leido = $cache[$hoja][(int) $mr[2]][$mr[1]] ?? null;
            if ($leido !== $texto) {
                @unlink($tmp);
                throw new RuntimeException("Verificación FALLIDA en {$hoja}!{$ref}: se esperaba '{$texto}' y se leyó '" . var_export($leido, true) . "' — el original NO fue tocado");
            }
        }
        return $tmp;
    }

    /**
     * Persiste los códigos SIAGIE emparejados (solo si el campo estaba vacío).
     * @param array $codigos estudiante_id => codigo.
     * @return int cuántos se escribieron efectivamente.
     */
    public function persistirCodigos(array $codigos): int
    {
        $persistidos = 0;
        foreach ($codigos as $estudianteId => $codigo) {
            if ($this->modelo->guardarCodigoSiagie((int) $estudianteId, $codigo)) {
                $persistidos++;
            }
        }
        return $persistidos;
    }

    // ── internos ────────────────────────────────────────────────

    /**
     * Aplica las resoluciones manuales de identidad sobre el resultado del
     * matching. Solo transforma filas sin_match/ambiguo en 'match_manual'
     * apuntando a un estudiante del roster; rechaza (y reporta) toda resolución
     * que viole las guardas: fuera del roster, estudiante ya asignado, o código
     * en conflicto. Con $resoluciones vacío es un NO-OP total (idéntico al CLI).
     *
     * @param array $rosterPorId estudiante_id => fila de estudiantesDeSeccion.
     */
    private function aplicarResoluciones(array $resultado, array $rosterPorId, array $resoluciones, array &$reporte): array
    {
        if ($resoluciones === []) {
            return $resultado;
        }

        // Estudiantes ya tomados por un match (automático o previo).
        $tomados = [];
        foreach ($resultado['matches'] as $mm) {
            if ($mm['estudiante'] !== null && $this->esMatch($mm['estado'])) {
                $tomados[(int) $mm['estudiante']['estudiante_id']] = $mm['fila'];
            }
        }

        $huboCambio = false;
        foreach ($resultado['matches'] as &$mm) {
            $fila = (int) $mm['fila'];
            if (!array_key_exists($fila, $resoluciones)) {
                continue;
            }
            $eid = (int) $resoluciones[$fila];
            if ($eid <= 0) {
                continue; // "dejar en blanco" explícito
            }
            if (!in_array($mm['estado'], ['sin_match', 'ambiguo'], true)) {
                $reporte[] = "  ⚠ RESOLUCION IGNORADA fila {$fila}: estado '{$mm['estado']}' no es resoluble manualmente";
                continue;
            }
            $e = $rosterPorId[$eid] ?? null;
            if ($e === null) {
                $reporte[] = "  ✗ RESOLUCION RECHAZADA fila {$fila}: el estudiante elegido no pertenece a la sección";
                continue;
            }
            if (isset($tomados[$eid])) {
                $reporte[] = "  ✗ RESOLUCION RECHAZADA fila {$fila}: ese estudiante ya está asignado a la fila {$tomados[$eid]}";
                continue;
            }
            $codigoSiga  = trim((string) ($e['codigo_estudiante'] ?? ''));
            $codigoExcel = trim((string) $mm['codigo']);
            if ($codigoSiga !== '' && $codigoExcel !== '' && $codigoSiga !== $codigoExcel) {
                $reporte[] = "  ✗ RESOLUCION RECHAZADA fila {$fila}: el estudiante ya tiene código {$codigoSiga} y el Excel trae {$codigoExcel} — corregir en su matrícula";
                continue;
            }
            $mm['estado']     = 'match_manual';
            $mm['estudiante'] = $e;
            $mm['detalle']    = "Resuelto manualmente → {$e['apellido_paterno']} {$e['apellido_materno']}, {$e['nombres']} (DNI {$e['dni']})";
            $tomados[$eid]    = $fila;
            $huboCambio       = true;
        }
        unset($mm);

        // Recalcular los "sin fila en el Excel" según las asignaciones finales.
        if ($huboCambio) {
            $sinFila = [];
            foreach ($rosterPorId as $eid => $e) {
                if (!isset($tomados[$eid])) {
                    $sinFila[] = $e;
                }
            }
            $resultado['siga_sin_fila'] = $sinFila;
        }

        return $resultado;
    }

    /** Estados que cuentan como emparejamiento efectivo (se escribe la nota). */
    private function esMatch(string $estado): bool
    {
        return $estado === 'match_codigo'
            || $estado === 'match_nombre'
            || $estado === 'match_manual';
    }

    /** Estados tras los cuales se persiste el código SIAGIE (no venía por código). */
    private function persisteCodigo(string $estado): bool
    {
        return $estado === 'match_nombre' || $estado === 'match_manual';
    }

    /** Columna siguiente en notación Excel (D→E, Z→AA). */
    private function siguienteColumna(string $col): string
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
}
