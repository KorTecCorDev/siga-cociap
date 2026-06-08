<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EstudianteModel;
use App\Models\OrdenMeritoModel;

class BuscadorEstudianteController extends BaseController
{
    private EstudianteModel  $model;
    private OrdenMeritoModel $ordenMeritoModel;

    public function __construct()
    {
        $this->requireRole([
            'admin',
            'registro_academico',
            'secretaria',
            'director_general',
            'director_ebr',
        ]);
        $this->model            = new EstudianteModel();
        $this->ordenMeritoModel = new OrdenMeritoModel();
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

        // El puesto del orden de mérito corresponde al ULTIMO bimestre cerrado.
        // Si aún no hay ninguno cerrado (estamos en el I Bimestre), no hay orden
        // de mérito vigente y no se calcula puesto.
        $bimestre   = $this->model->ultimoBimestreCerrado((int) $anio['id']);
        $periodoId  = $bimestre ? (int) $bimestre['id'] : null;

        $filas = $this->model->buscarEnAnioActivo($termino, (int) $anio['id']);

        // Puesto del orden de mérito: misma fuente que el ranking oficial
        // (OrdenMeritoModel, con cascada de desempate y resolución manual).
        $puestos = [];
        if ($periodoId !== null && $filas) {
            $gradoIds = array_filter(array_map(
                static fn($f) => (int) ($f['grado_id'] ?? 0),
                $filas
            ));
            if ($gradoIds) {
                $puestos = $this->ordenMeritoModel->puestosPorGrado($gradoIds, $periodoId);
            }
        }

        $resultados = array_map(function (array $f) use ($puestos): array {
            $tutor = !empty($f['tutor_apellido_paterno'])
                ? mb_strtoupper($f['tutor_apellido_paterno']) . ' '
                . mb_strtoupper($f['tutor_apellido_materno']) . ', '
                . $f['tutor_nombres']
                : null;

            $puesto = $puestos[(int) $f['matricula_id']]['puesto'] ?? null;

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
                'tutor'    => $tutor,
                'puesto'   => $puesto !== null ? (int) $puesto : null,
            ];
        }, $filas);

        $this->json([
            'success'          => true,
            'resultados'       => $resultados,
            'anio'             => (int) $anio['anio'],
            'bimestre'         => $bimestre['nombre_display'] ?? null,
            'tieneOrdenMerito' => $bimestre !== null,
            'termino'          => $termino,
        ]);
    }
}
