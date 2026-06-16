<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ConductaModel;
use Core\Session;

/**
 * Conducta — ETAPA 1 (Registro Academico).
 * Registra los criterios Si/No por alumno y bloquea/aprueba la seccion.
 * La ETAPA 2 (tutor) vive en Docente\ConductaTutorController.
 */
class ConductaController extends BaseController
{
    private ConductaModel $model;

    public function __construct()
    {
        // Conducta ahora la registra Registro Academico (ademas de admin).
        $this->requireRole(['admin', 'registro_academico']);
        $this->model = new ConductaModel();
    }

    /** Devuelve el primer periodo editable del año activo, o null. */
    private function periodoActivo(): ?array
    {
        foreach ($this->model->listarPeriodosActivos() as $p) {
            if ((bool) $p['editable']) {
                return $p;
            }
        }
        return null;
    }

    /** Busca una seccion del año activo por id, o null. */
    private function buscarSeccion(int $seccionId): ?array
    {
        foreach ($this->model->listarSeccionesActivas() as $s) {
            if ((int) $s['id'] === $seccionId) {
                return $s;
            }
        }
        return null;
    }

    // GET /admin/conducta
    public function index(): void
    {
        $secciones     = $this->model->listarSeccionesActivas();
        $periodoActivo = $this->periodoActivo();
        $progreso      = $periodoActivo
            ? $this->model->getProgresoConductaPorSeccion((int) $periodoActivo['id'])
            : [];

        $porNivel = [];
        foreach ($secciones as $s) {
            $porNivel[$s['nivel_nombre']][] = $s;
        }

        $this->view('admin/conducta/index', [
            'titulo'        => 'Calificaciones de Conducta',
            'porNivel'      => $porNivel,
            'periodoActivo' => $periodoActivo,
            'progreso'      => $progreso,
        ]);
    }

    // GET /admin/conducta/{seccion_id}
    public function seccion(string $seccionId): void
    {
        $seccionId     = (int) $seccionId;
        $seccion       = $this->buscarSeccion($seccionId);
        if (!$seccion) {
            $this->redirectWithError(url('admin/conducta'), 'Sección no encontrada.');
        }

        $periodoActivo = $this->periodoActivo();
        $nivelId       = (int) $seccion['nivel_id'];
        $criterios     = $this->model->getCriterios($nivelId);

        $estudiantes = $cierre = null;
        $completitud = ['esperados' => 0, 'completos' => 0];
        if ($periodoActivo) {
            $pid         = (int) $periodoActivo['id'];
            $estudiantes = $this->model->getEstudiantesParaRegistro($seccionId, $pid);
            $cierre      = $this->model->getCierreVigente($seccionId, $pid);
            $completitud = $this->model->completitudSeccion($seccionId, $pid, count($criterios));
        }

        $this->view('admin/conducta/seccion', [
            'titulo'        => 'Conducta — ' . $seccion['grado_nombre'] . ' ' . $seccion['seccion_nombre'],
            'seccion'       => $seccion,
            'periodoActivo' => $periodoActivo,
            'criterios'     => $criterios,
            'estudiantes'   => $estudiantes ?? [],
            'cierre'        => $cierre,
            'completitud'   => $completitud,
            'page_scripts'  => ['conducta'],
        ]);
    }

    // POST /admin/conducta/guardar  (AJAX — respuestas de un alumno)
    public function guardar(): void
    {
        $this->validateCsrf();

        $matriculaId = (int) $this->input('matricula_id');
        $periodoId   = (int) $this->input('periodo_id');
        $userId      = (int) Session::user()['id'];

        if (!$matriculaId || !$periodoId) {
            $this->json(['success' => false, 'mensaje' => 'Datos incompletos.'], 400);
        }
        if (!$this->model->periodoEditable($periodoId)) {
            $this->json(['success' => false, 'mensaje' => 'El periodo no está disponible para edición.'], 403);
        }

        $ctx = $this->model->contextoMatricula($matriculaId);
        if (!$ctx) {
            $this->json(['success' => false, 'mensaje' => 'Matrícula no encontrada.'], 404);
        }
        $seccionId = (int) $ctx['seccion_id'];
        $nivelId   = (int) $ctx['nivel_id'];

        // Si la seccion ya esta bloqueada, RA no puede editar (debe desbloquear admin).
        if ($this->model->getCierreVigente($seccionId, $periodoId)) {
            $this->json(['success' => false, 'mensaje' => 'La conducta de esta sección ya fue bloqueada; no se puede editar.'], 403);
        }

        $criterios   = $this->model->getCriterios($nivelId);
        $criterioIds = array_map(static fn($c) => (int) $c['id'], $criterios);
        $respIn      = $this->input('respuestas', []);
        if (!is_array($respIn)) {
            $respIn = [];
        }

        // Los criterios son OBLIGATORIOS: todos deben venir con 0 o 1.
        $respuestas = [];
        foreach ($criterioIds as $cid) {
            $v = $respIn[$cid] ?? null;
            if ($v === null || !in_array((string) $v, ['0', '1'], true)) {
                $this->json([
                    'success' => false,
                    'mensaje' => 'Debes responder Sí/No en los ' . count($criterioIds) . ' criterios.',
                ], 400);
            }
            $respuestas[$cid] = (int) $v;
        }

        $ok = $this->model->guardarRespuestas($matriculaId, $periodoId, $respuestas, $userId, $criterioIds);
        $this->json([
            'success' => $ok,
            'mensaje' => $ok ? 'Guardado.' : 'Error al guardar.',
        ], $ok ? 200 : 500);
    }

    // POST /admin/conducta/{seccion_id}/bloquear  (RA bloquea/aprueba la seccion)
    public function bloquear(string $seccionId): void
    {
        $this->validateCsrf();
        $seccionId = (int) $seccionId;

        $seccion = $this->buscarSeccion($seccionId);
        if (!$seccion) {
            $this->redirectWithError(url('admin/conducta'), 'Sección no encontrada.');
        }
        $periodoActivo = $this->periodoActivo();
        if (!$periodoActivo) {
            $this->redirectWithError(url('admin/conducta/' . $seccionId), 'No hay periodo abierto para edición.');
        }

        $total = $this->model->totalCriterios((int) $seccion['nivel_id']);
        $res   = $this->model->bloquearRA(
            $seccionId,
            (int) $periodoActivo['id'],
            (int) Session::user()['id'],
            $total
        );

        if ($res['ok']) {
            $this->redirectWithSuccess(url('admin/conducta/' . $seccionId), $res['mensaje']);
        }
        $this->redirectWithError(url('admin/conducta/' . $seccionId), $res['mensaje']);
    }
}
