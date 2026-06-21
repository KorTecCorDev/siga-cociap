<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnioAcademicoModel;
use App\Models\ControlOperativoModel;
use Core\Session;

/**
 * ControlOperativoController
 * Centro de Control Operativo: detecta inconsistencias de datos y enlaza al módulo
 * donde se corrigen. Ademas orquesta el HITO A del cierre de bimestre (aprobar
 * boletas -> borrador para los docentes).
 */
class ControlOperativoController extends BaseController
{
    private ControlOperativoModel $model;
    private AnioAcademicoModel    $anioModel;

    public function __construct()
    {
        $this->requireRole([
            'admin', 'registro_academico',
            'director_general', 'director_ebr',
        ]);
        $this->model     = new ControlOperativoModel();
        $this->anioModel = new AnioAcademicoModel();
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
                'estadoBoleta' => 'registro',
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
            'estadoBoleta'     => boleta_estado_bimestre($periodo['estado'] ?? null, $periodo['boletas_aprobadas_en'] ?? null),
        ]);
    }

    /**
     * POST /admin/control/{periodo_id}/aprobar-bimestre
     * HITO A: bloquea y aprueba el bimestre -> genera boletas BORRADOR para los
     * docentes. Fuerza el bloqueo de competencias pendientes (Incidencias).
     */
    public function aprobarBimestre(string $periodoId): void
    {
        $this->validateCsrf();
        $periodoId = (int) $periodoId;
        $periodo   = $this->model->getPeriodo($periodoId);
        $volver    = url('admin/control?periodo_id=' . $periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/control'), 'Bimestre no encontrado.');
        }
        if ($periodo['estado'] !== 'activo') {
            $this->redirectWithError($volver, 'Solo se puede aprobar un bimestre activo.');
        }
        if (!empty($periodo['boletas_aprobadas_en'])) {
            $this->redirectWithError($volver, 'Las boletas de este bimestre ya estan en borrador.');
        }

        $usuarioId = (int) (Session::user()['id'] ?? 0);
        try {
            $this->anioModel->beginTransaction();
            $incidencias = $this->anioModel->aprobarBoletasBimestre($periodoId, $usuarioId);
            $this->anioModel->commit();
        } catch (\Exception $e) {
            $this->anioModel->rollback();
            log_error('Error aprobando boletas del bimestre', ['id' => $periodoId, 'error' => $e->getMessage()]);
            $this->redirectWithError($volver, 'No se pudo aprobar el bimestre. Intenta de nuevo.');
        }

        $msg = 'Bimestre aprobado: las boletas BORRADOR ya estan disponibles para los docentes.';
        if ($incidencias > 0) {
            $msg .= ' Se forzo el bloqueo de ' . $incidencias . ' competencia(s) pendiente(s).';
        }
        $this->redirectWithSuccess($volver, $msg);
    }

    /**
     * POST /admin/control/{periodo_id}/anular-aprobacion
     * Revierte el HITO A (BORRADOR -> EN REGISTRO). No libera bloqueos.
     */
    public function anularAprobacion(string $periodoId): void
    {
        $this->validateCsrf();
        $periodoId = (int) $periodoId;
        $periodo   = $this->model->getPeriodo($periodoId);
        $volver    = url('admin/control?periodo_id=' . $periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/control'), 'Bimestre no encontrado.');
        }
        if ($periodo['estado'] !== 'activo' || empty($periodo['boletas_aprobadas_en'])) {
            $this->redirectWithError($volver, 'Este bimestre no tiene boletas en borrador para revertir.');
        }

        $this->anioModel->anularAprobacionBoletas($periodoId);
        $this->redirectWithSuccess($volver, 'Aprobacion revertida: las boletas borrador dejaron de mostrarse.');
    }
}
