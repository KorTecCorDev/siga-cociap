<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SeccionModel;

class SeccionController extends BaseController
{
    private SeccionModel $model;

    public function __construct()
    {
        $this->requireRole('admin');
        $this->model = new SeccionModel();
    }

    // GET /admin/secciones
    public function index(): void
    {
        $this->view('admin/secciones/index', [
            'titulo'    => 'Secciones y Tutores',
            'secciones' => $this->model->listarConTutor(),
            'docentes'  => $this->model->listarDocentes(),
        ]);
    }

    // POST /admin/secciones/{id}/tutor
    public function asignarTutor(string $id): void
    {
        $this->validateCsrf();
        $seccionId = (int) $id;
        $raw       = $this->input('tutor_id');
        $tutorId   = ($raw === '' || $raw === null) ? null : (int) $raw;

        try {
            $this->model->asignarTutor($seccionId, $tutorId);
            $this->redirectWithSuccess(
                url('admin/secciones'),
                $tutorId ? 'Tutor asignado correctamente.' : 'Tutor removido de la sección.'
            );
        } catch (\Exception $e) {
            $this->redirectWithError(
                url('admin/secciones'),
                'Error: ' . $e->getMessage()
            );
        }
    }
}
