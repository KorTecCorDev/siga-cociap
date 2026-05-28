<?php

namespace App\Models;

class EstudianteModel extends BaseModel
{
    protected string $table = 'estudiantes';

    /** Año académico activo (NULL si no hay ninguno marcado como activo). */
    public function anioActivo(): ?array
    {
        return $this->queryOne("
            SELECT id, anio
            FROM anios_academicos
            WHERE estado = 'activo'
            ORDER BY anio DESC
            LIMIT 1
        ");
    }

    /** Periodo (bimestre) activo del año dado (NULL si ninguno está abierto). */
    public function periodoActivo(int $anioId): ?array
    {
        return $this->queryOne("
            SELECT id, numero, nombre_display
            FROM periodos
            WHERE anio_id = ? AND estado = 'activo'
            ORDER BY numero DESC
            LIMIT 1
        ", [$anioId]);
    }

    /**
     * Busca estudiantes matriculados en el año activo por DNI o por
     * apellidos/nombres. Devuelve nivel, grado, sección, estado de matrícula
     * y datos personales.
     *
     * - Si el término es solo dígitos → coincidencia por prefijo de DNI.
     * - En otro caso → coincidencia parcial por cada palabra contra el nombre
     *   completo (todas las palabras deben aparecer).
     */
    public function buscarEnAnioActivo(
        string $termino,
        int $anioId,
        ?int $periodoActivoId = null,
        int $limite = 25
    ): array {
        $termino = trim($termino);
        if ($termino === '') {
            return [];
        }

        // El placeholder del periodo (tabla derivada de ranking) aparece ANTES
        // que el de m.anio_id en el SQL, por eso va primero en $params.
        $params  = [$periodoActivoId, $anioId];
        $condicion = '';

        if (ctype_digit($termino)) {
            $condicion = 'p.dni LIKE ?';
            $params[]  = $termino . '%';
        } else {
            $palabras = preg_split('/\s+/', $termino);
            $partes   = [];
            foreach ($palabras as $palabra) {
                $partes[]  = "CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ' ', p.nombres) LIKE ?";
                $params[]  = '%' . $palabra . '%';
            }
            $condicion = implode(' AND ', $partes);
        }

        $limite = max(1, min(100, $limite));

        // El puesto del orden de mérito se rankea por grado (todas las secciones
        // juntas) sobre el promedio de competencias NO transversales del periodo
        // activo — misma lógica que OrdenMeritoController::calcularRanking().
        return $this->query("
            SELECT
                p.dni,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                p.sexo,
                m.estado          AS matricula_estado,
                s.nombre          AS seccion_nombre,
                g.nombre_display  AS grado_nombre,
                n.nombre          AS nivel_nombre,
                tp.apellido_paterno AS tutor_apellido_paterno,
                tp.apellido_materno AS tutor_apellido_materno,
                tp.nombres          AS tutor_nombres,
                rk.puesto           AS puesto_grado
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas    p ON p.id = e.persona_id
            LEFT  JOIN secciones   s ON s.id = m.seccion_id
            LEFT  JOIN grados      g ON g.id = s.grado_id
            LEFT  JOIN niveles     n ON n.id = g.nivel_id
            LEFT  JOIN usuarios   tu ON tu.id = s.tutor_id
            LEFT  JOIN personas   tp ON tp.id = tu.persona_id
            LEFT  JOIN (
                SELECT
                    matricula_id,
                    ROW_NUMBER() OVER (PARTITION BY grado_id ORDER BY promedio DESC) AS puesto
                FROM (
                    SELECT
                        m2.id AS matricula_id,
                        g2.id AS grado_id,
                        ROUND(AVG(cal.nota_numerica), 2) AS promedio
                    FROM matriculas m2
                    INNER JOIN secciones s2       ON s2.id   = m2.seccion_id
                    INNER JOIN grados g2          ON g2.id   = s2.grado_id
                    INNER JOIN calificaciones cal ON cal.matricula_id = m2.id
                    INNER JOIN competencias comp  ON comp.id = cal.competencia_id
                    LEFT  JOIN subareas sa        ON sa.id   = comp.subarea_id
                    INNER JOIN areas a            ON a.id    = COALESCE(sa.area_id, comp.area_id)
                    WHERE cal.periodo_id = ?
                      AND m2.estado      = 'aprobada'
                      AND a.tipo        != 'transversal'
                    GROUP BY m2.id, g2.id
                ) prom
            ) rk ON rk.matricula_id = m.id
            WHERE m.anio_id = ?
              AND ({$condicion})
            ORDER BY p.apellido_paterno, p.apellido_materno, p.nombres
            LIMIT {$limite}
        ", $params);
    }
}
