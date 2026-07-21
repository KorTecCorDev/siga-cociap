<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnioAcademicoModel;
use App\Models\ControlOperativoModel;
use App\Models\PublicacionBoletaModel;
use Core\Session;

/**
 * ControlOperativoController
 * Centro de Control Operativo: detecta inconsistencias de datos y enlaza al módulo
 * donde se corrigen. Ademas orquesta el HITO A del cierre de bimestre (aprobar
 * boletas -> borrador para los docentes) y la COMPUERTA DE PUBLICACION de las
 * boletas a las familias (migracion 044).
 */
class ControlOperativoController extends BaseController
{
    private ControlOperativoModel  $model;
    private AnioAcademicoModel     $anioModel;
    private PublicacionBoletaModel $publicacionModel;

    /**
     * Quien PUBLICA. Los directores entran al Centro de Control y VEN el estado
     * de publicacion, pero no operan la compuerta. Se valida en cada metodo (no
     * ocultando el boton): esconder la UI no es control de acceso.
     */
    private const ROLES_PUBLICAN = ['admin', 'registro_academico'];

    public function __construct()
    {
        $this->requireRole([
            'admin', 'registro_academico',
            'director_general', 'director_ebr',
        ]);
        $this->model            = new ControlOperativoModel();
        $this->anioModel        = new AnioAcademicoModel();
        $this->publicacionModel = new PublicacionBoletaModel();
    }

    /**
     * GET /admin/control  (acepta ?periodo_id)
     * Arma los 4 chequeos para el periodo seleccionado (o el activo por defecto).
     */
    public function index(): void
    {
        $periodos = $this->model->getPeriodos();

        $periodoId = (int) ($this->query('periodo_id', 0));
        $periodo = $periodoId > 0
            ? $this->model->getPeriodo($periodoId)
            : $this->model->getPeriodoPorDefecto();

        if (!$periodo) {
            $this->view('admin/control/index', [
                'titulo'   => 'Centro de Control',
                'periodos' => $periodos,
                'periodo'  => null,
                'chequeos' => [],
                'totalIncidencias' => 0,
                'estadoBoleta' => 'registro',
                'incidencias'  => ['docentes' => [], 'resumen' => ['competencias' => 0, 'cargas' => 0, 'docentes' => 0, 'sin_avance' => 0]],
                'publicacion'  => [],
                'puedePublicar'=> false,
            ]);
            return;
        }

        $periodoId = (int) $periodo['id'];
        $anioId    = (int) $periodo['anio_id'];

        $chequeos = [
            'empates' => [
                'titulo'    => 'Empates de orden de mérito sin resolver',
                'severidad' => 'critico',
                'accion'    => 'Resolver en orden de mérito',
                'items'     => $this->model->empatesPendientes($periodoId),
            ],
            'competencias' => [
                'titulo'    => 'Competencias con notas sin bloquear',
                'severidad' => 'critico',
                'accion'    => 'Ir a bloqueos del bimestre',
                'accion_url'=> url('director/bloqueos'),
                'items'     => $this->model->competenciasSinBloquear($periodoId),
            ],
            'fantasmas' => [
                'titulo'    => 'Competencias fantasma (bloqueadas sin criterios)',
                'severidad' => 'critico',
                'accion'    => 'Requiere depuracion de datos (migracion 033)',
                'items'     => $this->model->competenciasFantasma($periodoId),
            ],
            'tutores' => [
                'titulo'    => 'Secciones sin tutor asignado',
                'severidad' => 'advertencia',
                'accion'    => 'Ir a secciones y tutores',
                'accion_url'=> url('admin/secciones'),
                'items'     => $this->model->seccionesSinTutor(),
            ],
            'matriculas' => [
                'titulo'    => 'Matrículas pendientes de activar',
                'severidad' => 'advertencia',
                'accion'    => 'Ir a matrículas',
                'accion_url'=> url('matriculas'),
                'items'     => $this->model->matriculasPendientes($anioId),
            ],
        ];

        $totalIncidencias = 0;
        foreach ($chequeos as $c) {
            $totalIncidencias += count($c['items']);
        }

        $estadoBoleta = boleta_estado_bimestre($periodo['estado'] ?? null, $periodo['boletas_aprobadas_en'] ?? null);

        // F5: reporte de incidencias del cierre forzado. Solo tiene sentido una
        // vez aprobado el bimestre (en 'registro' aun no hay bloqueos de cierre).
        $incidencias = $estadoBoleta === 'registro'
            ? ['docentes' => [], 'resumen' => ['competencias' => 0, 'cargas' => 0, 'docentes' => 0, 'sin_avance' => 0]]
            : $this->model->incidenciasCierre($periodoId);

        $this->view('admin/control/index', [
            'titulo'           => 'Centro de Control',
            'periodos'         => $periodos,
            'periodo'          => $periodo,
            'chequeos'         => $chequeos,
            'totalIncidencias' => $totalIncidencias,
            'estadoBoleta'     => $estadoBoleta,
            'incidencias'      => $incidencias,
            // COMPUERTA DE PUBLICACION (044): estado por nivel del bimestre.
            'publicacion'      => $this->publicacionModel->estadoPorNivel($periodoId),
            'puedePublicar'    => $this->puedePublicar(),
        ]);
    }

    /**
     * POST /admin/control/{periodo_id}/aprobar-bimestre
     * HITO A: bloquea y aprueba el bimestre -> genera boletas BORRADOR para los
     * docentes. Fuerza el bloqueo de competencias pendientes (Incidencias).
     */
    public function aprobarBimestre(string $periodoId): void
    {
        $this->validateCsrf();
        $periodoId = (int) $periodoId;
        $periodo   = $this->model->getPeriodo($periodoId);
        $volver    = url('admin/control?periodo_id=' . $periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/control'), 'Bimestre no encontrado.');
        }
        if ($periodo['estado'] !== 'activo') {
            $this->redirectWithError($volver, 'Solo se puede aprobar un bimestre activo.');
        }
        if (!empty($periodo['boletas_aprobadas_en'])) {
            $this->redirectWithError($volver, 'Las boletas de este bimestre ya estan en borrador.');
        }

        $usuarioId = (int) (Session::user()['id'] ?? 0);
        try {
            $this->anioModel->beginTransaction();
            $incidencias = $this->anioModel->aprobarBoletasBimestre($periodoId, $usuarioId);
            $this->anioModel->commit();
        } catch (\Exception $e) {
            $this->anioModel->rollback();
            log_error('Error aprobando boletas del bimestre', ['id' => $periodoId, 'error' => $e->getMessage()]);
            $this->redirectWithError($volver, 'No se pudo aprobar el bimestre. Intenta de nuevo.');
        }

        $msg = 'Bimestre aprobado: las boletas BORRADOR ya estan disponibles para los docentes.';
        if ($incidencias > 0) {
            $msg .= ' Se forzo el bloqueo de ' . $incidencias . ' competencia(s) pendiente(s).';
        }
        $this->redirectWithSuccess($volver, $msg);
    }

    /**
     * POST /admin/control/{periodo_id}/anular-aprobacion
     * Revierte el HITO A (BORRADOR -> EN REGISTRO). No libera bloqueos.
     */
    public function anularAprobacion(string $periodoId): void
    {
        $this->validateCsrf();
        $periodoId = (int) $periodoId;
        $periodo   = $this->model->getPeriodo($periodoId);
        $volver    = url('admin/control?periodo_id=' . $periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/control'), 'Bimestre no encontrado.');
        }
        if ($periodo['estado'] !== 'activo' || empty($periodo['boletas_aprobadas_en'])) {
            $this->redirectWithError($volver, 'Este bimestre no tiene boletas en borrador para revertir.');
        }

        $this->anioModel->anularAprobacionBoletas($periodoId);
        $this->redirectWithSuccess($volver, 'Aprobacion revertida: las boletas borrador dejaron de mostrarse.');
    }

    // ── COMPUERTA DE PUBLICACION DE BOLETAS (migracion 044) ──────────────
    //
    // Cerrar un bimestre YA NO publica sus boletas a las familias: publicar
    // es un acto separado, POR NIVEL y con fecha/hora, porque las boletas se
    // entregan en reuniones oficiales (primaria suele ser un dia antes que
    // secundaria). Afecta las 3 superficies de familias: boleta por token,
    // boleta digital y /padre/notas. NO afecta al staff: la salida masiva
    // impresa, el docente, la gestion, el SIAGIE, el orden de merito y las
    // rectificaciones siguen viendo lo mismo que antes.

    /** ¿El usuario actual puede operar la compuerta? (los directores solo ven) */
    private function puedePublicar(): bool
    {
        return Session::hasRole(self::ROLES_PUBLICAN);
    }

    /**
     * Validaciones comunes de las 3 acciones de la compuerta. Corta con
     * redirect si algo no cuadra; si retorna, se puede escribir.
     *
     * @return array{0: array, 1: int, 2: string} [periodo, nivelId, volverUrl]
     */
    private function guardPublicacion(int $periodoId): array
    {
        $this->validateCsrf();

        $periodo = $this->model->getPeriodo($periodoId);
        $volver  = url('admin/control?periodo_id=' . $periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/control'), 'Bimestre no encontrado.');
        }
        // El rol se valida en el METODO, no ocultando el boton en la vista.
        if (!$this->puedePublicar()) {
            $this->redirectWithError($volver, 'Tu rol no puede publicar boletas. Consulta con Registro Academico.');
        }
        // Publicar exige bimestre CERRADO: no se entregan boletas de un
        // bimestre que todavia admite cambios de notas.
        if ($periodo['estado'] !== 'cerrado') {
            $this->redirectWithError($volver, 'Solo se pueden publicar las boletas de un bimestre cerrado.');
        }

        $nivelId = (int) $this->input('nivel_id', 0);
        if (!$this->publicacionModel->nivelExiste($nivelId)) {
            $this->redirectWithError($volver, 'Nivel no valido.');
        }

        return [$periodo, $nivelId, $volver];
    }

    /**
     * POST /admin/control/{periodo_id}/publicar
     * Publica AHORA las boletas del bimestre para un nivel.
     */
    public function publicar(string $periodoId): void
    {
        $periodoId = (int) $periodoId;
        [$periodo, $nivelId, $volver] = $this->guardPublicacion($periodoId);

        $this->publicacionModel->publicar(
            $periodoId,
            $nivelId,
            $this->publicacionModel->ahora(),
            (int) (Session::user()['id'] ?? 0)
        );

        $this->redirectWithSuccess(
            $volver,
            'Boletas publicadas: las familias del nivel ya pueden verlas en linea.'
        );
    }

    /**
     * POST /admin/control/{periodo_id}/programar
     * Programa la publicacion para una fecha y hora futura. Sin cron: la
     * condicion se evalua al leer la boleta.
     */
    public function programar(string $periodoId): void
    {
        $periodoId = (int) $periodoId;
        [$periodo, $nivelId, $volver] = $this->guardPublicacion($periodoId);

        // datetime-local llega como 'YYYY-MM-DDTHH:MM'.
        $entrada = trim((string) $this->input('publica_en', ''));
        $fecha   = $entrada !== '' ? date_create(str_replace('T', ' ', $entrada)) : false;

        if (!$fecha) {
            $this->redirectWithError($volver, 'Indica una fecha y hora validas para la publicacion.');
        }

        $publicaEn = $fecha->format('Y-m-d H:i:s');
        // La hora se compara SIEMPRE contra el reloj de la aplicacion
        // (America/Lima), nunca contra NOW() de MySQL: el servidor de
        // produccion puede estar en otro huso y adelantaria la publicacion.
        if ($publicaEn <= $this->publicacionModel->ahora()) {
            $this->redirectWithError(
                $volver,
                'La fecha programada debe ser futura. Si quieres publicar de inmediato, usa "Publicar ahora".'
            );
        }

        $this->publicacionModel->publicar(
            $periodoId,
            $nivelId,
            $publicaEn,
            (int) (Session::user()['id'] ?? 0)
        );

        $this->redirectWithSuccess(
            $volver,
            'Publicacion programada para el ' . $fecha->format('d/m/Y') . ' a las ' . $fecha->format('H:i')
            . '. Hasta ese momento las familias no ven nada.'
        );
    }

    /**
     * POST /admin/control/{periodo_id}/despublicar
     * Retira las boletas ya publicadas. Es DEFINITIVO (a diferencia de la
     * suspension automatica por reapertura): solo se revierte publicando de
     * nuevo a mano. Exige motivo, que queda auditado en la fila.
     */
    public function despublicar(string $periodoId): void
    {
        $periodoId = (int) $periodoId;
        [$periodo, $nivelId, $volver] = $this->guardPublicacion($periodoId);

        $motivo = trim((string) $this->input('motivo', ''));
        if (mb_strlen($motivo) < 10) {
            $this->redirectWithError($volver, 'Debes indicar el motivo para retirar las boletas (minimo 10 caracteres).');
        }

        $this->publicacionModel->despublicar(
            $periodoId,
            $nivelId,
            mb_substr($motivo, 0, 500),
            (int) (Session::user()['id'] ?? 0)
        );

        $this->redirectWithSuccess(
            $volver,
            'Boletas retiradas: las familias del nivel dejaron de verlas en linea.'
        );
    }
}
