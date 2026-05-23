<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ConductaModel;
use Core\Session;

class ConductaController extends BaseController
{
    private ConductaModel $model;

    public function __construct()
    {
        $this->requireRole('admin');
        $this->model = new ConductaModel();
    }

    // GET /admin/conducta
    public function index(): void
    {
        $secciones = $this->model->listarSeccionesActivas();
        $periodos  = $this->model->listarPeriodosActivos();

        // Periodo abierto actual: el progreso de las secciones refleja el
        // llenado del periodo en curso (coherente con la vista de detalle
        // que solo expone el editable). Si no hay periodo activo, $progreso
        // queda vacío y la vista omite la barra.
        $periodoActivo = null;
        foreach ($periodos as $p) {
            if ((bool) $p['editable']) {
                $periodoActivo = $p;
                break;
            }
        }

        $progreso = $periodoActivo
            ? $this->model->getProgresoConductaPorSeccion((int) $periodoActivo['id'])
            : [];

        // Agrupar secciones por nivel
        $porNivel = [];
        foreach ($secciones as $s) {
            $porNivel[$s['nivel_nombre']][] = $s;
        }

        $this->view('admin/conducta/index', [
            'titulo'        => 'Calificaciones de Conducta',
            'porNivel'      => $porNivel,
            'periodos'      => $periodos,
            'periodoActivo' => $periodoActivo,
            'progreso'      => $progreso,
        ]);
    }

    // GET /admin/conducta/{seccion_id}
    public function seccion(string $seccionId): void
    {
        $seccionId = (int) $seccionId;
        $periodos  = $this->model->listarPeriodosActivos();

        if (empty($periodos)) {
            $this->redirectWithError(url('admin/conducta'), 'No hay periodos configurados.');
        }

        // Solo mostramos los periodos editables (los cerrados o vencidos
        // permanecen ocultos en esta vista). array_values reindexa para
        // que las claves sean 0..N tras el filtrado.
        $periodos = array_values(array_filter(
            $periodos,
            fn(array $p): bool => (bool) $p['editable']
        ));

        $periodoIds  = array_column($periodos, 'id');
        $estudiantes = $this->model->getEstudiantesConConducra($seccionId, $periodoIds);

        // Info de la sección
        $secciones = $this->model->listarSeccionesActivas();
        $seccion   = null;
        foreach ($secciones as $s) {
            if ((int)$s['id'] === $seccionId) {
                $seccion = $s;
                break;
            }
        }

        if (!$seccion) {
            $this->redirectWithError(url('admin/conducta'), 'Sección no encontrada.');
        }

        $this->view('admin/conducta/seccion', [
            'titulo'       => 'Conducta — ' . $seccion['grado_nombre'] . ' ' . $seccion['seccion_nombre'],
            'seccion'      => $seccion,
            'periodos'     => $periodos,
            'estudiantes'  => $estudiantes,
            'literales'    => ['AD', 'A', 'B', 'C'],
            'page_scripts' => ['conducta'],
        ]);
    }

    // POST /admin/conducta/guardar  (AJAX)
    public function guardar(): void
    {
        $this->validateCsrf();

        $matriculaId = (int)   $this->input('matricula_id');
        $periodoId   = (int)   $this->input('periodo_id');
        $literal     = trim((string) $this->input('literal', ''));
        $userId      = (int) Session::user()['id'];

        if (!$matriculaId || !$periodoId) {
            $this->json(['success' => false, 'mensaje' => 'Datos incompletos.'], 400);
        }

        // Verificar que el periodo esté abierto
        if (!$this->model->periodoEditable($periodoId)) {
            $this->json(['success' => false, 'mensaje' => 'El periodo no está disponible para edición.'], 403);
        }

        // Literal vacío = eliminar la nota
        if ($literal === '' || $literal === 'null') {
            $this->model->eliminar($matriculaId, $periodoId);
            $this->json(['success' => true, 'mensaje' => 'Nota eliminada.']);
        }

        if (!in_array($literal, ['AD', 'A', 'B', 'C'], true)) {
            $this->json(['success' => false, 'mensaje' => 'Literal inválido.'], 400);
        }

        $ok = $this->model->guardar($matriculaId, $periodoId, $literal, $userId);
        $this->json([
            'success' => $ok,
            'mensaje' => $ok ? 'Guardado.' : 'Error al guardar.',
        ], $ok ? 200 : 500);
    }
}
