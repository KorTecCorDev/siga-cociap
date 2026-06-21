<?php

namespace App\Models;

/**
 * MatriculaModel
 * Gestión de matrículas del SIGA-COCIAP: registro, estado, documentos
 * y notas externas (traslados de entrada).
 *
 * Estados que usa este módulo: 'pendiente' → 'activo' / 'desactivado'.
 * (La demo del I Bimestre quedó en 'aprobada'; el enero ampliado los soporta.)
 */
class MatriculaModel extends BaseModel
{
    protected string $table = 'matriculas';

    // ── Listado con filtros y paginación ─────────────────────────

    /**
     * Construye el WHERE compartido por listar() y contar().
     * Retorna [sqlCondiciones, params].
     */
    private function construirFiltros(array $f): array
    {
        $cond   = ['1 = 1'];
        $params = [];

        if (!empty($f['anio_id'])) {
            $cond[]   = 'm.anio_id = ?';
            $params[] = (int) $f['anio_id'];
        }
        if (!empty($f['grado_id'])) {
            $cond[]   = 'g.id = ?';
            $params[] = (int) $f['grado_id'];
        }
        if (!empty($f['seccion_id'])) {
            $cond[]   = 'm.seccion_id = ?';
            $params[] = (int) $f['seccion_id'];
        }
        if (!empty($f['estado'])) {
            $cond[]   = 'm.estado = ?';
            $params[] = $f['estado'];
        }
        if (!empty($f['tipo'])) {
            $cond[]   = 'm.tipo = ?';
            $params[] = $f['tipo'];
        }
        if (!empty($f['search'])) {
            $termino = trim((string) $f['search']);
            if (ctype_digit($termino)) {
                $cond[]   = 'p.dni LIKE ?';
                $params[] = $termino . '%';
            } else {
                $cond[]   = "CONCAT(p.apellido_paterno,' ',p.apellido_materno,' ',p.nombres) LIKE ?";
                $params[] = '%' . $termino . '%';
            }
        }

        return [implode(' AND ', $cond), $params];
    }

    /**
     * Lista matrículas con datos completos: estudiante, grado, sección y
     * apoderado responsable. Acepta filtros y paginación (limit/offset).
     */
    public function listar(array $filtros = []): array
    {
        [$where, $params] = $this->construirFiltros($filtros);

        $limit  = max(1, min(200, (int) ($filtros['limit']  ?? 25)));
        $offset = max(0, (int) ($filtros['offset'] ?? 0));

        return $this->query("
            SELECT
                m.id,
                m.estado,
                m.motivo_estado,
                m.tipo,
                m.serie_recibo,
                m.fecha_registro,
                m.seccion_id,
                p.dni,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                CONCAT(p.apellido_paterno,' ',p.apellido_materno,', ',p.nombres) AS nombre_completo,
                g.nombre_display AS grado_nombre,
                g.numero         AS grado_numero,
                n.nombre         AS nivel_nombre,
                s.nombre         AS seccion_nombre,
                a.anio,
                -- Rol de la fila dentro de un retorno de grado ACTIVO:
                --  ro presente → m es la matricula OFICIAL (su pareja operativa = ro.matricula_operativa_id)
                --  rp presente → m es la matricula OPERATIVA (su pareja oficial = rp.matricula_oficial_id)
                ro.matricula_operativa_id AS retorno_operativa_id,
                rp.matricula_oficial_id   AS retorno_oficial_id,
                (
                    SELECT CONCAT(pap.apellido_paterno,' ',pap.apellido_materno,', ',pap.nombres)
                    FROM vinculo_familiar vf
                    INNER JOIN apoderados ap ON ap.id = vf.apoderado_id
                    INNER JOIN personas  pap ON pap.id = ap.persona_id
                    WHERE vf.estudiante_id = e.id AND vf.es_responsable = 1
                    LIMIT 1
                ) AS apoderado_responsable
            FROM matriculas m
            INNER JOIN estudiantes e      ON e.id = m.estudiante_id
            INNER JOIN personas p         ON p.id = e.persona_id
            INNER JOIN anios_academicos a ON a.id = m.anio_id
            LEFT  JOIN secciones s        ON s.id = m.seccion_id
            LEFT  JOIN grados g           ON g.id = s.grado_id
            LEFT  JOIN niveles n          ON n.id = g.nivel_id
            LEFT  JOIN retornos_grado ro  ON ro.matricula_oficial_id  = m.id AND ro.estado = 'activo'
            LEFT  JOIN retornos_grado rp  ON rp.matricula_operativa_id = m.id AND rp.estado = 'activo'
            WHERE {$where}
            ORDER BY m.fecha_registro DESC, m.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ", $params);
    }

    /** Cuenta el total de matrículas que cumplen los filtros (para paginación). */
    public function contar(array $filtros = []): int
    {
        [$where, $params] = $this->construirFiltros($filtros);
        $r = $this->queryOne("
            SELECT COUNT(*) AS total
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            LEFT  JOIN secciones s   ON s.id = m.seccion_id
            LEFT  JOIN grados g      ON g.id = s.grado_id
            WHERE {$where}
        ", $params);
        return (int) ($r['total'] ?? 0);
    }

    /**
     * Estadísticas de matrícula de un año para el dashboard /matriculas/resumen.
     * "Matriculados" = estado='aprobada' (vigentes); las desactivadas se reportan
     * aparte como KPI. Todo scopeado al año indicado.
     *
     * Retorna ['kpis'=>..., 'por_grado'=>[...], 'por_tipo'=>[...], 'por_genero'=>[...]].
     */
    public function getResumen(int $anioId): array
    {
        // 1) KPIs por estado.
        $estados = $this->query("
            SELECT m.estado, COUNT(*) AS n
            FROM matriculas m
            WHERE m.anio_id = ?
            GROUP BY m.estado
        ", [$anioId]);

        $porEstado = ['aprobada' => 0, 'pendiente' => 0, 'desactivado' => 0];
        foreach ($estados as $e) {
            $porEstado[$e['estado']] = (int) $e['n'];
        }

        $secc = $this->queryOne("
            SELECT COUNT(DISTINCT m.seccion_id) AS n
            FROM matriculas m
            WHERE m.anio_id = ? AND m.estado = 'aprobada'
        ", [$anioId]);
        $nSecciones = (int) ($secc['n'] ?? 0);

        $kpis = [
            'aprobadas'    => $porEstado['aprobada'],
            'pendientes'   => $porEstado['pendiente'],
            'desactivadas' => $porEstado['desactivado'],
            'secciones'    => $nSecciones,
            'promedio_seccion' => $nSecciones > 0
                ? round($porEstado['aprobada'] / $nSecciones, 1)
                : 0.0,
        ];

        // 2) Matriculados por grado (agrupable por nivel en la vista).
        $porGrado = $this->query("
            SELECT
                n.id     AS nivel_id,
                n.nombre AS nivel_nombre,
                n.codigo AS nivel_codigo,
                g.id     AS grado_id,
                g.numero AS grado_numero,
                g.nombre_display AS grado_nombre,
                COUNT(*) AS n
            FROM matriculas m
            INNER JOIN secciones s ON s.id = m.seccion_id
            INNER JOIN grados g    ON g.id = s.grado_id
            INNER JOIN niveles n   ON n.id = g.nivel_id
            WHERE m.anio_id = ? AND m.estado = 'aprobada'
            GROUP BY n.id, n.nombre, n.codigo, g.id, g.numero, g.nombre_display
            ORDER BY n.id, g.numero
        ", [$anioId]);

        // 2b) Matriculados por sección (con desglose de sexo para el gráfico apilado).
        $porSeccion = $this->query("
            SELECT
                n.id     AS nivel_id,
                n.codigo AS nivel_codigo,
                g.numero AS grado_numero,
                s.id     AS seccion_id,
                s.nombre AS seccion_nombre,
                COUNT(*)                  AS n,
                SUM(p.sexo = 'M')         AS m,
                SUM(p.sexo = 'F')         AS f,
                SUM(p.sexo IS NULL)       AS sin_dato
            FROM matriculas m
            INNER JOIN secciones s   ON s.id = m.seccion_id
            INNER JOIN grados g      ON g.id = s.grado_id
            INNER JOIN niveles n     ON n.id = g.nivel_id
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            WHERE m.anio_id = ? AND m.estado = 'aprobada'
            GROUP BY n.id, n.codigo, g.numero, s.id, s.nombre
            ORDER BY n.id, g.numero, s.nombre
        ", [$anioId]);

        // 3) Matriculados por tipo.
        $porTipo = $this->query("
            SELECT m.tipo, COUNT(*) AS n
            FROM matriculas m
            WHERE m.anio_id = ? AND m.estado = 'aprobada'
            GROUP BY m.tipo
        ", [$anioId]);

        // 4) Matriculados por género (NULL => 'sin_dato') + cobertura.
        $porGenero = $this->query("
            SELECT COALESCE(p.sexo, 'ND') AS sexo, COUNT(*) AS n
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            WHERE m.anio_id = ? AND m.estado = 'aprobada'
            GROUP BY COALESCE(p.sexo, 'ND')
        ", [$anioId]);

        $gen = ['M' => 0, 'F' => 0, 'ND' => 0];
        foreach ($porGenero as $g) {
            $gen[$g['sexo']] = (int) $g['n'];
        }
        $totalGen = $gen['M'] + $gen['F'] + $gen['ND'];
        $conDato  = $gen['M'] + $gen['F'];

        return [
            'kpis'      => $kpis,
            'por_grado' => array_map(static fn($r) => [
                'nivel_nombre' => $r['nivel_nombre'],
                'nivel_codigo' => $r['nivel_codigo'],
                'grado_nombre' => $r['grado_nombre'],
                'grado_numero' => (int) $r['grado_numero'],
                'n'            => (int) $r['n'],
            ], $porGrado),
            'por_seccion' => array_map(static fn($r) => [
                'nivel_codigo'   => $r['nivel_codigo'],
                'grado_numero'   => (int) $r['grado_numero'],
                'seccion_nombre' => $r['seccion_nombre'],
                'n'              => (int) $r['n'],
                'm'              => (int) $r['m'],
                'f'              => (int) $r['f'],
                'sin_dato'       => (int) $r['sin_dato'],
            ], $porSeccion),
            'por_tipo'  => array_map(static fn($r) => [
                'tipo' => $r['tipo'],
                'n'    => (int) $r['n'],
            ], $porTipo),
            'por_genero' => [
                'm'         => $gen['M'],
                'f'         => $gen['F'],
                'sin_dato'  => $gen['ND'],
                'cobertura' => $totalGen > 0 ? round($conDato / $totalGen * 100, 1) : 0.0,
            ],
        ];
    }

    /** Matrícula con todos los datos del estudiante, grado y sección. */
    public function findById(int $id): ?array
    {
        return $this->queryOne("
            SELECT
                m.*,
                e.id             AS estudiante_real_id,
                e.codigo_estudiante,
                p.id             AS persona_id,
                p.dni,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                p.fecha_nacimiento,
                p.sexo,
                CONCAT(p.apellido_paterno,' ',p.apellido_materno,', ',p.nombres) AS nombre_completo,
                g.id             AS grado_id,
                g.nombre_display AS grado_nombre,
                g.numero         AS grado_numero,
                n.id             AS nivel_id,
                n.nombre         AS nivel_nombre,
                s.nombre         AS seccion_nombre,
                a.anio
            FROM matriculas m
            INNER JOIN estudiantes e      ON e.id = m.estudiante_id
            INNER JOIN personas p         ON p.id = e.persona_id
            INNER JOIN anios_academicos a ON a.id = m.anio_id
            LEFT  JOIN secciones s        ON s.id = m.seccion_id
            LEFT  JOIN grados g           ON g.id = s.grado_id
            LEFT  JOIN niveles n          ON n.id = g.nivel_id
            WHERE m.id = ?
            LIMIT 1
        ", [$id]);
    }

    /**
     * Si la matrícula es la OPERATIVA de un retorno de grado ACTIVO, devuelve
     * el id de su matrícula oficial (hub de gestión); null en caso contrario.
     * Se usa para bloquear el acceso directo al detalle de la operativa: toda
     * la gestión del retorno vive en la oficial.
     */
    public function oficialSiEsOperativaEnRetornoActivo(int $matriculaId): ?int
    {
        $r = $this->queryOne(
            "SELECT matricula_oficial_id
             FROM retornos_grado
             WHERE matricula_operativa_id = ? AND estado = 'activo'
             LIMIT 1",
            [$matriculaId]
        );
        return $r ? (int) $r['matricula_oficial_id'] : null;
    }

    /** ¿El estudiante ya tiene matrícula en ese año académico? */
    public function existeMatricula(int $estudianteId, int $anioId): bool
    {
        $r = $this->queryOne(
            "SELECT id FROM matriculas WHERE estudiante_id = ? AND anio_id = ? LIMIT 1",
            [$estudianteId, $anioId]
        );
        return $r !== null;
    }

    /**
     * Crea una matrícula. SIEMPRE nace en estado='pendiente'.
     * Datos esperados: estudiante_id, seccion_id, anio_id, tipo,
     * serie_recibo, registrado_por.
     */
    public function crear(array $datos): int
    {
        return $this->create([
            'estudiante_id'  => (int) $datos['estudiante_id'],
            'seccion_id'     => $datos['seccion_id'] !== null ? (int) $datos['seccion_id'] : null,
            'anio_id'        => (int) $datos['anio_id'],
            'tipo'           => $datos['tipo'] ?? 'continuador',
            'serie_recibo'   => $datos['serie_recibo'] ?? null,
            'estado'         => 'pendiente',
            'fecha_registro' => date('Y-m-d'),
            'registrado_por' => (int) $datos['registrado_por'],
        ]);
    }

    /**
     * Cambia el estado de una matrícula. Guarda el motivo VISIBLE en
     * `motivo_estado` (lo que se muestra junto al badge) y deja además traza
     * histórica en `observaciones`. El control de roles (solo
     * registro_academico/admin para activar/desactivar) se hace en el controlador.
     *
     * @param ?string $motivo Motivo visible del estado. NULL lo limpia (p.ej. al
     *                        aprobar). Obligatorio en 'desactivado' (lo exige el
     *                        controlador, no este método).
     */
    public function cambiarEstado(int $id, string $estado, int $usuarioId, ?string $motivo = null): bool
    {
        $traza = sprintf(
            "[%s] estado → %s por usuario #%d%s",
            date('Y-m-d H:i'),
            $estado,
            $usuarioId,
            $motivo !== null && $motivo !== '' ? ' — ' . $motivo : ''
        );

        $sql = "UPDATE matriculas
                SET estado = ?,
                    motivo_estado = ?,
                    observaciones = TRIM(CONCAT(COALESCE(observaciones,''), '\n', ?))";

        $params = [$estado, ($motivo !== '' ? $motivo : null), $traza];

        // Si pasa a 'aprobada' registra aprobador y fecha de aprobación.
        if ($estado === 'aprobada') {
            $sql .= ", aprobado_por = ?, fecha_aprobacion = CURDATE()";
            $params[] = $usuarioId;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        return $this->execute($sql, $params);
    }

    // ── Sugerencia de sección ────────────────────────────────────

    /**
     * Sugiere la sección con MENOS matrículas activas del grado en el año.
     * Es el criterio para tipo='nuevo' (y el fallback general).
     */
    public function sugerirSeccion(int $gradoId, int $anioId): ?array
    {
        return $this->queryOne("
            SELECT
                s.id,
                s.nombre,
                COUNT(m.id) AS total
            FROM secciones s
            LEFT JOIN matriculas m
                ON m.seccion_id = s.id
               AND m.estado IN ('aprobada','pendiente')
            WHERE s.grado_id = ? AND s.anio_id = ?
            GROUP BY s.id, s.nombre
            ORDER BY total ASC, s.nombre ASC
            LIMIT 1
        ", [$gradoId, $anioId]);
    }

    /**
     * Para tipo='continuador': la sección del estudiante en el año anterior
     * (misma letra de sección en el grado correspondiente del año actual).
     * Si no se halla, retorna null y el controlador cae a sugerirSeccion().
     */
    public function seccionAnioAnterior(int $estudianteId, int $anioActual): ?array
    {
        return $this->queryOne("
            SELECT s.nombre
            FROM matriculas m
            INNER JOIN secciones s ON s.id = m.seccion_id
            INNER JOIN anios_academicos a ON a.id = m.anio_id
            WHERE m.estudiante_id = ? AND a.anio < ?
            ORDER BY a.anio DESC
            LIMIT 1
        ", [$estudianteId, $anioActual]);
    }

    // ── Estudiante + persona (alta) ──────────────────────────────

    /** Busca una persona por DNI (para reutilizarla si ya existe). */
    public function personaPorDni(string $dni): ?array
    {
        return $this->queryOne(
            "SELECT * FROM personas WHERE dni = ? LIMIT 1",
            [$dni]
        );
    }

    /** Busca un estudiante (con su persona) por DNI. */
    public function estudiantePorDni(string $dni): ?array
    {
        return $this->queryOne("
            SELECT
                e.id AS estudiante_id,
                p.id AS persona_id,
                p.dni,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                p.fecha_nacimiento,
                p.sexo
            FROM estudiantes e
            INNER JOIN personas p ON p.id = e.persona_id
            WHERE p.dni = ?
            LIMIT 1
        ", [$dni]);
    }

    /**
     * Crea un estudiante nuevo (persona + estudiante) en una transacción.
     * Si la persona ya existe por DNI la reutiliza. Retorna estudiante_id.
     */
    public function crearEstudianteConPersona(array $datosPersona): int
    {
        $this->beginTransaction();
        try {
            $persona = $this->personaPorDni($datosPersona['dni']);

            if ($persona) {
                $personaId = (int) $persona['id'];
            } else {
                $cols = implode(', ', array_keys($datosPersona));
                $ph   = implode(', ', array_fill(0, count($datosPersona), '?'));
                $this->execute(
                    "INSERT INTO personas ({$cols}) VALUES ({$ph})",
                    array_values($datosPersona)
                );
                $personaId = (int) $this->db->lastInsertId();
            }

            // Si la persona ya es estudiante, reutiliza ese estudiante.
            $est = $this->queryOne(
                "SELECT id FROM estudiantes WHERE persona_id = ? LIMIT 1",
                [$personaId]
            );
            if ($est) {
                $estudianteId = (int) $est['id'];
            } else {
                $this->execute(
                    "INSERT INTO estudiantes (persona_id) VALUES (?)",
                    [$personaId]
                );
                $estudianteId = (int) $this->db->lastInsertId();
            }

            $this->commit();
            return $estudianteId;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    // ── Documentos ───────────────────────────────────────────────

    public function getDocumentos(int $matriculaId): array
    {
        return $this->query("
            SELECT
                d.*,
                CONCAT(p.apellido_paterno,' ',p.nombres) AS registrado_por_nombre
            FROM documentos_matricula d
            LEFT JOIN usuarios u ON u.id = d.registrado_por
            LEFT JOIN personas p ON p.id = u.persona_id
            WHERE d.matricula_id = ?
            ORDER BY d.id
        ", [$matriculaId]);
    }

    /**
     * Registra (o actualiza) el estado de un documento de la matrícula.
     * Idempotente gracias al UNIQUE (matricula_id, tipo_documento).
     */
    public function registrarDocumento(
        int $matriculaId,
        string $tipo,
        bool $entregado,
        int $usuarioId,
        ?string $observacion = null
    ): bool {
        return $this->execute("
            INSERT INTO documentos_matricula
                (matricula_id, tipo_documento, entregado, observacion, registrado_por)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                entregado      = VALUES(entregado),
                observacion    = VALUES(observacion),
                registrado_por = VALUES(registrado_por),
                registrado_en  = CURRENT_TIMESTAMP
        ", [$matriculaId, $tipo, $entregado ? 1 : 0, $observacion, $usuarioId]);
    }

    // ── Notas externas (traslado de entrada) ─────────────────────

    public function getNotasExternas(int $matriculaId): array
    {
        return $this->query("
            SELECT *
            FROM notas_externas
            WHERE matricula_id = ?
            ORDER BY area_nombre, competencia_nombre
        ", [$matriculaId]);
    }

    /**
     * Registra una nota externa. Idempotente por el UNIQUE
     * (matricula_id, periodo_nombre, competencia_nombre).
     */
    public function registrarNotaExterna(array $datos): bool
    {
        return $this->execute("
            INSERT INTO notas_externas
                (matricula_id, periodo_nombre, competencia_nombre,
                 area_nombre, nota_literal, colegio_origen, registrado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                area_nombre    = VALUES(area_nombre),
                nota_literal   = VALUES(nota_literal),
                colegio_origen = VALUES(colegio_origen),
                registrado_por = VALUES(registrado_por),
                registrado_en  = CURRENT_TIMESTAMP
        ", [
            (int) $datos['matricula_id'],
            $datos['periodo_nombre'],
            $datos['competencia_nombre'],
            $datos['area_nombre'],
            $datos['nota_literal'],
            $datos['colegio_origen'] ?? null,
            (int) $datos['registrado_por'],
        ]);
    }

    // ── Datos auxiliares para filtros y selects ──────────────────

    public function listarAnios(): array
    {
        return $this->query("
            SELECT id, anio, estado
            FROM anios_academicos
            ORDER BY anio DESC
        ");
    }

    public function listarGrados(): array
    {
        return $this->query("
            SELECT g.id, g.numero, g.nombre_display, n.id AS nivel_id, n.nombre AS nivel_nombre
            FROM grados g
            INNER JOIN niveles n ON n.id = g.nivel_id
            ORDER BY n.id, g.numero
        ");
    }

    /** Secciones de un año (o de un grado concreto) para los selects. */
    public function listarSecciones(int $anioId, ?int $gradoId = null): array
    {
        $sql = "
            SELECT s.id, s.nombre, s.grado_id,
                   g.nombre_display AS grado_nombre, g.numero AS grado_numero,
                   n.nombre AS nivel_nombre
            FROM secciones s
            INNER JOIN grados g  ON g.id = s.grado_id
            INNER JOIN niveles n ON n.id = g.nivel_id
            WHERE s.anio_id = ?";
        $params = [$anioId];
        if ($gradoId) {
            $sql .= " AND s.grado_id = ?";
            $params[] = $gradoId;
        }
        $sql .= " ORDER BY n.id, g.numero, s.nombre";
        return $this->query($sql, $params);
    }
}
