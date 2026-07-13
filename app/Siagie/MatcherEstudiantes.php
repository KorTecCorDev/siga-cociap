<?php

namespace App\Siagie;

/**
 * MatcherEstudiantes
 *
 * Cruza las filas de estudiantes del Excel SIAGIE contra las matrículas de
 * la sección en SIGA. Regla de oro: ante cualquier duda NO se matchea (la
 * fila queda intacta y va al reporte para resolución manual) — jamás se
 * rellena a ciegas.
 *
 * Orden de resolución por fila del Excel:
 *   1. Por CÓDIGO SIAGIE (col. B == estudiantes.codigo_estudiante) — corridas
 *      posteriores a la primera. Si el nombre difiere, se advierte igual.
 *   2. Por NOMBRE normalizado, exacto y único ("APELLIDOS, NOMBRES" vs
 *      paterno+materno+nombres). Dos candidatos iguales = ambiguo.
 *   3. Sin match → se busca el más parecido solo como SUGERENCIA del reporte.
 *
 * Conflicto de código: si el estudiante matcheado por nombre ya tiene un
 * codigo_estudiante distinto al del Excel, la identidad es dudosa → NO se
 * escribe.
 */
class MatcherEstudiantes
{
    /**
     * Normalización simétrica de nombres (decisión del 03/07/2026):
     * mayúsculas, transliterar tildes/diéresis y Ñ→N, y eliminar TODO
     * carácter que no sea [A-Z0-9 espacio] (cubre ' & % . - y cualquier
     * otro especial), colapsando espacios.
     */
    public static function normalizar(string $s): string
    {
        $s = mb_strtoupper(trim($s), 'UTF-8');
        $s = strtr($s, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
            'Â' => 'A', 'Ê' => 'E', 'Î' => 'I', 'Ô' => 'O', 'Û' => 'U',
            'Ñ' => 'N', 'Ç' => 'C',
        ]);
        $s = preg_replace('/[^A-Z0-9 ]/', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    /**
     * @param array $filasExcel  [['fila'=>int,'id_siagie'=>string,'codigo'=>string,'nombre'=>string], …]
     * @param array $estudiantes filas de SiagieExportModel::estudiantesDeSeccion()
     * @return array ['matches'=>…, 'siga_sin_fila'=>…]
     *   matches: por cada fila del Excel →
     *     ['fila','nombre','codigo','estado','estudiante'=>?array,'detalle'=>string]
     *     estado ∈ match_codigo | match_nombre | sin_match | ambiguo | conflicto_codigo
     */
    public static function matchear(array $filasExcel, array $estudiantes): array
    {
        $porCodigo = [];
        $porNombre = [];
        foreach ($estudiantes as $e) {
            $codigo = trim((string) ($e['codigo_estudiante'] ?? ''));
            if ($codigo !== '') {
                $porCodigo[$codigo] = $e;
            }
            $clave = self::normalizar($e['apellido_paterno'] . ' ' . $e['apellido_materno'] . ' ' . $e['nombres']);
            $porNombre[$clave][] = $e;
        }

        $usados  = []; // estudiante_id ya asignado a una fila (detecta cruces)
        $matches = [];

        foreach ($filasExcel as $fx) {
            $codigoExcel = trim((string) $fx['codigo']);
            $claveExcel  = self::normalizar(str_replace(',', ' ', $fx['nombre']));
            $resultado   = [
                'fila'       => $fx['fila'],
                'nombre'     => $fx['nombre'],
                'codigo'     => $codigoExcel,
                'estado'     => 'sin_match',
                'estudiante' => null,
                'detalle'    => '',
            ];

            // 1) Por código SIAGIE ya persistido
            if ($codigoExcel !== '' && isset($porCodigo[$codigoExcel])) {
                $e = $porCodigo[$codigoExcel];
                $resultado['estado']     = 'match_codigo';
                $resultado['estudiante'] = $e;
                $claveSiga = self::normalizar($e['apellido_paterno'] . ' ' . $e['apellido_materno'] . ' ' . $e['nombres']);
                if ($claveSiga !== $claveExcel) {
                    $resultado['detalle'] = "ADVERTENCIA: el nombre difiere (SIGA: {$e['apellido_paterno']} {$e['apellido_materno']}, {$e['nombres']})";
                }
            }
            // 2) Por nombre normalizado, exacto y único
            elseif (isset($porNombre[$claveExcel])) {
                $candidatos = $porNombre[$claveExcel];
                if (count($candidatos) > 1) {
                    $resultado['estado']  = 'ambiguo';
                    $resultado['detalle'] = count($candidatos) . ' estudiantes de SIGA comparten este nombre — resolver manualmente';
                } else {
                    $e = $candidatos[0];
                    $codigoSiga = trim((string) ($e['codigo_estudiante'] ?? ''));
                    if ($codigoSiga !== '' && $codigoExcel !== '' && $codigoSiga !== $codigoExcel) {
                        $resultado['estado']     = 'conflicto_codigo';
                        $resultado['estudiante'] = $e;
                        $resultado['detalle']    = "SIGA ya tiene el código {$codigoSiga} y el Excel trae {$codigoExcel} — identidad dudosa, resolver manualmente";
                    } else {
                        $resultado['estado']     = 'match_nombre';
                        $resultado['estudiante'] = $e;
                    }
                }
            }
            // 3) Sin match → sugerencia (solo informativa, nunca escribe)
            else {
                $mejor = null;
                $mejorPct = 0.0;
                foreach ($estudiantes as $e) {
                    $claveSiga = self::normalizar($e['apellido_paterno'] . ' ' . $e['apellido_materno'] . ' ' . $e['nombres']);
                    similar_text($claveExcel, $claveSiga, $pct);
                    if ($pct > $mejorPct) {
                        $mejorPct = $pct;
                        $mejor    = $e;
                    }
                }
                if ($mejor !== null && $mejorPct >= 80.0) {
                    $resultado['detalle'] = sprintf(
                        '¿Es "%s %s, %s" de SIGA? (similitud %.0f%%)',
                        $mejor['apellido_paterno'], $mejor['apellido_materno'], $mejor['nombres'], $mejorPct
                    );
                }
            }

            // Un mismo estudiante de SIGA asignado a dos filas = cruce → ambos ambiguos
            if ($resultado['estudiante'] !== null && str_starts_with($resultado['estado'], 'match')) {
                $eid = (int) $resultado['estudiante']['estudiante_id'];
                if (isset($usados[$eid])) {
                    $resultado['estado']  = 'ambiguo';
                    $resultado['detalle'] = "El estudiante de SIGA ya fue asignado a la fila {$usados[$eid]} — cruce, resolver manualmente";
                    $resultado['estudiante'] = null;
                    foreach ($matches as &$prev) {
                        if ($prev['estudiante'] !== null && (int) $prev['estudiante']['estudiante_id'] === $eid) {
                            $prev['estado']  = 'ambiguo';
                            $prev['detalle'] = 'Asignación duplicada detectada — cruce, resolver manualmente';
                            $prev['estudiante'] = null;
                        }
                    }
                    unset($prev);
                } else {
                    $usados[$eid] = $fx['fila'];
                }
            }

            $matches[] = $resultado;
        }

        // Estudiantes de SIGA sin fila en el Excel (traslado posterior, etc.)
        $sinFila = [];
        foreach ($estudiantes as $e) {
            if (!isset($usados[(int) $e['estudiante_id']])) {
                $sinFila[] = $e;
            }
        }

        return ['matches' => $matches, 'siga_sin_fila' => $sinFila];
    }
}
