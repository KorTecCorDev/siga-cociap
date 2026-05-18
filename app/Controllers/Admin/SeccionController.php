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
        $secciones = $this->model->listarConTutor();
        $docentes  = $this->model->listarDocentes();

        // Si una sección tiene tutor asignado pero ese docente ya no está
        // activo (cambió de rol o fue desactivado), lo agrega al select
        // marcado como inactivo para que el admin lo vea y pueda reasignar.
        $docenteIds = array_column($docentes, 'id');
        foreach ($secciones as $s) {
            if ($s['tutor_id'] && !in_array((int) $s['tutor_id'], $docenteIds)) {
                $docentes[] = [
                    'id'               => $s['tutor_id'],
                    'apellido_paterno' => $s['tutor_apellido_paterno'] ?? '',
                    'apellido_materno' => $s['tutor_apellido_materno'] ?? '',
                    'nombres'          => $s['tutor_nombres'] ?? '',
                    'dni'              => $s['tutor_dni'] ?? '',
                    'tutor_seccion_id' => $s['id'],
                    'inactivo'         => true,
                ];
            }
        }

        // JSON con los docentes para el select dinámico del modal
        $docentesJson = array_map(fn($d) => [
            'id'        => (int) $d['id'],
            'nombre'    => mb_strtoupper($d['apellido_paterno'] ?? '') . ' '
                         . mb_strtoupper($d['apellido_materno'] ?? '') . ', '
                         . ($d['nombres'] ?? ''),
            'dni'       => $d['dni'] ?? '',
            'seccionId' => (int) ($d['tutor_seccion_id'] ?? 0),
            'inactivo'  => !empty($d['inactivo']),
        ], $docentes);

        $this->view('admin/secciones/index', [
            'titulo'       => 'Secciones y Tutores',
            'secciones'    => $secciones,
            'docentesJson' => $docentesJson,
            'page_scripts' => ['secciones'],
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
            $this->json([
                'success' => true,
                'mensaje' => $tutorId
                    ? 'Tutor asignado correctamente.'
                    : 'Tutor removido de la sección.',
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
