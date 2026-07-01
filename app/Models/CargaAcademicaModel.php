<?php

namespace App\Models;

class CargaAcademicaModel extends BaseModel
{
    protected string $table = 'cargas_academicas';

    private const ORDEN_DIAS = "FIELD(bh.dia_semana,'lunes','martes','miercoles','jueves','viernes')";

    // ── Listados para selects ─────────────────────────────────

    public function listarSecciones(): array
    {
        return $this->query("
            SELECT
                s.id,
                s.nombre         AS seccion,
                s.es_unidocente,
                s.tutor_id,
                g.nombre_display AS grado,
                g.id             AS grado_id,
                n.nombre         AS nivel,
                n.id             AS nivel_id,
                a.anio,
                a.id             AS anio_id
            FROM secciones s
            INNER JOIN grados g            ON g.id = s.grado_id
            INNER JOIN niveles n            ON n.id = g.nivel_id
            INNER JOIN anios_academicos a   ON a.id = s.anio_id
            WHERE a.estado IN ('planificado','activo')
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

    public function listarAreas(): array
    {
        return $this->query("
            SELECT a.id, a.nombre, a.tipo, n.id AS nivel_id, n.nombre AS nivel_nombre
            FROM areas a
            INNER JOIN niveles n ON n.id = a.nivel_id
            WHERE a.activa = 1
              AND a.tipo != 'transversal'
            ORDER BY n.id, a.orden
        ");
    }

    public function listarSubareas(): array
    {
        return $this->query("
            SELECT sa.id, sa.nombre, sa.area_id
            FROM subareas sa
            INNER JOIN areas a ON a.id = sa.area_id
            WHERE a.activa = 1
            ORDER BY sa.area_id, sa.orden
        ");
    }

    // ── Listado principal (con resumen de horario) ────────────

    public function listarTodas(): array
    {
        return $this->query("
            SELECT
                ca.id,
                ca.horas_semanales,
                ca.estado,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres           AS docente_nombres,
                s.nombre            AS seccion_nombre,
                g.nombre_display    AS grado_nombre,
                n.nombre            AS nivel_nombre,
                an.anio,
                CASE
                    WHEN ca.area_id IS NOT NULL THEN a_dir.nombre
                    ELSE a_via.nombre
                END                 AS area_nombre,
                sa.nombre           AS subarea_nombre,
                GROUP_CONCAT(
                    CONCAT(
                        UPPER(LEFT(bh.dia_semana,1)),
                        SUBSTRING(bh.dia_semana,2),
                        ' ',
                        TIME_FORMAT(bh.hora_inicio,'%H:%i'),
                        '-',
                        TIME_FORMAT(bh.hora_fin,'%H:%i')
                    )
                    ORDER BY " . self::ORDEN_DIAS . ", bh.hora_inicio
                    SEPARATOR ' | '
                )                   AS horario_resumen
            FROM cargas_academicas ca
            INNER JOIN usuarios u         ON u.id  = ca.docente_id
            INNER JOIN personas p         ON p.id  = u.persona_id
            INNER JOIN secciones s        ON s.id  = ca.seccion_id
            INNER JOIN grados g           ON g.id  = s.grado_id
            INNER JOIN niveles n          ON n.id  = g.nivel_id
            INNER JOIN anios_academicos an ON an.id = ca.anio_id
            LEFT JOIN areas a_dir         ON a_dir.id = ca.area_id
            LEFT JOIN subareas sa         ON sa.id    = ca.subarea_id
            LEFT JOIN areas a_via         ON a_via.id = sa.area_id
            LEFT JOIN sesiones_horario sh ON sh.carga_id  = ca.id
            LEFT JOIN bloques_horario bh  ON bh.id        = sh.bloque_id
            -- Oculta las cargas transversales (modelo viejo) — coherente con
            -- listarPorSeccion/listarSeccionesConCargas.
            WHERE COALESCE(a_dir.tipo, a_via.tipo) != 'transversal'
            GROUP BY
                ca.id, ca.horas_semanales, ca.estado,
                p.apellido_paterno, p.apellido_materno, p.nombres,
                s.nombre, g.nombre_display, n.nombre, an.anio,
                a_dir.nombre, a_via.nombre, sa.nombre
            ORDER BY an.anio DESC, n.id, g.numero, s.nombre
        ");
    }

    public function listarSeccionesConCargas(): array
    {
        return $this->query("
            SELECT
                s.id,
                s.nombre            AS seccion_nombre,
                g.nombre_display    AS grado_nombre,
                g.numero            AS grado_numero,
                n.id                AS nivel_id,
                n.nombre            AS nivel_nombre,
                an.anio,
                COUNT(ca.id)                    AS total_cargas,
                COALESCE(SUM(ca.estado = 'activa'), 0) AS cargas_activas
            FROM secciones s
            INNER JOIN grados g             ON g.id  = s.grado_id
            INNER JOIN niveles n            ON n.id  = g.nivel_id
            INNER JOIN anios_academicos an  ON an.id = s.anio_id
            -- Excluye las cargas transversales (modelo viejo) del conteo: ya no
            -- se gestionan en /director/cargas (ver listarPorSeccion). Quedan
            -- inactivas en BD como respaldo de las boletas del I Bimestre.
            LEFT  JOIN cargas_academicas ca
                   ON ca.seccion_id = s.id
                   AND ca.id NOT IN (
                       SELECT ca_t.id
                       FROM cargas_academicas ca_t
                       INNER JOIN areas a_t ON a_t.id = ca_t.area_id
                       WHERE a_t.tipo = 'transversal'
                   )
            WHERE an.estado IN ('planificado','activo')
            GROUP BY s.id, s.nombre, g.nombre_display, g.numero,
                     n.id, n.nombre, an.anio
            ORDER BY an.anio DESC, n.id, g.numero, s.nombre
        ");
    }

    public function findSeccion(int $id): ?array
    {
        return $this->queryOne("
            SELECT
                s.id,
                s.nombre            AS seccion_nombre,
                s.es_unidocente,
                s.tutor_id,
                pt.apellido_paterno AS tutor_paterno,
                pt.apellido_materno AS tutor_materno,
                pt.nombres          AS tutor_nombres,
                g.nombre_display    AS grado_nombre,
                g.numero            AS grado_numero,
                n.nombre            AS nivel_nombre,
                an.anio
            FROM secciones s
            INNER JOIN grados g            ON g.id  = s.grado_id
            INNER JOIN niveles n           ON n.id  = g.nivel_id
            INNER JOIN anios_academicos an ON an.id = s.anio_id
            LEFT  JOIN usuarios ut         ON ut.id = s.tutor_id
            LEFT  JOIN personas pt         ON pt.id = ut.persona_id
            WHERE s.id = ?
            LIMIT 1
        ", [$id]);
    }

    public function listarPorSeccion(int $seccionId): array
    {
        return $this->query("
            SELECT
                ca.id,
                ca.horas_semanales,
                ca.estado,
                ca.docente_id,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres           AS docente_nombres,
                s.nombre            AS seccion_nombre,
                g.nombre_display    AS grado_nombre,
                n.nombre            AS nivel_nombre,
                an.anio,
                COALESCE(ca.area_id, sa.area_id) AS area_real_id,
                CASE
                    WHEN ca.area_id IS NOT NULL THEN a_dir.nombre
                    ELSE a_via.nombre
                END                 AS area_nombre,
                sa.nombre           AS subarea_nombre,
                sa.orden            AS subarea_orden,
                GROUP_CONCAT(
                    CONCAT(
                        UPPER(LEFT(bh.dia_semana,1)),
                        SUBSTRING(bh.dia_semana,2),
                        ' ',
                        TIME_FORMAT(bh.hora_inicio,'%H:%i'),
                        '-',
                        TIME_FORMAT(bh.hora_fin,'%H:%i')
                    )
                    ORDER BY " . self::ORDEN_DIAS . ", bh.hora_inicio
                    SEPARATOR ' | '
                )                   AS horario_resumen
            FROM cargas_academicas ca
            INNER JOIN usuarios u          ON u.id   = ca.docente_id
            INNER JOIN personas p          ON p.id   = u.persona_id
            INNER JOIN secciones s         ON s.id   = ca.seccion_id
            INNER JOIN grados g            ON g.id   = s.grado_id
            INNER JOIN niveles n           ON n.id   = g.nivel_id
            INNER JOIN anios_academicos an ON an.id  = ca.anio_id
            LEFT  JOIN areas a_dir         ON a_dir.id = ca.area_id
            LEFT  JOIN subareas sa         ON sa.id    = ca.subarea_id
            LEFT  JOIN areas a_via         ON a_via.id = sa.area_id
            LEFT  JOIN sesiones_horario sh ON sh.carga_id = ca.id
            LEFT  JOIN bloques_horario bh  ON bh.id       = sh.bloque_id
            WHERE ca.seccion_id = ?
              -- Las cargas transversales (TIC/GAMA) son del modelo VIEJO: hoy
              -- cada docente registra sus transversales dentro de su propia
              -- carga y el tutor cierra en /docente/tutoria. NO se gestionan
              -- aquí (verlas con botón Activar revive el flujo fantasma). Sus
              -- registros permanecen inactivos en BD como respaldo de las
              -- boletas del I Bimestre — solo se ocultan de esta vista.
              AND COALESCE(a_dir.tipo, a_via.tipo) != 'transversal'
            GROUP BY
                ca.id, ca.horas_semanales, ca.estado, ca.docente_id,
                p.apellido_paterno, p.apellido_materno, p.nombres,
                s.nombre, g.nombre_display, n.nombre, an.anio,
                ca.area_id, sa.area_id, a_dir.nombre, a_via.nombre,
                sa.nombre, sa.orden
            -- Áreas por nombre y, dentro del área, subáreas por su orden
            -- curricular (la vista agrupa por área en secciones unidocentes).
            ORDER BY COALESCE(a_dir.nombre, a_via.nombre), sa.orden
        ", [$seccionId]);
    }

    public function findById(int $id): ?array
    {
        return $this->queryOne("
            SELECT
                ca.*,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres           AS docente_nombres,
                s.nombre            AS seccion_nombre,
                g.nombre_display    AS grado_nombre,
                g.id                AS grado_id,
                n.nombre            AS nivel_nombre,
                n.id                AS nivel_id,
                an.anio,
                CASE
                    WHEN ca.area_id IS NOT NULL THEN a_dir.nombre
                    ELSE a_via.nombre
                END                 AS area_nombre,
                CASE
                    WHEN ca.area_id IS NOT NULL THEN a_dir.id
                    ELSE a_via.id
                END                 AS area_real_id,
                CASE
                    WHEN ca.area_id IS NOT NULL THEN a_dir.tipo
                    ELSE a_via.tipo
                END                 AS area_tipo,
                sa.nombre           AS subarea_nombre
            FROM cargas_academicas ca
            INNER JOIN usuarios u         ON u.id  = ca.docente_id
            INNER JOIN personas p         ON p.id  = u.persona_id
            INNER JOIN secciones s        ON s.id  = ca.seccion_id
            INNER JOIN grados g           ON g.id  = s.grado_id
            INNER JOIN niveles n          ON n.id  = g.nivel_id
            INNER JOIN anios_academicos an ON an.id = ca.anio_id
            LEFT JOIN areas a_dir         ON a_dir.id = ca.area_id
            LEFT JOIN subareas sa         ON sa.id    = ca.subarea_id
            LEFT JOIN areas a_via         ON a_via.id = sa.area_id
            WHERE ca.id = ?
            LIMIT 1
        ", [$id]);
    }

    public function getSesionesDeCarga(int $cargaId): array
    {
        return $this->query("
            SELECT
                sh.id,
                sh.bloque_id,
                sh.seccion_id,
                bh.config_id,
                bh.dia_semana,
                TIME_FORMAT(bh.hora_inicio,'%H:%i') AS hora_inicio,
                TIME_FORMAT(bh.hora_fin,'%H:%i')    AS hora_fin,
                bh.numero_bloque
            FROM sesiones_horario sh
            INNER JOIN bloques_horario bh ON bh.id = sh.bloque_id
            WHERE sh.carga_id = ?
            ORDER BY " . self::ORDEN_DIAS . ", bh.hora_inicio
        ", [$cargaId]);
    }

    // ── Lógica de horario ─────────────────────────────────────

    public function getOrCreateConfiguracion(int $anioId): int
    {
        $config = $this->queryOne(
            "SELECT id FROM configuracion_horario WHERE anio_id = ? LIMIT 1",
            [$anioId]
        );
        if ($config) {
            return (int) $config['id'];
        }
        $this->execute(
            "INSERT INTO configuracion_horario (anio_id, duracion_hora_min, hora_inicio_clases)
             VALUES (?, 50, '07:45:00')",
            [$anioId]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Duración de la hora académica (en minutos) según la configuración de
     * horario. Fallback 45 si la configuración no existe o trae 0/NULL.
     */
    public function getDuracionHoraMin(int $configId): int
    {
        $cfg = $this->queryOne(
            "SELECT duracion_hora_min FROM configuracion_horario WHERE id = ? LIMIT 1",
            [$configId]
        );
        $duracion = (int) ($cfg['duracion_hora_min'] ?? 0);
        return $duracion > 0 ? $duracion : 45;
    }

    public function getOrCreateBloque(
        int    $configId,
        string $dia,
        string $horaInicio,
        string $horaFin
    ): int {
        $bloque = $this->queryOne("
            SELECT id FROM bloques_horario
            WHERE config_id = ? AND dia_semana = ? AND hora_inicio = ? AND hora_fin = ?
            LIMIT 1
        ", [$configId, $dia, $horaInicio, $horaFin]);

        if ($bloque) {
            return (int) $bloque['id'];
        }

        $max = $this->queryOne("
            SELECT COALESCE(MAX(numero_bloque),0) AS max_num
            FROM bloques_horario
            WHERE config_id = ? AND dia_semana = ?
        ", [$configId, $dia]);

        $numeroBloque = (int) ($max['max_num'] ?? 0) + 1;

        $this->execute("
            INSERT INTO bloques_horario (config_id, dia_semana, numero_bloque, hora_inicio, hora_fin)
            VALUES (?, ?, ?, ?, ?)
        ", [$configId, $dia, $numeroBloque, $horaInicio, $horaFin]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Verifica SOLAPES de horario (no solo bloque exacto) para los rangos
     * propuestos. Por cada sesión {dia, hora_inicio, hora_fin, config_id} busca
     * si la SECCIÓN o el DOCENTE ya tienen otra clase que se cruce en el tiempo
     * ese mismo día. Solape ESTRICTO: dos rangos chocan solo si se traslapan de
     * verdad; los contiguos (fin == inicio del otro) NO cuentan como conflicto.
     * Se acota al mismo config_id (año) para no comparar entre años distintos.
     * Al editar, excluye las sesiones de la propia carga.
     *
     * Cada $sesion debe traer: dia, hora_inicio ('HH:MM'), hora_fin ('HH:MM'),
     * seccion_id, docente_id, config_id.
     */
    public function verificarSolapes(
        array $sesiones,
        ?int  $excluirCargaId = null,
        array $tipos = ['seccion', 'docente']
    ): array {
        $conflictos = [];

        foreach ($sesiones as $s) {
            foreach ($tipos as $tipo) {
                $col      = $tipo === 'seccion' ? 'seccion_id' : 'docente_id';
                $filterId = $tipo === 'seccion' ? $s['seccion_id'] : $s['docente_id'];
                $sql = "
                    SELECT bh.dia_semana,
                           TIME_FORMAT(bh.hora_inicio,'%H:%i') AS hora_inicio,
                           TIME_FORMAT(bh.hora_fin,'%H:%i')    AS hora_fin,
                           '{$tipo}' AS tipo_conflicto
                    FROM sesiones_horario sh
                    INNER JOIN bloques_horario bh ON bh.id = sh.bloque_id
                    WHERE sh.{$col}       = ?
                      AND bh.config_id    = ?
                      AND bh.dia_semana   = ?
                      AND ?               < bh.hora_fin
                      AND bh.hora_inicio  < ?
                ";
                $params = [$filterId, $s['config_id'], $s['dia'], $s['hora_inicio'], $s['hora_fin']];

                if ($excluirCargaId !== null) {
                    $sql    .= " AND sh.carga_id != ?";
                    $params[] = $excluirCargaId;
                }

                $conf = $this->queryOne($sql . " LIMIT 1", $params);
                if ($conf) {
                    $conflictos[] = $conf;
                    break; // un conflicto por rango es suficiente
                }
            }
        }

        return $conflictos;
    }

    // ── Escritura transaccional ───────────────────────────────

    public function crearConHorario(array $datosCarga, array $sesiones): int
    {
        $this->beginTransaction();
        try {
            $cols = implode(', ', array_keys($datosCarga));
            $ph   = implode(', ', array_fill(0, count($datosCarga), '?'));
            $this->execute(
                "INSERT INTO cargas_academicas ({$cols}) VALUES ({$ph})",
                array_values($datosCarga)
            );
            $cargaId = (int) $this->db->lastInsertId();

            foreach ($sesiones as $s) {
                $this->execute("
                    INSERT INTO sesiones_horario (carga_id, bloque_id, seccion_id, docente_id)
                    VALUES (?, ?, ?, ?)
                ", [$cargaId, $s['bloque_id'], $s['seccion_id'], $s['docente_id']]);
            }

            $this->commit();
            return $cargaId;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function actualizarConHorario(
        int   $cargaId,
        array $datosCarga,
        array $sesiones
    ): void {
        $this->beginTransaction();
        try {
            $set = implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($datosCarga)));
            $this->execute(
                "UPDATE cargas_academicas SET {$set} WHERE id = ?",
                [...array_values($datosCarga), $cargaId]
            );

            $this->execute("DELETE FROM sesiones_horario WHERE carga_id = ?", [$cargaId]);

            foreach ($sesiones as $s) {
                $this->execute("
                    INSERT INTO sesiones_horario (carga_id, bloque_id, seccion_id, docente_id)
                    VALUES (?, ?, ?, ?)
                ", [$cargaId, $s['bloque_id'], $s['seccion_id'], $s['docente_id']]);
            }

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function toggleEstado(int $id): void
    {
        $this->execute("
            UPDATE cargas_academicas
            SET estado = IF(estado = 'activa', 'inactiva', 'activa')
            WHERE id = ?
        ", [$id]);
    }

    /**
     * ¿La carga tiene trabajo (criterios vivos o calificaciones agregadas) en el
     * bimestre ACTIVO? Base del blindaje al desactivar: si lo hay, desactivar
     * exige motivo. No considera bimestres cerrados (ya son oficiales/inmutables).
     */
    public function tieneTrabajoEnPeriodoActivo(int $cargaId): bool
    {
        return $this->queryOne("
            SELECT 1
            FROM periodos p
            WHERE p.estado = 'activo'
              AND (
                  EXISTS (
                      SELECT 1 FROM criterios cr
                      WHERE cr.carga_id     = ?
                        AND cr.periodo_id   = p.id
                        AND cr.eliminado_en IS NULL
                  )
                  OR EXISTS (
                      SELECT 1 FROM calificaciones cal
                      WHERE cal.carga_id   = ?
                        AND cal.periodo_id = p.id
                  )
              )
            LIMIT 1
        ", [$cargaId, $cargaId]) !== null;
    }

    public function existeCarga(
        int  $seccionId,
        ?int $subareaId,
        ?int $areaId,
        ?int $excluirId = null
    ): bool {
        if ($subareaId !== null) {
            $sql    = "SELECT id FROM cargas_academicas WHERE seccion_id = ? AND subarea_id = ?";
            $params = [$seccionId, $subareaId];
        } else {
            $sql    = "SELECT id FROM cargas_academicas WHERE seccion_id = ? AND area_id = ?";
            $params = [$seccionId, $areaId];
        }
        if ($excluirId !== null) {
            $sql    .= " AND id != ?";
            $params[] = $excluirId;
        }
        return $this->queryOne($sql . " LIMIT 1", $params) !== null;
    }
}
