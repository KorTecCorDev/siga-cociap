<?php

namespace App\Controllers\Director;

use App\Controllers\BaseController;
use App\Models\CalificacionModel;

/**
 * OrdenMeritoController
 * Ranking bimestral por grado.
 */
class OrdenMeritoController extends BaseController
{
    private CalificacionModel $calModel;

    public function __construct()
    {
        $this->requireRole([
            'admin', 'registro_academico',
            'director_general', 'director_ebr'
        ]);
        $this->calModel = new CalificacionModel();
    }

    /**
     * GET /director/orden-merito
     * Lista los periodos disponibles para ver el ranking.
     */
    public function index(): void
    {
        $periodos = $this->calModel->query("
            SELECT p.*, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.estado IN ('activo', 'cerrado')
            ORDER BY a.anio DESC, p.numero ASC
        ");

        $this->view('director/orden-merito', [
            'titulo'   => 'Orden de mérito',
            'periodos' => $periodos,
        ]);
    }

    /**
     * GET /director/orden-merito/{periodo_id}
     * Muestra el ranking de todos los grados en un periodo.
     */
    public function porPeriodo(string $periodoId): void
    {
        $periodoId = (int) $periodoId;

        $periodo = $this->calModel->queryOne("
            SELECT p.*, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
        ", [$periodoId]);

        if (!$periodo) {
            $this->redirectWithError(
                url('director/orden-merito'),
                'Periodo no encontrado.'
            );
        }

        // Obtener grados con estudiantes calificados
        $grados = $this->calModel->query("
            SELECT DISTINCT
                g.id,
                g.numero,
                g.nombre_display,
                n.nombre AS nivel_nombre,
                n.codigo AS nivel_codigo
            FROM matriculas m
            INNER JOIN secciones s  ON s.id = m.seccion_id
            INNER JOIN grados g     ON g.id = s.grado_id
            INNER JOIN niveles n    ON n.id = g.nivel_id
            INNER JOIN calificaciones cal ON cal.matricula_id = m.id
            WHERE cal.periodo_id = ?
              AND m.estado = 'aprobada'
            ORDER BY n.id, g.numero
        ", [$periodoId]);

        // Calcular ranking por cada grado
        $ranking = [];
        foreach ($grados as $grado) {
            $ranking[$grado['id']] = [
                'grado'      => $grado,
                'estudiantes'=> $this->calcularRanking(
                    $grado['id'],
                    $periodoId
                ),
            ];
        }

        $this->view('director/orden-merito-periodo', [
            'titulo'   => 'Orden de mérito — ' . $periodo['nombre_display'],
            'periodo'  => $periodo,
            'ranking'  => $ranking,
        ]);
    }

    /**
     * Calcula el ranking de estudiantes de un grado en un periodo.
     * Promedia TODAS las áreas con igual peso.
     */
    private function calcularRanking(int $gradoId, int $periodoId): array
    {
        $estudiantes = $this->calModel->query("
            SELECT
                m.id AS matricula_id,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                p.dni,
                s.nombre AS seccion_nombre,
                ROUND(AVG(cal.nota_numerica), 2) AS promedio_general
            FROM matriculas m
            INNER JOIN estudiantes e    ON e.id  = m.estudiante_id
            INNER JOIN personas p       ON p.id  = e.persona_id
            INNER JOIN secciones s      ON s.id  = m.seccion_id
            INNER JOIN grados g         ON g.id  = s.grado_id
            INNER JOIN calificaciones cal ON cal.matricula_id = m.id
            WHERE g.id           = ?
              AND cal.periodo_id = ?
              AND m.estado       = 'aprobada'
            GROUP BY m.id, p.apellido_paterno, p.apellido_materno,
                     p.nombres, p.dni, s.nombre
            ORDER BY promedio_general DESC
        ", [$gradoId, $periodoId]);

        // Agregar puesto
        foreach ($estudiantes as $i => &$est) {
            $est['puesto'] = $i + 1;
            $est['media_beca'] = ($i === 0); // puesto 1 = media beca
        }

        return $estudiantes;
    }
}