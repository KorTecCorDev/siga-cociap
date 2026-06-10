<?php

namespace App\Models;

/**
 * AnioAcademicoModel
 * Gestiona años académicos y sus bimestres (periodos):
 * creación, activación, cierre y los indicadores de cierre de cada bimestre.
 */
class AnioAcademicoModel extends BaseModel
{
    protected string $table = 'anios_academicos';

    /**
     * Plantilla de bimestres por defecto para un año nuevo.
     * Fechas referenciales del calendario escolar peruano (editables después).
     * [numero, nombre_display, inicio (MM-DD), fin (MM-DD)]
     */
    private const BIMESTRES_DEFAULT = [
        [1, 'I Bimestre',   '03-01', '05-15'],
        [2, 'II Bimestre',  '05-18', '07-17'],
        [3, 'III Bimestre', '08-03', '10-02'],
        [4, 'IV Bimestre',  '10-05', '12-18'],
    ];

    // ── Años ──────────────────────────────────────────────────

    /** Lista todos los años con el conteo de bimestres por estado. */
    public function listarAnios(): array
    {
        return $this->query("
            SELECT
                a.*,
                (SELECT COUNT(*) FROM periodos p WHERE p.anio_id = a.id)                          AS total_bimestres,
                (SELECT COUNT(*) FROM periodos p WHERE p.anio_id = a.id AND p.estado = 'activo')   AS bimestres_activos,
                (SELECT COUNT(*) FROM periodos p WHERE p.anio_id = a.id AND p.estado = 'cerrado')  AS bimestres_cerrados
            FROM anios_academicos a
            ORDER BY a.anio DESC
        ");
    }

    /** Verifica si ya existe un año con ese valor. */
    public function existeAnio(int $anio): bool
    {
        return $this->findBy('anio', $anio) !== null;
    }

    /**
     * Crea un año académico nuevo con sus 4 bimestres por defecto.
     * Solo genera el año y los periodos: no toca secciones ni cargas.
     * Retorna el ID del año creado.
     */
    public function crearAnio(int $anio): int
    {
        $this->beginTransaction();
        try {
            $this->execute("
                INSERT INTO anios_academicos (anio, fecha_inicio, fecha_fin, estado)
                VALUES (?, ?, ?, 'planificado')
            ", [
                $anio,
                sprintf('%d-03-01', $anio),
                sprintf('%d-12-18', $anio),
            ]);
            $anioId = (int) $this->db->lastInsertId();

            foreach (self::BIMESTRES_DEFAULT as [$numero, $nombre, $inicio, $fin]) {
                $this->execute("
                    INSERT INTO periodos
                        (anio_id, numero, tipo, nombre_display,
                         fecha_inicio, fecha_fin, limite_notas, estado)
                    VALUES (?, ?, 'bimestre', ?, ?, ?, NULL, 'pendiente')
                ", [
                    $anioId,
                    $numero,
                    $nombre,
                    sprintf('%d-%s', $anio, $inicio),
                    sprintf('%d-%s', $anio, $fin),
                ]);
            }

            $this->commit();
            return $anioId;
        } catch (\Exception $e) {
            $this->rollback();
            log_error('Error creando año académico', [
                'anio'  => $anio,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Activa un año académico. Solo puede haber uno activo a la vez:
     * cualquier otro año en estado 'activo' se cierra.
     */
    public function activarAnio(int $id): void
    {
        $this->beginTransaction();
        try {
            $this->execute("
                UPDATE anios_academicos
                SET estado = 'cerrado'
                WHERE estado = 'activo' AND id != ?
            ", [$id]);

            $this->execute("
                UPDATE anios_academicos SET estado = 'activo' WHERE id = ?
            ", [$id]);

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            log_error('Error activando año académico', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /** Cierra un año académico. */
    public function cerrarAnio(int $id): bool
    {
        return $this->execute("
            UPDATE anios_academicos SET estado = 'cerrado' WHERE id = ?
        ", [$id]);
    }

    // ── Bimestres (periodos) ──────────────────────────────────

    /** Lista los bimestres de un año, ordenados por número. */
    public function getPeriodos(int $anioId): array
    {
        return $this->query("
            SELECT p.*
            FROM periodos p
            WHERE p.anio_id = ?
            ORDER BY p.numero
        ", [$anioId]);
    }

    /** Obtiene un bimestre con datos de su año. */
    public function getPeriodo(int $id): ?array
    {
        return $this->queryOne("
            SELECT p.*, a.anio, a.estado AS anio_estado
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
        ", [$id]);
    }

    /** Indica si el año tiene algún bimestre activo distinto del indicado. */
    public function tieneBimestreActivo(int $anioId, int $exceptoPeriodoId = 0): bool
    {
        $fila = $this->queryOne("
            SELECT id FROM periodos
            WHERE anio_id = ? AND estado = 'activo' AND id != ?
            LIMIT 1
        ", [$anioId, $exceptoPeriodoId]);

        return $fila !== null;
    }

    /** Actualiza las fechas y la fecha límite de notas de un bimestre. */
    public function actualizarFechasPeriodo(
        int $id,
        string $fechaInicio,
        string $fechaFin,
        ?string $limiteNotas
    ): bool {
        return $this->execute("
            UPDATE periodos
            SET fecha_inicio = ?, fecha_fin = ?, limite_notas = ?
            WHERE id = ?
        ", [$fechaInicio, $fechaFin, $limiteNotas, $id]);
    }

    /** Cambia el estado de un bimestre. */
    public function setEstadoPeriodo(int $id, string $estado): bool
    {
        return $this->execute("
            UPDATE periodos SET estado = ? WHERE id = ?
        ", [$estado, $id]);
    }

    /**
     * Bloquea automáticamente todas las competencias del periodo que aún
     * no estén bloqueadas (cierre forzado del bimestre). Se bloquean con lo
     * que tengan, aunque no tengan notas. Idempotente (INSERT IGNORE sobre
     * la clave única uq_bloqueo). Retorna cuántas se bloquearon en esta llamada.
     */
    public function bloquearCompetenciasPendientes(int $periodoId, int $usuarioId): int
    {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO bloqueos_competencia
                (carga_id, competencia_id, periodo_id, bloqueado_por)
            SELECT ca.id, comp.id, ?, ?
            FROM cargas_academicas ca
            INNER JOIN competencias comp ON (
                (ca.subarea_id IS NOT NULL AND comp.subarea_id = ca.subarea_id)
                OR
                (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL AND comp.area_id = ca.area_id)
            )
            WHERE ca.estado  = 'activa'
              AND ca.anio_id = (SELECT anio_id FROM periodos WHERE id = ?)
        ");
        $stmt->execute([$periodoId, $usuarioId, $periodoId]);
        return $stmt->rowCount();
    }

    /**
     * Elimina los bloqueos "fantasma" de un periodo: los que no tienen
     * ninguna calificación detrás. Estos solo pueden provenir del cierre
     * forzado (bloquearCompetenciasPendientes bloquea TODAS las competencias
     * del año, tengan notas o no). Al reabrir un bimestre se limpian para
     * que los docentes recuperen el acceso; los bloqueos con notas reales
     * (aprobados por el docente) se conservan. Retorna cuántos se eliminaron.
     */
    public function eliminarBloqueosSinNotas(int $periodoId): int
    {
        $stmt = $this->db->prepare("
            DELETE bc FROM bloqueos_competencia bc
            WHERE bc.periodo_id = ?
              AND NOT EXISTS (
                  SELECT 1 FROM calificaciones cal
                  WHERE cal.carga_id       = bc.carga_id
                    AND cal.competencia_id = bc.competencia_id
                    AND cal.periodo_id     = bc.periodo_id
              )
        ");
        $stmt->execute([$periodoId]);
        return $stmt->rowCount();
    }

    /**
     * Registra una reapertura de bimestre en la bitácora de auditoría.
     * El motivo es obligatorio (lo valida el controlador). Guarda también
     * cuántos bloqueos sin notas se liberaron en esa reapertura.
     */
    public function registrarReapertura(
        int $periodoId,
        string $motivo,
        int $usuarioId,
        int $bloqueosLiberados
    ): bool {
        return $this->execute("
            INSERT INTO reaperturas_periodo
                (periodo_id, motivo, bloqueos_liberados, reabierto_por)
            VALUES (?, ?, ?, ?)
        ", [$periodoId, $motivo, $bloqueosLiberados, $usuarioId]);
    }

    /** Historial de reaperturas de un bimestre, de la más reciente a la más antigua. */
    public function getReaperturas(int $periodoId): array
    {
        return $this->query("
            SELECT
                rp.*,
                CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres) AS reabierto_por_nombre
            FROM reaperturas_periodo rp
            INNER JOIN usuarios u ON u.id = rp.reabierto_por
            INNER JOIN personas p ON p.id = u.persona_id
            WHERE rp.periodo_id = ?
            ORDER BY rp.reabierto_en DESC
        ", [$periodoId]);
    }

    // ── Indicadores de cierre ─────────────────────────────────

    /**
     * Indicadores de cierre de un bimestre, calculados en tiempo real:
     *  - por cada grado: primer puesto y los 2 de menor rendimiento
     *  - top de docentes que bloquearon todas sus competencias más rápido
     */
    public function getStatsCierre(int $periodoId): array
    {
        $grados   = $this->getGradosConCalificaciones($periodoId);
        $porGrado = [];

        foreach ($grados as $grado) {
            $ranking = $this->getRankingGrado((int) $grado['id'], $periodoId);
            if (empty($ranking)) {
                continue;
            }

            $mejor  = $ranking[0];
            // Los 2 de menor rendimiento, excluyendo al primer puesto.
            $peores = array_values(array_filter(
                array_slice($ranking, -2),
                fn($e) => (int) $e['matricula_id'] !== (int) $mejor['matricula_id']
            ));

            $porGrado[] = [
                'grado'  => $grado,
                'mejor'  => $mejor,
                'peores' => $peores,
                'total'  => count($ranking),
            ];
        }

        return [
            'por_grado' => $porGrado,
            'docentes'  => $this->getDocentesMasRapidos($periodoId),
        ];
    }

    /**
     * Indicadores globales del bimestre, separados por nivel (Primaria/Secundaria):
     *  - distribución de literales AD/A/B/C (contando cada calificación de competencia)
     *  - % en logro (AD+A) vs en proceso/inicio (B+C)
     *  - estudiantes en riesgo (promedio general en C)
     *  - histograma de estudiantes según cuántas competencias tienen en C
     * Excluye competencias transversales, igual que el ranking.
     */
    public function getResumenBimestre(int $periodoId): array
    {
        // 1) Distribución de literales a nivel de calificación.
        $dist = $this->query("
            SELECT
                n.id     AS nivel_id,
                n.nombre AS nivel_nombre,
                n.codigo AS nivel_codigo,
                SUM(cal.nota_numerica >= " . NOTA_MIN_AD . ")                              AS ad,
                SUM(cal.nota_numerica >= " . NOTA_MIN_A . " AND cal.nota_numerica < " . NOTA_MIN_AD . ")   AS a,
                SUM(cal.nota_numerica >= " . NOTA_MIN_B . " AND cal.nota_numerica < " . NOTA_MIN_A . ")   AS b,
                SUM(cal.nota_numerica < " . NOTA_MIN_B . ")                               AS c,
                COUNT(*)                                                  AS total_calif
            FROM calificaciones cal
            INNER JOIN matriculas m      ON m.id    = cal.matricula_id
            INNER JOIN secciones s       ON s.id    = m.seccion_id
            INNER JOIN grados g          ON g.id    = s.grado_id
            INNER JOIN niveles n         ON n.id    = g.nivel_id
            INNER JOIN competencias comp ON comp.id = cal.competencia_id
            LEFT  JOIN subareas sa       ON sa.id   = comp.subarea_id
            INNER JOIN areas ar          ON ar.id   = COALESCE(sa.area_id, comp.area_id)
            WHERE cal.periodo_id = ?
              AND m.estado       = 'aprobada'
              AND ar.tipo       != 'transversal'
            GROUP BY n.id, n.nombre, n.codigo
            ORDER BY n.id
        ", [$periodoId]);

        // 2) Agregados por estudiante (promedio + nº de C) → riesgo e histograma.
        $alumnos = $this->query("
            SELECT
                n.id AS nivel_id,
                COUNT(*)                       AS total_estudiantes,
                SUM(prom.promedio < 11)        AS en_riesgo,
                SUM(prom.num_c = 1)            AS c1,
                SUM(prom.num_c = 2)            AS c2,
                SUM(prom.num_c = 3)            AS c3,
                SUM(prom.num_c >= 4)           AS c4plus,
                SUM(prom.num_c >= 1)           AS con_c
            FROM (
                SELECT
                    m.id        AS matricula_id,
                    s.grado_id,
                    AVG(cal.nota_numerica)        AS promedio,
                    SUM(cal.nota_numerica < 11)   AS num_c
                FROM calificaciones cal
                INNER JOIN matriculas m      ON m.id    = cal.matricula_id
                INNER JOIN secciones s       ON s.id    = m.seccion_id
                INNER JOIN competencias comp ON comp.id = cal.competencia_id
                LEFT  JOIN subareas sa       ON sa.id   = comp.subarea_id
                INNER JOIN areas ar          ON ar.id   = COALESCE(sa.area_id, comp.area_id)
                WHERE cal.periodo_id = ?
                  AND m.estado       = 'aprobada'
                  AND ar.tipo       != 'transversal'
                GROUP BY m.id, s.grado_id
            ) prom
            INNER JOIN grados g  ON g.id = prom.grado_id
            INNER JOIN niveles n ON n.id = g.nivel_id
            GROUP BY n.id
        ", [$periodoId]);

        // Indexar los agregados por estudiante por nivel.
        $porNivelAlumnos = [];
        foreach ($alumnos as $row) {
            $porNivelAlumnos[(int) $row['nivel_id']] = $row;
        }

        $niveles = [];
        foreach ($dist as $d) {
            $nivelId = (int) $d['nivel_id'];
            $total   = (int) $d['total_calif'];
            $ad = (int) $d['ad']; $a = (int) $d['a'];
            $b  = (int) $d['b'];  $c = (int) $d['c'];

            $pct = fn(int $n): float => $total > 0 ? round($n / $total * 100, 1) : 0.0;
            $deg = fn(int $n): float => $total > 0 ? round($n / $total * 360, 2) : 0.0;

            // Cortes acumulados para el conic-gradient del donut.
            $degAd = $deg($ad);
            $degA  = $degAd + $deg($a);
            $degB  = $degA + $deg($b);

            $logro   = $ad + $a;
            $proceso = $b + $c;

            $alu = $porNivelAlumnos[$nivelId] ?? null;

            $niveles[] = [
                'nivel_id'          => $nivelId,
                'nivel_nombre'      => $d['nivel_nombre'],
                'nivel_codigo'      => $d['nivel_codigo'],
                'total_calif'       => $total,
                'ad' => $ad, 'a' => $a, 'b' => $b, 'c' => $c,
                'pct_ad' => $pct($ad), 'pct_a' => $pct($a),
                'pct_b'  => $pct($b),  'pct_c' => $pct($c),
                'deg_ad' => $degAd, 'deg_a' => $degA, 'deg_b' => $degB,
                'logro'        => $logro,
                'proceso'      => $proceso,
                'pct_logro'    => $pct($logro),
                'pct_proceso'  => $pct($proceso),
                'total_estudiantes' => (int) ($alu['total_estudiantes'] ?? 0),
                'en_riesgo'         => (int) ($alu['en_riesgo'] ?? 0),
                'con_c'             => (int) ($alu['con_c'] ?? 0),
                'hist' => [
                    'c1'     => (int) ($alu['c1']     ?? 0),
                    'c2'     => (int) ($alu['c2']     ?? 0),
                    'c3'     => (int) ($alu['c3']     ?? 0),
                    'c4plus' => (int) ($alu['c4plus'] ?? 0),
                ],
            ];
        }

        return ['niveles' => $niveles];
    }

    /** Grados con al menos un estudiante calificado en el periodo. */
    private function getGradosConCalificaciones(int $periodoId): array
    {
        return $this->query("
            SELECT DISTINCT
                g.id,
                g.numero,
                g.nombre_display,
                n.nombre AS nivel_nombre,
                n.codigo AS nivel_codigo
            FROM matriculas m
            INNER JOIN secciones s        ON s.id = m.seccion_id
            INNER JOIN grados g           ON g.id = s.grado_id
            INNER JOIN niveles n          ON n.id = g.nivel_id
            INNER JOIN calificaciones cal ON cal.matricula_id = m.id
            WHERE cal.periodo_id = ?
              AND m.estado = 'aprobada'
            ORDER BY n.id, g.numero
        ", [$periodoId]);
    }

    /**
     * Ranking de estudiantes de un grado en el periodo, por promedio general.
     * Excluye competencias transversales (mismo criterio que orden de mérito).
     */
    private function getRankingGrado(int $gradoId, int $periodoId): array
    {
        $estudiantes = $this->query("
            SELECT
                m.id AS matricula_id,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                s.nombre AS seccion_nombre,
                ROUND(AVG(cal.nota_numerica), 2) AS promedio_general
            FROM matriculas m
            INNER JOIN estudiantes e      ON e.id  = m.estudiante_id
            INNER JOIN personas p         ON p.id  = e.persona_id
            INNER JOIN secciones s        ON s.id  = m.seccion_id
            INNER JOIN grados g           ON g.id  = s.grado_id
            INNER JOIN calificaciones cal ON cal.matricula_id = m.id
            INNER JOIN competencias comp  ON comp.id = cal.competencia_id
            LEFT  JOIN subareas sa        ON sa.id   = comp.subarea_id
            INNER JOIN areas a            ON a.id    = COALESCE(sa.area_id, comp.area_id)
            WHERE g.id           = ?
              AND cal.periodo_id = ?
              AND m.estado       = 'aprobada'
              AND a.tipo        != 'transversal'
            GROUP BY m.id, p.apellido_paterno, p.apellido_materno,
                     p.nombres, s.nombre
            ORDER BY promedio_general DESC, p.apellido_paterno
        ", [$gradoId, $periodoId]);

        foreach ($estudiantes as $i => &$est) {
            $est['puesto']          = $i + 1;
            $est['nombre_completo'] = $est['apellido_paterno'] . ' '
                . $est['apellido_materno'] . ', ' . $est['nombres'];
        }
        unset($est);

        return $estudiantes;
    }

    /**
     * Docentes que bloquearon el 100% de las competencias que les correspondían
     * en el periodo, ordenados por mayor anticipación frente a la fecha límite.
     * Como el límite es el mismo para todo el bimestre, "mayor margen" equivale
     * a "último bloqueo más temprano". Devuelve hasta $limite docentes.
     */
    public function getDocentesMasRapidos(int $periodoId, int $limite = 5): array
    {
        $periodo = $this->queryOne("
            SELECT limite_notas, fecha_fin FROM periodos WHERE id = ?
        ", [$periodoId]);

        if (!$periodo) {
            return [];
        }

        // Referencia para el margen: la fecha límite de notas si existe,
        // si no, el fin del bimestre al cierre del día.
        $referencia = $periodo['limite_notas'] ?? ($periodo['fecha_fin'] . ' 23:59:59');
        $tsRef      = strtotime($referencia);

        $limite = max(1, $limite);
        $docentes = $this->query("
            SELECT
                ca.docente_id,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                COUNT(*)        AS total_comp,
                COUNT(bc.id)    AS bloqueadas,
                MAX(bc.bloqueado_en) AS ultimo_bloqueo
            FROM cargas_academicas ca
            INNER JOIN competencias comp ON (
                (ca.subarea_id IS NOT NULL AND comp.subarea_id = ca.subarea_id)
                OR
                (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL AND comp.area_id = ca.area_id)
            )
            INNER JOIN usuarios u ON u.id = ca.docente_id
            INNER JOIN personas p ON p.id = u.persona_id
            LEFT JOIN bloqueos_competencia bc
                ON  bc.carga_id       = ca.id
                AND bc.competencia_id = comp.id
                AND bc.periodo_id     = ?
            WHERE ca.estado  = 'activa'
              AND ca.anio_id = (SELECT anio_id FROM periodos WHERE id = ?)
            GROUP BY ca.docente_id, p.apellido_paterno, p.apellido_materno, p.nombres
            HAVING total_comp = bloqueadas AND total_comp > 0
            ORDER BY ultimo_bloqueo ASC
            LIMIT " . (int) $limite . "
        ", [$periodoId, $periodoId]);

        foreach ($docentes as &$d) {
            $d['nombre_completo'] = $d['apellido_paterno'] . ' '
                . $d['apellido_materno'] . ', ' . $d['nombres'];

            $tsBloqueo      = strtotime((string) $d['ultimo_bloqueo']);
            $margenSegundos = $tsRef - $tsBloqueo;
            $d['a_tiempo']  = $margenSegundos >= 0;
            $d['margen']    = $this->formatearMargen(abs($margenSegundos));
        }
        unset($d);

        return $docentes;
    }

    /** Formatea una cantidad de segundos como "Nd Nh" o "Nh Nmin". */
    private function formatearMargen(int $segundos): string
    {
        $dias  = intdiv($segundos, 86400);
        $horas = intdiv($segundos % 86400, 3600);
        $mins  = intdiv($segundos % 3600, 60);

        if ($dias > 0) {
            return $dias . ' d ' . $horas . ' h';
        }
        if ($horas > 0) {
            return $horas . ' h ' . $mins . ' min';
        }
        return $mins . ' min';
    }
}
