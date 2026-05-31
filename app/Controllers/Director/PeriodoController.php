<?php

namespace App\Controllers\Director;

use App\Controllers\BaseController;
use App\Models\AnioAcademicoModel;
use Core\Session;

/**
 * PeriodoController
 * Gestión de bimestres dentro de un año académico:
 * edición de fechas, apertura, cierre (con bloqueo automático e indicadores)
 * y reapertura excepcional.
 */
class PeriodoController extends BaseController
{
    private AnioAcademicoModel $model;

    private const ROLES = ['admin', 'director_general', 'director_ebr', 'registro_academico'];

    public function __construct()
    {
        $this->requireRole(self::ROLES);
        $this->model = new AnioAcademicoModel();
    }

    // POST /director/periodos/{id}/editar
    public function editar(string $id): void
    {
        $this->validateCsrf();
        $id      = (int) $id;
        $periodo = $this->model->getPeriodo($id);

        if (!$periodo) {
            $this->redirectWithError(url('director/anios'), 'Bimestre no encontrado.');
        }

        $volverUrl = url('director/anios/' . $periodo['anio_id']);

        $fechaInicio = trim((string) $this->input('fecha_inicio', ''));
        $fechaFin    = trim((string) $this->input('fecha_fin', ''));
        $limiteRaw   = trim((string) $this->input('limite_notas', ''));

        if ($fechaInicio === '' || $fechaFin === '') {
            $this->redirectWithError($volverUrl, 'La fecha de inicio y la de fin son obligatorias.');
        }
        if ($fechaFin <= $fechaInicio) {
            $this->redirectWithError($volverUrl, 'La fecha de fin debe ser posterior a la de inicio.');
        }

        // El input datetime-local llega como "Y-m-dTH:i"; lo normalizamos a datetime SQL.
        $limiteNotas = null;
        if ($limiteRaw !== '') {
            $limiteNotas = str_replace('T', ' ', $limiteRaw);
            if (strlen($limiteNotas) === 16) {
                $limiteNotas .= ':00';
            }
        }

        $this->model->actualizarFechasPeriodo($id, $fechaInicio, $fechaFin, $limiteNotas);

        $this->redirectWithSuccess($volverUrl, "Fechas del {$periodo['nombre_display']} actualizadas.");
    }

    // POST /director/periodos/{id}/abrir
    public function abrir(string $id): void
    {
        $this->validateCsrf();
        $id      = (int) $id;
        $periodo = $this->model->getPeriodo($id);

        if (!$periodo) {
            $this->redirectWithError(url('director/anios'), 'Bimestre no encontrado.');
        }

        $volverUrl = url('director/anios/' . $periodo['anio_id']);

        if ($periodo['estado'] !== 'pendiente') {
            $this->redirectWithError($volverUrl, 'Solo se puede abrir un bimestre pendiente.');
        }
        if ($periodo['anio_estado'] !== 'activo') {
            $this->redirectWithError($volverUrl, 'El año académico debe estar activo para abrir un bimestre.');
        }
        if ($this->model->tieneBimestreActivo((int) $periodo['anio_id'])) {
            $this->redirectWithError($volverUrl, 'Ya hay un bimestre activo en este año. Ciérralo antes de abrir otro.');
        }

        $this->model->setEstadoPeriodo($id, 'activo');
        $this->redirectWithSuccess($volverUrl, "{$periodo['nombre_display']} abierto. Los docentes ya pueden registrar notas.");
    }

    // POST /director/periodos/{id}/cerrar
    public function cerrar(string $id): void
    {
        $this->validateCsrf();
        $id      = (int) $id;
        $periodo = $this->model->getPeriodo($id);

        if (!$periodo) {
            $this->redirectWithError(url('director/anios'), 'Bimestre no encontrado.');
        }

        $volverUrl = url('director/anios/' . $periodo['anio_id']);

        if ($periodo['estado'] !== 'activo') {
            $this->redirectWithError($volverUrl, 'Solo se puede cerrar un bimestre activo.');
        }

        $usuarioId = (int) (Session::user()['id'] ?? 0);

        try {
            $this->model->beginTransaction();
            $this->model->bloquearCompetenciasPendientes($id, $usuarioId);
            $this->model->setEstadoPeriodo($id, 'cerrado');
            $this->model->commit();
        } catch (\Exception $e) {
            $this->model->rollback();
            log_error('Error cerrando bimestre', ['id' => $id, 'error' => $e->getMessage()]);
            $this->redirectWithError($volverUrl, 'No se pudo cerrar el bimestre. Intenta de nuevo.');
        }

        // Redirige a la vista del año con el flag para abrir el modal de indicadores.
        $this->redirectWithSuccess(
            url('director/anios/' . $periodo['anio_id']) . '?cerrado=' . $id,
            "{$periodo['nombre_display']} cerrado. Las competencias pendientes quedaron bloqueadas."
        );
    }

    // POST /director/periodos/{id}/reabrir
    public function reabrir(string $id): void
    {
        $this->validateCsrf();
        $id      = (int) $id;
        $periodo = $this->model->getPeriodo($id);

        if (!$periodo) {
            $this->redirectWithError(url('director/anios'), 'Bimestre no encontrado.');
        }

        $volverUrl = url('director/anios/' . $periodo['anio_id']);

        if ($periodo['estado'] !== 'cerrado') {
            $this->redirectWithError($volverUrl, 'Solo se puede reabrir un bimestre cerrado.');
        }
        if ($periodo['anio_estado'] === 'cerrado') {
            $this->redirectWithError($volverUrl, 'No se puede reabrir un bimestre de un año cerrado.');
        }
        if ($this->model->tieneBimestreActivo((int) $periodo['anio_id'])) {
            $this->redirectWithError($volverUrl, 'Ya hay un bimestre activo en este año. Ciérralo antes de reabrir otro.');
        }

        // El motivo es obligatorio: reabrir es excepcional y debe quedar auditado.
        $motivo = trim((string) $this->input('motivo', ''));
        if (mb_strlen($motivo) < 10) {
            $this->redirectWithError(
                $volverUrl,
                'Debes indicar el motivo de la reapertura (mínimo 10 caracteres).'
            );
        }
        $motivo = mb_substr($motivo, 0, 500);

        $usuarioId = (int) (Session::user()['id'] ?? 0);

        try {
            $this->model->beginTransaction();
            $this->model->setEstadoPeriodo($id, 'activo');
            // Al reabrir, los bloqueos auto-generados por el cierre forzado
            // (sin notas detrás) congelarían a los docentes. Se eliminan para
            // que recuperen el acceso; los aprobados con notas se conservan.
            $liberadas = $this->model->eliminarBloqueosSinNotas($id);
            // Deja traza del motivo y de cuántos bloqueos se liberaron.
            $this->model->registrarReapertura($id, $motivo, $usuarioId, $liberadas);
            $this->model->commit();
        } catch (\Exception $e) {
            $this->model->rollback();
            log_error('Error reabriendo bimestre', ['id' => $id, 'error' => $e->getMessage()]);
            $this->redirectWithError($volverUrl, 'No se pudo reabrir el bimestre. Intenta de nuevo.');
        }

        $detalle = $liberadas > 0
            ? " Se liberaron {$liberadas} competencias sin notas para edición."
            : ' Los bloqueos con notas aprobadas se conservan.';
        $this->redirectWithSuccess($volverUrl, "{$periodo['nombre_display']} reabierto.{$detalle}");
    }

    // GET /director/periodos/{id}/stats
    public function stats(string $id): void
    {
        $id      = (int) $id;
        $periodo = $this->model->getPeriodo($id);

        if (!$periodo) {
            $this->redirectWithError(url('director/anios'), 'Bimestre no encontrado.');
        }

        $this->view('director/anios/stats', [
            'titulo'      => 'Indicadores — ' . $periodo['nombre_display'] . ' ' . $periodo['anio'],
            'periodo'     => $periodo,
            'stats'       => $this->model->getStatsCierre($id),
            'resumen'     => $this->model->getResumenBimestre($id),
            'reaperturas' => $this->model->getReaperturas($id),
        ]);
    }
}
