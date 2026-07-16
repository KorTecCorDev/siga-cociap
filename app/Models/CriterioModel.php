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
     * Devuelve (o crea) el criterio ÚNICO de "Calificación extraordinaria" de
     * una competencia+carga+periodo. Lo usa SOLO el módulo de Rectificación
     * para insertar notas de RA a alumnos sin calificación del docente
     * (migración 042). Nace CONFIRMADO (el promedio agregado y el blindaje
     * anti-fantasma exigen criterio vivo confirmado) y con flag
     * `extraordinario=1` (el docente no puede tocarlo; guardas en
     * CalificacionController).
     *
     * INSERT protegido con WHERE NOT EXISTS: `criterios` no tiene UNIQUE KEY.
     */
    public function obtenerOCrearExtraordinario(
        int $cargaId,
        int $competenciaId,
        int $periodoId,
        int $usuarioId
    ): int {
        $existente = $this->queryOne("
            SELECT id FROM criterios
            WHERE carga_id       = ?
              AND competencia_id = ?
              AND periodo_id     = ?
              AND extraordinario = 1
              AND eliminado_en   IS NULL
            LIMIT 1
        ", [$cargaId, $competenciaId, $periodoId]);
        if ($existente) {
            return (int) $existente['id'];
        }

        $ultimo = $this->queryOne("
            SELECT MAX(orden) AS ultimo
            FROM criterios
            WHERE carga_id       = ?
              AND competencia_id = ?
              AND periodo_id     = ?
              AND eliminado_en   IS NULL
        ", [$cargaId, $competenciaId, $periodoId]);
        $orden = ($ultimo['ultimo'] ?? 0) + 1;

        $this->execute("
            INSERT INTO criterios
                (carga_id, competencia_id, periodo_id, nombre, descripcion,
                 orden, confirmado_en, confirmado_por, extraordinario)
            SELECT ?, ?, ?, ?, ?, ?, NOW(), ?, 1
            FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1 FROM criterios
                WHERE carga_id       = ?
                  AND competencia_id = ?
                  AND periodo_id     = ?
                  AND extraordinario = 1
                  AND eliminado_en   IS NULL
            )
        ", [
            $cargaId, $competenciaId, $periodoId,
            'Calificación extraordinaria',
            'Calificación registrada por Registro Académico a alumnos sin nota del docente. El motivo por alumno queda en la auditoría del módulo de Rectificación.',
            $orden, $usuarioId,
            $cargaId, $competenciaId, $periodoId,
        ]);

        $fila = $this->queryOne("
            SELECT id FROM criterios
            WHERE carga_id       = ?
              AND competencia_id = ?
              AND periodo_id     = ?
              AND extraordinario = 1
              AND eliminado_en   IS NULL
            LIMIT 1
        ", [$cargaId, $competenciaId, $periodoId]);

        return (int) ($fila['id'] ?? 0);
    }

    /**
     * ¿El criterio es el de "Calificación extraordinaria" (escrito por RA)?
     * Guarda de los endpoints del docente: autosave/confirmar/omisiones/
     * renombrar/eliminar lo rechazan — solo Rectificación escribe en él.
     */
    public function esExtraordinario(int $criterioId): bool
    {
        return (bool) $this->queryOne(
            "SELECT 1 FROM criterios WHERE id = ? AND extraordinario = 1 LIMIT 1",
            [$criterioId]
        );
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
     * ¿Existe al menos un criterio vivo y confirmado en la competencia?
     * Es la base de "resumen accesible": el docente solo llega al resumen si
     * confirmó (clic en "Confirmar", que pasó por el filtro de omisión) y no
     * rompió luego la completitud. Lo usan el autosave (sincroniza el botón) y
     * el guard de resumen() — una sola fuente de verdad.
     */
    public function existeConfirmado(int $cargaId, int $competenciaId, int $periodoId): bool
    {
        return (bool) $this->queryOne(
            "SELECT 1 FROM criterios
             WHERE carga_id       = ?
               AND competencia_id = ?
               AND periodo_id     = ?
               AND eliminado_en   IS NULL
               AND confirmado_en  IS NOT NULL
             LIMIT 1",
            [$cargaId, $competenciaId, $periodoId]
        );
    }

    /**
     * ¿La competencia está LISTA para el resumen? Es decir: tiene ≥1 criterio
     * vivo y TODOS están confirmados (ninguno pendiente). Un criterio vacío
     * —que no se puede confirmar— cuenta como pendiente y la deja "no lista".
     *
     * Endurece la regla anterior (bastaba ≥1 confirmado): editar u omitir
     * cualquier criterio lo desconfirma y, hasta re-confirmarlo, el botón
     * "Ver resumen" se deshabilita y la aprobación se rechaza. Garantiza que el
     * resumen y el promedio agregado solo reflejen criterios confirmados.
     */
    public function competenciaListaParaResumen(int $cargaId, int $competenciaId, int $periodoId): bool
    {
        $r = $this->queryOne(
            "SELECT
                 COUNT(*)                              AS total,
                 SUM(confirmado_en IS NULL)            AS pendientes
             FROM criterios
             WHERE carga_id       = ?
               AND competencia_id = ?
               AND periodo_id     = ?
               AND eliminado_en   IS NULL",
            [$cargaId, $competenciaId, $periodoId]
        );

        return (int) ($r['total'] ?? 0) > 0
            && (int) ($r['pendientes'] ?? 0) === 0;
    }

    /**
     * Revierte el sello de confirmación. Se llama ante CUALQUIER cambio en el
     * criterio tras confirmarlo: autosave de una nota (set o blank), registro/
     * cambio de una omisión, o edición del nombre/descripción (renombrar). El
     * criterio deja de estar "confirmado", re-bloquea "Ver resumen", lo saca del
     * promedio agregado y obliga a volver a Confirmar (que re-dispara el filtro
     * de omisión). Inverso de marcarConfirmado.
     */
    public function desconfirmar(int $id): bool
    {
        return $this->execute(
            "UPDATE criterios
             SET confirmado_en  = NULL,
                 confirmado_por = NULL
             WHERE id          = ?
               AND eliminado_en IS NULL",
            [$id]
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