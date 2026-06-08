<?php

namespace App\Controllers\Director;

use App\Controllers\BaseController;
use App\Models\CalificacionModel;
use App\Models\DesempateMeritoModel;
use App\Models\DirectorEbrModel;
use App\Models\OrdenMeritoModel;
use Core\Session;
use Core\View;

/**
 * OrdenMeritoController
 * Ranking bimestral por grado.
 */
class OrdenMeritoController extends BaseController
{
    private CalificacionModel    $calModel;
    private DirectorEbrModel     $dirModel;
    private DesempateMeritoModel $desempateModel;
    private OrdenMeritoModel     $ordenMeritoModel;

    public function __construct()
    {
        $this->requireRole([
            'admin', 'registro_academico',
            'director_general', 'director_ebr'
        ]);
        $this->calModel         = new CalificacionModel();
        $this->dirModel         = new DirectorEbrModel();
        $this->desempateModel   = new DesempateMeritoModel();
        $this->ordenMeritoModel = new OrdenMeritoModel();
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
     * GET /director/orden-merito/{periodo_id}/imprimir
     * Reporte imprimible: ranking general + primeros puestos por sección.
     */
    public function imprimir(string $periodoId): void
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

        $ranking = [];
        foreach ($grados as $grado) {
            $ranking[$grado['id']] = [
                'grado'       => $grado,
                'conteos'     => $this->getConteosGrado($grado['id'], $periodoId),
                'general'     => $this->calcularRanking($grado['id'], $periodoId),
                'por_seccion' => $this->calcularRankingPorSeccion($grado['id'], $periodoId),
                'tutores'     => $this->getTutoresPorGrado($grado['id']),
            ];
        }

        // Si hay algún empate sin resolver, el reporte no es oficializable.
        $hayPendientes = false;
        foreach ($ranking as $data) {
            if ($this->rankingTienePendientes($data['general'])) {
                $hayPendientes = true;
                break;
            }
            foreach ($data['por_seccion'] as $estudiantes) {
                if ($this->rankingTienePendientes($estudiantes)) {
                    $hayPendientes = true;
                    break 2;
                }
            }
        }

        View::setLayout('print');
        $this->view('director/reporte-merito', [
            'titulo'        => 'Orden de mérito — ' . $periodo['nombre_display'] . ' ' . $periodo['anio'],
            'periodo'       => $periodo,
            'ranking'       => $ranking,
            'institucion'   => config('institucion'),
            'directorEbr'   => $this->getDirectorEbr($periodo),
            'hayPendientes' => $hayPendientes,
        ]);
    }

    /**
     * GET /director/orden-merito/{periodo_id}/desempate/{grado_id}
     * Formulario para resolver los empates irreducibles de un grado.
     */
    public function desempate(string $periodoId, string $gradoId): void
    {
        $this->requireRole(['admin', 'registro_academico']);

        $periodoId = (int) $periodoId;
        $gradoId   = (int) $gradoId;

        $periodo = $this->calModel->queryOne("
            SELECT p.*, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
        ", [$periodoId]);

        $grado = $this->calModel->queryOne("
            SELECT g.id, g.numero, g.nombre_display, n.nombre AS nivel_nombre
            FROM grados g
            INNER JOIN niveles n ON n.id = g.nivel_id
            WHERE g.id = ?
        ", [$gradoId]);

        if (!$periodo || !$grado) {
            $this->redirectWithError(
                url('director/orden-merito'),
                'Periodo o grado no encontrado.'
            );
        }

        // Detectar los grupos pendientes a partir del ranking general del grado.
        $estudiantes = $this->calcularRanking($gradoId, $periodoId);
        $grupos = [];
        foreach ($estudiantes as $est) {
            if (!empty($est['empate_pendiente'])) {
                $grupos[$est['empate_clave']][] = $est;
            }
        }

        $this->view('director/orden-merito-desempate', [
            'titulo'  => 'Resolver empate — ' . $grado['nombre_display'],
            'periodo' => $periodo,
            'grado'   => $grado,
            'grupos'  => $grupos,
        ]);
    }

    /**
     * POST /director/orden-merito/{periodo_id}/desempate/{grado_id}
     * Guarda la resolución manual de un empate irreducible (con motivo, auditada).
     */
    public function guardarDesempate(string $periodoId, string $gradoId): void
    {
        $this->requireRole(['admin', 'registro_academico']);
        $this->validateCsrf();

        $periodoId = (int) $periodoId;
        $gradoId   = (int) $gradoId;
        $destino   = url('director/orden-merito/' . $periodoId);

        $motivo = trim((string) $this->input('motivo', ''));
        $orden  = $this->input('orden', []); // [matricula_id => posicion]

        if ($motivo === '' || !is_array($orden) || count($orden) < 2) {
            $this->redirectWithError($destino, 'Falta el motivo o el orden del desempate.');
        }

        // Validar que las posiciones formen una permutación estricta 1..n (sin repetir).
        $posiciones = array_map('intval', array_values($orden));
        sort($posiciones);
        $esperado = range(1, count($orden));
        if ($posiciones !== $esperado) {
            $this->redirectWithError(
                $destino,
                'El orden debe asignar posiciones distintas y consecutivas (1, 2, 3...).'
            );
        }

        // Ordenar las matrículas por la posición designada.
        $ordenMatriculas = array_map('intval', array_keys($orden));
        usort($ordenMatriculas, static fn($a, $b) => (int) $orden[$a] <=> (int) $orden[$b]);

        $usuario = Session::user();
        try {
            $this->desempateModel->guardar(
                $periodoId,
                $gradoId,
                $ordenMatriculas,
                (int) ($usuario['id'] ?? 0),
                $motivo
            );
        } catch (\Exception $e) {
            $this->redirectWithError($destino, 'No se pudo guardar la resolución del empate.');
        }

        $this->redirectWithSuccess($destino, 'Empate resuelto y registrado.');
    }

    /** ¿Alguna fila del ranking tiene un empate sin resolver? */
    private function rankingTienePendientes(array $estudiantes): bool
    {
        foreach ($estudiantes as $est) {
            if (!empty($est['empate_pendiente'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ranking de estudiantes de un grado en un periodo (con cascada de desempate).
     * Delega en OrdenMeritoModel — fuente única compartida con el buscador.
     */
    private function calcularRanking(int $gradoId, int $periodoId): array
    {
        return $this->ordenMeritoModel->rankingGrado($gradoId, $periodoId);
    }

    /**
     * Agrupa y rankea estudiantes dentro de cada sección del grado.
     * Retorna array [seccion_nombre => [top-N estudiantes con puesto]].
     */
    /**
     * Devuelve el nombre del tutor por sección para el grado dado.
     * Clave: seccion_nombre → string|null.
     */
    private function getTutoresPorGrado(int $gradoId): array
    {
        $secciones = $this->calModel->query("
            SELECT s.nombre AS seccion_nombre, s.tutor_id
            FROM secciones s
            INNER JOIN grados g ON g.id = s.grado_id
            WHERE g.id = ?
            ORDER BY s.nombre
        ", [$gradoId]);

        $tutores = [];
        foreach ($secciones as $sec) {
            $tutorId = (int) ($sec['tutor_id'] ?? 0);
            if (!$tutorId) {
                $tutores[$sec['seccion_nombre']] = null;
                continue;
            }

            $persona = $this->calModel->queryOne("
                SELECT p.apellido_paterno, p.apellido_materno, p.nombres, p.sexo
                FROM usuarios u
                INNER JOIN personas p ON p.id = u.persona_id
                WHERE u.id = ?
                LIMIT 1
            ", [$tutorId]);

            $tutores[$sec['seccion_nombre']] = ($persona && !empty($persona['apellido_paterno']))
                ? [
                    'nombre' => $persona['apellido_paterno'] . ' ' . $persona['apellido_materno'] . ', ' . $persona['nombres'],
                    'sexo'   => $persona['sexo'] ?? null,
                ]
                : null;
        }

        return $tutores;
    }

    /**
     * Cuenta áreas y competencias distintas calificadas en el grado+periodo.
     * Excluye competencias transversales del conteo.
     */
    private function getConteosGrado(int $gradoId, int $periodoId): array
    {
        $resultado = $this->calModel->queryOne("
            SELECT
                COUNT(DISTINCT COALESCE(sa.area_id, comp.area_id)) AS num_areas,
                COUNT(DISTINCT cal.competencia_id)                  AS num_competencias
            FROM calificaciones cal
            INNER JOIN matriculas m       ON m.id    = cal.matricula_id
            INNER JOIN secciones s        ON s.id    = m.seccion_id
            INNER JOIN grados g           ON g.id    = s.grado_id
            INNER JOIN competencias comp  ON comp.id = cal.competencia_id
            LEFT  JOIN subareas sa        ON sa.id   = comp.subarea_id
            INNER JOIN areas a            ON a.id    = COALESCE(sa.area_id, comp.area_id)
            WHERE g.id           = ?
              AND cal.periodo_id = ?
              AND m.estado IN ('aprobada', 'activo')
              -- Retorno de grado: el estudiante compite en su grado OPERATIVO.
              -- Se excluye la matrícula oficial (la operativa, en grado inferior,
              -- entra como 'activo' y rankea con su grado real de asistencia).
              AND m.id NOT IN (
                  SELECT matricula_oficial_id FROM retornos_grado WHERE estado = 'activo'
              )
              AND a.tipo        != 'transversal'
        ", [$gradoId, $periodoId]);

        return [
            'num_areas'        => (int) ($resultado['num_areas']        ?? 0),
            'num_competencias' => (int) ($resultado['num_competencias'] ?? 0),
        ];
    }

    /**
     * Director EBR vigente hoy para el año del periodo.
     * Siempre usa la fecha actual: el reporte se imprime y firma hoy,
     * por quien ejerce el cargo en este momento.
     */
    private function getDirectorEbr(array $periodo): ?array
    {
        return $this->dirModel->getVigenteEnFecha((int) $periodo['anio_id']);
    }

    /**
     * Ranking por sección dentro del grado (con cascada de desempate por sección).
     * Delega en OrdenMeritoModel — fuente única compartida.
     */
    private function calcularRankingPorSeccion(
        int $gradoId,
        int $periodoId,
        int $limite = 0
    ): array {
        return $this->ordenMeritoModel->rankingPorSeccion($gradoId, $periodoId, $limite);
    }
}