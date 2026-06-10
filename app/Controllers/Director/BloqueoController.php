<?php

namespace App\Controllers\Director;

use App\Controllers\BaseController;
use App\Models\CalificacionModel;
use App\Models\TransversalModel;
use Core\Session;

class BloqueoController extends BaseController
{
    private CalificacionModel $calModel;
    private TransversalModel  $transModel;

    public function __construct()
    {
        $this->requireRole(['admin', 'director_general', 'director_ebr']);
        $this->calModel   = new CalificacionModel();
        $this->transModel = new TransversalModel();
    }

    /**
     * GET /director/bloqueos
     * Lista todas las competencias del periodo seleccionado con su estado.
     */
    public function index(): void
    {
        $periodos = $this->calModel->query("
            SELECT p.id, p.numero, p.nombre_display, p.estado, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.estado IN ('activo', 'cerrado')
            ORDER BY a.anio DESC, p.numero ASC
        ");

        $periodoId    = (int) ($this->query('periodo_id') ?? 0);
        $competencias = [];
        $periodo      = null;
        $stats        = ['total' => 0, 'bloqueadas' => 0, 'pendientes' => 0, 'sin_criterios' => 0];

        if ($periodoId) {
            $periodo = $this->calModel->queryOne("
                SELECT p.*, a.anio
                FROM periodos p
                INNER JOIN anios_academicos a ON a.id = p.anio_id
                WHERE p.id = ?
            ", [$periodoId]);

            if ($periodo) {
                $competencias           = $this->calModel->getCompetenciasPorPeriodo($periodoId);
                $stats['total']         = count($competencias);
                $stats['bloqueadas']    = count(array_filter($competencias, fn($c) => $c['bloqueo_id'] !== null));
                $stats['sin_criterios'] = count(array_filter($competencias, fn($c) => $c['bloqueo_id'] === null && (int)$c['num_criterios'] === 0));
                $stats['pendientes']    = $stats['total'] - $stats['bloqueadas'] - $stats['sin_criterios'];

                // Agrupar por docente y calcular conteos
                $statsDocentes = [];
                foreach ($competencias as $c) {
                    $did = (int) $c['docente_id'];
                    if (!isset($statsDocentes[$did])) {
                        $statsDocentes[$did] = [
                            'apellido'      => $c['docente_apellido'],
                            'nombres'       => $c['docente_nombres'],
                            'total'         => 0,
                            'bloqueadas'    => 0,
                            'pendientes'    => 0,
                            'sin_criterios' => 0,
                        ];
                    }
                    $d = &$statsDocentes[$did];
                    $d['total']++;
                    if ($c['bloqueo_id'] !== null) {
                        $d['bloqueadas']++;
                    } elseif ((int) $c['num_criterios'] === 0) {
                        $d['sin_criterios']++;
                    } else {
                        $d['pendientes']++;
                    }
                    unset($d);
                }

                // Ordenar: más sin_criterios primero; en empate, más pendientes primero
                usort($statsDocentes, function ($a, $b) {
                    if ($b['sin_criterios'] !== $a['sin_criterios']) {
                        return $b['sin_criterios'] - $a['sin_criterios'];
                    }
                    return $b['pendientes'] - $a['pendientes'];
                });

                // Top 5 con algún incumplimiento
                $topCriticos = array_slice(
                    array_values(array_filter(
                        $statsDocentes,
                        fn($d) => $d['sin_criterios'] > 0 || $d['pendientes'] > 0
                    )),
                    0, 5
                );

                // Avance por nivel educativo
                $statsPorNivel = [];
                foreach ($competencias as $c) {
                    $nid = (int) $c['nivel_id'];
                    if (!isset($statsPorNivel[$nid])) {
                        $statsPorNivel[$nid] = [
                            'nombre'     => $c['nivel_nombre'],
                            'total'      => 0,
                            'bloqueadas' => 0,
                        ];
                    }
                    $statsPorNivel[$nid]['total']++;
                    if ($c['bloqueo_id'] !== null) {
                        $statsPorNivel[$nid]['bloqueadas']++;
                    }
                }

                // Secciones al 100%
                $_secStats = [];
                foreach ($competencias as $c) {
                    $sid = (int) $c['seccion_id'];
                    if (!isset($_secStats[$sid])) {
                        $_secStats[$sid] = ['total' => 0, 'bloqueadas' => 0];
                    }
                    $_secStats[$sid]['total']++;
                    if ($c['bloqueo_id'] !== null) {
                        $_secStats[$sid]['bloqueadas']++;
                    }
                }
                $totalSecciones     = count($_secStats);
                $seccionesCompletas = count(array_filter(
                    $_secStats,
                    fn($s) => $s['total'] > 0 && $s['bloqueadas'] === $s['total']
                ));

                // Días restantes para el cierre
                $diasRestantes = null;
                if (!empty($periodo['limite_notas'])) {
                    $diasRestantes = (int) ceil(
                        (strtotime($periodo['limite_notas']) - time()) / 86400
                    );
                }
            }
        }

        $this->view('director/bloqueos/index', [
            'titulo'             => 'Gestión de bloqueos',
            'periodos'           => $periodos,
            'periodoId'          => $periodoId,
            'periodo'            => $periodo,
            'competencias'       => $competencias,
            'stats'              => $stats,
            'statsDocentes'      => $statsDocentes   ?? [],
            'topCriticos'        => $topCriticos     ?? [],
            'statsPorNivel'      => $statsPorNivel   ?? [],
            'totalSecciones'     => $totalSecciones  ?? 0,
            'seccionesCompletas' => $seccionesCompletas ?? 0,
            'diasRestantes'      => $diasRestantes   ?? null,
            'page_scripts'       => ['bloqueos'],
        ]);
    }

    /**
     * POST /director/bloqueos/{id}/desbloquear
     * Elimina el bloqueo para que el docente pueda editar las notas.
     *
     * Reapertura de carga: invariante de la Variante 1 — las transversales
     * de la carga están bloqueadas solo cuando TODAS las propias lo están.
     * Al desbloquear una propia se liberan también las TIC/GAMA de la carga
     * (se re-bloquean juntas al aprobar de nuevo la última) y se ANULA el
     * cierre transversal vigente de la sección con traza de quién/por qué.
     */
    public function desbloquear(string $id): void
    {
        $this->validateCsrf();
        $id   = (int) $id;
        $user = Session::user();

        $bloqueo = $this->calModel->queryOne("
            SELECT bc.id, bc.periodo_id, bc.carga_id, bc.competencia_id,
                   ca.seccion_id,
                   comp.nombre_corto AS competencia_nombre,
                   (at2.tipo = 'transversal') AS es_transversal
            FROM bloqueos_competencia bc
            INNER JOIN cargas_academicas ca ON ca.id  = bc.carga_id
            INNER JOIN competencias comp    ON comp.id = bc.competencia_id
            LEFT  JOIN areas at2            ON at2.id  = comp.area_id
            WHERE bc.id = ?
        ", [$id]);

        if (!$bloqueo) {
            $this->redirectWithError(url('director/bloqueos'), 'Bloqueo no encontrado.');
        }

        $periodoId = (int) $bloqueo['periodo_id'];
        $cargaId   = (int) $bloqueo['carga_id'];
        $ok        = $this->calModel->desbloquearCompetencia($id);

        if ($ok) {
            // Liberar también las transversales de la carga (si la propia
            // desbloqueada no era ya una transversal).
            if (empty($bloqueo['es_transversal'])) {
                $this->calModel->execute("
                    DELETE bc FROM bloqueos_competencia bc
                    INNER JOIN competencias comp ON comp.id = bc.competencia_id
                    INNER JOIN areas a           ON a.id = comp.area_id AND a.tipo = 'transversal'
                    WHERE bc.carga_id   = ?
                      AND bc.periodo_id = ?
                ", [$cargaId, $periodoId]);
            }

            // Anular el cierre transversal vigente de la sección.
            $this->transModel->anularCierreVigente(
                (int) $bloqueo['seccion_id'],
                $periodoId,
                (int) $user['id'],
                'Desbloqueo de la competencia "' . ($bloqueo['competencia_nombre'] ?? $bloqueo['competencia_id'])
                    . '" (carga ' . $cargaId . ') por el director.'
            );

            $this->redirectWithSuccess(
                url("director/bloqueos?periodo_id={$periodoId}"),
                'Competencia desbloqueada. El docente puede volver a editar las notas. '
                . 'Si la sección tenía cierre transversal, quedó anulado hasta repetir el ciclo.'
            );
        }

        $this->redirectWithError(
            url("director/bloqueos?periodo_id={$periodoId}"),
            'No se pudo desbloquear la competencia.'
        );
    }

    /**
     * POST /director/bloqueos/bloquear
     * Bloquea manualmente una competencia desde el panel del director.
     */
    public function bloquear(): void
    {
        $this->validateCsrf();

        $cargaId       = (int) $this->input('carga_id');
        $competenciaId = (int) $this->input('competencia_id');
        $periodoId     = (int) $this->input('periodo_id');
        $user          = Session::user();

        if (!$cargaId || !$competenciaId || !$periodoId) {
            $this->redirectWithError(url('director/bloqueos'), 'Datos incompletos.');
        }

        $ok = $this->calModel->bloquearCompetencia(
            $cargaId, $competenciaId, $periodoId, $user['id']
        );

        if ($ok) {
            $this->redirectWithSuccess(
                url("director/bloqueos?periodo_id={$periodoId}"),
                'Competencia bloqueada correctamente.'
            );
        }

        $this->redirectWithError(
            url("director/bloqueos?periodo_id={$periodoId}"),
            'No se pudo bloquear la competencia.'
        );
    }
}
