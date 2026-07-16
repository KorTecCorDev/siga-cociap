<?php

namespace App\Controllers\Rectificacion;

use App\Controllers\BaseController;
use App\Models\RectificacionModel;
use App\Models\CalificacionModel;
use App\Models\CriterioModel;
use App\Models\OrdenMeritoModel;
use Core\Session;

/**
 * RectificacionController
 * Módulo GENERAL de rectificación de calificaciones.
 *
 * Permite a Registro Académico corregir, con auditoría obligatoria, una
 * calificación que YA salió del flujo normal del docente (periodo cerrado
 * y/o competencia bloqueada). El módulo ORQUESTA y AUDITA; la escritura de
 * notas la delega a CalificacionModel (criterios → promedio → conclusión).
 *
 * Control de seguridad (invariante): rol (admin/registro_academico) +
 * estado RECTIFICABLE (RectificacionModel::esRectificable) + motivo
 * obligatorio + traza en `rectificaciones_calificacion`. Si la competencia
 * está abierta y desbloqueada NO se rectifica aquí: se corrige por el flujo
 * del docente.
 */
class RectificacionController extends BaseController
{
    private RectificacionModel $model;
    private CalificacionModel  $calModel;
    private CriterioModel      $critModel;
    private OrdenMeritoModel   $ordenMeritoModel;

    public function __construct()
    {
        $this->requireRole(['admin', 'registro_academico']);
        $this->model            = new RectificacionModel();
        $this->calModel         = new CalificacionModel();
        $this->critModel        = new CriterioModel();
        $this->ordenMeritoModel = new OrdenMeritoModel();
    }

    /** GET /rectificaciones — buscador de estudiante + historial reciente. */
    public function index(): void
    {
        $this->view('rectificaciones/index', [
            'titulo'       => 'Rectificación de calificaciones',
            'historial'    => $this->model->getHistorial(20),
            'page_scripts' => ['buscador-estudiante'],
        ]);
    }

    /** GET /rectificaciones/matricula/{id} — competencias rectificables. */
    public function matricula(string $id): void
    {
        $matriculaId = (int) $id;
        $info = $this->model->getMatriculaInfo($matriculaId);
        if (!$info) {
            $this->notFound();
        }

        // Agrupa las competencias rectificables por bimestre para la vista.
        $competencias = $this->model->getCompetenciasRectificables($matriculaId);
        $porPeriodo   = [];
        foreach ($competencias as $c) {
            $pid = (int) $c['periodo_id'];
            if (!isset($porPeriodo[$pid])) {
                $porPeriodo[$pid] = [
                    'periodo_id'     => $pid,
                    'periodo_numero' => (int) $c['periodo_numero'],
                    'periodo_nombre' => $c['periodo_nombre'],
                    'periodo_estado' => $c['periodo_estado'],
                    'items'          => [],
                ];
            }
            $porPeriodo[$pid]['items'][] = $c;
        }

        // Competencias SIN calificación del alumno (cerradas/bloqueadas) →
        // candidatas a calificación EXTRAORDINARIA, agrupadas por bimestre.
        $insertables    = $this->model->getCompetenciasInsertables($matriculaId);
        $porPeriodoIns  = [];
        foreach ($insertables as $c) {
            $pid = (int) $c['periodo_id'];
            if (!isset($porPeriodoIns[$pid])) {
                $porPeriodoIns[$pid] = [
                    'periodo_id'     => $pid,
                    'periodo_numero' => (int) $c['periodo_numero'],
                    'periodo_nombre' => $c['periodo_nombre'],
                    'periodo_estado' => $c['periodo_estado'],
                    'items'          => [],
                ];
            }
            $porPeriodoIns[$pid]['items'][] = $c;
        }

        $this->view('rectificaciones/matricula', [
            'titulo'        => 'Rectificación — ' . $info['nombre_completo'],
            'info'          => $info,
            'porPeriodo'    => array_values($porPeriodo),
            'porPeriodoIns' => array_values($porPeriodoIns),
            'historial'     => $this->model->getHistorial(20, $matriculaId),
        ]);
    }

    /**
     * GET /rectificaciones/extraordinaria?matricula=&carga=&competencia=&periodo=
     * Formulario de CALIFICACIÓN EXTRAORDINARIA: alta de nota (con motivo)
     * a un alumno SIN calificación en una competencia cerrada/bloqueada.
     * La nota va a boleta y SIAGIE; NO cuenta en el orden de mérito.
     */
    public function extraordinaria(): void
    {
        $matriculaId   = (int) $this->query('matricula');
        $cargaId       = (int) $this->query('carga');
        $competenciaId = (int) $this->query('competencia');
        $periodoId     = (int) $this->query('periodo');

        $info = $this->model->getMatriculaInfo($matriculaId);
        if (!$info) {
            $this->notFound();
        }

        // Invariante de seguridad: solo tuplas insertables (sin nota previa,
        // cerrada/bloqueada, no exonerado, carga de su sección).
        if (!$this->model->esInsertable($matriculaId, $cargaId, $competenciaId, $periodoId)) {
            $this->redirectWithError(
                url('rectificaciones/matricula/' . $matriculaId),
                'Esa competencia no admite calificación extraordinaria (el alumno ya tiene nota, está exonerado, o la competencia sigue en el flujo del docente).'
            );
        }

        // Metadatos de la competencia elegida (desde la misma fuente que la lista).
        $meta = null;
        foreach ($this->model->getCompetenciasInsertables($matriculaId) as $c) {
            if ((int) $c['carga_id'] === $cargaId
                && (int) $c['competencia_id'] === $competenciaId
                && (int) $c['periodo_id'] === $periodoId) {
                $meta = $c;
                break;
            }
        }
        if ($meta === null) {
            $this->notFound();
        }

        $this->view('rectificaciones/extraordinaria', [
            'titulo'        => 'Calificación extraordinaria',
            'info'          => $info,
            'meta'          => $meta,
            'cargaId'       => $cargaId,
            'competenciaId' => $competenciaId,
            'periodoId'     => $periodoId,
        ]);
    }

    /**
     * POST /rectificaciones/extraordinaria/guardar
     * Alta de la calificación extraordinaria: criterio único confirmado
     * (extraordinario=1) + nota del alumno + promedio (= la nota) marcado
     * `extraordinaria=1` + conclusión + auditoría tipo 'extraordinaria'.
     * NO regenera el snapshot del mérito: el flag la excluye del ranking,
     * así que el orden vigente no cambia.
     */
    public function guardarExtraordinaria(): void
    {
        $this->validateCsrf();

        $matriculaId   = (int) $this->input('matricula_id');
        $cargaId       = (int) $this->input('carga_id');
        $competenciaId = (int) $this->input('competencia_id');
        $periodoId     = (int) $this->input('periodo_id');
        $motivo        = trim((string) $this->input('motivo', ''));
        $conclusion    = trim((string) $this->input('conclusion', ''));
        $notaRaw       = $this->input('nota', '');
        $usuarioId     = (int) (Session::user()['id'] ?? 0);

        $volverForm = url('rectificaciones/extraordinaria?matricula=' . $matriculaId
            . '&carga=' . $cargaId . '&competencia=' . $competenciaId . '&periodo=' . $periodoId);
        $volverLista = url('rectificaciones/matricula/' . $matriculaId);

        // ── Validaciones de entrada ──────────────────────────────
        $info = $this->model->getMatriculaInfo($matriculaId);
        if (!$info) {
            $this->notFound();
        }
        if ($motivo === '') {
            $this->redirectWithError($volverForm, 'El motivo de la calificación extraordinaria es obligatorio.');
        }
        if ($notaRaw === '' || $notaRaw === null || !is_numeric($notaRaw)) {
            $this->redirectWithError($volverForm, 'Ingresa la nota (0-20).');
        }
        $nota = max(0, min(20, (int) $notaRaw));

        // Invariante de seguridad: estado insertable (re-chequeo en el POST).
        if (!$this->model->esInsertable($matriculaId, $cargaId, $competenciaId, $periodoId)) {
            $this->redirectWithError($volverLista,
                'Esa competencia no admite calificación extraordinaria (el alumno ya tiene nota, está exonerado, o la competencia sigue en el flujo del docente).');
        }

        $literal = nota_a_literal($nota);
        if (CalificacionModel::conclusionObligatoria($literal, (string) $info['nivel_codigo']) && $conclusion === '') {
            $this->redirectWithError($volverForm,
                'La conclusión descriptiva es obligatoria para el literal ' . $literal . ' en este nivel.');
        }

        // ── Escritura atómica ────────────────────────────────────
        $this->model->beginTransaction();
        try {
            // Criterio único "Calificación extraordinaria" (nace confirmado:
            // el promedio agregado y el blindaje anti-fantasma lo exigen).
            $criterioId = $this->critModel->obtenerOCrearExtraordinario(
                $cargaId, $competenciaId, $periodoId, $usuarioId
            );
            if ($criterioId <= 0) {
                throw new \RuntimeException('No se pudo obtener el criterio extraordinario.');
            }

            $this->calModel->guardarNotaCriterio($criterioId, $matriculaId, $nota);

            // Promedio del alumno = su única nota viva confirmada (la extraordinaria).
            $promedio = $this->calModel->calcularPromedio($matriculaId, $cargaId, $competenciaId, $periodoId);
            if ($promedio === null) {
                throw new \RuntimeException('No se pudo calcular el promedio extraordinario.');
            }
            $notaFinal = (int) round($promedio);

            $this->calModel->guardarNotaFinal(
                $matriculaId, $cargaId, $periodoId, $competenciaId, $notaFinal, $usuarioId
            );
            $this->calModel->marcarCalificacionExtraordinaria(
                $matriculaId, $cargaId, $competenciaId, $periodoId
            );
            if ($conclusion !== '') {
                $this->calModel->actualizarConclusion(
                    $matriculaId, $cargaId, $competenciaId, $periodoId, $conclusion
                );
            }

            $this->model->registrar([
                'matricula_id'        => $matriculaId,
                'carga_id'            => $cargaId,
                'periodo_id'          => $periodoId,
                'competencia_id'      => $competenciaId,
                'tipo'                => 'extraordinaria',
                'nota_anterior'       => null,
                'nota_nueva'          => $notaFinal,
                'conclusion_anterior' => null,
                'conclusion_nueva'    => $conclusion !== '' ? $conclusion : null,
                'motivo'              => $motivo,
                'rectificado_por'     => $usuarioId,
            ]);

            $this->model->commit();
        } catch (\Exception $e) {
            $this->model->rollback();
            log_error('Error al registrar calificación extraordinaria', [
                'matricula' => $matriculaId, 'carga' => $cargaId,
                'competencia' => $competenciaId, 'periodo' => $periodoId,
                'error' => $e->getMessage(),
            ]);
            $this->redirectWithError($volverForm, 'No se pudo registrar la calificación extraordinaria.');
        }

        $avisoBoleta = $this->calModel->queryOne(
            "SELECT estado FROM periodos WHERE id = ?", [$periodoId]
        );
        $extraAviso = ($avisoBoleta && $avisoBoleta['estado'] === 'cerrado')
            ? ' La nota ya es visible en la boleta de la familia (bimestre cerrado).'
            : '';

        $this->redirectWithSuccess($volverLista,
            'Calificación extraordinaria registrada (' . fmt_nota($nota) . ' · ' . $literal . ').'
            . ' No cuenta para el orden de mérito.' . $extraAviso);
    }

    /**
     * GET /rectificaciones/editar?matricula=&carga=&competencia=&periodo=
     * Formulario de rectificación por criterio de UNA competencia.
     */
    public function editar(): void
    {
        $matriculaId   = (int) $this->query('matricula');
        $cargaId       = (int) $this->query('carga');
        $competenciaId = (int) $this->query('competencia');
        $periodoId     = (int) $this->query('periodo');

        $info = $this->model->getMatriculaInfo($matriculaId);
        if (!$info) {
            $this->notFound();
        }

        // Invariante de seguridad: solo competencias rectificables.
        if (!$this->model->esRectificable($matriculaId, $cargaId, $competenciaId, $periodoId)) {
            $this->redirectWithError(
                url('rectificaciones/matricula/' . $matriculaId),
                'Esa competencia no es rectificable (debe estar bloqueada o en un bimestre cerrado).'
            );
        }

        $detalle = $this->model->getDetalleCompetencia($matriculaId, $cargaId, $competenciaId, $periodoId);
        if (!$detalle) {
            $this->notFound();
        }

        $this->view('rectificaciones/editar', [
            'titulo'        => 'Rectificar calificación',
            'info'          => $info,
            'meta'          => $detalle['meta'],
            'criterios'     => $detalle['criterios'],
            'cargaId'       => $cargaId,
            'competenciaId' => $competenciaId,
            'periodoId'     => $periodoId,
            'page_scripts'  => ['rectificaciones'],
        ]);
    }

    /**
     * POST /rectificaciones/guardar
     * Aplica la rectificación por criterio + recálculo + conclusión, deja
     * la traza de auditoría y regenera el snapshot del orden de mérito.
     */
    public function guardar(): void
    {
        $this->validateCsrf();

        $matriculaId   = (int) $this->input('matricula_id');
        $cargaId       = (int) $this->input('carga_id');
        $competenciaId = (int) $this->input('competencia_id');
        $periodoId     = (int) $this->input('periodo_id');
        $motivo        = trim((string) $this->input('motivo', ''));
        $conclusion    = trim((string) $this->input('conclusion', ''));
        $notasPost     = $this->input('notas', []);
        $usuarioId     = (int) (Session::user()['id'] ?? 0);

        $volverEditar = url('rectificaciones/editar?matricula=' . $matriculaId
            . '&carga=' . $cargaId . '&competencia=' . $competenciaId . '&periodo=' . $periodoId);
        $volverLista  = url('rectificaciones/matricula/' . $matriculaId);

        // ── Validaciones de entrada ──────────────────────────────
        $info = $this->model->getMatriculaInfo($matriculaId);
        if (!$info) {
            $this->notFound();
        }
        if ($motivo === '') {
            $this->redirectWithError($volverEditar, 'El motivo de la rectificación es obligatorio.');
        }
        // Invariante de seguridad: estado rectificable.
        if (!$this->model->esRectificable($matriculaId, $cargaId, $competenciaId, $periodoId)) {
            $this->redirectWithError($volverLista,
                'Esa competencia no es rectificable (debe estar bloqueada o en un bimestre cerrado).');
        }

        $detalle = $this->model->getDetalleCompetencia($matriculaId, $cargaId, $competenciaId, $periodoId);
        if (!$detalle) {
            $this->notFound();
        }
        $criterios = $detalle['criterios'];
        if (empty($criterios)) {
            $this->redirectWithError($volverLista,
                'Esta competencia no tiene criterios registrados; no puede rectificarse por criterio.');
        }

        // Solo se aceptan notas de criterios válidos de esta competencia.
        $idsValidos = array_map(static fn($c) => (int) $c['id'], $criterios);
        $notas      = [];
        if (is_array($notasPost)) {
            foreach ($notasPost as $cid => $valor) {
                $cid = (int) $cid;
                if (in_array($cid, $idsValidos, true) && $valor !== '' && $valor !== null) {
                    $notas[$cid] = max(0, min(20, (int) $valor));
                }
            }
        }
        if (empty($notas)) {
            $this->redirectWithError($volverEditar, 'Ingresa al menos una nota de criterio.');
        }

        // Estado ANTERIOR (para la traza).
        $notaAnterior       = $detalle['meta']['nota_actual'] !== null ? (int) $detalle['meta']['nota_actual'] : null;
        $conclusionAnterior = $detalle['meta']['conclusion_actual'];
        $nivelCodigo        = (string) $info['nivel_codigo'];

        // ── Escritura atómica ────────────────────────────────────
        $this->model->beginTransaction();
        try {
            foreach ($notas as $criterioId => $nota) {
                $this->calModel->guardarNotaCriterio($criterioId, $matriculaId, $nota);
            }

            $promedio = $this->calModel->calcularPromedio($matriculaId, $cargaId, $competenciaId, $periodoId);
            if ($promedio === null) {
                $this->model->rollback();
                $this->redirectWithError($volverEditar, 'No se pudo calcular el promedio. Revisa las notas.');
            }
            $notaNueva = (int) round($promedio);
            $literal   = nota_a_literal($notaNueva);

            // Conclusión obligatoria según literal + nivel.
            if (CalificacionModel::conclusionObligatoria($literal, $nivelCodigo) && $conclusion === '') {
                $this->model->rollback();
                $this->redirectWithError($volverEditar,
                    'La conclusión descriptiva es obligatoria para el literal ' . $literal . ' en este nivel.');
            }

            $this->calModel->guardarNotaFinal(
                $matriculaId, $cargaId, $periodoId, $competenciaId, $notaNueva, $usuarioId
            );
            $this->calModel->actualizarConclusion(
                $matriculaId, $cargaId, $competenciaId, $periodoId, $conclusion
            );

            $this->model->registrar([
                'matricula_id'        => $matriculaId,
                'carga_id'            => $cargaId,
                'periodo_id'          => $periodoId,
                'competencia_id'      => $competenciaId,
                'nota_anterior'       => $notaAnterior,
                'nota_nueva'          => $notaNueva,
                'conclusion_anterior' => $conclusionAnterior,
                'conclusion_nueva'    => $conclusion !== '' ? $conclusion : null,
                'motivo'              => $motivo,
                'rectificado_por'     => $usuarioId,
            ]);

            $this->model->commit();
        } catch (\Exception $e) {
            $this->model->rollback();
            log_error('Error al rectificar calificación', [
                'matricula' => $matriculaId, 'carga' => $cargaId,
                'competencia' => $competenciaId, 'periodo' => $periodoId,
                'error' => $e->getMessage(),
            ]);
            $this->redirectWithError($volverEditar, 'No se pudo aplicar la rectificación.');
        }

        // ── Regeneración del orden de mérito + aviso de empate ────
        // El snapshot lee del cálculo en vivo (ya con la corrección aplicada).
        $avisoEmpate = '';
        try {
            $this->ordenMeritoModel->generarSnapshot($periodoId, $usuarioId);
            if ($this->ordenMeritoModel->gradoTieneEmpateLivePendiente((int) $info['grado_id'], $periodoId)) {
                $avisoEmpate = ' Atención: el ranking del grado quedó con un empate pendiente de '
                    . 'resolver; coordina con el director para definirlo.';
            }
        } catch (\Exception $e) {
            log_error('Error al regenerar snapshot tras rectificación', [
                'periodo' => $periodoId, 'error' => $e->getMessage(),
            ]);
            $avisoEmpate = ' (No se pudo regenerar el orden de mérito automáticamente; revísalo manualmente.)';
        }

        $this->redirectWithSuccess($volverLista,
            'Rectificación aplicada.' . $avisoEmpate);
    }

    /** Respuesta 404 estándar del proyecto. */
    private function notFound(): never
    {
        http_response_code(404);
        $this->view('shared/404');
        exit;
    }
}
