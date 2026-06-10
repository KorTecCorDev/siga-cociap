<?php

namespace App\Models;

/**
 * CalificacionModel
 * Gestiona notas por criterio y calcula promedios de competencias.
 */
class CalificacionModel extends BaseModel
{
    protected string $table = 'calificaciones';

    // ── Notas por criterio ───────────────────────────────────

    /**
     * Guarda o actualiza la nota de un alumno en un criterio.
     */
    public function guardarNotaCriterio(
        int $criterioId,
        int $matriculaId,
        int $nota
    ): bool {
        return $this->execute("
            INSERT INTO calificaciones_criterio
                (criterio_id, matricula_id, nota, registrado_en)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                nota          = VALUES(nota),
                modificado_en = NOW()
        ", [$criterioId, $matriculaId, $nota]);
    }

    /**
     * Guarda las notas de TODOS los alumnos de un criterio.
     * Recibe array [matricula_id => nota].
     */
    public function guardarNotasMasivas(
        int $criterioId,
        array $notas
    ): bool {
        if (empty($notas)) return true;

        $this->beginTransaction();
        try {
            foreach ($notas as $matriculaId => $nota) {
                $nota = max(0, min(20, (int) $nota));
                $this->guardarNotaCriterio(
                    $criterioId,
                    (int) $matriculaId,
                    $nota
                );
            }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            log_error('Error guardando notas masivas', [
                'criterio_id' => $criterioId,
                'error'       => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ── Promedios ────────────────────────────────────────────

    /**
     * Calcula el promedio de un alumno en una competencia.
     */
    public function calcularPromedio(
        int $matriculaId,
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): ?float {
        $resultado = $this->queryOne("
            SELECT ROUND(AVG(cc.nota), 0) AS promedio
            FROM calificaciones_criterio cc
            INNER JOIN criterios cr ON cr.id = cc.criterio_id
            WHERE cc.matricula_id   = ?
              AND cr.carga_id       = ?
              AND cr.competencia_id = ?
              AND cr.periodo_id     = ?
              AND cr.eliminado_en   IS NULL
        ", [$matriculaId, $cargaId, $competenciaId, $periodoId]);

        return isset($resultado['promedio'])
            ? (float) $resultado['promedio']
            : null;
    }

    /**
     * Guarda la nota final de una competencia (promedio de criterios).
     */
    public function guardarNotaFinal(
        int $matriculaId,
        int $cargaId,
        int $periodoId,
        int $competenciaId,
        int $notaNumerica,
        int $registradoPor,
        ?string $conclusion = null
    ): bool {
        return $this->execute("
            INSERT INTO calificaciones
                (matricula_id, carga_id, periodo_id, competencia_id,
                 nota_numerica, conclusion_descriptiva,
                 registrado_por, registrado_en)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                nota_numerica = VALUES(nota_numerica),
                modificado_en = NOW()
        ", [
            $matriculaId, $cargaId, $periodoId, $competenciaId,
            $notaNumerica, $conclusion, $registradoPor
        ]);
    }

    /**
     * Recalcula el promedio de TODOS los alumnos de una sección
     * para una competencia y periodo.
     */
    public function recalcularPromedioSeccion(
        int $cargaId,
        int $competenciaId,
        int $periodoId,
        int $registradoPor
    ): void {
        $alumnos = $this->query("
            SELECT DISTINCT cc.matricula_id
            FROM calificaciones_criterio cc
            INNER JOIN criterios cr ON cr.id = cc.criterio_id
            WHERE cr.carga_id       = ?
              AND cr.competencia_id = ?
              AND cr.periodo_id     = ?
              AND cr.eliminado_en   IS NULL
        ", [$cargaId, $competenciaId, $periodoId]);

        foreach ($alumnos as $alumno) {
            $promedio = $this->calcularPromedio(
                $alumno['matricula_id'],
                $cargaId,
                $competenciaId,
                $periodoId
            );

            if ($promedio !== null) {
                $this->guardarNotaFinal(
                    $alumno['matricula_id'],
                    $cargaId,
                    $periodoId,
                    $competenciaId,
                    (int) round($promedio),
                    $registradoPor
                );
            }
        }
    }

    /**
     * Actualiza la conclusión descriptiva de un alumno en una competencia.
     * Retorna false si no existe la fila en calificaciones (notas no guardadas aún).
     */
    public function actualizarConclusion(
        int $matriculaId,
        int $cargaId,
        int $competenciaId,
        int $periodoId,
        string $conclusion
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE calificaciones
            SET conclusion_descriptiva = ?,
                modificado_en          = NOW()
            WHERE matricula_id   = ?
              AND carga_id       = ?
              AND competencia_id = ?
              AND periodo_id     = ?
        ");
        $stmt->execute([$conclusion, $matriculaId, $cargaId, $competenciaId, $periodoId]);
        return $stmt->rowCount() > 0;
    }

    // ── Validaciones ─────────────────────────────────────────

    /**
     * Convierte nota numérica a literal.
     */
    public static function toLiteral(int $nota): string
    {
        return nota_a_literal($nota);
    }

    /**
     * Verifica si la conclusión descriptiva es obligatoria.
     */
    public static function conclusionObligatoria(
        string $literal,
        string $nivelCodigo
    ): bool {
        if ($nivelCodigo === 'prim') {
            return in_array($literal, ['B', 'C']);
        }
        return $literal === 'C';
    }

    /**
     * Verifica si el periodo está bloqueado para el docente.
     */
    public function periodoEstaBloqueado(int $periodoId): bool
    {
        $periodo = $this->queryOne("
            SELECT limite_notas, estado
            FROM periodos
            WHERE id = ?
        ", [$periodoId]);

        if (!$periodo) return true;
        if ($periodo['estado'] === 'cerrado') return true;
        if ($periodo['limite_notas'] === null) return false;

        return strtotime($periodo['limite_notas']) < time();
    }

    /**
     * Obtiene la boleta completa de un alumno en un periodo.
     */
    public function getBoletaAlumno(
        int $matriculaId,
        int $periodoId
    ): array {
        $notas = $this->query("
            SELECT
                cal.nota_numerica,
                cal.conclusion_descriptiva,
                comp.id              AS competencia_id,
                comp.nombre_completo AS competencia_nombre,
                comp.nombre_corto,
                comp.codigo_minedu,
                a.id                 AS area_id,
                a.nombre             AS area_nombre,
                a.nombre_boleta,
                a.alias_boleta,
                a.tipo               AS area_tipo,
                sa.nombre            AS subarea_nombre,
                ca.id                AS carga_id
            FROM calificaciones cal
            INNER JOIN cargas_academicas ca    ON ca.id   = cal.carga_id
            INNER JOIN competencias comp       ON comp.id = cal.competencia_id
            INNER JOIN bloqueos_competencia bc ON bc.carga_id       = cal.carga_id
                                               AND bc.competencia_id = cal.competencia_id
                                               AND bc.periodo_id     = cal.periodo_id
            LEFT  JOIN subareas sa             ON sa.id  = ca.subarea_id
            LEFT  JOIN areas a                 ON a.id   = COALESCE(ca.area_id, sa.area_id)
            WHERE cal.matricula_id = ?
              AND cal.periodo_id   = ?
            ORDER BY a.orden, comp.orden
        ", [$matriculaId, $periodoId]);

        foreach ($notas as &$nota) {
            $nota['criterios'] = $this->query("
                SELECT
                    cr.nombre AS criterio_nombre,
                    cr.orden,
                    cc.nota
                FROM criterios cr
                LEFT JOIN calificaciones_criterio cc
                    ON cc.criterio_id  = cr.id
                    AND cc.matricula_id = ?
                WHERE cr.carga_id       = ?
                  AND cr.competencia_id = ?
                  AND cr.periodo_id     = ?
                  AND cr.eliminado_en   IS NULL
                ORDER BY cr.orden
            ", [
                $matriculaId,
                $nota['carga_id'],
                $nota['competencia_id'],
                $periodoId
            ]);
        }

        return $notas;
    }

    // ─── Bloqueos de competencia ─────────────────────────────────

/**
 * Verifica si una competencia está bloqueada por el docente.
 */
    public function competenciaBloqueada(
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): bool {
        $resultado = $this->queryOne("
            SELECT id FROM bloqueos_competencia
            WHERE carga_id       = ?
            AND competencia_id = ?
            AND periodo_id     = ?
            LIMIT 1
        ", [$cargaId, $competenciaId, $periodoId]);

        return $resultado !== null;
    }

    /**
     * Bloquea una competencia — el docente aprueba sus notas.
     */
    public function bloquearCompetencia(
        int $cargaId,
        int $competenciaId,
        int $periodoId,
        int $usuarioId
    ): bool {
        return $this->execute("
            INSERT IGNORE INTO bloqueos_competencia
                (carga_id, competencia_id, periodo_id, bloqueado_por)
            VALUES (?, ?, ?, ?)
        ", [$cargaId, $competenciaId, $periodoId, $usuarioId]);
    }

    /**
     * Obtiene el resumen de notas de todos los alumnos
     * para una competencia específica.
     */
    /**
     * Elimina un bloqueo por su ID (desbloquea la competencia).
     */
    public function desbloquearCompetencia(int $bloqueoId): bool
    {
        return $this->execute("
            DELETE FROM bloqueos_competencia WHERE id = ?
        ", [$bloqueoId]);
    }

    /**
     * Devuelve TODAS las (carga, competencia) del año del periodo,
     * con o sin criterios, junto con su estado de bloqueo.
     * Incluye num_criterios para distinguir los cuatro estados posibles:
     *   bloqueada con notas | bloqueada sin notas | pendiente | sin criterios
     */
    public function getCompetenciasPorPeriodo(int $periodoId): array
    {
        return $this->query("
            SELECT
                ca.id                AS carga_id,
                comp.id              AS competencia_id,
                bc.id                AS bloqueo_id,
                bc.bloqueado_en,
                comp.nombre_completo AS competencia_nombre,
                a.nombre             AS area_nombre,
                sa.nombre            AS subarea_nombre,
                n.nombre             AS nivel_nombre,
                n.codigo             AS nivel_codigo,
                n.id                 AS nivel_id,
                g.numero             AS grado_numero,
                g.nombre_display     AS grado_nombre,
                s.id                 AS seccion_id,
                s.nombre             AS seccion_nombre,
                ud.id                AS docente_id,
                pu.apellido_paterno  AS docente_apellido,
                pu.nombres           AS docente_nombres,
                (
                    SELECT COUNT(*)
                    FROM criterios cr
                    WHERE cr.carga_id       = ca.id
                      AND cr.competencia_id = comp.id
                      AND cr.periodo_id     = ?
                      AND cr.eliminado_en   IS NULL
                )                    AS num_criterios
            FROM cargas_academicas ca
            INNER JOIN secciones s   ON s.id   = ca.seccion_id
            INNER JOIN grados g      ON g.id   = s.grado_id
            INNER JOIN niveles n     ON n.id   = g.nivel_id
            INNER JOIN usuarios ud   ON ud.id  = ca.docente_id
            INNER JOIN personas pu   ON pu.id  = ud.persona_id
            LEFT  JOIN subareas sa   ON sa.id  = ca.subarea_id
            LEFT  JOIN areas a       ON a.id   = COALESCE(ca.area_id, sa.area_id)
            INNER JOIN competencias comp ON (
                (ca.subarea_id IS NOT NULL AND comp.subarea_id = ca.subarea_id)
                OR
                (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL AND comp.area_id = ca.area_id)
            )
            LEFT JOIN bloqueos_competencia bc
                ON  bc.carga_id       = ca.id
                AND bc.competencia_id = comp.id
                AND bc.periodo_id     = ?
            WHERE ca.estado  = 'activa'
              AND ca.anio_id = (SELECT anio_id FROM periodos WHERE id = ?)
            ORDER BY n.id, g.numero, s.nombre, a.orden, comp.orden
        ", [$periodoId, $periodoId, $periodoId]);
    }

    public function getResumenCompetencia(
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): array {
        // Obtener criterios activos (excluye eliminados)
        $criterios = $this->query("
            SELECT id, nombre, orden
            FROM criterios
            WHERE carga_id       = ?
            AND competencia_id = ?
            AND periodo_id     = ?
            AND eliminado_en   IS NULL
            ORDER BY orden, id
        ", [$cargaId, $competenciaId, $periodoId]);

        // Obtener alumnos con sus notas
        $alumnos = $this->query("
            SELECT
                m.id AS matricula_id,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                p.dni,
                CONCAT(
                    p.apellido_paterno, ' ',
                    p.apellido_materno, ', ',
                    p.nombres
                ) AS nombre_completo,
                cal.nota_numerica AS promedio,
                cal.conclusion_descriptiva
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            INNER JOIN secciones s   ON s.id = m.seccion_id
            LEFT JOIN calificaciones cal
                ON  cal.matricula_id   = m.id
                AND cal.carga_id       = ?
                AND cal.competencia_id = ?
                AND cal.periodo_id     = ?
            WHERE s.id = (
                SELECT seccion_id FROM cargas_academicas WHERE id = ?
            )
            -- Mismo criterio que getAlumnosSeccion: incluye 'pendiente' (recién
            -- creadas) y 'aprobada' (vigentes, incl. retorno de grado), excluye
            -- trasladados. Así el resumen y la validación de bloqueo cuadran con
            -- la grilla de ingreso.
            AND m.estado IN ('aprobada', 'pendiente')
            AND m.tipo  != 'trasladado'
            ORDER BY p.apellido_paterno, p.apellido_materno
        ", [$cargaId, $competenciaId, $periodoId, $cargaId]);

        // Agregar notas por criterio a cada alumno
        foreach ($alumnos as &$alumno) {
            $alumno['notas_criterios'] = [];
            foreach ($criterios as $criterio) {
                $nota = $this->queryOne("
                    SELECT nota
                    FROM calificaciones_criterio
                    WHERE criterio_id  = ?
                    AND matricula_id = ?
                ", [$criterio['id'], $alumno['matricula_id']]);

                $alumno['notas_criterios'][$criterio['id']] = $nota['nota'] ?? null;
            }

            // Calcular literal del promedio
            $alumno['literal'] = $alumno['promedio'] !== null
                ? nota_a_literal((int) $alumno['promedio'])
                : null;
        }

        return [
            'criterios' => $criterios,
            'alumnos'   => $alumnos,
        ];
    }
}

