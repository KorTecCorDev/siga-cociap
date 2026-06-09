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
