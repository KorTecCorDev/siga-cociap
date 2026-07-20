<?php

namespace App\Models;

/**
 * ConductaModel — modelo NUMERICO de conducta (rediseno migracion 021).
 *
 * Dos etapas (patron Tutoria/Transversales):
 *   1. Registro Academico responde 10 criterios Si/No por alumno -> nota RA =
 *      (Si / total_criterios) * 20. Bloquea la seccion (cierres_conducta) ->
 *      la conducta se hace visible en boleta (final = nota RA sola).
 *   2. El tutor, solo si RA ya bloqueo, agrega su nota 00-20 opcional (final =
 *      promedio, .5 a favor del estudiante) y cierra/aprueba la seccion.
 *
 * El literal SIEMPRE sale de nota_a_literal() (escala oficial 18/14/11).
 * Las filas del I Bimestre (literal directo, sin respuestas) son legado.
 */
class ConductaModel extends BaseModel
{
    // ── Catalogos / listados ─────────────────────────────────────

    /** Secciones del año activo (nomina aprobada) agrupables por nivel. */
    public function listarSeccionesActivas(): array
    {
        return $this->query("
            SELECT
                s.id,
                s.nombre          AS seccion_nombre,
                g.nombre_display  AS grado_nombre,
                g.numero          AS grado_numero,
                g.nivel_id        AS nivel_id,
                n.nombre          AS nivel_nombre,
                a.id              AS anio_id,
                a.anio
            FROM secciones s
            INNER JOIN grados g             ON g.id = s.grado_id
            INNER JOIN niveles n            ON n.id = g.nivel_id
            INNER JOIN anios_academicos a   ON a.id = s.anio_id
            WHERE a.estado = 'activo'
              AND s.estado_nomina = 'aprobada'
            ORDER BY n.id, g.numero, s.nombre
        ");
    }

    /** Periodos del año activo con flag de edicion (mismo criterio que el resto). */
    public function listarPeriodosActivos(): array
    {
        return $this->query("
            SELECT
                p.id, p.numero, p.nombre_display, p.estado, p.limite_notas, a.anio,
                (
                    p.estado = 'activo'
                    AND (p.limite_notas IS NULL OR NOW() <= p.limite_notas)
                ) AS editable
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE a.estado = 'activo'
            ORDER BY p.numero
        ");
    }

    /** Criterios vigentes; si se pasa $nivelId, incluye los de ambos (nivel_id NULL). */
    public function getCriterios(?int $nivelId = null): array
    {
        if ($nivelId === null) {
            return $this->query("
                SELECT id, texto, orden, nivel_id
                FROM criterios_conducta
                WHERE eliminado_en IS NULL
                ORDER BY orden, id
            ");
        }
        return $this->query("
            SELECT id, texto, orden, nivel_id
            FROM criterios_conducta
            WHERE eliminado_en IS NULL
              AND (nivel_id IS NULL OR nivel_id = ?)
            ORDER BY orden, id
        ", [$nivelId]);
    }

    /** Total de criterios vigentes que aplican a un nivel (para la formula y completitud). */
    public function totalCriterios(int $nivelId): int
    {
        $row = $this->queryOne("
            SELECT COUNT(*) AS total
            FROM criterios_conducta
            WHERE eliminado_en IS NULL
              AND (nivel_id IS NULL OR nivel_id = ?)
        ", [$nivelId]);
        return (int) ($row['total'] ?? 0);
    }

    /** [seccion_id, nivel_id] de una matricula (para validar en el guardado). */
    public function contextoMatricula(int $matriculaId): ?array
    {
        return $this->queryOne("
            SELECT m.seccion_id, g.nivel_id
            FROM matriculas m
            INNER JOIN secciones s ON s.id = m.seccion_id
            INNER JOIN grados    g ON g.id = s.grado_id
            WHERE m.id = ?
        ", [$matriculaId]);
    }

    // ── Indice: progreso y estado de bloqueo por seccion ─────────

    /**
     * Por seccion: esperados, calificados (alumnos con sus N criterios completos),
     * y estado del cierre (bloqueada por RA / cerrada por tutor). Una sola query.
     */
    public function getProgresoConductaPorSeccion(int $periodoId): array
    {
        $rows = $this->query("
            SELECT
                s.id AS seccion_id,
                COUNT(DISTINCT m.id) AS esperados,
                COUNT(DISTINCT CASE
                    WHEN sub.respondidos > 0 AND sub.respondidos >= (
                        SELECT COUNT(*) FROM criterios_conducta k
                        WHERE k.eliminado_en IS NULL
                          AND (k.nivel_id IS NULL OR k.nivel_id = g.nivel_id)
                    ) THEN m.id END) AS calificados,
                MAX(z.id IS NOT NULL)              AS bloqueada,
                MAX(z.tutor_cerrado_en IS NOT NULL) AS cerrada_tutor
            FROM secciones s
            INNER JOIN grados g ON g.id = s.grado_id
            INNER JOIN anios_academicos a ON a.id = s.anio_id AND a.estado = 'activo'
            LEFT JOIN matriculas m
                   ON m.seccion_id = s.id AND m.anio_id = s.anio_id
                  -- Mismo roster que el docente (getAlumnosSeccion): todos salvo el
                  -- traslado de salida; retorno excluye la matricula que no aplica.
                  AND m.tipo != 'trasladado'
                  AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
                  AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
            LEFT JOIN (
                SELECT matricula_id, COUNT(*) AS respondidos
                FROM conducta_respuestas WHERE periodo_id = ?
                GROUP BY matricula_id
            ) sub ON sub.matricula_id = m.id
            LEFT JOIN cierres_conducta z
                   ON z.seccion_id = s.id AND z.periodo_id = ? AND z.anulado_en IS NULL
            WHERE s.estado_nomina = 'aprobada'
            GROUP BY s.id, g.nivel_id
        ", [$periodoId, $periodoId]);

        $mapa = [];
        foreach ($rows as $r) {
            $mapa[(int) $r['seccion_id']] = [
                'esperados'     => (int) $r['esperados'],
                'calificados'   => (int) $r['calificados'],
                'bloqueada'     => (bool) $r['bloqueada'],
                'cerrada_tutor' => (bool) $r['cerrada_tutor'],
            ];
        }
        return $mapa;
    }

    // ── Etapa 1: Registro Academico (respuestas Si/No) ───────────

    /**
     * Alumnos de la seccion con su matriz de respuestas [criterio_id => 0|1].
     * Para la grilla de registro de RA.
     */
    public function getEstudiantesParaRegistro(int $seccionId, int $periodoId): array
    {
        $alumnos = $this->query("
            SELECT
                m.id AS matricula_id,
                CONCAT(p.apellido_paterno,' ',p.apellido_materno,', ',p.nombres) AS nombre_completo
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas    p ON p.id = e.persona_id
            WHERE m.seccion_id = ?
              -- Mismo roster que el docente al ingresar notas (getAlumnosSeccion):
              -- TODOS los matriculados de la seccion (aprobada, pendiente e incluso
              -- desactivado por baja administrativa/deuda: siguen asistiendo). El
              -- UNICO excluido es el traslado de salida (tipo='trasladado'). El
              -- retorno de grado excluye la matricula que no se califica en su grado.
              AND m.tipo != 'trasladado'
              AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
              AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
              AND m.anio_id = (SELECT id FROM anios_academicos WHERE estado='activo' LIMIT 1)
            ORDER BY p.apellido_paterno, p.apellido_materno, p.nombres
        ", [$seccionId]);

        if (empty($alumnos)) {
            return [];
        }

        $ids = array_column($alumnos, 'matricula_id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $resp = $this->query("
            SELECT matricula_id, criterio_id, respuesta
            FROM conducta_respuestas
            WHERE periodo_id = ? AND matricula_id IN ($ph)
        ", array_merge([$periodoId], $ids));

        $idx = [];
        foreach ($resp as $r) {
            $idx[(int) $r['matricula_id']][(int) $r['criterio_id']] = (int) $r['respuesta'];
        }

        foreach ($alumnos as &$a) {
            $a['respuestas'] = $idx[(int) $a['matricula_id']] ?? [];
        }
        return $alumnos;
    }

    /**
     * Literales legados de la seccion (I Bimestre: literal directo en
     * calificaciones_conducta, sin matriz de respuestas). Mismo roster que
     * getEstudiantesParaRegistro; literal NULL si el alumno no tiene registro.
     * Para el historial de solo lectura de RA.
     */
    public function getLiteralesLegado(int $seccionId, int $periodoId): array
    {
        return $this->query("
            SELECT
                m.id AS matricula_id,
                CONCAT(p.apellido_paterno,' ',p.apellido_materno,', ',p.nombres) AS nombre_completo,
                cc.literal
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas    p ON p.id = e.persona_id
            LEFT JOIN calificaciones_conducta cc
                ON cc.matricula_id = m.id AND cc.periodo_id = ?
            WHERE m.seccion_id = ?
              -- Mismo roster que getEstudiantesParaRegistro (todos salvo el
              -- traslado de salida; retorno excluye la matricula que no aplica).
              AND m.tipo != 'trasladado'
              AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
              AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
              AND m.anio_id = (SELECT id FROM anios_academicos WHERE estado='activo' LIMIT 1)
            ORDER BY p.apellido_paterno, p.apellido_materno, p.nombres
        ", [$periodoId, $seccionId]);
    }

    /**
     * Datos del registro legado de la seccion (bimestre con literal directo,
     * anterior a la matriz de criterios): quien registro y cuando (ultimo
     * guardado). Devuelve null si la seccion tiene matriz de respuestas en el
     * periodo (modelo nuevo) o si no hay literales registrados.
     */
    public function getRegistroLegado(int $seccionId, int $periodoId): ?array
    {
        $hayMatriz = $this->queryOne("
            SELECT 1
            FROM conducta_respuestas r
            INNER JOIN matriculas m ON m.id = r.matricula_id
            WHERE m.seccion_id = ? AND r.periodo_id = ?
            LIMIT 1
        ", [$seccionId, $periodoId]);
        if ($hayMatriz) {
            return null;
        }

        $registro = $this->queryOne("
            SELECT
                CONCAT(p.nombres, ' ', p.apellido_paterno, ' ', p.apellido_materno) AS usuario,
                cc.registrado_en
            FROM calificaciones_conducta cc
            INNER JOIN matriculas m ON m.id = cc.matricula_id
            INNER JOIN usuarios   u ON u.id = cc.registrado_por
            INNER JOIN personas   p ON p.id = u.persona_id
            WHERE m.seccion_id = ? AND cc.periodo_id = ? AND cc.literal IS NOT NULL
            ORDER BY cc.registrado_en DESC
            LIMIT 1
        ", [$seccionId, $periodoId]);

        return $registro ?: null;
    }

    /**
     * Upsert atomico de las respuestas de un alumno. Exige que esten TODOS los
     * criterios de $criterioIds (los 10 son obligatorios). Devuelve false si falta
     * alguno o ante error de BD.
     * @param array<int,int> $respuestas [criterio_id => 0|1]
     * @param array<int>     $criterioIds ids de criterios vigentes del nivel
     */
    public function guardarRespuestas(int $matriculaId, int $periodoId, array $respuestas, int $userId, array $criterioIds): bool
    {
        foreach ($criterioIds as $cid) {
            if (!array_key_exists($cid, $respuestas)) {
                return false; // incompleto: no se permiten respuestas en blanco
            }
        }

        $this->beginTransaction();
        try {
            $sql = "INSERT INTO conducta_respuestas
                        (matricula_id, periodo_id, criterio_id, respuesta, registrado_por)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        respuesta      = VALUES(respuesta),
                        registrado_por = VALUES(registrado_por),
                        modificado_en  = NOW()";
            foreach ($criterioIds as $cid) {
                $this->execute($sql, [
                    $matriculaId, $periodoId, $cid, $respuestas[$cid] ? 1 : 0, $userId,
                ]);
            }
            $this->commit();
            return true;
        } catch (\Throwable $ex) {
            $this->rollback();
            log_error('conducta.guardarRespuestas', ['err' => $ex->getMessage()]);
            return false;
        }
    }

    // ── Completitud y cierre por seccion ─────────────────────────

    /** [esperados, completos]: alumnos aprobados vs. los que tienen sus N respuestas. */
    public function completitudSeccion(int $seccionId, int $periodoId, int $totalCriterios): array
    {
        $row = $this->queryOne("
            SELECT
                COUNT(*) AS esperados,
                SUM(CASE WHEN sub.respondidos >= ? THEN 1 ELSE 0 END) AS completos
            FROM matriculas m
            LEFT JOIN (
                SELECT matricula_id, COUNT(*) AS respondidos
                FROM conducta_respuestas WHERE periodo_id = ?
                GROUP BY matricula_id
            ) sub ON sub.matricula_id = m.id
            WHERE m.seccion_id = ?
              -- Mismo roster que el docente (getAlumnosSeccion): la compuerta de
              -- completitud debe contar exactamente a quienes aparecen en la grilla.
              AND m.tipo != 'trasladado'
              AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
              AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
              AND m.anio_id = (SELECT id FROM anios_academicos WHERE estado='activo' LIMIT 1)
        ", [$totalCriterios, $periodoId, $seccionId]);

        return [
            'esperados' => (int) ($row['esperados'] ?? 0),
            'completos' => (int) ($row['completos'] ?? 0),
        ];
    }

    /**
     * Resumen por seccion para el panel del director (espejo del de
     * transversales): datos de la seccion + tutor, estado de las DOS etapas
     * del cierre de conducta y completitud (alumnos calificados / esperados).
     * Solo secciones del año del periodo CON tutor asignado (la etapa 2 lo exige).
     */
    public function getResumenSeccionesPorPeriodo(int $periodoId): array
    {
        $secciones = $this->query("
            SELECT s.id              AS seccion_id,
                   s.nombre          AS seccion_nombre,
                   g.numero          AS grado_numero,
                   g.nombre_display  AS grado_nombre,
                   n.id              AS nivel_id,
                   n.nombre          AS nivel_nombre,
                   n.codigo          AS nivel_codigo,
                   CONCAT(pu.apellido_paterno, ', ', pu.nombres) AS tutor_nombre,
                   cc.id             AS cierre_id,
                   cc.ra_bloqueado_en,
                   cc.tutor_cerrado_en
            FROM secciones s
            INNER JOIN grados g    ON g.id  = s.grado_id
            INNER JOIN niveles n   ON n.id  = g.nivel_id
            INNER JOIN usuarios u  ON u.id  = s.tutor_id
            INNER JOIN personas pu ON pu.id = u.persona_id
            LEFT JOIN cierres_conducta cc
                ON  cc.seccion_id = s.id
                AND cc.periodo_id = ?
                AND cc.anulado_en IS NULL
            WHERE s.anio_id   = (SELECT anio_id FROM periodos WHERE id = ?)
              AND s.tutor_id IS NOT NULL
            ORDER BY n.id, g.numero, s.nombre
        ", [$periodoId, $periodoId]);

        // Completitud (esperados/calificados) reutilizando la query del indice de RA.
        $progreso = $this->getProgresoConductaPorSeccion($periodoId);
        foreach ($secciones as &$s) {
            $p = $progreso[(int) $s['seccion_id']] ?? ['esperados' => 0, 'calificados' => 0];
            $s['esperados']   = $p['esperados'];
            $s['calificados'] = $p['calificados'];
        }
        unset($s);

        return $secciones;
    }

    /** Cierre vigente (anulado_en IS NULL) de una seccion+periodo, o null. */
    public function getCierreVigente(int $seccionId, int $periodoId): ?array
    {
        return $this->queryOne("
            SELECT * FROM cierres_conducta
            WHERE seccion_id = ? AND periodo_id = ? AND anulado_en IS NULL
            ORDER BY id DESC LIMIT 1
        ", [$seccionId, $periodoId]);
    }

    /** Cierre vigente con los nombres de quien bloqueó/cerró (para el imprimible). */
    public function getCierreDetalle(int $seccionId, int $periodoId): ?array
    {
        return $this->queryOne("
            SELECT cc.*,
                   CONCAT(pr.apellido_paterno, ' ', pr.apellido_materno, ', ', pr.nombres) AS ra_nombre,
                   CONCAT(pt.apellido_paterno, ' ', pt.apellido_materno, ', ', pt.nombres) AS tutor_nombre
            FROM cierres_conducta cc
            INNER JOIN usuarios ur ON ur.id = cc.ra_bloqueado_por
            INNER JOIN personas pr ON pr.id = ur.persona_id
            LEFT JOIN usuarios ut  ON ut.id = cc.tutor_cerrado_por
            LEFT JOIN personas pt  ON pt.id = ut.persona_id
            WHERE cc.seccion_id = ? AND cc.periodo_id = ? AND cc.anulado_en IS NULL
            ORDER BY cc.id DESC LIMIT 1
        ", [$seccionId, $periodoId]);
    }

    /**
     * Etapa 1: RA bloquea/aprueba la seccion. Precondicion: completitud total.
     * @return array{ok:bool,mensaje:string}
     */
    public function bloquearRA(int $seccionId, int $periodoId, int $userId, int $totalCriterios): array
    {
        if ($this->getCierreVigente($seccionId, $periodoId)) {
            return ['ok' => false, 'mensaje' => 'La conducta de esta seccion ya esta bloqueada.'];
        }
        $c = $this->completitudSeccion($seccionId, $periodoId, $totalCriterios);
        if ($c['esperados'] === 0) {
            return ['ok' => false, 'mensaje' => 'No hay estudiantes matriculados en esta seccion.'];
        }
        if ($c['completos'] < $c['esperados']) {
            return ['ok' => false, 'mensaje' =>
                "Faltan estudiantes por calificar ({$c['completos']}/{$c['esperados']}). " .
                'Complete los criterios de todos antes de bloquear.'];
        }
        $ok = $this->execute("
            INSERT INTO cierres_conducta (seccion_id, periodo_id, ra_bloqueado_en, ra_bloqueado_por)
            VALUES (?, ?, NOW(), ?)
        ", [$seccionId, $periodoId, $userId]);

        return ['ok' => $ok, 'mensaje' => $ok ? 'Conducta bloqueada y aprobada.' : 'Error al bloquear.'];
    }

    /**
     * Etapa 2: el tutor cierra/aprueba. Precondicion: RA ya bloqueo.
     * @return array{ok:bool,mensaje:string}
     */
    public function cerrarTutor(int $seccionId, int $periodoId, int $userId): array
    {
        $cierre = $this->getCierreVigente($seccionId, $periodoId);
        if (!$cierre) {
            return ['ok' => false, 'mensaje' =>
                'Todavia los auxiliares academicos no han registrado sus calificaciones de conducta. ' .
                'Consulte con Registro Academico para mas informacion.'];
        }
        if ($cierre['tutor_cerrado_en']) {
            return ['ok' => false, 'mensaje' => 'La conducta de esta seccion ya fue cerrada por el tutor.'];
        }
        $ok = $this->execute("
            UPDATE cierres_conducta
            SET tutor_cerrado_en = NOW(), tutor_cerrado_por = ?
            WHERE id = ?
        ", [$userId, (int) $cierre['id']]);

        return ['ok' => $ok, 'mensaje' => $ok ? 'Conducta cerrada y aprobada.' : 'Error al cerrar.'];
    }

    /** Desbloqueo (admin/director): anula el cierre vigente con traza. */
    public function anularCierre(int $seccionId, int $periodoId, int $userId, string $motivo): bool
    {
        $c = $this->getCierreVigente($seccionId, $periodoId);
        if (!$c) {
            return false;
        }
        return $this->execute("
            UPDATE cierres_conducta
            SET anulado_en = NOW(), anulado_por = ?, motivo_anulacion = ?
            WHERE id = ?
        ", [$userId, $motivo, (int) $c['id']]);
    }

    // ── Etapa 2: nota del tutor ──────────────────────────────────

    /** Upsert de la nota del tutor (00-20). $nota === null limpia la nota. */
    public function guardarNotaTutor(int $matriculaId, int $periodoId, ?int $nota, int $userId): bool
    {
        if ($nota === null) {
            return $this->execute("
                UPDATE calificaciones_conducta
                SET nota_tutor = NULL, modificado_en = NOW()
                WHERE matricula_id = ? AND periodo_id = ?
            ", [$matriculaId, $periodoId]);
        }
        return $this->execute("
            INSERT INTO calificaciones_conducta
                (matricula_id, periodo_id, literal, nota_tutor, registrado_por)
            VALUES (?, ?, NULL, ?, ?)
            ON DUPLICATE KEY UPDATE
                nota_tutor     = VALUES(nota_tutor),
                registrado_por = VALUES(registrado_por),
                modificado_en  = NOW()
        ", [$matriculaId, $periodoId, $nota, $userId]);
    }

    /**
     * Alumnos de la seccion con nota RA derivada, nota del tutor y vista previa de
     * la final/literal. Para el panel del tutor.
     */
    public function getEstudiantesParaTutor(int $seccionId, int $periodoId, int $totalCriterios): array
    {
        $rows = $this->query("
            SELECT
                m.id AS matricula_id,
                CONCAT(p.apellido_paterno,' ',p.apellido_materno,', ',p.nombres) AS nombre_completo,
                cc.nota_tutor,
                cc.literal AS literal_legado,
                (SELECT COUNT(*) FROM conducta_respuestas r
                  WHERE r.matricula_id = m.id AND r.periodo_id = ? AND r.respuesta = 1) AS si,
                (SELECT COUNT(*) FROM conducta_respuestas r
                  WHERE r.matricula_id = m.id AND r.periodo_id = ?) AS respondidos
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas    p ON p.id = e.persona_id
            LEFT JOIN calificaciones_conducta cc ON cc.matricula_id = m.id AND cc.periodo_id = ?
            WHERE m.seccion_id = ?
              -- Mismo roster que el docente (getAlumnosSeccion): todos salvo el
              -- traslado de salida; retorno excluye la matricula que no aplica.
              AND m.tipo != 'trasladado'
              AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
              AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
              AND m.anio_id = (SELECT id FROM anios_academicos WHERE estado='activo' LIMIT 1)
            ORDER BY p.apellido_paterno, p.apellido_materno, p.nombres
        ", [$periodoId, $periodoId, $periodoId, $seccionId]);

        foreach ($rows as &$r) {
            $respondidos = (int) $r['respondidos'];
            $si          = (int) $r['si'];
            $nt          = $r['nota_tutor'] !== null ? (int) $r['nota_tutor'] : null;

            if ($respondidos > 0 && $totalCriterios > 0) {
                // Modelo nuevo (B2+): nota RA derivada de las respuestas Si/No.
                $notaRA = ($si / $totalCriterios) * 20;
                $final  = $this->promediar($notaRA, $nt);

                $r['nota_ra']       = (int) round($notaRA, 0, PHP_ROUND_HALF_UP);
                $r['nota_final']    = $final;
                $r['literal_final'] = nota_a_literal($final);
                $r['es_legado']     = false;
            } elseif ($r['literal_legado'] !== null) {
                // Legado (I Bimestre): literal directo, sin nota numerica.
                // Mismo criterio que componerLiteral() (la nota del tutor no aplica).
                $r['nota_ra']       = null;
                $r['nota_final']    = null;
                $r['literal_final'] = $r['literal_legado'];
                $r['es_legado']     = true;
            } else {
                // Sin datos para este periodo.
                $r['nota_ra']       = null;
                $r['nota_final']    = null;
                $r['literal_final'] = null;
                $r['es_legado']     = false;
            }
        }
        unset($r);
        return $rows;
    }

    // ── Calculo de la nota final ─────────────────────────────────

    /**
     * Promedio peso igual con redondeo A FAVOR del estudiante (.5 sube). Si el tutor
     * no califico ($notaTutor === null), la final es la nota RA sola.
     */
    private function promediar(float $notaRA, ?int $notaTutor): int
    {
        $valor = $notaTutor !== null ? ($notaRA + $notaTutor) / 2 : $notaRA;
        return (int) round($valor, 0, PHP_ROUND_HALF_UP);
    }

    // ── Lectura para boleta / panel del padre ────────────────────

    /**
     * [periodo_id => literal] de los periodos del año cuya conducta esta VISIBLE
     * (cierre vigente con ra_bloqueado). B2+ deriva el literal de la nota final;
     * B1 (legado) usa el literal directo. Usado en boleta y panel del padre.
     */
    public function getParaBoleta(int $matriculaId, int $anioId): array
    {
        $rows = $this->query("
            SELECT
                p.id                                   AS periodo_id,
                g.nivel_id                             AS nivel_id,
                cc.literal                             AS literal_legado,
                cc.nota_tutor                          AS nota_tutor,
                (SELECT COUNT(*) FROM conducta_respuestas r
                  WHERE r.matricula_id = m.id AND r.periodo_id = p.id AND r.respuesta = 1) AS si,
                (SELECT COUNT(*) FROM conducta_respuestas r
                  WHERE r.matricula_id = m.id AND r.periodo_id = p.id) AS respondidos,
                (SELECT COUNT(*) FROM criterios_conducta k
                  WHERE k.eliminado_en IS NULL
                    AND (k.nivel_id IS NULL OR k.nivel_id = g.nivel_id)) AS total_criterios,
                EXISTS(SELECT 1 FROM cierres_conducta z
                  WHERE z.seccion_id = m.seccion_id AND z.periodo_id = p.id
                    AND z.anulado_en IS NULL) AS visible
            FROM matriculas m
            INNER JOIN secciones s ON s.id = m.seccion_id
            INNER JOIN grados    g ON g.id = s.grado_id
            INNER JOIN periodos  p ON p.anio_id = ?
            LEFT JOIN calificaciones_conducta cc ON cc.matricula_id = m.id AND cc.periodo_id = p.id
            WHERE m.id = ?
        ", [$anioId, $matriculaId]);

        $result = [];
        foreach ($rows as $r) {
            if (!$r['visible']) {
                continue;
            }
            $literal = $this->componerLiteral(
                (int) $r['si'],
                (int) $r['respondidos'],
                (int) $r['total_criterios'],
                $r['nota_tutor'] !== null ? (int) $r['nota_tutor'] : null,
                $r['literal_legado']
            );
            if ($literal !== null) {
                $result[(int) $r['periodo_id']] = $literal;
            }
        }
        return $result;
    }

    /**
     * Versión por UNIÓN para el retorno de grado: fusiona la conducta de varias
     * matrículas del mismo estudiante (oficial + operativa) en un único mapa
     * [periodo_id => literal]. Las matrículas vienen ordenadas con la oficial al
     * final, de modo que gana ante un eventual choque en el mismo periodo.
     * Con una sola matrícula se comporta igual que getParaBoleta().
     */
    public function getParaBoletaUnion(array $matriculaIds, int $anioId): array
    {
        if (count($matriculaIds) <= 1) {
            return $this->getParaBoleta((int) ($matriculaIds[0] ?? 0), $anioId);
        }

        $out = [];
        foreach ($matriculaIds as $id) {
            $out = array_replace($out, $this->getParaBoleta((int) $id, $anioId));
        }
        return $out;
    }

    /** Conducta (literal) de un alumno en un solo periodo, o null si no es visible. */
    public function getParaPeriodo(int $matriculaId, int $periodoId): ?string
    {
        $r = $this->queryOne("
            SELECT
                g.nivel_id,
                cc.literal    AS literal_legado,
                cc.nota_tutor AS nota_tutor,
                (SELECT COUNT(*) FROM conducta_respuestas r
                  WHERE r.matricula_id = m.id AND r.periodo_id = ? AND r.respuesta = 1) AS si,
                (SELECT COUNT(*) FROM conducta_respuestas r
                  WHERE r.matricula_id = m.id AND r.periodo_id = ?) AS respondidos,
                (SELECT COUNT(*) FROM criterios_conducta k
                  WHERE k.eliminado_en IS NULL
                    AND (k.nivel_id IS NULL OR k.nivel_id = g.nivel_id)) AS total_criterios,
                EXISTS(SELECT 1 FROM cierres_conducta z
                  WHERE z.seccion_id = m.seccion_id AND z.periodo_id = ?
                    AND z.anulado_en IS NULL) AS visible
            FROM matriculas m
            INNER JOIN secciones s ON s.id = m.seccion_id
            INNER JOIN grados    g ON g.id = s.grado_id
            LEFT JOIN calificaciones_conducta cc ON cc.matricula_id = m.id AND cc.periodo_id = ?
            WHERE m.id = ?
        ", [$periodoId, $periodoId, $periodoId, $periodoId, $matriculaId]);

        if (!$r || !$r['visible']) {
            return null;
        }
        return $this->componerLiteral(
            (int) $r['si'],
            (int) $r['respondidos'],
            (int) $r['total_criterios'],
            $r['nota_tutor'] !== null ? (int) $r['nota_tutor'] : null,
            $r['literal_legado']
        );
    }

    /**
     * Compone el literal final para boleta: modelo nuevo (hay respuestas de RA) ->
     * deriva de la nota final; legado B1 (sin respuestas) -> literal directo.
     */
    private function componerLiteral(int $si, int $respondidos, int $total, ?int $notaTutor, ?string $literalLegado): ?string
    {
        if ($respondidos > 0 && $total > 0) {
            $notaRA = ($si / $total) * 20;
            return nota_a_literal($this->promediar($notaRA, $notaTutor));
        }
        return $literalLegado; // puede ser null si no hay nada
    }

    // ── Verificacion de edicion ──────────────────────────────────

    /** true si el periodo esta abierto para edicion. */
    public function periodoEditable(int $periodoId): bool
    {
        $p = $this->queryOne("SELECT estado, limite_notas FROM periodos WHERE id = ?", [$periodoId]);
        if (!$p || $p['estado'] !== 'activo') {
            return false;
        }
        if ($p['limite_notas'] && strtotime($p['limite_notas']) < time()) {
            return false;
        }
        return true;
    }
}
