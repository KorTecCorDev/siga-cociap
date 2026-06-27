<?php

namespace App\Models;

/**
 * BoletaModel
 *
 * Ensamblador UNICO de los datos de una boleta (digital e imprimible).
 * Fusiona las tres copias que vivian dispersas en los controladores
 * (Boleta\BoletaController, BoletaPublicaController publico y
 * Admin\BoletaPublicaController). Un solo punto de verdad para el
 * documento: las reglas de agregacion, transversales, exoneraciones,
 * conducta, asistencia y firma del director se calculan aqui.
 *
 * Es PURO: data in -> array out. La autorizacion (rol, alcance, padre)
 * vive en los entry points (controladores), no aqui.
 */
class BoletaModel extends BaseModel
{
    private CalificacionModel    $calModel;
    private ConductaModel        $conductaModel;
    private AsistenciaModel      $asistenciaModel;
    private OmisionCriterioModel $omisionModel;
    private ExoneracionModel     $exoModel;
    private DirectorEbrModel     $dirModel;

    public function __construct()
    {
        parent::__construct();
        $this->calModel        = new CalificacionModel();
        $this->conductaModel   = new ConductaModel();
        $this->asistenciaModel = new AsistenciaModel();
        $this->omisionModel    = new OmisionCriterioModel();
        $this->exoModel        = new ExoneracionModel();
        $this->dirModel        = new DirectorEbrModel();
    }

    /**
     * Arma la boleta anual completa de un alumno.
     *
     * @param int  $matriculaId   matricula consultada (puede ser operativa en retorno).
     * @param int  $periodoId     periodo a resaltar como activo.
     * @param bool $soloOficiales true = solo bimestres CERRADOS (regla de familias:
     *                            publico/token/codigo). false = todos los periodos
     *                            (uso interno: docente con BORRADOR, salida masiva).
     * @return array|null         null si la matricula o el periodo no existen.
     */
    public function armar(int $matriculaId, int $periodoId, bool $soloOficiales = false): ?array
    {
        // Retorno de grado: la boleta SIEMPRE se rotula con la matricula oficial
        // (grado/seccion SIAGIE) y sus notas se leen por union de las matriculas
        // involucradas (operativa + oficial). En el caso normal, identidad y
        // unica fuente son la propia matricula.
        $ctx       = $this->calModel->boletaContexto($matriculaId);
        $identidad = (int) $ctx['identidad'];
        $fuentes   = $ctx['fuentes'];

        $alumno  = $this->getAlumno($identidad);
        $periodo = $this->getPeriodo($periodoId);

        if (!$alumno || !$periodo) {
            return null;
        }

        $anioId   = (int) $periodo['anio_id'];
        $periodos = $this->getPeriodosDelAnio($anioId, $soloOficiales);

        $datosPorPeriodo = [];
        foreach ($periodos as $p) {
            $rows = [];
            foreach ($fuentes as $mid) {
                $rows = array_merge($rows, $this->calModel->getBoletaAlumno((int) $mid, $p['id']));
            }
            $datosPorPeriodo[$p['id']] = $rows;
        }

        $areas   = $this->buildAreasConBimestres($datosPorPeriodo, $periodos);
        $exoData = $this->exoModel->getConCompetenciasParaBoletaUnion($fuentes, $anioId);
        $areas   = ExoneracionModel::inyectarEnAreas($areas, $exoData, $periodos);

        return [
            'alumno'            => $alumno,
            'periodos'          => $periodos,
            'periodo_activo_id' => $periodoId,
            'areas'             => $areas,
            'conducta'          => $this->conductaModel->getParaBoletaUnion($fuentes, $anioId),
            'asistencia'        => [
                'bimestre' => $this->asistenciaModel->getDelBimestreUnion($fuentes, $periodoId),
                'anual'    => $this->asistenciaModel->getAcumuladoAnualUnion($fuentes, $periodoId),
            ],
            'omisiones'   => $this->omisionModel->getPorMatriculaAnioUnion($fuentes, $anioId),
            'institucion' => config('institucion'),
            'tutor'       => $this->getTutorSeccion($identidad),
            'directorEbr' => $this->dirModel->getVigenteEnFecha($anioId),
        ];
    }

    // ── Queries privadas ────────────────────────────────────────

    private function getAlumno(int $matriculaId): ?array
    {
        return $this->queryOne("
            SELECT
                m.id                AS matricula_id,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno,
                p.dni,
                CONCAT(
                    p.apellido_paterno, ' ',
                    p.apellido_materno, ', ',
                    p.nombres
                )                   AS nombre_completo,
                g.nombre_display    AS grado_nombre,
                s.nombre            AS seccion_nombre,
                n.nombre            AS nivel_nombre,
                n.codigo            AS nivel_codigo,
                n.escala_boleta,
                a.anio              AS anio_academico
            FROM matriculas m
            INNER JOIN estudiantes e        ON e.id = m.estudiante_id
            INNER JOIN personas p           ON p.id = e.persona_id
            INNER JOIN secciones s          ON s.id = m.seccion_id
            INNER JOIN grados g             ON g.id = s.grado_id
            INNER JOIN niveles n            ON n.id = g.nivel_id
            INNER JOIN anios_academicos a   ON a.id = m.anio_id
            WHERE m.id = ?
            LIMIT 1
        ", [$matriculaId]);
    }

    private function getPeriodo(int $periodoId): ?array
    {
        return $this->queryOne("
            SELECT
                p.id,
                p.anio_id,
                a.anio,
                CONCAT(p.nombre_display, ' — ', a.anio) AS nombre_display
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
            LIMIT 1
        ", [$periodoId]);
    }

    /**
     * Periodos del anio. Con $soloOficiales filtra a bimestres CERRADOS
     * (regla de familias: el BORRADOR de Hito A nunca se expone al publico).
     */
    private function getPeriodosDelAnio(int $anioId, bool $soloOficiales = false): array
    {
        $filtro = $soloOficiales ? "AND estado = 'cerrado'" : '';
        return $this->query("
            SELECT id, numero, nombre_display, estado
            FROM periodos
            WHERE anio_id = ? {$filtro}
            ORDER BY numero
        ", [$anioId]);
    }

    /**
     * Reorganiza los datos planos por periodo en una estructura
     * areas[nombre_area][comp_id] = { nombre, bimestres[periodo_id], literal_final }.
     */
    private function buildAreasConBimestres(array $datosPorPeriodo, array $periodos): array
    {
        $areas     = [];
        $periodIds = array_column($periodos, 'id');

        foreach ($datosPorPeriodo as $periodoId => $notas) {
            foreach ($notas as $nota) {
                $nombreArea = $nota['nombre_boleta'] ?? $nota['area_nombre'];
                if (!empty($nota['alias_boleta'])) {
                    $nombreArea .= ' ' . $nota['alias_boleta'];
                }
                $compId = $nota['competencia_id'];

                if (!isset($areas[$nombreArea][$compId])) {
                    // En secciones unidocentes (1°-3° primaria) el área se muestra
                    // como bloque área-curso: cada competencia por su código MINEDU
                    // + nombre, SIN prefijo ni etiqueta de subárea (afecta boleta
                    // imprimible y digital). Para especialistas (4°-6° y secundaria)
                    // se conserva el prefijo/etiqueta "Aritmética — …".
                    $muestraSubarea = empty($nota['es_unidocente'])
                        && ($nota['area_tipo'] ?? '') === 'con_subareas'
                        && !empty($nota['subarea_nombre']);
                    $prefijoSubarea = $muestraSubarea ? $nota['subarea_nombre'] . ' — ' : '';
                    $areas[$nombreArea][$compId] = [
                        'nombre'            => trim(
                            $prefijoSubarea .
                            ($nota['codigo_minedu'] ? $nota['codigo_minedu'] . '. ' : '') .
                            ($nota['nombre_corto'] ?? $nota['competencia_nombre'] ?? '')
                        ),
                        'nombre_largo'      => trim($prefijoSubarea . ($nota['competencia_nombre'] ?? '')),
                        'subarea_nombre'    => $muestraSubarea ? ($nota['subarea_nombre'] ?? '') : '',
                        'competencia_texto' => $nota['competencia_nombre'] ?? '',
                        'bimestres'         => [],
                    ];
                }

                $notaNum = isset($nota['nota_numerica']) ? (int) $nota['nota_numerica'] : null;
                $areas[$nombreArea][$compId]['bimestres'][$periodoId] = [
                    'nota'       => $notaNum,
                    'literal'    => $notaNum !== null ? CalificacionModel::toLiteral($notaNum) : null,
                    'conclusion' => $nota['conclusion_descriptiva'] ?? null,
                ];
            }
        }

        // Calcular literal_final solo cuando los 4 bimestres tienen nota
        foreach ($areas as &$comps) {
            foreach ($comps as &$comp) {
                $notasAnuales = [];
                foreach ($periodIds as $pid) {
                    $b = $comp['bimestres'][$pid] ?? null;
                    if ($b === null || $b['nota'] === null) {
                        $notasAnuales = null;
                        break;
                    }
                    $notasAnuales[] = $b['nota'];
                }

                if ($notasAnuales !== null && count($notasAnuales) === count($periodIds)) {
                    $prom = (int) round(array_sum($notasAnuales) / count($notasAnuales));
                    $comp['literal_final'] = CalificacionModel::toLiteral($prom);
                } else {
                    $comp['literal_final'] = null;
                }
            }
        }
        unset($comps, $comp);

        return $areas;
    }

    private function getTutorSeccion(int $matriculaId): ?array
    {
        $seccion = $this->queryOne("
            SELECT s.tutor_id
            FROM matriculas m
            INNER JOIN secciones s ON s.id = m.seccion_id
            WHERE m.id = ?
            LIMIT 1
        ", [$matriculaId]);

        $tutorId = (int) ($seccion['tutor_id'] ?? 0);
        if (!$tutorId) {
            return null;
        }

        $persona = $this->queryOne("
            SELECT p.apellido_paterno, p.apellido_materno, p.nombres, p.sexo
            FROM usuarios u
            INNER JOIN personas p ON p.id = u.persona_id
            WHERE u.id = ?
            LIMIT 1
        ", [$tutorId]);

        if (!$persona || empty($persona['apellido_paterno'])) {
            return null;
        }

        return [
            'nombre' => $persona['apellido_paterno'] . ' '
                      . $persona['apellido_materno'] . ', '
                      . $persona['nombres'],
            'sexo'   => $persona['sexo'],
        ];
    }
}
