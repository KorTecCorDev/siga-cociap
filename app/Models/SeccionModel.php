<?php

namespace App\Models;

class SeccionModel extends BaseModel
{
    protected string $table = 'secciones';

    public function listarConTutor(): array
    {
        return $this->query("
            SELECT
                s.id,
                s.nombre          AS seccion_nombre,
                s.es_unidocente,
                s.estado_nomina,
                s.tutor_id,
                g.nombre_display  AS grado_nombre,
                g.numero          AS grado_numero,
                n.nombre          AS nivel_nombre,
                n.id              AS nivel_id,
                a.anio,
                p.apellido_paterno AS tutor_apellido_paterno,
                p.apellido_materno AS tutor_apellido_materno,
                p.nombres          AS tutor_nombres,
                p.dni              AS tutor_dni,
                COUNT(m.id)        AS total_matriculados
            FROM secciones s
            INNER JOIN grados g           ON g.id  = s.grado_id
            INNER JOIN niveles n          ON n.id  = g.nivel_id
            INNER JOIN anios_academicos a ON a.id  = s.anio_id
            LEFT  JOIN usuarios u         ON u.id  = s.tutor_id
            LEFT  JOIN personas p         ON p.id  = u.persona_id
            LEFT  JOIN matriculas m       ON m.seccion_id = s.id AND m.estado = 'aprobada'
            WHERE a.estado IN ('planificado', 'activo')
            GROUP BY
                s.id, s.nombre, s.es_unidocente, s.estado_nomina, s.tutor_id,
                g.nombre_display, g.numero, n.nombre, n.id, a.anio,
                p.apellido_paterno, p.apellido_materno, p.nombres, p.dni
            ORDER BY a.anio DESC, n.id, g.numero, s.nombre
        ");
    }

    public function listarDocentes(): array
    {
        return $this->query("
            SELECT
                u.id,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                p.dni
            FROM usuarios u
            INNER JOIN personas p ON p.id = u.persona_id
            INNER JOIN roles r    ON r.id = u.rol_id
            WHERE r.codigo = 'docente'
              AND u.estado = 'activo'
            ORDER BY p.apellido_paterno, p.apellido_materno, p.nombres
        ");
    }

    /**
     * Asigna (o quita) el tutor de una sección.
     * Gestiona automáticamente la carga transversal en la misma transacción:
     *  - Primera asignación  → INSERT carga transversal activa
     *  - Cambio de tutor     → UPDATE docente_id de la carga existente
     *  - Quitar tutor (null) → marca la carga como inactiva
     */
    public function asignarTutor(int $seccionId, ?int $tutorId): void
    {
        $this->beginTransaction();
        try {
            $seccion = $this->queryOne("
                SELECT s.id, s.anio_id, g.nivel_id
                FROM secciones s
                INNER JOIN grados g ON g.id = s.grado_id
                WHERE s.id = ?
            ", [$seccionId]);

            if (!$seccion) {
                throw new \RuntimeException('Sección no encontrada.');
            }

            $areaTransversal = $this->queryOne("
                SELECT id FROM areas
                WHERE nivel_id = ? AND tipo = 'transversal'
                LIMIT 1
            ", [(int) $seccion['nivel_id']]);

            if (!$areaTransversal) {
                throw new \RuntimeException('No existe área transversal para este nivel.');
            }

            $areaId = (int) $areaTransversal['id'];

            $this->execute(
                "UPDATE secciones SET tutor_id = ? WHERE id = ?",
                [$tutorId, $seccionId]
            );

            $cargaExistente = $this->queryOne("
                SELECT id FROM cargas_academicas
                WHERE seccion_id = ? AND area_id = ? AND anio_id = ?
                LIMIT 1
            ", [$seccionId, $areaId, (int) $seccion['anio_id']]);

            if ($tutorId === null) {
                if ($cargaExistente) {
                    $this->execute(
                        "UPDATE cargas_academicas SET estado = 'inactiva' WHERE id = ?",
                        [(int) $cargaExistente['id']]
                    );
                }
            } elseif ($cargaExistente) {
                $this->execute(
                    "UPDATE cargas_academicas SET docente_id = ?, estado = 'activa' WHERE id = ?",
                    [$tutorId, (int) $cargaExistente['id']]
                );
            } else {
                $this->execute("
                    INSERT INTO cargas_academicas
                        (docente_id, seccion_id, anio_id, area_id, horas_semanales, estado)
                    VALUES (?, ?, ?, ?, 0, 'activa')
                ", [$tutorId, $seccionId, (int) $seccion['anio_id'], $areaId]);
            }

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
