<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AsistenciaModel;
use Core\Session;

class AsistenciaController extends BaseController
{
    /** Tope duro por contador (HTML5 max + validación server). */
    private const TOPE_MAX = 99;

    private AsistenciaModel $model;

    public function __construct()
    {
        // Cuando se cree el rol auxiliar_academico, se añade al array.
        $this->requireRole(['admin', 'registro_academico']);
        $this->model = new AsistenciaModel();
    }

    // GET /admin/asistencia
    public function index(): void
    {
        $secciones = $this->model->listarSeccionesActivas();
        $periodos  = $this->model->listarPeriodosActivos();

        // Periodo abierto actual: el progreso del índice refleja el llenado
        // del bimestre en curso. Si no hay periodo activo, no hay barra.
        $periodoActivo = null;
        foreach ($periodos as $p) {
            if ((bool) $p['editable']) {
                $periodoActivo = $p;
                break;
            }
        }

        $progreso = $periodoActivo
            ? $this->model->getProgresoPorSeccion((int) $periodoActivo['id'])
            : [];

        $porNivel = [];
        foreach ($secciones as $s) {
            $porNivel[$s['nivel_nombre']][] = $s;
        }

        $this->view('admin/asistencia/index', [
            'titulo'        => 'Asistencia — Incidencias',
            'porNivel'      => $porNivel,
            'periodoActivo' => $periodoActivo,
            'progreso'      => $progreso,
        ]);
    }

    // GET /admin/asistencia/{seccion_id}
    public function seccion(string $seccionId): void
    {
        $seccionId = (int) $seccionId;
        $periodos  = $this->model->listarPeriodosActivos();

        if (empty($periodos)) {
            $this->redirectWithError(url('admin/asistencia'), 'No hay periodos configurados.');
        }

        // Solo el periodo abierto actual entra a la vista de ingreso.
        $periodoActivo = null;
        foreach ($periodos as $p) {
            if ((bool) $p['editable']) {
                $periodoActivo = $p;
                break;
            }
        }

        $estudiantes = $periodoActivo
            ? $this->model->getEstudiantesConIncidencias($seccionId, (int) $periodoActivo['id'])
            : [];

        // Info de la sección (reutilizamos el listado activo)
        $secciones = $this->model->listarSeccionesActivas();
        $seccion   = null;
        foreach ($secciones as $s) {
            if ((int) $s['id'] === $seccionId) {
                $seccion = $s;
                break;
            }
        }

        if (!$seccion) {
            $this->redirectWithError(url('admin/asistencia'), 'Sección no encontrada.');
        }

        $this->view('admin/asistencia/seccion', [
            'titulo'        => 'Asistencia — ' . $seccion['grado_nombre'] . ' ' . $seccion['seccion_nombre'],
            'seccion'       => $seccion,
            'periodoActivo' => $periodoActivo,
            'estudiantes'   => $estudiantes,
            'topeMax'       => self::TOPE_MAX,
            'page_scripts'  => ['asistencia'],
        ]);
    }

    // POST /admin/asistencia/guardar  (AJAX, batch por fila)
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

        // Saneamiento y validación de los 4 contadores. Cualquier valor
        // fuera de [0..TOPE_MAX] o no numérico se rechaza con 400.
        $campos = [
            'faltas'                 => $this->input('faltas'),
            'faltas_justificadas'    => $this->input('faltas_justificadas'),
            'tardanzas'              => $this->input('tardanzas'),
            'tardanzas_justificadas' => $this->input('tardanzas_justificadas'),
        ];

        $valores = [];
        foreach ($campos as $nombre => $crudo) {
            $crudo = trim((string) $crudo);
            if ($crudo === '' || !ctype_digit($crudo)) {
                $this->json([
                    'success' => false,
                    'mensaje' => "El campo {$nombre} debe ser un entero entre 0 y " . self::TOPE_MAX . '.',
                ], 400);
            }
            $n = (int) $crudo;
            if ($n < 0 || $n > self::TOPE_MAX) {
                $this->json([
                    'success' => false,
                    'mensaje' => "El campo {$nombre} debe estar entre 0 y " . self::TOPE_MAX . '.',
                ], 400);
            }
            $valores[$nombre] = $n;
        }

        $ok = $this->model->guardar(
            $matriculaId,
            $periodoId,
            $valores['faltas'],
            $valores['faltas_justificadas'],
            $valores['tardanzas'],
            $valores['tardanzas_justificadas'],
            $userId
        );

        $this->json([
            'success' => $ok,
            'mensaje' => $ok ? 'Guardado.' : 'Error al guardar.',
        ], $ok ? 200 : 500);
    }
}
