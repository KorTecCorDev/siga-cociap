<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AsistenciaModel;
use App\Models\BoletaPublicaModel;
use App\Models\CalificacionModel;
use App\Models\ConductaModel;
use App\Models\DirectorEbrModel;
use App\Models\ExoneracionModel;
use App\Models\OmisionCriterioModel;
use Core\Session;
use Core\View;

class BoletaPublicaController extends BaseController
{
    private BoletaPublicaModel   $model;
    private CalificacionModel    $calModel;
    private ConductaModel        $conductaModel;
    private DirectorEbrModel     $dirModel;
    private AsistenciaModel      $asistenciaModel;
    private OmisionCriterioModel $omisionModel;
    private ExoneracionModel     $exoModel;

    public function __construct()
    {
        $this->requireRole(['admin', 'registro_academico']);
        $this->model           = new BoletaPublicaModel();
        $this->calModel        = new CalificacionModel();
        $this->conductaModel   = new ConductaModel();
        $this->dirModel        = new DirectorEbrModel();
        $this->asistenciaModel = new AsistenciaModel();
        $this->omisionModel    = new OmisionCriterioModel();
        $this->exoModel        = new ExoneracionModel();
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

        $boletas   = $this->model->getPorPeriodo($periodoId);
        $secciones = $this->model->getSeccionesParaPeriodo($periodoId);

        // total_aprobables ya viene calculado por sección; el total global
        // del periodo es la suma. Evita la query separada que vivía aquí.
        $totalAprobadas    = array_sum(array_map(fn($s) => (int) $s['total_aprobables'], $secciones));
        $totalConNovedades = count(array_filter($boletas, fn($b) => (int) $b['novedades_count'] > 0));

        $this->view('admin/boletas-publicas/periodo', [
            'titulo'            => 'Boletas Públicas — ' . $periodo['nombre_display'],
            'periodo'           => $periodo,
            'boletas'           => $boletas,
            'secciones'         => $secciones,
            'totalAprobadas'    => $totalAprobadas,
            'totalConNovedades' => $totalConNovedades,
        ]);
    }

    /** POST /admin/boletas-publicas/generar-tokens — genera tokens permanentes para matrículas sin token */
    public function generarTokens(): void
    {
        $this->validateCsrf();
        $count = $this->model->generarTokensActivos();
        $msg   = $count > 0
            ? "Se generaron {$count} token" . ($count !== 1 ? 's' : '') . " de acceso."
            : 'Todas las matrículas ya tienen token de acceso.';

        $count > 0
            ? $this->redirectWithSuccess(url('admin/boletas-publicas'), $msg)
            : $this->redirectWithError(url('admin/boletas-publicas'), $msg);
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

    /**
     * GET /admin/boletas-publicas/{periodo_id}/imprimir[?seccion_id=N]
     * Impresión de códigos de acceso. Opcionalmente loteable por sección.
     */
    public function imprimir($periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $seccionId = (int) $this->query('seccion_id', 0) ?: null;
        $boletas   = $this->model->getPorPeriodo($periodoId, $seccionId);

        View::setLayout('print');
        $this->view('admin/boletas-publicas/imprimir', [
            'titulo'  => 'Códigos de Acceso — ' . $periodo['nombre_display'],
            'periodo' => $periodo,
            'boletas' => $boletas,
        ]);
    }

    /**
     * GET /admin/boletas-publicas/{periodo_id}/vista-previa[?seccion_id=N]
     * Vista previa antes de la aprobación de registro académico.
     * Itera sobre las matrículas con ≥1 competencia bloqueada (set candidato
     * a generación), no sobre las que ya tienen código. Pasa $vistaPrevia=true
     * a la vista compartida boleta/alumno.php para suprimir QR y la imagen
     * de firma del director (los datos de la línea de firma se mantienen).
     * Opcionalmente loteable por sección para evitar timeouts con muchos alumnos.
     */
    public function vistaPrevia($periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $seccionId   = (int) $this->query('seccion_id', 0) ?: null;
        $matriculas  = $this->model->getMatriculasAprobadasParaBoleta($periodoId, $seccionId);
        $boletasData = [];

        foreach ($matriculas as $m) {
            $data = $this->buildBoletaData((int) $m['matricula_id'], $periodoId, (int) $periodo['anio_id']);
            if ($data) {
                // En vista previa no inyectamos url_boleta (sin QR) ni mostramos
                // firma del director; el flag lo decide en alumno.php.
                $data['vistaPrevia'] = true;
                $boletasData[] = $data;
            }
        }

        View::setLayout('print');
        $this->view('admin/boletas-publicas/vista-previa', [
            'titulo'      => 'Vista previa — ' . $periodo['nombre_display'],
            'periodo'     => $periodo,
            'boletasData' => $boletasData,
        ]);
    }

    /**
     * GET /admin/boletas-publicas/{periodo_id}/boletas-alumno[?seccion_id=N]
     * Impresión masiva de boletas. Opcionalmente loteable por sección para
     * evitar que el render de 200+ boletas dispare timeouts.
     */
    public function boletasAlumno($periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $seccionId   = (int) $this->query('seccion_id', 0) ?: null;
        $boletas     = $this->model->getPorPeriodo($periodoId, $seccionId);
        $boletasData = [];

        foreach ($boletas as $b) {
            $data = $this->buildBoletaData((int) $b['matricula_id'], $periodoId, (int) $periodo['anio_id']);
            if ($data) {
                $data['url_boleta'] = !empty($b['token_acceso'])
                    ? url("boleta/digital/{$b['token_acceso']}")
                    : url("boleta/digital/{$b['matricula_id']}/{$periodoId}");
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

    /**
     * GET /admin/boletas-publicas/{periodo_id}/archivar[?seccion_id=N]
     * Genera PDFs individuales en el navegador (html2pdf.js + JSZip)
     * agrupados en carpetas por sección dentro de un ZIP descargable.
     */
    public function archivar($periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $seccionId   = (int) $this->query('seccion_id', 0) ?: null;
        $boletas     = $this->model->getPorPeriodo($periodoId, $seccionId);
        $boletasData = [];

        foreach ($boletas as $b) {
            $data = $this->buildBoletaData((int) $b['matricula_id'], $periodoId, (int) $periodo['anio_id']);
            if (!$data) continue;

            $a = $data['alumno'];

            // Nombre de archivo: APELLIDOS_NOMBRES (sin DNI, mayúsculas con _)
            $partes = [
                mb_strtoupper($a['apellido_paterno']),
                mb_strtoupper($a['apellido_materno']),
                mb_strtoupper($a['nombres']),
            ];
            $data['nombre_archivo'] = str_replace(' ', '_', implode('_', $partes));

            // Carpeta jerárquica: NIVEL/GRADO_SECCION (JSZip crea subcarpetas con /)
            $nivel   = mb_strtoupper(str_replace(' ', '_', trim($a['nivel_nombre'])));
            $grado   = mb_strtoupper(preg_replace('/[°\s.]+/', '', trim($a['grado_nombre'])));
            $seccion = mb_strtoupper(trim($a['seccion_nombre']));
            $data['carpeta'] = "{$nivel}/{$grado}_{$seccion}";

            $data['url_boleta'] = !empty($b['token_acceso'])
                ? url("boleta/digital/{$b['token_acceso']}")
                : url("boleta/digital/{$b['matricula_id']}/{$periodoId}");

            $boletasData[] = $data;
        }

        View::setLayout('print');
        $this->view('admin/boletas-publicas/archivar', [
            'titulo'         => 'Archivar boletas — ' . $periodo['nombre_display'],
            'periodo'        => $periodo,
            'boletasData'    => $boletasData,
            'seccionFiltro'  => $seccionId,
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
        // Retorno de grado: la boleta se rotula con la matrícula oficial
        // (grado/sección SIAGIE) y une las notas de las matrículas involucradas.
        // En el caso normal, identidad y única fuente coinciden.
        $ctx       = $this->calModel->boletaContexto($matriculaId);
        $identidad = (int) $ctx['identidad'];
        $fuentes   = $ctx['fuentes'];

        $alumno   = $this->getAlumno($identidad);
        $periodos = $this->getPeriodosDelAnio($anioId);

        if (!$alumno || empty($periodos)) return null;

        $datosPorPeriodo = [];
        foreach ($periodos as $p) {
            $rows = [];
            foreach ($fuentes as $mid) {
                $rows = array_merge($rows, $this->calModel->getBoletaAlumno((int) $mid, $p['id']));
            }
            $datosPorPeriodo[$p['id']] = $rows;
        }

        $areas  = $this->buildAreasConBimestres($datosPorPeriodo, $periodos);
        $exoData = $this->exoModel->getConCompetenciasParaBoletaUnion($fuentes, $anioId);
        $areas  = ExoneracionModel::inyectarEnAreas($areas, $exoData, $periodos);

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

    private function getTutorSeccion(int $matriculaId): ?array
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
                        'nombre'            => trim(
                            $prefijoSubarea .
                            ($nota['codigo_minedu'] ? $nota['codigo_minedu'] . '. ' : '') .
                            ($nota['nombre_corto'] ?? $nota['competencia_nombre'] ?? '')
                        ),
                        'nombre_largo'      => trim($prefijoSubarea . ($nota['competencia_nombre'] ?? '')),
                        'subarea_nombre'    => ($nota['area_tipo'] ?? '') === 'con_subareas' ? ($nota['subarea_nombre'] ?? '') : '',
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
