<?php

namespace App\Models;

/**
 * OrdenMeritoModel
 * Fuente única de verdad del ranking del orden de mérito: query de promedios +
 * cascada de desempate por regularidad + resolución manual.
 *
 * La usan tanto Director\OrdenMeritoController (ranking y reporte) como el buscador
 * de estudiantes (puesto por estudiante), para que el puesto sea idéntico en todos
 * lados. La cascada vivía antes en el controller; se extrajo aquí para no duplicarla.
 *
 * Escala COCIAP: AD 17-20 · A 14-16 · B 11-13 · C 0-10.
 */
class OrdenMeritoModel extends BaseModel
{
    protected string $table = 'matriculas';

    private DesempateMeritoModel $desempateModel;
    private PublicacionBoletaModel $publicacionModel;

    /** Memo de debeUsarSnapshot() por periodo (constante dentro de la request). */
    private array $usaSnapshot = [];

    public function __construct()
    {
        parent::__construct();
        $this->desempateModel   = new DesempateMeritoModel();
        $this->publicacionModel = new PublicacionBoletaModel();
    }

    /**
     * Ranking de un grado en un periodo (todas las secciones juntas), con la cascada
     * de desempate aplicada. Cada fila trae: puesto, media_beca, empate_pendiente,
     * empate_clave, además de las métricas. Excluye competencias transversales.
     *
     * Snapshot-aware: si el periodo está CERRADO y tiene snapshot, devuelve el
     * ranking CONGELADO (documento oficial inmutable). Si no, lo calcula en vivo.
     */
    public function rankingGrado(int $gradoId, int $periodoId): array
    {
        if ($this->debeUsarSnapshot($periodoId)) {
            return $this->rankingGradoDesdeSnapshot($gradoId, $periodoId);
        }
        return $this->rankingGradoLive($gradoId, $periodoId);
    }

    /**
     * ¿El grado tiene algún empate irreducible SIN resolver, calculado EN VIVO?
     * Ignora el snapshot a propósito: se usa tras una rectificación de notas
     * (periodo ya cerrado y con snapshot) para avisar si la corrección introdujo
     * un empate que el director debe resolver antes de regenerar el documento.
     */
    public function gradoTieneEmpateLivePendiente(int $gradoId, int $periodoId): bool
    {
        foreach ($this->rankingGradoLive($gradoId, $periodoId) as $fila) {
            if (!empty($fila['empate_pendiente'])) {
                return true;
            }
        }
        return false;
    }

    /** Cálculo EN VIVO del ranking de grado (fuente de la vista activa y del snapshot). */
    private function rankingGradoLive(int $gradoId, int $periodoId): array
    {
        $estudiantes = $this->query("
            SELECT
                m.id AS matricula_id,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                p.dni,
                s.nombre AS seccion_nombre,
                COUNT(cal.nota_numerica)            AS num_competencias,
                SUM(cal.nota_numerica)             AS total_notas,
                ROUND(AVG(cal.nota_numerica), 2)   AS promedio_general,
                AVG(cal.nota_numerica)             AS promedio_exacto,
                SUM(cal.nota_numerica <= 10)               AS num_c,
                SUM(cal.nota_numerica BETWEEN 11 AND 13)   AS num_b,
                SUM(cal.nota_numerica >= " . NOTA_MIN_AD . ")               AS num_ad,
                SUM(cal.nota_numerica IN (15, 16))         AS num_alto,
                SUM(cal.nota_numerica = 16)                AS num_16
            FROM matriculas m
            INNER JOIN estudiantes e      ON e.id  = m.estudiante_id
            INNER JOIN personas p         ON p.id  = e.persona_id
            INNER JOIN secciones s        ON s.id  = m.seccion_id
            INNER JOIN grados g           ON g.id  = s.grado_id
            INNER JOIN calificaciones cal ON cal.matricula_id = m.id
            -- P2 (rediseño 2): el mérito en vivo solo cuenta competencias
            -- BLOQUEADAS (aprobadas por el docente o forzadas por el cierre).
            INNER JOIN bloqueos_competencia bc
                    ON bc.carga_id       = cal.carga_id
                   AND bc.competencia_id = cal.competencia_id
                   AND bc.periodo_id     = cal.periodo_id
            INNER JOIN competencias comp  ON comp.id = cal.competencia_id
            LEFT  JOIN subareas sa        ON sa.id   = comp.subarea_id
            INNER JOIN areas a            ON a.id    = COALESCE(sa.area_id, comp.area_id)
            WHERE g.id           = ?
              AND cal.periodo_id = ?
              -- Las calificaciones EXTRAORDINARIAS (insertadas por RA vía
              -- Rectificación, migración 042) NO cuentan para el mérito:
              -- van a boleta y SIAGIE, pero no mueven puestos.
              AND cal.extraordinaria = 0
              -- Filtro por TIPO (no por estado): el alumno permanece en el orden
              -- de mérito hasta que su tipo sea 'trasladado' o 'retirado'. Los
              -- 'desactivado' por deuda y 'pendiente' SÍ compiten; la operativa de
              -- un retorno revertido (continuador) queda incluida por tipo.
              AND m.tipo NOT IN ('trasladado', 'retirado')
              -- Anclaje por bimestre: el alumno compite donde están sus notas de
              -- ESE periodo. Se excluye la OFICIAL cuando su operativa cubrió este
              -- periodo (retorno activo siempre; revertido solo en sus bimestres).
              AND m.id NOT IN (
                  SELECT matricula_oficial_id FROM retornos_grado WHERE estado = 'activo'
                  UNION
                  SELECT r.matricula_oficial_id
                  FROM retornos_grado r
                  INNER JOIN calificaciones c2
                      ON c2.matricula_id = r.matricula_operativa_id
                     AND c2.periodo_id   = ?
                  WHERE r.estado = 'revertido'
              )
              -- P5 (rediseño 2): Ética y Valores (tutoría de secundaria con la
              -- competencia C57) SÍ cuenta en el mérito — reemplaza a Ed. Religiosa.
              -- El resto de la tutoría (TOE) y las transversales siguen fuera.
              AND (a.tipo NOT IN ('transversal', 'tutoria')
                   OR a.nombre_boleta = '" . AREA_ETICA_NOMBRE_BOLETA . "')
            GROUP BY m.id, p.apellido_paterno, p.apellido_materno,
                     p.nombres, p.dni, s.nombre
            ORDER BY promedio_exacto DESC, num_c ASC, num_b ASC, num_ad DESC,
                     num_alto DESC, num_16 DESC,
                     -- P1 (rediseño 2): tras num_16 el desempate es MANUAL; el
                     -- apellido ya no dirime. Orden estable neutro por matricula.
                     m.id
        ", [$gradoId, $periodoId, $periodoId]);

        return $this->aplicarDesempate($estudiantes, $periodoId);
    }

    /**
     * Ranking por sección dentro del grado, con la cascada aplicada por sección.
     * Retorna [seccion_nombre => filas con puesto]. Si $limite > 0 corta al top-N.
     *
     * Snapshot-aware: periodo CERRADO con snapshot → ranking congelado.
     */
    public function rankingPorSeccion(int $gradoId, int $periodoId, int $limite = 0): array
    {
        if ($this->debeUsarSnapshot($periodoId)) {
            return $this->rankingPorSeccionDesdeSnapshot($gradoId, $periodoId, $limite);
        }
        return $this->rankingPorSeccionLive($gradoId, $periodoId, $limite);
    }

    /** Cálculo EN VIVO del ranking por sección (fuente de la vista activa y del snapshot). */
    private function rankingPorSeccionLive(int $gradoId, int $periodoId, int $limite = 0): array
    {
        $filas = $this->query("
            SELECT
                m.id AS matricula_id,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                s.id     AS seccion_id,
                s.nombre AS seccion_nombre,
                COUNT(cal.nota_numerica)            AS num_competencias,
                SUM(cal.nota_numerica)             AS total_notas,
                ROUND(AVG(cal.nota_numerica), 2)   AS promedio_general,
                AVG(cal.nota_numerica)             AS promedio_exacto,
                SUM(cal.nota_numerica <= 10)               AS num_c,
                SUM(cal.nota_numerica BETWEEN 11 AND 13)   AS num_b,
                SUM(cal.nota_numerica >= " . NOTA_MIN_AD . ")               AS num_ad,
                SUM(cal.nota_numerica IN (15, 16))         AS num_alto,
                SUM(cal.nota_numerica = 16)                AS num_16
            FROM matriculas m
            INNER JOIN estudiantes e      ON e.id  = m.estudiante_id
            INNER JOIN personas p         ON p.id  = e.persona_id
            INNER JOIN secciones s        ON s.id  = m.seccion_id
            INNER JOIN grados g           ON g.id  = s.grado_id
            INNER JOIN calificaciones cal ON cal.matricula_id = m.id
            -- P2 (rediseño 2): el mérito en vivo solo cuenta competencias
            -- BLOQUEADAS (aprobadas por el docente o forzadas por el cierre).
            INNER JOIN bloqueos_competencia bc
                    ON bc.carga_id       = cal.carga_id
                   AND bc.competencia_id = cal.competencia_id
                   AND bc.periodo_id     = cal.periodo_id
            INNER JOIN competencias comp  ON comp.id = cal.competencia_id
            LEFT  JOIN subareas sa        ON sa.id   = comp.subarea_id
            INNER JOIN areas a            ON a.id    = COALESCE(sa.area_id, comp.area_id)
            WHERE g.id           = ?
              AND cal.periodo_id = ?
              -- Extraordinarias fuera del mérito (ver rankingGradoLive).
              AND cal.extraordinaria = 0
              -- Filtro por TIPO (ver rankingGradoLive).
              AND m.tipo NOT IN ('trasladado', 'retirado')
              AND m.id NOT IN (
                  SELECT matricula_oficial_id FROM retornos_grado WHERE estado = 'activo'
                  UNION
                  SELECT r.matricula_oficial_id
                  FROM retornos_grado r
                  INNER JOIN calificaciones c2
                      ON c2.matricula_id = r.matricula_operativa_id
                     AND c2.periodo_id   = ?
                  WHERE r.estado = 'revertido'
              )
              -- P5 (rediseño 2): Ética y Valores (tutoría de secundaria con la
              -- competencia C57) SÍ cuenta en el mérito — reemplaza a Ed. Religiosa.
              -- El resto de la tutoría (TOE) y las transversales siguen fuera.
              AND (a.tipo NOT IN ('transversal', 'tutoria')
                   OR a.nombre_boleta = '" . AREA_ETICA_NOMBRE_BOLETA . "')
            GROUP BY m.id, p.apellido_paterno, p.apellido_materno,
                     p.nombres, s.id, s.nombre
            ORDER BY s.nombre, promedio_exacto DESC, num_c ASC, num_b ASC, num_ad DESC,
                     num_alto DESC, num_16 DESC,
                     -- P1 (rediseño 2): tras num_16 el desempate es MANUAL; el
                     -- apellido ya no dirime. Orden estable neutro por matricula.
                     m.id
        ", [$gradoId, $periodoId, $periodoId]);

        $porSeccion = [];
        foreach ($filas as $fila) {
            $porSeccion[$fila['seccion_nombre']][] = $fila;
        }

        $secciones = [];
        foreach ($porSeccion as $sec => $estudiantes) {
            $rankeados = $this->aplicarDesempate($estudiantes, $periodoId);
            $secciones[$sec] = $limite > 0
                ? array_slice($rankeados, 0, $limite)
                : $rankeados;
        }

        return $secciones;
    }

    // ── Snapshot del orden de mérito (documento oficial congelado) ───────────

    /**
     * ¿El periodo debe leerse del SNAPSHOT? Hace falta que YA tenga filas
     * grabadas (antes del backfill, con la tabla vacía, todo cae al cálculo en
     * vivo) y además una de dos condiciones:
     *   - el periodo está 'cerrado' (documento oficial vigente), o
     *   - el periodo YA FUE PUBLICADO alguna vez (candado de la migración 046).
     *
     * La segunda condición cubre la REAPERTURA (rediseño 2): al reabrir un
     * bimestre publicado su estado deja de ser 'cerrado', pero el orden de mérito
     * oficial ya salió a las familias y es INMUTABLE. Sin esto, el claustro y las
     * familias verían un cálculo en vivo distinto del documento entregado. El
     * efecto de las correcciones se ve al re-cerrar, en la versión rectificada de
     * /admin/control. Mismo criterio monotónico (`fuePublicado`) que usa
     * registrarRanking para decidir oficial vs. rectificado.
     *
     * Memoizado por periodo: rankingGrado se llama en bucle (un grado por
     * iteración) y esta decisión es constante dentro de la request.
     */
    private function debeUsarSnapshot(int $periodoId): bool
    {
        if (isset($this->usaSnapshot[$periodoId])) {
            return $this->usaSnapshot[$periodoId];
        }

        $cerradoConSnapshot = $this->queryOne("
            SELECT 1
            FROM periodos pe
            WHERE pe.id = ? AND pe.estado = 'cerrado'
              AND EXISTS (SELECT 1 FROM orden_merito_snapshot s WHERE s.periodo_id = pe.id)
            LIMIT 1
        ", [$periodoId]) !== null;

        if ($cerradoConSnapshot) {
            return $this->usaSnapshot[$periodoId] = true;
        }

        // Reabierto: manda el snapshot si el bimestre ya estuvo publicado.
        $tieneSnapshot = $this->queryOne("
            SELECT 1 FROM orden_merito_snapshot WHERE periodo_id = ? LIMIT 1
        ", [$periodoId]) !== null;

        return $this->usaSnapshot[$periodoId] =
            $tieneSnapshot && $this->publicacionModel->fuePublicado($periodoId);
    }

    /** Ranking de grado CONGELADO (lee del snapshot). Mismo shape que el vivo. */
    private function rankingGradoDesdeSnapshot(int $gradoId, int $periodoId): array
    {
        $filas = $this->query("
            SELECT
                s.matricula_id,
                p.apellido_paterno, p.apellido_materno, p.nombres, p.dni,
                s.seccion_id,
                sec.nombre AS seccion_nombre,
                s.num_competencias, s.total_notas,
                s.promedio_general, s.promedio_exacto,
                s.num_c, s.num_b, s.num_ad, s.num_alto, s.num_16,
                s.puesto_grado AS puesto
            FROM orden_merito_snapshot s
            INNER JOIN matriculas m  ON m.id = s.matricula_id
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            LEFT  JOIN secciones sec ON sec.id = s.seccion_id
            WHERE s.periodo_id = ? AND s.grado_id = ?
            ORDER BY s.puesto_grado
        ", [$periodoId, $gradoId]);

        return $this->normalizarSnapshot($filas);
    }

    /** Ranking por sección CONGELADO (lee del snapshot). [seccion_nombre => filas]. */
    private function rankingPorSeccionDesdeSnapshot(int $gradoId, int $periodoId, int $limite = 0): array
    {
        $filas = $this->query("
            SELECT
                s.matricula_id,
                p.apellido_paterno, p.apellido_materno, p.nombres, p.dni,
                s.seccion_id,
                sec.nombre AS seccion_nombre,
                s.num_competencias, s.total_notas,
                s.promedio_general, s.promedio_exacto,
                s.num_c, s.num_b, s.num_ad, s.num_alto, s.num_16,
                s.puesto_seccion AS puesto
            FROM orden_merito_snapshot s
            INNER JOIN matriculas m  ON m.id = s.matricula_id
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            LEFT  JOIN secciones sec ON sec.id = s.seccion_id
            WHERE s.periodo_id = ? AND s.grado_id = ? AND s.puesto_seccion IS NOT NULL
            ORDER BY sec.nombre, s.puesto_seccion
        ", [$periodoId, $gradoId]);

        $porSeccion = [];
        foreach ($this->normalizarSnapshot($filas) as $f) {
            $porSeccion[$f['seccion_nombre']][] = $f;
        }
        if ($limite > 0) {
            foreach ($porSeccion as $sec => $rows) {
                $porSeccion[$sec] = array_slice($rows, 0, $limite);
            }
        }
        return $porSeccion;
    }

    /**
     * Normaliza filas del snapshot al mismo shape que el ranking en vivo:
     * puesto entero, media_beca (1º del grupo), y sin empates pendientes
     * (todos se resolvieron antes de cerrar, por eso el snapshot es definitivo).
     */
    private function normalizarSnapshot(array $filas): array
    {
        foreach ($filas as &$f) {
            $f['puesto']           = (int) $f['puesto'];
            $f['media_beca']       = ($f['puesto'] === 1);
            $f['empate_pendiente'] = false;
            $f['empate_clave']     = null;
        }
        unset($f);
        return $filas;
    }

    /**
     * Grados con ranking en un periodo (id, numero, nombre, nivel). Snapshot-aware:
     * para un periodo cerrado con snapshot enumera desde él (así incluye grados
     * "congelados" como la sección operativa de un retorno que ya no existe en el
     * estado actual de las matrículas). Para el resto, los grados con notas en vivo.
     */
    public function gradosConRanking(int $periodoId): array
    {
        if ($this->debeUsarSnapshot($periodoId)) {
            return $this->query("
                SELECT DISTINCT g.id, g.numero, g.nombre_display,
                       n.id AS nivel_id, n.nombre AS nivel_nombre, n.codigo AS nivel_codigo
                FROM orden_merito_snapshot s
                INNER JOIN grados g  ON g.id = s.grado_id
                INNER JOIN niveles n ON n.id = g.nivel_id
                WHERE s.periodo_id = ?
                ORDER BY n.id, g.numero
            ", [$periodoId]);
        }

        return $this->query("
            SELECT DISTINCT g.id, g.numero, g.nombre_display,
                   n.id AS nivel_id, n.nombre AS nivel_nombre, n.codigo AS nivel_codigo
            FROM matriculas m
            INNER JOIN secciones s        ON s.id  = m.seccion_id
            INNER JOIN grados g           ON g.id  = s.grado_id
            INNER JOIN niveles n          ON n.id  = g.nivel_id
            INNER JOIN calificaciones cal ON cal.matricula_id = m.id
            -- P2 (rediseño 2): enumerar solo grados con notas BLOQUEADAS,
            -- mismo universo que el ranking en vivo.
            INNER JOIN bloqueos_competencia bc
                    ON bc.carga_id       = cal.carga_id
                   AND bc.competencia_id = cal.competencia_id
                   AND bc.periodo_id     = cal.periodo_id
            WHERE cal.periodo_id = ?
              AND m.tipo NOT IN ('trasladado', 'retirado')
            ORDER BY n.id, g.numero
        ", [$periodoId]);
    }

    /**
     * Calcula las filas del ranking de un periodo (puesto por grado + puesto por
     * sección + métricas) listas para persistir. Es la fuente COMÚN del snapshot
     * oficial y de la versión rectificada, para que ambos congelen exactamente el
     * mismo cálculo EN VIVO (con anclaje por bimestre, inmune al estado actual del
     * retorno). No escribe nada: solo devuelve las filas.
     */
    private function calcularFilasRanking(int $periodoId): array
    {
        // Grados con notas en el periodo (incluye operativas de retornos revertidos,
        // que compiten por tipo en los bimestres que cursaron).
        $grados = $this->query("
            SELECT DISTINCT g.id
            FROM matriculas m
            INNER JOIN secciones s        ON s.id  = m.seccion_id
            INNER JOIN grados g           ON g.id  = s.grado_id
            INNER JOIN calificaciones cal ON cal.matricula_id = m.id AND cal.periodo_id = ?
            -- P2 (rediseño 2): solo grados con notas BLOQUEADAS.
            INNER JOIN bloqueos_competencia bc
                    ON bc.carga_id       = cal.carga_id
                   AND bc.competencia_id = cal.competencia_id
                   AND bc.periodo_id     = cal.periodo_id
            WHERE m.tipo NOT IN ('trasladado', 'retirado')
        ", [$periodoId]);

        $filas = [];
        foreach ($grados as $g) {
            $gradoId = (int) $g['id'];
            $general = $this->rankingGradoLive($gradoId, $periodoId);
            if (empty($general)) {
                continue;
            }

            // Puesto y sección por matrícula desde el ranking por sección (todas).
            $secMap = [];
            foreach ($this->rankingPorSeccionLive($gradoId, $periodoId) as $rows) {
                foreach ($rows as $f) {
                    $secMap[(int) $f['matricula_id']] = [
                        'seccion_id'     => isset($f['seccion_id']) ? (int) $f['seccion_id'] : null,
                        'puesto_seccion' => (int) $f['puesto'],
                    ];
                }
            }

            foreach ($general as $f) {
                $mid = (int) $f['matricula_id'];
                $sec = $secMap[$mid] ?? ['seccion_id' => null, 'puesto_seccion' => null];
                $filas[] = [
                    'periodo_id'       => $periodoId,
                    'matricula_id'     => $mid,
                    'grado_id'         => $gradoId,
                    'seccion_id'       => $sec['seccion_id'],
                    'puesto_grado'     => (int) $f['puesto'],
                    'puesto_seccion'   => $sec['puesto_seccion'],
                    'num_competencias' => (int) $f['num_competencias'],
                    'total_notas'      => (int) $f['total_notas'],
                    'promedio_general' => $f['promedio_general'],
                    'promedio_exacto'  => $f['promedio_exacto'],
                    'num_c'            => (int) $f['num_c'],
                    'num_b'            => (int) $f['num_b'],
                    'num_ad'           => (int) $f['num_ad'],
                    'num_alto'         => (int) $f['num_alto'],
                    'num_16'           => (int) $f['num_16'],
                ];
            }
        }

        return $filas;
    }

    /**
     * (Re)genera el snapshot OFICIAL de un periodo: borra el existente e inserta
     * el ranking calculado. Se llama al CERRAR (dentro de su transacción) y en el
     * backfill. OJO: NO honra la inmutabilidad por sí mismo — quien deba respetar
     * "un bimestre publicado no cambia su oficial" usa registrarRanking().
     */
    public function generarSnapshot(int $periodoId, ?int $usuarioId = null): void
    {
        $this->execute("DELETE FROM orden_merito_snapshot WHERE periodo_id = ?", [$periodoId]);

        foreach ($this->calcularFilasRanking($periodoId) as $f) {
            $this->execute("
                INSERT INTO orden_merito_snapshot
                    (periodo_id, matricula_id, grado_id, seccion_id,
                     puesto_grado, puesto_seccion,
                     num_competencias, total_notas, promedio_general, promedio_exacto,
                     num_c, num_b, num_ad, num_alto, num_16, generado_por)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ", [
                $f['periodo_id'], $f['matricula_id'], $f['grado_id'], $f['seccion_id'],
                $f['puesto_grado'], $f['puesto_seccion'],
                $f['num_competencias'], $f['total_notas'],
                $f['promedio_general'], $f['promedio_exacto'],
                $f['num_c'], $f['num_b'], $f['num_ad'],
                $f['num_alto'], $f['num_16'], $usuarioId,
            ]);
        }
    }

    /**
     * (Re)genera la versión RECTIFICADA (no oficial) de un periodo. Misma fuente de
     * cálculo que el oficial, pero va a orden_merito_rectificado con su motivo.
     * Sobrescribe la versión anterior del periodo (guardamos la última).
     */
    public function generarSnapshotRectificado(int $periodoId, ?int $usuarioId, string $motivo): void
    {
        $this->execute("DELETE FROM orden_merito_rectificado WHERE periodo_id = ?", [$periodoId]);

        foreach ($this->calcularFilasRanking($periodoId) as $f) {
            $this->execute("
                INSERT INTO orden_merito_rectificado
                    (periodo_id, matricula_id, grado_id, seccion_id,
                     puesto_grado, puesto_seccion,
                     num_competencias, total_notas, promedio_general, promedio_exacto,
                     num_c, num_b, num_ad, num_alto, num_16, generado_por, motivo)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ", [
                $f['periodo_id'], $f['matricula_id'], $f['grado_id'], $f['seccion_id'],
                $f['puesto_grado'], $f['puesto_seccion'],
                $f['num_competencias'], $f['total_notas'],
                $f['promedio_general'], $f['promedio_exacto'],
                $f['num_c'], $f['num_b'], $f['num_ad'],
                $f['num_alto'], $f['num_16'], $usuarioId, $motivo,
            ]);
        }
    }

    /** ¿El periodo ya tiene snapshot OFICIAL grabado? */
    public function tieneSnapshotOficial(int $periodoId): bool
    {
        return $this->queryOne(
            "SELECT 1 FROM orden_merito_snapshot WHERE periodo_id = ? LIMIT 1",
            [$periodoId]
        ) !== null;
    }

    /**
     * PUNTO ÚNICO de escritura del ranking que respeta la INMUTABILIDAD (migr. 046).
     * Si el periodo ya ESTUVO publicado (compuerta 044) y tiene oficial → escribe la
     * versión RECTIFICADA no oficial (el oficial no se toca). Si no → (re)genera el
     * oficial. Lo usan PeriodoController::cerrar y RectificacionController.
     * @return string 'oficial' | 'rectificado'
     */
    public function registrarRanking(int $periodoId, ?int $usuarioId, string $motivo): string
    {
        if ($this->publicacionModel->fuePublicado($periodoId) && $this->tieneSnapshotOficial($periodoId)) {
            $this->generarSnapshotRectificado($periodoId, $usuarioId, $motivo);
            return 'rectificado';
        }
        $this->generarSnapshot($periodoId, $usuarioId);
        return 'oficial';
    }

    // ── Lectores de la versión RECTIFICADA (Centro de control) ───────────────

    /**
     * Metadatos de la versión rectificada de un periodo (o null si no hay):
     * generado_en, motivo, generado_por + nombre y num_alumnos.
     */
    public function infoRectificado(int $periodoId): ?array
    {
        $row = $this->queryOne("
            SELECT r.generado_en, r.motivo, r.generado_por,
                   CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres) AS generado_por_nombre
            FROM orden_merito_rectificado r
            LEFT JOIN usuarios u ON u.id = r.generado_por
            LEFT JOIN personas p ON p.id = u.persona_id
            WHERE r.periodo_id = ?
            ORDER BY r.generado_en DESC
            LIMIT 1
        ", [$periodoId]);

        if (!$row) {
            return null;
        }
        $cnt = $this->queryOne(
            "SELECT COUNT(*) AS n FROM orden_merito_rectificado WHERE periodo_id = ?",
            [$periodoId]
        );
        $row['num_alumnos'] = (int) ($cnt['n'] ?? 0);
        return $row;
    }

    /** Grados con ranking en la versión rectificada de un periodo. */
    public function gradosConRectificado(int $periodoId): array
    {
        return $this->query("
            SELECT DISTINCT g.id, g.numero, g.nombre_display,
                   n.id AS nivel_id, n.nombre AS nivel_nombre, n.codigo AS nivel_codigo
            FROM orden_merito_rectificado r
            INNER JOIN grados g  ON g.id = r.grado_id
            INNER JOIN niveles n ON n.id = g.nivel_id
            WHERE r.periodo_id = ?
            ORDER BY n.id, g.numero
        ", [$periodoId]);
    }

    /** Ranking de grado desde la versión rectificada (mismo shape que el snapshot). */
    public function rankingGradoRectificado(int $gradoId, int $periodoId): array
    {
        $filas = $this->query("
            SELECT
                r.matricula_id,
                p.apellido_paterno, p.apellido_materno, p.nombres, p.dni,
                r.seccion_id,
                sec.nombre AS seccion_nombre,
                r.num_competencias, r.total_notas,
                r.promedio_general, r.promedio_exacto,
                r.num_c, r.num_b, r.num_ad, r.num_alto, r.num_16,
                r.puesto_grado AS puesto
            FROM orden_merito_rectificado r
            INNER JOIN matriculas m  ON m.id = r.matricula_id
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            LEFT  JOIN secciones sec ON sec.id = r.seccion_id
            WHERE r.periodo_id = ? AND r.grado_id = ?
            ORDER BY r.puesto_grado
        ", [$periodoId, $gradoId]);

        return $this->normalizarSnapshot($filas);
    }

    /**
     * Mapa matricula_id => ['puesto'=>int, 'empate_pendiente'=>bool] para varios
     * grados a la vez. Lo usa el buscador para mostrar el puesto exacto del orden
     * de mérito (incluida la resolución manual de empates).
     */
    public function puestosPorGrado(array $gradoIds, int $periodoId): array
    {
        $map = [];
        foreach (array_unique(array_map('intval', $gradoIds)) as $gid) {
            foreach ($this->rankingGrado($gid, $periodoId) as $fila) {
                $map[(int) $fila['matricula_id']] = [
                    'puesto'           => (int) $fila['puesto'],
                    'empate_pendiente' => !empty($fila['empate_pendiente']),
                ];
            }
        }
        return $map;
    }

    /**
     * Lista de grados del periodo que TODAVÍA tienen empates irreducibles sin
     * resolver (etiqueta "Nivel — Grado"). Se usa para impedir el cierre del
     * bimestre hasta que todos los empates estén resueltos: el snapshot oficial
     * del orden de mérito debe congelar un ranking 100% definido. Recorre los
     * mismos grados y la misma cascada que usa el director para resolverlos, así
     * la validación cuadra exactamente con la UI de desempate.
     *
     * Usa el cálculo EN VIVO a propósito (rankingGradoLive, NO rankingGrado):
     * valida lo que se está por congelar, no lo ya congelado. Importa al RE-CERRAR
     * un bimestre publicado y reabierto — ahí debeUsarSnapshot devuelve true
     * (candado 046) y leer por rankingGrado devolvería el snapshot viejo, sin ver
     * los empates que introdujeron las rectificaciones.
     */
    public function gradosConEmpatesPendientes(int $periodoId): array
    {
        $grados = $this->query("
            SELECT DISTINCT g.id, g.numero, g.nombre_display,
                            n.id AS nivel_id, n.nombre AS nivel_nombre
            FROM matriculas m
            INNER JOIN secciones s        ON s.id  = m.seccion_id
            INNER JOIN grados g           ON g.id  = s.grado_id
            INNER JOIN niveles n          ON n.id  = g.nivel_id
            INNER JOIN calificaciones cal ON cal.matricula_id = m.id
            -- P2 (rediseño 2): enumerar solo grados con notas BLOQUEADAS,
            -- mismo universo que el ranking en vivo.
            INNER JOIN bloqueos_competencia bc
                    ON bc.carga_id       = cal.carga_id
                   AND bc.competencia_id = cal.competencia_id
                   AND bc.periodo_id     = cal.periodo_id
            WHERE cal.periodo_id = ?
              AND m.tipo NOT IN ('trasladado', 'retirado')
            ORDER BY n.id, g.numero
        ", [$periodoId]);

        $pendientes = [];
        foreach ($grados as $g) {
            foreach ($this->rankingGradoLive((int) $g['id'], $periodoId) as $fila) {
                if (!empty($fila['empate_pendiente'])) {
                    $pendientes[] = $g['nivel_nombre'] . ' — ' . $g['nombre_display'];
                    break;
                }
            }
        }

        return $pendientes;
    }

    // ── Cascada de desempate (movida desde OrdenMeritoController) ─────────────

    /**
     * Aplica la cascada de desempate sobre filas YA ordenadas por SQL
     * (promedio_exacto DESC, num_c ASC, num_b ASC, num_ad DESC, apellidos):
     *  - N (num_competencias) distinto en el empate → irreducible (decisión humana).
     *  - misma distribución literal exacta (num_c, num_b, num_ad) → irreducible.
     * Para cada grupo irreducible aplica la resolución humana si existe; si no, marca
     * `empate_pendiente`. Asigna puesto secuencial y media beca al 1º no pendiente.
     */
    private function aplicarDesempate(array $filas, int $periodoId): array
    {
        $total = count($filas);

        $i = 0;
        $resultado = [];
        while ($i < $total) {
            $j = $i;
            $promRef = round((float) $filas[$i]['promedio_exacto'], 6);
            while (
                $j + 1 < $total
                && round((float) $filas[$j + 1]['promedio_exacto'], 6) === $promRef
            ) {
                $j++;
            }
            $grupoProm = array_slice($filas, $i, $j - $i + 1);

            if (count($grupoProm) === 1) {
                $resultado[] = $this->marcarFila($grupoProm[0], false, null);
                $i = $j + 1;
                continue;
            }

            // ¿N uniforme dentro del grupo de promedio?
            $ns = array_unique(array_map(
                static fn($f) => (int) $f['num_competencias'],
                $grupoProm
            ));

            if (count($ns) > 1) {
                // N desigual → todo el grupo es irreducible (decisión humana).
                $resultado = array_merge(
                    $resultado,
                    $this->resolverGrupoIrreducible($grupoProm, $periodoId)
                );
                $i = $j + 1;
                continue;
            }

            // N uniforme → subagrupar por distribución literal (num_c, num_b, num_ad).
            $k = 0;
            $sub = count($grupoProm);
            while ($k < $sub) {
                $l = $k;
                $tuplaRef = $this->tuplaLiteral($grupoProm[$k]);
                while (
                    $l + 1 < $sub
                    && $this->tuplaLiteral($grupoProm[$l + 1]) === $tuplaRef
                ) {
                    $l++;
                }
                $subgrupo = array_slice($grupoProm, $k, $l - $k + 1);

                if (count($subgrupo) === 1) {
                    $resultado[] = $this->marcarFila($subgrupo[0], false, null);
                } else {
                    $resultado = array_merge(
                        $resultado,
                        $this->resolverGrupoIrreducible($subgrupo, $periodoId)
                    );
                }
                $k = $l + 1;
            }

            $i = $j + 1;
        }

        foreach ($resultado as $idx => &$fila) {
            $fila['puesto']     = $idx + 1;
            $fila['media_beca'] = ($idx === 0 && empty($fila['empate_pendiente']));
        }

        return $resultado;
    }

    /**
     * Resuelve un grupo irreducible: aplica la resolución humana si existe, o lo
     * marca como pendiente conservando el orden estable de entrada.
     */
    private function resolverGrupoIrreducible(array $grupo, int $periodoId): array
    {
        $matriculas = array_map(static fn($f) => (int) $f['matricula_id'], $grupo);
        $resolucion = $this->desempateModel->getResolucion($periodoId, $matriculas);

        if ($resolucion !== null) {
            $orden = $resolucion['orden']; // [matricula_id => orden_manual]
            usort($grupo, static function ($a, $b) use ($orden) {
                return ($orden[(int) $a['matricula_id']] ?? PHP_INT_MAX)
                     <=> ($orden[(int) $b['matricula_id']] ?? PHP_INT_MAX);
            });
            return array_map(
                fn($f) => $this->marcarFila($f, false, null),
                $grupo
            );
        }

        $clave = DesempateMeritoModel::claveGrupo($matriculas);
        return array_map(
            fn($f) => $this->marcarFila($f, true, $clave),
            $grupo
        );
    }

    /**
     * Tupla que identifica un empate irreducible. Incluye la distribución literal
     * (C, B, AD) y los criterios de regularidad alta (cantidad de notas 15-16 y de 16):
     * dos alumnos solo son irreducibles si coinciden en LOS CINCO conteos.
     */
    private function tuplaLiteral(array $fila): string
    {
        return (int) $fila['num_c'] . '|'
             . (int) $fila['num_b'] . '|'
             . (int) $fila['num_ad'] . '|'
             . (int) $fila['num_alto'] . '|'
             . (int) $fila['num_16'];
    }

    /** Anota una fila con el estado de empate sin perder sus datos. */
    private function marcarFila(array $fila, bool $pendiente, ?string $clave): array
    {
        $fila['empate_pendiente'] = $pendiente;
        $fila['empate_clave']     = $clave;
        return $fila;
    }
}
