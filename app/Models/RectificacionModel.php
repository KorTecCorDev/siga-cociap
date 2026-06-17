<?php

namespace App\Models;

/**
 * RectificacionModel
 * Dueño de la tabla de auditoría `rectificaciones_calificacion` y de las
 * lecturas que alimentan el módulo de Rectificación de calificaciones.
 *
 * El módulo ORQUESTA y AUDITA; la escritura real de notas la hace
 * CalificacionModel (criterios → promedio → conclusión). Aquí solo se
 * descubre QUÉ es rectificable, se valida el estado y se registra la traza.
 *
 * Estado RECTIFICABLE: una competencia de la matrícula que ya salió del
 * flujo normal del docente, es decir, periodo CERRADO o competencia
 * BLOQUEADA. Si está abierta y desbloqueada NO se rectifica aquí: se corrige
 * por el flujo del docente.
 */
class RectificacionModel extends BaseModel
{
    protected string $table = 'rectificaciones_calificacion';

    /**
     * Datos de la matrícula objetivo (estudiante + ubicación + nivel).
     * Incluye `nivel_codigo` ('prim'/'sec') para resolver la obligatoriedad
     * de la conclusión descriptiva.
     */
    public function getMatriculaInfo(int $matriculaId): ?array
    {
        return $this->queryOne("
            SELECT
                m.id            AS matricula_id,
                m.estado,
                m.tipo,
                m.anio_id,
                p.dni,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres) AS nombre_completo,
                s.id            AS seccion_id,
                s.nombre        AS seccion_nombre,
                g.id            AS grado_id,
                g.nombre_display AS grado_nombre,
                g.numero        AS grado_numero,
                n.id            AS nivel_id,
                n.nombre        AS nivel_nombre,
                n.codigo        AS nivel_codigo
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            INNER JOIN secciones s   ON s.id = m.seccion_id
            INNER JOIN grados g      ON g.id = s.grado_id
            INNER JOIN niveles n     ON n.id = g.nivel_id
            WHERE m.id = ?
            LIMIT 1
        ", [$matriculaId]);
    }

    /**
     * Competencias RECTIFICABLES de una matrícula: tienen calificación
     * registrada en un periodo cerrado y/o con la competencia bloqueada.
     * Devuelve filas con metadatos de área/competencia/periodo + flags.
     * `tiene_criterios` permite a la UI distinguir las que se pueden
     * rectificar por criterio de las que solo tienen nota final.
     */
    public function getCompetenciasRectificables(int $matriculaId): array
    {
        return $this->query("
            SELECT
                cal.periodo_id,
                per.numero            AS periodo_numero,
                per.nombre_display    AS periodo_nombre,
                per.estado            AS periodo_estado,
                cal.carga_id,
                comp.id               AS competencia_id,
                comp.nombre_completo  AS competencia_nombre,
                comp.nombre_corto,
                comp.codigo_minedu,
                a.id                  AS area_id,
                a.nombre              AS area_nombre,
                a.nombre_boleta,
                a.tipo                AS area_tipo,
                sa.nombre             AS subarea_nombre,
                cal.nota_numerica,
                (bc.competencia_id IS NOT NULL) AS bloqueada,
                (SELECT COUNT(*) FROM criterios cr
                  WHERE cr.carga_id       = cal.carga_id
                    AND cr.competencia_id = cal.competencia_id
                    AND cr.periodo_id     = cal.periodo_id
                    AND cr.eliminado_en   IS NULL) AS tiene_criterios
            FROM calificaciones cal
            INNER JOIN periodos per         ON per.id  = cal.periodo_id
            INNER JOIN cargas_academicas ca ON ca.id   = cal.carga_id
            INNER JOIN competencias comp    ON comp.id = cal.competencia_id
            LEFT  JOIN subareas sa          ON sa.id   = ca.subarea_id
            LEFT  JOIN areas a              ON a.id    = COALESCE(ca.area_id, sa.area_id)
            LEFT  JOIN bloqueos_competencia bc
                   ON bc.carga_id       = cal.carga_id
                  AND bc.competencia_id = cal.competencia_id
                  AND bc.periodo_id     = cal.periodo_id
            WHERE cal.matricula_id = ?
              AND (per.estado = 'cerrado' OR bc.competencia_id IS NOT NULL)
            ORDER BY per.numero, a.orden, comp.orden
        ", [$matriculaId]);
    }

    /**
     * Detalle de UNA competencia para el formulario de rectificación:
     *   - 'meta'      : encabezado (área, competencia, periodo, nota/conclusión
     *                   actuales, flags de estado).
     *   - 'criterios' : criterios activos con la nota actual de ESTA matrícula.
     * Devuelve null si la matrícula no tiene calificación en esa competencia.
     */
    public function getDetalleCompetencia(
        int $matriculaId,
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): ?array {
        $meta = $this->queryOne("
            SELECT
                cal.nota_numerica         AS nota_actual,
                cal.conclusion_descriptiva AS conclusion_actual,
                per.numero                AS periodo_numero,
                per.nombre_display        AS periodo_nombre,
                per.estado                AS periodo_estado,
                comp.nombre_completo      AS competencia_nombre,
                comp.nombre_corto,
                comp.codigo_minedu,
                a.nombre                  AS area_nombre,
                a.nombre_boleta,
                a.tipo                    AS area_tipo,
                sa.nombre                 AS subarea_nombre,
                (bc.competencia_id IS NOT NULL) AS bloqueada
            FROM calificaciones cal
            INNER JOIN periodos per         ON per.id  = cal.periodo_id
            INNER JOIN cargas_academicas ca ON ca.id   = cal.carga_id
            INNER JOIN competencias comp    ON comp.id = cal.competencia_id
            LEFT  JOIN subareas sa          ON sa.id   = ca.subarea_id
            LEFT  JOIN areas a              ON a.id    = COALESCE(ca.area_id, sa.area_id)
            LEFT  JOIN bloqueos_competencia bc
                   ON bc.carga_id       = cal.carga_id
                  AND bc.competencia_id = cal.competencia_id
                  AND bc.periodo_id     = cal.periodo_id
            WHERE cal.matricula_id   = ?
              AND cal.carga_id       = ?
              AND cal.competencia_id = ?
              AND cal.periodo_id     = ?
            LIMIT 1
        ", [$matriculaId, $cargaId, $competenciaId, $periodoId]);

        if (!$meta) {
            return null;
        }

        $criterios = $this->query("
            SELECT
                cr.id,
                cr.nombre,
                cr.descripcion,
                cr.orden,
                cc.nota
            FROM criterios cr
            LEFT JOIN calificaciones_criterio cc
                   ON cc.criterio_id  = cr.id
                  AND cc.matricula_id = ?
            WHERE cr.carga_id       = ?
              AND cr.competencia_id = ?
              AND cr.periodo_id     = ?
              AND cr.eliminado_en   IS NULL
            ORDER BY cr.orden, cr.id
        ", [$matriculaId, $cargaId, $competenciaId, $periodoId]);

        return ['meta' => $meta, 'criterios' => $criterios];
    }

    /**
     * ¿La competencia de la matrícula está en estado RECTIFICABLE?
     * (periodo cerrado o competencia bloqueada, y con calificación existente).
     * Es el invariante de seguridad del módulo: sin esto, no se rectifica.
     */
    public function esRectificable(
        int $matriculaId,
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): bool {
        $fila = $this->queryOne("
            SELECT 1
            FROM calificaciones cal
            INNER JOIN periodos per ON per.id = cal.periodo_id
            LEFT  JOIN bloqueos_competencia bc
                   ON bc.carga_id       = cal.carga_id
                  AND bc.competencia_id = cal.competencia_id
                  AND bc.periodo_id     = cal.periodo_id
            WHERE cal.matricula_id   = ?
              AND cal.carga_id       = ?
              AND cal.competencia_id = ?
              AND cal.periodo_id     = ?
              AND (per.estado = 'cerrado' OR bc.competencia_id IS NOT NULL)
            LIMIT 1
        ", [$matriculaId, $cargaId, $competenciaId, $periodoId]);

        return $fila !== null;
    }

    /** Registra una fila de auditoría de rectificación. Retorna el ID nuevo. */
    public function registrar(array $data): int
    {
        return $this->create([
            'matricula_id'        => $data['matricula_id'],
            'carga_id'            => $data['carga_id'],
            'periodo_id'          => $data['periodo_id'],
            'competencia_id'      => $data['competencia_id'],
            'nota_anterior'       => $data['nota_anterior'],
            'nota_nueva'          => $data['nota_nueva'],
            'conclusion_anterior' => $data['conclusion_anterior'],
            'conclusion_nueva'    => $data['conclusion_nueva'],
            'motivo'              => $data['motivo'],
            'rectificado_por'     => $data['rectificado_por'],
        ]);
    }

    /**
     * Historial de rectificaciones (más recientes primero), enriquecido con
     * el nombre del estudiante, la competencia y quién rectificó. Si se pasa
     * $matriculaId, filtra solo las de esa matrícula.
     */
    public function getHistorial(int $limite = 50, ?int $matriculaId = null): array
    {
        $where  = '';
        $params = [];
        if ($matriculaId !== null) {
            $where = 'WHERE r.matricula_id = ?';
            $params[] = $matriculaId;
        }
        // LIMIT se interpola como entero (cast seguro): MySQL no admite bindear
        // el parámetro de LIMIT con emulación de prepares desactivada.
        $limite = max(1, min(500, $limite));

        return $this->query("
            SELECT
                r.*,
                CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres) AS estudiante,
                comp.nombre_corto AS competencia_nombre,
                per.nombre_display AS periodo_nombre,
                CONCAT(pu.apellido_paterno, ' ', pu.nombres) AS rectificador
            FROM rectificaciones_calificacion r
            INNER JOIN matriculas m   ON m.id  = r.matricula_id
            INNER JOIN estudiantes e  ON e.id  = m.estudiante_id
            INNER JOIN personas p     ON p.id  = e.persona_id
            INNER JOIN competencias comp ON comp.id = r.competencia_id
            INNER JOIN periodos per   ON per.id = r.periodo_id
            LEFT  JOIN usuarios u     ON u.id  = r.rectificado_por
            LEFT  JOIN personas pu    ON pu.id = u.persona_id
            {$where}
            ORDER BY r.rectificado_en DESC, r.id DESC
            LIMIT {$limite}
        ", $params);
    }
}
