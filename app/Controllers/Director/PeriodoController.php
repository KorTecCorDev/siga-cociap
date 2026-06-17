<?php

namespace App\Controllers\Director;

use App\Controllers\BaseController;
use App\Models\AnioAcademicoModel;
use App\Models\OrdenMeritoModel;
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
        // Orden cronológico: no se puede abrir un bimestre si uno anterior sigue
        // sin abrir. Evita saltarse bimestres (lo que distorsiona el "vigente").
        if ($this->model->hayBimestrePrevioPendiente((int) $periodo['anio_id'], (int) $periodo['numero'])) {
            $this->redirectWithError($volverUrl, 'Debes abrir los bimestres en orden: hay un bimestre anterior que aún no se ha abierto.');
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

        // Todos los empates del orden de mérito deben estar resueltos antes de
        // cerrar: el snapshot oficial (Fase 2) congela un ranking definitivo, así
        // que un empate pendiente al cierre quedaría petrificado sin resolver.
        $empates = (new OrdenMeritoModel())->gradosConEmpatesPendientes($id);
        if (!empty($empates)) {
            $this->redirectWithError(
                $volverUrl,
                'No se puede cerrar: hay empates sin resolver en el orden de mérito ('
                . implode('; ', array_unique($empates))
                . '). Resuélvelos antes de cerrar el bimestre.'
            );
        }

        $usuarioId = (int) (Session::user()['id'] ?? 0);

        try {
            $this->model->beginTransaction();
            // Bloquea competencias propias + transversales (TIC/GAMA) de cada carga.
            $this->model->bloquearCompetenciasPendientes($id, $usuarioId);
            // Cierra las transversales por seccion para que agreguen en boleta
            // (respeta los cierres que el tutor ya hizo).
            $this->model->crearCierresTransversalesPendientes($id, $usuarioId);
            $this->model->setEstadoPeriodo($id, 'cerrado');
            // Congela el orden de mérito oficial del bimestre (documento inmutable).
            // Mismo PDO singleton → entra en esta misma transacción.
            (new OrdenMeritoModel())->generarSnapshot($id, $usuarioId);
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
            // Reabrir NO libera bloqueos automáticamente: las competencias
            // finalizadas-vacías (origen='docente', aprobadas sin notas) deben
            // permanecer bloqueadas. Para liberar los bloqueos del cierre forzado
            // (origen='cierre') el director usa el botón manual del panel de
            // bloqueos. Solo se deja traza del motivo de la reapertura.
            $this->model->registrarReapertura($id, $motivo, $usuarioId, 0);
            $this->model->commit();
        } catch (\Exception $e) {
            $this->model->rollback();
            log_error('Error reabriendo bimestre', ['id' => $id, 'error' => $e->getMessage()]);
            $this->redirectWithError($volverUrl, 'No se pudo reabrir el bimestre. Intenta de nuevo.');
        }

        $this->redirectWithSuccess(
            $volverUrl,
            "{$periodo['nombre_display']} reabierto. Los bloqueos se conservan; "
            . 'usa el panel de bloqueos para liberar los del cierre forzado si hace falta.'
        );
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
