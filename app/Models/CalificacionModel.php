<?php

namespace App\Models;

/**
 * CalificacionModel
 * Gestiona notas por criterio y calcula promedios de competencias.
 */
class CalificacionModel extends BaseModel
{
    protected string $table = 'calificaciones';

    // ── Notas por criterio ───────────────────────────────────

    /**
     * Guarda o actualiza la nota de un alumno en un criterio.
     */
    public function guardarNotaCriterio(
        int $criterioId,
        int $matriculaId,
        int $nota
    ): bool {
        return $this->execute("
            INSERT INTO calificaciones_criterio
                (criterio_id, matricula_id, nota, registrado_en)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                nota          = VALUES(nota),
                modificado_en = NOW()
        ", [$criterioId, $matriculaId, $nota]);
    }

    /**
     * Borra la nota de un alumno en un criterio (celda vaciada por el docente).
     */
    public function eliminarNotaCriterio(int $criterioId, int $matriculaId): bool
    {
        return $this->execute("
            DELETE FROM calificaciones_criterio
            WHERE criterio_id = ? AND matricula_id = ?
        ", [$criterioId, $matriculaId]);
    }

    /**
     * Guarda las notas de TODOS los alumnos de un criterio.
     * Recibe array [matricula_id => nota].
     */
    public function guardarNotasMasivas(
        int $criterioId,
        array $notas
    ): bool {
        if (empty($notas)) return true;

        $this->beginTransaction();
        try {
            foreach ($notas as $matriculaId => $nota) {
                $nota = max(0, min(20, (int) $nota));
                $this->guardarNotaCriterio(
                    $criterioId,
                    (int) $matriculaId,
                    $nota
                );
            }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            log_error('Error guardando notas masivas', [
                'criterio_id' => $criterioId,
                'error'       => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ── Promedios ────────────────────────────────────────────

    /**
     * Calcula el promedio de un alumno en una competencia.
     */
    public function calcularPromedio(
        int $matriculaId,
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): ?float {
        $resultado = $this->queryOne("
            SELECT ROUND(AVG(cc.nota), 0) AS promedio
            FROM calificaciones_criterio cc
            INNER JOIN criterios cr ON cr.id = cc.criterio_id
            WHERE cc.matricula_id   = ?
              AND cr.carga_id       = ?
              AND cr.competencia_id = ?
              AND cr.periodo_id     = ?
              AND cr.eliminado_en   IS NULL
        ", [$matriculaId, $cargaId, $competenciaId, $periodoId]);

        return isset($resultado['promedio'])
            ? (float) $resultado['promedio']
            : null;
    }

    /**
     * Guarda la nota final de una competencia (promedio de criterios).
     */
    public function guardarNotaFinal(
        int $matriculaId,
        int $cargaId,
        int $periodoId,
        int $competenciaId,
        int $notaNumerica,
        int $registradoPor,
        ?string $conclusion = null
    ): bool {
        return $this->execute("
            INSERT INTO calificaciones
                (matricula_id, carga_id, periodo_id, competencia_id,
                 nota_numerica, conclusion_descriptiva,
                 registrado_por, registrado_en)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                nota_numerica = VALUES(nota_numerica),
                modificado_en = NOW()
        ", [
            $matriculaId, $cargaId, $periodoId, $competenciaId,
            $notaNumerica, $conclusion, $registradoPor
        ]);
    }

    /**
     * Recalcula el promedio de TODOS los alumnos de una sección
     * para una competencia y periodo.
     */
    public function recalcularPromedioSeccion(
        int $cargaId,
        int $competenciaId,
        int $periodoId,
        int $registradoPor
    ): void {
        $alumnos = $this->query("
            SELECT DISTINCT cc.matricula_id
            FROM calificaciones_criterio cc
            INNER JOIN criterios cr ON cr.id = cc.criterio_id
            WHERE cr.carga_id       = ?
              AND cr.competencia_id = ?
              AND cr.periodo_id     = ?
              AND cr.eliminado_en   IS NULL
        ", [$cargaId, $competenciaId, $periodoId]);

        foreach ($alumnos as $alumno) {
            $promedio = $this->calcularPromedio(
                $alumno['matricula_id'],
                $cargaId,
                $competenciaId,
                $periodoId
            );

            if ($promedio !== null) {
                $this->guardarNotaFinal(
                    $alumno['matricula_id'],
                    $cargaId,
                    $periodoId,
                    $competenciaId,
                    (int) round($promedio),
                    $registradoPor
                );
            }
        }

        // Limpieza de promedios huérfanos: si un alumno quedó SIN ninguna nota
        // viva de criterio en esta competencia (borró su última nota, o se
        // eliminó el único criterio), su fila agregada en `calificaciones` ya no
        // representa nada. Como `nota_numerica` es NOT NULL, se elimina la fila
        // (semántica "sin nota": todos los consumidores usan LEFT JOIN). Sin
        // esto, el promedio anterior quedaba persistido como fantasma y la
        // boleta/orden de mérito/resumen seguían mostrándolo.
        $this->execute("
            DELETE c FROM calificaciones c
            WHERE c.carga_id       = ?
              AND c.competencia_id = ?
              AND c.periodo_id     = ?
              AND NOT EXISTS (
                  SELECT 1
                  FROM calificaciones_criterio cc
                  INNER JOIN criterios cr ON cr.id = cc.criterio_id
                  WHERE cc.matricula_id   = c.matricula_id
                    AND cr.carga_id       = c.carga_id
                    AND cr.competencia_id = c.competencia_id
                    AND cr.periodo_id     = c.periodo_id
                    AND cr.eliminado_en   IS NULL
              )
        ", [$cargaId, $competenciaId, $periodoId]);
    }

    /**
     * Actualiza la conclusión descriptiva de un alumno en una competencia.
     * Retorna false si no existe la fila en calificaciones (notas no guardadas aún).
     */
    public function actualizarConclusion(
        int $matriculaId,
        int $cargaId,
        int $competenciaId,
        int $periodoId,
        string $conclusion
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE calificaciones
            SET conclusion_descriptiva = ?,
                modificado_en          = NOW()
            WHERE matricula_id   = ?
              AND carga_id       = ?
              AND competencia_id = ?
              AND periodo_id     = ?
        ");
        $stmt->execute([$conclusion, $matriculaId, $cargaId, $competenciaId, $periodoId]);
        return $stmt->rowCount() > 0;
    }

    // ── Validaciones ─────────────────────────────────────────

    /**
     * Convierte nota numérica a literal.
     */
    public static function toLiteral(int $nota): string
    {
        return nota_a_literal($nota);
    }

    /**
     * Verifica si la conclusión descriptiva es obligatoria.
     */
    public static function conclusionObligatoria(
        string $literal,
        string $nivelCodigo
    ): bool {
        if ($nivelCodigo === 'prim') {
            return in_array($literal, ['B', 'C']);
        }
        return $literal === 'C';
    }

    /**
     * Verifica si el periodo está bloqueado para el docente.
     */
    public function periodoEstaBloqueado(int $periodoId): bool
    {
        $periodo = $this->queryOne("
            SELECT limite_notas, estado
            FROM periodos
            WHERE id = ?
        ", [$periodoId]);

        if (!$periodo) return true;
        if ($periodo['estado'] === 'cerrado') return true;
        if ($periodo['limite_notas'] === null) return false;

        return strtotime($periodo['limite_notas']) < time();
    }

    /**
     * Contexto de boleta para soportar el RETORNO DE GRADO. Una estudiante
     * puede tener dos matrículas en el mismo año: la OFICIAL (grado/sección
     * SIAGIE) y una OPERATIVA en un grado inferior mientras se nivela. Las
     * notas viven repartidas (p. ej. B1-B2 en la operativa, B3-B4 en la
     * oficial), pero la boleta SIEMPRE se identifica con la matrícula oficial.
     *
     * Devuelve:
     *   - 'identidad': matrícula con la que se rotula la boleta (encabezado,
     *      tutor, director). Es la OFICIAL si participa de un retorno.
     *   - 'fuentes': matrículas de las que se leen las notas, ordenadas
     *      [operativa, oficial] para que, al fusionar por periodo, la oficial
     *      gane ante un eventual choque del mismo periodo.
     *
     * Si la matrícula no participa de ningún retorno (caso normal) devuelve
     * la propia matrícula como identidad y única fuente. Cubre tanto el retorno
     * 'activo' como el 'revertido' (en ambos la boleta es la oficial).
     */
    public function boletaContexto(int $matriculaId): array
    {
        $r = $this->queryOne("
            SELECT matricula_oficial_id, matricula_operativa_id
            FROM retornos_grado
            WHERE matricula_oficial_id = ? OR matricula_operativa_id = ?
            ORDER BY id DESC
            LIMIT 1
        ", [$matriculaId, $matriculaId]);

        if (!$r) {
            return ['identidad' => $matriculaId, 'fuentes' => [$matriculaId]];
        }

        $oficial   = (int) $r['matricula_oficial_id'];
        $operativa = (int) $r['matricula_operativa_id'];

        return ['identidad' => $oficial, 'fuentes' => [$operativa, $oficial]];
    }

    /**
     * Obtiene la boleta completa de un alumno en un periodo.
     *
     * Las competencias TRANSVERSALES (TIC/GAMA) no salen de las filas crudas
     * (desde B2 cada docente registra las suyas → habría duplicados): se
     * agregan al final como promedio de promedios por carga bloqueada, y
     * SOLO si existe el cierre vigente del tutor para la sección + periodo.
     */
    public function getBoletaAlumno(
        int $matriculaId,
        int $periodoId
    ): array {
        $notas = $this->query("
            SELECT
                cal.nota_numerica,
                cal.conclusion_descriptiva,
                comp.id              AS competencia_id,
                comp.nombre_completo AS competencia_nombre,
                comp.nombre_corto,
                comp.codigo_minedu,
                a.id                 AS area_id,
                a.nombre             AS area_nombre,
                a.nombre_boleta,
                a.alias_boleta,
                a.tipo               AS area_tipo,
                sa.nombre            AS subarea_nombre,
                s.es_unidocente,
                ca.id                AS carga_id
            FROM calificaciones cal
            INNER JOIN cargas_academicas ca    ON ca.id   = cal.carga_id
            INNER JOIN competencias comp       ON comp.id = cal.competencia_id
            INNER JOIN bloqueos_competencia bc ON bc.carga_id       = cal.carga_id
                                               AND bc.competencia_id = cal.competencia_id
                                               AND bc.periodo_id     = cal.periodo_id
            LEFT  JOIN subareas sa             ON sa.id  = ca.subarea_id
            LEFT  JOIN areas a                 ON a.id   = COALESCE(ca.area_id, sa.area_id)
            LEFT  JOIN secciones s             ON s.id   = ca.seccion_id
            WHERE cal.matricula_id = ?
              AND cal.periodo_id   = ?
              -- La transversalidad se decide por el área de la COMPETENCIA
              -- (no de la carga): cubre tanto la carga del tutor (B1) como
              -- las filas TIC/GAMA registradas en cargas de docentes (B2+).
              AND NOT EXISTS (
                  SELECT 1 FROM areas at2
                  WHERE at2.id = comp.area_id AND at2.tipo = 'transversal'
              )
            ORDER BY a.orden, comp.orden
        ", [$matriculaId, $periodoId]);

        foreach ($notas as &$nota) {
            $nota['criterios'] = $this->query("
                SELECT
                    cr.nombre      AS criterio_nombre,
                    cr.descripcion AS criterio_descripcion,
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
                ORDER BY cr.orden
            ", [
                $matriculaId,
                $nota['carga_id'],
                $nota['competencia_id'],
                $periodoId
            ]);
        }
        unset($nota);

        return array_merge(
            $notas,
            $this->getTransversalesAgregadas($matriculaId, $periodoId)
        );
    }

    /**
     * Filas TIC/GAMA agregadas para la boleta: promedio de promedios por
     * carga bloqueada, condicionado al cierre vigente del tutor. La
     * conclusión sale de conclusiones_transversales (la registra el tutor).
     */
    private function getTransversalesAgregadas(int $matriculaId, int $periodoId): array
    {
        $cierre = $this->queryOne("
            SELECT ct.id
            FROM cierres_transversales ct
            INNER JOIN matriculas m ON m.seccion_id = ct.seccion_id
            WHERE m.id          = ?
              AND ct.periodo_id = ?
              AND ct.anulado_en IS NULL
            LIMIT 1
        ", [$matriculaId, $periodoId]);

        if (!$cierre) {
            return [];
        }

        $filas = $this->query("
            SELECT
                ROUND(AVG(cal.nota_numerica)) AS nota_numerica,
                ct.conclusion                 AS conclusion_descriptiva,
                comp.id                       AS competencia_id,
                comp.nombre_completo          AS competencia_nombre,
                comp.nombre_corto,
                comp.codigo_minedu,
                a.id                          AS area_id,
                a.nombre                      AS area_nombre,
                a.nombre_boleta,
                a.alias_boleta,
                a.tipo                        AS area_tipo,
                NULL                          AS subarea_nombre,
                NULL                          AS carga_id
            FROM calificaciones cal
            INNER JOIN bloqueos_competencia bc
                ON  bc.carga_id       = cal.carga_id
                AND bc.competencia_id = cal.competencia_id
                AND bc.periodo_id     = cal.periodo_id
            INNER JOIN competencias comp ON comp.id = cal.competencia_id
            INNER JOIN areas a           ON a.id = comp.area_id AND a.tipo = 'transversal'
            LEFT  JOIN conclusiones_transversales ct
                ON  ct.matricula_id   = cal.matricula_id
                AND ct.competencia_id = cal.competencia_id
                AND ct.periodo_id     = cal.periodo_id
            WHERE cal.matricula_id = ?
              AND cal.periodo_id   = ?
            GROUP BY comp.id, comp.nombre_completo, comp.nombre_corto,
                     comp.codigo_minedu, a.id, a.nombre, a.nombre_boleta,
                     a.alias_boleta, a.tipo, ct.conclusion
            ORDER BY comp.orden
        ", [$matriculaId, $periodoId]);

        foreach ($filas as &$f) {
            $f['criterios'] = [];
        }

        return $filas;
    }

    // ─── Bloqueos de competencia ─────────────────────────────────

/**
 * Verifica si una competencia está bloqueada por el docente.
 */
    public function competenciaBloqueada(
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): bool {
        $resultado = $this->queryOne("
            SELECT id FROM bloqueos_competencia
            WHERE carga_id       = ?
            AND competencia_id = ?
            AND periodo_id     = ?
            LIMIT 1
        ", [$cargaId, $competenciaId, $periodoId]);

        return $resultado !== null;
    }

    /**
     * Bloquea una competencia — el docente aprueba sus notas.
     */
    public function bloquearCompetencia(
        int $cargaId,
        int $competenciaId,
        int $periodoId,
        int $usuarioId
    ): bool {
        return $this->execute("
            INSERT IGNORE INTO bloqueos_competencia
                (carga_id, competencia_id, periodo_id, bloqueado_por, origen)
            VALUES (?, ?, ?, ?, 'docente')
        ", [$cargaId, $competenciaId, $periodoId, $usuarioId]);
    }

    /**
     * ¿Se puede marcar una competencia académica de esta carga como
     * "no se evaluó" sin dejar la carga totalmente sin calificaciones?
     *
     * Regla de negocio: el docente es autónomo para elegir qué competencias
     * evaluar, pero su carga NO puede quedar sin ninguna calificación. Solo
     * cuenta las competencias ACADÉMICAS de la carga (las transversales TIC/GAMA
     * se gobiernan aparte con el cierre del tutor, no por este piso).
     *
     * Devuelve false cuando marcar una "no se evaluó" dejaría la carga vacía y
     * finalizada, es decir cuando `con_notas == 0 && abiertas <= 1`:
     *   - con_notas = competencias con al menos un alumno calificado.
     *   - abiertas  = competencias aún no bloqueadas (pueden recibir notas).
     * El chequeo es a nivel de carga (no depende de cuál competencia), así que
     * sirve igual para la validación del servidor y para ocultar el botón.
     *
     * UNIDOCENTE (área-aware): cuando la sección es `es_unidocente`, el mismo
     * docente dicta TODAS las áreas, así que el "piso" se mide a nivel de ÁREA,
     * no de carga: cuenta las competencias de TODAS las cargas activas de la
     * misma área en la sección (resolviendo el área vía
     * COALESCE(area_id, subarea→area_id)). Así una subárea de una sola
     * competencia (p. ej. Aritmética) acepta "No se evaluó" mientras quede ≥1
     * subárea de Matemática evaluada. Para especialistas (no unidocente) el
     * alcance se reduce a la propia carga → comportamiento idéntico al anterior
     * (la regla "subárea de 1 competencia = obligatoria" sigue viva en 4°-6° y
     * en secundaria).
     *
     * El director/admin SÍ puede forzar una carga sin notas desde el panel de
     * bloqueos (vía bloquearCompetencia, que no pasa por este piso).
     */
    public function permiteNoEvaluarEnCarga(int $cargaId, int $periodoId): bool
    {
        $r = $this->queryOne("
            SELECT
                COALESCE(SUM(CASE WHEN ac.notas    > 0 THEN 1 ELSE 0 END), 0) AS con_notas,
                COALESCE(SUM(CASE WHEN ac.bloqueada = 0 THEN 1 ELSE 0 END), 0) AS abiertas
            FROM (
                SELECT
                    sc.carga_id,
                    comp.id AS competencia_id,
                    (
                        SELECT COUNT(DISTINCT cc.matricula_id)
                        FROM calificaciones_criterio cc
                        INNER JOIN criterios cr ON cr.id = cc.criterio_id
                        WHERE cr.carga_id       = sc.carga_id
                          AND cr.competencia_id = comp.id
                          AND cr.periodo_id     = ?
                          AND cr.eliminado_en   IS NULL
                    ) AS notas,
                    (
                        SELECT COUNT(*)
                        FROM bloqueos_competencia bc
                        WHERE bc.carga_id       = sc.carga_id
                          AND bc.competencia_id = comp.id
                          AND bc.periodo_id     = ?
                    ) AS bloqueada
                FROM (
                    -- Cargas dentro del alcance: la propia carga SIEMPRE; si la
                    -- sección es unidocente, además todas las cargas activas de
                    -- la misma área en esa sección.
                    SELECT ca2.id AS carga_id, ca2.subarea_id, ca2.area_id
                    FROM cargas_academicas ca0
                    INNER JOIN secciones s    ON s.id   = ca0.seccion_id
                    LEFT  JOIN subareas  sa0  ON sa0.id = ca0.subarea_id
                    INNER JOIN cargas_academicas ca2 ON (
                        ca2.id = ca0.id
                        OR (
                            s.es_unidocente = 1
                            AND ca2.seccion_id = ca0.seccion_id
                            AND ca2.estado     = 'activa'
                            AND COALESCE(
                                    ca2.area_id,
                                    (SELECT sax.area_id FROM subareas sax WHERE sax.id = ca2.subarea_id)
                                ) = COALESCE(ca0.area_id, sa0.area_id)
                        )
                    )
                    WHERE ca0.id = ?
                ) sc
                INNER JOIN competencias comp ON (
                    (sc.subarea_id IS NOT NULL AND comp.subarea_id = sc.subarea_id)
                    OR (sc.area_id IS NOT NULL AND comp.area_id = sc.area_id)
                )
            ) ac
        ", [
            $periodoId, $periodoId, $cargaId,
        ]);

        $conNotas = (int) ($r['con_notas'] ?? 0);
        $abiertas = (int) ($r['abiertas'] ?? 0);

        return !($conNotas === 0 && $abiertas <= 1);
    }

    /**
     * Obtiene el resumen de notas de todos los alumnos
     * para una competencia específica.
     */
    /**
     * Elimina un bloqueo por su ID (desbloquea la competencia).
     */
    public function desbloquearCompetencia(int $bloqueoId): bool
    {
        return $this->execute("
            DELETE FROM bloqueos_competencia WHERE id = ?
        ", [$bloqueoId]);
    }

    /**
     * Devuelve TODAS las (carga, competencia) del año del periodo,
     * con o sin criterios, junto con su estado de bloqueo.
     * Incluye num_criterios para distinguir los cuatro estados posibles:
     *   bloqueada con notas | bloqueada sin notas | pendiente | sin criterios
     */
    public function getCompetenciasPorPeriodo(int $periodoId): array
    {
        return $this->query("
            SELECT
                ca.id                AS carga_id,
                comp.id              AS competencia_id,
                bc.id                AS bloqueo_id,
                bc.origen            AS bloqueo_origen,
                bc.bloqueado_en,
                comp.nombre_completo AS competencia_nombre,
                a.nombre             AS area_nombre,
                sa.nombre            AS subarea_nombre,
                n.nombre             AS nivel_nombre,
                n.codigo             AS nivel_codigo,
                n.id                 AS nivel_id,
                g.numero             AS grado_numero,
                g.nombre_display     AS grado_nombre,
                s.id                 AS seccion_id,
                s.nombre             AS seccion_nombre,
                ud.id                AS docente_id,
                pu.apellido_paterno  AS docente_apellido,
                pu.apellido_materno  AS docente_materno,
                pu.nombres           AS docente_nombres,
                (
                    SELECT COUNT(*)
                    FROM criterios cr
                    WHERE cr.carga_id       = ca.id
                      AND cr.competencia_id = comp.id
                      AND cr.periodo_id     = ?
                      AND cr.eliminado_en   IS NULL
                )                    AS num_criterios
            FROM cargas_academicas ca
            INNER JOIN secciones s   ON s.id   = ca.seccion_id
            INNER JOIN grados g      ON g.id   = s.grado_id
            INNER JOIN niveles n     ON n.id   = g.nivel_id
            INNER JOIN usuarios ud   ON ud.id  = ca.docente_id
            INNER JOIN personas pu   ON pu.id  = ud.persona_id
            LEFT  JOIN subareas sa   ON sa.id  = ca.subarea_id
            LEFT  JOIN areas a       ON a.id   = COALESCE(ca.area_id, sa.area_id)
            INNER JOIN competencias comp ON (
                (ca.subarea_id IS NOT NULL AND comp.subarea_id = ca.subarea_id)
                OR
                (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL AND comp.area_id = ca.area_id)
            )
            LEFT JOIN bloqueos_competencia bc
                ON  bc.carga_id       = ca.id
                AND bc.competencia_id = comp.id
                AND bc.periodo_id     = ?
            WHERE ca.estado  = 'activa'
              AND ca.anio_id = (SELECT anio_id FROM periodos WHERE id = ?)
            ORDER BY n.id, g.numero, s.nombre, a.orden, comp.orden
        ", [$periodoId, $periodoId, $periodoId]);
    }


    public function getResumenCompetencia(
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): array {
        // Obtener criterios activos (excluye eliminados)
        $criterios = $this->query("
            SELECT id, nombre, descripcion, orden
            FROM criterios
            WHERE carga_id       = ?
            AND competencia_id = ?
            AND periodo_id     = ?
            AND eliminado_en   IS NULL
            ORDER BY orden, id
        ", [$cargaId, $competenciaId, $periodoId]);

        // Obtener alumnos con sus notas
        $alumnos = $this->query("
            SELECT
                m.id AS matricula_id,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                p.dni,
                CONCAT(
                    p.apellido_paterno, ' ',
                    p.apellido_materno, ', ',
                    p.nombres
                ) AS nombre_completo,
                cal.nota_numerica AS promedio,
                cal.conclusion_descriptiva
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            INNER JOIN secciones s   ON s.id = m.seccion_id
            LEFT JOIN calificaciones cal
                ON  cal.matricula_id   = m.id
                AND cal.carga_id       = ?
                AND cal.competencia_id = ?
                AND cal.periodo_id     = ?
            WHERE s.id = (
                SELECT seccion_id FROM cargas_academicas WHERE id = ?
            )
            -- Mismo criterio que getAlumnosSeccion: incluye 'pendiente' (recién
            -- creadas) y 'aprobada' (vigentes, incl. retorno de grado), excluye
            -- trasladados. Así el resumen y la validación de bloqueo cuadran con
            -- la grilla de ingreso.
            AND m.estado IN ('aprobada', 'pendiente')
            AND m.tipo  != 'trasladado'
            -- Retorno de grado: misma exclusión que getAlumnosSeccion (oficial en
            -- retorno activo / operativa ya revertida no se califican aquí).
            AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
            AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
            ORDER BY p.apellido_paterno, p.apellido_materno
        ", [$cargaId, $competenciaId, $periodoId, $cargaId]);

        // Agregar notas por criterio a cada alumno
        foreach ($alumnos as &$alumno) {
            $alumno['notas_criterios'] = [];
            foreach ($criterios as $criterio) {
                $nota = $this->queryOne("
                    SELECT nota
                    FROM calificaciones_criterio
                    WHERE criterio_id  = ?
                    AND matricula_id = ?
                ", [$criterio['id'], $alumno['matricula_id']]);

                $alumno['notas_criterios'][$criterio['id']] = $nota['nota'] ?? null;
            }

            // Calcular literal del promedio
            $alumno['literal'] = $alumno['promedio'] !== null
                ? nota_a_literal((int) $alumno['promedio'])
                : null;
        }

        return [
            'criterios' => $criterios,
            'alumnos'   => $alumnos,
        ];
    }
}

