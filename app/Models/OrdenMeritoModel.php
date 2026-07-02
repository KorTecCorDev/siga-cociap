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

    public function __construct()
    {
        parent::__construct();
        $this->desempateModel = new DesempateMeritoModel();
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
            INNER JOIN competencias comp  ON comp.id = cal.competencia_id
            LEFT  JOIN subareas sa        ON sa.id   = comp.subarea_id
            INNER JOIN areas a            ON a.id    = COALESCE(sa.area_id, comp.area_id)
            WHERE g.id           = ?
              AND cal.periodo_id = ?
              AND (
                  m.estado = 'aprobada'
                  -- Operativa de un retorno REVERTIDO: sigue compitiendo en los
                  -- bimestres que cursó, aunque su matrícula esté desactivada.
                  OR m.id IN (
                      SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido'
                  )
              )
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
              AND a.tipo        NOT IN ('transversal', 'tutoria')
            GROUP BY m.id, p.apellido_paterno, p.apellido_materno,
                     p.nombres, p.dni, s.nombre
            ORDER BY promedio_exacto DESC, num_c ASC, num_b ASC, num_ad DESC,
                     num_alto DESC, num_16 DESC,
                     p.apellido_paterno, p.apellido_materno, p.nombres
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
            INNER JOIN competencias comp  ON comp.id = cal.competencia_id
            LEFT  JOIN subareas sa        ON sa.id   = comp.subarea_id
            INNER JOIN areas a            ON a.id    = COALESCE(sa.area_id, comp.area_id)
            WHERE g.id           = ?
              AND cal.periodo_id = ?
              AND (
                  m.estado = 'aprobada'
                  OR m.id IN (
                      SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido'
                  )
              )
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
              AND a.tipo        NOT IN ('transversal', 'tutoria')
            GROUP BY m.id, p.apellido_paterno, p.apellido_materno,
                     p.nombres, s.id, s.nombre
            ORDER BY s.nombre, promedio_exacto DESC, num_c ASC, num_b ASC, num_ad DESC,
                     num_alto DESC, num_16 DESC,
                     p.apellido_paterno, p.apellido_materno, p.nombres
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
     * ¿El periodo debe leerse del SNAPSHOT? Solo si está 'cerrado' y ya tiene
     * filas grabadas. Antes del backfill (tabla vacía) un periodo cerrado cae al
     * cálculo en vivo, manteniendo el comportamiento previo sin romper nada.
     */
    private function debeUsarSnapshot(int $periodoId): bool
    {
        return $this->queryOne("
            SELECT 1
            FROM periodos pe
            WHERE pe.id = ? AND pe.estado = 'cerrado'
              AND EXISTS (SELECT 1 FROM orden_merito_snapshot s WHERE s.periodo_id = pe.id)
            LIMIT 1
        ", [$periodoId]) !== null;
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
            WHERE cal.periodo_id = ?
              AND m.estado = 'aprobada'
            ORDER BY n.id, g.numero
        ", [$periodoId]);
    }

    /**
     * (Re)genera el snapshot oficial de un periodo: borra el existente e inserta
     * el ranking por grado (puesto_grado) + por sección (puesto_seccion) de todos
     * los grados con ranking. Se llama al CERRAR (dentro de su transacción) y en
     * el backfill. Usa el cálculo EN VIVO con anclaje por bimestre, por lo que es
     * inmune al estado actual del retorno (congela dónde estaban las notas).
     */
    public function generarSnapshot(int $periodoId, ?int $usuarioId = null): void
    {
        $this->execute("DELETE FROM orden_merito_snapshot WHERE periodo_id = ?", [$periodoId]);

        // Grados con notas en el periodo, incluyendo operativas de retornos
        // revertidos (compiten en los bimestres que cursaron).
        $grados = $this->query("
            SELECT DISTINCT g.id
            FROM matriculas m
            INNER JOIN secciones s        ON s.id  = m.seccion_id
            INNER JOIN grados g           ON g.id  = s.grado_id
            INNER JOIN calificaciones cal ON cal.matricula_id = m.id AND cal.periodo_id = ?
            WHERE (m.estado = 'aprobada'
                   OR m.id IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido'))
        ", [$periodoId]);

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
                $this->execute("
                    INSERT INTO orden_merito_snapshot
                        (periodo_id, matricula_id, grado_id, seccion_id,
                         puesto_grado, puesto_seccion,
                         num_competencias, total_notas, promedio_general, promedio_exacto,
                         num_c, num_b, num_ad, num_alto, num_16, generado_por)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                ", [
                    $periodoId, $mid, $gradoId, $sec['seccion_id'],
                    (int) $f['puesto'], $sec['puesto_seccion'],
                    (int) $f['num_competencias'], (int) $f['total_notas'],
                    $f['promedio_general'], $f['promedio_exacto'],
                    (int) $f['num_c'], (int) $f['num_b'], (int) $f['num_ad'],
                    (int) $f['num_alto'], (int) $f['num_16'], $usuarioId,
                ]);
            }
        }
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
     * mismos grados y la misma cascada (rankingGrado) que usa el director para
     * resolverlos, así la validación cuadra exactamente con la UI de desempate.
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
            WHERE cal.periodo_id = ?
              AND m.estado = 'aprobada'
            ORDER BY n.id, g.numero
        ", [$periodoId]);

        $pendientes = [];
        foreach ($grados as $g) {
            foreach ($this->rankingGrado((int) $g['id'], $periodoId) as $fila) {
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
