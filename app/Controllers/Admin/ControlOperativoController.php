<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ControlOperativoModel;

/**
 * ControlOperativoController
 * Centro de Control Operativo: detecta inconsistencias de datos y enlaza al módulo
 * donde se corrigen. Solo lectura — no modifica nada.
 */
class ControlOperativoController extends BaseController
{
    private ControlOperativoModel $model;

    public function __construct()
    {
        $this->requireRole([
            'admin', 'registro_academico',
            'director_general', 'director_ebr',
        ]);
        $this->model = new ControlOperativoModel();
    }

    /**
     * GET /admin/control  (acepta ?periodo_id)
     * Arma los 4 chequeos para el periodo seleccionado (o el activo por defecto).
     */
    public function index(): void
    {
        $periodos = $this->model->getPeriodos();

        $periodoId = (int) ($this->query('periodo_id', 0));
        $periodo = $periodoId > 0
            ? $this->model->getPeriodo($periodoId)
            : $this->model->getPeriodoPorDefecto();

        if (!$periodo) {
            $this->view('admin/control/index', [
                'titulo'   => 'Centro de Control',
                'periodos' => $periodos,
                'periodo'  => null,
                'chequeos' => [],
                'totalIncidencias' => 0,
            ]);
            return;
        }

        $periodoId = (int) $periodo['id'];
        $anioId    = (int) $periodo['anio_id'];

        $chequeos = [
            'empates' => [
                'titulo'    => 'Empates de orden de mérito sin resolver',
                'severidad' => 'critico',
                'accion'    => 'Resolver en orden de mérito',
                'items'     => $this->model->empatesPendientes($periodoId),
            ],
            'competencias' => [
                'titulo'    => 'Competencias con notas sin bloquear',
                'severidad' => 'critico',
                'accion'    => 'Ir a bloqueos del bimestre',
                'accion_url'=> url('director/bloqueos'),
                'items'     => $this->model->competenciasSinBloquear($periodoId),
            ],
            'tutores' => [
                'titulo'    => 'Secciones sin tutor asignado',
                'severidad' => 'advertencia',
                'accion'    => 'Ir a secciones y tutores',
                'accion_url'=> url('admin/secciones'),
                'items'     => $this->model->seccionesSinTutor(),
            ],
            'matriculas' => [
                'titulo'    => 'Matrículas pendientes de activar',
                'severidad' => 'advertencia',
                'accion'    => 'Ir a matrículas',
                'accion_url'=> url('matriculas'),
                'items'     => $this->model->matriculasPendientes($anioId),
            ],
        ];

        $totalIncidencias = 0;
        foreach ($chequeos as $c) {
            $totalIncidencias += count($c['items']);
        }

        $this->view('admin/control/index', [
            'titulo'           => 'Centro de Control',
            'periodos'         => $periodos,
            'periodo'          => $periodo,
            'chequeos'         => $chequeos,
            'totalIncidencias' => $totalIncidencias,
        ]);
    }
}
