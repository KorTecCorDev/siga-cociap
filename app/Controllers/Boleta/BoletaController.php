<?php

namespace App\Controllers\Boleta;

use App\Controllers\BaseController;
use App\Models\CalificacionModel;
use Core\Session;
use Core\View;

class BoletaController extends BaseController
{
    private CalificacionModel $calModel;

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
        $this->calModel = new CalificacionModel();
    }

    /**
     * GET /boleta/{matricula_id}/{periodo_id}
     * Muestra la boleta de calificaciones lista para imprimir.
     */
    public function ver($matriculaId, $periodoId): void
    {
        $matriculaId = (int) $matriculaId;
        $periodoId   = (int) $periodoId;

        // Si es padre, verificar que la matrícula le pertenece
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

        $notas = $this->calModel->getBoletaAlumno($matriculaId, $periodoId);

        $areas = [];
        foreach ($notas as $nota) {
            $nombreArea = $nota['nombre_boleta'] ?? $nota['area_nombre'];
            if (!empty($nota['alias_boleta'])) {
                $nombreArea .= ' ' . $nota['alias_boleta'];
            }
            $areas[$nombreArea][] = $nota;
        }

        View::setLayout('print');
        $this->view('boleta/alumno', [
            'titulo'      => 'Boleta — ' . $alumno['nombre_completo'],
            'alumno'      => $alumno,
            'periodo'     => $periodo,
            'areas'       => $areas,
            'institucion' => config('institucion'),
        ]);
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
                a.anio,
                CONCAT(p.nombre_display, ' — ', a.anio) AS nombre_display
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
            LIMIT 1
        ", [$periodoId]);
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
