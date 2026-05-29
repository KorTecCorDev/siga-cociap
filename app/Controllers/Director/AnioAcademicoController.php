<?php

namespace App\Controllers\Director;

use App\Controllers\BaseController;
use App\Models\AnioAcademicoModel;

/**
 * AnioAcademicoController
 * Gestión de años académicos: listado, creación, activación y cierre.
 * Los bimestres de cada año se gestionan en PeriodoController.
 */
class AnioAcademicoController extends BaseController
{
    private AnioAcademicoModel $model;

    private const ROLES = ['admin', 'director_general', 'director_ebr', 'registro_academico'];

    public function __construct()
    {
        $this->requireRole(self::ROLES);
        $this->model = new AnioAcademicoModel();
    }

    // GET /director/anios
    public function index(): void
    {
        $this->view('director/anios/index', [
            'titulo' => 'Año académico',
            'anios'  => $this->model->listarAnios(),
        ]);
    }

    // GET /director/anios/crear
    public function create(): void
    {
        $this->view('director/anios/crear', [
            'titulo'        => 'Nuevo año académico',
            'anioSugerido'  => (int) date('Y') + 1,
        ]);
    }

    // POST /director/anios/crear
    public function store(): void
    {
        $this->validateCsrf();

        $anio = (int) $this->input('anio', 0);

        if ($anio < 2000 || $anio > 2100) {
            $this->redirectWithError(url('director/anios/crear'), 'Ingresa un año válido (entre 2000 y 2100).');
        }

        if ($this->model->existeAnio($anio)) {
            $this->redirectWithError(url('director/anios/crear'), "El año académico {$anio} ya existe.");
        }

        try {
            $anioId = $this->model->crearAnio($anio);
        } catch (\Exception $e) {
            $this->redirectWithError(url('director/anios/crear'), 'No se pudo crear el año académico. Intenta de nuevo.');
        }

        $this->redirectWithSuccess(
            url('director/anios/' . $anioId),
            "Año académico {$anio} creado con sus 4 bimestres. Ajusta las fechas y actívalo cuando corresponda."
        );
    }

    // GET /director/anios/{id}
    public function show(string $id): void
    {
        $id   = (int) $id;
        $anio = $this->model->find($id);

        if (!$anio) {
            $this->redirectWithError(url('director/anios'), 'Año académico no encontrado.');
        }

        $periodos = $this->model->getPeriodos($id);

        // Si venimos de cerrar un bimestre, preparamos sus indicadores para el modal.
        $cerradoId  = (int) ($this->query('cerrado', 0));
        $statsCierre = null;
        $periodoCerrado = null;
        if ($cerradoId > 0) {
            foreach ($periodos as $p) {
                if ((int) $p['id'] === $cerradoId) {
                    $periodoCerrado = $p;
                    break;
                }
            }
            if ($periodoCerrado) {
                $statsCierre = $this->model->getStatsCierre($cerradoId);
            }
        }

        $this->view('director/anios/show', [
            'titulo'         => 'Año académico ' . $anio['anio'],
            'anio'           => $anio,
            'periodos'       => $periodos,
            'page_scripts'   => ['anio-academico'],
            'cerradoId'      => $cerradoId,
            'periodoCerrado' => $periodoCerrado,
            'statsCierre'    => $statsCierre,
        ]);
    }

    // POST /director/anios/{id}/activar
    public function activar(string $id): void
    {
        $this->validateCsrf();
        $id   = (int) $id;
        $anio = $this->model->find($id);

        if (!$anio) {
            $this->redirectWithError(url('director/anios'), 'Año académico no encontrado.');
        }
        if ($anio['estado'] === 'activo') {
            $this->redirectWithError(url('director/anios/' . $id), 'El año académico ya está activo.');
        }
        if ($anio['estado'] === 'cerrado') {
            $this->redirectWithError(url('director/anios/' . $id), 'No se puede activar un año académico cerrado.');
        }

        try {
            $this->model->activarAnio($id);
        } catch (\Exception $e) {
            $this->redirectWithError(url('director/anios/' . $id), 'No se pudo activar el año académico.');
        }

        $this->redirectWithSuccess(
            url('director/anios/' . $id),
            "Año académico {$anio['anio']} activado. Cualquier otro año activo fue cerrado."
        );
    }

    // POST /director/anios/{id}/cerrar
    public function cerrar(string $id): void
    {
        $this->validateCsrf();
        $id   = (int) $id;
        $anio = $this->model->find($id);

        if (!$anio) {
            $this->redirectWithError(url('director/anios'), 'Año académico no encontrado.');
        }
        if ($anio['estado'] === 'cerrado') {
            $this->redirectWithError(url('director/anios/' . $id), 'El año académico ya está cerrado.');
        }
        if ($this->model->tieneBimestreActivo($id)) {
            $this->redirectWithError(
                url('director/anios/' . $id),
                'No se puede cerrar el año: aún hay un bimestre activo. Ciérralo primero.'
            );
        }

        $this->model->cerrarAnio($id);

        $this->redirectWithSuccess(
            url('director/anios/' . $id),
            "Año académico {$anio['anio']} cerrado."
        );
    }
}
