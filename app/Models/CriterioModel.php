<?php

namespace App\Models;

/**
 * CriterioModel
 * Gestiona los criterios de evaluación definidos por cada docente.
 */
class CriterioModel extends BaseModel
{
    protected string $table = 'criterios';

    /**
     * Obtiene los criterios activos (no eliminados) de una carga + competencia + periodo.
     */
    public function getCriterios(
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): array {
        return $this->query("
            SELECT *
            FROM criterios
            WHERE carga_id       = ?
              AND competencia_id = ?
              AND periodo_id     = ?
              AND eliminado_en   IS NULL
            ORDER BY orden, id
        ", [$cargaId, $competenciaId, $periodoId]);
    }

    /**
     * Crea un nuevo criterio y ajusta el orden automáticamente.
     */
    public function crear(
        int $cargaId,
        int $competenciaId,
        int $periodoId,
        string $nombre,
        ?string $descripcion = null
    ): int {
        $ultimo = $this->queryOne("
            SELECT MAX(orden) AS ultimo
            FROM criterios
            WHERE carga_id       = ?
              AND competencia_id = ?
              AND periodo_id     = ?
              AND eliminado_en   IS NULL
        ", [$cargaId, $competenciaId, $periodoId]);

        $orden = ($ultimo['ultimo'] ?? 0) + 1;

        return $this->create([
            'carga_id'       => $cargaId,
            'competencia_id' => $competenciaId,
            'periodo_id'     => $periodoId,
            'nombre'         => trim($nombre),
            'descripcion'    => ($descripcion !== null && trim($descripcion) !== '')
                ? trim($descripcion) : null,
            'orden'          => $orden,
        ]);
    }

    /**
     * Verifica si un criterio ya tiene calificaciones registradas.
     */
    public function tieneCalificaciones(int $criterioId): bool
    {
        $resultado = $this->queryOne("
            SELECT COUNT(*) AS total
            FROM calificaciones_criterio
            WHERE criterio_id = ?
        ", [$criterioId]);

        return ($resultado['total'] ?? 0) > 0;
    }

    /**
     * Cambia el nombre y la descripción de un criterio.
     * Siempre permitido, incluso con calificaciones.
     * Descripción vacía o null la limpia (NULL en BD).
     */
    public function renombrar(int $id, string $nombre, ?string $descripcion = null): bool
    {
        $desc = ($descripcion !== null && trim($descripcion) !== '')
            ? trim($descripcion) : null;

        return $this->execute(
            "UPDATE criterios SET nombre = ?, descripcion = ? WHERE id = ?",
            [trim($nombre), $desc, $id]
        );
    }

    /**
     * Sella el criterio como CONFIRMADO por el docente (clic en "Confirmar",
     * endpoint /guardar). El autosave NUNCA llama esto. Solo escribe si aún
     * está en NULL, para preservar la marca de tiempo de la primera confirmación.
     * Habilita el botón "Ver resumen" de la competencia de forma persistente.
     */
    public function marcarConfirmado(int $id, int $confirmadoPor): bool
    {
        return $this->execute(
            "UPDATE criterios
             SET confirmado_en  = NOW(),
                 confirmado_por = ?
             WHERE id           = ?
               AND confirmado_en IS NULL",
            [$confirmadoPor, $id]
        );
    }

    /**
     * Soft-delete con auditoría: marca el criterio como eliminado.
     * Funciona aunque el criterio ya tenga calificaciones registradas.
     * Los registros de criterios y calificaciones_criterio se conservan en BD.
     */
    public function eliminarConAuditoria(int $id, int $eliminadoPor): bool
    {
        return $this->execute(
            "UPDATE criterios
             SET eliminado_en  = NOW(),
                 eliminado_por = ?
             WHERE id          = ?
               AND eliminado_en IS NULL",
            [$eliminadoPor, $id]
        );
    }

    /**
     * Obtiene todas las competencias de una carga con sus criterios.
     */
    public function getCompetenciasConCriterios(
        int $cargaId,
        int $periodoId
    ): array {
        $competencias = $this->query("
            SELECT
                c.id,
                c.nombre_completo,
                c.nombre_corto,
                c.codigo_minedu,
                c.orden,
                -- Promedio actual de la competencia (excluye criterios eliminados)
                ROUND(
                    (
                        SELECT AVG(cc.nota)
                        FROM calificaciones_criterio cc
                        INNER JOIN criterios cr ON cr.id = cc.criterio_id
                        WHERE cr.carga_id       = ?
                        AND cr.competencia_id = c.id
                        AND cr.periodo_id     = ?
                        AND cr.eliminado_en   IS NULL
                    ), 0
                ) AS promedio_actual,
                -- Cuántos alumnos tienen nota (excluye criterios eliminados)
                (
                    SELECT COUNT(DISTINCT cc.matricula_id)
                    FROM calificaciones_criterio cc
                    INNER JOIN criterios cr ON cr.id = cc.criterio_id
                    WHERE cr.carga_id       = ?
                    AND cr.competencia_id = c.id
                    AND cr.periodo_id     = ?
                    AND cr.eliminado_en   IS NULL
                ) AS alumnos_calificados,
                -- Conclusión descriptiva si existe
                (
                    SELECT cal.conclusion_descriptiva
                    FROM calificaciones cal
                    WHERE cal.carga_id       = ?
                    AND cal.competencia_id = c.id
                    AND cal.periodo_id     = ?
                    LIMIT 1
                ) AS conclusion_descriptiva
            FROM competencias c
            WHERE c.id IN (
                SELECT comp.id FROM competencias comp
                INNER JOIN cargas_academicas ca
                    ON ca.subarea_id = comp.subarea_id
                WHERE ca.id = ?
                AND comp.subarea_id IS NOT NULL
                UNION
                SELECT comp.id FROM competencias comp
                INNER JOIN cargas_academicas ca
                    ON ca.area_id = comp.area_id
                WHERE ca.id = ?
                AND comp.area_id IS NOT NULL
            )
            ORDER BY c.orden
        ", [
            $cargaId, $periodoId,
            $cargaId, $periodoId,
            $cargaId, $periodoId,
            $cargaId, $cargaId
        ]);

        foreach ($competencias as &$competencia) {
            $competencia['criterios'] = $this->getCriterios(
                $cargaId,
                $competencia['id'],
                $periodoId
            );

            // Calcular literal del promedio
            $competencia['literal_actual'] = $competencia['promedio_actual'] !== null
                ? nota_a_literal((int) $competencia['promedio_actual'])
                : null;
        }

        return $competencias;
    }

    /**
     * Competencias TRANSVERSALES (TIC/GAMA) del nivel con los criterios de
     * ESTA carga — misma estructura que getCompetenciasConCriterios para que
     * la vista del docente las renderice con el mismo mecanismo.
     */
    public function getCompetenciasTransversalesConCriterios(
        int $cargaId,
        int $periodoId,
        int $nivelId
    ): array {
        $competencias = $this->query("
            SELECT
                c.id,
                c.nombre_completo,
                c.nombre_corto,
                c.codigo_minedu,
                c.orden,
                ROUND(
                    (
                        SELECT AVG(cc.nota)
                        FROM calificaciones_criterio cc
                        INNER JOIN criterios cr ON cr.id = cc.criterio_id
                        WHERE cr.carga_id       = ?
                        AND cr.competencia_id = c.id
                        AND cr.periodo_id     = ?
                        AND cr.eliminado_en   IS NULL
                    ), 0
                ) AS promedio_actual,
                (
                    SELECT COUNT(DISTINCT cc.matricula_id)
                    FROM calificaciones_criterio cc
                    INNER JOIN criterios cr ON cr.id = cc.criterio_id
                    WHERE cr.carga_id       = ?
                    AND cr.competencia_id = c.id
                    AND cr.periodo_id     = ?
                    AND cr.eliminado_en   IS NULL
                ) AS alumnos_calificados,
                NULL AS conclusion_descriptiva
            FROM competencias c
            INNER JOIN areas a ON a.id = c.area_id
            WHERE a.tipo     = 'transversal'
              AND a.nivel_id = ?
            ORDER BY c.orden
        ", [
            $cargaId, $periodoId,
            $cargaId, $periodoId,
            $nivelId
        ]);

        foreach ($competencias as &$competencia) {
            $competencia['es_transversal'] = true;
            $competencia['criterios'] = $this->getCriterios(
                $cargaId,
                $competencia['id'],
                $periodoId
            );
            $competencia['literal_actual'] = $competencia['promedio_actual'] !== null
                ? nota_a_literal((int) $competencia['promedio_actual'])
                : null;
        }

        return $competencias;
    }
}