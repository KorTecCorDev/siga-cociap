<?php

namespace App\Controllers\Consulta;

use App\Controllers\BaseController;
use App\Models\CalificacionModel;
use App\Models\OmisionCriterioModel;
use App\Models\ExoneracionModel;

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

    public function __construct()
    {
        $this->requireRole(['admin', 'registro_academico', 'director_general', 'director_ebr']);
        $this->calModel     = new CalificacionModel();
        $this->omisionModel = new OmisionCriterioModel();
        $this->exoModel     = new ExoneracionModel();
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
                    ];
                }
                $byCarga[$cid]['competencias']++;
            }
            $cargas = array_values($byCarga);
        }

        $this->view('consulta-notas/seccion', [
            'titulo'  => 'Consulta de calificaciones',
            'periodo' => $periodo,
            'seccion' => $seccion,
            'cargas'  => $cargas,
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
