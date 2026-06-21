<?php

namespace App\Controllers\Docente;

use App\Controllers\BaseController;
use App\Models\CalificacionModel;
use App\Models\OrdenMeritoModel;

/**
 * OrdenMeritoController (docente) — consulta PÚBLICA de solo lectura para todo
 * el claustro. Reutiliza OrdenMeritoModel (fuente única del ranking + cascada).
 * DOS flujos separados para no confundirlos:
 *   - Orden de mérito  (por GRADO)   → define la media beca (1.er puesto del grado).
 *   - Ranking por sección (por SECCIÓN) → ranking interno; NO otorga media beca.
 * El docente NO gestiona desempates ni oficializa.
 */
class OrdenMeritoController extends BaseController
{
    private CalificacionModel $calModel;
    private OrdenMeritoModel  $ordenModel;

    public function __construct()
    {
        $this->requireRole(['docente', 'admin']);
        $this->calModel   = new CalificacionModel();
        $this->ordenModel = new OrdenMeritoModel();
    }

    /** GET /docente/orden-merito — selector de periodos (orden de mérito por grado). */
    public function index(): void
    {
        $this->verSelector('docente/orden-merito', 'Orden de mérito');
    }

    /** GET /docente/ranking-seccion — selector de periodos (ranking por sección). */
    public function seccionIndex(): void
    {
        $this->verSelector('docente/ranking-seccion', 'Ranking por sección');
    }

    /** GET /docente/orden-merito/{periodo_id} — ranking por GRADO (media beca). */
    public function porPeriodo(string $periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->cargarPeriodo($periodoId, 'docente/orden-merito');

        $ranking = [];
        foreach ($this->ordenModel->gradosConRanking($periodoId) as $grado) {
            $gid = (int) $grado['id'];
            $ranking[$gid] = [
                'grado'       => $grado,
                'estudiantes' => $this->ordenModel->rankingGrado($gid, $periodoId),
            ];
        }

        $this->view('docente/orden-merito-periodo', [
            'titulo'  => 'Orden de mérito — ' . $periodo['nombre_display'],
            'periodo' => $periodo,
            'ranking' => $ranking,
        ]);
    }

    /** GET /docente/ranking-seccion/{periodo_id} — ranking por SECCIÓN (sin media beca). */
    public function seccionPorPeriodo(string $periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->cargarPeriodo($periodoId, 'docente/ranking-seccion');

        $ranking = [];
        foreach ($this->ordenModel->gradosConRanking($periodoId) as $grado) {
            $gid = (int) $grado['id'];
            $ranking[$gid] = [
                'grado'     => $grado,
                'secciones' => $this->ordenModel->rankingPorSeccion($gid, $periodoId),
            ];
        }

        $this->view('docente/ranking-seccion-periodo', [
            'titulo'  => 'Ranking por sección — ' . $periodo['nombre_display'],
            'periodo' => $periodo,
            'ranking' => $ranking,
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** Selector de periodos parametrizado (mismo componente para ambos flujos). */
    private function verSelector(string $rutaBase, string $titulo): void
    {
        // Solo bimestres CERRADOS: el orden de merito se PUBLICA al cerrar el
        // bimestre (Hito B). El director lo ve en vivo desde su propio modulo;
        // para el claustro es un documento publicado, no un calculo provisional.
        $periodos = $this->calModel->query("
            SELECT p.*, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.estado = 'cerrado'
            ORDER BY a.anio DESC, p.numero ASC
        ");

        $this->view('docente/orden-merito', [
            'titulo'   => $titulo,
            'rutaBase' => $rutaBase,
            'periodos' => $periodos,
        ]);
    }

    private function cargarPeriodo(int $periodoId, string $rutaBase): array
    {
        $periodo = $this->calModel->queryOne("
            SELECT p.*, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
        ", [$periodoId]);

        if (!$periodo) {
            $this->redirectWithError(url($rutaBase), 'Periodo no encontrado.');
        }
        // Gate: el ranking del claustro solo se ve si el bimestre esta cerrado
        // (publicado). Antes del cierre no hay ranking oficial que mostrar.
        if ($periodo['estado'] !== 'cerrado') {
            $this->redirectWithError(
                url($rutaBase),
                'El orden de mérito de este bimestre aún no está publicado (se publica al cerrar el bimestre).'
            );
        }

        return $periodo;
    }
}
