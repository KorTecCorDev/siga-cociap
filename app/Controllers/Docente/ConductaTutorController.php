<?php

namespace App\Controllers\Docente;

use App\Controllers\BaseController;
use App\Models\ConductaModel;
use App\Models\TransversalModel;
use Core\Session;

/**
 * Conducta — ETAPA 2 (Tutor de seccion).
 * Solo procede cuando Registro Academico ya bloqueo la conducta de la seccion
 * (cierre vigente con ra_bloqueado). El tutor revisa la nota de RA, agrega su nota
 * 00-20 opcional (final = promedio, .5 a favor) y cierra/aprueba la seccion.
 */
class ConductaTutorController extends BaseController
{
    private ConductaModel    $model;
    private TransversalModel $transModel; // reutiliza getSeccionDelTutor

    public function __construct()
    {
        $this->requireRole(['docente', 'admin']);
        $this->model      = new ConductaModel();
        $this->transModel = new TransversalModel();
    }

    /** Sección de la que el usuario es tutor, o aborta con redirect/JSON. */
    private function seccionTutor(): ?array
    {
        return $this->transModel->getSeccionDelTutor((int) Session::user()['id']);
    }

    // GET /docente/conducta[/{periodo_id}]
    public function index(string $periodoId = ''): void
    {
        $seccion = $this->seccionTutor();
        if (!$seccion) {
            $this->redirectWithError(url('docente/mis-cargas'), 'No eres tutor(a) de ninguna sección este año.');
        }

        $periodos = $this->model->query("
            SELECT p.id, p.numero, p.nombre_display, p.estado
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id AND a.estado = 'activo'
            WHERE p.estado IN ('cerrado', 'activo')
            ORDER BY p.numero
        ");

        $periodoSel = null;
        if ($periodoId !== '') {
            foreach ($periodos as $p) {
                if ((int) $p['id'] === (int) $periodoId) { $periodoSel = $p; break; }
            }
        } else {
            foreach ($periodos as $p) {
                if ($p['estado'] === 'activo') { $periodoSel = $p; break; }
            }
            $periodoSel = $periodoSel ?? ($periodos[0] ?? null);
        }
        if (!$periodoSel) {
            $this->redirectWithError(url('docente/mis-cargas'), 'Bimestre no encontrado.');
        }

        $pid     = (int) $periodoSel['id'];
        $sid     = (int) $seccion['id'];
        $total   = $this->model->totalCriterios((int) $seccion['nivel_id']);
        $cierre  = $this->model->getCierreVigente($sid, $pid);

        // Solo hay datos que mostrar cuando RA ya bloqueo (cierre vigente).
        $estudiantes = $cierre ? $this->model->getEstudiantesParaTutor($sid, $pid, $total) : [];

        $cerradoTutor = $cierre && !empty($cierre['tutor_cerrado_en']);
        $editable     = $cierre && !$cerradoTutor && $this->model->periodoEditable($pid);

        $this->view('docente/conducta', [
            'titulo'       => 'Conducta — Sección ' . $seccion['nombre'],
            'seccion'      => $seccion,
            'periodos'     => $periodos,
            'periodoSel'   => $periodoSel,
            'cierre'       => $cierre,
            'estudiantes'  => $estudiantes,
            'editable'     => $editable,
            'cerradoTutor' => $cerradoTutor,
            'page_scripts' => ['conducta-tutor'],
        ]);
    }

    // POST /docente/conducta/{periodo_id}/nota  (AJAX)
    public function guardarNota(string $periodoId): void
    {
        $this->validateCsrf();

        $seccion = $this->seccionTutor();
        if (!$seccion) {
            $this->json(['success' => false, 'mensaje' => 'No eres tutor(a) de una sección.'], 403);
        }

        $pid         = (int) $periodoId;
        $sid         = (int) $seccion['id'];
        $matriculaId = (int) $this->input('matricula_id');
        $notaRaw     = trim((string) $this->input('nota', ''));

        if (!$matriculaId) {
            $this->json(['success' => false, 'mensaje' => 'Datos incompletos.'], 400);
        }

        // El alumno debe pertenecer a la sección del tutor.
        $pertenece = $this->model->queryOne(
            "SELECT id FROM matriculas WHERE id = ? AND seccion_id = ?",
            [$matriculaId, $sid]
        );
        if (!$pertenece) {
            $this->json(['success' => false, 'mensaje' => 'El alumno no pertenece a tu sección.'], 403);
        }

        $cierre = $this->model->getCierreVigente($sid, $pid);
        if (!$cierre) {
            $this->json(['success' => false, 'mensaje' =>
                'Todavía los auxiliares académicos no han registrado sus calificaciones de conducta. ' .
                'Consulte con Registro Académico para más información.'], 403);
        }
        if (!empty($cierre['tutor_cerrado_en'])) {
            $this->json(['success' => false, 'mensaje' => 'La conducta de esta sección ya fue cerrada.'], 403);
        }
        if (!$this->model->periodoEditable($pid)) {
            $this->json(['success' => false, 'mensaje' => 'El periodo no está disponible para edición.'], 403);
        }

        // Nota vacia = limpiar; si viene, debe ser entero 0-20.
        $nota = null;
        if ($notaRaw !== '') {
            if (!ctype_digit($notaRaw) || (int) $notaRaw < 0 || (int) $notaRaw > 20) {
                $this->json(['success' => false, 'mensaje' => 'La nota debe ser un entero entre 00 y 20.'], 400);
            }
            $nota = (int) $notaRaw;
        }

        $ok = $this->model->guardarNotaTutor($matriculaId, $pid, $nota, (int) Session::user()['id']);
        $this->json([
            'success' => $ok,
            'mensaje' => $ok ? 'Guardado.' : 'Error al guardar.',
        ], $ok ? 200 : 500);
    }

    // POST /docente/conducta/{periodo_id}/cerrar
    public function cerrar(string $periodoId): void
    {
        $this->validateCsrf();

        $seccion = $this->seccionTutor();
        if (!$seccion) {
            $this->json(['success' => false, 'mensaje' => 'No eres tutor(a) de una sección.'], 403);
        }

        $pid = (int) $periodoId;
        $sid = (int) $seccion['id'];

        if (!$this->model->periodoEditable($pid)) {
            $this->json(['success' => false, 'mensaje' => 'El periodo no está disponible para edición.'], 403);
        }

        $res = $this->model->cerrarTutor($sid, $pid, (int) Session::user()['id']);
        $this->json(['success' => $res['ok'], 'mensaje' => $res['mensaje']], $res['ok'] ? 200 : 400);
    }
}
