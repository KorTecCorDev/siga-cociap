<?php

namespace App\Models;

class CurriculumModel extends BaseModel
{
    protected string $table = 'areas';

    public function getNiveles(): array
    {
        return $this->query("SELECT id, nombre FROM niveles ORDER BY id");
    }

    public function getAreasParaSidebar(int $nivelId): array
    {
        return $this->query(
            "SELECT id, nombre, tipo, activa, orden FROM areas WHERE nivel_id = ? ORDER BY orden, nombre",
            [$nivelId]
        );
    }

    public function getAreaDetalle(int $areaId): ?array
    {
        $area = $this->queryOne("
            SELECT a.id, a.nombre, a.nombre_boleta, a.alias_boleta,
                   a.nombre_siagie, a.tipo, a.orden, a.activa, a.nivel_id,
                   n.nombre AS nivel_nombre
            FROM areas a
            INNER JOIN niveles n ON n.id = a.nivel_id
            WHERE a.id = ?
        ", [$areaId]);

        if (!$area) return null;

        if ($area['tipo'] === 'con_subareas') {
            $subareas = $this->query(
                "SELECT id, nombre, orden FROM subareas WHERE area_id = ? ORDER BY orden",
                [$areaId]
            );
            foreach ($subareas as &$sa) {
                $sa['competencias'] = $this->query(
                    "SELECT id, codigo_minedu, nombre_completo, nombre_corto, orden
                     FROM competencias WHERE subarea_id = ? ORDER BY orden",
                    [$sa['id']]
                );
            }
            unset($sa);
            $area['subareas']     = $subareas;
            $area['competencias'] = [];
        } else {
            $area['subareas']     = [];
            $area['competencias'] = $this->query(
                "SELECT id, codigo_minedu, nombre_completo, nombre_corto, orden
                 FROM competencias WHERE area_id = ? ORDER BY orden",
                [$areaId]
            );
        }

        return $area;
    }

    public function actualizarArea(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function actualizarSubarea(int $id, string $nombre, int $orden): bool
    {
        return $this->execute(
            "UPDATE subareas SET nombre = ?, orden = ? WHERE id = ?",
            [$nombre, $orden, $id]
        );
    }

    public function actualizarCompetencia(
        int $id,
        ?string $codigo,
        string $nombreCompleto,
        ?string $nombreCorto,
        int $orden
    ): bool {
        return $this->execute(
            "UPDATE competencias
             SET codigo_minedu = ?, nombre_completo = ?, nombre_corto = ?, orden = ?
             WHERE id = ?",
            [$codigo, $nombreCompleto, $nombreCorto, $orden, $id]
        );
    }

    public function toggleActivaArea(int $id): bool
    {
        return $this->execute(
            "UPDATE areas SET activa = IF(activa = 1, 0, 1) WHERE id = ?",
            [$id]
        );
    }

    public function moverArea(int $id, string $direccion): bool
    {
        $actual = $this->queryOne(
            "SELECT id, nivel_id, orden FROM areas WHERE id = ?",
            [$id]
        );
        if (!$actual) return false;

        if ($direccion === 'up') {
            $vecino = $this->queryOne(
                "SELECT id, orden FROM areas WHERE nivel_id = ? AND orden < ? ORDER BY orden DESC LIMIT 1",
                [$actual['nivel_id'], $actual['orden']]
            );
        } else {
            $vecino = $this->queryOne(
                "SELECT id, orden FROM areas WHERE nivel_id = ? AND orden > ? ORDER BY orden ASC LIMIT 1",
                [$actual['nivel_id'], $actual['orden']]
            );
        }

        if (!$vecino) return false;

        $this->beginTransaction();
        try {
            $this->execute("UPDATE areas SET orden = ? WHERE id = ?", [$vecino['orden'], $actual['id']]);
            $this->execute("UPDATE areas SET orden = ? WHERE id = ?", [$actual['orden'], $vecino['id']]);
            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollback();
            return false;
        }
    }
}
