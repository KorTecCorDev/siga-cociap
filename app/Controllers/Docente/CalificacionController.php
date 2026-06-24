<?php

namespace App\Controllers\Docente;

use App\Controllers\BaseController;
use App\Models\CalificacionModel;
use App\Models\CriterioModel;
use App\Models\ExoneracionModel;
use App\Models\OmisionCriterioModel;
use Core\Session;

/**
 * CalificacionController
 * Panel del docente para gestión de criterios y notas.
 */


class CalificacionController extends BaseController
{
    /** Longitud máxima del nombre de un criterio; el detalle va en `descripcion` */
    public const CRITERIO_NOMBRE_MAX = 100;

    private CalificacionModel    $calModel;
    private CriterioModel        $critModel;
    private OmisionCriterioModel $omisionModel;
    private ExoneracionModel     $exoModel;

    public function __construct()
    {
        $this->requireRole(['docente', 'admin', 'registro_academico']);
        $this->calModel    = new CalificacionModel();
        $this->critModel   = new CriterioModel();
        $this->omisionModel = new OmisionCriterioModel();
        $this->exoModel    = new ExoneracionModel();
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
        $user          = Session::user();
        $periodoActivo = $this->getPeriodoActivo();

        // Bimestres seleccionables del año vigente: activo + cerrados. Sirven
        // para que el docente revise sus notas de bimestres pasados (read-only).
        $periodos = $this->calModel->query("
            SELECT p.id, p.numero, p.nombre_display, p.estado, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE a.estado = 'activo'
              AND p.estado IN ('activo', 'cerrado')
            ORDER BY p.numero ASC
        ");

        // Periodo seleccionado: el de la query si es valido; si no, el activo.
        $periodoId = (int) ($this->query('periodo_id') ?? 0);
        $periodo   = null;
        foreach ($periodos as $p) {
            if ((int) $p['id'] === $periodoId) {
                $periodo = $p;
                break;
            }
        }
        if (!$periodo) {
            $periodo = $periodoActivo;
        }

        // Historico = el periodo elegido NO es el activo (grilla en solo lectura).
        $esHistorico = $periodo
            && (!$periodoActivo || (int) $periodo['id'] !== (int) $periodoActivo['id']);

        $cargas = $this->getCargas($user['id'], $periodo ? (int) $periodo['id'] : 0);

        // Docente de aula (unidocente): es tutor(a) de aula si alguna carga es de
        // una seccion es_unidocente. La vista marca ESE grupo como "Mi aula"; las
        // demas secciones (caso mixto: ademas especialista en otro grado) se
        // listan normalmente con su propio encabezado.
        $tieneAula = false;
        $aula      = null;
        foreach ($cargas as $c) {
            if (!empty($c['es_unidocente'])) {
                $tieneAula = true;
                $aula      = trim($c['grado_nombre'] . ' ' . $c['seccion_nombre']);
                break;
            }
        }

        // Tutoría y Conducta tienen sus propias cards de acceso en el dashboard
        // (/docente/inicio); aquí solo se listan las cargas académicas.
        $this->view('docente/mis-cargas', [
            'titulo'      => 'Mis cargas académicas',
            'cargas'      => $cargas,
            'periodo'     => $periodo,
            'periodos'    => $periodos,
            'esHistorico' => $esHistorico,
            'tieneAula'   => $tieneAula,
            'aula'        => $aula,
        ]);
    }

    /**
     * GET /docente/calificaciones/{carga_id}/historial/{periodo_id}
     * Grilla criterio-a-criterio de SU carga en un bimestre cerrado, SOLO
     * LECTURA. Muestra unicamente las competencias oficiales (bloqueadas) y
     * reutiliza el parcial consulta-notas/_tabla.php. Para corregir se usa
     * Rectificacion (RA); aqui no hay edicion.
     */
    public function historial(string $cargaId, string $periodoId): void
    {
        $cargaId   = (int) $cargaId;
        $periodoId = (int) $periodoId;

        // validarCargaDocente filtra por docente_id = usuario actual: garantiza
        // que el docente solo abra SUS propias cargas.
        $carga = $this->validarCargaDocente($cargaId);
        if (!$carga) {
            $this->redirectWithError(url('docente/mis-cargas'), 'Carga no encontrada.');
        }

        $periodo = $this->calModel->queryOne("
            SELECT p.*, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
        ", [$periodoId]);
        if (!$periodo) {
            $this->redirectWithError(url('docente/mis-cargas'), 'Periodo no encontrado.');
        }

        // Competencias OFICIALES (con bloqueo) de la carga en ese periodo.
        $bloqueadas = $this->calModel->query("
            SELECT comp.id,
                   comp.nombre_completo,
                   comp.codigo_minedu,
                   (a.tipo = 'transversal') AS es_transversal
            FROM bloqueos_competencia bc
            INNER JOIN competencias comp ON comp.id = bc.competencia_id
            LEFT  JOIN areas a ON a.id = comp.area_id
            WHERE bc.carga_id   = ?
              AND bc.periodo_id = ?
            ORDER BY comp.orden, comp.id
        ", [$cargaId, $periodoId]);

        $exonerados   = $this->exoModel->getActivasParaCarga($cargaId, (int) $periodo['anio_id']);
        $competencias = [];

        foreach ($bloqueadas as $b) {
            $competenciaId = (int) $b['id'];
            $resumen = $this->calModel->getResumenCompetencia($cargaId, $competenciaId, $periodoId);

            $omisionesPorCriterio = [];
            foreach ($resumen['criterios'] as $cr) {
                $omisionesPorCriterio[(int) $cr['id']] =
                    $this->omisionModel->getPorCriterio((int) $cr['id']);
            }
            foreach ($resumen['alumnos'] as &$al) {
                $al['omisiones_criterios'] = [];
                $mid = (int) $al['matricula_id'];
                foreach ($omisionesPorCriterio as $critId => $porMat) {
                    if (isset($porMat[$mid])) {
                        $al['omisiones_criterios'][$critId] = $porMat[$mid];
                    }
                }
            }
            unset($al);

            $competencias[] = [
                'competencia' => [
                    'nombre_completo' => $b['nombre_completo'],
                    'codigo_minedu'   => $b['codigo_minedu'],
                    'es_transversal'  => $b['es_transversal'],
                ],
                'criterios' => $resumen['criterios'],
                'alumnos'   => $resumen['alumnos'],
            ];
        }

        $this->view('docente/historial-carga', [
            'titulo'       => 'Historial — ' . ($carga['nombre_display'] ?? ''),
            'carga'        => $carga,
            'periodo'      => $periodo,
            'competencias' => $competencias,
            'exonerados'   => $exonerados,
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

        // Modelo nuevo: las competencias transversales (TIC/GAMA) las registra
        // cada docente DENTRO de su propia carga (sección "Competencias
        // Transversales" más abajo). Una carga transversal independiente es del
        // modelo viejo: ya no se califica aquí. El tutor solo agrega las
        // conclusiones y cierra el bimestre desde /docente/tutoria.
        if ($carga['area_tipo'] === 'transversal') {
            $this->redirectWithSuccess(
                url('docente/tutoria'),
                'Las competencias transversales ahora se registran en cada carga. '
                . 'Como tutor(a), aquí agregas las conclusiones y cierras el bimestre.'
            );
        }

        $bloqueado       = $this->calModel->periodoEstaBloqueado($periodo['id']);
        $competencias    = $this->critModel->getCompetenciasConCriterios(
            $cargaId,
            $periodo['id']
        );

        // Competencias transversales (TIC/GAMA): cada docente las registra
        // en su propia carga con el mismo mecanismo de criterios y notas.
        // Se bloquean junto con la última competencia propia (Variante 1).
        $transversales = $this->critModel->getCompetenciasTransversalesConCriterios(
            $cargaId,
            $periodo['id'],
            (int) $carga['nivel_id']
        );
        $competencias = array_merge($competencias, $transversales);

        $alumnos         = $this->getAlumnosSeccion($carga['seccion_id']);
        $notasExistentes = $this->getNotasExistentes($cargaId, $periodo['id']);
        $bloqueos        = $this->getBloqueos($cargaId, $periodo['id']);
        $exonerados      = $this->exoModel->getActivasParaCarga($cargaId, (int) $periodo['anio_id']);

        $this->view('docente/calificaciones', [
            'titulo'          => 'Calificaciones — ' . (!empty($carga['es_unidocente'])
                ? ($carga['area_nombre'] ?? '')
                : ($carga['nombre_display'] ?? '')),
            'carga'           => $carga,
            'periodo'         => $periodo,
            'competencias'    => $competencias,
            'alumnos'         => $alumnos,
            'bloqueado'       => $bloqueado,
            'notasExistentes' => $notasExistentes,
            'bloqueos'        => $bloqueos,
            'exonerados'      => $exonerados,
            'page_scripts'    => ['calificaciones'],
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

        // Sella el criterio como CONFIRMADO (clic explícito en "Confirmar").
        // El autosave (/autosave) nunca pasa por aquí, así que no desbloquea
        // "Ver resumen": eso evita saltarse el filtro de omisión con el
        // autoguardado. Es persistente (sobrevive al recargado).
        $this->critModel->marcarConfirmado($criterioId, Session::user()['id']);

        $this->json([
            'success' => true,
            'mensaje' => 'Notas guardadas correctamente.',
        ]);
    }

    /**
     * POST /docente/calificaciones/{carga_id}/autosave
     * Guarda o borra la nota de UNA celda al salir del campo (blur).
     * Nota vacía = borrar la fila en calificaciones_criterio.
     */
    public function autosave(string $cargaId): void
    {
        $this->validateCsrf();
        $cargaId = (int) $cargaId;

        $periodo = $this->getPeriodoActivo();
        if (!$periodo || $this->calModel->periodoEstaBloqueado($periodo['id'])) {
            $this->json(['success' => false, 'mensaje' => 'El periodo está cerrado.'], 403);
        }

        $criterioId    = (int) $this->input('criterio_id');
        $competenciaId = (int) $this->input('competencia_id');
        $matriculaId   = (int) $this->input('matricula_id');
        $nota          = trim($this->input('nota', ''));

        if (!$criterioId || !$competenciaId || !$matriculaId) {
            $this->json(['success' => false, 'mensaje' => 'Datos incompletos.'], 400);
        }

        if ($this->calModel->competenciaBloqueada($cargaId, $competenciaId, $periodo['id'])) {
            $this->json(['success' => false, 'mensaje' => 'Competencia bloqueada.'], 403);
        }

        if ($nota === '') {
            $this->calModel->eliminarNotaCriterio($criterioId, $matriculaId);
        } else {
            $notaInt = max(0, min(20, (int) $nota));
            $this->calModel->guardarNotaCriterio($criterioId, $matriculaId, $notaInt);
        }

        try {
            $this->calModel->recalcularPromedioSeccion(
                $cargaId,
                $competenciaId,
                $periodo['id'],
                Session::user()['id']
            );
        } catch (\Exception $e) {
            log_error('Autosave: error recalculando promedio', [
                'carga_id'       => $cargaId,
                'competencia_id' => $competenciaId,
                'error'          => $e->getMessage(),
            ]);
        }

        $this->json(['success' => true]);
    }

    /**
     * POST /docente/calificaciones/{carga_id}/omisiones
     * Registra el motivo por el que uno o más alumnos no fueron evaluados
     * en un criterio. Puede llamarse varias veces (upsert por pares).
     */
    public function guardarOmisiones(string $cargaId): void
    {
        $this->validateCsrf();
        $cargaId = (int) $cargaId;

        $periodo = $this->getPeriodoActivo();
        if (!$periodo || $this->calModel->periodoEstaBloqueado($periodo['id'])) {
            $this->json(['success' => false, 'mensaje' => 'El periodo está cerrado.'], 403);
        }

        $criterioId    = (int) $this->input('criterio_id');
        $competenciaId = (int) $this->input('competencia_id');
        $omisiones     = $this->input('omisiones', []);

        if (!$criterioId || !$competenciaId || empty($omisiones) || !is_array($omisiones)) {
            $this->json(['success' => false, 'mensaje' => 'Datos incompletos.'], 400);
        }

        if ($this->calModel->competenciaBloqueada($cargaId, $competenciaId, $periodo['id'])) {
            $this->json([
                'success' => false,
                'mensaje' => 'Esta competencia ya fue aprobada y bloqueada.',
            ], 403);
        }

        $this->omisionModel->guardarLote($criterioId, $omisiones, Session::user()['id']);

        $this->json(['success' => true, 'mensaje' => 'Omisiones registradas.']);
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
        $descripcion   = trim($this->input('descripcion', ''));
        $periodo       = $this->getPeriodoActivo();

        if (empty($nombre) || !$periodo) {
            $this->json([
                'success' => false,
                'mensaje' => 'Datos incompletos.',
            ], 400);
        }

        if (mb_strlen($nombre) > self::CRITERIO_NOMBRE_MAX) {
            $this->json([
                'success' => false,
                'mensaje' => 'El nombre del criterio no puede superar los '
                    . self::CRITERIO_NOMBRE_MAX . ' caracteres (tiene '
                    . mb_strlen($nombre) . '). Usa el campo descripción para el detalle.',
            ], 422);
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
            $nombre,
            $descripcion !== '' ? $descripcion : null
        );

        $this->json([
            'success'     => true,
            'id'          => $id,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'mensaje'     => 'Criterio creado.',
        ]);
    }

    /**
     * POST /docente/criterios/{id}/renombrar
     * Cambia el nombre de un criterio. Permitido aunque ya tenga calificaciones.
     */
    public function renombrarCriterio(string $id): void
    {
        $this->validateCsrf();
        $id          = (int) $id;
        $nombre      = trim($this->input('nombre', ''));
        $descripcion = trim($this->input('descripcion', ''));

        if (empty($nombre)) {
            $this->json(['success' => false, 'mensaje' => 'El nombre no puede estar vacío.'], 400);
        }

        if (mb_strlen($nombre) > self::CRITERIO_NOMBRE_MAX) {
            $this->json([
                'success' => false,
                'mensaje' => 'El nombre del criterio no puede superar los '
                    . self::CRITERIO_NOMBRE_MAX . ' caracteres (tiene '
                    . mb_strlen($nombre) . '). Usa el campo descripción para el detalle.',
            ], 422);
        }

        $criterio = $this->critModel->queryOne(
            "SELECT id, periodo_id FROM criterios WHERE id = ?",
            [$id]
        );

        if (!$criterio) {
            $this->json(['success' => false, 'mensaje' => 'Criterio no encontrado.'], 404);
        }

        if ($this->calModel->periodoEstaBloqueado((int) $criterio['periodo_id'])) {
            $this->json(['success' => false, 'mensaje' => 'Periodo bloqueado.'], 403);
        }

        $this->critModel->renombrar($id, $nombre, $descripcion !== '' ? $descripcion : null);

        $this->json([
            'success'     => true,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'mensaje'     => 'Criterio actualizado.',
        ]);
    }

    /**
     * POST /docente/criterios/{id}/eliminar
     * Soft-delete de un criterio aunque ya tenga calificaciones.
     * El criterio y sus calificaciones_criterio se conservan en BD para auditoría.
     * Si tenía calificaciones, recalcula el promedio de la competencia.
     */
    public function eliminarCriterio(string $id): void
    {
        $this->validateCsrf();
        $id = (int) $id;

        $criterio = $this->critModel->queryOne(
            "SELECT id, carga_id, competencia_id, periodo_id
             FROM criterios
             WHERE id = ? AND eliminado_en IS NULL",
            [$id]
        );

        if (!$criterio) {
            $this->json(['success' => false, 'mensaje' => 'Criterio no encontrado.'], 404);
        }

        $periodoId     = (int) $criterio['periodo_id'];
        $cargaId       = (int) $criterio['carga_id'];
        $competenciaId = (int) $criterio['competencia_id'];

        if ($this->calModel->periodoEstaBloqueado($periodoId)) {
            $this->json(['success' => false, 'mensaje' => 'El periodo está cerrado.'], 403);
        }

        if ($this->calModel->competenciaBloqueada($cargaId, $competenciaId, $periodoId)) {
            $this->json([
                'success' => false,
                'mensaje' => 'Esta competencia ya fue aprobada y bloqueada.',
            ], 403);
        }

        $teniaCals = $this->critModel->tieneCalificaciones($id);
        $user      = Session::user();

        $ok = $this->critModel->eliminarConAuditoria($id, $user['id']);

        if (!$ok) {
            $this->json(['success' => false, 'mensaje' => 'Error al eliminar el criterio.'], 500);
        }

        if ($teniaCals) {
            try {
                $this->calModel->recalcularPromedioSeccion(
                    $cargaId,
                    $competenciaId,
                    $periodoId,
                    $user['id']
                );
            } catch (\Exception $e) {
                log_error('Error al recalcular promedio tras eliminar criterio', [
                    'criterio_id'    => $id,
                    'carga_id'       => $cargaId,
                    'competencia_id' => $competenciaId,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        $this->json([
            'success'           => true,
            'mensaje'           => 'Criterio eliminado.',
            'tenia_calificaciones' => $teniaCals,
        ]);
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
                sa.nombre         AS subarea_nombre,
                a.id              AS area_id,
                -- Competencia vinculada a la subarea (1 subarea = 1 competencia).
                -- El unidocente NO dicta subareas: sus cards muestran el nombre
                -- corto + codigo MINEDU de la competencia en vez de la subarea.
                (SELECT comp.nombre_corto  FROM competencias comp
                    WHERE comp.subarea_id = ca.subarea_id ORDER BY comp.id LIMIT 1
                ) AS competencia_corto,
                (SELECT comp.codigo_minedu FROM competencias comp
                    WHERE comp.subarea_id = ca.subarea_id ORDER BY comp.id LIMIT 1
                ) AS competencia_codigo,
                (
                    -- Competencias PROPIAS del area/subarea de la carga. La barra de
                    -- avance compara este total contra competencias_bloqueadas, que
                    -- recorre EXACTAMENTE este mismo universo (propias). Las TIC/GAMA
                    -- transversales NO se suman aqui: tienen su propio distintivo
                    -- (total_transversales / transversales_bloqueadas). Sumarlas al
                    -- total mientras el numerador solo cuenta propias dejaba el avance
                    -- atascado (p. ej. 5/7 = 71%) al bloquear todo (Variante 1).
                    SELECT COUNT(DISTINCT comp2.id)
                    FROM competencias comp2
                    WHERE (
                        (ca.subarea_id IS NOT NULL AND comp2.subarea_id = ca.subarea_id)
                        OR
                        (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL
                            AND comp2.area_id = ca.area_id)
                    )
                ) AS total_competencias,
                -- Avance defensivo: numerador y denominador recorren el MISMO
                -- universo (las competencias PROPIAS de la carga, vía su predicado
                -- subarea/area). Para una carga normal excluye los bloqueos
                -- transversales TIC/GAMA (Variante 1, mismo carga_id); para una
                -- carga transversal (B1 reactivada) sus propias TIC/GAMA SÍ cuentan.
                (
                    SELECT COUNT(*)
                    FROM bloqueos_competencia bc2
                    WHERE bc2.carga_id   = ca.id
                      AND bc2.periodo_id = ?
                      AND bc2.competencia_id IN (
                          SELECT comp3.id
                          FROM competencias comp3
                          WHERE (ca.subarea_id IS NOT NULL AND comp3.subarea_id = ca.subarea_id)
                             OR (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL
                                 AND comp3.area_id = ca.area_id)
                      )
                ) AS competencias_bloqueadas,
                (
                    SELECT COUNT(DISTINCT cr2.competencia_id)
                    FROM criterios cr2
                    WHERE cr2.carga_id     = ca.id
                      AND cr2.periodo_id   = ?
                      AND cr2.eliminado_en IS NULL
                      AND cr2.competencia_id IN (
                          SELECT comp4.id
                          FROM competencias comp4
                          WHERE (ca.subarea_id IS NOT NULL AND comp4.subarea_id = ca.subarea_id)
                             OR (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL
                                 AND comp4.area_id = ca.area_id)
                      )
                ) AS competencias_con_criterios,
                -- Distintivo de transversales: las TIC/GAMA de la carga viven en
                -- el area transversal del nivel (n.id). Se bloquean junto a la
                -- ultima competencia propia (Variante 1). 3 estados en la vista:
                -- bloqueadas==total -> completas; con_criterios>0 -> en progreso.
                (
                    SELECT COUNT(*)
                    FROM competencias compt
                    INNER JOIN areas at2 ON at2.id = compt.area_id
                    WHERE at2.tipo     = 'transversal'
                      AND at2.nivel_id = n.id
                ) AS total_transversales,
                (
                    SELECT COUNT(*)
                    FROM bloqueos_competencia bct
                    INNER JOIN competencias compt ON compt.id = bct.competencia_id
                    INNER JOIN areas at2 ON at2.id = compt.area_id AND at2.tipo = 'transversal'
                    WHERE bct.carga_id   = ca.id
                      AND bct.periodo_id = ?
                      AND at2.nivel_id   = n.id
                ) AS transversales_bloqueadas,
                (
                    SELECT COUNT(DISTINCT crt.competencia_id)
                    FROM criterios crt
                    INNER JOIN competencias compt ON compt.id = crt.competencia_id
                    INNER JOIN areas at2 ON at2.id = compt.area_id AND at2.tipo = 'transversal'
                    WHERE crt.carga_id     = ca.id
                      AND crt.periodo_id   = ?
                      AND crt.eliminado_en IS NULL
                      AND at2.nivel_id     = n.id
                ) AS transversales_con_criterios
            FROM cargas_academicas ca
            INNER JOIN secciones s  ON s.id  = ca.seccion_id
            INNER JOIN grados g     ON g.id  = s.grado_id
            INNER JOIN niveles n    ON n.id  = g.nivel_id
            LEFT  JOIN subareas sa  ON sa.id = ca.subarea_id
            LEFT  JOIN areas a      ON a.id  = COALESCE(ca.area_id, sa.area_id)
            WHERE ca.docente_id = ?
              AND ca.estado     = 'activa'
              -- Las TIC/GAMA se registran DENTRO de cada carga normal (sección
              -- transversal del formulario). Una carga transversal independiente
              -- (modelo viejo) NO debe listarse como tarjeta: era el ingreso de
              -- promedios del tutor, hoy reemplazado por /docente/tutoria.
              AND (a.tipo IS NULL OR a.tipo != 'transversal')
            ORDER BY n.id, g.numero, s.nombre, a.orden, sa.orden
        ", [$periodoId, $periodoId, $periodoId, $periodoId, $docenteId]);
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
                n.id              AS nivel_id,
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
            -- Regla del proyecto: el docente tiene a disposición a TODOS los
            -- estudiantes matriculados de la sección (aprobada, pendiente e
            -- incluso desactivado por baja administrativa, p. ej. deuda: el
            -- alumno sigue asistiendo mientras regulariza). El ÚNICO excluido es
            -- el TRASLADO DE SALIDA (tipo='trasladado'): ese sí abandonó el
            -- colegio y no debe calificarse. Un traslado siempre es desactivado,
            -- así que basta filtrar por tipo.
            AND m.tipo != 'trasladado'
            -- Retorno de grado: durante la nivelación la matrícula OFICIAL no se
            -- califica en su grado (lo hace la operativa); tras revertir, la
            -- operativa deja de calificarse (lo hace de nuevo la oficial).
            AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
            AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
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
            WHERE cr.carga_id     = ?
              AND cr.periodo_id   = ?
              AND cr.eliminado_en IS NULL
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

        // Obtener competencia (con flag de transversal: su conclusión y
        // bloqueo no se gestionan desde este resumen sino vía tutor/Variante 1)
        $competencia = $this->calModel->queryOne("
            SELECT c.*,
                   (a.tipo = 'transversal') AS es_transversal
            FROM competencias c
            LEFT JOIN areas a ON a.id = c.area_id
            WHERE c.id = ?
        ", [$competenciaId]);

        // Verificar si está bloqueada
        $bloqueada = $this->calModel->competenciaBloqueada(
            $cargaId, $competenciaId, $periodo['id']
        );

        // Obtener resumen completo
        $resumen = $this->calModel->getResumenCompetencia(
            $cargaId, $competenciaId, $periodo['id']
        );

        // Añadir omisiones por criterio a cada alumno
        $omisionesPorCriterio = [];
        foreach ($resumen['criterios'] as $criterio) {
            $omisionesPorCriterio[(int) $criterio['id']] =
                $this->omisionModel->getPorCriterio((int) $criterio['id']);
        }
        foreach ($resumen['alumnos'] as &$alumno) {
            $alumno['omisiones_criterios'] = [];
            $matId = (int) $alumno['matricula_id'];
            foreach ($omisionesPorCriterio as $critId => $porMatricula) {
                if (isset($porMatricula[$matId])) {
                    $alumno['omisiones_criterios'][$critId] = $porMatricula[$matId];
                }
            }
        }
        unset($alumno);

        $exonerados = $this->exoModel->getActivasParaCarga($cargaId, (int) $periodo['anio_id']);

        $this->view('docente/resumen-competencia', [
            'titulo'       => 'Resumen — ' . ($competencia['nombre_corto'] ?? ''),
            'carga'        => $carga,
            'periodo'      => $periodo,
            'competencia'  => $competencia,
            'criterios'    => $resumen['criterios'],
            'alumnos'      => $resumen['alumnos'],
            'bloqueada'    => $bloqueada,
            'exonerados'   => $exonerados,
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
     * Aprueba y bloquea UNA competencia de forma independiente.
     *
     * Desde el II Bimestre cada competencia —incluidas las transversales
     * TIC/GAMA registradas en la propia carga— se aprueba y bloquea por
     * separado, con el mismo mecanismo y las mismas validaciones. Ya no
     * existe el empaquetado de la "última competencia propia".
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

        $confirmaSinNotas = !empty($this->input('sin_calificaciones'));

        // Validar la competencia que se quiere bloquear (propia o transversal).
        $error = $this->errorBloqueoCompetencia(
            $cargaId, $competenciaId, $periodo, $confirmaSinNotas
        );
        if ($error !== null) {
            $this->json(['success' => false, 'mensaje' => $error], 400);
        }

        $ok = $this->calModel->bloquearCompetencia(
            $cargaId, $competenciaId, $periodo['id'], $user['id']
        );

        $this->json([
            'success' => $ok,
            'mensaje' => $ok ? 'Competencia aprobada y bloqueada correctamente.'
                             : 'Error al bloquear.',
        ]);
    }

    /**
     * Valida si una competencia puede bloquearse. Retorna NULL si está lista
     * o el mensaje de error. Alumnos sin promedio son válidos solo si tienen
     * omisión registrada o están exonerados de la carga.
     */
    private function errorBloqueoCompetencia(
        int $cargaId,
        int $competenciaId,
        array $periodo,
        bool $confirmaSinNotas
    ): ?string {
        $resumen = $this->calModel->getResumenCompetencia(
            $cargaId, $competenciaId, (int) $periodo['id']
        );

        $sinCriterios = empty($resumen['criterios']);
        if ($sinCriterios && $confirmaSinNotas) {
            return null;
        }
        if ($sinCriterios) {
            return 'sin criterios ni notas registradas.';
        }

        $matriculasConOmision = $this->omisionModel->getMatriculasConOmisionEnCompetencia(
            $cargaId, $competenciaId, (int) $periodo['id']
        );
        $exonerados = $this->exoModel->getActivasParaCarga($cargaId, (int) $periodo['anio_id']);

        $sinNota = array_filter(
            $resumen['alumnos'],
            fn($a) => $a['promedio'] === null
                && !in_array((int) $a['matricula_id'], $matriculasConOmision, true)
                && !in_array((int) $a['matricula_id'], $exonerados, true)
        );

        if (!empty($sinNota)) {
            return 'Hay ' . count($sinNota) . ' alumno(s) sin nota ni motivo de omisión registrado.';
        }

        return null;
    }
}