<?php

namespace App\Controllers\Padre;

use App\Controllers\BaseController;
use App\Models\CalificacionModel;
use App\Models\ConductaModel;
use Core\Session;

/**
 * PanelController
 * Panel del padre de familia.
 */
class PanelController extends BaseController
{
    private CalificacionModel $calModel;
    private ConductaModel     $conductaModel;

    public function __construct()
    {
        $this->requireRole(['padre', 'admin', 'registro_academico']);
        $this->calModel      = new CalificacionModel();
        $this->conductaModel = new ConductaModel();
    }

    /**
     * GET /padre/inicio
     * Panel principal del padre.
     */
    public function index(): void
    {
        $user     = Session::user();
        $hijo     = $this->getHijo($user['id']);
        $periodo  = $this->getPeriodoActivo();
        $alertas  = $hijo ? $this->getAlertas($hijo['matricula_id']) : [];

        $this->view('padre/inicio', [
            'titulo'  => 'Panel del padre',
            'hijo'    => $hijo,
            'periodo' => $periodo,
            'alertas' => $alertas,
        ]);
    }

    /**
     * GET /padre/notas
     * Ver notas del hijo en el periodo activo.
     */
    public function notas(): void
    {
        $user    = Session::user();
        $hijo    = $this->getHijo($user['id']);
        $periodo = $this->getPeriodoActivo();

        if (!$hijo || !$periodo) {
            $this->redirectWithError(
                url('padre/inicio'),
                'No se encontró información del estudiante.'
            );
        }

        $notas = $this->calModel->getBoletaAlumno(
            $hijo['matricula_id'],
            $periodo['id']
        );

        // Agrupar notas por área
        $areas = [];
        foreach ($notas as $nota) {
            $areaNombre = $nota['nombre_boleta'] ?? $nota['area_nombre'];
            if ($nota['alias_boleta']) {
                $areaNombre .= ' ' . $nota['alias_boleta'];
            }
            $areas[$areaNombre][] = $nota;
        }

        $conducta = $this->conductaModel->getParaPeriodo(
            $hijo['matricula_id'],
            $periodo['id']
        );

        $this->view('padre/notas', [
            'titulo'   => 'Notas de ' . $hijo['nombres'],
            'hijo'     => $hijo,
            'periodo'  => $periodo,
            'areas'    => $areas,
            'conducta' => $conducta,
        ]);
    }

    /**
     * GET /padre/alertas
     * Ver alertas del tutor.
     */
    public function alertas(): void
    {
        $user    = Session::user();
        $hijo    = $this->getHijo($user['id']);
        $alertas = $hijo ? $this->getAlertas($hijo['matricula_id']) : [];

        $this->view('padre/alertas', [
            'titulo'  => 'Alertas',
            'hijo'    => $hijo,
            'alertas' => $alertas,
        ]);
    }

    // ── Métodos privados ─────────────────────────────────────

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

    private function getHijo(int $usuarioId): ?array
    {
        return $this->calModel->queryOne("
            SELECT
                e.id            AS estudiante_id,
                m.id            AS matricula_id,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno,
                p.dni,
                CONCAT(
                    p.apellido_paterno, ' ',
                    p.apellido_materno, ', ',
                    p.nombres
                )               AS nombre_completo,
                g.nombre_display AS grado_nombre,
                s.nombre        AS seccion_nombre,
                n.nombre        AS nivel_nombre,
                n.codigo        AS nivel_codigo,
                n.escala_boleta,
                m.estado        AS estado_matricula
            FROM usuarios u
            INNER JOIN personas pa      ON pa.id = u.persona_id
            INNER JOIN apoderados ap    ON ap.persona_id = pa.id
            INNER JOIN vinculo_familiar vf ON vf.apoderado_id = ap.id
            INNER JOIN estudiantes e    ON e.id = vf.estudiante_id
            INNER JOIN personas p       ON p.id = e.persona_id
            INNER JOIN matriculas m     ON m.estudiante_id = e.id
            INNER JOIN secciones s      ON s.id = m.seccion_id
            INNER JOIN grados g         ON g.id = s.grado_id
            INNER JOIN niveles n        ON n.id = g.nivel_id
            INNER JOIN anios_academicos a ON a.id = m.anio_id
            WHERE u.id      = ?
              AND a.estado  = 'activo'
              AND m.estado  = 'aprobada'
            LIMIT 1
        ", [$usuarioId]);
    }

    private function getAlertas(int $matriculaId): array
    {
        return $this->calModel->query("
            SELECT
                al.id,
                al.tipo,
                al.mensaje,
                al.leida,
                al.created_at,
                CONCAT(pt.nombres, ' ', pt.apellido_paterno) AS tutor_nombre
            FROM alertas al
            INNER JOIN usuarios ut  ON ut.id  = al.tutor_id
            INNER JOIN personas pt  ON pt.id  = ut.persona_id
            WHERE al.matricula_id = ?
            ORDER BY al.created_at DESC
        ", [$matriculaId]);
    }
}