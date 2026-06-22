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
    /**
     * Columnas ordenables: clave pública => columnas SQL reales.
     * Lista blanca obligatoria — el ORDER BY no se puede parametrizar, así que
     * el nombre de columna NUNCA puede venir directo del usuario. La dirección
     * se aplica a CADA columna del grupo (no solo a la última).
     */
    public const ORDENABLES = [
        'estudiante'   => ['p.apellido_paterno', 'p.apellido_materno', 'p.nombres'],
        'dni'          => ['p.dni'],
        'grado'        => ['n.id', 'g.numero', 's.nombre'],
        'estado'       => ['m.estado'],
        'registro'     => ['m.fecha_registro'],
        'modificacion' => ['m.updated_at'],
    ];

    public function listar(array $filtros = []): array
    {
        [$where, $params] = $this->construirFiltros($filtros);

        $limit  = max(1, min(200, (int) ($filtros['limit']  ?? 25)));
        $offset = max(0, (int) ($filtros['offset'] ?? 0));

        // Orden seguro desde la lista blanca (default: modificación reciente).
        $cols = self::ORDENABLES[$filtros['orden'] ?? ''] ?? self::ORDENABLES['modificacion'];
        $dir  = strtolower((string) ($filtros['dir'] ?? '')) === 'asc' ? 'ASC' : 'DESC';
        $orderBy = implode(', ', array_map(static fn($c) => "$c $dir", $cols)) . ', m.id DESC';

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
            ORDER BY {$orderBy}
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
     * Listado COMPLETO (sin paginación) para la nómina detallada admin/RA
     * (reporte al comité directivo). A diferencia de la nómina del docente:
     *  - Incluye AMBAS matrículas de un retorno de grado (oficial Y operativa),
     *    cada una con el cruce de su contraparte (regla R3). No oculta la operativa.
     *  - Trae género (p.sexo), DNI y celular del apoderado responsable.
     * Usa los MISMOS filtros que listar()/contar() (construirFiltros), pero NO
     * modifica esos métodos: el index de /matriculas queda intacto.
     * Ordenado por nivel→grado→sección→apellidos para agrupar por sección.
     */
    public function listarParaNomina(array $filtros = []): array
    {
        [$where, $params] = $this->construirFiltros($filtros);

        return $this->query("
            SELECT
                m.id,
                m.estado,
                m.motivo_estado,
                m.tipo,
                p.dni,
                p.sexo,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                CONCAT(p.apellido_paterno,' ',p.apellido_materno,', ',p.nombres) AS nombre_completo,
                n.id     AS nivel_id,
                n.nombre AS nivel_nombre,
                g.id     AS grado_id,
                g.numero AS grado_numero,
                g.nombre_display AS grado_nombre,
                s.id     AS seccion_id,
                s.nombre AS seccion_nombre,
                a.anio,
                -- Rol de la fila dentro de un retorno de grado ACTIVO (igual que listar()):
                --   retorno_operativa_id presente → m es la OFICIAL (alumno cursa en otro grado)
                --   retorno_oficial_id   presente → m es la OPERATIVA (alumno cursa AQUÍ)
                ro.matricula_operativa_id AS retorno_operativa_id,
                rp.matricula_oficial_id   AS retorno_oficial_id,
                -- Ubicación de la contraparte para la nota cruzada:
                CONCAT(go.nombre_display,' ',so.nombre) AS retorno_op_ubic,
                CONCAT(gf.nombre_display,' ',sf.nombre) AS retorno_of_ubic,
                ap.telefono AS apoderado_telefono,
                TRIM(CONCAT(
                    COALESCE(ap.apellido_paterno,''),' ',
                    COALESCE(ap.apellido_materno,''),' ',
                    COALESCE(ap.nombres,'')
                )) AS apoderado_nombre
            FROM matriculas m
            INNER JOIN estudiantes e      ON e.id = m.estudiante_id
            INNER JOIN personas p         ON p.id = e.persona_id
            INNER JOIN anios_academicos a ON a.id = m.anio_id
            LEFT  JOIN secciones s        ON s.id = m.seccion_id
            LEFT  JOIN grados g           ON g.id = s.grado_id
            LEFT  JOIN niveles n          ON n.id = g.nivel_id
            LEFT  JOIN retornos_grado ro  ON ro.matricula_oficial_id  = m.id AND ro.estado = 'activo'
            LEFT  JOIN retornos_grado rp  ON rp.matricula_operativa_id = m.id AND rp.estado = 'activo'
            LEFT  JOIN matriculas mo ON mo.id = ro.matricula_operativa_id
            LEFT  JOIN secciones so  ON so.id = mo.seccion_id
            LEFT  JOIN grados go     ON go.id = so.grado_id
            LEFT  JOIN matriculas mf ON mf.id = rp.matricula_oficial_id
            LEFT  JOIN secciones sf  ON sf.id = mf.seccion_id
            LEFT  JOIN grados gf     ON gf.id = sf.grado_id
            LEFT  JOIN vinculo_familiar vf
                ON  vf.estudiante_id = e.id
                AND vf.es_responsable = 1
                AND vf.id = (
                    SELECT MIN(vf2.id) FROM vinculo_familiar vf2
                    WHERE vf2.estudiante_id = e.id AND vf2.es_responsable = 1
                )
            LEFT  JOIN apoderados apo ON apo.id = vf.apoderado_id
            LEFT  JOIN personas ap    ON ap.id = apo.persona_id
            WHERE {$where}
            ORDER BY n.id, g.numero, s.nombre,
                     p.apellido_paterno, p.apellido_materno, p.nombres
        ", $params);
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

    /**
     * Cuadro cruzado de matrícula por grado (panorama del año para el comité):
     * filas = grado (agrupable por nivel), columnas = tipo × estado × género × total.
     * Cuenta TODAS las matrículas del año (todos los estados). Retorno de grado:
     * cuenta UNA sola vez por la matrícula OFICIAL (excluye la operativa) para no
     * inflar el panorama. Filtro opcional por nivel. El género NO suma a quien no
     * tiene sexo registrado (M + F puede ser < total, como acordado).
     */
    public function getCuadroMatricula(int $anioId, ?int $nivelId = null): array
    {
        $cond   = ['m.anio_id = ?'];
        $params = [$anioId];
        if ($nivelId) {
            $cond[]   = 'n.id = ?';
            $params[] = $nivelId;
        }
        $where = implode(' AND ', $cond);

        $rows = $this->query("
            SELECT
                n.id     AS nivel_id,
                n.nombre AS nivel_nombre,
                g.numero AS grado_numero,
                g.nombre_display AS grado_nombre,
                SUM(m.tipo = 'nuevo')         AS t_nuevo,
                SUM(m.tipo = 'continuador')   AS t_cont,
                SUM(m.tipo = 'trasladado')    AS t_tras,
                SUM(m.estado = 'aprobada')    AS e_aprob,
                SUM(m.estado = 'pendiente')   AS e_pend,
                SUM(m.estado = 'desactivado') AS e_desact,
                SUM(p.sexo = 'M')             AS gen_m,
                SUM(p.sexo = 'F')             AS gen_f,
                COUNT(*)                      AS total
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            INNER JOIN secciones s   ON s.id = m.seccion_id
            INNER JOIN grados g      ON g.id = s.grado_id
            INNER JOIN niveles n     ON n.id = g.nivel_id
            WHERE {$where}
              AND m.id NOT IN (
                  SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'activo'
              )
            GROUP BY n.id, n.nombre, g.numero, g.nombre_display
            ORDER BY n.id, g.numero
        ", $params);

        return array_map(static fn($r) => [
            'nivel_id'     => (int) $r['nivel_id'],
            'nivel_nombre' => $r['nivel_nombre'],
            'grado_numero' => (int) $r['grado_numero'],
            'grado_nombre' => $r['grado_nombre'],
            't_nuevo'  => (int) $r['t_nuevo'],
            't_cont'   => (int) $r['t_cont'],
            't_tras'   => (int) $r['t_tras'],
            'e_aprob'  => (int) $r['e_aprob'],
            'e_pend'   => (int) $r['e_pend'],
            'e_desact' => (int) $r['e_desact'],
            'gen_m'    => (int) $r['gen_m'],
            'gen_f'    => (int) $r['gen_f'],
            'total'    => (int) $r['total'],
        ], $rows);
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

    /**
     * ¿El DNI ya pertenece a OTRA persona (distinta de $personaId)? Se usa al
     * editar los datos personales para no romper la unicidad del DNI.
     */
    public function dniEnUsoPorOtra(string $dni, int $personaId): bool
    {
        $r = $this->queryOne(
            "SELECT id FROM personas WHERE dni = ? AND id <> ? LIMIT 1",
            [$dni, $personaId]
        );
        return $r !== null;
    }

    /**
     * Actualiza los datos personales del estudiante (tabla personas, compartida
     * por todos sus años/matrículas). Solo toca identidad básica; NO el grado,
     * sección ni estado de la matrícula. `updated_at` se refresca solo.
     */
    public function actualizarDatosPersonales(int $personaId, array $d): bool
    {
        return $this->execute(
            "UPDATE personas
                SET dni = ?, apellido_paterno = ?, apellido_materno = ?,
                    nombres = ?, fecha_nacimiento = ?, sexo = ?
              WHERE id = ?",
            [
                $d['dni'],
                $d['apellido_paterno'],
                $d['apellido_materno'],
                $d['nombres'],
                $d['fecha_nacimiento'],
                $d['sexo'],
                $personaId,
            ]
        );
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

    /** Niveles educativos (para el filtro del cuadro de matrícula). */
    public function listarNiveles(): array
    {
        return $this->query("SELECT id, nombre, codigo FROM niveles ORDER BY id");
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
