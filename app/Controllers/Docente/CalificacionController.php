<?php

namespace App\Controllers\Docente;

use App\Controllers\BaseController;
use App\Models\CalificacionModel;
use App\Models\CriterioModel;
use Core\Session;

/**
 * CalificacionController
 * Panel del docente para gestión de criterios y notas.
 */


class CalificacionController extends BaseController
{
    private CalificacionModel $calModel;
    private CriterioModel     $critModel;

    public function __construct()
    {
        $this->requireRole(['docente', 'admin', 'registro_academico']);
        $this->calModel  = new CalificacionModel();
        $this->critModel = new CriterioModel();
    }
    
    private function getBloqueos(int $cargaId, int $periodoId): array
    {
        $resultado = $this->calModel->query("
            SELECT competencia_id
            FROM bloqueos_competencia
            WHERE carga_id  = ?
            AND periodo_id = ?
        ", [$cargaId, $periodoId]);

        // Retorna array de IDs bloqueados para chequeo rápido
        return array_column($resultado, 'competencia_id');
    }

    /**
     * GET /docente/mis-cargas
     * Lista las cargas académicas del docente en el periodo activo.
     */
    public function misCargas(): void
    {
        $user    = Session::user();
        $periodo = $this->getPeriodoActivo();
        $cargas  = $this->getCargas($user['id'], $periodo ? (int) $periodo['id'] : 0);

        $this->view('docente/mis-cargas', [
            'titulo'  => 'Mis cargas académicas',
            'cargas'  => $cargas,
            'periodo' => $periodo,
        ]);
    }

    /**
     * GET /docente/calificaciones/{carga_id}
     * Muestra las competencias y criterios de una carga.
     */
    public function formulario(string $cargaId): void
    {
        $cargaId = (int) $cargaId;
        $periodo = $this->getPeriodoActivo();

        if (!$periodo) {
            $this->redirectWithError(
                url('docente/mis-cargas'),
                'No hay un periodo activo.'
            );
        }

        $carga = $this->validarCargaDocente($cargaId);
        if (!$carga) {
            $this->redirectWithError(
                url('docente/mis-cargas'),
                'Carga no encontrada.'
            );
        }

        if ($carga['area_tipo'] === 'transversal') {
            $this->formularioTransversal($carga, $periodo);
            return;
        }

        $bloqueado       = $this->calModel->periodoEstaBloqueado($periodo['id']);
        $competencias    = $this->critModel->getCompetenciasConCriterios(
            $cargaId,
            $periodo['id']
        );
        $alumnos         = $this->getAlumnosSeccion($carga['seccion_id']);
        $notasExistentes = $this->getNotasExistentes($cargaId, $periodo['id']);
        $bloqueos        = $this->getBloqueos($cargaId, $periodo['id']);

        $this->view('docente/calificaciones', [
            'titulo'          => 'Calificaciones — ' . ($carga['nombre_display'] ?? ''),
            'carga'           => $carga,
            'periodo'         => $periodo,
            'competencias'    => $competencias,
            'alumnos'         => $alumnos,
            'bloqueado'       => $bloqueado,
            'notasExistentes' => $notasExistentes,
            'bloqueos'        => $bloqueos,
            'page_scripts'    => ['calificaciones'],
        ]);
    }

    /**
     * Ramificación interna para cargas de tipo transversal.
     * Auto-crea el criterio único por competencia si no existe y
     * renderiza la página de ingreso de notas (un input por alumno).
     * El resumen completo se ve desde el botón "Ver resumen" que
     * redirige al método resumen() existente.
     */
    private function formularioTransversal(array $carga, array $periodo): void
    {
        $cargaId   = (int) $carga['id'];
        $periodoId = (int) $periodo['id'];

        $competencias = $this->calModel->query("
            SELECT c.id, c.codigo_minedu, c.nombre_corto, c.nombre_completo, c.orden
            FROM competencias c
            WHERE c.area_id = ?
            ORDER BY c.orden
        ", [(int) $carga['area_id']]);

        // Garantizar un criterio único por cada competencia+periodo
        $criteriosPorComp = [];
        foreach ($competencias as $comp) {
            $compId = (int) $comp['id'];
            $crit   = $this->critModel->queryOne("
                SELECT id FROM criterios
                WHERE carga_id       = ?
                  AND competencia_id = ?
                  AND periodo_id     = ?
                LIMIT 1
            ", [$cargaId, $compId, $periodoId]);

            if (!$crit) {
                $this->calModel->execute("
                    INSERT INTO criterios
                        (carga_id, competencia_id, periodo_id, nombre, orden)
                    VALUES (?, ?, ?, ?, 1)
                ", [$cargaId, $compId, $periodoId, $periodo['nombre_display']]);

                $crit = $this->critModel->queryOne("
                    SELECT id FROM criterios
                    WHERE carga_id       = ?
                      AND competencia_id = ?
                      AND periodo_id     = ?
                    LIMIT 1
                ", [$cargaId, $compId, $periodoId]);
            }

            $criteriosPorComp[$compId] = $crit ? (int) $crit['id'] : null;
        }

        $this->view('docente/calificaciones-transversales', [
            'titulo'           => 'Competencias Transversales — ' . $carga['seccion_nombre'],
            'carga'            => $carga,
            'periodo'          => $periodo,
            'competencias'     => $competencias,
            'criteriosPorComp' => $criteriosPorComp,
            'alumnos'          => $this->getAlumnosSeccion((int) $carga['seccion_id']),
            'notasExistentes'  => $this->getNotasExistentes($cargaId, $periodoId),
            'bloqueos'         => $this->getBloqueos($cargaId, $periodoId),
            'bloqueado'        => $this->calModel->periodoEstaBloqueado($periodoId),
            'page_scripts'     => ['calificaciones'],
        ]);
    }

    /**
     * POST /docente/calificaciones/{carga_id}
     * Guarda las notas de un criterio para todos los alumnos.
     */
    public function guardar(string $cargaId): void
    {
        $this->validateCsrf();
        $cargaId = (int) $cargaId;

        $periodo = $this->getPeriodoActivo();
        if (!$periodo || $this->calModel->periodoEstaBloqueado($periodo['id'])) {
            $this->json([
                'success' => false,
                'mensaje' => 'El periodo está cerrado.',
            ], 403);
        }

        $criterioId    = (int) $this->input('criterio_id');
        $competenciaId = (int) $this->input('competencia_id');
        $notas         = $this->input('notas', []);

        // ── Verificar bloqueo de competencia ────────────────
        if ($this->calModel->competenciaBloqueada(
            $cargaId, $competenciaId, $periodo['id']
        )) {
            $this->json([
                'success' => false,
                'mensaje' => 'Esta competencia ya fue aprobada y bloqueada. No se pueden modificar las notas.',
            ], 403);
        }

        if (!$criterioId || empty($notas)) {
            $this->json([
                'success' => false,
                'mensaje' => 'Datos incompletos.',
            ], 400);
        }

        $ok = $this->calModel->guardarNotasMasivas($criterioId, $notas);

        if (!$ok) {
            $this->json([
                'success' => false,
                'mensaje' => 'Error al guardar las notas.',
            ], 500);
        }

        try {
            $this->calModel->recalcularPromedioSeccion(
                $cargaId,
                $competenciaId,
                $periodo['id'],
                Session::user()['id']
            );
        } catch (\Exception $e) {
            log_error('Error al recalcular promedio', [
                'carga_id'       => $cargaId,
                'competencia_id' => $competenciaId,
                'periodo_id'     => $periodo['id'],
                'error'          => $e->getMessage(),
            ]);
            $this->json([
                'success' => false,
                'mensaje' => 'Notas guardadas, pero falló el cálculo del promedio. Contacte al administrador.',
            ], 500);
        }

        $this->json([
            'success' => true,
            'mensaje' => 'Notas guardadas correctamente.',
        ]);
    }

    /**
     * POST /docente/criterios/crear
     * Crea un nuevo criterio de evaluación.
     */
    public function crearCriterio(): void
    {
        $this->validateCsrf();

        $cargaId       = (int) $this->input('carga_id');
        $competenciaId = (int) $this->input('competencia_id');
        $nombre        = trim($this->input('nombre', ''));
        $periodo       = $this->getPeriodoActivo();

        if (empty($nombre) || !$periodo) {
            $this->json([
                'success' => false,
                'mensaje' => 'Datos incompletos.',
            ], 400);
        }

        if ($this->calModel->periodoEstaBloqueado($periodo['id'])) {
            $this->json([
                'success' => false,
                'mensaje' => 'Periodo bloqueado.',
            ], 403);
        }

        $id = $this->critModel->crear(
            $cargaId,
            $competenciaId,
            $periodo['id'],
            $nombre
        );

        $this->json([
            'success' => true,
            'id'      => $id,
            'nombre'  => $nombre,
            'mensaje' => 'Criterio creado.',
        ]);
    }

    /**
     * POST /docente/criterios/{id}/eliminar
     * Elimina un criterio si no tiene calificaciones.
     */
    public function eliminarCriterio(string $id): void
    {
        $this->validateCsrf();
        $id = (int) $id;

        $ok = $this->critModel->eliminarSiVacio($id);

        if ($ok) {
            $this->json(['success' => true, 'mensaje' => 'Criterio eliminado.']);
        } else {
            $this->json([
                'success' => false,
                'mensaje' => 'No se puede eliminar — ya tiene calificaciones.',
            ], 409);
        }
    }

    /**
     * POST /docente/calificaciones/conclusion
     * Guarda la conclusión descriptiva de una competencia.
     */
    public function guardarConclusion(): void
    {
        $this->validateCsrf();

        $matriculaId   = (int) $this->input('matricula_id');
        $cargaId       = (int) $this->input('carga_id');
        $competenciaId = (int) $this->input('competencia_id');
        $conclusion    = trim($this->input('conclusion', ''));
        $periodo       = $this->getPeriodoActivo();

        if (!$periodo) {
            $this->json([
                'success' => false,
                'mensaje' => 'Sin periodo activo.',
            ], 400);
        }

        $ok = $this->calModel->execute("
            UPDATE calificaciones
            SET conclusion_descriptiva = ?,
                modificado_en          = NOW()
            WHERE matricula_id   = ?
              AND carga_id       = ?
              AND competencia_id = ?
              AND periodo_id     = ?
        ", [$conclusion, $matriculaId, $cargaId, $competenciaId, $periodo['id']]);

        $this->json([
            'success' => $ok,
            'mensaje' => $ok ? 'Conclusión guardada.' : 'Error al guardar.',
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

    private function getCargas(int $docenteId, int $periodoId = 0): array
    {
        return $this->calModel->query("
            SELECT
                ca.id,
                ca.horas_semanales,
                ca.seccion_id,
                s.nombre          AS seccion_nombre,
                s.es_unidocente,
                g.nombre_display  AS grado_nombre,
                n.nombre          AS nivel_nombre,
                n.codigo          AS nivel_codigo,
                n.escala_boleta,
                CASE
                    WHEN s.es_unidocente = 1 THEN a.nombre
                    ELSE COALESCE(sa.nombre, a.nombre)
                END               AS nombre_display,
                a.nombre          AS area_nombre,
                a.tipo            AS area_tipo,
                sa.id             AS subarea_id,
                a.id              AS area_id,
                (
                    SELECT COUNT(DISTINCT comp2.id)
                    FROM competencias comp2
                    WHERE (
                        (ca.subarea_id IS NOT NULL AND comp2.subarea_id = ca.subarea_id)
                        OR
                        (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL
                            AND comp2.area_id = ca.area_id)
                    )
                ) AS total_competencias,
                (
                    SELECT COUNT(*)
                    FROM bloqueos_competencia bc2
                    WHERE bc2.carga_id   = ca.id
                      AND bc2.periodo_id = ?
                ) AS competencias_bloqueadas
            FROM cargas_academicas ca
            INNER JOIN secciones s  ON s.id  = ca.seccion_id
            INNER JOIN grados g     ON g.id  = s.grado_id
            INNER JOIN niveles n    ON n.id  = g.nivel_id
            LEFT  JOIN subareas sa  ON sa.id = ca.subarea_id
            LEFT  JOIN areas a      ON a.id  = COALESCE(ca.area_id, sa.area_id)
            WHERE ca.docente_id = ?
              AND ca.estado     = 'activa'
            ORDER BY n.id, g.numero, s.nombre, a.orden
        ", [$periodoId, $docenteId]);
    }

    private function validarCargaDocente(int $cargaId): ?array
    {
        $user = Session::user();
        return $this->calModel->queryOne("
            SELECT
                ca.*,
                s.nombre          AS seccion_nombre,
                s.es_unidocente,
                g.nombre_display  AS grado_nombre,
                n.nombre          AS nivel_nombre,
                n.codigo          AS nivel_codigo,
                n.escala_boleta,
                COALESCE(sa.nombre, a.nombre) AS nombre_display,
                a.nombre          AS area_nombre,
                a.tipo            AS area_tipo
            FROM cargas_academicas ca
            INNER JOIN secciones s  ON s.id  = ca.seccion_id
            INNER JOIN grados g     ON g.id  = s.grado_id
            INNER JOIN niveles n    ON n.id  = g.nivel_id
            LEFT  JOIN subareas sa  ON sa.id = ca.subarea_id
            LEFT  JOIN areas a      ON a.id  = COALESCE(ca.area_id, sa.area_id)
            WHERE ca.id         = ?
              AND ca.docente_id = ?
              AND ca.estado     = 'activa'
        ", [$cargaId, $user['id']]);
    }

    private function getAlumnosSeccion(int $seccionId): array
    {
        return $this->calModel->query("
            SELECT
                m.id AS matricula_id,
                p.dni,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                CONCAT(
                    p.apellido_paterno, ' ',
                    p.apellido_materno, ', ',
                    p.nombres
                ) AS nombre_completo
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            WHERE m.seccion_id = ?
            AND m.estado     = 'aprobada'
            ORDER BY p.apellido_paterno, p.apellido_materno, p.nombres
        ", [$seccionId]);
    }

    private function getNotasExistentes(int $cargaId, int $periodoId): array
    {
        $resultado = $this->calModel->query("
            SELECT
                cc.matricula_id,
                cc.nota,
                cr.id AS criterio_id,
                cr.competencia_id
            FROM calificaciones_criterio cc
            INNER JOIN criterios cr ON cr.id = cc.criterio_id
            WHERE cr.carga_id   = ?
            AND cr.periodo_id = ?
        ", [$cargaId, $periodoId]);

        // Indexar por criterio_id y matricula_id para acceso rápido
        $notas = [];
        foreach ($resultado as $row) {
            $notas[$row['criterio_id']][$row['matricula_id']] = $row['nota'];
        }
        return $notas;
    }
        /**
 * GET /docente/calificaciones/{carga_id}/resumen/{competencia_id}
 * Vista de resumen con promedios y conclusiones por alumno.
 */
    public function resumen(string $cargaId, string $competenciaId): void
    {
        $cargaId       = (int) $cargaId;
        $competenciaId = (int) $competenciaId;
        $periodo       = $this->getPeriodoActivo();

        if (!$periodo) {
            $this->redirectWithError(
                url('docente/mis-cargas'),
                'No hay un periodo activo.'
            );
        }

        $carga = $this->validarCargaDocente($cargaId);
        if (!$carga) {
            $this->redirectWithError(
                url('docente/mis-cargas'),
                'Carga no encontrada.'
            );
        }

        // Obtener competencia
        $competencia = $this->calModel->queryOne("
            SELECT * FROM competencias WHERE id = ?
        ", [$competenciaId]);

        // Verificar si está bloqueada
        $bloqueada = $this->calModel->competenciaBloqueada(
            $cargaId, $competenciaId, $periodo['id']
        );

        // Obtener resumen completo
        $resumen = $this->calModel->getResumenCompetencia(
            $cargaId, $competenciaId, $periodo['id']
        );

        $this->view('docente/resumen-competencia', [
            'titulo'       => 'Resumen — ' . ($competencia['nombre_corto'] ?? ''),
            'carga'        => $carga,
            'periodo'      => $periodo,
            'competencia'  => $competencia,
            'criterios'    => $resumen['criterios'],
            'alumnos'      => $resumen['alumnos'],
            'bloqueada'    => $bloqueada,
            'page_scripts' => ['resumen'],
        ]);
    }

    /**
     * POST /docente/calificaciones/{carga_id}/conclusion/{competencia_id}
     * Guarda la conclusión de UN alumno específico.
     */
    public function guardarConclusionAlumno(
        string $cargaId,
        string $competenciaId
    ): void {
        $this->validateCsrf();

        $cargaId       = (int) $cargaId;
        $competenciaId = (int) $competenciaId;
        $matriculaId   = (int) $this->input('matricula_id');
        $conclusion    = trim($this->input('conclusion', ''));
        $periodo       = $this->getPeriodoActivo();

        if (!$periodo) {
            $this->json(['success' => false, 'mensaje' => 'Sin periodo activo.'], 400);
        }

        if ($this->calModel->competenciaBloqueada($cargaId, $competenciaId, $periodo['id'])) {
            $this->json(['success' => false, 'mensaje' => 'Competencia bloqueada.'], 403);
        }

        try {
            $guardado = $this->calModel->actualizarConclusion(
                $matriculaId, $cargaId, $competenciaId, $periodo['id'], $conclusion
            );

            if (!$guardado) {
                $this->json([
                    'success' => false,
                    'mensaje' => 'No se encontró la calificación. Guarda las notas del alumno primero.',
                ], 400);
                return;
            }

            $this->json(['success' => true, 'mensaje' => 'Conclusión guardada.']);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /docente/calificaciones/{carga_id}/bloquear/{competencia_id}
     * Aprueba y bloquea una competencia.
     */
    public function bloquear(string $cargaId, string $competenciaId): void
    {
        $this->validateCsrf();

        $cargaId       = (int) $cargaId;
        $competenciaId = (int) $competenciaId;
        $periodo       = $this->getPeriodoActivo();
        $user          = Session::user();

        if (!$periodo) {
            $this->json(['success' => false, 'mensaje' => 'Sin periodo activo.'], 400);
        }

        $resumen = $this->calModel->getResumenCompetencia(
            $cargaId, $competenciaId, $periodo['id']
        );

        // Bypass: docente confirma que la competencia no fue trabajada en el bimestre.
        // Solo válido cuando efectivamente no existen criterios ni calificaciones.
        $sinCriterios     = empty($resumen['criterios']);
        $confirmaSinNotas = !empty($this->input('sin_calificaciones'));

        if (!($sinCriterios && $confirmaSinNotas)) {
            // Validación normal: todos los alumnos deben tener promedio
            $sinNota = array_filter(
                $resumen['alumnos'],
                fn($a) => $a['promedio'] === null
            );

            if (!empty($sinNota)) {
                $this->json([
                    'success' => false,
                    'mensaje' => 'Hay ' . count($sinNota) . ' alumno(s) sin nota registrada.',
                ], 400);
            }
        }

        $ok = $this->calModel->bloquearCompetencia(
            $cargaId, $competenciaId, $periodo['id'], $user['id']
        );

        $this->json([
            'success' => $ok,
            'mensaje' => $ok
                ? 'Competencia aprobada y bloqueada correctamente.'
                : 'Error al bloquear.',
        ]);
    }

    
}