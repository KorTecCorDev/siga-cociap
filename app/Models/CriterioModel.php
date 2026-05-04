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
     * Obtiene los criterios de una carga + competencia + periodo.
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
        string $nombre
    ): int {
        $ultimo = $this->queryOne("
            SELECT MAX(orden) AS ultimo
            FROM criterios
            WHERE carga_id       = ?
              AND competencia_id = ?
              AND periodo_id     = ?
        ", [$cargaId, $competenciaId, $periodoId]);

        $orden = ($ultimo['ultimo'] ?? 0) + 1;

        return $this->create([
            'carga_id'       => $cargaId,
            'competencia_id' => $competenciaId,
            'periodo_id'     => $periodoId,
            'nombre'         => trim($nombre),
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
     * Elimina un criterio solo si no tiene calificaciones.
     */
    public function eliminarSiVacio(int $id): bool
    {
        if ($this->tieneCalificaciones($id)) {
            return false;
        }
        return $this->delete($id);
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
                c.orden
            FROM competencias c
            WHERE c.id IN (
                SELECT comp.id
                FROM competencias comp
                INNER JOIN cargas_academicas ca
                    ON ca.subarea_id = comp.subarea_id
                WHERE ca.id = ?
                  AND comp.subarea_id IS NOT NULL
                UNION
                SELECT comp.id
                FROM competencias comp
                INNER JOIN cargas_academicas ca
                    ON ca.area_id = comp.area_id
                WHERE ca.id = ?
                  AND comp.area_id IS NOT NULL
            )
            ORDER BY c.orden
        ", [$cargaId, $cargaId]);

        foreach ($competencias as &$competencia) {
            $competencia['criterios'] = $this->getCriterios(
                $cargaId,
                $competencia['id'],
                $periodoId
            );
        }

        return $competencias;
    }
}