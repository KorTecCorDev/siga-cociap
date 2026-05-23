<?php

namespace App\Models;

class ConductaModel extends BaseModel
{
    // ── Consultas para el administrador ─────────────────────────

    /** Secciones del año activo con info de nivel/grado para el índice admin. */
    public function listarSeccionesActivas(): array
    {
        return $this->query("
            SELECT
                s.id,
                s.nombre          AS seccion_nombre,
                g.nombre_display  AS grado_nombre,
                g.numero          AS grado_numero,
                n.nombre          AS nivel_nombre,
                n.id              AS nivel_id,
                a.id              AS anio_id,
                a.anio
            FROM secciones s
            INNER JOIN grados g             ON g.id = s.grado_id
            INNER JOIN niveles n            ON n.id = g.nivel_id
            INNER JOIN anios_academicos a   ON a.id = s.anio_id
            WHERE a.estado = 'activo'
              AND s.estado_nomina = 'aprobada'
            ORDER BY n.id, g.numero, s.nombre
        ");
    }

    /**
     * Periodos del año activo con flag de edición.
     * "editable" solo es true cuando el periodo está en estado 'activo'
     * y dentro del límite de notas. Los periodos 'pendiente' (aún no abiertos)
     * y 'cerrado' (ya vencidos) quedan como no editables.
     */
    public function listarPeriodosActivos(): array
    {
        return $this->query("
            SELECT
                p.id,
                p.numero,
                p.nombre_display,
                p.estado,
                p.limite_notas,
                (
                    p.estado = 'activo'
                    AND (p.limite_notas IS NULL OR NOW() <= p.limite_notas)
                ) AS editable
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE a.estado = 'activo'
            ORDER BY p.numero
        ");
    }

    /**
     * Progreso de llenado de conducta por sección para un periodo dado.
     * Devuelve un mapa [seccion_id => ['esperados' => N, 'calificados' => M]].
     * Una sola query con LEFT JOIN para evitar N+1 cuando hay decenas de
     * secciones. Solo se consideran matrículas con estado 'aprobada' del año
     * activo (mismo criterio que getEstudiantesConConducra).
     */
    public function getProgresoConductaPorSeccion(int $periodoId): array
    {
        $rows = $this->query("
            SELECT
                s.id                            AS seccion_id,
                COUNT(DISTINCT m.id)            AS esperados,
                COUNT(DISTINCT cc.matricula_id) AS calificados
            FROM secciones s
            INNER JOIN anios_academicos a ON a.id = s.anio_id AND a.estado = 'activo'
            LEFT JOIN matriculas m
                   ON m.seccion_id = s.id
                  AND m.estado     = 'aprobada'
                  AND m.anio_id    = s.anio_id
            LEFT JOIN calificaciones_conducta cc
                   ON cc.matricula_id = m.id
                  AND cc.periodo_id   = ?
            WHERE s.estado_nomina = 'aprobada'
            GROUP BY s.id
        ", [$periodoId]);

        $mapa = [];
        foreach ($rows as $r) {
            $mapa[(int) $r['seccion_id']] = [
                'esperados'   => (int) $r['esperados'],
                'calificados' => (int) $r['calificados'],
            ];
        }
        return $mapa;
    }

    /**
     * Estudiantes de una sección con su conducta por periodo.
     * Devuelve una fila por estudiante; la conducta de cada periodo
     * viene como conducta_{periodo_id}.
     */
    public function getEstudiantesConConducra(int $seccionId, array $periodoIds): array
    {
        if (empty($periodoIds)) {
            return [];
        }

        $alumnos = $this->query("
            SELECT
                m.id  AS matricula_id,
                CONCAT(
                    p.apellido_paterno, ' ',
                    p.apellido_materno, ', ',
                    p.nombres
                )     AS nombre_completo,
                p.dni
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas    p ON p.id = e.persona_id
            WHERE m.seccion_id = ?
              AND m.estado      = 'aprobada'
              AND m.anio_id     = (SELECT id FROM anios_academicos WHERE estado = 'activo' LIMIT 1)
            ORDER BY p.apellido_paterno, p.apellido_materno, p.nombres
        ", [$seccionId]);

        if (empty($alumnos)) {
            return [];
        }

        $matriculaIds = array_column($alumnos, 'matricula_id');
        $placeholders = implode(',', array_fill(0, count($matriculaIds), '?'));
        $pPlaceholders = implode(',', array_fill(0, count($periodoIds), '?'));

        $notas = $this->query("
            SELECT matricula_id, periodo_id, literal
            FROM calificaciones_conducta
            WHERE matricula_id IN ($placeholders)
              AND periodo_id   IN ($pPlaceholders)
        ", array_merge($matriculaIds, $periodoIds));

        // Indexar por [matricula_id][periodo_id]
        $index = [];
        foreach ($notas as $n) {
            $index[$n['matricula_id']][$n['periodo_id']] = $n['literal'];
        }

        foreach ($alumnos as &$a) {
            foreach ($periodoIds as $pid) {
                $a['conducta'][$pid] = $index[$a['matricula_id']][$pid] ?? null;
            }
        }

        return $alumnos;
    }

    // ── Guardar / actualizar ─────────────────────────────────────

    public function guardar(
        int    $matriculaId,
        int    $periodoId,
        string $literal,
        int    $userId
    ): bool {
        return $this->execute("
            INSERT INTO calificaciones_conducta
                (matricula_id, periodo_id, literal, registrado_por)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                literal         = VALUES(literal),
                registrado_por  = VALUES(registrado_por),
                modificado_en   = NOW()
        ", [$matriculaId, $periodoId, $literal, $userId]);
    }

    public function eliminar(int $matriculaId, int $periodoId): bool
    {
        return $this->execute("
            DELETE FROM calificaciones_conducta
            WHERE matricula_id = ? AND periodo_id = ?
        ", [$matriculaId, $periodoId]);
    }

    // ── Consultas para boleta y panel padre ──────────────────────

    /**
     * Devuelve [periodo_id => literal] para todos los periodos
     * del año académico del alumno. Usado en boleta y panel padre.
     */
    public function getParaBoleta(int $matriculaId, int $anioId): array
    {
        $rows = $this->query("
            SELECT cc.periodo_id, cc.literal
            FROM calificaciones_conducta cc
            INNER JOIN periodos p ON p.id = cc.periodo_id
            WHERE cc.matricula_id = ?
              AND p.anio_id       = ?
        ", [$matriculaId, $anioId]);

        $result = [];
        foreach ($rows as $r) {
            $result[(int)$r['periodo_id']] = $r['literal'];
        }
        return $result;
    }

    /**
     * Devuelve la conducta de un alumno en un único periodo.
     * Usado en el panel del padre (vista de notas del periodo activo).
     */
    public function getParaPeriodo(int $matriculaId, int $periodoId): ?string
    {
        $row = $this->queryOne("
            SELECT literal
            FROM calificaciones_conducta
            WHERE matricula_id = ? AND periodo_id = ?
        ", [$matriculaId, $periodoId]);

        return $row ? $row['literal'] : null;
    }

    // ── Verificación de edición ──────────────────────────────────

    /**
     * true si el periodo está abierto para edición de conducta.
     * Solo aceptamos estado 'activo' — los 'pendiente' aún no abren y los
     * 'cerrado' ya vencieron. Mismo criterio que el flag editable de
     * listarPeriodosActivos para mantener defense in depth coherente.
     */
    public function periodoEditable(int $periodoId): bool
    {
        $p = $this->queryOne("
            SELECT estado, limite_notas
            FROM periodos WHERE id = ?
        ", [$periodoId]);

        if (!$p || $p['estado'] !== 'activo') {
            return false;
        }
        if ($p['limite_notas'] && strtotime($p['limite_notas']) < time()) {
            return false;
        }
        return true;
    }
}
