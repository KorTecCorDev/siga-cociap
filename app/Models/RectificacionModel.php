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

    /**
     * Competencias INSERTABLES de una matrícula: el alumno NO tiene fila en
     * `calificaciones` y la competencia ya salió del flujo del docente
     * (periodo cerrado y/o bloqueada) → candidatas a CALIFICACIÓN
     * EXTRAORDINARIA por RA (migración 042). Cubre los dos casos: alumno
     * suelto sin promedio (omisiones con motivo) y competencia entera
     * "No se evaluó" (`notas_seccion = 0`).
     *
     * Se excluyen: transversales (su flujo es la agregación + cierre del
     * tutor; una fila cruda no llega a boleta), cargas inactivas y alumnos
     * EXONERADOS del área/subárea (candado nota+EXO). DISTINCT por si
     * existieran cargas duplicadas (la tabla no tiene UNIQUE KEY).
     */
    public function getCompetenciasInsertables(int $matriculaId): array
    {
        return $this->query("
            SELECT DISTINCT
                per.id                AS periodo_id,
                per.numero            AS periodo_numero,
                per.nombre_display    AS periodo_nombre,
                per.estado            AS periodo_estado,
                ca.id                 AS carga_id,
                c.id                  AS competencia_id,
                c.nombre_completo     AS competencia_nombre,
                c.nombre_corto,
                c.codigo_minedu,
                a.id                  AS area_id,
                a.nombre              AS area_nombre,
                a.nombre_boleta,
                a.tipo                AS area_tipo,
                sa.nombre             AS subarea_nombre,
                (bc.competencia_id IS NOT NULL) AS bloqueada,
                (SELECT COUNT(*) FROM calificaciones cal2
                  WHERE cal2.carga_id       = ca.id
                    AND cal2.competencia_id = c.id
                    AND cal2.periodo_id     = per.id) AS notas_seccion
            FROM matriculas m
            INNER JOIN cargas_academicas ca ON ca.seccion_id = m.seccion_id
                                           AND ca.estado     = 'activa'
            INNER JOIN competencias c
                    ON (c.subarea_id IS NOT NULL AND c.subarea_id = ca.subarea_id)
                    OR (c.area_id    IS NOT NULL AND c.area_id    = ca.area_id)
            LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
            LEFT  JOIN areas a     ON a.id  = COALESCE(ca.area_id, sa.area_id)
            INNER JOIN periodos per ON per.anio_id = m.anio_id
            LEFT  JOIN bloqueos_competencia bc
                    ON bc.carga_id       = ca.id
                   AND bc.competencia_id = c.id
                   AND bc.periodo_id     = per.id
            WHERE m.id = ?
              AND a.tipo <> 'transversal'
              AND (per.estado = 'cerrado' OR bc.competencia_id IS NOT NULL)
              AND NOT EXISTS (
                  SELECT 1 FROM calificaciones cal
                  WHERE cal.matricula_id   = m.id
                    AND cal.carga_id       = ca.id
                    AND cal.competencia_id = c.id
                    AND cal.periodo_id     = per.id
              )
              AND NOT EXISTS (
                  SELECT 1 FROM exoneraciones exo
                  WHERE exo.matricula_id = m.id
                    AND exo.anio_id      = m.anio_id
                    AND exo.revocado_en  IS NULL
                    AND (
                        (exo.area_id    IS NOT NULL AND exo.area_id    = a.id)
                        OR (exo.subarea_id IS NOT NULL AND exo.subarea_id = ca.subarea_id)
                    )
              )
            ORDER BY per.numero, a.orden, c.orden
        ", [$matriculaId]);
    }

    /**
     * ¿La tupla es INSERTABLE como calificación extraordinaria? Mismas
     * condiciones que getCompetenciasInsertables, para UNA combinación.
     * Es el invariante de seguridad del alta: sin esto, no se inserta.
     */
    public function esInsertable(
        int $matriculaId,
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): bool {
        $fila = $this->queryOne("
            SELECT 1
            FROM matriculas m
            INNER JOIN cargas_academicas ca ON ca.id = ?
                                           AND ca.seccion_id = m.seccion_id
                                           AND ca.estado     = 'activa'
            INNER JOIN competencias c
                    ON c.id = ?
                   AND (
                       (c.subarea_id IS NOT NULL AND c.subarea_id = ca.subarea_id)
                       OR (c.area_id IS NOT NULL AND c.area_id    = ca.area_id)
                   )
            LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
            LEFT  JOIN areas a     ON a.id  = COALESCE(ca.area_id, sa.area_id)
            INNER JOIN periodos per ON per.id = ? AND per.anio_id = m.anio_id
            LEFT  JOIN bloqueos_competencia bc
                    ON bc.carga_id       = ca.id
                   AND bc.competencia_id = c.id
                   AND bc.periodo_id     = per.id
            WHERE m.id = ?
              AND a.tipo <> 'transversal'
              AND (per.estado = 'cerrado' OR bc.competencia_id IS NOT NULL)
              AND NOT EXISTS (
                  SELECT 1 FROM calificaciones cal
                  WHERE cal.matricula_id   = m.id
                    AND cal.carga_id       = ca.id
                    AND cal.competencia_id = c.id
                    AND cal.periodo_id     = per.id
              )
              AND NOT EXISTS (
                  SELECT 1 FROM exoneraciones exo
                  WHERE exo.matricula_id = m.id
                    AND exo.anio_id      = m.anio_id
                    AND exo.revocado_en  IS NULL
                    AND (
                        (exo.area_id    IS NOT NULL AND exo.area_id    = a.id)
                        OR (exo.subarea_id IS NOT NULL AND exo.subarea_id = ca.subarea_id)
                    )
              )
            LIMIT 1
        ", [$cargaId, $competenciaId, $periodoId, $matriculaId]);

        return $fila !== null;
    }


    /**
     * Competencias TRANSVERSALES sin nota del alumno en un bimestre CERRADO,
     * candidatas al lote. Devuelve la MISMA forma que
     * getCompetenciasInsertables para que la grilla las mezcle sin ramas.
     *
     * ⚠️ POR QUÉ EXISTE, SI `getCompetenciasInsertables` LAS EXCLUYE.
     * Aquella exclusión (migración 042) se justificaba así: «su flujo es la
     * agregación + cierre del tutor; una fila cruda no llega a boleta».
     * **Medido el 10/09/2026: eso NO se cumple en un bimestre ya cerrado.**
     * `CalificacionModel::getTransversalesAgregadas` promedia las cargas con
     * BLOQUEO y solo exige un `cierres_transversales` vigente — y en los
     * bimestres cerrados las tres condiciones ya se dan. Una nota nueva entra
     * en ese AVG y SALE en la boleta (comprobado con escritura + rollback:
     * la celda pasó de vacía a `16 / A`).
     *
     * La exclusión SIGUE VIGENTE para el alta individual, que es el flujo del
     * bimestre en curso. Aquí se levanta solo para completar bimestres
     * cerrados, y con tres candados que la hacen segura:
     *
     *   1. **Periodo CERRADO** (no se toca el bimestre vivo del tutor).
     *   2. **`cierres_transversales` VIGENTE** en su sección: sin él la nota
     *      no llegaría a la boleta y quedaría registrada e invisible.
     *   3. **CARGA DUEÑA DERIVADA DEL DATO, nunca elegida a dedo**: es la
     *      carga que ya aporta esa transversal al RESTO de su sección y que
     *      tiene bloqueo. Si no existe (nadie de la sección la trabajó), la
     *      competencia NO se ofrece. En la práctica es una sola carga por
     *      sección, así que no hay ambigüedad que resolver.
     *
     * ⚠️ La CONCLUSIÓN de una transversal NO vive en `calificaciones`, vive en
     * `conclusiones_transversales` (la escribe el tutor). Quien llame a esto
     * tiene que enrutarla allí — lo hace `guardarExtraordinariaLote`.
     */
    public function getTransversalesInsertables(int $matriculaId): array
    {
        return $this->query("
            SELECT * FROM (
                SELECT
                    per.id             AS periodo_id,
                    per.numero         AS periodo_numero,
                    per.nombre_display AS periodo_nombre,
                    per.estado         AS periodo_estado,
                    c.id               AS competencia_id,
                    c.nombre_completo  AS competencia_nombre,
                    c.nombre_corto,
                    c.codigo_minedu,
                    a.id               AS area_id,
                    a.nombre           AS area_nombre,
                    a.nombre_boleta,
                    a.tipo             AS area_tipo,
                    NULL               AS subarea_nombre,
                    1                  AS bloqueada,
                    1                  AS es_transversal,
                    -- CARGA DUEÑA: la que ya aporta esta transversal al resto
                    -- de la sección Y tiene bloqueo. Se deriva; no se elige.
                    (SELECT cal2.carga_id
                       FROM calificaciones cal2
                       INNER JOIN matriculas m2
                               ON m2.id = cal2.matricula_id
                              AND m2.seccion_id = m.seccion_id
                       INNER JOIN bloqueos_competencia bc2
                               ON bc2.carga_id       = cal2.carga_id
                              AND bc2.competencia_id = cal2.competencia_id
                              AND bc2.periodo_id     = cal2.periodo_id
                      WHERE cal2.competencia_id = c.id
                        AND cal2.periodo_id     = per.id
                      GROUP BY cal2.carga_id
                      ORDER BY COUNT(*) DESC
                      LIMIT 1)         AS carga_id,
                    (SELECT COUNT(DISTINCT cal3.matricula_id)
                       FROM calificaciones cal3
                       INNER JOIN matriculas m3
                               ON m3.id = cal3.matricula_id
                              AND m3.seccion_id = m.seccion_id
                      WHERE cal3.competencia_id = c.id
                        AND cal3.periodo_id     = per.id) AS notas_seccion
                FROM matriculas m
                INNER JOIN secciones s ON s.id = m.seccion_id
                INNER JOIN grados    g ON g.id = s.grado_id
                INNER JOIN areas     a ON a.tipo = 'transversal' AND a.nivel_id = g.nivel_id
                INNER JOIN competencias c ON c.area_id = a.id
                INNER JOIN periodos per   ON per.anio_id = m.anio_id
                                         AND per.estado  = 'cerrado'
                -- Sin cierre vigente la nota no llegaria a la boleta.
                INNER JOIN cierres_transversales ct
                        ON ct.seccion_id = m.seccion_id
                       AND ct.periodo_id = per.id
                       AND ct.anulado_en IS NULL
                WHERE m.id = ?
                  -- Sin nota en NINGUNA carga: la boleta agrega por competencia.
                  AND NOT EXISTS (
                      SELECT 1 FROM calificaciones cal
                      WHERE cal.matricula_id   = m.id
                        AND cal.competencia_id = c.id
                        AND cal.periodo_id     = per.id
                  )
            ) t
            WHERE t.carga_id IS NOT NULL
            ORDER BY t.periodo_numero, t.area_id, t.competencia_id
        ", [$matriculaId]);
    }

    /**
     * ¿Esta tupla transversal es insertable? Re-chequeo del POST.
     *
     * Se resuelve PREGUNTÁNDOLE a getTransversalesInsertables en vez de
     * repetir sus condiciones: el universo es diminuto (2 competencias por
     * bimestre cerrado) y duplicar la regla a mano es el patrón de fallo
     * conocido de este repo. La carga tiene que coincidir con la DUEÑA.
     */
    public function esInsertableTransversal(
        int $matriculaId,
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): bool {
        foreach ($this->getTransversalesInsertables($matriculaId) as $t) {
            if ((int) $t['competencia_id'] === $competenciaId
                && (int) $t['periodo_id'] === $periodoId
                && (int) $t['carga_id'] === $cargaId) {
                return true;
            }
        }
        return false;
    }
    /**
     * Calificaciones extraordinarias registradas en una competencia+carga+
     * periodo, con el motivo y quién las registró. Alimenta el bloque
     * informativo de las vistas de solo lectura (historial del docente,
     * consulta de notas, resumen): el docente debe ver que esa nota NO salió
     * de su registro ordinario, sino de RA con autorización.
     */
    public function getExtraordinariasDeCompetencia(
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): array {
        return $this->query("
            SELECT
                r.matricula_id,
                r.nota_nueva,
                r.motivo,
                r.rectificado_en,
                CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres) AS estudiante,
                CONCAT(pu.apellido_paterno, ' ', pu.nombres) AS registrador
            FROM rectificaciones_calificacion r
            INNER JOIN matriculas m  ON m.id = r.matricula_id
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            LEFT  JOIN usuarios u    ON u.id = r.rectificado_por
            LEFT  JOIN personas pu   ON pu.id = u.persona_id
            WHERE r.carga_id       = ?
              AND r.competencia_id = ?
              AND r.periodo_id     = ?
              AND r.tipo           = 'extraordinaria'
            ORDER BY " . orden_alfabetico('p') . ", r.id
        ", [$cargaId, $competenciaId, $periodoId]);
    }

    /** Registra una fila de auditoría de rectificación. Retorna el ID nuevo. */
    public function registrar(array $data): int
    {
        return $this->create([
            'matricula_id'        => $data['matricula_id'],
            'carga_id'            => $data['carga_id'],
            'periodo_id'          => $data['periodo_id'],
            'competencia_id'      => $data['competencia_id'],
            'tipo'                => $data['tipo'] ?? 'rectificacion',
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
