<?php

namespace App\Controllers\Director;

use App\Controllers\BaseController;
use App\Models\CargaAcademicaModel;
use App\Models\ReemplazoDocenteModel;
use Core\Session;

/**
 * ReemplazoDocenteController
 * Proceso oficial para cambiar el docente de una carga ACTIVA con auditoría por
 * snapshot. General (cualquier carga). El entrante hereda y continúa en vivo; el
 * trabajo del saliente queda congelado en un archivo lateral de solo lectura.
 */
class ReemplazoDocenteController extends BaseController
{
    private const ROLES = ['admin', 'director_general'];

    private CargaAcademicaModel    $cargas;
    private ReemplazoDocenteModel  $reemplazos;

    public function __construct()
    {
        $this->requireRole(self::ROLES);
        $this->cargas     = new CargaAcademicaModel();
        $this->reemplazos = new ReemplazoDocenteModel();
    }

    /** GET /director/cargas/{id}/reemplazar — formulario de reemplazo. */
    public function form(string $id): void
    {
        $id    = (int) $id;
        $carga = $this->cargas->findById($id);
        if (!$carga) {
            $this->redirectWithError(url('director/cargas'), 'Carga no encontrada.');
        }

        // Docentes disponibles como entrantes (todos menos el actual de la carga).
        $docentes = array_values(array_filter(
            $this->cargas->listarDocentes(),
            fn($d) => (int) $d['id'] !== (int) $carga['docente_id']
        ));

        $this->view('director/reemplazos/form', [
            'titulo'   => 'Reemplazar docente — ' . ($carga['area_nombre'] ?? ''),
            'carga'    => $carga,
            'docentes' => $docentes,
        ]);
    }

    /** POST /director/cargas/{id}/reemplazar — ejecuta el reemplazo. */
    public function reemplazar(string $id): void
    {
        $this->validateCsrf();
        $id = (int) $id;

        $carga = $this->cargas->findById($id);
        if (!$carga) {
            $this->redirectWithError(url('director/cargas'), 'Carga no encontrada.');
        }

        $entranteId = (int) $this->input('docente_entrante_id', 0);
        $motivo     = trim((string) $this->input('motivo', ''));

        if ($entranteId <= 0) {
            $this->redirectWithError(
                url("director/cargas/{$id}/reemplazar"),
                'Debes seleccionar el docente entrante.'
            );
        }
        if ($motivo === '') {
            $this->redirectWithError(
                url("director/cargas/{$id}/reemplazar"),
                'El motivo del reemplazo es obligatorio.'
            );
        }

        // El entrante hereda el MISMO horario de la carga: debe estar libre (sin
        // SOLAPES) en esos rangos. Solo se chequea al DOCENTE entrante (la sección
        // conserva sus mismos horarios; solo cambia el docente). Se excluye la
        // propia carga.
        $sesionesChk = array_map(fn($s) => [
            'dia'         => $s['dia_semana'],
            'hora_inicio' => $s['hora_inicio'],
            'hora_fin'    => $s['hora_fin'],
            'seccion_id'  => (int) $carga['seccion_id'],
            'docente_id'  => $entranteId,
            'config_id'   => (int) $s['config_id'],
        ], $this->cargas->getSesionesDeCarga($id));

        $conflictos = $this->cargas->verificarSolapes($sesionesChk, $id, ['docente']);
        if ($conflictos) {
            $this->redirectWithError(
                url("director/cargas/{$id}/reemplazar"),
                'El docente entrante ya tiene clases en el horario de esta carga. '
                . 'Elige otro docente o ajusta primero el horario.'
            );
        }

        try {
            $reemplazoId = $this->reemplazos->reemplazar(
                $id, $entranteId, $motivo, Session::user()['id']
            );
        } catch (\RuntimeException $e) {
            $this->redirectWithError(url("director/cargas/{$id}/reemplazar"), $e->getMessage());
        } catch (\Exception $e) {
            log_error('Error en reemplazo de docente', ['carga_id' => $id, 'msg' => $e->getMessage()]);
            $this->redirectWithError(
                url("director/cargas/{$id}/reemplazar"),
                'No se pudo completar el reemplazo. Intenta nuevamente.'
            );
        }

        $this->redirectWithSuccess(
            url("director/reemplazos/{$reemplazoId}/snapshot"),
            'Docente reemplazado. El trabajo del saliente quedó archivado para auditoría.'
        );
    }

    /** GET /director/cargas/{id}/reemplazos — historial de reemplazos de la carga. */
    public function historial(string $id): void
    {
        $id    = (int) $id;
        $carga = $this->cargas->findById($id);
        if (!$carga) {
            $this->redirectWithError(url('director/cargas'), 'Carga no encontrada.');
        }

        $this->view('director/reemplazos/historial', [
            'titulo'     => 'Reemplazos — ' . ($carga['area_nombre'] ?? ''),
            'carga'      => $carga,
            'reemplazos' => $this->reemplazos->getHistorialPorCarga($id),
        ]);
    }

    /** GET /director/reemplazos/{id}/snapshot — reporte de auditoría (solo lectura). */
    public function verSnapshot(string $id): void
    {
        $id      = (int) $id;
        $evento  = $this->reemplazos->getSnapshot($id);
        if (!$evento) {
            $this->redirectWithError(url('director/cargas'), 'Reemplazo no encontrado.');
        }

        $carga = $this->cargas->findById((int) $evento['carga_id']);
        $snap  = $evento['snapshot'] ?? [];

        // Diccionarios para mostrar nombres en vez de IDs (resueltos en vivo:
        // competencias y nombres de alumnos son estables).
        $compIds = $matIds = [];
        foreach (($snap['criterios'] ?? []) as $cr) {
            $compIds[(int) $cr['competencia_id']] = true;
            foreach ($cr['notas'] ?? [] as $n) {
                $matIds[(int) $n['matricula_id']] = true;
            }
        }
        foreach (($snap['calificaciones'] ?? []) as $c) {
            $compIds[(int) $c['competencia_id']] = true;
            $matIds[(int) $c['matricula_id']]    = true;
        }
        foreach (($snap['bloqueos'] ?? []) as $b) {
            $compIds[(int) $b['competencia_id']] = true;
        }

        $this->view('director/reemplazos/snapshot', [
            'titulo'         => 'Auditoría de reemplazo',
            'evento'         => $evento,
            'carga'          => $carga,
            'snapshot'       => $snap,
            'compNombres'    => $this->nombresCompetencias(array_keys($compIds)),
            'alumnoNombres'  => $this->nombresAlumnos(array_keys($matIds)),
            'periodoNombres' => $this->nombresPeriodos(),
        ]);
    }

    /** [periodo_id => 'nombre_display'] de todos los bimestres del año. */
    private function nombresPeriodos(): array
    {
        $filas = $this->cargas->query(
            "SELECT id, nombre_display FROM periodos ORDER BY numero"
        );
        $out = [];
        foreach ($filas as $f) {
            $out[(int) $f['id']] = $f['nombre_display'];
        }
        return $out;
    }

    // ── Diccionarios para el reporte ──────────────────────────────

    /** [competencia_id => 'CODIGO — nombre'] */
    private function nombresCompetencias(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $ph   = implode(',', array_fill(0, count($ids), '?'));
        $filas = $this->cargas->query("
            SELECT id, codigo_minedu, nombre_completo
            FROM competencias
            WHERE id IN ($ph)
        ", array_map('intval', $ids));

        $out = [];
        foreach ($filas as $f) {
            $cod = $f['codigo_minedu'] ? $f['codigo_minedu'] . ' — ' : '';
            $out[(int) $f['id']] = $cod . $f['nombre_completo'];
        }
        return $out;
    }

    /** [matricula_id => 'APELLIDOS, Nombres'] */
    private function nombresAlumnos(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $ph   = implode(',', array_fill(0, count($ids), '?'));
        $filas = $this->cargas->query("
            SELECT m.id AS matricula_id,
                   CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres) AS nombre
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            WHERE m.id IN ($ph)
        ", array_map('intval', $ids));

        $out = [];
        foreach ($filas as $f) {
            $out[(int) $f['matricula_id']] = $f['nombre'];
        }
        return $out;
    }
}
