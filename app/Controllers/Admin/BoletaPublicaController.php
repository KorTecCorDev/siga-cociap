<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BoletaModel;
use App\Models\BoletaPublicaModel;
use App\Models\CalificacionModel;
use Core\Session;
use Core\View;

class BoletaPublicaController extends BaseController
{
    private BoletaPublicaModel $model;
    private CalificacionModel  $calModel;
    private BoletaModel        $boletaModel;

    public function __construct()
    {
        $this->requireRole(['admin', 'registro_academico']);
        $this->model       = new BoletaPublicaModel();
        $this->calModel    = new CalificacionModel();
        $this->boletaModel = new BoletaModel();
    }

    /**
     * URL del QR permanente por token (matrícula identidad). Mismo criterio que
     * Boleta\BoletaController: un solo enlace por estudiante, estable todo el año.
     */
    private function urlBoletaToken(int $matriculaId): string
    {
        $identidad = (int) $this->calModel->boletaContexto($matriculaId)['identidad'];
        return url('boleta/digital/' . $this->model->getOCrearToken($identidad));
    }

    /** GET /admin/boletas-publicas — selector de periodos */
    public function index(): void
    {
        // Conteo de boletas OFICIALES disponibles (matrículas con ≥1 competencia
        // bloqueada), no de códigos generados (el código quedó dormido).
        $periodos = $this->model->query("
            SELECT p.id, p.numero, p.nombre_display, a.anio,
                   COUNT(DISTINCT CASE WHEN bc.id IS NOT NULL THEN cal.matricula_id END) AS total_boletas
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            LEFT JOIN calificaciones cal ON cal.periodo_id = p.id
            LEFT JOIN bloqueos_competencia bc
                   ON bc.carga_id       = cal.carga_id
                  AND bc.competencia_id = cal.competencia_id
                  AND bc.periodo_id     = cal.periodo_id
            WHERE a.estado = 'activo'
            GROUP BY p.id
            ORDER BY p.numero
        ");

        $this->view('admin/boletas-publicas/index', [
            'titulo'   => 'Boletas Públicas',
            'periodos' => $periodos,
        ]);
    }

    /** GET /admin/boletas-publicas/{periodo_id} — tabla de boletas generadas */
    public function porPeriodo($periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $estudiantes = $this->model->getEstudiantesParaPeriodo($periodoId);
        $secciones   = $this->model->getSeccionesParaPeriodo($periodoId);

        $totalBoletas     = count($estudiantes);
        $totalConsultadas = count(array_filter($estudiantes, fn($e) => (int) $e['token_consultas'] > 0));

        $this->view('admin/boletas-publicas/periodo', [
            'titulo'           => 'Boletas — ' . $periodo['nombre_display'],
            'periodo'          => $periodo,
            'estudiantes'      => $estudiantes,
            'secciones'        => $secciones,
            'totalBoletas'     => $totalBoletas,
            'totalConsultadas' => $totalConsultadas,
        ]);
    }

    /** POST /admin/boletas-publicas/generar-tokens — genera tokens permanentes para matrículas sin token */
    public function generarTokens(): void
    {
        $this->validateCsrf();
        $count = $this->model->generarTokensActivos();
        $msg   = $count > 0
            ? "Se generaron {$count} token" . ($count !== 1 ? 's' : '') . " de acceso."
            : 'Todas las matrículas ya tienen token de acceso.';

        $count > 0
            ? $this->redirectWithSuccess(url('admin/boletas-publicas'), $msg)
            : $this->redirectWithError(url('admin/boletas-publicas'), $msg);
    }

    /** POST /admin/boletas-publicas/{periodo_id}/actualizar — resetea fechas de boletas con novedades */
    public function actualizar($periodoId): void
    {
        $this->validateCsrf();
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $usuarioId  = Session::user()['id'] ?? 0;
        $actualizadas = $this->model->actualizarTimestamps($periodoId, $usuarioId);

        $msg = $actualizadas > 0
            ? "{$actualizadas} boleta(s) actualizadas con las nuevas competencias bloqueadas."
            : 'No hay boletas con nuevas competencias desde la última generación.';

        $actualizadas > 0
            ? $this->redirectWithSuccess(url("admin/boletas-publicas/{$periodoId}"), $msg)
            : $this->redirectWithError(url("admin/boletas-publicas/{$periodoId}"), $msg);
    }

    /** POST /admin/boletas-publicas/{periodo_id}/generar */
    public function generar($periodoId): void
    {
        $this->validateCsrf();
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $usuarioId = Session::user()['id'] ?? 0;
        $nuevas    = $this->model->generarMasivo($periodoId, $usuarioId);

        $msg = $nuevas > 0
            ? "Se generaron {$nuevas} boleta(s) nueva(s) con código de acceso."
            : 'No hay boletas nuevas que generar (ya están todas generadas).';

        $nuevas > 0
            ? $this->redirectWithSuccess(url("admin/boletas-publicas/{$periodoId}"), $msg)
            : $this->redirectWithError(url("admin/boletas-publicas/{$periodoId}"), $msg);
    }

    /**
     * GET /admin/boletas-publicas/{periodo_id}/imprimir[?seccion_id=N]
     * Impresión de códigos de acceso. Opcionalmente loteable por sección.
     */
    public function imprimir($periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $seccionId = (int) $this->query('seccion_id', 0) ?: null;
        $boletas   = $this->model->getPorPeriodo($periodoId, $seccionId);

        View::setLayout('print');
        $this->view('admin/boletas-publicas/imprimir', [
            'titulo'  => 'Códigos de Acceso — ' . $periodo['nombre_display'],
            'periodo' => $periodo,
            'boletas' => $boletas,
        ]);
    }

    /**
     * GET /admin/boletas-publicas/{periodo_id}/vista-previa[?seccion_id=N]
     * Vista previa antes de la aprobación de registro académico.
     * Itera sobre las matrículas con ≥1 competencia bloqueada (set candidato
     * a generación), no sobre las que ya tienen código. Pasa $vistaPrevia=true
     * a la vista compartida boleta/alumno.php para suprimir QR y la imagen
     * de firma del director (los datos de la línea de firma se mantienen).
     * Opcionalmente loteable por sección para evitar timeouts con muchos alumnos.
     */
    public function vistaPrevia($periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $seccionId   = (int) $this->query('seccion_id', 0) ?: null;
        $matriculas  = $this->model->getMatriculasAprobadasParaBoleta($periodoId, $seccionId);
        $boletasData = [];

        foreach ($matriculas as $m) {
            // EXCEPCION a la compuerta del Hito A: la vista previa de RA muestra
            // TODOS los periodos (incluido el activo en 'registro') porque es su
            // herramienta para decidir el Hito A. Staff, sin QR, marcada BORRADOR.
            $data = $this->boletaModel->armar((int) $m['matricula_id'], $periodoId, 'todos');
            if ($data) {
                // En vista previa no inyectamos url_boleta (sin QR) ni mostramos
                // firma del director; el flag lo decide en alumno.php.
                $data['vistaPrevia'] = true;
                $boletasData[] = $data;
            }
        }

        View::setLayout('print');
        $this->view('admin/boletas-publicas/vista-previa', [
            'titulo'      => 'Vista previa — ' . $periodo['nombre_display'],
            'periodo'     => $periodo,
            'boletasData' => $boletasData,
        ]);
    }

    /**
     * GET /admin/boletas-publicas/{periodo_id}/boletas-alumno[?seccion_id=N]
     * Impresión masiva de boletas. Opcionalmente loteable por sección para
     * evitar que el render de 200+ boletas dispare timeouts.
     */
    public function boletasAlumno($periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $seccionId   = (int) $this->query('seccion_id', 0) ?: null;
        $matriculas  = $this->model->getMatriculasAprobadasParaBoleta($periodoId, $seccionId);
        $boletasData = [];

        foreach ($matriculas as $m) {
            $matriculaId = (int) $m['matricula_id'];
            // Documento de ARCHIVO generado por staff: solo lo oficial (cerrado),
            // pero IGNORA la compuerta de publicacion (044) porque RA imprime las
            // boletas ANTES de la reunion de entrega; con 'oficial' saldrian en
            // blanco. La compuerta protege el acceso EN LINEA, no la impresion.
            // El QR que lleva impreso si respeta la compuerta: al escanearlo el
            // dia de la entrega, la publicacion ya esta vigente.
            $data = $this->boletaModel->armar($matriculaId, $periodoId, 'archivo');
            if ($data) {
                $data['url_boleta'] = $this->urlBoletaToken($matriculaId);
                $boletasData[] = $data;
            }
        }

        View::setLayout('print');
        $this->view('admin/boletas-publicas/boletas-alumno', [
            'titulo'      => 'Boletas — ' . $periodo['nombre_display'],
            'periodo'     => $periodo,
            'boletasData' => $boletasData,
        ]);
    }

    /**
     * GET /admin/boletas-publicas/{periodo_id}/archivar[?seccion_id=N]
     * Genera PDFs individuales en el navegador (html2pdf.js + JSZip)
     * agrupados en carpetas por sección dentro de un ZIP descargable.
     */
    public function archivar($periodoId): void
    {
        $periodoId = (int) $periodoId;
        $periodo   = $this->getPeriodo($periodoId);

        if (!$periodo) {
            $this->redirectWithError(url('admin/boletas-publicas'), 'Período no encontrado.');
        }

        $seccionId   = (int) $this->query('seccion_id', 0) ?: null;
        $matriculas  = $this->model->getMatriculasAprobadasParaBoleta($periodoId, $seccionId);
        $boletasData = [];

        foreach ($matriculas as $m) {
            $matriculaId = (int) $m['matricula_id'];
            // Documento de ARCHIVO (PDF/ZIP para el colegio): igual que
            // boletasAlumno — solo cerrados, ignorando la compuerta de
            // publicacion (044). Ver el comentario extendido alli.
            $data = $this->boletaModel->armar($matriculaId, $periodoId, 'archivo');
            if (!$data) continue;

            $a = $data['alumno'];

            // Nombre de archivo: APELLIDOS_NOMBRES (sin DNI, mayúsculas con _)
            $partes = [
                mb_strtoupper($a['apellido_paterno']),
                mb_strtoupper($a['apellido_materno']),
                mb_strtoupper($a['nombres']),
            ];
            $data['nombre_archivo'] = str_replace(' ', '_', implode('_', $partes));

            // Carpeta jerárquica: NIVEL/GRADO_SECCION (JSZip crea subcarpetas con /)
            $nivel   = mb_strtoupper(str_replace(' ', '_', trim($a['nivel_nombre'])));
            $grado   = mb_strtoupper(preg_replace('/[°\s.]+/', '', trim($a['grado_nombre'])));
            $seccion = mb_strtoupper(trim($a['seccion_nombre']));
            $data['carpeta'] = "{$nivel}/{$grado}_{$seccion}";

            $data['url_boleta'] = $this->urlBoletaToken($matriculaId);

            $boletasData[] = $data;
        }

        View::setLayout('print');
        $this->view('admin/boletas-publicas/archivar', [
            'titulo'         => 'Archivar boletas — ' . $periodo['nombre_display'],
            'periodo'        => $periodo,
            'boletasData'    => $boletasData,
            'seccionFiltro'  => $seccionId,
        ]);
    }

    // ── Helpers privados ────────────────────────────────────────

    private function getPeriodo(int $periodoId): ?array
    {
        return $this->model->queryOne("
            SELECT p.id, p.numero, p.nombre_display, a.anio, a.id AS anio_id
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.id = ?
            LIMIT 1
        ", [$periodoId]);
    }

}
