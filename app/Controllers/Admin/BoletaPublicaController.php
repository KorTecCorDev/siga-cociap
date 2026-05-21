<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BoletaPublicaModel;
use App\Models\CalificacionModel;
use App\Models\ConductaModel;
use Core\Session;
use Core\View;

class BoletaPublicaController extends BaseController
{
    private BoletaPublicaModel $model;
    private CalificacionModel  $calModel;
    private ConductaModel      $conductaModel;

    public function __construct()
    {
        $this->requireRole(['admin', 'registro_academico']);
        $this->model         = new BoletaPublicaModel();
        $this->calModel      = new CalificacionModel();
        $this->conductaModel = new ConductaModel();
    }

    /** GET /admin/boletas-publicas — selector de periodos */
    public function index(): void
    {
        $periodos = $this->model->query("
            SELECT p.id, p.numero, p.nombre_display, a.anio,
                   COUNT(bp.id) AS total_generadas
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            LEFT  JOIN boletas_publicas bp ON bp.periodo_id = p.id
            WHERE a.estado = 'activo'
            GROUP BY p.id
            ORDER BY p.numero
        ");

        $this->view('admin/boletas-publicas/index', [
            'titulo'   => 'Boletas Públicas',
            'periodos' => $periodos,
        ]);
    }

    /** GET /admin/boletas-publicas/{periodo_id} — tabla de boletas generadas */
    public function porPeriodo($periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $boletas = $this->model->getPorPeriodo($periodoId);

        $row = $this->model->queryOne("
            SELECT COUNT(DISTINCT m.id) AS total
            FROM matriculas m
            INNER JOIN calificaciones cal
                ON cal.matricula_id = m.id AND cal.periodo_id = ?
            INNER JOIN bloqueos_competencia bc
                ON bc.carga_id       = cal.carga_id
               AND bc.competencia_id = cal.competencia_id
               AND bc.periodo_id     = cal.periodo_id
            WHERE m.estado = 'aprobada'
        ", [$periodoId]);
        $totalAprobadas  = (int) ($row['total'] ?? 0);
        $totalConNovedades = count(array_filter($boletas, fn($b) => (int)$b['novedades_count'] > 0));

        $this->view('admin/boletas-publicas/periodo', [
            'titulo'            => 'Boletas Públicas — ' . $periodo['nombre_display'],
            'periodo'           => $periodo,
            'boletas'           => $boletas,
            'totalAprobadas'    => $totalAprobadas,
            'totalConNovedades' => $totalConNovedades,
        ]);
    }

    /** POST /admin/boletas-publicas/{periodo_id}/actualizar — resetea fechas de boletas con novedades */
    public function actualizar($periodoId): void
    {
        $this->validateCsrf();
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $usuarioId  = Session::user()['id'] ?? 0;
        $actualizadas = $this->model->actualizarTimestamps($periodoId, $usuarioId);

        $msg = $actualizadas > 0
            ? "{$actualizadas} boleta(s) actualizadas con las nuevas competencias bloqueadas."
            : 'No hay boletas con nuevas competencias desde la última generación.';

        $actualizadas > 0
            ? $this->redirectWithSuccess(url("admin/boletas-publicas/{$periodoId}"), $msg)
            : $this->redirectWithError(url("admin/boletas-publicas/{$periodoId}"), $msg);
    }

    /** POST /admin/boletas-publicas/{periodo_id}/generar */
    public function generar($periodoId): void
    {
        $this->validateCsrf();
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $usuarioId = Session::user()['id'] ?? 0;
        $nuevas    = $this->model->generarMasivo($periodoId, $usuarioId);

        $msg = $nuevas > 0
            ? "Se generaron {$nuevas} boleta(s) nueva(s) con código de acceso."
            : 'No hay boletas nuevas que generar (ya están todas generadas).';

        $nuevas > 0
            ? $this->redirectWithSuccess(url("admin/boletas-publicas/{$periodoId}"), $msg)
            : $this->redirectWithError(url("admin/boletas-publicas/{$periodoId}"), $msg);
    }

    /** GET /admin/boletas-publicas/{periodo_id}/imprimir — impresión de códigos de acceso */
    public function imprimir($periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $boletas = $this->model->getPorPeriodo($periodoId);

        View::setLayout('print');
        $this->view('admin/boletas-publicas/imprimir', [
            'titulo'  => 'Códigos de Acceso — ' . $periodo['nombre_display'],
            'periodo' => $periodo,
            'boletas' => $boletas,
        ]);
    }

    /** GET /admin/boletas-publicas/{periodo_id}/boletas-alumno — impresión masiva de boletas */
    public function boletasAlumno($periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $boletas     = $this->model->getPorPeriodo($periodoId);
        $boletasData = [];

        foreach ($boletas as $b) {
            $data = $this->buildBoletaData((int) $b['matricula_id'], $periodoId, (int) $periodo['anio_id']);
            if ($data) {
                $data['url_boleta'] = url("boleta/digital/{$b['matricula_id']}/{$periodoId}");
                $boletasData[] = $data;
            }
        }

        View::setLayout('print');
        $this->view('admin/boletas-publicas/boletas-alumno', [
            'titulo'      => 'Boletas — ' . $periodo['nombre_display'],
            'periodo'     => $periodo,
            'boletasData' => $boletasData,
        ]);
    }

    // ── Helpers privados ────────────────────────────────────────

    private function getPeriodo(int $periodoId): ?array
    {
        return $this->model->queryOne("
            SELECT p.id, p.numero, p.nombre_display, a.anio, a.id AS anio_id
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
            LIMIT 1
        ", [$periodoId]);
    }

    private function buildBoletaData(int $matriculaId, int $periodoId, int $anioId): ?array
    {
        $alumno   = $this->getAlumno($matriculaId);
        $periodos = $this->getPeriodosDelAnio($anioId);

        if (!$alumno || empty($periodos)) return null;

        $datosPorPeriodo = [];
        foreach ($periodos as $p) {
            $datosPorPeriodo[$p['id']] = $this->calModel->getBoletaAlumno($matriculaId, $p['id']);
        }

        return [
            'alumno'      => $alumno,
            'periodos'    => $periodos,
            'areas'       => $this->buildAreasConBimestres($datosPorPeriodo, $periodos),
            'conducta'    => $this->conductaModel->getParaBoleta($matriculaId, $anioId),
            'institucion' => config('institucion'),
        ];
    }

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
            INNER JOIN estudiantes e       ON e.id = m.estudiante_id
            INNER JOIN personas p          ON p.id = e.persona_id
            INNER JOIN secciones s         ON s.id = m.seccion_id
            INNER JOIN grados g            ON g.id = s.grado_id
            INNER JOIN niveles n           ON n.id = g.nivel_id
            INNER JOIN anios_academicos a  ON a.id = m.anio_id
            WHERE m.id = ?
            LIMIT 1
        ", [$matriculaId]);
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
                    'nota'       => $notaNum,
                    'literal'    => $notaNum !== null ? CalificacionModel::toLiteral($notaNum) : null,
                    'conclusion' => $nota['conclusion_descriptiva'] ?? null,
                ];
            }
        }

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
}
