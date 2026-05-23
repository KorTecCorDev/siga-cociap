<?php

namespace App\Models;

class AsistenciaModel extends BaseModel
{
    // ── Consultas para el panel de registro ─────────────────────

    /** Secciones del año activo con info de nivel/grado para el índice. */
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
            INNER JOIN grados            g ON g.id = s.grado_id
            INNER JOIN niveles           n ON n.id = g.nivel_id
            INNER JOIN anios_academicos  a ON a.id = s.anio_id
            WHERE a.estado = 'activo'
              AND s.estado_nomina = 'aprobada'
            ORDER BY n.id, g.numero, s.nombre
        ");
    }

    /**
     * Periodos del año activo con flag de edición.
     * "editable" solo es true cuando el periodo está en estado 'activo'
     * y dentro del límite de notas. Mismo criterio que ConductaModel para
     * mantener coherencia entre módulos.
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
     * Progreso de llenado de incidencias por sección para un periodo dado.
     * Una sección está "al X%" según cuántas matrículas tienen al menos
     * una fila guardada (incluso con todos los contadores en cero, porque
     * fue una acción consciente del operador).
     * Devuelve [seccion_id => ['esperados' => N, 'registrados' => M]].
     */
    public function getProgresoPorSeccion(int $periodoId): array
    {
        $rows = $this->query("
            SELECT
                s.id                         AS seccion_id,
                COUNT(DISTINCT m.id)         AS esperados,
                COUNT(DISTINCT i.matricula_id) AS registrados
            FROM secciones s
            INNER JOIN anios_academicos a ON a.id = s.anio_id AND a.estado = 'activo'
            LEFT JOIN matriculas m
                   ON m.seccion_id = s.id
                  AND m.estado     = 'aprobada'
                  AND m.anio_id    = s.anio_id
            LEFT JOIN inasistencias i
                   ON i.matricula_id = m.id
                  AND i.periodo_id   = ?
            WHERE s.estado_nomina = 'aprobada'
            GROUP BY s.id
        ", [$periodoId]);

        $mapa = [];
        foreach ($rows as $r) {
            $mapa[(int) $r['seccion_id']] = [
                'esperados'   => (int) $r['esperados'],
                'registrados' => (int) $r['registrados'],
            ];
        }
        return $mapa;
    }

    /**
     * Estudiantes de una sección con sus incidencias del periodo activo.
     * Devuelve una fila por estudiante con los 4 contadores (en 0 si no hay registro).
     */
    public function getEstudiantesConIncidencias(int $seccionId, int $periodoId): array
    {
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
              AND m.estado     = 'aprobada'
              AND m.anio_id    = (SELECT id FROM anios_academicos WHERE estado = 'activo' LIMIT 1)
            ORDER BY p.apellido_paterno, p.apellido_materno, p.nombres
        ", [$seccionId]);

        if (empty($alumnos)) {
            return [];
        }

        $matriculaIds = array_column($alumnos, 'matricula_id');
        $placeholders = implode(',', array_fill(0, count($matriculaIds), '?'));

        $registros = $this->query("
            SELECT
                matricula_id,
                faltas,
                faltas_justificadas,
                tardanzas,
                tardanzas_justificadas
            FROM inasistencias
            WHERE matricula_id IN ($placeholders)
              AND periodo_id = ?
        ", array_merge($matriculaIds, [$periodoId]));

        $index = [];
        foreach ($registros as $r) {
            $index[(int) $r['matricula_id']] = [
                'faltas'                 => (int) $r['faltas'],
                'faltas_justificadas'    => (int) $r['faltas_justificadas'],
                'tardanzas'              => (int) $r['tardanzas'],
                'tardanzas_justificadas' => (int) $r['tardanzas_justificadas'],
                'registrado'             => true,
            ];
        }

        foreach ($alumnos as &$a) {
            $mid = (int) $a['matricula_id'];
            $a['incidencias'] = $index[$mid] ?? [
                'faltas'                 => 0,
                'faltas_justificadas'    => 0,
                'tardanzas'              => 0,
                'tardanzas_justificadas' => 0,
                'registrado'             => false,
            ];
        }

        return $alumnos;
    }

    // ── Guardar / actualizar ─────────────────────────────────────

    /**
     * Upsert atómico de los 4 contadores para una matrícula y periodo.
     * Si no había fila, se crea con registrado_por; si ya existía, se
     * actualiza dejando registrado_por con el último usuario que escribió
     * y modificado_en con NOW().
     */
    public function guardar(
        int $matriculaId,
        int $periodoId,
        int $faltas,
        int $faltasJustificadas,
        int $tardanzas,
        int $tardanzasJustificadas,
        int $userId
    ): bool {
        return $this->execute("
            INSERT INTO inasistencias
                (matricula_id, periodo_id, faltas, faltas_justificadas,
                 tardanzas, tardanzas_justificadas, registrado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                faltas                  = VALUES(faltas),
                faltas_justificadas     = VALUES(faltas_justificadas),
                tardanzas               = VALUES(tardanzas),
                tardanzas_justificadas  = VALUES(tardanzas_justificadas),
                registrado_por          = VALUES(registrado_por),
                modificado_en           = NOW()
        ", [
            $matriculaId, $periodoId,
            $faltas, $faltasJustificadas,
            $tardanzas, $tardanzasJustificadas,
            $userId,
        ]);
    }

    // ── Consultas para boleta ────────────────────────────────────

    /**
     * Incidencias de una matrícula en un periodo específico (para la boleta).
     * Devuelve los 4 contadores en cero si no hay fila guardada, así la
     * boleta nunca falla por falta de datos.
     */
    public function getDelBimestre(int $matriculaId, int $periodoId): array
    {
        $row = $this->queryOne("
            SELECT faltas, faltas_justificadas, tardanzas, tardanzas_justificadas
            FROM inasistencias
            WHERE matricula_id = ? AND periodo_id = ?
        ", [$matriculaId, $periodoId]);

        if (!$row) {
            return [
                'faltas'                 => 0,
                'faltas_justificadas'    => 0,
                'tardanzas'              => 0,
                'tardanzas_justificadas' => 0,
            ];
        }

        return [
            'faltas'                 => (int) $row['faltas'],
            'faltas_justificadas'    => (int) $row['faltas_justificadas'],
            'tardanzas'              => (int) $row['tardanzas'],
            'tardanzas_justificadas' => (int) $row['tardanzas_justificadas'],
        ];
    }

    /**
     * Acumulado anual hasta el periodo dado (incluido).
     * Suma los contadores de todos los periodos del año académico cuyo
     * "numero" sea <= al numero del periodo de la boleta. Esto evita que
     * una boleta del I Bimestre incluya datos de bimestres posteriores.
     */
    public function getAcumuladoAnual(int $matriculaId, int $periodoIdHasta): array
    {
        $row = $this->queryOne("
            SELECT
                COALESCE(SUM(i.faltas), 0)                 AS faltas,
                COALESCE(SUM(i.faltas_justificadas), 0)    AS faltas_justificadas,
                COALESCE(SUM(i.tardanzas), 0)              AS tardanzas,
                COALESCE(SUM(i.tardanzas_justificadas), 0) AS tardanzas_justificadas
            FROM inasistencias i
            INNER JOIN periodos p_ref ON p_ref.id = ?
            INNER JOIN periodos p_row ON p_row.id = i.periodo_id
            WHERE i.matricula_id = ?
              AND p_row.anio_id  = p_ref.anio_id
              AND p_row.numero  <= p_ref.numero
        ", [$periodoIdHasta, $matriculaId]);

        return [
            'faltas'                 => (int) ($row['faltas']                 ?? 0),
            'faltas_justificadas'    => (int) ($row['faltas_justificadas']    ?? 0),
            'tardanzas'              => (int) ($row['tardanzas']              ?? 0),
            'tardanzas_justificadas' => (int) ($row['tardanzas_justificadas'] ?? 0),
        ];
    }

    // ── Verificación de edición ──────────────────────────────────

    /**
     * true si el periodo está abierto para edición de asistencia.
     * Mismo criterio que ConductaModel::periodoEditable.
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
