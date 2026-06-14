<?php

namespace App\Controllers\Docente;

use App\Controllers\BaseController;
use App\Models\CalificacionModel;
use App\Models\TransversalModel;
use Core\Session;
use Core\View;

/**
 * PanelController
 * Dashboard del docente y nómina de matriculados de su(s) nivel(es).
 */
class PanelController extends BaseController
{
    private CalificacionModel $calModel;
    private TransversalModel  $transModel;

    public function __construct()
    {
        $this->requireRole(['docente', 'admin']);
        $this->calModel   = new CalificacionModel();
        $this->transModel = new TransversalModel();
    }

    /**
     * GET /docente/inicio — dashboard del docente.
     */
    public function index(): void
    {
        $user    = Session::user();
        $did     = (int) $user['id'];
        $periodo = $this->getPeriodoActivo();
        $pid     = $periodo ? (int) $periodo['id'] : 0;

        $cargas  = $pid ? $this->getCargasResumen($did, $pid) : [];

        // KPIs / resumen de cargas
        $sumTotal = $sumBloq = $completas = $sinCriterios = 0;
        $pendientes = [];
        foreach ($cargas as $c) {
            $total = (int) $c['total_comp'];
            $bloq  = (int) $c['bloq'];
            $crit  = (int) $c['con_criterios'];
            $sumTotal += $total;
            $sumBloq  += $bloq;
            if ($total > 0 && $bloq >= $total) {
                $completas++;
            }
            if ($bloq === 0 && $crit === 0) {
                $sinCriterios++;
            }
            // Lista de pendientes: cargas que aún no están completas.
            if ($total === 0 || $bloq < $total) {
                $pendientes[] = [
                    'id'      => (int) $c['id'],
                    'nombre'  => $c['nombre_display'] ?? '—',
                    'seccion' => $c['grado_nombre'] . ' ' . $c['seccion_nombre'],
                    'motivo'  => ($bloq === 0 && $crit === 0)
                        ? 'Sin criterios'
                        : 'Faltan ' . max(0, $total - $bloq) . ' de ' . $total,
                    'critico' => ($bloq === 0 && $crit === 0),
                ];
            }
        }
        $avance = $sumTotal > 0 ? (int) round($sumBloq / $sumTotal * 100) : 0;

        // Días para el cierre (limite_notas)
        $diasCierre = null;
        if ($periodo && !empty($periodo['limite_notas'])) {
            $diasCierre = (int) ceil(
                (strtotime($periodo['limite_notas']) - time()) / 86400
            );
        }

        // Card de Tutoría (solo tutores del año activo)
        $tutoria      = null;
        $seccionTutor = $this->transModel->getSeccionDelTutor($did);
        if ($seccionTutor && $periodo) {
            $sid    = (int) $seccionTutor['id'];
            $estado = $this->transModel->estadoCargasSeccion($sid, $pid);
            $cierre = $this->transModel->getCierreVigente($sid, $pid);
            $listo  = $estado['total'] > 0 && $estado['bloqueadas'] >= $estado['total'];
            $tutoria = [
                'seccion'    => $seccionTutor,
                'total'      => $estado['total'],
                'bloqueadas' => $estado['bloqueadas'],
                'cierre'     => $cierre,
                'listo'      => $listo,
                'pendientes' => ($listo && !$cierre)
                    ? $this->transModel->conclusionesObligatoriasPendientes(
                        $sid, $pid, $seccionTutor['nivel_codigo']
                      )
                    : 0,
            ];
        }

        // Niveles del docente + resumen de nómina
        $niveles      = $this->getNivelesDocente($did);
        $nominaResumen = $this->getNominaResumen($niveles);
        $totalNomina  = array_sum(array_column($nominaResumen, 'n'));

        // Horario de la semana
        $horario = $this->getHorario($did);

        $this->view('docente/inicio', [
            'titulo'        => 'Inicio',
            'periodo'       => $periodo,
            'cargas'        => $cargas,
            'nCargas'       => count($cargas),
            'avance'        => $avance,
            'sumTotal'      => $sumTotal,
            'sumBloq'       => $sumBloq,
            'completas'     => $completas,
            'sinCriterios'  => $sinCriterios,
            'diasCierre'    => $diasCierre,
            'pendientes'    => $pendientes,
            'tutoria'       => $tutoria,
            'niveles'       => $niveles,
            'nominaResumen' => $nominaResumen,
            'totalNomina'   => $totalNomina,
            'horario'       => $horario,
            'page_scripts'  => [],
        ]);
    }

    /**
     * GET /docente/nomina — buscador en vivo de matriculados (aprobados) de los
     * niveles del docente + selector para imprimir la nómina de una sección.
     * Nunca expone el DNI (dato sensible de consulta restringida).
     */
    public function nomina(): void
    {
        $user    = Session::user();
        $did     = (int) $user['id'];
        $niveles = $this->getNivelesDocente($did);

        $alumnos = $this->getMatriculados($niveles);

        // Lista de secciones (para el selector de impresión), única y ordenada.
        $secciones = [];
        foreach ($alumnos as $a) {
            $sid = (int) $a['seccion_id'];
            if (!isset($secciones[$sid])) {
                $secciones[$sid] = [
                    'seccion_id'     => $sid,
                    'nivel_nombre'   => $a['nivel_nombre'],
                    'grado_nombre'   => $a['grado_nombre'],
                    'seccion_nombre' => $a['seccion_nombre'],
                    'n'              => 0,
                ];
            }
            $secciones[$sid]['n']++;
        }

        $this->view('docente/nomina', [
            'titulo'       => 'Nómina de matriculados',
            'alumnos'      => $alumnos,
            'secciones'    => array_values($secciones),
            'total'        => count($alumnos),
            'page_scripts' => ['nomina'],
        ]);
    }

    /**
     * GET /docente/nomina/{seccion_id}/imprimir — nómina A4 de una sección.
     * Nunca incluye DNI (dato sensible de consulta restringida).
     */
    public function nominaImprimir(string $seccionId): void
    {
        $user      = Session::user();
        $did       = (int) $user['id'];
        $seccionId = (int) $seccionId;
        $niveles   = $this->getNivelesDocente($did);
        $nivelIds  = array_map('intval', array_column($niveles, 'id'));

        $seccion = $this->calModel->queryOne("
            SELECT s.id, s.nombre AS seccion_nombre,
                   g.numero AS grado_numero, g.nombre_display AS grado_nombre,
                   n.id AS nivel_id, n.nombre AS nivel_nombre
            FROM secciones s
            INNER JOIN grados g  ON g.id = s.grado_id
            INNER JOIN niveles n ON n.id = g.nivel_id
            WHERE s.id = ?
        ", [$seccionId]);

        // Autorización: la sección debe pertenecer a un nivel del docente.
        if (!$seccion || !in_array((int) $seccion['nivel_id'], $nivelIds, true)) {
            $this->redirectWithError(url('docente/nomina'), 'Sección no disponible.');
        }

        $alumnos = $this->getMatriculados($niveles, $seccionId);

        View::setLayout('print');
        $this->view('docente/nomina-imprimir', [
            'titulo'  => 'Nómina ' . $seccion['grado_nombre'] . ' ' . $seccion['seccion_nombre'],
            'seccion' => $seccion,
            'alumnos' => $alumnos,
        ]);
    }

    // ── Privados ─────────────────────────────────────────────────

    private function getPeriodoActivo(): ?array
    {
        return $this->calModel->queryOne("
            SELECT p.*, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.estado = 'activo'
            LIMIT 1
        ");
    }

    private function getNivelesDocente(int $docenteId): array
    {
        return $this->calModel->query("
            SELECT DISTINCT n.id, n.nombre, n.codigo
            FROM cargas_academicas ca
            INNER JOIN secciones s ON s.id = ca.seccion_id
            INNER JOIN grados g    ON g.id = s.grado_id
            INNER JOIN niveles n   ON n.id = g.nivel_id
            WHERE ca.docente_id = ? AND ca.estado = 'activa'
            ORDER BY n.id
        ", [$docenteId]);
    }

    /** Resumen de cada carga: total/bloqueadas/con-criterios (solo propias). */
    private function getCargasResumen(int $docenteId, int $periodoId): array
    {
        return $this->calModel->query("
            SELECT ca.id, ca.horas_semanales,
                   s.nombre          AS seccion_nombre,
                   g.nombre_display  AS grado_nombre,
                   n.nombre          AS nivel_nombre,
                   CASE WHEN s.es_unidocente = 1 THEN a.nombre
                        ELSE COALESCE(sa.nombre, a.nombre) END AS nombre_display,
                   (
                       SELECT COUNT(DISTINCT c2.id) FROM competencias c2
                       WHERE (ca.subarea_id IS NOT NULL AND c2.subarea_id = ca.subarea_id)
                          OR (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL AND c2.area_id = ca.area_id)
                   ) AS total_comp,
                   (
                       SELECT COUNT(*) FROM bloqueos_competencia bc
                       WHERE bc.carga_id = ca.id AND bc.periodo_id = ?
                         AND bc.competencia_id IN (
                             SELECT c3.id FROM competencias c3
                             WHERE (ca.subarea_id IS NOT NULL AND c3.subarea_id = ca.subarea_id)
                                OR (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL AND c3.area_id = ca.area_id)
                         )
                   ) AS bloq,
                   (
                       SELECT COUNT(DISTINCT cr.competencia_id) FROM criterios cr
                       WHERE cr.carga_id = ca.id AND cr.periodo_id = ? AND cr.eliminado_en IS NULL
                         AND cr.competencia_id IN (
                             SELECT c4.id FROM competencias c4
                             WHERE (ca.subarea_id IS NOT NULL AND c4.subarea_id = ca.subarea_id)
                                OR (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL AND c4.area_id = ca.area_id)
                         )
                   ) AS con_criterios
            FROM cargas_academicas ca
            INNER JOIN secciones s ON s.id = ca.seccion_id
            INNER JOIN grados g    ON g.id = s.grado_id
            INNER JOIN niveles n   ON n.id = g.nivel_id
            LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
            LEFT  JOIN areas a     ON a.id  = COALESCE(ca.area_id, sa.area_id)
            WHERE ca.docente_id = ? AND ca.estado = 'activa'
            ORDER BY n.id, g.numero, s.nombre, a.orden
        ", [$periodoId, $periodoId, $docenteId]);
    }

    /** Conteo de matriculados aprobados por sección de los niveles dados. */
    private function getNominaResumen(array $niveles): array
    {
        $ids = array_map('intval', array_column($niveles, 'id'));
        if (empty($ids)) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        return $this->calModel->query("
            SELECT n.id AS nivel_id, n.nombre AS nivel_nombre,
                   g.numero AS grado_numero, g.nombre_display AS grado_nombre,
                   s.id AS seccion_id, s.nombre AS seccion_nombre,
                   COUNT(*) AS n
            FROM matriculas m
            INNER JOIN secciones s ON s.id = m.seccion_id
            INNER JOIN grados g    ON g.id = s.grado_id
            INNER JOIN niveles n   ON n.id = g.nivel_id
            WHERE m.estado = 'aprobada' AND m.tipo != 'trasladado'
              AND n.id IN ($ph)
            GROUP BY s.id
            ORDER BY n.id, g.numero, s.nombre
        ", $ids);
    }

    /**
     * Matriculados aprobados de los niveles dados (o de una sección concreta),
     * con su apoderado responsable (vinculo_familiar.es_responsable = 1).
     */
    private function getMatriculados(array $niveles, int $seccionId = 0): array
    {
        $ids = array_map('intval', array_column($niveles, 'id'));
        if (empty($ids)) {
            return [];
        }
        $ph     = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $filtroSeccion = '';
        if ($seccionId > 0) {
            $filtroSeccion = ' AND s.id = ?';
            $params[]      = $seccionId;
        }

        return $this->calModel->query("
            SELECT m.id AS matricula_id,
                   p.apellido_paterno, p.apellido_materno, p.nombres,
                   s.id AS seccion_id, s.nombre AS seccion_nombre,
                   g.numero AS grado_numero, g.nombre_display AS grado_nombre,
                   n.id AS nivel_id, n.nombre AS nivel_nombre,
                   TRIM(CONCAT(
                       COALESCE(ap.apellido_paterno, ''), ' ',
                       COALESCE(ap.apellido_materno, ''), ' ',
                       COALESCE(ap.nombres, '')
                   )) AS apoderado_nombre,
                   ap.telefono AS apoderado_telefono
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            INNER JOIN secciones s   ON s.id = m.seccion_id
            INNER JOIN grados g      ON g.id = s.grado_id
            INNER JOIN niveles n     ON n.id = g.nivel_id
            LEFT JOIN vinculo_familiar vf
                ON  vf.estudiante_id = e.id
                AND vf.es_responsable = 1
                AND vf.id = (
                    SELECT MIN(vf2.id) FROM vinculo_familiar vf2
                    WHERE vf2.estudiante_id = e.id AND vf2.es_responsable = 1
                )
            LEFT JOIN apoderados apo ON apo.id = vf.apoderado_id
            LEFT JOIN personas ap    ON ap.id = apo.persona_id
            WHERE m.estado = 'aprobada' AND m.tipo != 'trasladado'
              AND n.id IN ($ph)$filtroSeccion
            ORDER BY n.id, g.numero, s.nombre,
                     p.apellido_paterno, p.apellido_materno, p.nombres
        ", $params);
    }

    /** Sesiones de horario del docente, ordenadas por día y bloque. */
    private function getHorario(int $docenteId): array
    {
        return $this->calModel->query("
            SELECT bh.dia_semana, bh.numero_bloque, bh.hora_inicio, bh.hora_fin,
                   g.nombre_display AS grado_nombre, s.nombre AS seccion_nombre,
                   CASE WHEN s.es_unidocente = 1 THEN a.nombre
                        ELSE COALESCE(sa.nombre, a.nombre) END AS area_nombre
            FROM sesiones_horario sh
            INNER JOIN bloques_horario bh ON bh.id = sh.bloque_id
            INNER JOIN cargas_academicas ca ON ca.id = sh.carga_id AND ca.estado = 'activa'
            INNER JOIN secciones s ON s.id = sh.seccion_id
            INNER JOIN grados g    ON g.id = s.grado_id
            LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
            LEFT  JOIN areas a     ON a.id  = COALESCE(ca.area_id, sa.area_id)
            WHERE sh.docente_id = ?
            ORDER BY FIELD(bh.dia_semana,'lunes','martes','miercoles','jueves','viernes'),
                     bh.numero_bloque
        ", [$docenteId]);
    }
}
