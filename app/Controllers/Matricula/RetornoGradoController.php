<?php

namespace App\Controllers\Matricula;

use App\Controllers\BaseController;
use App\Models\MatriculaModel;
use Core\Session;

/**
 * RetornoGradoController
 * Caso especial: un estudiante asiste a un grado INFERIOR al oficial de SIAGIE.
 * Se crea una "matrícula operativa" en el grado destino y se vincula con la
 * "matrícula oficial" en retornos_grado. El estudiante compite académicamente
 * con su grado OPERATIVO (ver integración en OrdenMeritoController).
 */
class RetornoGradoController extends BaseController
{
    private MatriculaModel $model;

    public function __construct()
    {
        $this->requireRole(['admin', 'registro_academico', 'director_ebr']);
        $this->model = new MatriculaModel();
    }

    // ── GET /matriculas/{id}/retorno ─────────────────────────────
    public function create(string $matriculaId): void
    {
        $matricula = $this->requireMatricula((int) $matriculaId);

        // ¿Ya tiene un retorno activo?
        $existente = $this->model->queryOne(
            "SELECT id FROM retornos_grado WHERE matricula_oficial_id = ? AND estado = 'activo' LIMIT 1",
            [(int) $matriculaId]
        );
        if ($existente) {
            $this->redirectWithError(url('matriculas/' . $matriculaId),
                'Esta matrícula ya tiene un retorno de grado activo.');
        }

        // Secciones de grados INFERIORES al actual, del mismo año y nivel.
        $secciones = $this->model->query("
            SELECT s.id, s.nombre, g.numero AS grado_numero, g.nombre_display AS grado_nombre
            FROM secciones s
            INNER JOIN grados g ON g.id = s.grado_id
            WHERE s.anio_id = ?
              AND g.nivel_id = ?
              AND g.numero < ?
            ORDER BY g.numero, s.nombre
        ", [(int) $matricula['anio_id'], (int) $matricula['nivel_id'], (int) $matricula['grado_numero']]);

        $this->view('matriculas/retorno', [
            'titulo'    => 'Retorno de grado',
            'matricula' => $matricula,
            'secciones' => $secciones,
        ]);
    }

    // ── POST /matriculas/{id}/retorno ────────────────────────────
    public function store(string $matriculaId): void
    {
        $this->validateCsrf();
        $oficial   = $this->requireMatricula((int) $matriculaId);
        $usuarioId = (int) (Session::user()['id'] ?? 0);

        $seccionDestino = (int) $this->input('seccion_destino_id');
        $motivo         = trim((string) $this->input('motivo'));

        if (!$seccionDestino || $motivo === '') {
            $this->redirectWithError(url('matriculas/' . $matriculaId . '/retorno'),
                'Selecciona la sección destino y describe el motivo.');
        }

        // Validar que la sección destino exista, sea del mismo año y de grado inferior.
        $destino = $this->model->queryOne("
            SELECT s.id, g.numero AS grado_numero
            FROM secciones s
            INNER JOIN grados g ON g.id = s.grado_id
            WHERE s.id = ? AND s.anio_id = ? AND g.nivel_id = ?
            LIMIT 1
        ", [$seccionDestino, (int) $oficial['anio_id'], (int) $oficial['nivel_id']]);

        if (!$destino || (int) $destino['grado_numero'] >= (int) $oficial['grado_numero']) {
            $this->redirectWithError(url('matriculas/' . $matriculaId . '/retorno'),
                'La sección destino debe ser de un grado inferior al oficial.');
        }

        // Evitar duplicado de retorno activo.
        $yaActivo = $this->model->queryOne(
            "SELECT id FROM retornos_grado WHERE matricula_oficial_id = ? AND estado = 'activo' LIMIT 1",
            [(int) $matriculaId]
        );
        if ($yaActivo) {
            $this->redirectWithError(url('matriculas/' . $matriculaId),
                'Esta matrícula ya tiene un retorno de grado activo.');
        }

        $this->model->beginTransaction();
        try {
            // 1) Matrícula operativa en el grado inferior (estado 'aprobada' para
            //    que el estudiante figure en calificaciones y ranking operativo).
            $operativaId = $this->model->create([
                'estudiante_id'  => (int) $oficial['estudiante_id'],
                'seccion_id'     => $seccionDestino,
                'anio_id'        => (int) $oficial['anio_id'],
                'tipo'           => 'continuador',
                'serie_recibo'   => $oficial['serie_recibo'] ?? null,
                'estado'         => 'aprobada',
                'fecha_registro' => date('Y-m-d'),
                'registrado_por' => $usuarioId,
            ]);

            // 2) Vínculo en retornos_grado.
            $this->model->execute("
                INSERT INTO retornos_grado
                    (matricula_oficial_id, matricula_operativa_id, motivo,
                     autorizado_por, fecha_retorno, estado)
                VALUES (?, ?, ?, ?, CURDATE(), 'activo')
            ", [(int) $matriculaId, $operativaId, $motivo, $usuarioId]);

            // 3) Transferir notas existentes respetando competencias: se copian
            //    las calificaciones de la matrícula oficial a la operativa
            //    (preservando competencia y periodo). Best-effort: no pisa notas
            //    ya existentes en la operativa.
            $this->model->execute("
                INSERT IGNORE INTO calificaciones
                    (matricula_id, carga_id, periodo_id, competencia_id,
                     nota_numerica, conclusion_descriptiva, registrado_por)
                SELECT ?, cal.carga_id, cal.periodo_id, cal.competencia_id,
                       cal.nota_numerica, cal.conclusion_descriptiva, ?
                FROM calificaciones cal
                WHERE cal.matricula_id = ?
            ", [$operativaId, $usuarioId, (int) $matriculaId]);

            $this->model->commit();
        } catch (\Exception $e) {
            $this->model->rollback();
            log_error('Error en retorno de grado', ['id' => $matriculaId, 'error' => $e->getMessage()]);
            $this->redirectWithError(url('matriculas/' . $matriculaId . '/retorno'),
                'No se pudo registrar el retorno de grado.');
        }

        $this->redirectWithSuccess(url('matriculas/' . $matriculaId),
            'Retorno de grado registrado. El estudiante competirá en su grado operativo.');
    }

    // ── GET /matriculas/{id}/retorno/revertir ────────────────────
    public function confirmarReversion(string $matriculaId): void
    {
        $oficial = $this->requireMatricula((int) $matriculaId);
        $retorno = $this->getRetornoActivo((int) $matriculaId);

        if (!$retorno) {
            $this->redirectWithError(url('matriculas/' . $matriculaId),
                'Esta matrícula no tiene un retorno de grado activo que revertir.');
        }

        // Bimestres en los que la matrícula operativa tiene notas: son los que se
        // consolidarán en la boleta oficial. Deben estar cerrados para revertir.
        $periodos = $this->periodosConNotas((int) $retorno['matricula_operativa_id']);

        $this->view('matriculas/retorno-revertir', [
            'titulo'    => 'Revertir retorno de grado',
            'matricula' => $oficial,
            'retorno'   => $retorno,
            'periodos'  => $periodos,
        ]);
    }

    // ── POST /matriculas/{id}/retorno/revertir ────────────────────
    public function revertir(string $matriculaId): void
    {
        $this->validateCsrf();
        $oficial   = $this->requireMatricula((int) $matriculaId);
        $usuarioId = (int) (Session::user()['id'] ?? 0);

        $motivo = trim((string) $this->input('motivo'));
        if ($motivo === '') {
            $this->redirectWithError(url('matriculas/' . $matriculaId . '/retorno/revertir'),
                'Describe el motivo de la reversión.');
        }

        $retorno = $this->getRetornoActivo((int) $matriculaId);
        if (!$retorno) {
            $this->redirectWithError(url('matriculas/' . $matriculaId),
                'Esta matrícula no tiene un retorno de grado activo que revertir.');
        }

        // Regla: solo se revierte con el/los bimestre(s) cursado(s) en el grado
        // operativo CERRADOS, para congelar esas notas antes de consolidar.
        $abiertos = $this->periodosConNotas((int) $retorno['matricula_operativa_id'], false);
        if ($abiertos) {
            $nombres = implode(', ', array_column($abiertos, 'nombre_display'));
            $this->redirectWithError(url('matriculas/' . $matriculaId . '/retorno/revertir'),
                'No se puede revertir: el/los bimestre(s) con notas en el grado operativo deben estar cerrados (' . $nombres . ').');
        }

        $this->model->beginTransaction();
        try {
            // 1) Marcar el retorno como revertido (auditoría).
            $this->model->execute("
                UPDATE retornos_grado
                SET estado           = 'revertido',
                    fecha_reversion  = CURDATE(),
                    motivo_reversion = ?,
                    revertido_por    = ?
                WHERE id = ?
            ", [$motivo, $usuarioId, (int) $retorno['id']]);

            // 2) Desactivar la matrícula operativa (queda solo para auditoría).
            //    La boleta de la oficial seguirá leyendo sus notas por unión.
            $this->model->cambiarEstado(
                (int) $retorno['matricula_operativa_id'],
                'desactivado',
                $usuarioId,
                'Retorno de grado revertido — ' . $motivo
            );

            $this->model->commit();
        } catch (\Exception $e) {
            $this->model->rollback();
            log_error('Error al revertir retorno de grado', ['id' => $matriculaId, 'error' => $e->getMessage()]);
            $this->redirectWithError(url('matriculas/' . $matriculaId . '/retorno/revertir'),
                'No se pudo revertir el retorno de grado.');
        }

        $this->redirectWithSuccess(url('matriculas/' . $matriculaId),
            'Retorno de grado revertido. El estudiante vuelve a calificarse en su grado oficial.');
    }

    /** Retorno ACTIVO cuya matrícula oficial es la indicada, o null. */
    private function getRetornoActivo(int $oficialId): ?array
    {
        return $this->model->queryOne("
            SELECT r.*, g.nombre_display AS grado_destino, s.nombre AS seccion_destino
            FROM retornos_grado r
            INNER JOIN matriculas mo ON mo.id = r.matricula_operativa_id
            LEFT  JOIN secciones s   ON s.id = mo.seccion_id
            LEFT  JOIN grados g      ON g.id = s.grado_id
            WHERE r.matricula_oficial_id = ? AND r.estado = 'activo'
            LIMIT 1
        ", [$oficialId]);
    }

    /**
     * Periodos en los que una matrícula tiene calificaciones registradas.
     * Con $cerrados=true devuelve solo los cerrados (los que se consolidan);
     * con $cerrados=false devuelve solo los NO cerrados (bloquean la reversión).
     */
    private function periodosConNotas(int $matriculaId, bool $cerrados = true): array
    {
        $condEstado = $cerrados ? "p.estado = 'cerrado'" : "p.estado <> 'cerrado'";

        return $this->model->query("
            SELECT DISTINCT p.id, p.numero, p.nombre_display, p.estado
            FROM calificaciones cal
            INNER JOIN periodos p ON p.id = cal.periodo_id
            WHERE cal.matricula_id = ?
              AND {$condEstado}
            ORDER BY p.numero
        ", [$matriculaId]);
    }

    /** Carga la matrícula o muestra 404. */
    private function requireMatricula(int $id): array
    {
        $matricula = $this->model->findById($id);
        if (!$matricula) {
            http_response_code(404);
            $this->view('shared/404');
            exit;
        }
        return $matricula;
    }
}
