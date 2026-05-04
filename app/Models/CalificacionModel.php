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
                nota_numerica          = VALUES(nota_numerica),
                conclusion_descriptiva = VALUES(conclusion_descriptiva),
                modificado_en          = NOW()
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

    // ── Validaciones ─────────────────────────────────────────

    /**
     * Convierte nota numérica a literal.
     */
    public static function toLiteral(int $nota): string
    {
        return match(true) {
            $nota >= 18 => 'AD',
            $nota >= 14 => 'A',
            $nota >= 11 => 'B',
            default     => 'C',
        };
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
            INNER JOIN cargas_academicas ca ON ca.id  = cal.carga_id
            INNER JOIN competencias comp    ON comp.id = cal.competencia_id
            LEFT  JOIN subareas sa          ON sa.id  = ca.subarea_id
            LEFT  JOIN areas a              ON a.id   = COALESCE(ca.area_id, sa.area_id)
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
}