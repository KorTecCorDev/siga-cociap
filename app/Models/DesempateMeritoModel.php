<?php

namespace App\Models;

/**
 * DesempateMeritoModel
 * Resolucion manual de empates irreducibles del orden de merito.
 *
 * Un empate es irreducible cuando la cascada automatica (promedio exacto ->
 * menos C -> menos B -> mas AD) no separa a los alumnos, o cuando tienen distinto
 * numero de competencias (exoneraciones). Ahi el puesto lo designa una persona.
 *
 * La resolucion se ancla al CONJUNTO de matriculas empatadas + periodo via
 * `grupo_clave` (CSV ordenado de matricula_id), para que la misma decision sirva
 * en el ranking general y en el ranking por seccion sin resolverse dos veces.
 */
class DesempateMeritoModel extends BaseModel
{
    protected string $table = 'desempates_merito';

    /**
     * Clave canonica de un grupo empatado: matricula_id ordenados, separados por coma.
     * Asi el mismo conjunto produce siempre la misma clave sin importar el orden de entrada.
     */
    public static function claveGrupo(array $matriculaIds): string
    {
        $ids = array_map('intval', $matriculaIds);
        sort($ids, SORT_NUMERIC);
        return implode(',', $ids);
    }

    /**
     * Devuelve la resolucion que cubre a un grupo empatado, o null si aun no se resolvio.
     * Retorna: ['id', 'motivo', 'resuelto_por', 'resuelto_en',
     *           'orden' => [matricula_id => orden_manual]].
     *
     * Busca un desempate del periodo cuyo detalle CONTENGA todas las matriculas pedidas
     * (coincidencia exacta o superconjunto). Asi una resolucion del ranking general
     * sobre {A,B,C} tambien ordena al subgrupo {A,B} del ranking por seccion, sin
     * resolver el mismo empate dos veces con resultados contradictorios. El orden_manual
     * es un orden total; restringirlo a un subconjunto preserva el orden relativo.
     */
    public function getResolucion(int $periodoId, array $matriculaIds): ?array
    {
        $ids = array_values(array_unique(array_map('intval', $matriculaIds)));
        $n   = count($ids);
        if ($n < 2) {
            return null;
        }

        $placeholders = implode(',', array_fill(0, $n, '?'));

        // Desempate del periodo que contenga TODAS las matriculas pedidas.
        $row = $this->queryOne("
            SELECT dmo.desempate_id
            FROM desempates_merito_orden dmo
            INNER JOIN desempates_merito dm ON dm.id = dmo.desempate_id
            WHERE dm.periodo_id = ?
              AND dmo.matricula_id IN ($placeholders)
            GROUP BY dmo.desempate_id
            HAVING COUNT(DISTINCT dmo.matricula_id) = ?
            ORDER BY dmo.desempate_id DESC
            LIMIT 1
        ", array_merge([$periodoId], $ids, [$n]));

        if (!$row) {
            return null;
        }

        $desempateId = (int) $row['desempate_id'];

        $cabecera = $this->queryOne("
            SELECT id, motivo, resuelto_por, resuelto_en
            FROM desempates_merito WHERE id = ?
        ", [$desempateId]);

        // Orden restringido a las matriculas pedidas (preserva el orden relativo).
        $detalle = $this->query("
            SELECT matricula_id, orden_manual
            FROM desempates_merito_orden
            WHERE desempate_id = ?
              AND matricula_id IN ($placeholders)
            ORDER BY orden_manual
        ", array_merge([$desempateId], $ids));

        $orden = [];
        foreach ($detalle as $fila) {
            $orden[(int) $fila['matricula_id']] = (int) $fila['orden_manual'];
        }

        if (count($orden) !== $n) {
            return null;
        }

        $cabecera['orden'] = $orden;
        return $cabecera;
    }

    /**
     * Guarda (o reemplaza) la resolucion de un grupo empatado.
     * $ordenMatriculas: matricula_id en el orden designado (indice 0 = primer puesto).
     * Idempotente por (periodo_id, grupo_clave): re-resolver reemplaza el detalle y
     * actualiza quien/cuando/motivo -> permite el override de Admin sobre Registro.
     */
    public function guardar(
        int $periodoId,
        int $gradoId,
        array $ordenMatriculas,
        int $usuarioId,
        string $motivo
    ): int {
        $clave = self::claveGrupo($ordenMatriculas);

        $this->beginTransaction();
        try {
            $existente = $this->queryOne("
                SELECT id FROM desempates_merito
                WHERE periodo_id = ? AND grupo_clave = ?
                LIMIT 1
            ", [$periodoId, $clave]);

            if ($existente) {
                $desempateId = (int) $existente['id'];
                $this->execute("
                    UPDATE desempates_merito
                    SET grado_id = ?, motivo = ?, resuelto_por = ?, resuelto_en = NOW()
                    WHERE id = ?
                ", [$gradoId, $motivo, $usuarioId, $desempateId]);
                $this->execute(
                    "DELETE FROM desempates_merito_orden WHERE desempate_id = ?",
                    [$desempateId]
                );
            } else {
                $this->execute("
                    INSERT INTO desempates_merito
                        (periodo_id, grado_id, grupo_clave, motivo, resuelto_por)
                    VALUES (?, ?, ?, ?, ?)
                ", [$periodoId, $gradoId, $clave, $motivo, $usuarioId]);
                $desempateId = (int) $this->db->lastInsertId();
            }

            $orden = 1;
            foreach ($ordenMatriculas as $matriculaId) {
                $this->execute("
                    INSERT INTO desempates_merito_orden
                        (desempate_id, matricula_id, orden_manual)
                    VALUES (?, ?, ?)
                ", [$desempateId, (int) $matriculaId, $orden]);
                $orden++;
            }

            $this->commit();
            return $desempateId;
        } catch (\Exception $e) {
            $this->rollback();
            log_error('Error guardando desempate de merito', [
                'periodo_id' => $periodoId,
                'grado_id'   => $gradoId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Lista las resoluciones de un periodo (para auditoria/listado).
     */
    public function getResolucionesPorPeriodo(int $periodoId): array
    {
        return $this->query("
            SELECT
                dm.id, dm.grado_id, dm.grupo_clave, dm.motivo,
                dm.resuelto_en,
                p.apellido_paterno, p.apellido_materno, p.nombres
            FROM desempates_merito dm
            INNER JOIN usuarios u ON u.id = dm.resuelto_por
            INNER JOIN personas p ON p.id = u.persona_id
            WHERE dm.periodo_id = ?
            ORDER BY dm.resuelto_en DESC
        ", [$periodoId]);
    }
}
