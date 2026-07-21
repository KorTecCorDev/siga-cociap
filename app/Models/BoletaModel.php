<?php

namespace App\Models;

/**
 * BoletaModel
 *
 * Ensamblador UNICO de los datos de una boleta (digital e imprimible).
 * Fusiona las tres copias que vivian dispersas en los controladores
 * (Boleta\BoletaController, BoletaPublicaController publico y
 * Admin\BoletaPublicaController). Un solo punto de verdad para el
 * documento: las reglas de agregacion, transversales, exoneraciones,
 * conducta, asistencia y firma del director se calculan aqui.
 *
 * Es PURO: data in -> array out. La autorizacion (rol, alcance, padre)
 * vive en los entry points (controladores), no aqui.
 */
class BoletaModel extends BaseModel
{
    private CalificacionModel    $calModel;
    private ConductaModel        $conductaModel;
    private AsistenciaModel      $asistenciaModel;
    private OmisionCriterioModel $omisionModel;
    private ExoneracionModel     $exoModel;
    private DirectorEbrModel     $dirModel;
    private PublicacionBoletaModel $publicacionModel;

    public function __construct()
    {
        parent::__construct();
        $this->calModel         = new CalificacionModel();
        $this->conductaModel    = new ConductaModel();
        $this->asistenciaModel  = new AsistenciaModel();
        $this->omisionModel     = new OmisionCriterioModel();
        $this->exoModel         = new ExoneracionModel();
        $this->dirModel         = new DirectorEbrModel();
        $this->publicacionModel = new PublicacionBoletaModel();
    }

    /**
     * Arma la boleta anual completa de un alumno.
     *
     * COMPUERTA DEL HITO A (09/07/2026): un bimestre aporta NOTAS segun su estado
     * de boleta (boleta_estado_bimestre). $datos define el umbral:
     *   - 'oficial'  : solo bimestres CERRADOS **y PUBLICADOS al nivel del alumno**
     *                  (acceso EN LINEA de familias: token, digital, /padre/notas).
     *                  El BORRADOR de Hito A NUNCA se expone al publico.
     *   - 'archivo'  : solo bimestres CERRADOS, IGNORANDO la compuerta de
     *                  publicacion (documento generado por STAFF: salida masiva
     *                  impresa y boleta del trasladado). Ver abajo.
     *   - 'borrador' : cerrado O activo con Hito A aprobado (interno: docente y
     *                  gestion). Un bimestre en 'registro' NO aporta notas aunque
     *                  el docente ya haya bloqueado su competencia.
     *   - 'todos'    : incluye 'registro' (UNICA excepcion: la vista previa de RA,
     *                  su herramienta para decidir el Hito A).
     *
     * COMPUERTA DE PUBLICACION (21/07/2026, migracion 044): cerrar un bimestre ya
     * NO publica sus boletas; publicar es un acto separado, por NIVEL y con
     * fecha/hora. El corte 'oficial' / 'archivo' existe porque RA IMPRIME las
     * boletas ANTES de la reunion de entrega: la compuerta protege el acceso EN
     * LINEA de las familias, no la impresion del colegio. Mismo umbral de datos
     * (solo cerrados) en ambos; solo cambia si se respeta la publicacion.
     *
     * @param int    $matriculaId  matricula consultada (puede ser operativa en retorno).
     * @param int    $periodoId    periodo a resaltar como activo.
     * @param string $datos        'oficial' | 'archivo' | 'borrador' | 'todos'.
     * @param bool   $estructuraCompleta REGLA DE FORMATO (09/07/2026): mantiene la
     *                             estructura anual completa (todas las columnas de
     *                             bimestres) aunque $datos filtre las notas
     *                             insertadas (trasladados via gestion). Sin ella y
     *                             con $datos='oficial', las columnas colapsan a
     *                             cerrados (comportamiento historico del token).
     * @return array|null          null si la matricula o el periodo no existen.
     */
    public function armar(int $matriculaId, int $periodoId, string $datos = 'oficial', bool $estructuraCompleta = false): ?array
    {
        // Retorno de grado: la boleta SIEMPRE se rotula con la matricula oficial
        // (grado/seccion SIAGIE) y sus notas se leen por union de las matriculas
        // involucradas (operativa + oficial). En el caso normal, identidad y
        // unica fuente son la propia matricula.
        $ctx       = $this->calModel->boletaContexto($matriculaId);
        $identidad = (int) $ctx['identidad'];
        $fuentes   = $ctx['fuentes'];

        $alumno  = $this->getAlumno($identidad);
        $periodo = $this->getPeriodo($periodoId);

        if (!$alumno || !$periodo) {
            return null;
        }

        $anioId = (int) $periodo['anio_id'];

        // Compuerta de PUBLICACION: solo la respeta el umbral 'oficial' (acceso en
        // linea de familias). 'archivo' comparte el corte de datos pero la ignora,
        // porque RA imprime las boletas antes de la reunion de entrega. null = la
        // compuerta no aplica a este umbral.
        $publicados = ($datos === 'oficial')
            ? $this->publicacionModel->periodosPublicados($anioId, (int) $alumno['nivel_id'])
            : null;

        // Estructura (columnas) vs datos: las columnas son todos los periodos del
        // anio, salvo el modo historico del token ('oficial'/'archivo' sin
        // estructura completa), que colapsa a cerrados. El umbral de NOTAS lo
        // aplica el guard del loop de abajo segun $datos.
        $colapsarColumnas = in_array($datos, ['oficial', 'archivo'], true) && !$estructuraCompleta;
        $periodos = $this->getPeriodosDelAnio($anioId, $colapsarColumnas);

        // Con las columnas colapsadas, un bimestre cerrado pero AUN NO PUBLICADO
        // tampoco arma columna: la familia no debe ver una columna vacia que
        // delate que el bimestre ya cerro. Sin colapsar (estructura anual
        // completa) la columna se mantiene y es el guard de datos el que la
        // deja vacia.
        if ($publicados !== null && $colapsarColumnas) {
            $periodos = array_values(array_filter(
                $periodos,
                fn(array $p): bool => isset($publicados[(int) $p['id']])
            ));
        }

        // Logro anual = nota del ULTIMO bimestre del anio (mayor numero), visible
        // SOLO cuando ese bimestre esta cerrado. NO es el ultimo bimestre CERRADO
        // ni un promedio: es el nivel alcanzado al final del anio (competencias).
        $ultimoBim        = $this->getUltimoBimestreDelAnio($anioId);
        $ultimoBimestreId = $ultimoBim ? (int) $ultimoBim['id'] : 0;
        $ultimoCerrado    = $ultimoBim !== null && $ultimoBim['estado'] === 'cerrado';

        // Compuerta del Hito A: qué periodos APORTAN notas segun $datos. Un
        // periodo que no aporta queda como columna vacia (la estructura no cambia).
        $periodosConDatos = [];
        $datosPorPeriodo  = [];
        foreach ($periodos as $p) {
            if (!$this->periodoAportaNotas($p, $datos, $publicados)) {
                $datosPorPeriodo[$p['id']] = [];
                continue;
            }
            $periodosConDatos[$p['id']] = true;
            $rows = [];
            foreach ($fuentes as $mid) {
                $rows = array_merge($rows, $this->calModel->getBoletaAlumno((int) $mid, $p['id']));
            }
            $datosPorPeriodo[$p['id']] = $rows;
        }

        $areas   = $this->buildAreasConBimestres($datosPorPeriodo, $periodos, $ultimoBimestreId, $ultimoCerrado);
        $exoData = $this->exoModel->getConCompetenciasParaBoletaUnion($fuentes, $anioId);
        $areas   = ExoneracionModel::inyectarEnAreas($areas, $exoData, $periodos);

        // Asistencia: una columna por bimestre CERRADO (todos los registrados) +
        // total. Solo cerrados (misma regla en la boleta de familias y la interna,
        // independiente del modo $datos). El total SUMA los bimestres mostrados
        // (no un acumulado por numero<=, que podria incluir uno no mostrado).
        // COMPUERTA DE PUBLICACION: cuando aplica ('oficial'), un bimestre cerrado
        // pero no publicado tampoco aporta ASISTENCIA. Si no, la familia veria la
        // columna de asistencia de un bimestre cuyas notas siguen ocultas, lo que
        // delataria que ya cerro y expondria medio bimestre. Hasta la migracion 044
        // ambos conjuntos coincidian (cerrado == visible) y el filtro no hacia falta.
        $periodosCerrados = $this->getPeriodosDelAnio($anioId, true);
        $asisBimestres = [];
        $asisTotal = ['faltas' => 0, 'faltas_justificadas' => 0, 'tardanzas' => 0, 'tardanzas_justificadas' => 0];
        foreach ($periodosCerrados as $pc) {
            if ($publicados !== null && !isset($publicados[(int) $pc['id']])) {
                continue;
            }
            // OJO: variable propia, NO reusar el nombre $datos (parametro del metodo,
            // leido mas abajo por el filtro de conducta).
            $asisDatos = $this->asistenciaModel->getDelBimestreUnion($fuentes, (int) $pc['id']);
            $asisBimestres[] = ['id' => (int) $pc['id'], 'numero' => (int) $pc['numero'], 'datos' => $asisDatos];
            foreach ($asisTotal as $k => $_) { $asisTotal[$k] += (int) $asisDatos[$k]; }
        }

        // Conducta [periodo_id => literal]: mismo umbral del Hito A que las notas.
        // Solo los periodos que APORTAN (segun $datos) muestran conducta; en 'todos'
        // no se filtra (vista previa de RA).
        $conducta = $this->conductaModel->getParaBoletaUnion($fuentes, $anioId);
        if ($datos !== 'todos') {
            $conducta = array_intersect_key($conducta, $periodosConDatos);
        }

        return [
            'alumno'            => $alumno,
            'periodos'          => $periodos,
            'periodo_activo_id' => $periodoId,
            'areas'             => $areas,
            'conducta'          => $conducta,
            'asistencia'        => [
                'bimestres' => $asisBimestres,
                'total'     => $asisTotal,
            ],
            'omisiones'   => $this->omisionModel->getPorMatriculaAnioUnion($fuentes, $anioId),
            'institucion' => config('institucion'),
            'tutor'       => $this->getTutorSeccion($identidad),
            'directorEbr' => $this->dirModel->getVigenteEnFecha($anioId),
        ];
    }

    // ── Queries privadas ────────────────────────────────────────

    private function getAlumno(int $matriculaId): ?array
    {
        return $this->queryOne("
            SELECT
                m.id                AS matricula_id,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno,
                p.dni,
                CONCAT(
                    p.apellido_paterno, ' ',
                    p.apellido_materno, ', ',
                    p.nombres
                )                   AS nombre_completo,
                g.nombre_display    AS grado_nombre,
                s.nombre            AS seccion_nombre,
                n.id                AS nivel_id,
                n.nombre            AS nivel_nombre,
                n.codigo            AS nivel_codigo,
                n.escala_boleta,
                a.anio              AS anio_academico
            FROM matriculas m
            INNER JOIN estudiantes e        ON e.id = m.estudiante_id
            INNER JOIN personas p           ON p.id = e.persona_id
            INNER JOIN secciones s          ON s.id = m.seccion_id
            INNER JOIN grados g             ON g.id = s.grado_id
            INNER JOIN niveles n            ON n.id = g.nivel_id
            INNER JOIN anios_academicos a   ON a.id = m.anio_id
            WHERE m.id = ?
            LIMIT 1
        ", [$matriculaId]);
    }

    private function getPeriodo(int $periodoId): ?array
    {
        return $this->queryOne("
            SELECT
                p.id,
                p.anio_id,
                a.anio,
                CONCAT(p.nombre_display, ' — ', a.anio) AS nombre_display
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
            LIMIT 1
        ", [$periodoId]);
    }

    /**
     * ¿El periodo aporta NOTAS a la boleta segun el umbral $datos? Compuerta del
     * Hito A: usa boleta_estado_bimestre (punto unico de verdad) por periodo.
     *   - 'oficial'  -> 'oficial' (cerrado) Y publicado al nivel del alumno.
     *   - 'archivo'  -> 'oficial' (cerrado), sin mirar la publicacion.
     *   - 'borrador' -> 'oficial' o 'borrador' (cerrado o activo con Hito A).
     *   - 'todos'    -> siempre (incluye 'registro'; solo vista previa de RA).
     *
     * @param array|null $publicados set [periodo_id => true] de la compuerta de
     *                   publicacion, o null si el umbral no la respeta.
     */
    private function periodoAportaNotas(array $periodo, string $datos, ?array $publicados = null): bool
    {
        if ($datos === 'todos') {
            return true;
        }
        $estado = boleta_estado_bimestre(
            $periodo['estado'] ?? null,
            $periodo['boletas_aprobadas_en'] ?? null
        );

        if ($datos === 'oficial' || $datos === 'archivo') {
            if ($estado !== 'oficial') {
                return false;
            }
            // 'archivo' ($publicados === null) ignora la compuerta a proposito.
            return $publicados === null || isset($publicados[(int) $periodo['id']]);
        }

        return $estado !== 'registro';   // 'borrador': oficial o borrador
    }

    /**
     * Periodos del anio. Con $soloCerrados filtra a bimestres CERRADOS
     * (estructura de columnas del token: el BORRADOR de Hito A no arma columna).
     * Incluye `boletas_aprobadas_en` para que el guard de datos de armar() pueda
     * derivar el estado de boleta (Hito A) por periodo.
     */
    private function getPeriodosDelAnio(int $anioId, bool $soloCerrados = false): array
    {
        $filtro = $soloCerrados ? "AND estado = 'cerrado'" : '';
        return $this->query("
            SELECT id, numero, nombre_display, estado, boletas_aprobadas_en
            FROM periodos
            WHERE anio_id = ? {$filtro}
            ORDER BY numero
        ", [$anioId]);
    }

    /**
     * Ultimo bimestre del anio: el periodo de mayor `numero` (dinamico, no
     * hardcodea la cantidad). Su cierre habilita el logro anual. Retorna
     * id/numero/estado o null si el anio no tiene periodos.
     */
    private function getUltimoBimestreDelAnio(int $anioId): ?array
    {
        return $this->queryOne("
            SELECT id, numero, estado
            FROM periodos
            WHERE anio_id = ?
            ORDER BY numero DESC
            LIMIT 1
        ", [$anioId]);
    }

    /**
     * Reorganiza los datos planos por periodo en una estructura
     * areas[nombre_area][comp_id] = { nombre, bimestres[periodo_id], literal_final }.
     * `literal_final` (logro anual) sale de la nota del ULTIMO bimestre del anio,
     * solo si ese bimestre esta cerrado (ver getUltimoBimestreDelAnio).
     */
    private function buildAreasConBimestres(
        array $datosPorPeriodo,
        array $periodos,
        int $ultimoBimestreId,
        bool $ultimoCerrado
    ): array {
        $areas = [];

        foreach ($datosPorPeriodo as $periodoId => $notas) {
            foreach ($notas as $nota) {
                $nombreArea = $nota['nombre_boleta'] ?? $nota['area_nombre'];
                if (!empty($nota['alias_boleta'])) {
                    $nombreArea .= ' ' . $nota['alias_boleta'];
                }
                $compId = $nota['competencia_id'];

                if (!isset($areas[$nombreArea][$compId])) {
                    // En secciones unidocentes (1°-3° primaria) el área se muestra
                    // como bloque área-curso: cada competencia por su código MINEDU
                    // + nombre, SIN prefijo ni etiqueta de subárea (afecta boleta
                    // imprimible y digital). Para especialistas (4°-6° y secundaria)
                    // se conserva el prefijo/etiqueta "Aritmética — …".
                    $muestraSubarea = empty($nota['es_unidocente'])
                        && ($nota['area_tipo'] ?? '') === 'con_subareas'
                        && !empty($nota['subarea_nombre']);
                    $prefijoSubarea = $muestraSubarea ? $nota['subarea_nombre'] . ' — ' : '';
                    $areas[$nombreArea][$compId] = [
                        'nombre'            => trim(
                            $prefijoSubarea .
                            ($nota['codigo_minedu'] ? $nota['codigo_minedu'] . '. ' : '') .
                            ($nota['nombre_corto'] ?? $nota['competencia_nombre'] ?? '')
                        ),
                        'nombre_largo'      => trim($prefijoSubarea . ($nota['competencia_nombre'] ?? '')),
                        'subarea_nombre'    => $muestraSubarea ? ($nota['subarea_nombre'] ?? '') : '',
                        'competencia_texto' => $nota['competencia_nombre'] ?? '',
                        'bimestres'         => [],
                    ];
                }

                $notaNum = isset($nota['nota_numerica']) ? (int) $nota['nota_numerica'] : null;
                $areas[$nombreArea][$compId]['bimestres'][$periodoId] = [
                    'nota'       => $notaNum,
                    'literal'    => $notaNum !== null ? CalificacionModel::toLiteral($notaNum) : null,
                    'conclusion' => $nota['conclusion_descriptiva'] ?? null,
                ];
            }
        }

        // Logro anual = literal de la nota del ULTIMO bimestre del anio, y SOLO si
        // ese bimestre esta cerrado. No se promedian los bimestres ni se usa el
        // ultimo CERRADO: es el nivel final del anio (modelo por competencias).
        // Sin cierre del ultimo bimestre => null (el chip "Anual" muestra —).
        foreach ($areas as &$comps) {
            foreach ($comps as &$comp) {
                $b = $ultimoCerrado ? ($comp['bimestres'][$ultimoBimestreId] ?? null) : null;
                $comp['literal_final'] = ($b !== null && $b['nota'] !== null)
                    ? $b['literal']
                    : null;
            }
        }
        unset($comps, $comp);

        return $areas;
    }

    private function getTutorSeccion(int $matriculaId): ?array
    {
        $seccion = $this->queryOne("
            SELECT s.tutor_id
            FROM matriculas m
            INNER JOIN secciones s ON s.id = m.seccion_id
            WHERE m.id = ?
            LIMIT 1
        ", [$matriculaId]);

        $tutorId = (int) ($seccion['tutor_id'] ?? 0);
        if (!$tutorId) {
            return null;
        }

        $persona = $this->queryOne("
            SELECT p.apellido_paterno, p.apellido_materno, p.nombres, p.sexo
            FROM usuarios u
            INNER JOIN personas p ON p.id = u.persona_id
            WHERE u.id = ?
            LIMIT 1
        ", [$tutorId]);

        if (!$persona || empty($persona['apellido_paterno'])) {
            return null;
        }

        return [
            'nombre' => $persona['apellido_paterno'] . ' '
                      . $persona['apellido_materno'] . ', '
                      . $persona['nombres'],
            'sexo'   => $persona['sexo'],
        ];
    }
}
