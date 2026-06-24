<?php

namespace App\Controllers\Docente;

use App\Controllers\BaseController;
use App\Models\CalificacionModel;
use App\Models\ConductaModel;
use App\Models\DirectorEbrModel;
use App\Models\EstudianteModel;
use App\Models\OrdenMeritoModel;
use App\Models\TransversalModel;
use Core\Session;
use Core\View;

/**
 * PanelController
 * Dashboard del docente y nómina de matriculados de su(s) nivel(es).
 */
class PanelController extends BaseController
{
    private CalificacionModel $calModel;
    private TransversalModel  $transModel;
    private ConductaModel     $conductaModel;

    public function __construct()
    {
        $this->requireRole(['docente', 'admin']);
        $this->calModel      = new CalificacionModel();
        $this->transModel    = new TransversalModel();
        $this->conductaModel = new ConductaModel();
    }

    /**
     * GET /docente/inicio — dashboard del docente.
     */
    public function index(): void
    {
        $user    = Session::user();
        $did     = (int) $user['id'];
        $periodo = $this->getPeriodoActivo();
        $pid     = $periodo ? (int) $periodo['id'] : 0;

        $cargas  = $pid ? $this->getCargasResumen($did, $pid) : [];

        // Docente de aula (unidocente): si alguna carga pertenece a una seccion
        // es_unidocente, dicta TODAS las areas de esa aula y es su tutor.
        //   - tieneAula: es tutor(a) de aula (para el badge de identidad).
        //   - soloAula : el aula es TODA su carga (habilita el rotulo "Mi aula").
        // Caso mixto (unidocente de una seccion + especialista en otra, p.ej. el
        // docente que ademas dicta C. y T. en otro grado): tieneAula=true pero
        // soloAula=false, asi el dashboard usa el rotulo generico y NO mezcla
        // secciones bajo "Mi aula"; la identidad queda en el badge de bienvenida.
        $tieneAula  = false;
        $hayOtras   = false;
        $aula       = null;
        $areasAula  = [];
        $seccionesAula = [];   // labels unicos de aulas unidocentes (para los chips)
        foreach ($cargas as $c) {
            if (!empty($c['es_unidocente'])) {
                $tieneAula = true;
                $label     = trim($c['grado_nombre'] . ' ' . $c['seccion_nombre']);
                $aula    ??= $label;
                $seccionesAula[$label] = true;
                $areasAula[(int) $c['area_id']] = true;
            } else {
                $hayOtras = true;
            }
        }
        $soloAula   = $tieneAula && !$hayOtras;
        $nAreasAula = count($areasAula);

        // KPIs / resumen de cargas
        $sumTotal = $sumBloq = $completas = $sinCriterios = 0;
        $pendientes = [];
        foreach ($cargas as $c) {
            $total = (int) $c['total_comp'];
            $bloq  = (int) $c['bloq'];
            $crit  = (int) $c['con_criterios'];
            $sumTotal += $total;
            $sumBloq  += $bloq;
            if ($total > 0 && $bloq >= $total) {
                $completas++;
            }
            if ($bloq === 0 && $crit === 0) {
                $sinCriterios++;
            }
            // Lista de pendientes: cargas que aún no están completas.
            if ($total === 0 || $bloq < $total) {
                $pendientes[] = [
                    'id'      => (int) $c['id'],
                    'nombre'  => $c['nombre_display'] ?? '—',
                    'seccion' => $c['grado_nombre'] . ' ' . $c['seccion_nombre'],
                    'motivo'  => ($bloq === 0 && $crit === 0)
                        ? 'Sin criterios'
                        : 'Faltan ' . max(0, $total - $bloq) . ' de ' . $total,
                    'critico' => ($bloq === 0 && $crit === 0),
                    'faltan'  => max(0, $total - $bloq),
                ];
            }
        }
        $avance = $sumTotal > 0 ? (int) round($sumBloq / $sumTotal * 100) : 0;

        // Prioridad: primero lo más crítico (cargas sin criterios, sin iniciar),
        // luego las que tienen más competencias por bloquear. usort es estable
        // en PHP 8.2, así que en empate se conserva el orden por nivel/grado.
        usort($pendientes, static function (array $a, array $b): int {
            return [(int) $b['critico'], $b['faltan']]
               <=> [(int) $a['critico'], $a['faltan']];
        });

        // Días para el cierre (limite_notas)
        $diasCierre = null;
        if ($periodo && !empty($periodo['limite_notas'])) {
            $diasCierre = (int) ceil(
                (strtotime($periodo['limite_notas']) - time()) / 86400
            );
        }

        // Card de Tutoría (solo tutores del año activo)
        $tutoria      = null;
        $seccionTutor = $this->transModel->getSeccionDelTutor($did);
        if ($seccionTutor && $periodo) {
            $sid    = (int) $seccionTutor['id'];
            $estado = $this->transModel->estadoCargasSeccion($sid, $pid);
            $cierre = $this->transModel->getCierreVigente($sid, $pid);
            $listo  = $estado['total'] > 0 && $estado['bloqueadas'] >= $estado['total'];
            $tutoria = [
                'seccion'    => $seccionTutor,
                'total'      => $estado['total'],
                'bloqueadas' => $estado['bloqueadas'],
                'cierre'     => $cierre,
                'listo'      => $listo,
                'pendientes' => ($listo && !$cierre)
                    ? $this->transModel->conclusionesObligatoriasPendientes(
                        $sid, $pid, $seccionTutor['nivel_codigo']
                      )
                    : 0,
            ];
        }

        // Card de Conducta (solo tutores del año activo): pendiente (RA no
        // bloqueó) / disponible / cerrado. Misma fuente que /docente/mis-cargas.
        $conducta = null;
        if ($seccionTutor && $periodo) {
            $cc = $this->conductaModel->getCierreVigente((int) $seccionTutor['id'], $pid);
            $conducta = [
                'seccion' => $seccionTutor,
                'cierre'  => $cc,
                'cerrado' => $cc && !empty($cc['tutor_cerrado_en']),
            ];
        }

        // Chips de identidad del encabezado: un chip por cada ROL que cumple el
        // docente, combinables. Unidocente (dicta todas las areas de un aula) y
        // tutor (responsable de una seccion) son atributos INDEPENDIENTES, con
        // fuentes distintas (es_unidocente vs secciones.tutor_id).
        //   - "Unidocente - X"  por cada aula unidocente.
        //   - "Tutor(a) - Y"    si es tutor de una seccion (cualquier nivel).
        //   - "Docente"         como complemento si ademas dicta fuera de su aula,
        //                       o como unica etiqueta del especialista sin rol.
        $chips = [];
        foreach (array_keys($seccionesAula) as $label) {
            $chips[] = ['tipo' => 'unidocente', 'texto' => 'Unidocente — ' . $label];
        }
        if ($seccionTutor) {
            $rotTutor = match ($user['sexo'] ?? null) {
                'M'     => 'Tutor',
                'F'     => 'Tutora',
                default => 'Tutor(a)',
            };
            $chips[] = [
                'tipo'  => 'tutor',
                'texto' => $rotTutor . ' — ' . trim($seccionTutor['grado_nombre'] . ' ' . $seccionTutor['nombre']),
            ];
        }
        if ((!$tieneAula && !$seccionTutor) || ($tieneAula && $hayOtras)) {
            $chips[] = ['tipo' => 'docente', 'texto' => 'Docente'];
        }

        // Avance TOTAL del bimestre: suma TODAS las responsabilidades del docente
        // como unidades — cada competencia académica + (solo si es tutor) la
        // tutoría y la conducta de su sección, una unidad cada una. Es el número
        // global del KPI "Avance del bimestre"; el avance de la card "Mis cargas"
        // sigue siendo solo académico ($avance).
        $respTotal = $sumTotal;
        $respHecho = $sumBloq;
        if ($tutoria !== null) {
            $respTotal++;
            if ($tutoria['cierre']) { $respHecho++; }
        }
        if ($conducta !== null) {
            $respTotal++;
            if ($conducta['cerrado']) { $respHecho++; }
        }
        $avanceTotal = $respTotal > 0 ? (int) round($respHecho / $respTotal * 100) : 0;

        // Niveles del docente + resumen de nómina
        $niveles      = $this->getNivelesDocente($did);
        $nominaResumen = $this->getNominaResumen($niveles);
        $totalNomina  = array_sum(array_column($nominaResumen, 'n'));

        // Horario de la semana
        $horario = $this->getHorario($did);

        $this->view('docente/inicio', [
            'titulo'        => 'Inicio',
            'periodo'       => $periodo,
            'cargas'        => $cargas,
            'tieneAula'     => $tieneAula,
            'soloAula'      => $soloAula,
            'aula'          => $aula,
            'chips'         => $chips,
            'nAreasAula'    => $nAreasAula,
            'nCargas'       => count($cargas),
            'avance'        => $avance,
            'avanceTotal'   => $avanceTotal,
            'sumTotal'      => $sumTotal,
            'sumBloq'       => $sumBloq,
            'completas'     => $completas,
            'sinCriterios'  => $sinCriterios,
            'diasCierre'    => $diasCierre,
            'pendientes'    => $pendientes,
            'tutoria'       => $tutoria,
            'conducta'      => $conducta,
            'niveles'       => $niveles,
            'nominaResumen' => $nominaResumen,
            'totalNomina'   => $totalNomina,
            'horario'       => $horario,
            'page_scripts'  => [],
        ]);
    }

    /**
     * GET /docente/nomina — buscador en vivo de matriculados (aprobados) de los
     * niveles del docente + selector para imprimir la nómina de una sección.
     * Nunca expone el DNI (dato sensible de consulta restringida).
     */
    public function nomina(): void
    {
        $user    = Session::user();
        $did     = (int) $user['id'];
        $niveles = $this->getNivelesDocente($did);

        $alumnos = $this->getMatriculados($niveles);

        // Orden de mérito: puesto del ULTIMO bimestre cerrado del año activo
        // (misma fuente que el ranking oficial). Si no hay bimestre cerrado aún
        // —p. ej. en el I Bimestre— no hay puesto vigente.
        $estModel  = new EstudianteModel();
        $anio      = $estModel->anioActivo();
        $bimestre  = $anio ? $estModel->ultimoBimestreCerrado((int) $anio['id']) : null;
        $puestos   = [];
        if ($bimestre && $alumnos) {
            $gradoIds = array_filter(array_map(
                static fn($a) => (int) ($a['grado_id'] ?? 0),
                $alumnos
            ));
            if ($gradoIds) {
                $puestos = (new OrdenMeritoModel())
                    ->puestosPorGrado($gradoIds, (int) $bimestre['id']);
            }
        }
        foreach ($alumnos as &$a) {
            $a['puesto'] = $puestos[(int) $a['matricula_id']]['puesto'] ?? null;
        }
        unset($a);

        // Lista de secciones (para el selector de impresión), única y ordenada.
        $secciones = [];
        foreach ($alumnos as $a) {
            $sid = (int) $a['seccion_id'];
            if (!isset($secciones[$sid])) {
                $secciones[$sid] = [
                    'seccion_id'     => $sid,
                    'nivel_nombre'   => $a['nivel_nombre'],
                    'grado_nombre'   => $a['grado_nombre'],
                    'seccion_nombre' => $a['seccion_nombre'],
                    'n'              => 0,
                ];
            }
            $secciones[$sid]['n']++;
        }

        // Estado de la boleta del bimestre ACTIVO: 'borrador' tras el Hito A (RA
        // aprobo), 'registro' antes. Mientras el activo no se aprueba, la boleta
        // visible es la OFICIAL del ultimo bimestre CERRADO ($bimestre).
        $periodoActivo = $this->getPeriodoActivo();
        $estadoBoleta  = boleta_estado_bimestre(
            $periodoActivo['estado'] ?? null,
            $periodoActivo['boletas_aprobadas_en'] ?? null
        );

        $this->view('docente/nomina', [
            'titulo'           => 'Nómina de matriculados',
            'alumnos'          => $alumnos,
            'secciones'        => array_values($secciones),
            'total'            => count($alumnos),
            'tieneOrdenMerito' => $bimestre !== null,
            'bimestre'         => $bimestre['nombre_display'] ?? null,
            'estadoBoleta'     => $estadoBoleta,
            'bimestreActivo'   => $periodoActivo['nombre_display'] ?? null,
            'bimestreCerrado'  => $bimestre['nombre_display'] ?? null,
            'page_scripts'     => ['nomina'],
        ]);
    }

    /**
     * GET /docente/nomina/{seccion_id}/imprimir — nómina A4 de una sección.
     * Nunca incluye DNI (dato sensible de consulta restringida).
     */
    public function nominaImprimir(string $seccionId): void
    {
        $user      = Session::user();
        $did       = (int) $user['id'];
        $seccionId = (int) $seccionId;
        $niveles   = $this->getNivelesDocente($did);
        $nivelIds  = array_map('intval', array_column($niveles, 'id'));

        $seccion = $this->calModel->queryOne("
            SELECT s.id, s.nombre AS seccion_nombre,
                   g.numero AS grado_numero, g.nombre_display AS grado_nombre,
                   n.id AS nivel_id, n.nombre AS nivel_nombre,
                   tp.sexo AS tutor_sexo,
                   CASE WHEN tp.id IS NULL THEN ''
                        ELSE CONCAT(tp.apellido_paterno, ' ', tp.apellido_materno, ', ', tp.nombres)
                   END AS tutor_nombre
            FROM secciones s
            INNER JOIN grados g  ON g.id = s.grado_id
            INNER JOIN niveles n ON n.id = g.nivel_id
            LEFT  JOIN usuarios tu ON tu.id = s.tutor_id
            LEFT  JOIN personas tp ON tp.id = tu.persona_id
            WHERE s.id = ?
        ", [$seccionId]);

        // Autorización: la sección debe pertenecer a un nivel del docente.
        if (!$seccion || !in_array((int) $seccion['nivel_id'], $nivelIds, true)) {
            $this->redirectWithError(url('docente/nomina'), 'Sección no disponible.');
        }

        $alumnos = $this->getMatriculados($niveles, $seccionId);

        // Sello del Director EBR vigente del año académico activo (solo el sello).
        $anio        = $this->getAnioActivo();
        $directorEbr = $anio
            ? (new DirectorEbrModel())->getVigenteEnFecha((int) $anio['id'])
            : null;

        View::setLayout('print');
        $this->view('docente/nomina-imprimir', [
            'titulo'      => 'Nómina ' . $seccion['grado_nombre'] . ' ' . $seccion['seccion_nombre'],
            'seccion'     => $seccion,
            'alumnos'     => $alumnos,
            'directorEbr' => $directorEbr,
            'anio'        => $anio,
        ]);
    }

    /**
     * GET /docente/horario/imprimir — horario semanal del docente en tabla de
     * doble entrada (días en columnas, franjas horarias en filas). Una hoja
     * A4 horizontal, con color por carga y leyenda al final. Layout: print.
     */
    public function horarioImprimir(): void
    {
        $user     = Session::user();
        $did      = (int) $user['id'];
        $sesiones = $this->getHorario($did);

        if (empty($sesiones)) {
            $this->redirectWithError(
                url('docente/inicio'),
                'No tienes horario registrado para imprimir.'
            );
        }

        // Días fijos lunes-viernes (la BD no maneja fin de semana).
        $dias = [
            'lunes'     => 'Lunes',
            'martes'    => 'Martes',
            'miercoles' => 'Miércoles',
            'jueves'    => 'Jueves',
            'viernes'   => 'Viernes',
        ];

        // Franjas horarias distintas (clave compartida entre días) ordenadas.
        $inicioPorFranja = [];
        foreach ($sesiones as $s) {
            $clave = $s['hora_inicio'] . '|' . $s['hora_fin'];
            $inicioPorFranja[$clave] = $s['hora_inicio'];
        }
        asort($inicioPorFranja);
        $franjas = array_keys($inicioPorFranja);

        // Color por SECCIÓN + MATERIA: la misma materia dictada en una misma
        // sección comparte color aunque esté repartida en más de una carga o en
        // varios bloques (p. ej. Trigonometría en dos cargas distintas de la
        // misma sección). Primero se agrupan y ORDENAN por grado (primaria
        // 1°-6°, luego secundaria 1°-5°, sección y materia); el color se asigna
        // en ese orden para que la leyenda quede correlativa.
        $grupos     = [];
        $totalHoras = 0;
        foreach ($sesiones as $s) {
            $key = $s['seccion_id'] . '|' . $s['area_nombre'];
            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'key'            => $key,
                    'nivel_codigo'   => $s['nivel_codigo'],
                    'grado_numero'   => (int) $s['grado_numero'],
                    'seccion_nombre' => $s['seccion_nombre'],
                    'seccion'        => $s['grado_nombre'] . ' ' . $s['seccion_nombre'],
                    'area'           => $s['area_nombre'],
                    'horas'          => 0,
                ];
            }
            // Cada bloque dictado = una hora pedagógica.
            $grupos[$key]['horas']++;
            $totalHoras++;
        }

        // Orden: primaria antes que secundaria, luego grado 1→N, sección y materia.
        $nivelOrden = ['prim' => 0, 'sec' => 1];
        usort($grupos, function ($a, $b) use ($nivelOrden) {
            return [$nivelOrden[$a['nivel_codigo']] ?? 9, $a['grado_numero'],
                    $a['seccion_nombre'], $a['area']]
               <=> [$nivelOrden[$b['nivel_codigo']] ?? 9, $b['grado_numero'],
                    $b['seccion_nombre'], $b['area']];
        });

        // Asignar color en el orden ya definido y armar la leyenda. El tono se
        // calcula con el angulo aureo (137.508 deg): cada grupo seccion+materia
        // recibe un color claramente distinto y SIN repetir, sea cual sea la
        // cantidad de grupos del docente. Saturacion/luminosidad fijas para
        // mantener fondos claros legibles con texto oscuro.
        $colorPorGrupo = [];
        $leyenda       = [];
        foreach ($grupos as $i => $g) {
            $hue   = (int) round(fmod($i * 137.508, 360));
            $color = "hsl({$hue}, 70%, 82%)";
            $colorPorGrupo[$g['key']] = $color;
            $leyenda[] = [
                'color'   => $color,
                'nivel'   => $g['nivel_codigo'],
                'seccion' => $g['seccion'],
                'areas'   => [$g['area']],
                'horas'   => $g['horas'],
            ];
        }

        // Matriz de la tabla: color por grupo (sección + materia).
        $matriz = [];
        foreach ($sesiones as $s) {
            $key   = $s['seccion_id'] . '|' . $s['area_nombre'];
            $clave = $s['hora_inicio'] . '|' . $s['hora_fin'];
            $matriz[$clave][$s['dia_semana']] = [
                'area'    => $s['area_nombre'],
                'seccion' => $s['grado_nombre'] . ' ' . $s['seccion_nombre'],
                'nivel'   => $s['nivel_codigo'],
                'color'   => $colorPorGrupo[$key],
            ];
        }

        // Descripción de cada franja: "Nª hora" → rango horario almacenado.
        $bloques = [];
        foreach ($franjas as $clave) {
            [$ini, $fin] = explode('|', $clave);
            $bloques[]   = ['inicio' => $ini, 'fin' => $fin];
        }

        // Documento → nombre legal completo del docente (no el nombre corto).
        $docente = trim(
            ($user['apellido_paterno'] ?? '') . ' ' .
            ($user['apellido_materno'] ?? '') . ', ' .
            ($user['nombres'] ?? '')
        );

        // Sello del Director EBR vigente del año académico activo.
        $anio        = $this->getAnioActivo();
        $directorEbr = $anio
            ? (new DirectorEbrModel())->getVigenteEnFecha((int) $anio['id'])
            : null;

        View::setLayout('print');
        $this->view('docente/horario-imprimir', [
            'titulo'      => 'Horario — ' . $docente,
            'docente'     => $docente,
            'anio'        => $anio,
            'dias'        => $dias,
            'franjas'     => $franjas,
            'matriz'      => $matriz,
            'bloques'     => $bloques,
            'leyenda'     => array_values($leyenda),
            'totalHoras'  => $totalHoras,
            'directorEbr' => $directorEbr,
        ]);
    }

    // ── Privados ─────────────────────────────────────────────────

    private function getAnioActivo(): ?array
    {
        return $this->calModel->queryOne("
            SELECT id, anio FROM anios_academicos WHERE estado = 'activo' LIMIT 1
        ");
    }

    private function getPeriodoActivo(): ?array
    {
        return $this->calModel->queryOne("
            SELECT p.*, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.estado = 'activo'
            LIMIT 1
        ");
    }

    private function getNivelesDocente(int $docenteId): array
    {
        return $this->calModel->query("
            SELECT DISTINCT n.id, n.nombre, n.codigo
            FROM cargas_academicas ca
            INNER JOIN secciones s ON s.id = ca.seccion_id
            INNER JOIN grados g    ON g.id = s.grado_id
            INNER JOIN niveles n   ON n.id = g.nivel_id
            WHERE ca.docente_id = ? AND ca.estado = 'activa'
            ORDER BY n.id
        ", [$docenteId]);
    }

    /**
     * Resumen de cada carga: total/bloqueadas/con-criterios. El total y las
     * bloqueadas incluyen las competencias transversales TIC/GAMA del nivel
     * (cada docente las registra en su carga): una carga queda pendiente hasta
     * bloquear sus oficiales Y sus transversales. `con_criterios` se mide solo
     * sobre las oficiales (gobierna el aviso "Sin criterios").
     */
    private function getCargasResumen(int $docenteId, int $periodoId): array
    {
        return $this->calModel->query("
            SELECT ca.id, ca.horas_semanales,
                   s.nombre          AS seccion_nombre,
                   s.es_unidocente,
                   g.nombre_display  AS grado_nombre,
                   n.nombre          AS nivel_nombre,
                   a.id              AS area_id,
                   CASE WHEN s.es_unidocente = 1 THEN a.nombre
                        ELSE COALESCE(sa.nombre, a.nombre) END AS nombre_display,
                   (
                       (
                           SELECT COUNT(DISTINCT c2.id) FROM competencias c2
                           WHERE (ca.subarea_id IS NOT NULL AND c2.subarea_id = ca.subarea_id)
                              OR (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL AND c2.area_id = ca.area_id)
                       ) + (
                           SELECT COUNT(*) FROM competencias ct
                           INNER JOIN areas at2 ON at2.id = ct.area_id
                           WHERE at2.tipo = 'transversal' AND at2.nivel_id = n.id
                       )
                   ) AS total_comp,
                   (
                       SELECT COUNT(*) FROM bloqueos_competencia bc
                       WHERE bc.carga_id = ca.id AND bc.periodo_id = ?
                   ) AS bloq,
                   (
                       SELECT COUNT(DISTINCT cr.competencia_id) FROM criterios cr
                       WHERE cr.carga_id = ca.id AND cr.periodo_id = ? AND cr.eliminado_en IS NULL
                         AND cr.competencia_id IN (
                             SELECT c4.id FROM competencias c4
                             WHERE (ca.subarea_id IS NOT NULL AND c4.subarea_id = ca.subarea_id)
                                OR (ca.area_id IS NOT NULL AND ca.subarea_id IS NULL AND c4.area_id = ca.area_id)
                         )
                   ) AS con_criterios
            FROM cargas_academicas ca
            INNER JOIN secciones s ON s.id = ca.seccion_id
            INNER JOIN grados g    ON g.id = s.grado_id
            INNER JOIN niveles n   ON n.id = g.nivel_id
            LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
            LEFT  JOIN areas a     ON a.id  = COALESCE(ca.area_id, sa.area_id)
            WHERE ca.docente_id = ? AND ca.estado = 'activa'
              -- Excluye la carga transversal independiente (modelo viejo): las
              -- TIC/GAMA se registran dentro de cada carga; el tutor cierra en
              -- /docente/tutoria. Sin esto, el conteo de la card del dashboard
              -- sumaba una carga fantasma al tutor.
              AND (a.tipo IS NULL OR a.tipo != 'transversal')
            ORDER BY n.id, g.numero, s.nombre, a.orden, sa.orden
        ", [$periodoId, $periodoId, $docenteId]);
    }

    /** Conteo de matriculados aprobados por sección de los niveles dados. */
    private function getNominaResumen(array $niveles): array
    {
        $ids = array_map('intval', array_column($niveles, 'id'));
        if (empty($ids)) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        return $this->calModel->query("
            SELECT n.id AS nivel_id, n.nombre AS nivel_nombre,
                   g.numero AS grado_numero, g.nombre_display AS grado_nombre,
                   s.id AS seccion_id, s.nombre AS seccion_nombre,
                   COUNT(*) AS n
            FROM matriculas m
            INNER JOIN secciones s ON s.id = m.seccion_id
            INNER JOIN grados g    ON g.id = s.grado_id
            INNER JOIN niveles n   ON n.id = g.nivel_id
            WHERE m.estado = 'aprobada' AND m.tipo != 'trasladado'
              -- Retorno de grado: la nomina (documento oficial SIAGIE) muestra
              -- la matricula OFICIAL y oculta la operativa interna. Mismo filtro
              -- que getMatriculados, para que la card y el detalle cuadren.
              AND m.id NOT IN (
                  SELECT matricula_operativa_id
                  FROM retornos_grado
                  WHERE estado = 'activo'
              )
              AND n.id IN ($ph)
            GROUP BY s.id
            ORDER BY n.id, g.numero, s.nombre
        ", $ids);
    }

    /**
     * Matriculados aprobados de los niveles dados (o de una sección concreta),
     * con su apoderado responsable (vinculo_familiar.es_responsable = 1).
     */
    private function getMatriculados(array $niveles, int $seccionId = 0): array
    {
        $ids = array_map('intval', array_column($niveles, 'id'));
        if (empty($ids)) {
            return [];
        }
        $ph     = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $filtroSeccion = '';
        if ($seccionId > 0) {
            $filtroSeccion = ' AND s.id = ?';
            $params[]      = $seccionId;
        }

        return $this->calModel->query("
            SELECT m.id AS matricula_id,
                   p.apellido_paterno, p.apellido_materno, p.nombres,
                   s.id AS seccion_id, s.nombre AS seccion_nombre,
                   g.id AS grado_id, g.numero AS grado_numero, g.nombre_display AS grado_nombre,
                   n.id AS nivel_id, n.nombre AS nivel_nombre,
                   TRIM(CONCAT(
                       COALESCE(ap.apellido_paterno, ''), ' ',
                       COALESCE(ap.apellido_materno, ''), ' ',
                       COALESCE(ap.nombres, '')
                   )) AS apoderado_nombre,
                   ap.telefono AS apoderado_telefono,
                   tp.sexo AS tutor_sexo,
                   TRIM(CONCAT(
                       COALESCE(tp.apellido_paterno, ''), ' ',
                       COALESCE(tp.apellido_materno, ''), ' ',
                       COALESCE(tp.nombres, '')
                   )) AS tutor_nombre,
                   -- ¿Tiene al menos una competencia bloqueada (boleta con
                   -- contenido)? Gobierna la aparicion de los botones de boleta.
                   EXISTS (
                       SELECT 1 FROM calificaciones cal
                       INNER JOIN bloqueos_competencia bc
                           ON bc.carga_id       = cal.carga_id
                          AND bc.competencia_id = cal.competencia_id
                          AND bc.periodo_id     = cal.periodo_id
                       WHERE cal.matricula_id = m.id
                   ) AS tiene_boleta
            FROM matriculas m
            INNER JOIN estudiantes e ON e.id = m.estudiante_id
            INNER JOIN personas p    ON p.id = e.persona_id
            INNER JOIN secciones s   ON s.id = m.seccion_id
            INNER JOIN grados g      ON g.id = s.grado_id
            INNER JOIN niveles n     ON n.id = g.nivel_id
            LEFT  JOIN usuarios tu   ON tu.id = s.tutor_id
            LEFT  JOIN personas tp   ON tp.id = tu.persona_id
            LEFT JOIN vinculo_familiar vf
                ON  vf.estudiante_id = e.id
                AND vf.es_responsable = 1
                AND vf.id = (
                    SELECT MIN(vf2.id) FROM vinculo_familiar vf2
                    WHERE vf2.estudiante_id = e.id AND vf2.es_responsable = 1
                )
            LEFT JOIN apoderados apo ON apo.id = vf.apoderado_id
            LEFT JOIN personas ap    ON ap.id = apo.persona_id
            WHERE m.estado = 'aprobada' AND m.tipo != 'trasladado'
              -- Retorno de grado: la nómina es un documento OFICIAL (SIAGIE), por
              -- lo que muestra la matrícula ORIGINAL (grado/sección oficial) y
              -- oculta la operativa interna (grado inferior). Espejo de la regla
              -- de OrdenMeritoModel, que excluye la oficial para el ranking operativo.
              AND m.id NOT IN (
                  SELECT matricula_operativa_id
                  FROM retornos_grado
                  WHERE estado = 'activo'
              )
              AND n.id IN ($ph)$filtroSeccion
            ORDER BY n.id, g.numero, s.nombre,
                     p.apellido_paterno, p.apellido_materno, p.nombres
        ", $params);
    }

    /** Sesiones de horario del docente, ordenadas por día y bloque. */
    private function getHorario(int $docenteId): array
    {
        return $this->calModel->query("
            SELECT bh.dia_semana, bh.numero_bloque, bh.hora_inicio, bh.hora_fin,
                   s.id AS seccion_id,
                   g.nombre_display AS grado_nombre, g.numero AS grado_numero,
                   n.codigo AS nivel_codigo,
                   s.nombre AS seccion_nombre,
                   CASE WHEN s.es_unidocente = 1 THEN a.nombre
                        ELSE COALESCE(sa.nombre, a.nombre) END AS area_nombre
            FROM sesiones_horario sh
            INNER JOIN bloques_horario bh ON bh.id = sh.bloque_id
            INNER JOIN cargas_academicas ca ON ca.id = sh.carga_id AND ca.estado = 'activa'
            INNER JOIN secciones s ON s.id = sh.seccion_id
            INNER JOIN grados g    ON g.id = s.grado_id
            INNER JOIN niveles n   ON n.id = g.nivel_id
            LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
            LEFT  JOIN areas a     ON a.id  = COALESCE(ca.area_id, sa.area_id)
            WHERE sh.docente_id = ?
            ORDER BY FIELD(bh.dia_semana,'lunes','martes','miercoles','jueves','viernes'),
                     bh.hora_inicio, bh.hora_fin
        ", [$docenteId]);
    }
}
