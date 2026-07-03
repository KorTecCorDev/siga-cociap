<?php

namespace App\Controllers\Matricula;

use App\Controllers\BaseController;
use App\Models\MatriculaModel;
use App\Models\ApoderadoModel;
use App\Models\EstudianteModel;
use App\Models\TrasladoModel;
use App\Models\DirectorEbrModel;
use Core\Session;
use Core\View;

/**
 * MatriculaController
 * Wizard de matrícula en 3 pasos (estudiante → apoderado → documentos),
 * detalle, activación/desactivación y notas externas (traslados).
 *
 * Reglas clave:
 *  - Toda matrícula nace en estado='pendiente'.
 *  - Solo admin y registro_academico pueden activar/desactivar.
 *  - Las secretarías solo crean y registran documentos.
 *  - Serie del recibo obligatoria siempre.
 */
class MatriculaController extends BaseController
{
    private MatriculaModel $model;
    private ApoderadoModel $apoderados;
    private EstudianteModel $estudiantes;
    private TrasladoModel $traslados;

    /** Tipos de vínculo disponibles: valor BD => etiqueta mostrada. */
    private const TIPOS_VINCULO = [
        'padre'     => 'Padre',
        'madre'     => 'Madre',
        'apoderado' => 'Apoderado',
        'apoderada' => 'Apoderada',
        'abuelo'    => 'Abuelo',
        'abuela'    => 'Abuela',
        'tio'       => 'Tío',
        'tia'       => 'Tía',
        'padrino'   => 'Padrino',
        'madrina'   => 'Madrina',
        'hermano'   => 'Hermano',
        'hermana'   => 'Hermana',
        'primo'     => 'Primo',
        'prima'     => 'Prima',
    ];

    /** Documentos requeridos según el tipo de matrícula. */
    private const DOCS_NUEVO = [
        'recibo_pago'            => 'Recibo de pago',
        'certificado_estudios'   => 'Certificado de estudios',
        'boleta_siagie'          => 'Boleta SIAGIE',
        'ficha_matricula_siagie' => 'Ficha de matrícula SIAGIE',
        'dni_estudiante'         => 'DNI del estudiante',
        'dni_padre'              => 'DNI del padre',
        'dni_madre'              => 'DNI de la madre',
        'dni_apoderado'          => 'DNI del apoderado',
    ];
    private const DOCS_CONTINUADOR = [
        'recibo_pago' => 'Recibo de pago',
    ];

    /**
     * Documentos OBLIGATORIOS para activar una matrícula 'nuevo'. El resto de
     * DOCS_NUEVO es ideal pero opcional. Adicionalmente se exige al menos UNO
     * del grupo DOCS_DNI_APODERADO.
     */
    private const DOCS_OBLIGATORIOS_NUEVO = [
        'recibo_pago', 'certificado_estudios', 'boleta_siagie',
        'ficha_matricula_siagie', 'dni_estudiante',
    ];
    /** Grupo "al menos uno": basta cualquiera de estos DNI de apoderado. */
    private const DOCS_DNI_APODERADO = ['dni_padre', 'dni_madre', 'dni_apoderado'];

    public function __construct()
    {
        $this->requireRole([
            'admin', 'registro_academico',
            'secretaria_academica', 'secretaria_administrativa',
        ]);
        $this->model       = new MatriculaModel();
        $this->apoderados  = new ApoderadoModel();
        $this->estudiantes = new EstudianteModel();
        $this->traslados   = new TrasladoModel();
    }

    /** Catálogo de tipos de vínculo (para reutilizar desde otros módulos). */
    public static function tiposVinculo(): array
    {
        return self::TIPOS_VINCULO;
    }

    // ── GET /matriculas ──────────────────────────────────────────
    public function index(): void
    {
        $anioActivo = $this->estudiantes->anioActivo();
        $anioFiltro = (int) ($this->query('anio_id') ?: ($anioActivo['id'] ?? 0));

        // Orden seguro: validar contra la lista blanca del modelo (default modificación/desc).
        $orden = array_key_exists((string) $this->query('orden'), MatriculaModel::ORDENABLES)
            ? (string) $this->query('orden')
            : 'modificacion';
        $dir = strtolower((string) $this->query('dir')) === 'asc' ? 'asc' : 'desc';

        $filtros = [
            'anio_id'    => $anioFiltro ?: null,
            'grado_id'   => (int) $this->query('grado_id') ?: null,
            'seccion_id' => (int) $this->query('seccion_id') ?: null,
            'estado'     => $this->query('estado') ?: null,
            'tipo'       => $this->query('tipo') ?: null,
            'search'     => trim((string) $this->query('search', '')) ?: null,
            'orden'      => $orden,
            'dir'        => $dir,
        ];

        $porPagina = 25;
        $pagina    = max(1, (int) $this->query('pagina', 1));
        $total     = $this->model->contar($filtros);
        $totalPags = max(1, (int) ceil($total / $porPagina));
        $pagina    = min($pagina, $totalPags);

        $filtros['limit']  = $porPagina;
        $filtros['offset'] = ($pagina - 1) * $porPagina;

        $matriculas = $this->model->listar($filtros);

        $this->view('matriculas/index', [
            'titulo'      => 'Matrículas',
            'matriculas'  => $matriculas,
            'filtros'     => $filtros,
            'anios'       => $this->model->listarAnios(),
            'grados'      => $this->model->listarGrados(),
            'secciones'   => $anioFiltro ? $this->model->listarSecciones($anioFiltro) : [],
            'total'       => $total,
            'pagina'      => $pagina,
            'total_pags'  => $totalPags,
            'orden'       => $orden,
            'dir'         => $dir,
        ]);
    }

    // ── GET /matriculas/resumen ──────────────────────────────────
    /** Dashboard de estadísticas de matrícula (KPIs + gráficos) por año. */
    public function resumen(): void
    {
        $anioActivo = $this->estudiantes->anioActivo();
        $anioFiltro = (int) ($this->query('anio_id') ?: ($anioActivo['id'] ?? 0));
        $nivelId    = (int) $this->query('nivel_id') ?: null;

        $anios   = $this->model->listarAnios();
        $resumen = $anioFiltro
            ? $this->model->getResumen($anioFiltro)
            : ['kpis' => [], 'por_grado' => [], 'por_tipo' => [], 'por_genero' => []];

        // Cuadro cruzado por grado (panorama de todos los estados del año).
        $cuadro = $anioFiltro ? $this->model->getCuadroMatricula($anioFiltro, $nivelId) : [];

        $anioSel = null;
        foreach ($anios as $a) {
            if ((int) $a['id'] === $anioFiltro) { $anioSel = $a; break; }
        }

        $this->view('matriculas/resumen', [
            'titulo'  => 'Resumen de matrículas',
            'resumen' => $resumen,
            'cuadro'  => $cuadro,
            'anios'   => $anios,
            'niveles' => $this->model->listarNiveles(),
            'anioId'  => $anioFiltro,
            'nivelId' => $nivelId,
            'anioSel' => $anioSel,
        ]);
    }

    // ── GET /matriculas/resumen/imprimir ─────────────────────────
    /**
     * Cuadro de matrícula imprimible (A4 portrait) para el comité directivo.
     * Solo admin y registro_academico. Panorama por grado del año (filtro
     * opcional por nivel). Conteo único por matrícula oficial.
     */
    public function resumenImprimir(): void
    {
        $this->requireRole(['admin', 'registro_academico']);

        $anioActivo = $this->estudiantes->anioActivo();
        $anioFiltro = (int) ($this->query('anio_id') ?: ($anioActivo['id'] ?? 0));
        $nivelId    = (int) $this->query('nivel_id') ?: null;

        $cuadro = $anioFiltro ? $this->model->getCuadroMatricula($anioFiltro, $nivelId) : [];

        $anioLabel = '';
        foreach ($this->model->listarAnios() as $an) {
            if ((int) $an['id'] === $anioFiltro) { $anioLabel = (string) $an['anio']; break; }
        }
        $nivelLabel = 'Todos los niveles';
        if ($nivelId) {
            foreach ($this->model->listarNiveles() as $nv) {
                if ((int) $nv['id'] === $nivelId) { $nivelLabel = $nv['nombre']; break; }
            }
        }
        $directorEbr = $anioFiltro
            ? (new DirectorEbrModel())->getVigenteEnFecha($anioFiltro)
            : null;

        View::setLayout('print');
        $this->view('matriculas/resumen-imprimir', [
            'titulo'      => 'Cuadro de matrícula',
            'cuadro'      => $cuadro,
            'anioLabel'   => $anioLabel,
            'nivelLabel'  => $nivelLabel,
            'directorEbr' => $directorEbr,
        ]);
    }

    // ── GET /matriculas/nomina/imprimir ──────────────────────────
    /**
     * Nómina detallada imprimible (reporte al comité directivo). Solo admin y
     * registro_academico. Eje global con los MISMOS filtros del index, agrupada
     * por sección; cada sección cierra con su cuadro resumen. Retorno de grado:
     * el alumno aparece en su sección oficial Y en la operativa (regla R3).
     */
    public function nominaImprimir(): void
    {
        $this->requireRole(['admin', 'registro_academico']);

        $anioActivo = $this->estudiantes->anioActivo();
        $anioFiltro = (int) ($this->query('anio_id') ?: ($anioActivo['id'] ?? 0));

        $filtros = [
            'anio_id'    => $anioFiltro ?: null,
            'grado_id'   => (int) $this->query('grado_id') ?: null,
            'seccion_id' => (int) $this->query('seccion_id') ?: null,
            'estado'     => $this->query('estado') ?: null,
            'tipo'       => $this->query('tipo') ?: null,
            'search'     => trim((string) $this->query('search', '')) ?: null,
        ];

        $alumnos = $this->model->listarParaNomina($filtros);

        // Agrupar por sección preservando el orden (nivel→grado→sección→apellidos).
        $grupos = [];
        foreach ($alumnos as $a) {
            $sid = (int) ($a['seccion_id'] ?? 0);
            if (!isset($grupos[$sid])) {
                $grupos[$sid] = [
                    'seccion_id'     => $sid,
                    'nivel_nombre'   => $a['nivel_nombre']   ?? '',
                    'grado_nombre'   => $a['grado_nombre']   ?? '',
                    'seccion_nombre' => $a['seccion_nombre'] ?? '',
                    'alumnos'        => [],
                ];
            }
            $grupos[$sid]['alumnos'][] = $a;
        }
        foreach ($grupos as &$g) {
            $g['resumen'] = $this->resumenSeccionNomina($g['alumnos']);
        }
        unset($g);

        // Año del reporte (etiqueta) y sello del Director EBR vigente de ese año.
        $anioLabel = '';
        foreach ($this->model->listarAnios() as $an) {
            if ((int) $an['id'] === $anioFiltro) { $anioLabel = (string) $an['anio']; break; }
        }
        $directorEbr = $anioFiltro
            ? (new DirectorEbrModel())->getVigenteEnFecha($anioFiltro)
            : null;

        View::setLayout('print');
        $this->view('matriculas/nomina-imprimir', [
            'titulo'       => 'Nómina detallada de matrículas',
            'grupos'       => array_values($grupos),
            'totalGeneral' => count($alumnos),
            'filtrosTexto' => $this->describirFiltrosNomina($filtros),
            'anioLabel'    => $anioLabel,
            'directorEbr'  => $directorEbr,
        ]);
    }

    /**
     * Cuadro resumen de una sección de la nómina. Cuenta las filas mostradas en
     * esa sección; el género NO contabiliza a quien no tiene registro (sexo NULL).
     * Lleva además el detalle de retorno de grado de la sección:
     *  - cursan_aqui: filas operativas (el alumno cursa en esta aula).
     *  - informativa: filas oficiales cuyo alumno cursa en otro grado.
     */
    private function resumenSeccionNomina(array $alumnos): array
    {
        $r = [
            'total'       => count($alumnos),
            'tipo'        => ['nuevo' => 0, 'continuador' => 0, 'trasladado' => 0],
            'estado'      => ['aprobada' => 0, 'pendiente' => 0, 'desactivado' => 0],
            'genero'      => ['M' => 0, 'F' => 0],
            'cursan_aqui' => 0,
            'informativa' => 0,
        ];
        foreach ($alumnos as $a) {
            if (isset($r['tipo'][$a['tipo']]))     { $r['tipo'][$a['tipo']]++; }
            if (isset($r['estado'][$a['estado']])) { $r['estado'][$a['estado']]++; }
            if ($a['sexo'] === 'M' || $a['sexo'] === 'F') { $r['genero'][$a['sexo']]++; }
            if (!empty($a['retorno_oficial_id']))   { $r['cursan_aqui']++; }
            if (!empty($a['retorno_operativa_id'])) { $r['informativa']++; }
        }
        return $r;
    }

    /** Texto legible de los filtros aplicados, para el encabezado del reporte. */
    private function describirFiltrosNomina(array $f): string
    {
        $estados = ['aprobada' => 'Aprobado', 'pendiente' => 'Pendiente', 'desactivado' => 'Desactivado'];
        $tipos   = ['nuevo' => 'Nuevo', 'continuador' => 'Continuador', 'trasladado' => 'Trasladado'];

        $partes = [];
        if (!empty($f['grado_id'])) {
            foreach ($this->model->listarGrados() as $g) {
                if ((int) $g['id'] === (int) $f['grado_id']) {
                    $partes[] = 'Grado: ' . $g['nivel_nombre'] . ' ' . $g['nombre_display'];
                    break;
                }
            }
        }
        if (!empty($f['seccion_id']) && !empty($f['anio_id'])) {
            foreach ($this->model->listarSecciones((int) $f['anio_id']) as $s) {
                if ((int) $s['id'] === (int) $f['seccion_id']) {
                    $partes[] = 'Sección: ' . $s['grado_nombre'] . ' ' . $s['nombre'];
                    break;
                }
            }
        }
        if (!empty($f['estado'])) { $partes[] = 'Estado: ' . ($estados[$f['estado']] ?? $f['estado']); }
        if (!empty($f['tipo']))   { $partes[] = 'Tipo: ' . ($tipos[$f['tipo']] ?? $f['tipo']); }
        if (!empty($f['search'])) { $partes[] = 'Búsqueda: ' . $f['search']; }

        return $partes ? implode(' · ', $partes) : 'Todos los registros del año';
    }

    // ── GET /matriculas/crear ────────────────────────────────────
    public function create(): void
    {
        $anioActivo = $this->estudiantes->anioActivo();
        if (!$anioActivo) {
            $this->redirectWithError(url('matriculas'), 'No hay un año académico activo.');
        }

        // Búsqueda de estudiante por DNI (paso 1).
        $dni        = trim((string) $this->query('dni', ''));
        $estudiante = null;
        $yaMatriculado = false;
        $seccionSugerida = null;

        if ($dni !== '') {
            $estudiante = $this->model->estudiantePorDni($dni);
            if ($estudiante) {
                $yaMatriculado = $this->model->existeMatricula(
                    (int) $estudiante['estudiante_id'],
                    (int) $anioActivo['id']
                );
            }
        }

        $this->view('matriculas/crear', [
            'titulo'          => 'Nueva matrícula',
            'paso'            => 1,
            'anioActivo'      => $anioActivo,
            'grados'          => $this->model->listarGrados(),
            'secciones'       => $this->model->listarSecciones((int) $anioActivo['id']),
            'dni'             => $dni,
            'estudiante'      => $estudiante,
            'yaMatriculado'   => $yaMatriculado,
            'seccionSugerida' => $seccionSugerida,
            'page_scripts'    => ['matriculas'],
        ]);
    }

    // ── POST /matriculas/crear ───────────────────────────────────
    public function store(): void
    {
        $this->validateCsrf();

        $anioActivo = $this->estudiantes->anioActivo();
        if (!$anioActivo) {
            $this->redirectWithError(url('matriculas'), 'No hay un año académico activo.');
        }
        $anioId = (int) $anioActivo['id'];

        $dni          = trim((string) $this->input('dni', ''));
        $gradoId      = (int) $this->input('grado_id');
        $tipo         = in_array($this->input('tipo'), ['continuador', 'nuevo'], true)
            ? $this->input('tipo') : 'continuador';
        $serieRecibo  = trim((string) $this->input('serie_recibo', ''));
        $seccionPost  = (int) $this->input('seccion_id');
        $provisional  = (bool) $this->input('provisional');

        // ── Alta PROVISIONAL (estudiante sin DNI todavía) ────────────────
        // El padre matricula al hijo pero no dejó el DNI ni los documentos de
        // traslado. Se registra con un código provisional para que el docente
        // pueda calificarlo (la matrícula 'pendiente' aparece en su grilla); el
        // DNI real y los documentos se regularizan antes de activar. La serie
        // del recibo es OPCIONAL aquí (se exige antes de activar).
        if ($provisional) {
            if (!$gradoId) {
                $this->redirectWithError(url('matriculas/crear'),
                    'Selecciona el grado de destino.');
            }
            $apePat  = mb_strtoupper(trim((string) $this->input('apellido_paterno')));
            $nombres = mb_strtoupper(trim((string) $this->input('nombres')));
            if ($apePat === '' || $nombres === '') {
                $this->redirectWithError(url('matriculas/crear'),
                    'El registro provisional requiere al menos apellido paterno y nombres.');
            }

            $datosPersona = [
                'apellido_paterno' => $apePat,
                'apellido_materno' => mb_strtoupper(trim((string) $this->input('apellido_materno'))),
                'nombres'          => $nombres,
                'fecha_nacimiento' => $this->input('fecha_nacimiento') ?: null,
                'sexo'             => in_array($this->input('sexo'), ['M', 'F'], true)
                    ? $this->input('sexo') : null,
            ];
            try {
                $estudianteId = $this->model->crearEstudianteProvisional($datosPersona);
            } catch (\Exception $e) {
                log_error('Error al crear estudiante provisional', ['error' => $e->getMessage()]);
                $this->redirectWithError(url('matriculas/crear'),
                    'No se pudo registrar al estudiante provisional.');
            }

            $seccionId = $seccionPost ?: $this->resolverSeccion($estudianteId, $gradoId, $anioId, $tipo);

            $matriculaId = $this->model->crear([
                'estudiante_id'  => $estudianteId,
                'seccion_id'     => $seccionId,
                'anio_id'        => $anioId,
                'tipo'           => $tipo,
                'serie_recibo'   => $serieRecibo !== '' ? $serieRecibo : null,
                'registrado_por' => (int) (Session::user()['id'] ?? 0),
            ]);
            $this->model->update($matriculaId, [
                'motivo_estado' => 'Registro provisional — pendiente de DNI y documentos',
            ]);

            $this->redirectWithSuccess(
                url('matriculas/' . $matriculaId . '/apoderado'),
                'Matrícula provisional creada sin DNI. Regulariza el DNI real y los documentos antes de activarla.'
            );
        }

        // Serie del recibo obligatoria SIEMPRE.
        if ($serieRecibo === '') {
            $this->redirectWithError(url('matriculas/crear?dni=' . urlencode($dni)),
                'La serie del recibo es obligatoria.');
        }
        if ($dni === '' || !ctype_digit($dni) || strlen($dni) !== 8) {
            $this->redirectWithError(url('matriculas/crear'), 'DNI inválido (8 dígitos).');
        }
        if (!$gradoId) {
            $this->redirectWithError(url('matriculas/crear?dni=' . urlencode($dni)),
                'Selecciona el grado de destino.');
        }

        // 1) Estudiante: existente o nuevo.
        $existente = $this->model->estudiantePorDni($dni);
        if ($existente) {
            $estudianteId = (int) $existente['estudiante_id'];
        } else {
            $datosPersona = [
                'dni'              => $dni,
                'apellido_paterno' => mb_strtoupper(trim((string) $this->input('apellido_paterno'))),
                'apellido_materno' => mb_strtoupper(trim((string) $this->input('apellido_materno'))),
                'nombres'          => mb_strtoupper(trim((string) $this->input('nombres'))),
                'fecha_nacimiento' => $this->input('fecha_nacimiento') ?: null,
                'sexo'             => in_array($this->input('sexo'), ['M', 'F'], true)
                    ? $this->input('sexo') : null,
            ];
            if ($datosPersona['apellido_paterno'] === '' || $datosPersona['nombres'] === '') {
                $this->redirectWithError(url('matriculas/crear?dni=' . urlencode($dni)),
                    'Faltan datos del estudiante nuevo.');
            }
            try {
                $estudianteId = $this->model->crearEstudianteConPersona($datosPersona);
            } catch (\Exception $e) {
                log_error('Error al crear estudiante', ['error' => $e->getMessage()]);
                $this->redirectWithError(url('matriculas/crear'),
                    'No se pudo registrar al estudiante.');
            }
        }

        // 2) Evitar matrícula duplicada en el mismo año.
        if ($this->model->existeMatricula($estudianteId, $anioId)) {
            $this->redirectWithError(url('matriculas'),
                'El estudiante ya tiene una matrícula registrada este año.');
        }

        // 3) Sección: la posteada o la sugerida por el sistema.
        $seccionId = $seccionPost ?: $this->resolverSeccion($estudianteId, $gradoId, $anioId, $tipo);

        // 4) Crear matrícula (siempre 'pendiente').
        $matriculaId = $this->model->crear([
            'estudiante_id'  => $estudianteId,
            'seccion_id'     => $seccionId,
            'anio_id'        => $anioId,
            'tipo'           => $tipo,
            'serie_recibo'   => $serieRecibo,
            'registrado_por' => (int) (Session::user()['id'] ?? 0),
        ]);

        $this->redirectWithSuccess(
            url('matriculas/' . $matriculaId . '/apoderado'),
            'Matrícula creada en estado pendiente. Ahora vincula al apoderado.'
        );
    }

    /**
     * Resuelve la sección sugerida: continuador → misma letra del año
     * anterior; en otro caso (o si no se encuentra) → menos poblada.
     */
    private function resolverSeccion(int $estudianteId, int $gradoId, int $anioId, string $tipo): ?int
    {
        if ($tipo === 'continuador') {
            $anterior = $this->model->seccionAnioAnterior($estudianteId, (int) date('Y'));
            if ($anterior) {
                foreach ($this->model->listarSecciones($anioId, $gradoId) as $s) {
                    if ($s['nombre'] === $anterior['nombre']) {
                        return (int) $s['id'];
                    }
                }
            }
        }
        $sug = $this->model->sugerirSeccion($gradoId, $anioId);
        return $sug ? (int) $sug['id'] : null;
    }

    // ── GET /matriculas/{id}/apoderado ───────────────────────────
    public function apoderado(string $id): void
    {
        $matricula = $this->requireMatricula((int) $id);

        $dni       = trim((string) $this->query('dni', ''));
        $apoderado = $dni !== '' ? $this->apoderados->buscarPorDni($dni) : null;

        $this->view('matriculas/apoderado', [
            'titulo'        => 'Matrícula — Apoderado',
            'paso'          => 2,
            'matricula'     => $matricula,
            'tiposVinculo'  => self::TIPOS_VINCULO,
            'vinculos'      => $this->apoderados->getVinculos((int) $matricula['estudiante_id']),
            'dni'           => $dni,
            'apoderado'     => $apoderado,
        ]);
    }

    // ── POST /matriculas/{id}/apoderado ──────────────────────────
    public function storeApoderado(string $id): void
    {
        $this->validateCsrf();
        $matricula    = $this->requireMatricula((int) $id);
        $estudianteId = (int) $matricula['estudiante_id'];
        $anioId       = (int) $matricula['anio_id'];

        $tipoVinculo  = $this->input('tipo_vinculo');
        if (!array_key_exists($tipoVinculo, self::TIPOS_VINCULO)) {
            $this->redirectWithError(url('matriculas/' . $id . '/apoderado'),
                'Tipo de vínculo inválido.');
        }
        $esResponsable = (bool) $this->input('es_responsable');

        // Máximo 3 apoderados por estudiante (salvo que se actualice un tipo ya existente).
        $vinculos = $this->apoderados->getVinculos($estudianteId);
        $tiposActuales = array_column($vinculos, 'tipo_vinculo');
        if (count($vinculos) >= 3 && !in_array($tipoVinculo, $tiposActuales, true)) {
            $this->redirectWithError(url('matriculas/' . $id . '/apoderado'),
                'Un estudiante admite máximo 3 apoderados.');
        }

        // Apoderado existente (por id) o nuevo.
        $apoderadoId = (int) $this->input('apoderado_id');
        if (!$apoderadoId) {
            $dni = trim((string) $this->input('dni', ''));
            if ($dni === '' || !ctype_digit($dni) || strlen($dni) !== 8) {
                $this->redirectWithError(url('matriculas/' . $id . '/apoderado'),
                    'DNI del apoderado inválido (8 dígitos).');
            }
            try {
                $apoderadoId = $this->apoderados->crear([
                    'dni'              => $dni,
                    'apellido_paterno' => mb_strtoupper(trim((string) $this->input('apellido_paterno'))),
                    'apellido_materno' => mb_strtoupper(trim((string) $this->input('apellido_materno'))),
                    'nombres'          => mb_strtoupper(trim((string) $this->input('nombres'))),
                    'telefono'         => trim((string) $this->input('telefono')) ?: null,
                    'correo'           => trim((string) $this->input('correo')) ?: null,
                ]);
            } catch (\Exception $e) {
                log_error('Error al crear apoderado', ['error' => $e->getMessage()]);
                $this->redirectWithError(url('matriculas/' . $id . '/apoderado'),
                    'No se pudo registrar al apoderado.');
            }
        }

        // Máximo 3 estudiantes activos por apoderado en el año.
        if ($this->apoderados->contarHijosActivos($apoderadoId, $anioId) >= 3
            && !$this->yaVinculado($vinculos, $apoderadoId)) {
            $this->redirectWithError(url('matriculas/' . $id . '/apoderado'),
                'Este apoderado ya tiene 3 estudiantes vinculados este año.');
        }

        $this->apoderados->vincularEstudiante($apoderadoId, $estudianteId, $tipoVinculo, $esResponsable);

        // "Agregar otro" vuelve al paso 2; "continuar" pasa a documentos.
        if ($this->input('accion') === 'agregar_otro') {
            $this->redirectWithSuccess(url('matriculas/' . $id . '/apoderado'),
                'Apoderado vinculado. Puedes agregar otro.');
        }
        $this->redirectWithSuccess(url('matriculas/' . $id . '/documentos'),
            'Apoderado vinculado. Ahora registra los documentos.');
    }

    private function yaVinculado(array $vinculos, int $apoderadoId): bool
    {
        foreach ($vinculos as $v) {
            if ((int) $v['apoderado_id'] === $apoderadoId) {
                return true;
            }
        }
        return false;
    }

    // ── GET /matriculas/{id}/documentos ──────────────────────────
    public function documentos(string $id): void
    {
        $matricula = $this->requireMatricula((int) $id);
        $requeridos = $matricula['tipo'] === 'nuevo' ? self::DOCS_NUEVO : self::DOCS_CONTINUADOR;

        // Mapea el estado actual de cada documento ya registrado.
        $actuales = [];
        foreach ($this->model->getDocumentos((int) $id) as $d) {
            $actuales[$d['tipo_documento']] = $d;
        }

        // Marcado de obligatoriedad para activar (el resto es opcional/ideal).
        $obligatorios = $matricula['tipo'] === 'nuevo'
            ? self::DOCS_OBLIGATORIOS_NUEVO
            : array_keys(self::DOCS_CONTINUADOR);

        $this->view('matriculas/documentos', [
            'titulo'       => 'Matrícula — Documentos',
            'paso'         => 3,
            'matricula'    => $matricula,
            'requeridos'   => $requeridos,
            'actuales'     => $actuales,
            'obligatorios' => $obligatorios,
            'grupoDni'     => $matricula['tipo'] === 'nuevo' ? self::DOCS_DNI_APODERADO : [],
        ]);
    }

    // ── POST /matriculas/{id}/documentos ─────────────────────────
    public function storeDocumentos(string $id): void
    {
        $this->validateCsrf();
        $matricula  = $this->requireMatricula((int) $id);
        $usuarioId  = (int) (Session::user()['id'] ?? 0);
        $requeridos = $matricula['tipo'] === 'nuevo' ? self::DOCS_NUEVO : self::DOCS_CONTINUADOR;

        // Serie de recibo obligatoria — se actualiza siempre.
        $serie = trim((string) $this->input('serie_recibo', ''));
        if ($serie === '') {
            $this->redirectWithError(url('matriculas/' . $id . '/documentos'),
                'La serie del recibo es obligatoria.');
        }
        $this->model->update((int) $id, ['serie_recibo' => $serie]);

        $entregados = (array) ($this->input('entregado', []));
        $observaciones = (array) ($this->input('observacion', []));

        foreach ($requeridos as $tipo => $label) {
            $this->model->registrarDocumento(
                (int) $id,
                $tipo,
                isset($entregados[$tipo]),
                $usuarioId,
                trim((string) ($observaciones[$tipo] ?? '')) ?: null
            );
        }

        // Mantener el estado correcto: una matrícula 'aprobada' que quedó
        // incompleta (p.ej. se desmarcó un documento) vuelve a 'pendiente'
        // anotando los requisitos faltantes como motivo visible.
        $actual = $this->requireMatricula((int) $id);
        $faltan = $this->pendientesParaActivar($actual);
        if ($actual['estado'] === 'aprobada' && !empty($faltan)) {
            $this->model->cambiarEstado((int) $id, 'pendiente', $usuarioId,
                'Faltan requisitos: ' . implode('; ', $faltan));
            $this->redirectWithSuccess(url('matriculas/' . $id),
                'Documentos actualizados. La matrícula volvió a PENDIENTE porque aún faltan requisitos.');
        }

        $this->redirectWithSuccess(url('matriculas/' . $id),
            'Documentos registrados correctamente.');
    }

    // ── GET /matriculas/{id} ─────────────────────────────────────
    public function show(string $id): void
    {
        $matricula  = $this->requireMatricula((int) $id);

        // Si es la matrícula OPERATIVA de un retorno activo, su detalle no es
        // accesible: es solo informativa. Toda la gestión vive en la oficial.
        $oficialId = $this->model->oficialSiEsOperativaEnRetornoActivo((int) $id);
        if ($oficialId !== null) {
            $this->redirectWithError(url('matriculas/' . $oficialId),
                'Esa es la matrícula operativa de un retorno de grado (solo informativa). La gestión se hace desde la matrícula oficial.');
        }

        $retorno    = $this->model->queryOne("
            SELECT r.*, g.nombre_display AS grado_destino
            FROM retornos_grado r
            INNER JOIN matriculas mo ON mo.id = r.matricula_operativa_id
            LEFT  JOIN secciones s   ON s.id = mo.seccion_id
            LEFT  JOIN grados g      ON g.id = s.grado_id
            WHERE r.matricula_oficial_id = ?
            LIMIT 1
        ", [(int) $id]);

        // Boleta INTERNA de gestion: la vista enlaza por id a
        // Boleta\BoletaController (mismo flujo que la del docente, muestra
        // BORRADOR mientras el bimestre no cierra), autenticada por rol. El
        // enlace publico por token (solo oficial) es otro camino, aparte.

        $this->view('matriculas/show', [
            'titulo'       => 'Detalle de matrícula',
            'matricula'    => $matricula,
            'vinculos'     => $this->apoderados->getVinculos((int) $matricula['estudiante_id']),
            'documentos'   => $this->model->getDocumentos((int) $id),
            'notasExternas'=> $this->model->getNotasExternas((int) $id),
            'tiposVinculo' => self::TIPOS_VINCULO,
            'retorno'      => $retorno,
            'traslado'     => $this->traslados->getUltimaPorMatricula((int) $id),
            'puedeGestionar' => has_role(['admin', 'registro_academico']),
            'pendientes'   => $this->pendientesParaActivar($matricula),
            'page_scripts' => ['matriculas'],
        ]);
    }

    // ── POST /matriculas/{id}/estudiante ─────────────────────────
    /**
     * Actualiza los DATOS PERSONALES del estudiante (tabla personas, compartida
     * por todos sus años). NO toca grado, sección ni estado de la matrícula
     * (eso se gestiona por Retorno/Traslado y por el flujo de estado). Solo
     * admin y registro_academico. Ante error: NO se conservan los cambios; el
     * detalle se recarga con el último registro guardado.
     */
    public function actualizarEstudiante(string $id): void
    {
        $this->requireRole(['admin', 'registro_academico']);
        $this->validateCsrf();

        $id        = (int) $id;
        $matricula = $this->model->findById($id);
        if (!$matricula) {
            $this->redirectWithError(url('matriculas'), 'Matrícula no encontrada.');
        }
        $volver    = url('matriculas/' . $id);
        $personaId = (int) $matricula['persona_id'];

        $dni      = trim((string) $this->input('dni', ''));
        $apePat   = trim((string) $this->input('apellido_paterno', ''));
        $apeMat   = trim((string) $this->input('apellido_materno', ''));
        $nombres  = trim((string) $this->input('nombres', ''));

        if ($apePat === '' || $apeMat === '' || $nombres === '') {
            $this->redirectWithError($volver, 'Apellidos y nombres son obligatorios.');
        }
        if (!ctype_digit($dni) || strlen($dni) !== 8) {
            $this->redirectWithError($volver, 'DNI inválido (8 dígitos).');
        }
        if ($this->model->dniEnUsoPorOtra($dni, $personaId)) {
            $this->redirectWithError($volver,
                'Ese DNI ya pertenece a otra persona registrada. Verifica si el estudiante '
                . 'ya existía en el sistema; la fusión de registros debe hacerse manualmente.');
        }

        $datos = [
            'dni'              => $dni,
            'apellido_paterno' => mb_strtoupper($apePat),
            'apellido_materno' => mb_strtoupper($apeMat),
            'nombres'          => mb_strtoupper($nombres),
            'fecha_nacimiento' => $this->input('fecha_nacimiento') ?: null,
            'sexo'             => in_array($this->input('sexo'), ['M', 'F'], true)
                ? $this->input('sexo') : null,
        ];

        try {
            $this->model->actualizarDatosPersonales($personaId, $datos);
        } catch (\Exception $e) {
            log_error('Error actualizando datos del estudiante', ['id' => $id, 'error' => $e->getMessage()]);
            $this->redirectWithError($volver, 'No se pudieron guardar los cambios. Intenta de nuevo.');
        }

        $this->redirectWithSuccess($volver, 'Datos del estudiante actualizados.');
    }

    /**
     * Lista los requisitos pendientes para que una matrícula pueda quedar 'activo'.
     * Una matrícula solo se considera COMPLETA cuando tiene al menos un apoderado
     * vinculado, serie de recibo y todos los documentos requeridos (según su tipo)
     * marcados como entregados. Devuelve etiquetas legibles; arreglo vacío = completa.
     */
    private function pendientesParaActivar(array $matricula): array
    {
        $faltan = [];
        $id     = (int) $matricula['id'];

        // 0) DNI real: un alta provisional (código 'P…') no puede activarse hasta
        //    reemplazar el código por el DNI real del estudiante. Esto impide que
        //    un DNI falso llegue a boletas / orden de mérito.
        if (es_dni_provisional($matricula['dni'] ?? null)) {
            $faltan[] = 'Reemplazar el DNI provisional por el DNI real del estudiante';
        }

        // 1) Al menos un apoderado vinculado.
        if (empty($this->apoderados->getVinculos((int) $matricula['estudiante_id']))) {
            $faltan[] = 'Vincular al menos un apoderado';
        }

        // 2) Serie del recibo.
        if (trim((string) ($matricula['serie_recibo'] ?? '')) === '') {
            $faltan[] = 'Registrar la serie del recibo';
        }

        // 3) Documentos obligatorios entregados. No se exige TODA la lista de
        //    DOCS_NUEVO (el resto es ideal pero opcional): solo el subconjunto
        //    obligatorio + al menos un DNI de apoderado del grupo flexible.
        $entregados = [];
        foreach ($this->model->getDocumentos($id) as $d) {
            $entregados[$d['tipo_documento']] = (int) $d['entregado'] === 1;
        }

        if ($matricula['tipo'] === 'nuevo') {
            foreach (self::DOCS_OBLIGATORIOS_NUEVO as $tipo) {
                if (empty($entregados[$tipo])) {
                    $faltan[] = 'Documento: ' . self::DOCS_NUEVO[$tipo];
                }
            }
            // Grupo "al menos uno": DNI del padre, de la madre o del apoderado.
            $tieneDniApoderado = false;
            foreach (self::DOCS_DNI_APODERADO as $tipo) {
                if (!empty($entregados[$tipo])) {
                    $tieneDniApoderado = true;
                    break;
                }
            }
            if (!$tieneDniApoderado) {
                $faltan[] = 'Documento: DNI del padre, de la madre o del apoderado (al menos uno)';
            }
        } else {
            foreach (self::DOCS_CONTINUADOR as $tipo => $label) {
                if (empty($entregados[$tipo])) {
                    $faltan[] = 'Documento: ' . $label;
                }
            }
        }

        return $faltan;
    }

    // ── POST /matriculas/{id}/activar ────────────────────────────
    public function activar(string $id): void
    {
        $this->validateCsrf();
        $this->requireRole(['admin', 'registro_academico']);
        $matricula = $this->requireMatricula((int) $id);

        // Solo se activa una matrícula COMPLETA (sin nada pendiente).
        $faltan = $this->pendientesParaActivar($matricula);
        if (!empty($faltan)) {
            $this->redirectWithError(url('matriculas/' . $id),
                'No se puede activar: la matrícula está incompleta. Pendiente — '
                . implode('; ', $faltan) . '.');
        }

        // Activar = estado 'aprobada' (único estado vigente). El motivo se limpia
        // (null) porque ya no hay pendientes que reportar.
        $this->model->cambiarEstado((int) $id, 'aprobada', (int) (Session::user()['id'] ?? 0), null);

        // Reactivar las boletas públicas que se apagaron al desactivar/trasladar,
        // para que la matrícula reactivada vuelva a exponer su boleta.
        $this->model->execute(
            "UPDATE boletas_publicas SET activa = 1 WHERE matricula_id = ?",
            [(int) $id]
        );

        // Si la matrícula venía de una desactivación quedó marcada 'trasladado'.
        // Al reactivar se restaura el ORIGEN real preservado en `tipo_anterior`
        // (reversibilidad 100%), de modo que vuelva a su tipo nuevo/continuador
        // y reaparezca en calificaciones. Fallback a 'continuador' solo si no
        // hubiera respaldo (datos previos a esta lógica).
        if (($matricula['tipo'] ?? '') === 'trasladado') {
            $original = $matricula['tipo_anterior'] ?? null;
            $this->model->update((int) $id, [
                'tipo'          => in_array($original, ['continuador', 'nuevo'], true)
                    ? $original : 'continuador',
                'tipo_anterior' => null,
            ]);
        }

        $this->redirectWithSuccess(url('matriculas/' . $id), 'Matrícula activada.');
    }

    // ── POST /matriculas/{id}/desactivar ─────────────────────────
    // Baja administrativa: apaga la matrícula PERO conserva su tipo
    // (continuador/nuevo). Reversible al reactivar. El traslado de salida (con
    // constancia + tipo='trasladado') vive en TrasladoController.
    public function desactivar(string $id): void
    {
        $this->validateCsrf();
        $this->requireRole(['admin', 'registro_academico']);
        $matricula = $this->requireMatricula((int) $id);
        $usuarioId = (int) (Session::user()['id'] ?? 0);

        // El motivo de la desactivación es OBLIGATORIO (queda visible junto al
        // estado). Sin él no se procesa la baja.
        $motivo = trim((string) $this->input('motivo'));
        if ($motivo === '') {
            $this->redirectWithError(url('matriculas/' . $id),
                'Debes indicar el motivo de la desactivación.');
        }

        $this->model->beginTransaction();
        try {
            // estado=desactivado conservando el tipo; apaga login del apoderado
            // y códigos de boleta pública del periodo activo.
            $this->model->cambiarEstado((int) $id, 'desactivado', $usuarioId, $motivo);
            $this->apoderados->desactivarUsuarioDeEstudiante((int) $matricula['estudiante_id']);

            // Apaga las boletas públicas de TODOS los periodos de la matrícula,
            // no solo el activo: un alumno desactivado no debe exponer ninguna
            // boleta (la baja puede ocurrir en un bimestre posterior al de la
            // boleta ya generada e impresa).
            $this->model->execute(
                "UPDATE boletas_publicas SET activa = 0 WHERE matricula_id = ?",
                [(int) $id]
            );

            $this->model->commit();
        } catch (\Exception $e) {
            $this->model->rollback();
            log_error('Error al desactivar matrícula', ['id' => $id, 'error' => $e->getMessage()]);
            $this->redirectWithError(url('matriculas/' . $id), 'No se pudo desactivar la matrícula.');
        }

        $this->redirectWithSuccess(url('matriculas/' . $id), 'Matrícula desactivada.');
    }

    // ── GET /matriculas/{id}/notas-externas ──────────────────────
    public function notasExternas(string $id): void
    {
        $matricula = $this->requireMatricula((int) $id);
        if ($matricula['tipo'] !== 'nuevo') {
            $this->redirectWithError(url('matriculas/' . $id),
                'Las notas externas solo aplican a traslados de entrada (tipo nuevo).');
        }

        $this->view('matriculas/notas-externas', [
            'titulo'    => 'Notas externas (traslado)',
            'matricula' => $matricula,
            'notas'     => $this->model->getNotasExternas((int) $id),
        ]);
    }

    // ── POST /matriculas/{id}/notas-externas ─────────────────────
    public function storeNotasExternas(string $id): void
    {
        $this->validateCsrf();
        $matricula = $this->requireMatricula((int) $id);

        $area    = trim((string) $this->input('area_nombre'));
        $comp    = trim((string) $this->input('competencia_nombre'));
        $periodo = trim((string) $this->input('periodo_nombre'));
        $literal = $this->input('nota_literal');

        if ($area === '' || $comp === '' || $periodo === ''
            || !in_array($literal, ['AD', 'A', 'B', 'C'], true)) {
            $this->redirectWithError(url('matriculas/' . $id . '/notas-externas'),
                'Completa área, competencia, periodo y nota literal válida.');
        }

        $this->model->registrarNotaExterna([
            'matricula_id'       => (int) $id,
            'periodo_nombre'     => $periodo,
            'competencia_nombre' => $comp,
            'area_nombre'        => $area,
            'nota_literal'       => $literal,
            'colegio_origen'     => trim((string) $this->input('colegio_origen')) ?: null,
            'registrado_por'     => (int) (Session::user()['id'] ?? 0),
        ]);

        $this->redirectWithSuccess(url('matriculas/' . $id . '/notas-externas'),
            'Nota externa registrada.');
    }

    /** Carga la matrícula o muestra 404 si no existe. */
    private function requireMatricula(int $id): array
    {
        $matricula = $this->model->findById($id);
        if (!$matricula) {
            http_response_code(404);
            $this->view('shared/404');
            exit;
        }
        return $matricula;
    }
}
