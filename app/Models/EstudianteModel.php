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
     * Último bimestre CERRADO del año dado (el de mayor número con
     * estado = 'cerrado'). Es el orden de mérito vigente para consulta.
     * NULL si aún no se cerró ningún bimestre (estamos en el I Bimestre).
     */
    public function ultimoBimestreCerrado(int $anioId): ?array
    {
        return $this->queryOne("
            SELECT id, numero, nombre_display
            FROM periodos
            WHERE anio_id = ? AND estado = 'cerrado'
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
        int $limite = 25
    ): array {
        $termino = trim($termino);
        if ($termino === '') {
            return [];
        }

        $params  = [$anioId];
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

        // Devuelve matricula_id y grado_id para que el controlador calcule el puesto
        // del orden de mérito con OrdenMeritoModel (fuente única, con cascada de
        // desempate y resolución manual). NO se rankea aquí.
        return $this->query("
            SELECT
                m.id              AS matricula_id,
                g.id              AS grado_id,
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
                tp.nombres          AS tutor_nombres
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas    p ON p.id = e.persona_id
            LEFT  JOIN secciones   s ON s.id = m.seccion_id
            LEFT  JOIN grados      g ON g.id = s.grado_id
            LEFT  JOIN niveles     n ON n.id = g.nivel_id
            LEFT  JOIN usuarios   tu ON tu.id = s.tutor_id
            LEFT  JOIN personas   tp ON tp.id = tu.persona_id
            WHERE m.anio_id = ?
              AND ({$condicion})
            ORDER BY p.apellido_paterno, p.apellido_materno, p.nombres
            LIMIT {$limite}
        ", $params);
    }
}
