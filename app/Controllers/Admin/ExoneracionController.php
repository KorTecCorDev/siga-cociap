<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ExoneracionModel;
use Core\Session;

class ExoneracionController extends BaseController
{
    private ExoneracionModel $exoModel;

    public function __construct()
    {
        $this->requireRole(['admin', 'registro_academico']);
        $this->exoModel = new ExoneracionModel();
    }

    /** GET /admin/exoneraciones — lista de secciones */
    public function index(): void
    {
        $anio = $this->getAnioActivo();
        if (!$anio) {
            $this->redirectWithError(url('dashboard'), 'No hay año académico activo.');
        }

        $secciones = $this->exoModel->query("
            SELECT
                s.id,
                s.nombre         AS seccion_nombre,
                g.numero         AS grado_numero,
                g.nombre_display AS grado_nombre,
                n.nombre         AS nivel_nombre,
                n.id             AS nivel_id,
                (SELECT COUNT(*)
                 FROM exoneraciones e
                 INNER JOIN matriculas m ON m.id = e.matricula_id
                 WHERE m.seccion_id = s.id
                   AND e.anio_id   = ?
                   AND e.revocado_en IS NULL
                ) AS total_exoneraciones
            FROM secciones s
            INNER JOIN grados  g ON g.id = s.grado_id
            INNER JOIN niveles n ON n.id = g.nivel_id
            WHERE s.anio_id = ?
            ORDER BY n.id, g.numero, s.nombre
        ", [(int) $anio['id'], (int) $anio['id']]);

        $this->view('admin/exoneraciones/index', [
            'titulo'    => 'Exoneraciones',
            'secciones' => $secciones,
            'anio'      => $anio,
        ]);
    }

    /** GET /admin/exoneraciones/{seccion_id} — detalle de sección */
    public function seccion(string $seccionId): void
    {
        $seccionId = (int) $seccionId;
        $anio      = $this->getAnioActivo();
        if (!$anio) {
            $this->redirectWithError(url('admin/exoneraciones'), 'No hay año académico activo.');
        }

        $seccion = $this->exoModel->queryOne("
            SELECT s.id, s.nombre AS seccion_nombre, s.anio_id,
                   g.nombre_display AS grado_nombre, g.numero AS grado_numero,
                   n.nombre AS nivel_nombre, n.id AS nivel_id
            FROM secciones s
            INNER JOIN grados  g ON g.id = s.grado_id
            INNER JOIN niveles n ON n.id = g.nivel_id
            WHERE s.id = ?
        ", [$seccionId]);

        if (!$seccion) {
            $this->redirectWithError(url('admin/exoneraciones'), 'Sección no encontrada.');
        }

        $exoneraciones = $this->exoModel->getParaSeccion($seccionId, (int) $anio['id']);
        $alumnos       = $this->exoModel->getAlumnosSeccion($seccionId, (int) $anio['id']);
        $opciones      = $this->exoModel->getOpcionesParaSeccion($seccionId, (int) $anio['id']);

        $this->view('admin/exoneraciones/seccion', [
            'titulo'        => 'Exoneraciones — ' . $seccion['grado_nombre'] . ' ' . $seccion['seccion_nombre'],
            'seccion'       => $seccion,
            'anio'          => $anio,
            'exoneraciones' => $exoneraciones,
            'alumnos'       => $alumnos,
            'opciones'      => $opciones,
        ]);
    }

    /** POST /admin/exoneraciones/{seccion_id}/registrar — nueva exoneración */
    public function registrar(string $seccionId): void
    {
        $this->validateCsrf();
        $seccionId = (int) $seccionId;

        $anio = $this->getAnioActivo();
        if (!$anio) {
            $this->json(['success' => false, 'mensaje' => 'No hay año académico activo.']);
            return;
        }

        $matriculaId = (int) ($_POST['matricula_id'] ?? 0);
        $areaSubarea = trim($_POST['area_subarea'] ?? '');
        $motivo      = trim($_POST['motivo'] ?? '');

        if (!$matriculaId || !$areaSubarea) {
            $this->redirectWithError(
                url("admin/exoneraciones/$seccionId"),
                'Selecciona alumno y área/subárea.'
            );
        }

        // Parsear "area_5" → area_id=5 o "sub_3" → subarea_id=3
        $areaId    = null;
        $subareaId = null;
        if (str_starts_with($areaSubarea, 'area_')) {
            $areaId = (int) substr($areaSubarea, 5);
        } elseif (str_starts_with($areaSubarea, 'sub_')) {
            $subareaId = (int) substr($areaSubarea, 4);
        } else {
            $this->redirectWithError(
                url("admin/exoneraciones/$seccionId"),
                'Opción de área inválida.'
            );
        }

        // Notas vivas: ya NO bloquea, pide CONFIRMACION EXPLICITA (05/08/2026).
        // Ver la nota extensa en registrarDesdeMatricula().
        if (!$this->confirmacionDeNotasVivas($matriculaId, (int) $anio['id'], $areaId, $subareaId)) {
            $this->redirectWithError(
                url("admin/exoneraciones/$seccionId"),
                'El alumno YA TIENE notas registradas este año en esa área. Si continúas, '
                . 'esas calificaciones dejarán de mostrarse en su boleta —incluidas las de '
                . 'bimestres ya cerrados y entregados— y en su lugar aparecerá EXO. '
                . 'Marca la casilla de confirmación para registrar la exoneración.'
            );
        }

        $ok = $this->exoModel->registrar(
            $matriculaId,
            (int) $anio['id'],
            $areaId,
            $subareaId,
            $motivo ?: 'Sin especificar',
            Session::user()['id']
        );

        if ($ok) {
            $this->redirectWithSuccess(
                url("admin/exoneraciones/$seccionId"),
                'Exoneración registrada correctamente.'
            );
        } else {
            $this->redirectWithError(
                url("admin/exoneraciones/$seccionId"),
                'Ya existe una exoneración activa para este alumno en esa área/subárea.'
            );
        }
    }

    /**
     * ¿Se puede seguir adelante con la exoneración, habiendo notas del año?
     *
     * HISTORIA (importa para no reintroducir el candado viejo). Hasta el
     * 05/08/2026 tener notas vivas BLOQUEABA la exoneración: había que pedir al
     * docente que las eliminara primero. Eso dejaba sin salida el caso real —una
     * estudiante con notas en un bimestre CERRADO y otro abierto— porque:
     *   - `tieneNotasVivas` mira TODO EL AÑO, así que limpiar el bimestre
     *     abierto no bastaba mientras el cerrado conservara una sola nota; y
     *   - las del bimestre cerrado NO se pueden borrar: `periodoEstaBloqueado`
     *     bloquea todo periodo cerrado, y reabrirlo lo dejaría sin poder
     *     re-cerrarse (guard de evaluación incompleta).
     *
     * Ahora el aviso se mantiene pero es FRANQUEABLE con una confirmación
     * explícita del formulario. La protección contra el clic accidental sigue
     * (sin marcar la casilla no pasa), y la decisión queda en manos de quien
     * registra, que es quien tiene la solicitud de la familia delante.
     *
     * Sin notas vivas no se pide nada: devuelve true directamente.
     */
    private function confirmacionDeNotasVivas(
        int  $matriculaId,
        int  $anioId,
        ?int $areaId,
        ?int $subareaId
    ): bool {
        if (!$this->exoModel->tieneNotasVivas($matriculaId, $anioId, $areaId, $subareaId)) {
            return true;
        }
        return !empty($_POST['confirmar_notas']);
    }

    /**
     * POST /matriculas/{id}/exonerar — registro desde el detalle de matrícula.
     * Mismo flujo (parseo + confirmación de notas vivas + registrar) que
     * registrar(), pero anclado a la matrícula: usa SU anio_id y vuelve a
     * /matriculas/{id}.
     */
    public function registrarDesdeMatricula(string $matriculaId): void
    {
        $this->validateCsrf();
        $matriculaId = (int) $matriculaId;
        $volver      = url('matriculas/' . $matriculaId);

        $mat = $this->exoModel->queryOne(
            "SELECT id, anio_id FROM matriculas WHERE id = ? LIMIT 1",
            [$matriculaId]
        );
        if (!$mat) {
            $this->redirectWithError(url('matriculas'), 'Matrícula no encontrada.');
        }

        $areaSubarea = trim($_POST['area_subarea'] ?? '');
        $motivo      = trim($_POST['motivo'] ?? '');

        // Parsear "area_5" → area_id=5 o "sub_3" → subarea_id=3
        $areaId    = null;
        $subareaId = null;
        if (str_starts_with($areaSubarea, 'area_')) {
            $areaId = (int) substr($areaSubarea, 5);
        } elseif (str_starts_with($areaSubarea, 'sub_')) {
            $subareaId = (int) substr($areaSubarea, 4);
        } else {
            $this->redirectWithError($volver, 'Selecciona el área o subárea a exonerar.');
        }

        // Notas vivas: ya NO bloquea, pide CONFIRMACION EXPLICITA (05/08/2026).
        if (!$this->confirmacionDeNotasVivas($matriculaId, (int) $mat['anio_id'], $areaId, $subareaId)) {
            $this->redirectWithError(
                $volver,
                'El alumno YA TIENE notas registradas este año en esa área. Si continúas, '
                . 'esas calificaciones dejarán de mostrarse en su boleta —incluidas las de '
                . 'bimestres ya cerrados y entregados— y en su lugar aparecerá EXO. '
                . 'Marca la casilla de confirmación para registrar la exoneración.'
            );
        }

        $ok = $this->exoModel->registrar(
            $matriculaId,
            (int) $mat['anio_id'],
            $areaId,
            $subareaId,
            $motivo ?: 'Sin especificar',
            Session::user()['id']
        );

        if ($ok) {
            $this->redirectWithSuccess($volver, 'Exoneración registrada correctamente.');
        } else {
            $this->redirectWithError($volver, 'Ya existe una exoneración activa para este alumno en esa área/subárea.');
        }
    }

    /** POST /admin/exoneraciones/{id}/revocar — revoca una exoneración */
    public function revocar(string $id): void
    {
        $this->validateCsrf();
        $exoId = (int) $id;

        $exo = $this->exoModel->queryOne("
            SELECT e.id, m.seccion_id
            FROM exoneraciones e
            INNER JOIN matriculas m ON m.id = e.matricula_id
            WHERE e.id = ?
              AND e.revocado_en IS NULL
        ", [$exoId]);

        if (!$exo) {
            $this->redirectWithError(url('admin/exoneraciones'), 'Exoneración no encontrada.');
        }

        $this->exoModel->revocar($exoId, Session::user()['id']);
        $this->redirectWithSuccess(
            url("admin/exoneraciones/{$exo['seccion_id']}"),
            'Exoneración revocada.'
        );
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function getAnioActivo(): ?array
    {
        return $this->exoModel->queryOne("
            SELECT id, anio FROM anios_academicos WHERE estado = 'activo' LIMIT 1
        ", []);
    }
}
