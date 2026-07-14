<?php

namespace App\Models;

/**
 * NotaAutorizadaSiagieModel
 *
 * "Informe aparte" de notas que dirección (director EBR) ordena consignar SOLO
 * para el SIAGIE, cuando un alumno no fue evaluado y el docente dejó registrado
 * un MOTIVO de omisión (cualquiera). NO toca `calificaciones`, ni la boleta, ni
 * el orden de mérito: solo rellena la celda en blanco del export SIAGIE.
 *
 * Candado de elegibilidad (ver competenciasElegibles): una competencia solo es
 * autorizable si el alumno tiene una omisión REGISTRADA en ella (con cualquier
 * motivo), está bloqueada y NO tiene calificación viva. El "por qué faltó" lo
 * clasifica el docente en la omisión; la decisión de autorizar es de dirección.
 * Ver migración 040 y docs/modulos/export-siagie.md.
 */
class NotaAutorizadaSiagieModel extends BaseModel
{
    protected string $table = 'notas_autorizadas_siagie';

    /**
     * Notas autorizadas del alumno en el periodo, para el EXPORT.
     * @return array competencia_id => ['literal'=>string, 'conclusion'=>?string]
     */
    public function getParaExport(int $matriculaId, int $periodoId): array
    {
        $rows = $this->query("
            SELECT competencia_id, nota_literal, conclusion_descriptiva
            FROM notas_autorizadas_siagie
            WHERE matricula_id = ? AND periodo_id = ?
        ", [$matriculaId, $periodoId]);

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['competencia_id']] = [
                'literal'    => $r['nota_literal'],
                'conclusion' => $r['conclusion_descriptiva'] !== null
                    ? trim((string) $r['conclusion_descriptiva'])
                    : null,
            ];
        }
        return $out;
    }

    /**
     * Registros del alumno en el periodo, con nombres para la UI/informe.
     */
    public function getDetalle(int $matriculaId, int $periodoId): array
    {
        return $this->query("
            SELECT
                na.id, na.competencia_id, na.nota_literal,
                na.conclusion_descriptiva, na.resolucion, na.registrado_en,
                comp.nombre_completo AS competencia_nombre,
                a.nombre             AS area_nombre,
                TRIM(CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres)) AS registrador
            FROM notas_autorizadas_siagie na
            INNER JOIN competencias comp ON comp.id = na.competencia_id
            LEFT  JOIN areas a           ON a.id   = comp.area_id
            LEFT  JOIN usuarios u        ON u.id   = na.registrado_por
            LEFT  JOIN personas p        ON p.id   = u.persona_id
            WHERE na.matricula_id = ? AND na.periodo_id = ?
            ORDER BY a.nombre, comp.nombre_completo
        ", [$matriculaId, $periodoId]);
    }

    /**
     * Competencias AUTORIZABLES del alumno en el periodo: bloqueadas en su
     * sección, con una omisión REGISTRADA del alumno (cualquier motivo) y SIN
     * calificación viva (la celda que el SIAGIE exige y SIGA deja en blanco).
     * Excluye las que ya tienen nota autorizada registrada.
     *
     * @return array filas [competencia_id, competencia_nombre, area_nombre, carga_id]
     */
    public function competenciasElegibles(int $matriculaId, int $seccionId, int $periodoId): array
    {
        return $this->query("
            SELECT DISTINCT
                comp.id              AS competencia_id,
                comp.nombre_completo AS competencia_nombre,
                a.nombre             AS area_nombre,
                bc.carga_id          AS carga_id
            FROM bloqueos_competencia bc
            INNER JOIN cargas_academicas ca ON ca.id = bc.carga_id
                                           AND ca.seccion_id = ?
            INNER JOIN competencias comp    ON comp.id = bc.competencia_id
            LEFT  JOIN subareas sa          ON sa.id = ca.subarea_id
            LEFT  JOIN areas a              ON a.id  = COALESCE(ca.area_id, sa.area_id)
            WHERE bc.periodo_id = ?
              -- El alumno tiene una omisión REGISTRADA en esta competencia
              -- (cualquier motivo: basta que el docente la haya dejado marcada).
              AND EXISTS (
                  SELECT 1
                  FROM omisiones_criterio oc
                  INNER JOIN criterios cr ON cr.id = oc.criterio_id
                  WHERE cr.carga_id       = bc.carga_id
                    AND cr.competencia_id = bc.competencia_id
                    AND cr.periodo_id     = bc.periodo_id
                    AND cr.eliminado_en   IS NULL
                    AND oc.matricula_id   = ?
              )
              -- Y NO tiene calificación viva (la celda está realmente en blanco).
              AND NOT EXISTS (
                  SELECT 1
                  FROM calificaciones cal
                  WHERE cal.matricula_id   = ?
                    AND cal.competencia_id = bc.competencia_id
                    AND cal.periodo_id     = bc.periodo_id
                    AND cal.carga_id       = bc.carga_id
              )
              -- Y no está ya autorizada.
              AND NOT EXISTS (
                  SELECT 1
                  FROM notas_autorizadas_siagie na
                  WHERE na.matricula_id   = ?
                    AND na.competencia_id = bc.competencia_id
                    AND na.periodo_id     = bc.periodo_id
              )
            ORDER BY a.nombre, comp.nombre_completo
        ", [$seccionId, $periodoId, $matriculaId, $matriculaId, $matriculaId]);
    }

    /**
     * ¿La competencia es autorizable para este alumno/periodo? Candado de
     * SERVIDOR: se llama ANTES de registrar (no confiar en el POST). A diferencia
     * de competenciasElegibles NO excluye las ya autorizadas (para permitir
     * editar una existente): comprueba bloqueo + omisión registrada (cualquier
     * motivo) + sin calificación viva, que siguen siendo ciertos tras autorizar.
     */
    public function esElegible(int $matriculaId, int $seccionId, int $periodoId, int $competenciaId): bool
    {
        return (bool) $this->queryOne("
            SELECT 1
            FROM bloqueos_competencia bc
            INNER JOIN cargas_academicas ca ON ca.id = bc.carga_id
                                           AND ca.seccion_id = ?
            WHERE bc.periodo_id     = ?
              AND bc.competencia_id = ?
              AND EXISTS (
                  SELECT 1
                  FROM omisiones_criterio oc
                  INNER JOIN criterios cr ON cr.id = oc.criterio_id
                  WHERE cr.carga_id       = bc.carga_id
                    AND cr.competencia_id = bc.competencia_id
                    AND cr.periodo_id     = bc.periodo_id
                    AND cr.eliminado_en   IS NULL
                    AND oc.matricula_id   = ?
              )
              AND NOT EXISTS (
                  SELECT 1
                  FROM calificaciones cal
                  WHERE cal.matricula_id   = ?
                    AND cal.competencia_id = bc.competencia_id
                    AND cal.periodo_id     = bc.periodo_id
                    AND cal.carga_id       = bc.carga_id
              )
            LIMIT 1
        ", [$seccionId, $periodoId, $competenciaId, $matriculaId, $matriculaId]);
    }

    /**
     * Registra o actualiza una nota autorizada. Idempotente por el UNIQUE
     * (matricula_id, competencia_id, periodo_id).
     */
    public function registrar(array $d): bool
    {
        return $this->execute("
            INSERT INTO notas_autorizadas_siagie
                (matricula_id, competencia_id, periodo_id, nota_literal,
                 conclusion_descriptiva, resolucion, registrado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                nota_literal           = VALUES(nota_literal),
                conclusion_descriptiva = VALUES(conclusion_descriptiva),
                resolucion             = VALUES(resolucion),
                registrado_por         = VALUES(registrado_por),
                actualizado_en         = CURRENT_TIMESTAMP
        ", [
            (int) $d['matricula_id'],
            (int) $d['competencia_id'],
            (int) $d['periodo_id'],
            $d['nota_literal'],
            $d['conclusion_descriptiva'] ?? null,
            $d['resolucion'],
            (int) $d['registrado_por'],
        ]);
    }

    /**
     * Todas las notas autorizadas de la matrícula (todos los bimestres), con
     * nombres para el resumen de la card en el detalle.
     */
    public function getTodasPorMatricula(int $matriculaId): array
    {
        return $this->query("
            SELECT
                na.id, na.periodo_id, na.competencia_id, na.nota_literal,
                comp.nombre_completo AS competencia_nombre,
                per.nombre_display   AS periodo_nombre,
                per.numero           AS periodo_numero
            FROM notas_autorizadas_siagie na
            INNER JOIN competencias comp ON comp.id = na.competencia_id
            INNER JOIN periodos per       ON per.id = na.periodo_id
            WHERE na.matricula_id = ?
            ORDER BY per.numero, comp.nombre_completo
        ", [$matriculaId]);
    }

    /** Elimina una nota autorizada por id, acotada a la matrícula (seguridad). */
    public function eliminar(int $id, int $matriculaId): bool
    {
        return $this->execute(
            "DELETE FROM notas_autorizadas_siagie WHERE id = ? AND matricula_id = ?",
            [$id, $matriculaId]
        );
    }
}
