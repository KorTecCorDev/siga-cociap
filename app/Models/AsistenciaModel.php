<?php

namespace App\Models;

class AsistenciaModel extends BaseModel
{
    // ── Consultas para el panel de registro ─────────────────────

    /** Secciones del año activo con info de nivel/grado para el índice. */
    public function listarSeccionesActivas(): array
    {
        return $this->query("
            SELECT
                s.id,
                s.nombre          AS seccion_nombre,
                g.nombre_display  AS grado_nombre,
                g.numero          AS grado_numero,
                n.nombre          AS nivel_nombre,
                n.id              AS nivel_id,
                a.id              AS anio_id,
                a.anio
            FROM secciones s
            INNER JOIN grados            g ON g.id = s.grado_id
            INNER JOIN niveles           n ON n.id = g.nivel_id
            INNER JOIN anios_academicos  a ON a.id = s.anio_id
            WHERE a.estado = 'activo'
              AND s.estado_nomina = 'aprobada'
            ORDER BY n.id, g.numero, s.nombre
        ");
    }

    /**
     * Periodos del año activo con flag de edición.
     * "editable" solo es true cuando el periodo está en estado 'activo'
     * y dentro del límite de notas. Mismo criterio que ConductaModel para
     * mantener coherencia entre módulos.
     *
     * ZONA HORARIA — el "ahora" lo calcula PHP (`America/Lima`, aplicado en
     * public/index.php) y viaja como parámetro preparado. NO usar NOW(): el
     * MySQL de produccion corre en UTC, 5 horas adelantado, y este flag se
     * apagaba 5 horas ANTES que el guard real de escritura
     * (`periodoEditable`, que compara con `time()` en PHP). Misma regla que
     * `PublicacionBoletaModel::ahora()`.
     */
    public function listarPeriodosActivos(): array
    {
        return $this->query("
            SELECT
                p.id,
                p.numero,
                p.nombre_display,
                p.estado,
                p.limite_notas,
                a.anio,
                (
                    p.estado = 'activo'
                    AND (p.limite_notas IS NULL OR ? <= p.limite_notas)
                ) AS editable
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE a.estado = 'activo'
            ORDER BY p.numero
        ", [date('Y-m-d H:i:s')]);
    }

    /**
     * Progreso de llenado de incidencias por sección para un periodo dado.
     * Una sección está "al X%" según cuántas matrículas tienen al menos
     * una fila guardada (incluso con todos los contadores en cero, porque
     * fue una acción consciente del operador).
     * Devuelve [seccion_id => ['esperados' => N, 'registrados' => M]].
     */
    public function getProgresoPorSeccion(int $periodoId): array
    {
        $rows = $this->query("
            SELECT
                s.id                         AS seccion_id,
                COUNT(DISTINCT m.id)         AS esperados,
                COUNT(DISTINCT i.matricula_id) AS registrados
            FROM secciones s
            INNER JOIN anios_academicos a ON a.id = s.anio_id AND a.estado = 'activo'
            LEFT JOIN matriculas m
                   ON m.seccion_id = s.id
                  AND m.anio_id    = s.anio_id
                  -- Mismo roster que getEstudiantesConIncidencias: los
                  -- 'esperados' tienen que contar exactamente a quienes
                  -- aparecen en la grilla, o el avance miente.
                  AND m.tipo NOT IN ('trasladado', 'retirado')
                  AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
                  AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
            LEFT JOIN inasistencias i
                   ON i.matricula_id = m.id
                  AND i.periodo_id   = ?
            WHERE s.estado_nomina = 'aprobada'
            GROUP BY s.id
        ", [$periodoId]);

        $mapa = [];
        foreach ($rows as $r) {
            $mapa[(int) $r['seccion_id']] = [
                'esperados'   => (int) $r['esperados'],
                'registrados' => (int) $r['registrados'],
            ];
        }
        return $mapa;
    }

    /**
     * Los 4 contadores de incidencias, en el orden en que se muestran.
     * PUNTO ÚNICO: lo usan el partial de la tabla, los totales y el imprimible.
     */
    public const CAMPOS = ['faltas', 'faltas_justificadas', 'tardanzas', 'tardanzas_justificadas'];

    /**
     * Suma los 4 contadores de un roster ya cargado, más cuántos tienen registro.
     *
     * Recibe la salida de `getEstudiantesConIncidencias` en vez de consultar otra
     * vez: el total tiene que ser el de LAS FILAS QUE SE PINTAN, no el de una
     * consulta paralela que podría aplicar otro roster. Es el mismo motivo por el
     * que el roster de asistencia es el de notas y no uno propio.
     *
     * @param  array $alumnos salida de getEstudiantesConIncidencias()
     * @return array{faltas:int,faltas_justificadas:int,tardanzas:int,tardanzas_justificadas:int,registrados:int}
     */
    public static function totalesIncidencias(array $alumnos): array
    {
        $totales = array_fill_keys(self::CAMPOS, 0) + ['registrados' => 0];

        foreach ($alumnos as $a) {
            $inc = $a['incidencias'] ?? [];
            foreach (self::CAMPOS as $campo) {
                $totales[$campo] += (int) ($inc[$campo] ?? 0);
            }
            if (!empty($inc['registrado'])) {
                $totales['registrados']++;
            }
        }
        return $totales;
    }

    /**
     * Estudiantes de una sección con sus incidencias del periodo activo.
     * Devuelve una fila por estudiante con los 4 contadores (en 0 si no hay registro).
     */
    public function getEstudiantesConIncidencias(int $seccionId, int $periodoId): array
    {
        $alumnos = $this->query("
            SELECT
                m.id  AS matricula_id,
                CONCAT(
                    p.apellido_paterno, ' ',
                    p.apellido_materno, ', ',
                    p.nombres
                )     AS nombre_completo,
                p.dni
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas    p ON p.id = e.persona_id
            WHERE m.seccion_id = ?
              -- MISMO ROSTER QUE LA GRILLA DEL DOCENTE (getAlumnosSeccion):
              -- el registro de asistencia debe cubrir exactamente a quien se
              -- evalua. NO se filtra por estado: 'pendiente' y 'desactivado'
              -- (baja administrativa por deuda) siguen asistiendo y deben
              -- tener donde registrarse. Los unicos excluidos son el traslado
              -- de salida y el retiro (migracion 045), que ya no asisten.
              AND m.tipo NOT IN ('trasladado', 'retirado')
              -- Retorno de grado: durante la nivelacion la asistencia se toma
              -- en la matricula OPERATIVA (el grado donde la estudiante esta
              -- fisicamente); tras revertir, vuelve a la OFICIAL.
              AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
              AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
              AND m.anio_id    = (SELECT id FROM anios_academicos WHERE estado = 'activo' LIMIT 1)
            ORDER BY " . orden_alfabetico('p') . "
        ", [$seccionId]);

        if (empty($alumnos)) {
            return [];
        }

        $matriculaIds = array_column($alumnos, 'matricula_id');
        $placeholders = implode(',', array_fill(0, count($matriculaIds), '?'));

        $registros = $this->query("
            SELECT
                matricula_id,
                faltas,
                faltas_justificadas,
                tardanzas,
                tardanzas_justificadas
            FROM inasistencias
            WHERE matricula_id IN ($placeholders)
              AND periodo_id = ?
        ", array_merge($matriculaIds, [$periodoId]));

        $index = [];
        foreach ($registros as $r) {
            $index[(int) $r['matricula_id']] = [
                'faltas'                 => (int) $r['faltas'],
                'faltas_justificadas'    => (int) $r['faltas_justificadas'],
                'tardanzas'              => (int) $r['tardanzas'],
                'tardanzas_justificadas' => (int) $r['tardanzas_justificadas'],
                'registrado'             => true,
            ];
        }

        foreach ($alumnos as &$a) {
            $mid = (int) $a['matricula_id'];
            $a['incidencias'] = $index[$mid] ?? [
                'faltas'                 => 0,
                'faltas_justificadas'    => 0,
                'tardanzas'              => 0,
                'tardanzas_justificadas' => 0,
                'registrado'             => false,
            ];
        }

        return $alumnos;
    }

    // ── Guardar / actualizar ─────────────────────────────────────

    /**
     * Upsert atómico de los 4 contadores para una matrícula y periodo.
     * Si no había fila, se crea con registrado_por; si ya existía, se
     * actualiza dejando registrado_por con el último usuario que escribió
     * y modificado_en con NOW().
     */
    public function guardar(
        int $matriculaId,
        int $periodoId,
        int $faltas,
        int $faltasJustificadas,
        int $tardanzas,
        int $tardanzasJustificadas,
        int $userId
    ): bool {
        return $this->execute("
            INSERT INTO inasistencias
                (matricula_id, periodo_id, faltas, faltas_justificadas,
                 tardanzas, tardanzas_justificadas, registrado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                faltas                  = VALUES(faltas),
                faltas_justificadas     = VALUES(faltas_justificadas),
                tardanzas               = VALUES(tardanzas),
                tardanzas_justificadas  = VALUES(tardanzas_justificadas),
                registrado_por          = VALUES(registrado_por),
                modificado_en           = NOW()
        ", [
            $matriculaId, $periodoId,
            $faltas, $faltasJustificadas,
            $tardanzas, $tardanzasJustificadas,
            $userId,
        ]);
    }

    // ── Consultas para boleta ────────────────────────────────────

    /**
     * Incidencias de una matrícula en un periodo específico (para la boleta).
     * Devuelve los 4 contadores en cero si no hay fila guardada, así la
     * boleta nunca falla por falta de datos.
     */
    public function getDelBimestre(int $matriculaId, int $periodoId): array
    {
        $row = $this->queryOne("
            SELECT faltas, faltas_justificadas, tardanzas, tardanzas_justificadas
            FROM inasistencias
            WHERE matricula_id = ? AND periodo_id = ?
        ", [$matriculaId, $periodoId]);

        if (!$row) {
            return [
                'faltas'                 => 0,
                'faltas_justificadas'    => 0,
                'tardanzas'              => 0,
                'tardanzas_justificadas' => 0,
            ];
        }

        return [
            'faltas'                 => (int) $row['faltas'],
            'faltas_justificadas'    => (int) $row['faltas_justificadas'],
            'tardanzas'              => (int) $row['tardanzas'],
            'tardanzas_justificadas' => (int) $row['tardanzas_justificadas'],
        ];
    }

    /**
     * Acumulado anual hasta el periodo dado (incluido).
     * Suma los contadores de todos los periodos del año académico cuyo
     * "numero" sea <= al numero del periodo de la boleta. Esto evita que
     * una boleta del I Bimestre incluya datos de bimestres posteriores.
     */
    public function getAcumuladoAnual(int $matriculaId, int $periodoIdHasta): array
    {
        $row = $this->queryOne("
            SELECT
                COALESCE(SUM(i.faltas), 0)                 AS faltas,
                COALESCE(SUM(i.faltas_justificadas), 0)    AS faltas_justificadas,
                COALESCE(SUM(i.tardanzas), 0)              AS tardanzas,
                COALESCE(SUM(i.tardanzas_justificadas), 0) AS tardanzas_justificadas
            FROM inasistencias i
            INNER JOIN periodos p_ref ON p_ref.id = ?
            INNER JOIN periodos p_row ON p_row.id = i.periodo_id
            WHERE i.matricula_id = ?
              AND p_row.anio_id  = p_ref.anio_id
              AND p_row.numero  <= p_ref.numero
        ", [$periodoIdHasta, $matriculaId]);

        return [
            'faltas'                 => (int) ($row['faltas']                 ?? 0),
            'faltas_justificadas'    => (int) ($row['faltas_justificadas']    ?? 0),
            'tardanzas'              => (int) ($row['tardanzas']              ?? 0),
            'tardanzas_justificadas' => (int) ($row['tardanzas_justificadas'] ?? 0),
        ];
    }

    // ── Variantes por UNIÓN para el retorno de grado ─────────────
    // Una estudiante en retorno tiene dos matrículas (oficial + operativa) y su
    // asistencia queda repartida por bimestre. La boleta (siempre la oficial)
    // suma ambas: por bimestre solo una tiene datos, así que la suma no infla.
    // Con una sola matrícula se comportan igual que los métodos base.

    public function getDelBimestreUnion(array $matriculaIds, int $periodoId): array
    {
        if (count($matriculaIds) <= 1) {
            return $this->getDelBimestre((int) ($matriculaIds[0] ?? 0), $periodoId);
        }
        return $this->sumarAsistencias(array_map(
            fn($id) => $this->getDelBimestre((int) $id, $periodoId),
            $matriculaIds
        ));
    }

    /**
     * ¿Alguna de estas matrículas tiene FILA de asistencia en el periodo?
     *
     * Es la pregunta que `getDelBimestre` no puede responder: devuelve los 4
     * contadores en CERO tanto cuando el registro dice "no faltó ningún día"
     * como cuando NADIE registró nada. Para la boleta esa diferencia es todo:
     * un cero se lee como dato real, y en el segundo caso no hay dato.
     *
     * ⚠️ Va por UNIÓN igual que `getDelBimestreUnion`, y no es un detalle: en un
     * retorno de grado la asistencia queda repartida por bimestre entre la
     * oficial y la operativa. Preguntando matrícula por matrícula, la boleta de
     * B1 de la operativa 692 —que no tiene fila propia— saldría en guion pese a
     * que su fila de B1 vive en la oficial 190. Medido: es exactamente el caso
     * del retorno #1, en los dos sentidos.
     *
     * Sin fila NO es lo mismo que "sin incidencias": el registro escribe una
     * fila por alumno aunque vaya en cero (medido: 197 filas así en B1 y 173 en
     * B2), y esas conservan su 0.
     */
    public function tieneRegistroUnion(array $matriculaIds, int $periodoId): bool
    {
        $ids = array_values(array_filter(array_map('intval', $matriculaIds)));
        if (empty($ids)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        return (bool) $this->queryOne("
            SELECT 1 AS hay
            FROM inasistencias
            WHERE periodo_id = ?
              AND matricula_id IN ({$placeholders})
            LIMIT 1
        ", array_merge([$periodoId], $ids));
    }

    public function getAcumuladoAnualUnion(array $matriculaIds, int $periodoIdHasta): array
    {
        if (count($matriculaIds) <= 1) {
            return $this->getAcumuladoAnual((int) ($matriculaIds[0] ?? 0), $periodoIdHasta);
        }
        return $this->sumarAsistencias(array_map(
            fn($id) => $this->getAcumuladoAnual((int) $id, $periodoIdHasta),
            $matriculaIds
        ));
    }

    /** Suma campo a campo los contadores de asistencia de varias matrículas. */
    private function sumarAsistencias(array $items): array
    {
        $keys = ['faltas', 'faltas_justificadas', 'tardanzas', 'tardanzas_justificadas'];
        $out  = array_fill_keys($keys, 0);
        foreach ($items as $it) {
            foreach ($keys as $k) {
                $out[$k] += (int) ($it[$k] ?? 0);
            }
        }
        return $out;
    }

    // ── Cierre (aprobacion y bloqueo) por seccion ────────────────
    // Espejo de una sola etapa del cierre de conducta: RA bloquea la
    // seccion y el registro queda de solo lectura. Sin fila = 0
    // incidencias (estado valido), asi que NO exige completitud.

    /** Cierre vigente (anulado_en IS NULL) de una seccion+periodo, o null. */
    public function getCierreVigente(int $seccionId, int $periodoId): ?array
    {
        return $this->queryOne("
            SELECT * FROM cierres_asistencia
            WHERE seccion_id = ? AND periodo_id = ? AND anulado_en IS NULL
            ORDER BY id DESC LIMIT 1
        ", [$seccionId, $periodoId]);
    }

    /** Cierre vigente con el nombre completo de quien bloqueó (para el imprimible). */
    public function getCierreDetalle(int $seccionId, int $periodoId): ?array
    {
        return $this->queryOne("
            SELECT ca.*,
                   CONCAT(pr.apellido_paterno, ' ', pr.apellido_materno, ', ', pr.nombres) AS ra_nombre
            FROM cierres_asistencia ca
            INNER JOIN usuarios ur ON ur.id = ca.ra_bloqueado_por
            INNER JOIN personas pr ON pr.id = ur.persona_id
            WHERE ca.seccion_id = ? AND ca.periodo_id = ? AND ca.anulado_en IS NULL
            ORDER BY ca.id DESC LIMIT 1
        ", [$seccionId, $periodoId]);
    }

    /**
     * RA bloquea/aprueba el registro de asistencia de la seccion.
     * @return array{ok:bool,mensaje:string}
     */
    public function bloquearRA(int $seccionId, int $periodoId, int $userId): array
    {
        if ($this->getCierreVigente($seccionId, $periodoId)) {
            return ['ok' => false, 'mensaje' => 'La asistencia de esta seccion ya esta bloqueada.'];
        }
        $ok = $this->execute("
            INSERT INTO cierres_asistencia (seccion_id, periodo_id, ra_bloqueado_en, ra_bloqueado_por)
            VALUES (?, ?, NOW(), ?)
        ", [$seccionId, $periodoId, $userId]);

        return ['ok' => $ok, 'mensaje' => $ok ? 'Asistencia bloqueada y aprobada.' : 'Error al bloquear.'];
    }

    /** Desbloqueo (director/admin): anula el cierre vigente con traza. */
    public function anularCierre(int $seccionId, int $periodoId, int $userId, string $motivo): bool
    {
        $c = $this->getCierreVigente($seccionId, $periodoId);
        if (!$c) {
            return false;
        }
        return $this->execute("
            UPDATE cierres_asistencia
            SET anulado_en = NOW(), anulado_por = ?, motivo_anulacion = ?
            WHERE id = ?
        ", [$userId, $motivo, (int) $c['id']]);
    }

    /**
     * Secciones del año del periodo con su estado de cierre de asistencia
     * (para el panel de bloqueos del director). Espejo del resumen de conducta,
     * sin requisito de tutor: la asistencia es de la seccion.
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
                   ca.id             AS cierre_id,
                   ca.ra_bloqueado_en
            FROM secciones s
            INNER JOIN grados g  ON g.id = s.grado_id
            INNER JOIN niveles n ON n.id = g.nivel_id
            LEFT JOIN cierres_asistencia ca
                ON  ca.seccion_id = s.id
                AND ca.periodo_id = ?
                AND ca.anulado_en IS NULL
            WHERE s.anio_id = (SELECT anio_id FROM periodos WHERE id = ?)
              AND s.estado_nomina = 'aprobada'
            ORDER BY n.id, g.numero, s.nombre
        ", [$periodoId, $periodoId]);

        // Avance de llenado (registrados/esperados) reusando la query del indice.
        $progreso = $this->getProgresoPorSeccion($periodoId);
        foreach ($secciones as &$s) {
            $p = $progreso[(int) $s['seccion_id']] ?? ['esperados' => 0, 'registrados' => 0];
            $s['esperados']   = $p['esperados'];
            $s['registrados'] = $p['registrados'];
        }
        unset($s);

        return $secciones;
    }

    /** seccion_id de una matricula, o null si no existe (gate de guardado). */
    public function seccionDeMatricula(int $matriculaId): ?int
    {
        $row = $this->queryOne("
            SELECT seccion_id FROM matriculas WHERE id = ?
        ", [$matriculaId]);
        return $row ? (int) $row['seccion_id'] : null;
    }

    /**
     * true si la matricula pertenece al ROSTER de asistencia (mismo criterio
     * que getEstudiantesConIncidencias). Guard del endpoint de guardado: la
     * grilla ya no pinta a los excluidos, pero una pestaña abierta desde antes
     * del cambio si podria enviarlos.
     */
    public function matriculaEnRoster(int $matriculaId): bool
    {
        $row = $this->queryOne("
            SELECT 1 AS ok
            FROM matriculas m
            WHERE m.id = ?
              AND m.tipo NOT IN ('trasladado', 'retirado')
              AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
              AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
        ", [$matriculaId]);

        return $row !== null;
    }

    // ── Verificación de edición ──────────────────────────────────

    /**
     * true si el periodo está abierto para edición de asistencia.
     * Mismo criterio que ConductaModel::periodoEditable.
     */
    public function periodoEditable(int $periodoId): bool
    {
        $p = $this->queryOne("
            SELECT estado, limite_notas
            FROM periodos WHERE id = ?
        ", [$periodoId]);

        if (!$p || $p['estado'] !== 'activo') {
            return false;
        }
        if ($p['limite_notas'] && strtotime($p['limite_notas']) < time()) {
            return false;
        }
        return true;
    }
}
