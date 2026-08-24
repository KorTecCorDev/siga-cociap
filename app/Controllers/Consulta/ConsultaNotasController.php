<?php

namespace App\Controllers\Consulta;

use App\Controllers\BaseController;
use App\Models\AsistenciaModel;
use App\Models\CalificacionModel;
use App\Models\OmisionCriterioModel;
use App\Models\ExoneracionModel;
use App\Models\TransversalModel;
use App\Models\ConductaModel;

/**
 * ConsultaNotasController
 * Consulta de calificaciones en SOLO LECTURA (capa de supervision).
 *
 * Eje de navegacion: PERIODO -> SECCION -> AREA/CARGA -> grilla criterio-a-
 * criterio. Muestra UNICAMENTE lo OFICIAL (competencias con bloqueo), igual
 * criterio que la boleta. NO edita: para corregir se usa /rectificaciones
 * (que ya audita). Reutiliza la capa de datos de CalificacionModel
 * (getCompetenciasPorPeriodo para navegar, getResumenCompetencia para el
 * detalle) y no introduce metodos de modelo nuevos.
 *
 * Roles: admin, registro_academico, director_general, director_ebr. El filtro
 * por nivel del director_ebr NO se aplica aqui (mismo comportamiento que
 * /director/bloqueos, que estos roles ya usan sin restriccion de nivel).
 */
class ConsultaNotasController extends BaseController
{
    private CalificacionModel    $calModel;
    private OmisionCriterioModel $omisionModel;
    private ExoneracionModel     $exoModel;
    private TransversalModel     $transModel;
    private ConductaModel        $conductaModel;
    private AsistenciaModel      $asistenciaModel;

    public function __construct()
    {
        $this->requireRole(['admin', 'registro_academico', ...ROLES_DIRECCION]);
        $this->calModel      = new CalificacionModel();
        $this->omisionModel  = new OmisionCriterioModel();
        $this->exoModel      = new ExoneracionModel();
        $this->transModel    = new TransversalModel();
        $this->conductaModel = new ConductaModel();
        $this->asistenciaModel = new AsistenciaModel();
    }

    /**
     * Roster canonico de una seccion (matricula_id + nombre), con las exclusiones
     * de retorno de grado ya aplicadas. Se reusa `ConductaModel::getEstudiantesParaTutor`
     * A PROPOSITO en vez de escribir otro SELECT: es el mismo roster que ven el
     * tutor y la grilla del docente, y duplicar ese filtro a mano es exactamente
     * como nacieron los bugs de asistencia del 04/08/2026. Los campos de conducta
     * que trae de propina los usa F3; F2 solo necesita id y nombre.
     */
    private function rosterSeccion(int $seccionId, int $periodoId, int $nivelId): array
    {
        return $this->conductaModel->getEstudiantesParaTutor(
            $seccionId,
            $periodoId,
            $this->conductaModel->totalCriterios($nivelId)
        );
    }

    /** Periodos disponibles para el selector (activos + cerrados). */
    private function listarPeriodos(): array
    {
        return $this->calModel->query("
            SELECT p.id, p.numero, p.nombre_display, p.estado, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.estado IN ('activo', 'cerrado')
            ORDER BY a.anio DESC, p.numero ASC
        ");
    }

    /** Nombre completo del docente desde una fila de getCompetenciasPorPeriodo. */
    private function nombreDocente(array $c): string
    {
        $apellidos = trim(($c['docente_apellido'] ?? '') . ' ' . ($c['docente_materno'] ?? ''));
        $nombres   = trim($c['docente_nombres'] ?? '');

        if ($apellidos === '' && $nombres === '') {
            return '';
        }
        return $nombres !== '' ? trim($apellidos . ', ' . $nombres) : $apellidos;
    }

    private function getPeriodo(int $periodoId): ?array
    {
        return $this->calModel->queryOne("
            SELECT p.*, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
        ", [$periodoId]);
    }

    /**
     * Competencias OFICIALES (con bloqueo) del periodo. Filtra en PHP sobre
     * getCompetenciasPorPeriodo para no duplicar la query de navegacion.
     */
    private function competenciasOficiales(int $periodoId): array
    {
        return array_values(array_filter(
            $this->calModel->getCompetenciasPorPeriodo($periodoId),
            fn($c) => $c['bloqueo_id'] !== null
        ));
    }

    /**
     * Competencias TRANSVERSALES (TIC/GAMA) con CONTENIDO REAL de las cargas
     * indicadas, indexadas por carga: [carga_id => [ {competencia_id, ...}, ... ]].
     *
     * ⚠️ POR QUE NO SALEN POR LA VIA NORMAL: `getCompetenciasPorPeriodo` une
     * competencia<->carga por el AREA DE LA CARGA, y las transversales cuelgan de
     * un area propia (`tipo='transversal'`), asi que ese JOIN no puede alcanzarlas
     * — el vinculo transversal<->carga no existe en el esquema, se resuelve por
     * NIVEL. Es el mismo limite que las deja fuera del panel de bloqueos.
     *
     * 🔴 EL BLOQUEO NO ES SENAL DE CONTENIDO, y por eso hace falta el EXISTS:
     * el cierre forzado propaga bloqueos en cascada, asi que hay 820 bloqueos
     * transversales sobre 410 cargas en CADA bimestre, pero en B1 solo 23 cargas
     * tienen notas. Copiar el criterio del resto de la pantalla ("mostrar lo que
     * tenga bloqueo") pintaria 387 bloques VACIOS en B1. La condicion de
     * contenido no es opcional.
     */
    private function transversalesConContenido(array $cargaIds, int $periodoId): array
    {
        $ids = array_values(array_unique(array_map('intval', $cargaIds)));
        if (empty($ids)) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));

        $filas = $this->calModel->query("
            SELECT bc.carga_id,
                   bc.bloqueado_en,
                   comp.id              AS competencia_id,
                   comp.nombre_completo,
                   comp.nombre_corto,
                   comp.codigo_minedu,
                   comp.orden
            FROM bloqueos_competencia bc
            INNER JOIN competencias comp ON comp.id = bc.competencia_id
            INNER JOIN areas a           ON a.id    = comp.area_id AND a.tipo = 'transversal'
            WHERE bc.periodo_id = ?
              AND bc.carga_id IN ({$ph})
              AND EXISTS (
                  SELECT 1 FROM calificaciones cal
                  WHERE cal.carga_id       = bc.carga_id
                    AND cal.competencia_id = comp.id
                    AND cal.periodo_id     = bc.periodo_id
              )
            ORDER BY bc.carga_id, comp.orden
        ", array_merge([$periodoId], $ids));

        $out = [];
        foreach ($filas as $f) {
            $out[(int) $f['carga_id']][] = $f;
        }
        return $out;
    }

    /** GET /consulta-notas — selector de periodo + secciones con notas oficiales. */
    public function index(): void
    {
        $periodos  = $this->listarPeriodos();
        $periodoId = (int) ($this->query('periodo_id') ?? 0);
        $periodo   = null;
        $secciones = [];

        if ($periodoId) {
            $periodo = $this->getPeriodo($periodoId);
            if ($periodo) {
                $bySec = [];
                foreach ($this->competenciasOficiales($periodoId) as $c) {
                    $sid = (int) $c['seccion_id'];
                    if (!isset($bySec[$sid])) {
                        $bySec[$sid] = [
                            'seccion_id'     => $sid,
                            'seccion_nombre' => $c['seccion_nombre'],
                            'grado_nombre'   => $c['grado_nombre'],
                            'grado_numero'   => (int) $c['grado_numero'],
                            'nivel_nombre'   => $c['nivel_nombre'],
                            'nivel_id'       => (int) $c['nivel_id'],
                            'cargas'         => [],
                            'competencias'   => 0,
                        ];
                    }
                    $bySec[$sid]['cargas'][(int) $c['carga_id']] = true;
                    $bySec[$sid]['competencias']++;
                }
                foreach ($bySec as &$s) {
                    $s['areas'] = count($s['cargas']);
                    unset($s['cargas']);
                }
                unset($s);

                $secciones = array_values($bySec);
                usort($secciones, fn($a, $b) =>
                    [$a['nivel_id'], $a['grado_numero'], $a['seccion_nombre']]
                    <=> [$b['nivel_id'], $b['grado_numero'], $b['seccion_nombre']]
                );
            }
        }

        $this->view('consulta-notas/index', [
            'titulo'    => 'Consulta de calificaciones',
            'periodos'  => $periodos,
            'periodoId' => $periodoId,
            'periodo'   => $periodo,
            'secciones' => $secciones,
        ]);
    }

    /** GET /consulta-notas/{periodo_id}/seccion/{seccion_id} — areas/cargas de la seccion. */
    public function seccion(string $periodoId, string $seccionId): void
    {
        $periodoId = (int) $periodoId;
        $seccionId = (int) $seccionId;

        $periodo = $this->getPeriodo($periodoId);
        if (!$periodo) {
            $this->notFound();
        }

        $filas = array_values(array_filter(
            $this->competenciasOficiales($periodoId),
            fn($c) => (int) $c['seccion_id'] === $seccionId
        ));

        $seccion = null;
        $cargas  = [];
        if (!empty($filas)) {
            $primera = $filas[0];
            $seccion = [
                'seccion_id'     => $seccionId,
                'seccion_nombre' => $primera['seccion_nombre'],
                'grado_nombre'   => $primera['grado_nombre'],
                'nivel_nombre'   => $primera['nivel_nombre'],
                'nivel_id'       => (int) $primera['nivel_id'],
            ];

            $byCarga = [];
            foreach ($filas as $c) {
                $cid = (int) $c['carga_id'];
                if (!isset($byCarga[$cid])) {
                    $byCarga[$cid] = [
                        'carga_id'       => $cid,
                        'area_nombre'    => $c['area_nombre'],
                        'subarea_nombre' => $c['subarea_nombre'],
                        'docente'        => $this->nombreDocente($c),
                        'competencias'   => 0,
                        'transversales'  => 0,
                    ];
                }
                $byCarga[$cid]['competencias']++;
            }

            // El contador de la tarjeta debe incluir las transversales, o mentiria
            // respecto a los bloques que la vista de carga va a pintar.
            $transv = $this->transversalesConContenido(array_keys($byCarga), $periodoId);
            foreach ($transv as $cid => $lista) {
                if (isset($byCarga[$cid])) {
                    $byCarga[$cid]['transversales'] = count($lista);
                    $byCarga[$cid]['competencias'] += count($lista);
                }
            }

            $cargas = array_values($byCarga);
        }

        // Las dos entradas de nivel SECCION (no de carga). Solo se ofrecen si el
        // registro esta OFICIALMENTE terminado (decision D3): cierre transversal
        // vigente / conducta con sus DOS etapas y sin anular. Lo que esta a medias
        // no aparece — es la misma promesa que ya hace la pantalla con las notas.
        $tieneTransversales = false;
        $tieneConducta      = false;
        if ($seccion) {
            $tieneTransversales = $this->transModel->getCierreVigente($seccionId, $periodoId) !== null;
            $cierreC            = $this->conductaModel->getCierreDetalle($seccionId, $periodoId);
            $tieneConducta      = $cierreC !== null
                && !empty($cierreC['ra_bloqueado_en'])
                && !empty($cierreC['tutor_cerrado_en']);
        }

        $this->view('consulta-notas/seccion', [
            'titulo'             => 'Consulta de calificaciones',
            'periodo'            => $periodo,
            'seccion'            => $seccion,
            'cargas'             => $cargas,
            'tieneTransversales' => $tieneTransversales,
            'tieneConducta'      => $tieneConducta,
        ]);
    }

    /**
     * GET /consulta-notas/{periodo_id}/seccion/{seccion_id}/transversales
     * Agregado transversal de la seccion: el promedio por competencia que
     * EFECTIVAMENTE llega a la boleta, con la conclusion del tutor.
     *
     * Es la otra cara de las transversales crudas que se ven dentro de cada carga:
     * aquellas son lo que registro cada docente; esto es lo que el tutor congelo
     * al cerrar. Sin cierre vigente no hay agregado que mostrar (`getTransversalesAgregadas`
     * aplica el mismo corte en la boleta), asi que la ruta responde 404 — no basta
     * con ocultar el enlace: la URL queda en marcadores.
     */
    public function transversales(string $periodoId, string $seccionId): void
    {
        $periodoId = (int) $periodoId;
        $seccionId = (int) $seccionId;

        $periodo = $this->getPeriodo($periodoId);
        if (!$periodo) {
            $this->notFound();
        }

        $cierre = $this->transModel->getCierreVigente($seccionId, $periodoId);
        if (!$cierre) {
            $this->notFound();
        }

        $filas = array_values(array_filter(
            $this->competenciasOficiales($periodoId),
            fn($c) => (int) $c['seccion_id'] === $seccionId
        ));
        if (empty($filas)) {
            $this->notFound();
        }
        $primera = $filas[0];
        $nivelId = (int) $primera['nivel_id'];

        $seccion = [
            'seccion_id'     => $seccionId,
            'seccion_nombre' => $primera['seccion_nombre'],
            'grado_nombre'   => $primera['grado_nombre'],
            'nivel_nombre'   => $primera['nivel_nombre'],
            'nivel_codigo'   => $primera['nivel_codigo'],
        ];

        // ⚠️ Los promedios vienen indexados POR MATRICULA y con competencia_id
        // como clave interna: hay que cruzarlos con el roster, nunca asumir orden.
        $this->view('consulta-notas/transversales', [
            'titulo'       => 'Transversales — ' . $seccion['grado_nombre'] . ' ' . $seccion['seccion_nombre'],
            'periodo'      => $periodo,
            'seccion'      => $seccion,
            'cierre'       => $cierre,
            'competencias' => $this->transModel->getCompetencias($nivelId),
            'alumnos'      => $this->rosterSeccion($seccionId, $periodoId, $nivelId),
            'promedios'    => $this->transModel->getPromediosSeccion($seccionId, $periodoId),
            'conclusiones' => $this->transModel->getConclusionesSeccion($seccionId, $periodoId),
        ]);
    }

    /**
     * GET /consulta-notas/{periodo_id}/seccion/{seccion_id}/conducta
     * Conducta de la seccion en SOLO LECTURA. Entra aqui —y no ampliando los
     * roles de /admin/conducta— porque aquella pantalla tiene botones de
     * escritura y no es de solo lectura por diseno (decision D2).
     *
     * Cierra un hueco real: director_general y director_ebr no tenian NINGUNA
     * forma de ver la conducta de una seccion.
     *
     * ⚠️ B1 y B2 NO comparten modelo: B1 es legado (literal directo, 0 respuestas)
     * y B2+ deriva la nota de las respuestas Si/No. `getEstudiantesParaTutor`
     * resuelve las dos y marca cada fila con `es_legado`; la vista ramifica.
     */
    public function conducta(string $periodoId, string $seccionId): void
    {
        $periodoId = (int) $periodoId;
        $seccionId = (int) $seccionId;

        $periodo = $this->getPeriodo($periodoId);
        if (!$periodo) {
            $this->notFound();
        }

        // Gate D3: las DOS etapas cumplidas y sin anular.
        $cierre = $this->conductaModel->getCierreDetalle($seccionId, $periodoId);
        if (!$cierre || empty($cierre['ra_bloqueado_en']) || empty($cierre['tutor_cerrado_en'])) {
            $this->notFound();
        }

        $filas = array_values(array_filter(
            $this->competenciasOficiales($periodoId),
            fn($c) => (int) $c['seccion_id'] === $seccionId
        ));
        if (empty($filas)) {
            $this->notFound();
        }
        $primera = $filas[0];
        $nivelId = (int) $primera['nivel_id'];

        $alumnos  = $this->rosterSeccion($seccionId, $periodoId, $nivelId);
        $esLegado = !empty($alumnos) && !empty($alumnos[0]['es_legado']);

        $this->view('consulta-notas/conducta', [
            'titulo'    => 'Conducta — ' . $primera['grado_nombre'] . ' ' . $primera['seccion_nombre'],
            'periodo'   => $periodo,
            'seccion'   => [
                'seccion_id'     => $seccionId,
                'seccion_nombre' => $primera['seccion_nombre'],
                'grado_nombre'   => $primera['grado_nombre'],
                'nivel_nombre'   => $primera['nivel_nombre'],
            ],
            'cierre'    => $cierre,
            'alumnos'   => $alumnos,
            'criterios' => $esLegado ? [] : $this->conductaModel->getCriterios($nivelId),
            'esLegado'  => $esLegado,
        ]);
    }

    /**
     * GET /consulta-notas/{periodo_id}/docentes
     *
     * EJE POR DOCENTE (24/08/2026). La pantalla solo navegaba
     * periodo -> seccion -> carga; para responder "que registro este docente"
     * habia que recorrer las 23 secciones a mano.
     *
     * Sin metodos de modelo nuevos: se agrupa la misma lista de competencias
     * oficiales que ya alimenta el resto del controlador.
     */
    public function docentes(string $periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);
        if (!$periodo) {
            $this->notFound();
        }

        $porDocente = [];
        foreach ($this->competenciasOficiales($periodoId) as $c) {
            $did = (int) $c['docente_id'];
            if (!isset($porDocente[$did])) {
                $porDocente[$did] = [
                    'docente_id'   => $did,
                    'nombre'       => $this->nombreDocente($c),
                    'cargas'       => [],
                    'competencias' => 0,
                    'secciones'    => [],
                ];
            }
            $porDocente[$did]['cargas'][(int) $c['carga_id']] = true;
            $porDocente[$did]['secciones'][(int) $c['seccion_id']] = true;
            $porDocente[$did]['competencias']++;
        }

        foreach ($porDocente as &$d) {
            $d['n_cargas']    = count($d['cargas']);
            $d['n_secciones'] = count($d['secciones']);
            unset($d['cargas'], $d['secciones']);
        }
        unset($d);

        // Alfabetico por el nombre ya compuesto (apellidos primero).
        usort($porDocente, fn($a, $b) => strcoll($a['nombre'], $b['nombre']));

        $this->view('consulta-notas/docentes', [
            'titulo'   => 'Docentes — ' . $periodo['nombre_display'],
            'periodo'  => $periodo,
            'docentes' => array_values($porDocente),
        ]);
    }

    /**
     * GET /consulta-notas/{periodo_id}/docente/{docente_id}
     * Cargas oficiales de un docente en el periodo. Enlaza a la MISMA vista de
     * carga del eje por seccion: es el mismo destino por otro camino.
     */
    public function docente(string $periodoId, string $docenteId): void
    {
        $periodoId = (int) $periodoId;
        $docenteId = (int) $docenteId;

        $periodo = $this->getPeriodo($periodoId);
        if (!$periodo) {
            $this->notFound();
        }

        $filas = array_values(array_filter(
            $this->competenciasOficiales($periodoId),
            fn($c) => (int) $c['docente_id'] === $docenteId
        ));
        if (empty($filas)) {
            $this->notFound();
        }

        $cargas = [];
        foreach ($filas as $c) {
            $cid = (int) $c['carga_id'];
            if (!isset($cargas[$cid])) {
                $cargas[$cid] = [
                    'carga_id'       => $cid,
                    'area_nombre'    => $c['area_nombre'],
                    'subarea_nombre' => $c['subarea_nombre'] ?? null,
                    'grado_nombre'   => $c['grado_nombre'],
                    'seccion_nombre' => $c['seccion_nombre'],
                    'nivel_nombre'   => $c['nivel_nombre'],
                    'grado_numero'   => (int) $c['grado_numero'],
                    'competencias'   => 0,
                ];
            }
            $cargas[$cid]['competencias']++;
        }

        $cargas = array_values($cargas);
        usort($cargas, fn($a, $b) => [$a['nivel_nombre'], $a['grado_numero'], $a['seccion_nombre'], $a['area_nombre']]
                                 <=> [$b['nivel_nombre'], $b['grado_numero'], $b['seccion_nombre'], $b['area_nombre']]);

        $this->view('consulta-notas/docente', [
            'titulo'  => 'Cargas de ' . $this->nombreDocente($filas[0]),
            'periodo' => $periodo,
            'docente' => ['id' => $docenteId, 'nombre' => $this->nombreDocente($filas[0])],
            'cargas'  => $cargas,
        ]);
    }

    /**
     * GET /consulta-notas/{periodo_id}/seccion/{seccion_id}/asistencia
     *
     * Asistencia de la sección en SOLO LECTURA (24/08/2026). Cierra el cuarto
     * registro del bimestre: hasta hoy la asistencia solo existía en
     * `/admin/asistencia`, que es la pantalla de ESCRITURA de Registro
     * Académico — dirección no tenía ninguna forma de consultarla.
     *
     * ⚠️ A DIFERENCIA de transversales y conducta, esta vista NO exige cierre:
     * se muestra EN VIVO (decisión del usuario, 24/08/2026). El criterio del
     * resto de la pantalla es "solo el dato aprobado y bloqueado", pero una
     * inasistencia no es una calificación sujeta a aprobación docente — ya
     * ocurrió. El estado del cierre se muestra como dato, no como candado.
     *
     * El roster sale de `AsistenciaModel::getEstudiantesConIncidencias`, que es
     * el MISMO de la grilla de notas (`getAlumnosSeccion`): sin filtrar por
     * `estado` y con las exclusiones de retorno de grado. No se reescribe ese
     * filtro a mano — así nacieron los bugs de asistencia del 04/08.
     */
    public function asistencia(string $periodoId, string $seccionId): void
    {
        $periodoId = (int) $periodoId;
        $seccionId = (int) $seccionId;

        $periodo = $this->getPeriodo($periodoId);
        if (!$periodo) {
            $this->notFound();
        }

        // La sección debe pertenecer al periodo (mismo anclaje que conducta).
        $filas = array_values(array_filter(
            $this->competenciasOficiales($periodoId),
            fn($c) => (int) $c['seccion_id'] === $seccionId
        ));
        if (empty($filas)) {
            $this->notFound();
        }
        $primera = $filas[0];

        $alumnos = $this->asistenciaModel->getEstudiantesConIncidencias($seccionId, $periodoId);

        // Totales de la sección, para la cabecera.
        $totales = ['faltas' => 0, 'faltas_justificadas' => 0, 'tardanzas' => 0, 'tardanzas_justificadas' => 0, 'registrados' => 0];
        foreach ($alumnos as $a) {
            foreach (['faltas', 'faltas_justificadas', 'tardanzas', 'tardanzas_justificadas'] as $k) {
                $totales[$k] += (int) $a['incidencias'][$k];
            }
            if (!empty($a['incidencias']['registrado'])) {
                $totales['registrados']++;
            }
        }

        $this->view('consulta-notas/asistencia', [
            'titulo'  => 'Asistencia — ' . $primera['grado_nombre'] . ' ' . $primera['seccion_nombre'],
            'periodo' => $periodo,
            'seccion' => [
                'seccion_id'     => $seccionId,
                'seccion_nombre' => $primera['seccion_nombre'],
                'grado_nombre'   => $primera['grado_nombre'],
                'nivel_nombre'   => $primera['nivel_nombre'],
            ],
            'alumnos' => $alumnos,
            'totales' => $totales,
            'cierre'  => $this->asistenciaModel->getCierreDetalle($seccionId, $periodoId),
        ]);
    }

    /** GET /consulta-notas/{periodo_id}/carga/{carga_id} — grillas read-only de la carga. */
    public function carga(string $periodoId, string $cargaId): void
    {
        $periodoId = (int) $periodoId;
        $cargaId   = (int) $cargaId;

        $periodo = $this->getPeriodo($periodoId);
        if (!$periodo) {
            $this->notFound();
        }

        $filas = array_values(array_filter(
            $this->competenciasOficiales($periodoId),
            fn($c) => (int) $c['carga_id'] === $cargaId
        ));
        if (empty($filas)) {
            $this->notFound();
        }

        $primera = $filas[0];
        $carga = [
            'id'             => $cargaId,
            'seccion_id'     => (int) $primera['seccion_id'],
            'seccion_nombre' => $primera['seccion_nombre'],
            'grado_nombre'   => $primera['grado_nombre'],
            'nivel_nombre'   => $primera['nivel_nombre'],
            'nivel_codigo'   => $primera['nivel_codigo'],
            'area_nombre'    => $primera['area_nombre'],
            'subarea_nombre' => $primera['subarea_nombre'],
            'docente'        => $this->nombreDocente($primera),
        ];

        $exonerados   = $this->exoModel->getActivasParaCarga($cargaId, (int) $periodo['anio_id']);
        $competencias = [];

        foreach ($filas as $c) {
            $competenciaId = (int) $c['competencia_id'];

            $info = $this->calModel->queryOne("
                SELECT c.*, (a.tipo = 'transversal') AS es_transversal
                FROM competencias c
                LEFT JOIN areas a ON a.id = c.area_id
                WHERE c.id = ?
            ", [$competenciaId]);

            $resumen = $this->calModel->getResumenCompetencia($cargaId, $competenciaId, $periodoId);

            // Enriquecer cada alumno con sus omisiones por criterio (igual que el
            // resumen del docente: el badge "—" del casillero omitido).
            $omisionesPorCriterio = [];
            foreach ($resumen['criterios'] as $cr) {
                $omisionesPorCriterio[(int) $cr['id']] =
                    $this->omisionModel->getPorCriterio((int) $cr['id']);
            }
            foreach ($resumen['alumnos'] as &$al) {
                $al['omisiones_criterios'] = [];
                $mid = (int) $al['matricula_id'];
                foreach ($omisionesPorCriterio as $critId => $porMat) {
                    if (isset($porMat[$mid])) {
                        $al['omisiones_criterios'][$critId] = $porMat[$mid];
                    }
                }
            }
            unset($al);

            // Calificaciones extraordinarias de RA (motivo + registrador)
            // para el bloque informativo del parcial.
            $extraordinarias = [];
            foreach ($resumen['criterios'] as $cr) {
                if (!empty($cr['extraordinario'])) {
                    $extraordinarias = (new \App\Models\RectificacionModel())
                        ->getExtraordinariasDeCompetencia($cargaId, $competenciaId, $periodoId);
                    break;
                }
            }

            $competencias[] = [
                'competencia'     => $info,
                'criterios'       => $resumen['criterios'],
                'alumnos'         => $resumen['alumnos'],
                'bloqueado_en'    => $c['bloqueado_en'],
                'extraordinarias' => $extraordinarias,
                'es_transversal'  => false,
            ];
        }

        // Transversales (TIC/GAMA) de esta carga, al final y solo si tienen
        // contenido. Se pintan con el MISMO parcial `_tabla.php`: getResumenCompetencia
        // funciona igual sobre una competencia transversal (verificado con sonda:
        // devuelve las mismas claves), asi que basta con anadirlas al array.
        foreach ($this->transversalesConContenido([$cargaId], $periodoId)[$cargaId] ?? [] as $t) {
            $competenciaId = (int) $t['competencia_id'];
            $resumen = $this->calModel->getResumenCompetencia($cargaId, $competenciaId, $periodoId);

            $competencias[] = [
                'competencia'     => [
                    'id'              => $competenciaId,
                    'nombre_completo' => $t['nombre_completo'],
                    'nombre_corto'    => $t['nombre_corto'],
                    'codigo_minedu'   => $t['codigo_minedu'],
                    'es_transversal'  => 1,
                ],
                'criterios'       => $resumen['criterios'],
                'alumnos'         => $resumen['alumnos'],
                'bloqueado_en'    => $t['bloqueado_en'],
                'extraordinarias' => [],
                'es_transversal'  => true,
            ];
        }

        $this->view('consulta-notas/carga', [
            'titulo'       => 'Consulta — ' . $carga['grado_nombre'] . ' ' . $carga['seccion_nombre'],
            'periodo'      => $periodo,
            'carga'        => $carga,
            'competencias' => $competencias,
            'exonerados'   => $exonerados,
        ]);
    }
}
