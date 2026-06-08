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
     */
    public function rankingGrado(int $gradoId, int $periodoId): array
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
                SUM(cal.nota_numerica >= 17)               AS num_ad
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
              AND m.estado IN ('aprobada', 'activo')
              -- Retorno de grado: el estudiante compite en su grado OPERATIVO.
              AND m.id NOT IN (
                  SELECT matricula_oficial_id FROM retornos_grado WHERE estado = 'activo'
              )
              AND a.tipo        != 'transversal'
            GROUP BY m.id, p.apellido_paterno, p.apellido_materno,
                     p.nombres, p.dni, s.nombre
            ORDER BY promedio_exacto DESC, num_c ASC, num_b ASC, num_ad DESC,
                     p.apellido_paterno, p.apellido_materno, p.nombres
        ", [$gradoId, $periodoId]);

        return $this->aplicarDesempate($estudiantes, $periodoId);
    }

    /**
     * Ranking por sección dentro del grado, con la cascada aplicada por sección.
     * Retorna [seccion_nombre => filas con puesto]. Si $limite > 0 corta al top-N.
     */
    public function rankingPorSeccion(int $gradoId, int $periodoId, int $limite = 0): array
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
                SUM(cal.nota_numerica >= 17)               AS num_ad
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
              AND m.estado IN ('aprobada', 'activo')
              AND m.id NOT IN (
                  SELECT matricula_oficial_id FROM retornos_grado WHERE estado = 'activo'
              )
              AND a.tipo        != 'transversal'
            GROUP BY m.id, p.apellido_paterno, p.apellido_materno,
                     p.nombres, s.id, s.nombre
            ORDER BY s.nombre, promedio_exacto DESC, num_c ASC, num_b ASC, num_ad DESC,
                     p.apellido_paterno, p.apellido_materno, p.nombres
        ", [$gradoId, $periodoId]);

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

    /** Tupla de distribución literal (C, B, AD) que identifica un empate irreducible. */
    private function tuplaLiteral(array $fila): string
    {
        return (int) $fila['num_c'] . '|'
             . (int) $fila['num_b'] . '|'
             . (int) $fila['num_ad'];
    }

    /** Anota una fila con el estado de empate sin perder sus datos. */
    private function marcarFila(array $fila, bool $pendiente, ?string $clave): array
    {
        $fila['empate_pendiente'] = $pendiente;
        $fila['empate_clave']     = $clave;
        return $fila;
    }
}
