<?php

namespace App\Models;

/**
 * SiagieExportModel
 *
 * Capa de datos del volcado de notas SIGA → Excel SIAGIE (registro oficial
 * ante UGEL-MINEDU). Reutiliza las fuentes de verdad existentes:
 *   - CalificacionModel::boletaContexto/getBoletaAlumno → solo competencias
 *     BLOQUEADAS + transversales agregadas con cierre vigente del tutor,
 *     con unión oficial/operativa en retorno de grado.
 *   - ExoneracionModel → competencias exoneradas (celdas que se omiten).
 *
 * Es PURO respecto a la presentación: no sabe de Excel. Lo consume el CLI
 * scripts/siagie/llenar-siagie.php y, a futuro, el módulo web.
 */
class SiagieExportModel extends BaseModel
{
    private CalificacionModel $calModel;
    private ExoneracionModel  $exoModel;

    public function __construct()
    {
        parent::__construct();
        $this->calModel = new CalificacionModel();
        $this->exoModel = new ExoneracionModel();
    }

    /**
     * Resuelve los parámetros del archivo SIAGIE (hoja oculta "Parametros")
     * contra las entidades de SIGA. Devuelve ['error' => string] si algo no
     * calza — el archivo completo se rechaza con ese motivo.
     *
     * @param array $p ['anio'=>2026, 'nivel_nombre'=>'Primaria',
     *                  'periodo_codigo'=>'B1', 'seccion_texto'=>'1A']
     */
    public function resolverDestino(array $p): array
    {
        $anio = $this->queryOne(
            "SELECT id FROM anios_academicos WHERE anio = ?",
            [(int) ($p['anio'] ?? 0)]
        );
        if (!$anio) {
            return ['error' => "Año académico {$p['anio']} no existe en SIGA"];
        }

        $nivel = $this->queryOne(
            "SELECT id, codigo, nombre FROM niveles WHERE UPPER(nombre) = ?",
            [mb_strtoupper(trim((string) ($p['nivel_nombre'] ?? '')))]
        );
        if (!$nivel) {
            return ['error' => "Nivel '{$p['nivel_nombre']}' no existe en SIGA"];
        }

        if (!preg_match('/^(\d)\s*([A-ZÑ])$/u', mb_strtoupper(trim((string) ($p['seccion_texto'] ?? ''))), $m)) {
            return ['error' => "Sección '{$p['seccion_texto']}' con formato no reconocido (se espera '1A')"];
        }
        $gradoNumero  = (int) $m[1];
        $seccionLetra = $m[2];

        $grado = $this->queryOne(
            "SELECT id, nombre_display FROM grados WHERE nivel_id = ? AND numero = ?",
            [$nivel['id'], $gradoNumero]
        );
        if (!$grado) {
            return ['error' => "Grado {$gradoNumero}° de {$nivel['nombre']} no existe en SIGA"];
        }

        $seccion = $this->queryOne(
            "SELECT id, nombre FROM secciones WHERE grado_id = ? AND anio_id = ? AND nombre = ?",
            [$grado['id'], $anio['id'], $seccionLetra]
        );
        if (!$seccion) {
            return ['error' => "Sección {$gradoNumero}{$seccionLetra} de {$nivel['nombre']} no existe en el año {$p['anio']}"];
        }

        if (!preg_match('/^B(\d)$/', strtoupper(trim((string) ($p['periodo_codigo'] ?? ''))), $mp)) {
            return ['error' => "Periodo '{$p['periodo_codigo']}' con formato no reconocido (se espera 'B1'..'B4')"];
        }
        $periodo = $this->queryOne(
            "SELECT id, numero, estado, nombre_display FROM periodos WHERE anio_id = ? AND numero = ?",
            [$anio['id'], (int) $mp[1]]
        );
        if (!$periodo) {
            return ['error' => "Bimestre {$mp[1]} no existe en el año {$p['anio']}"];
        }

        return [
            'anio_id'         => (int) $anio['id'],
            'nivel_id'        => (int) $nivel['id'],
            'nivel_codigo'    => $nivel['codigo'],
            'nivel_nombre'    => $nivel['nombre'],
            'grado_id'        => (int) $grado['id'],
            'grado_numero'    => $gradoNumero,
            'seccion_id'      => (int) $seccion['id'],
            'seccion_nombre'  => $seccion['nombre'],
            'periodo_id'      => (int) $periodo['id'],
            'periodo_numero'  => (int) $periodo['numero'],
            'periodo_estado'  => $periodo['estado'],
            'periodo_nombre'  => $periodo['nombre_display'],
        ];
    }

    /**
     * Universo de estudiantes de la sección para el matching: matrículas
     * APROBADAS, excluyendo las OPERATIVAS de un retorno de grado activo
     * (ese alumno figura en el archivo SIAGIE de su sección OFICIAL).
     */
    public function estudiantesDeSeccion(int $seccionId): array
    {
        return $this->query("
            SELECT
                m.id  AS matricula_id,
                e.id  AS estudiante_id,
                e.codigo_estudiante,
                p.dni,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            WHERE m.seccion_id = ?
              AND m.estado     = 'aprobada'
              AND m.id NOT IN (
                  SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'activo'
              )
            ORDER BY p.apellido_paterno, p.apellido_materno, p.nombres
        ", [$seccionId]);
    }

    /**
     * Estudiantes de las OTRAS secciones del mismo grado y año (para detectar
     * un cambio de sección sin tramitar: la fila del SIAGIE pertenece a un
     * alumno que en SIGA sigue en otra sección). Mismos filtros que
     * estudiantesDeSeccion (aprobadas, excluye operativas de retorno activo)
     * MÁS la sección de origen (id + nombre) para poder informarla.
     */
    public function estudiantesDeOtrasSecciones(int $gradoId, int $anioId, int $seccionExcluida): array
    {
        return $this->query("
            SELECT
                m.id     AS matricula_id,
                e.id     AS estudiante_id,
                e.codigo_estudiante,
                p.dni,
                p.apellido_paterno,
                p.apellido_materno,
                p.nombres,
                s.id     AS seccion_id,
                s.nombre AS seccion_nombre
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            INNER JOIN secciones s    ON s.id = m.seccion_id
            WHERE s.grado_id   = ?
              AND s.anio_id    = ?
              AND m.seccion_id <> ?
              AND m.estado     = 'aprobada'
              AND m.id NOT IN (
                  SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'activo'
              )
            ORDER BY s.nombre, p.apellido_paterno, p.apellido_materno, p.nombres
        ", [$gradoId, $anioId, $seccionExcluida]);
    }

    /**
     * Notas oficiales del alumno en el periodo, indexadas por competencia_id.
     * Reutiliza boletaContexto (unión oficial/operativa en retorno) y
     * getBoletaAlumno (solo bloqueadas + transversales con cierre del tutor).
     * En choque por competencia gana la fuente posterior (la oficial).
     *
     * @return array competencia_id => ['nota_numerica'=>int, 'conclusion'=>?string]
     */
    public function notasOficiales(int $matriculaId, int $periodoId): array
    {
        $ctx   = $this->calModel->boletaContexto($matriculaId);
        $notas = [];
        foreach ($ctx['fuentes'] as $fuente) {
            foreach ($this->calModel->getBoletaAlumno((int) $fuente, $periodoId) as $fila) {
                $notas[(int) $fila['competencia_id']] = [
                    'nota_numerica' => (int) $fila['nota_numerica'],
                    'conclusion'    => $fila['conclusion_descriptiva'] !== null
                        ? trim((string) $fila['conclusion_descriptiva'])
                        : null,
                ];
            }
        }
        return $notas;
    }

    /**
     * Set de competencias exoneradas del alumno en el año (unión de fuentes
     * en retorno). Sus celdas en el SIAGIE están bloqueadas → se omiten.
     *
     * @return array competencia_id => true
     */
    public function competenciasExoneradas(int $matriculaId, int $anioId): array
    {
        $ctx = $this->calModel->boletaContexto($matriculaId);
        $set = [];
        foreach ($this->exoModel->getConCompetenciasParaBoletaUnion($ctx['fuentes'], $anioId) as $fila) {
            $set[(int) $fila['competencia_id']] = true;
        }
        return $set;
    }

    /**
     * Catálogo de competencias del nivel (directas y vía subáreas) para
     * mapear la leyenda de cada hoja SIAGIE → competencia SIGA.
     */
    public function competenciasDelNivel(int $nivelId): array
    {
        return $this->query("
            SELECT
                c.id   AS competencia_id,
                c.nombre_completo,
                c.codigo_minedu,
                a.id   AS area_id,
                a.nombre AS area_nombre,
                a.tipo AS area_tipo
            FROM competencias c
            LEFT JOIN subareas s ON s.id = c.subarea_id
            INNER JOIN areas a   ON a.id = COALESCE(c.area_id, s.area_id)
            WHERE a.nivel_id = ?
            ORDER BY a.orden, c.orden
        ", [$nivelId]);
    }

    /**
     * Área del nivel cuya `codigo_siagie` incluye el código de la hoja SIAGIE
     * (p. ej. '063' → Matemática). `codigo_siagie` puede ser compuesto
     * ('0006,0007' para las dos hojas transversales) → se busca por FIND_IN_SET.
     * Devuelve null si ninguna área tiene ese código (hoja sin equivalente, o
     * nivel aún sin poblar como primaria).
     */
    public function areaPorCodigoSiagie(int $nivelId, string $codigo): ?array
    {
        return $this->queryOne("
            SELECT id, nombre, tipo
            FROM areas
            WHERE nivel_id = ?
              AND codigo_siagie IS NOT NULL
              AND FIND_IN_SET(?, codigo_siagie)
            LIMIT 1
        ", [$nivelId, $codigo]) ?: null;
    }

    /**
     * Competencias de UN área (directas y vía subáreas), en orden. Se usa para
     * resolver una columna dentro de su área: desambiguar homónimos (Matemática
     * vs Taller) por área, o asignar por posición cuando la leyenda SIAGIE es
     * abreviada (Inglés). Misma forma que competenciasDelNivel.
     */
    public function competenciasDeArea(int $areaId): array
    {
        return $this->query("
            SELECT
                c.id   AS competencia_id,
                c.nombre_completo,
                c.codigo_minedu,
                a.id   AS area_id,
                a.nombre AS area_nombre,
                a.tipo AS area_tipo
            FROM competencias c
            LEFT JOIN subareas s ON s.id = c.subarea_id
            INNER JOIN areas a   ON a.id = COALESCE(c.area_id, s.area_id)
            WHERE a.id = ?
            ORDER BY c.orden
        ", [$areaId]);
    }

    /**
     * Persiste el código SIAGIE (14 dígitos, col. B del Excel) tras un match
     * exacto por nombre. SOLO escribe si el campo está vacío: un valor
     * distinto ya almacenado es un conflicto que se reporta, nunca se pisa.
     */
    public function guardarCodigoSiagie(int $estudianteId, string $codigo): bool
    {
        return $this->execute("
            UPDATE estudiantes
            SET codigo_estudiante = ?
            WHERE id = ?
              AND (codigo_estudiante IS NULL OR codigo_estudiante = '')
        ", [$codigo, $estudianteId]);
    }
}
