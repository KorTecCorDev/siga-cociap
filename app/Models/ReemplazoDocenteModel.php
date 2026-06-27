<?php

namespace App\Models;

/**
 * ReemplazoDocenteModel
 * Cambio oficial del docente de una carga ACTIVA con auditoría por snapshot.
 *
 * El reemplazo NO desactiva ni duplica la carga: solo cambia `docente_id` (en la
 * carga y en sus sesiones de horario); el entrante hereda criterios, notas y
 * conclusiones y continúa en vivo. El trabajo del saliente se CONGELA en un
 * snapshot JSON (todos los bimestres) como archivo lateral de solo lectura. No
 * toca boleta, orden de mérito ni cierre (esos leen las tablas vivas).
 */
class ReemplazoDocenteModel extends BaseModel
{
    protected string $table = 'reemplazos_docente';

    /**
     * Ejecuta el reemplazo en una transacción:
     *   1. Congela el snapshot del trabajo ACTUAL (del saliente).
     *   2. UPDATE cargas_academicas.docente_id (la carga sigue 'activa').
     *   3. UPDATE sesiones_horario.docente_id (horario denormalizado).
     *   4. INSERT del evento + INSERT del snapshot.
     * Retorna el id del evento de reemplazo.
     *
     * @throws \RuntimeException si la carga no existe o el entrante == saliente.
     */
    public function reemplazar(int $cargaId, int $entranteId, string $motivo, int $userId): int
    {
        $carga = $this->queryOne(
            "SELECT id, docente_id FROM cargas_academicas WHERE id = ? LIMIT 1",
            [$cargaId]
        );
        if (!$carga) {
            throw new \RuntimeException('Carga no encontrada.');
        }

        $salienteId = (int) $carga['docente_id'];
        if ($entranteId === $salienteId) {
            throw new \RuntimeException('El docente entrante es el mismo que el actual.');
        }

        $entrante = $this->queryOne("
            SELECT u.id
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.id = ? AND r.codigo = 'docente' AND u.estado = 'activo'
            LIMIT 1
        ", [$entranteId]);
        if (!$entrante) {
            throw new \RuntimeException('El docente entrante no es válido o está inactivo.');
        }

        $periodoActivo = $this->queryOne(
            "SELECT id FROM periodos WHERE estado = 'activo' LIMIT 1"
        );
        $periodoId = $periodoActivo ? (int) $periodoActivo['id'] : null;

        $snapshot = $this->construirSnapshot($cargaId, $salienteId);
        $json     = json_encode($snapshot, JSON_UNESCAPED_UNICODE);

        $this->beginTransaction();
        try {
            $this->execute(
                "UPDATE cargas_academicas SET docente_id = ? WHERE id = ?",
                [$entranteId, $cargaId]
            );
            $this->execute(
                "UPDATE sesiones_horario SET docente_id = ? WHERE carga_id = ?",
                [$entranteId, $cargaId]
            );

            $this->execute("
                INSERT INTO reemplazos_docente
                    (carga_id, periodo_id, docente_saliente_id, docente_entrante_id, motivo, reasignado_por)
                VALUES (?, ?, ?, ?, ?, ?)
            ", [$cargaId, $periodoId, $salienteId, $entranteId, trim($motivo), $userId]);
            $reemplazoId = (int) $this->db->lastInsertId();

            $this->execute(
                "INSERT INTO reemplazos_snapshot (reemplazo_id, contenido) VALUES (?, ?)",
                [$reemplazoId, $json]
            );

            $this->commit();
            return $reemplazoId;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Foto solo-lectura del trabajo de una carga en TODOS los bimestres:
     * criterios (incl. eliminados, con sus notas por alumno), calificaciones
     * agregadas (nota + conclusión por alumno) y bloqueos/aprobaciones. Es PURA
     * (no escribe). El `docente_saliente_id` se guarda como contexto del autor.
     */
    public function construirSnapshot(int $cargaId, int $salienteId): array
    {
        $criterios = $this->query("
            SELECT id, competencia_id, periodo_id, nombre, descripcion, orden,
                   confirmado_en, eliminado_en
            FROM criterios
            WHERE carga_id = ?
            ORDER BY periodo_id, competencia_id, orden, id
        ", [$cargaId]);

        $notas = $this->query("
            SELECT cc.criterio_id, cc.matricula_id, cc.nota, cc.modificado_en
            FROM calificaciones_criterio cc
            INNER JOIN criterios cr ON cr.id = cc.criterio_id
            WHERE cr.carga_id = ?
            ORDER BY cc.criterio_id, cc.matricula_id
        ", [$cargaId]);

        $notasPorCriterio = [];
        foreach ($notas as $n) {
            $notasPorCriterio[(int) $n['criterio_id']][] = [
                'matricula_id' => (int) $n['matricula_id'],
                'nota'         => $n['nota'] === null ? null : (int) $n['nota'],
                'modificado_en' => $n['modificado_en'],
            ];
        }
        foreach ($criterios as &$cr) {
            $cr['notas'] = $notasPorCriterio[(int) $cr['id']] ?? [];
        }
        unset($cr);

        $calificaciones = $this->query("
            SELECT periodo_id, competencia_id, matricula_id,
                   nota_numerica, conclusion_descriptiva, modificado_en
            FROM calificaciones
            WHERE carga_id = ?
            ORDER BY periodo_id, competencia_id, matricula_id
        ", [$cargaId]);

        $bloqueos = $this->query("
            SELECT competencia_id, periodo_id, origen, bloqueado_por, bloqueado_en
            FROM bloqueos_competencia
            WHERE carga_id = ?
            ORDER BY periodo_id, competencia_id
        ", [$cargaId]);

        return [
            'version'             => 1,
            'carga_id'            => $cargaId,
            'docente_saliente_id' => $salienteId,
            'generado_en'         => date('c'),
            'criterios'           => $criterios,
            'calificaciones'      => $calificaciones,
            'bloqueos'            => $bloqueos,
        ];
    }

    /** Historial de reemplazos de una carga (más reciente primero), con nombres. */
    public function getHistorialPorCarga(int $cargaId): array
    {
        return $this->query("
            SELECT
                rd.id,
                rd.motivo,
                rd.reasignado_en,
                pr.nombre_display AS periodo_nombre,
                CONCAT(ps.apellido_paterno, ' ', ps.apellido_materno, ', ', ps.nombres) AS saliente_nombre,
                CONCAT(pe.apellido_paterno, ' ', pe.apellido_materno, ', ', pe.nombres) AS entrante_nombre,
                CONCAT(pu.apellido_paterno, ' ', pu.nombres) AS reasignado_por_nombre
            FROM reemplazos_docente rd
            INNER JOIN usuarios us ON us.id = rd.docente_saliente_id
            INNER JOIN personas ps ON ps.id = us.persona_id
            INNER JOIN usuarios ue ON ue.id = rd.docente_entrante_id
            INNER JOIN personas pe ON pe.id = ue.persona_id
            INNER JOIN usuarios uu ON uu.id = rd.reasignado_por
            INNER JOIN personas pu ON pu.id = uu.persona_id
            LEFT  JOIN periodos pr ON pr.id = rd.periodo_id
            WHERE rd.carga_id = ?
            ORDER BY rd.reasignado_en DESC, rd.id DESC
        ", [$cargaId]);
    }

    /** Un evento de reemplazo con su snapshot decodificado (para el reporte). */
    public function getSnapshot(int $reemplazoId): ?array
    {
        $evento = $this->queryOne("
            SELECT
                rd.*,
                pr.nombre_display AS periodo_nombre,
                CONCAT(ps.apellido_paterno, ' ', ps.apellido_materno, ', ', ps.nombres) AS saliente_nombre,
                CONCAT(pe.apellido_paterno, ' ', pe.apellido_materno, ', ', pe.nombres) AS entrante_nombre,
                CONCAT(pu.apellido_paterno, ' ', pu.nombres) AS reasignado_por_nombre,
                rs.contenido,
                rs.creado_en AS snapshot_creado_en
            FROM reemplazos_docente rd
            INNER JOIN usuarios us ON us.id = rd.docente_saliente_id
            INNER JOIN personas ps ON ps.id = us.persona_id
            INNER JOIN usuarios ue ON ue.id = rd.docente_entrante_id
            INNER JOIN personas pe ON pe.id = ue.persona_id
            INNER JOIN usuarios uu ON uu.id = rd.reasignado_por
            INNER JOIN personas pu ON pu.id = uu.persona_id
            LEFT  JOIN periodos pr           ON pr.id = rd.periodo_id
            LEFT  JOIN reemplazos_snapshot rs ON rs.reemplazo_id = rd.id
            WHERE rd.id = ?
            LIMIT 1
        ", [$reemplazoId]);

        if (!$evento) {
            return null;
        }

        $evento['snapshot'] = $evento['contenido']
            ? json_decode($evento['contenido'], true)
            : null;
        unset($evento['contenido']);

        return $evento;
    }
}
