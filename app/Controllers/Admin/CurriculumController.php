<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CurriculumModel;

class CurriculumController extends BaseController
{
    private CurriculumModel $model;

    public function __construct()
    {
        $this->requireRole('admin');
        $this->model = new CurriculumModel();
    }

    // GET /admin/curriculum
    public function index(): void
    {
        $nivelId = max(1, (int)($this->query('nivel') ?? 1));
        $areaId  = (int)($this->query('area') ?? 0);

        $niveles    = $this->model->getNiveles();
        $nivelesIds = array_map('intval', array_column($niveles, 'id'));

        if (!in_array($nivelId, $nivelesIds) && !empty($nivelesIds)) {
            $nivelId = $nivelesIds[0];
        }

        $areas = $this->model->getAreasParaSidebar($nivelId);

        if (!$areaId && !empty($areas)) {
            $areaId = (int)$areas[0]['id'];
        }

        $area = $areaId ? $this->model->getAreaDetalle($areaId) : null;

        $this->view('admin/curriculum/index', [
            'titulo'      => 'Curriculo Academico',
            'niveles'     => $niveles,
            'nivelActivo' => $nivelId,
            'areas'       => $areas,
            'areaId'      => $areaId,
            'area'        => $area,
        ]);
    }

    // POST /admin/curriculum/areas/{id}/editar
    public function guardarArea(string $id): void
    {
        $this->validateCsrf();
        $id      = (int)$id;
        $nivelId = (int)($this->input('nivel_id') ?? 1);
        $back    = url('admin/curriculum?nivel=' . $nivelId . '&area=' . $id);

        $nombre = trim($this->input('nombre') ?? '');
        if ($nombre === '') {
            $this->redirectWithError($back, 'El nombre del area es obligatorio.');
        }

        $this->model->actualizarArea($id, [
            'nombre'        => $nombre,
            'nombre_boleta' => trim($this->input('nombre_boleta') ?? '') ?: null,
            'alias_boleta'  => trim($this->input('alias_boleta')  ?? '') ?: null,
            'nombre_siagie' => trim($this->input('nombre_siagie') ?? '') ?: null,
            'orden'         => (int)($this->input('orden') ?? 0),
        ]);

        $this->redirectWithSuccess($back, 'Area actualizada correctamente.');
    }

    // POST /admin/curriculum/subareas/{id}/editar
    public function guardarSubarea(string $id): void
    {
        $this->validateCsrf();
        $id      = (int)$id;
        $areaId  = (int)($this->input('area_id')  ?? 0);
        $nivelId = (int)($this->input('nivel_id') ?? 1);
        $back    = url('admin/curriculum?nivel=' . $nivelId . '&area=' . $areaId);

        $nombre = trim($this->input('nombre') ?? '');
        if ($nombre === '') {
            $this->redirectWithError($back, 'El nombre de la subarea es obligatorio.');
        }

        $this->model->actualizarSubarea($id, $nombre, (int)($this->input('orden') ?? 0));
        $this->redirectWithSuccess($back, 'Subarea actualizada correctamente.');
    }

    // POST /admin/curriculum/competencias/{id}/editar
    public function guardarCompetencia(string $id): void
    {
        $this->validateCsrf();
        $id      = (int)$id;
        $areaId  = (int)($this->input('area_id')  ?? 0);
        $nivelId = (int)($this->input('nivel_id') ?? 1);
        $back    = url('admin/curriculum?nivel=' . $nivelId . '&area=' . $areaId);

        $nombreCompleto = trim($this->input('nombre_completo') ?? '');
        if ($nombreCompleto === '') {
            $this->redirectWithError($back, 'El nombre de la competencia es obligatorio.');
        }

        $this->model->actualizarCompetencia(
            $id,
            trim($this->input('codigo_minedu') ?? '') ?: null,
            $nombreCompleto,
            trim($this->input('nombre_corto') ?? '') ?: null,
            (int)($this->input('orden') ?? 0)
        );

        $this->redirectWithSuccess($back, 'Competencia actualizada correctamente.');
    }

    // POST /admin/curriculum/areas/{id}/mover
    public function moverArea(string $id): void
    {
        $this->validateCsrf();
        $id        = (int)$id;
        $nivelId   = (int)($this->input('nivel_id')  ?? 1);
        $areaId    = (int)($this->input('area_id')   ?? $id);
        $direccion = $this->input('direccion') === 'down' ? 'down' : 'up';
        $back      = url('admin/curriculum?nivel=' . $nivelId . '&area=' . $areaId);

        $this->model->moverArea($id, $direccion);
        $this->redirectWithSuccess($back, 'Orden actualizado.');
    }

    // POST /admin/curriculum/areas/{id}/toggle
    public function toggleActivaArea(string $id): void
    {
        $this->validateCsrf();
        $id      = (int)$id;
        $nivelId = (int)($this->input('nivel_id') ?? 1);
        $back    = url('admin/curriculum?nivel=' . $nivelId . '&area=' . $id);

        $this->model->toggleActivaArea($id);
        $this->redirectWithSuccess($back, 'Estado del area actualizado.');
    }
}
