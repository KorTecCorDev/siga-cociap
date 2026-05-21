<?php

namespace App\Models;

use Core\Session;

class BoletaPublicaModel extends BaseModel
{
    protected string $table = 'boletas_publicas';

    // Sin O, 0, I, 1, L para evitar confusión visual
    private const ALFAS = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Genera un código único con formato COCIAP-{anio}-B{bimestre}-XXXXXX.
     */
    public function generarCodigo(int $anio, int $numBimestre): string
    {
        do {
            $rand = '';
            for ($i = 0; $i < 6; $i++) {
                $rand .= self::ALFAS[random_int(0, strlen(self::ALFAS) - 1)];
            }
            $codigo = "COCIAP-{$anio}-B{$numBimestre}-{$rand}";
            $existe  = $this->findBy('codigo_acceso', $codigo);
        } while ($existe);

        return $codigo;
    }

    /**
     * Genera boletas para todas las matrículas aprobadas con ≥1 competencia
     * bloqueada en el periodo. Usa INSERT IGNORE para no duplicar.
     * Retorna el número de boletas nuevas generadas.
     */
    public function generarMasivo(int $periodoId, int $usuarioId): int
    {
        $periodo = $this->queryOne("
            SELECT p.numero, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
            LIMIT 1
        ", [$periodoId]);

        if (!$periodo) return 0;

        $matriculas = $this->query("
            SELECT DISTINCT m.id AS matricula_id
            FROM matriculas m
            INNER JOIN calificaciones cal
                ON cal.matricula_id = m.id AND cal.periodo_id = ?
            INNER JOIN bloqueos_competencia bc
                ON bc.carga_id       = cal.carga_id
               AND bc.competencia_id = cal.competencia_id
               AND bc.periodo_id     = cal.periodo_id
            WHERE m.estado = 'aprobada'
        ", [$periodoId]);

        $insertadas = 0;
        foreach ($matriculas as $mat) {
            $existe = $this->queryOne(
                "SELECT id FROM boletas_publicas WHERE matricula_id = ? AND periodo_id = ?",
                [$mat['matricula_id'], $periodoId]
            );
            if ($existe) continue;

            $codigo = $this->generarCodigo((int) $periodo['anio'], (int) $periodo['numero']);
            $this->execute(
                "INSERT INTO boletas_publicas (matricula_id, periodo_id, codigo_acceso, generada_por)
                 VALUES (?, ?, ?, ?)",
                [$mat['matricula_id'], $periodoId, $codigo, $usuarioId]
            );
            $insertadas++;
        }

        return $insertadas;
    }

    /**
     * Lista boletas de un periodo con datos del estudiante, grado y sección.
     * Incluye novedades_count: competencias bloqueadas DESPUÉS de que se generó la boleta.
     */
    public function getPorPeriodo(int $periodoId): array
    {
        return $this->query("
            SELECT
                bp.id,
                bp.matricula_id,
                bp.codigo_acceso,
                bp.veces_consultada,
                bp.ultima_consulta,
                bp.generada_en,
                CONCAT(
                    per.apellido_paterno, ' ',
                    per.apellido_materno, ', ',
                    per.nombres
                )                AS nombre_completo,
                g.nombre_display AS grado_nombre,
                s.nombre         AS seccion_nombre,
                (
                    SELECT COUNT(*)
                    FROM calificaciones cal
                    INNER JOIN bloqueos_competencia bc
                        ON  bc.carga_id       = cal.carga_id
                        AND bc.competencia_id = cal.competencia_id
                        AND bc.periodo_id     = cal.periodo_id
                    WHERE cal.matricula_id = bp.matricula_id
                      AND cal.periodo_id   = bp.periodo_id
                      AND bc.bloqueado_en  > bp.generada_en
                )                AS novedades_count
            FROM boletas_publicas bp
            INNER JOIN matriculas m  ON m.id   = bp.matricula_id
            INNER JOIN estudiantes e ON e.id   = m.estudiante_id
            INNER JOIN personas per  ON per.id = e.persona_id
            INNER JOIN secciones s   ON s.id   = m.seccion_id
            INNER JOIN grados g      ON g.id   = s.grado_id
            WHERE bp.periodo_id = ?
            ORDER BY g.id, s.nombre, per.apellido_paterno, per.apellido_materno, per.nombres
        ", [$periodoId]);
    }

    /**
     * Actualiza generada_en a NOW() para las boletas que tienen competencias
     * bloqueadas DESPUÉS de su fecha de generación.
     * Retorna el número de boletas actualizadas.
     */
    public function actualizarTimestamps(int $periodoId, int $usuarioId): int
    {
        // Contar cuántas serán actualizadas
        $row = $this->queryOne("
            SELECT COUNT(*) AS total
            FROM boletas_publicas bp
            WHERE bp.periodo_id = ?
              AND EXISTS (
                  SELECT 1
                  FROM calificaciones cal
                  INNER JOIN bloqueos_competencia bc
                      ON  bc.carga_id       = cal.carga_id
                      AND bc.competencia_id = cal.competencia_id
                      AND bc.periodo_id     = cal.periodo_id
                  WHERE cal.matricula_id = bp.matricula_id
                    AND cal.periodo_id   = bp.periodo_id
                    AND bc.bloqueado_en  > bp.generada_en
              )
        ", [$periodoId]);

        $total = (int) ($row['total'] ?? 0);
        if ($total === 0) return 0;

        $this->execute("
            UPDATE boletas_publicas bp
            SET bp.generada_en  = NOW(),
                bp.generada_por = ?
            WHERE bp.periodo_id = ?
              AND EXISTS (
                  SELECT 1
                  FROM calificaciones cal2
                  INNER JOIN bloqueos_competencia bc2
                      ON  bc2.carga_id       = cal2.carga_id
                      AND bc2.competencia_id = cal2.competencia_id
                      AND bc2.periodo_id     = cal2.periodo_id
                  WHERE cal2.matricula_id = bp.matricula_id
                    AND cal2.periodo_id   = bp.periodo_id
                    AND bc2.bloqueado_en  > bp.generada_en
              )
        ", [$usuarioId, $periodoId]);

        return $total;
    }

    /**
     * Busca por código; si existe incrementa el contador de consultas.
     * Retorna el registro completo (matricula_id + periodo_id para getBoletaAlumno).
     */
    public function getPorCodigo(string $codigo): ?array
    {
        $registro = $this->queryOne(
            "SELECT * FROM boletas_publicas WHERE codigo_acceso = ? LIMIT 1",
            [$codigo]
        );

        if (!$registro) return null;

        $this->execute(
            "UPDATE boletas_publicas
             SET veces_consultada = veces_consultada + 1,
                 ultima_consulta  = NOW()
             WHERE id = ?",
            [$registro['id']]
        );

        return $registro;
    }
}
