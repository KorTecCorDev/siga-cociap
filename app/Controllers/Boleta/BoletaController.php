<?php

namespace App\Controllers\Boleta;

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

class BoletaController extends BaseController
{
    private CalificacionModel    $calModel;
    private ConductaModel        $conductaModel;
    private DirectorEbrModel     $dirModel;
    private AsistenciaModel      $asistenciaModel;
    private OmisionCriterioModel $omisionModel;
    private ExoneracionModel     $exoModel;

    public function __construct()
    {
        $this->calModel        = new CalificacionModel();
        $this->conductaModel   = new ConductaModel();
        $this->dirModel        = new DirectorEbrModel();
        $this->asistenciaModel = new AsistenciaModel();
        $this->omisionModel    = new OmisionCriterioModel();
        $this->exoModel        = new ExoneracionModel();
    }

    /**
     * GET /boleta/{matricula_id}/{periodo_id}
     * Muestra la boleta anual con los 4 bimestres del año académico.
     */
    public function ver($matriculaId, $periodoId): void
    {
        $this->requireRole([
            'admin',
            'director_general',
            'director_ebr',
            'registro_academico',
            'secretaria',
            'padre',
        ]);
        $data = $this->buildBoletaData((int) $matriculaId, (int) $periodoId);

        View::setLayout('print');
        $this->view('boleta/alumno', array_merge($data, [
            'titulo'     => 'Boleta — ' . $data['alumno']['nombre_completo'],
            'url_boleta' => url("boleta/digital/{$matriculaId}/{$periodoId}"),
        ]));
    }

    /**
     * GET /boleta/digital/{token}
     * Vista digital pública sin login — resuelve token → matricula + periodo.
     */
    public function verDigitalToken(string $token): void
    {
        ['matricula_id' => $matriculaId, 'periodo_id' => $periodoId] = $this->resolveToken($token);

        (new BoletaPublicaModel())->registrarVisitaToken($matriculaId, $periodoId);

        $data = $this->buildBoletaData($matriculaId, $periodoId);

        View::setLayout('digital');
        $this->view('boleta/digital', array_merge($data, [
            'titulo'     => 'Boleta Digital — ' . $data['alumno']['nombre_completo'],
            'url_boleta' => url("boleta/digital/{$token}"),
        ]));
    }

    /**
     * GET /boleta/ver/{token}
     * Vista imprimible pública sin login — resuelve token → matricula + periodo.
     */
    public function verToken(string $token): void
    {
        ['matricula_id' => $matriculaId, 'periodo_id' => $periodoId] = $this->resolveToken($token);

        $data = $this->buildBoletaData($matriculaId, $periodoId);

        View::setLayout('print');
        $this->view('boleta/alumno', array_merge($data, [
            'titulo'     => 'Boleta — ' . $data['alumno']['nombre_completo'],
            'url_boleta' => url("boleta/digital/{$token}"),
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
            'titulo' => 'Boleta Digital — ' . $data['alumno']['nombre_completo'],
        ]));
    }

    /**
     * GET /docente/boleta/{matricula_id}
     * Boleta DIGITAL para el docente. Validada por alcance: solo alumnos de un
     * nivel donde el docente tiene carga activa. El periodo se resuelve solo.
     */
    public function verDigitalDocente($matriculaId): void
    {
        $this->requireRole(['docente', 'admin']);
        $matriculaId = (int) $matriculaId;
        $periodoId   = $this->resolverBoletaDocente($matriculaId);
        $esBorrador  = $this->estadoBoletaDePeriodo($periodoId) !== 'oficial';

        $data = $this->buildBoletaData($matriculaId, $periodoId);

        View::setLayout('digital');
        $this->view('boleta/digital', array_merge($data, [
            'titulo'         => 'Boleta Digital — ' . $data['alumno']['nombre_completo'],
            'url_boleta'     => url("docente/boleta/{$matriculaId}"),
            'vistaPrevia'    => $esBorrador,
        ]));
    }

    /**
     * GET /docente/boleta/{matricula_id}/imprimir
     * Boleta IMPRIMIBLE (A4) para el docente. Mismo alcance que verDigitalDocente.
     */
    public function verImprimirDocente($matriculaId): void
    {
        $this->requireRole(['docente', 'admin']);
        $matriculaId = (int) $matriculaId;
        $periodoId   = $this->resolverBoletaDocente($matriculaId);
        $esBorrador  = $this->estadoBoletaDePeriodo($periodoId) !== 'oficial';

        $data = $this->buildBoletaData($matriculaId, $periodoId);

        View::setLayout('print');
        $this->view('boleta/alumno', array_merge($data, [
            'titulo'         => 'Boleta — ' . $data['alumno']['nombre_completo'],
            'url_boleta'     => url("docente/boleta/{$matriculaId}"),
            'vistaPrevia'    => $esBorrador,
        ]));
    }

    /**
     * Valida que el docente actual pueda ver la boleta de $matriculaId (alumno en
     * un nivel donde tiene carga activa) y devuelve el periodo a mostrar: el más
     * reciente con notas bloqueadas, con fallback al primer periodo del año.
     * Responde 403 si está fuera de alcance, 404 si no hay periodos.
     */
    private function resolverBoletaDocente(int $matriculaId): int
    {
        $docenteId = (int) Session::user()['id'];

        // Alcance: la matrícula existe, no está desactivada y su NIVEL coincide con
        // un nivel donde el docente tiene carga activa. Evita abrir boletas fuera
        // de alcance manipulando el id en la URL.
        $mat = $this->calModel->queryOne("
            SELECT m.id, m.anio_id
            FROM matriculas m
            INNER JOIN secciones s ON s.id = m.seccion_id
            INNER JOIN grados g    ON g.id = s.grado_id
            WHERE m.id = ?
              AND m.estado <> 'desactivado'
              AND g.nivel_id IN (
                  SELECT DISTINCT g2.nivel_id
                  FROM cargas_academicas ca
                  INNER JOIN secciones s2 ON s2.id = ca.seccion_id
                  INNER JOIN grados g2    ON g2.id = s2.grado_id
                  WHERE ca.docente_id = ? AND ca.estado = 'activa'
              )
            LIMIT 1
        ", [$matriculaId, $docenteId]);

        if (!$mat) {
            http_response_code(403);
            $this->view('shared/403');
            exit;
        }

        $anioId = (int) $mat['anio_id'];

        // Solo periodos PUBLICABLES: cerrado (OFICIAL) o activo con boletas
        // aprobadas (BORRADOR, Hito A). Un bimestre en registro aun NO tiene
        // boleta para el docente -> se muestra hasta el ultimo publicable.
        $periodo = $this->calModel->queryOne("
            SELECT p.id
            FROM periodos p
            WHERE p.anio_id = ?
              AND (p.estado = 'cerrado'
                   OR (p.estado = 'activo' AND p.boletas_aprobadas_en IS NOT NULL))
              AND EXISTS (
                  SELECT 1 FROM calificaciones cal
                  INNER JOIN bloqueos_competencia bc
                      ON bc.carga_id = cal.carga_id
                     AND bc.competencia_id = cal.competencia_id
                     AND bc.periodo_id = cal.periodo_id
                  WHERE cal.matricula_id = ? AND cal.periodo_id = p.id
              )
            ORDER BY p.numero DESC
            LIMIT 1
        ", [$anioId, $matriculaId]);

        if (!$periodo) {
            http_response_code(404);
            require VIEW_PATH . '/shared/404.php';
            exit;
        }

        return (int) $periodo['id'];
    }

    /** Estado de boleta ('registro'|'borrador'|'oficial') de un periodo. */
    private function estadoBoletaDePeriodo(int $periodoId): string
    {
        $p = $this->calModel->queryOne(
            "SELECT estado, boletas_aprobadas_en FROM periodos WHERE id = ? LIMIT 1",
            [$periodoId]
        );
        return boleta_estado_bimestre($p['estado'] ?? null, $p['boletas_aprobadas_en'] ?? null);
    }

    // ── Datos compartidos entre ver() y verDigital() ────────────

    private function buildBoletaData(int $matriculaId, int $periodoId): array
    {
        // Retorno de grado: la boleta SIEMPRE se rotula con la matrícula oficial
        // (grado/sección SIAGIE) y sus notas se leen por unión de las matrículas
        // involucradas (operativa + oficial). En el caso normal, identidad y
        // única fuente son la propia matrícula.
        $ctx       = $this->calModel->boletaContexto($matriculaId);
        $identidad = (int) $ctx['identidad'];
        $fuentes   = $ctx['fuentes'];

        if (Session::hasRole('padre')) {
            $hijo   = $this->getHijoPadre(Session::user()['id']);
            $hijoOk = $hijo
                && (int) $this->calModel->boletaContexto((int) $hijo['matricula_id'])['identidad'] === $identidad;
            if (!$hijoOk) {
                http_response_code(403);
                $this->view('shared/403');
                exit;
            }
        }

        $alumno  = $this->getAlumno($identidad);
        $periodo = $this->getPeriodo($periodoId);

        if (!$alumno || !$periodo) {
            $this->redirectWithError(
                url('dashboard'),
                'No se encontró la boleta solicitada.'
            );
        }

        $anioId  = (int) $periodo['anio_id'];
        $periodos = $this->getPeriodosDelAnio($anioId);

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

    /**
     * Resuelve un token a matricula_id + periodo_id.
     * Elige el período más reciente con competencias bloqueadas;
     * si no hay ninguno, usa el primer período del año.
     * Termina con 404 si el token no existe o no hay períodos.
     */
    private function resolveToken(string $token): array
    {
        // El token es bin2hex(random_bytes(16)) = 32 hex en minúsculas. Validar
        // el formato antes de consultar rechaza basura/payloads de inmediato,
        // con el mismo 404 que un token inexistente. No afecta los QR ya
        // emitidos: todos los tokens vigentes cumplen este patrón.
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            http_response_code(404);
            require VIEW_PATH . '/shared/404.php';
            exit;
        }

        // Una matrícula 'desactivado' (baja o traslado) no expone su boleta por
        // token aunque el QR impreso siga circulando: se trata como inexistente.
        $matricula = $this->calModel->queryOne(
            "SELECT id, anio_id FROM matriculas
             WHERE token_acceso = ? AND estado <> 'desactivado' LIMIT 1",
            [$token]
        );

        if (!$matricula) {
            http_response_code(404);
            require VIEW_PATH . '/shared/404.php';
            exit;
        }

        $matriculaId = (int) $matricula['id'];
        $anioId      = (int) $matricula['anio_id'];

        $periodo = $this->calModel->queryOne("
            SELECT p.id
            FROM periodos p
            WHERE p.anio_id = ?
              AND EXISTS (
                  SELECT 1
                  FROM calificaciones cal
                  INNER JOIN bloqueos_competencia bc
                      ON bc.carga_id       = cal.carga_id
                     AND bc.competencia_id = cal.competencia_id
                     AND bc.periodo_id     = cal.periodo_id
                  WHERE cal.matricula_id = ? AND cal.periodo_id = p.id
              )
            ORDER BY p.numero DESC
            LIMIT 1
        ", [$anioId, $matriculaId]);

        if (!$periodo) {
            $periodo = $this->calModel->queryOne(
                "SELECT id FROM periodos WHERE anio_id = ? ORDER BY numero ASC LIMIT 1",
                [$anioId]
            );
        }

        if (!$periodo) {
            http_response_code(404);
            require VIEW_PATH . '/shared/404.php';
            exit;
        }

        return ['matricula_id' => $matriculaId, 'periodo_id' => (int) $periodo['id']];
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
