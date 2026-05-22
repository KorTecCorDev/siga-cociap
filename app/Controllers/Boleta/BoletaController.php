<?php

namespace App\Controllers\Boleta;

use App\Controllers\BaseController;
use App\Models\CalificacionModel;
use App\Models\ConductaModel;
use App\Models\DirectorEbrModel;
use Core\Session;
use Core\View;

class BoletaController extends BaseController
{
    private CalificacionModel $calModel;
    private ConductaModel     $conductaModel;
    private DirectorEbrModel  $dirModel;

    public function __construct()
    {
        $this->requireRole([
            'admin',
            'director_general',
            'director_ebr',
            'registro_academico',
            'secretaria',
            'padre',
        ]);
        $this->calModel      = new CalificacionModel();
        $this->conductaModel = new ConductaModel();
        $this->dirModel      = new DirectorEbrModel();
    }

    /**
     * GET /boleta/{matricula_id}/{periodo_id}
     * Muestra la boleta anual con los 4 bimestres del año académico.
     */
    public function ver($matriculaId, $periodoId): void
    {
        $data = $this->buildBoletaData((int) $matriculaId, (int) $periodoId);

        View::setLayout('print');
        $this->view('boleta/alumno', array_merge($data, [
            'titulo'     => 'Boleta — ' . $data['alumno']['nombre_completo'],
            'url_boleta' => url("boleta/digital/{$matriculaId}/{$periodoId}"),
        ]));
    }

    /**
     * GET /boleta/digital/{matricula_id}/{periodo_id}
     * Vista digital mobile-first con conclusiones completas y QR.
     */
    public function verDigital($matriculaId, $periodoId): void
    {
        $matriculaId = (int) $matriculaId;
        $periodoId   = (int) $periodoId;
        $data        = $this->buildBoletaData($matriculaId, $periodoId);

        View::setLayout('digital');
        $this->view('boleta/digital', array_merge($data, [
            'titulo'      => 'Boleta Digital — ' . $data['alumno']['nombre_completo'],
            'url_boleta'  => url("boleta/digital/{$matriculaId}/{$periodoId}"),
        ]));
    }

    // ── Datos compartidos entre ver() y verDigital() ────────────

    private function buildBoletaData(int $matriculaId, int $periodoId): array
    {
        if (Session::hasRole('padre')) {
            $hijo = $this->getHijoPadre(Session::user()['id']);
            if (!$hijo || (int) $hijo['matricula_id'] !== $matriculaId) {
                http_response_code(403);
                $this->view('shared/403');
                exit;
            }
        }

        $alumno  = $this->getAlumno($matriculaId);
        $periodo = $this->getPeriodo($periodoId);

        if (!$alumno || !$periodo) {
            $this->redirectWithError(
                url('dashboard'),
                'No se encontró la boleta solicitada.'
            );
        }

        $periodos = $this->getPeriodosDelAnio($periodo['anio_id']);

        $datosPorPeriodo = [];
        foreach ($periodos as $p) {
            $datosPorPeriodo[$p['id']] = $this->calModel->getBoletaAlumno($matriculaId, $p['id']);
        }

        return [
            'alumno'      => $alumno,
            'periodos'    => $periodos,
            'areas'       => $this->buildAreasConBimestres($datosPorPeriodo, $periodos),
            'conducta'    => $this->conductaModel->getParaBoleta($matriculaId, $periodo['anio_id']),
            'institucion' => config('institucion'),
            'tutor'       => $this->getTutorSeccion($matriculaId),
            'directorEbr' => $this->dirModel->getVigenteEnFecha((int) $periodo['anio_id']),
        ];
    }

    // ── Queries privadas ────────────────────────────────────────

    private function getAlumno(int $matriculaId): ?array
    {
        return $this->calModel->queryOne("
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
        return $this->calModel->queryOne("
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

    private function getPeriodosDelAnio(int $anioId): array
    {
        return $this->calModel->query("
            SELECT id, numero, nombre_display
            FROM periodos
            WHERE anio_id = ?
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
                    $prefijoSubarea = '';
                    if (($nota['area_tipo'] ?? '') === 'con_subareas' && !empty($nota['subarea_nombre'])) {
                        $prefijoSubarea = $nota['subarea_nombre'] . ' — ';
                    }
                    $areas[$nombreArea][$compId] = [
                        'nombre'    => trim(
                            $prefijoSubarea .
                            ($nota['codigo_minedu'] ? $nota['codigo_minedu'] . '. ' : '') .
                            ($nota['nombre_corto'] ?? $nota['competencia_nombre'] ?? '')
                        ),
                        'bimestres' => [],
                    ];
                }

                $notaNum = isset($nota['nota_numerica']) ? (int) $nota['nota_numerica'] : null;
                $areas[$nombreArea][$compId]['bimestres'][$periodoId] = [
                    'nota'      => $notaNum,
                    'literal'   => $notaNum !== null ? CalificacionModel::toLiteral($notaNum) : null,
                    'conclusion'=> $nota['conclusion_descriptiva'] ?? null,
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

        return $areas;
    }

    private function getTutorSeccion(int $matriculaId): ?string
    {
        $seccion = $this->calModel->queryOne("
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

        $persona = $this->calModel->queryOne("
            SELECT p.apellido_paterno, p.apellido_materno, p.nombres
            FROM usuarios u
            INNER JOIN personas p ON p.id = u.persona_id
            WHERE u.id = ?
            LIMIT 1
        ", [$tutorId]);

        if (!$persona || empty($persona['apellido_paterno'])) {
            return null;
        }

        return $persona['apellido_paterno'] . ' '
             . $persona['apellido_materno'] . ', '
             . $persona['nombres'];
    }

    private function getHijoPadre(int $usuarioId): ?array
    {
        return $this->calModel->queryOne("
            SELECT m.id AS matricula_id
            FROM usuarios u
            INNER JOIN personas pa          ON pa.id = u.persona_id
            INNER JOIN apoderados ap        ON ap.persona_id = pa.id
            INNER JOIN vinculo_familiar vf  ON vf.apoderado_id = ap.id
            INNER JOIN estudiantes e        ON e.id = vf.estudiante_id
            INNER JOIN matriculas m         ON m.estudiante_id = e.id
            INNER JOIN anios_academicos a   ON a.id = m.anio_id
            WHERE u.id      = ?
              AND a.estado  = 'activo'
              AND m.estado  = 'aprobada'
            LIMIT 1
        ", [$usuarioId]);
    }
}
