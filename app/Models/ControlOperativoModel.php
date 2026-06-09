<?php

namespace App\Models;

/**
 * ControlOperativoModel
 * Detección (solo lectura) de inconsistencias operativas para el Centro de Control.
 *
 * Cada método devuelve una lista de ítems para mostrar y enlazar al módulo donde se
 * corrige. NO modifica datos: el panel solo detecta y enlaza.
 */
class ControlOperativoModel extends BaseModel
{
    protected string $table = 'periodos';

    private CalificacionModel    $calModel;
    private SeccionModel         $seccionModel;
    private DesempateMeritoModel $desempateModel;

    public function __construct()
    {
        parent::__construct();
        $this->calModel       = new CalificacionModel();
        $this->seccionModel   = new SeccionModel();
        $this->desempateModel = new DesempateMeritoModel();
    }

    // ── Selector de periodo / año ────────────────────────────────

    /** Periodos visibles (activos o cerrados), patrón de OrdenMeritoController::index. */
    public function getPeriodos(): array
    {
        return $this->query("
            SELECT p.id, p.nombre_display, p.numero, p.estado, p.anio_id, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.estado IN ('activo', 'cerrado')
            ORDER BY a.anio DESC, p.numero ASC
        ");
    }

    /** Periodo por defecto: el activo; si no hay, el más reciente visible. */
    public function getPeriodoPorDefecto(): ?array
    {
        $activo = $this->queryOne("
            SELECT p.id, p.nombre_display, p.numero, p.estado, p.anio_id, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.estado = 'activo'
            ORDER BY a.anio DESC, p.numero ASC
            LIMIT 1
        ");
        if ($activo) return $activo;
        $periodos = $this->getPeriodos();
        return $periodos[0] ?? null;
    }

    public function getPeriodo(int $periodoId): ?array
    {
        return $this->queryOne("
            SELECT p.id, p.nombre_display, p.numero, p.estado, p.anio_id, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
        ", [$periodoId]);
    }

    // ── Chequeo 1: empates de orden de mérito sin resolver ───────

    /**
     * Grados del periodo con ≥1 grupo de empate irreducible aún sin resolver.
     * Replica la regla de detección de OrdenMeritoController::aplicarDesempate
     * (igual promedio exacto + N desigual, o igual distribución literal), y excluye
     * los grupos ya resueltos consultando DesempateMeritoModel::getResolucion.
     */
    public function empatesPendientes(int $periodoId): array
    {
        $filas = $this->query("
            SELECT
                m.id AS matricula_id,
                g.id AS grado_id,
                g.numero AS grado_numero,
                g.nombre_display AS grado_nombre,
                n.nombre AS nivel_nombre,
                n.id AS nivel_id,
                COUNT(cal.nota_numerica)                  AS n_comp,
                AVG(cal.nota_numerica)                    AS promedio_exacto,
                SUM(cal.nota_numerica <= 10)              AS num_c,
                SUM(cal.nota_numerica BETWEEN 11 AND 13)  AS num_b,
                SUM(cal.nota_numerica >= 17)              AS num_ad
            FROM matriculas m
            INNER JOIN secciones s        ON s.id  = m.seccion_id
            INNER JOIN grados g           ON g.id  = s.grado_id
            INNER JOIN niveles n          ON n.id  = g.nivel_id
            INNER JOIN calificaciones cal ON cal.matricula_id = m.id
            INNER JOIN competencias comp  ON comp.id = cal.competencia_id
            LEFT  JOIN subareas sa        ON sa.id   = comp.subarea_id
            INNER JOIN areas a            ON a.id    = COALESCE(sa.area_id, comp.area_id)
            WHERE cal.periodo_id = ?
              AND m.estado = 'aprobada'
              AND m.id NOT IN (
                  SELECT matricula_oficial_id FROM retornos_grado WHERE estado = 'activo'
              )
              AND a.tipo != 'transversal'
            GROUP BY m.id, g.id, g.numero, g.nombre_display, n.nombre, n.id
            ORDER BY n.id, g.numero
        ", [$periodoId]);

        // Agrupar por grado
        $porGrado = [];
        foreach ($filas as $f) {
            $porGrado[(int) $f['grado_id']]['info'] = [
                'grado_id'     => (int) $f['grado_id'],
                'grado_nombre' => $f['grado_nombre'],
                'nivel_nombre' => $f['nivel_nombre'],
            ];
            $porGrado[(int) $f['grado_id']]['filas'][] = $f;
        }

        $resultado = [];
        foreach ($porGrado as $grado) {
            $grupos = $this->detectarGruposIrreducibles($grado['filas']);
            $pendientes = 0;
            foreach ($grupos as $grupo) {
                $mats = array_map(static fn($r) => (int) $r['matricula_id'], $grupo);
                if ($this->desempateModel->getResolucion($periodoId, $mats) === null) {
                    $pendientes++;
                }
            }
            if ($pendientes > 0) {
                $resultado[] = $grado['info'] + ['n_grupos' => $pendientes];
            }
        }

        return $resultado;
    }

    /**
     * Dado el conjunto de alumnos de un grado, devuelve los grupos empatados
     * irreducibles (mismo promedio + N desigual, o misma distribución literal).
     */
    private function detectarGruposIrreducibles(array $alumnos): array
    {
        // Agrupar por promedio exacto
        $porProm = [];
        foreach ($alumnos as $a) {
            $clave = (string) round((float) $a['promedio_exacto'], 6);
            $porProm[$clave][] = $a;
        }

        $grupos = [];
        foreach ($porProm as $grupoProm) {
            if (count($grupoProm) < 2) {
                continue;
            }
            $ns = array_unique(array_map(static fn($r) => (int) $r['n_comp'], $grupoProm));
            if (count($ns) > 1) {
                // N desigual → todo el grupo es irreducible
                $grupos[] = $grupoProm;
                continue;
            }
            // N uniforme → subagrupar por distribución literal (num_c, num_b, num_ad)
            $porTupla = [];
            foreach ($grupoProm as $a) {
                $t = (int) $a['num_c'] . '|' . (int) $a['num_b'] . '|' . (int) $a['num_ad'];
                $porTupla[$t][] = $a;
            }
            foreach ($porTupla as $sub) {
                if (count($sub) >= 2) {
                    $grupos[] = $sub;
                }
            }
        }

        return $grupos;
    }

    // ── Chequeo 2: competencias con notas pero sin bloquear ──────

    /**
     * Secciones con competencias que tienen notas (num_criterios > 0) pero el docente
     * aún no las bloqueó → la boleta de ese grado no se puede emitir completa.
     */
    public function competenciasSinBloquear(int $periodoId): array
    {
        $todas = $this->calModel->getCompetenciasPorPeriodo($periodoId);

        $porSeccion = [];
        foreach ($todas as $c) {
            $tieneNotas  = (int) ($c['num_criterios'] ?? 0) > 0;
            $sinBloqueo  = ($c['bloqueo_id'] ?? null) === null;
            if (!$tieneNotas || !$sinBloqueo) {
                continue;
            }
            $sid = (int) $c['seccion_id'];
            if (!isset($porSeccion[$sid])) {
                $porSeccion[$sid] = [
                    'seccion_id'     => $sid,
                    'nivel_nombre'   => $c['nivel_nombre'],
                    'grado_nombre'   => $c['grado_nombre'],
                    'seccion_nombre' => $c['seccion_nombre'],
                    'n_competencias' => 0,
                ];
            }
            $porSeccion[$sid]['n_competencias']++;
        }

        return array_values($porSeccion);
    }

    // ── Chequeo 3: secciones sin tutor (año activo) ──────────────

    public function seccionesSinTutor(): array
    {
        $secciones = $this->seccionModel->listarConTutor();
        return array_values(array_filter(
            $secciones,
            static fn($s) => empty($s['tutor_id'])
        ));
    }

    // ── Chequeo 4: matrículas pendientes de activar ──────────────

    /**
     * Matrículas del año en estado 'pendiente' (proceso de matrícula sin completar).
     * El enum real es pendiente/activo/desactivado/aprobada (migr. 014). NO se filtra
     * por `observaciones` porque las matrículas aprobadas la usan como traza normal
     * (datos de origen, cód. modular) — no es señal de inconsistencia.
     */
    public function matriculasPendientes(int $anioId): array
    {
        return $this->query("
            SELECT
                m.id,
                m.estado,
                m.observaciones,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                p.dni,
                g.nombre_display AS grado_nombre,
                s.nombre         AS seccion_nombre,
                n.nombre         AS nivel_nombre
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            INNER JOIN secciones s   ON s.id = m.seccion_id
            INNER JOIN grados g      ON g.id = s.grado_id
            INNER JOIN niveles n     ON n.id = g.nivel_id
            WHERE m.anio_id = ?
              AND m.estado = 'pendiente'
            ORDER BY n.id, g.numero, s.nombre, p.apellido_paterno
        ", [$anioId]);
    }
}
