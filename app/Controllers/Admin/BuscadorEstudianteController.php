<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EstudianteModel;

class BuscadorEstudianteController extends BaseController
{
    private EstudianteModel $model;

    public function __construct()
    {
        $this->requireRole([
            'admin',
            'registro_academico',
            'secretaria',
            'director_general',
            'director_ebr',
        ]);
        $this->model = new EstudianteModel();
    }

    // GET /admin/buscar-estudiante
    public function index(): void
    {
        $anio = $this->model->anioActivo();

        $this->view('admin/buscar-estudiante/index', [
            'titulo'       => 'Buscador de Estudiantes',
            'anioActivo'   => $anio,
            'page_scripts' => ['buscador-estudiante'],
        ]);
    }

    // GET /admin/buscar-estudiante/api?q=...
    public function buscar(): void
    {
        $termino = trim((string) $this->query('q', ''));
        $anio    = $this->model->anioActivo();

        if (!$anio) {
            $this->json([
                'success' => false,
                'mensaje' => 'No hay un año académico activo configurado.',
            ]);
            return;
        }

        if (mb_strlen($termino) < 2) {
            $this->json([
                'success'     => true,
                'resultados'  => [],
                'anio'        => (int) $anio['anio'],
                'termino'     => $termino,
            ]);
            return;
        }

        $filas      = $this->model->buscarEnAnioActivo($termino, (int) $anio['id']);
        $resultados = array_map(function (array $f): array {
            return [
                'dni'      => $f['dni'],
                'nombre'   => mb_strtoupper($f['apellido_paterno']) . ' '
                            . mb_strtoupper($f['apellido_materno']) . ', '
                            . $f['nombres'],
                'sexo'     => $f['sexo'],
                'nivel'    => $f['nivel_nombre'],
                'grado'    => $f['grado_nombre'],
                'seccion'  => $f['seccion_nombre'],
                'estado'   => $f['matricula_estado'],
            ];
        }, $filas);

        $this->json([
            'success'    => true,
            'resultados' => $resultados,
            'anio'       => (int) $anio['anio'],
            'termino'    => $termino,
        ]);
    }
}
