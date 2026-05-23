<?php

namespace App\Controllers\Director;

use App\Controllers\BaseController;
use App\Models\CargaAcademicaModel;

class CargaAcademicaController extends BaseController
{
    private CargaAcademicaModel $model;

    private const DIAS  = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    private const ROLES = ['admin', 'director_general', 'director_ebr', 'registro_academico'];

    public function __construct()
    {
        $this->requireRole(self::ROLES);
        $this->model = new CargaAcademicaModel();
    }

    // GET /director/cargas
    public function index(): void
    {
        $secciones = $this->model->listarSeccionesConCargas();
        $porNivel  = [];
        foreach ($secciones as $s) {
            $porNivel[$s['nivel_nombre']][] = $s;
        }

        $this->view('director/cargas/index', [
            'titulo'   => 'Cargas Académicas',
            'porNivel' => $porNivel,
        ]);
    }

    // GET /director/cargas/seccion/{seccion_id}
    public function porSeccion(string $seccionId): void
    {
        $seccionId = (int) $seccionId;
        $seccion   = $this->model->findSeccion($seccionId);

        if (!$seccion) {
            $this->redirectWithError(url('director/cargas'), 'Sección no encontrada.');
        }

        $cargas = $this->model->listarPorSeccion($seccionId);

        $this->view('director/cargas/seccion', [
            'titulo'  => 'Cargas — ' . $seccion['grado_nombre'] . ' ' . $seccion['seccion_nombre'],
            'seccion' => $seccion,
            'cargas'  => $cargas,
        ]);
    }

    // GET /director/cargas/crear
    public function create(): void
    {
        $preselSeccionId = (int) ($_GET['seccion_id'] ?? 0);
        $preselDocenteId = 0;

        if ($preselSeccionId > 0) {
            $seccion = $this->model->findSeccion($preselSeccionId);
            if ($seccion && $seccion['es_unidocente'] && $seccion['tutor_id']) {
                $preselDocenteId = (int) $seccion['tutor_id'];
            }
        }

        $this->view('director/cargas/crear', $this->datosFormulario() + [
            'titulo'          => 'Nueva Carga Académica',
            'page_scripts'    => ['cargas'],
            'preselSeccionId' => $preselSeccionId,
            'preselDocenteId' => $preselDocenteId,
        ]);
    }

    // POST /director/cargas/crear
    public function store(): void
    {
        $this->validateCsrf();

        [$datosCarga, $sesiones, $error] = $this->procesarFormulario();

        if ($error) {
            $this->redirectWithError(url('director/cargas/crear'), $error);
        }

        if ($this->model->existeCarga(
            $datosCarga['seccion_id'],
            $datosCarga['subarea_id'],
            $datosCarga['area_id']
        )) {
            $this->redirectWithError(
                url('director/cargas/crear'),
                'Ya existe una carga para esta sección con el área/subárea seleccionada.'
            );
        }

        $conflictos = $this->model->verificarConflictos(
            array_column($sesiones, 'bloque_id'),
            $datosCarga['seccion_id'],
            $datosCarga['docente_id']
        );
        if ($conflictos) {
            $this->redirectWithError(url('director/cargas/crear'), $this->msgConflicto($conflictos[0]));
        }

        try {
            $this->model->crearConHorario($datosCarga, $sesiones);
        } catch (\Exception $e) {
            log_error('Error creando carga', ['msg' => $e->getMessage()]);
            $this->redirectWithError(
                url('director/cargas/crear'),
                'Error al registrar la carga. Verifica que no haya conflictos de horario.'
            );
        }

        $this->redirectWithSuccess(
            url('director/cargas/seccion/' . $datosCarga['seccion_id']),
            'Carga académica registrada correctamente.'
        );
    }

    // GET /director/cargas/{id}/editar
    public function edit(string $id): void
    {
        $carga = $this->model->findById((int) $id);
        if (!$carga) {
            $this->redirectWithError(url('director/cargas'), 'Carga no encontrada.');
        }

        $sesiones    = $this->model->getSesionesDeCarga((int) $id);
        $sesionesMap = [];
        foreach ($sesiones as $s) {
            $sesionesMap[$s['dia_semana']] = [
                'hora_inicio' => $s['hora_inicio'],
                'hora_fin'    => $s['hora_fin'],
            ];
        }

        $this->view('director/cargas/editar', $this->datosFormulario($id) + [
            'titulo'       => 'Editar Carga Académica',
            'carga'        => $carga,
            'sesionesMap'  => $sesionesMap,
            'page_scripts' => ['cargas'],
        ]);
    }

    // POST /director/cargas/{id}/editar
    public function update(string $id): void
    {
        $this->validateCsrf();
        $id = (int) $id;

        $carga = $this->model->findById($id);
        if (!$carga) {
            $this->redirectWithError(url('director/cargas'), 'Carga no encontrada.');
        }

        [$datosCarga, $sesiones, $error] = $this->procesarFormulario();

        if ($error) {
            $this->redirectWithError(url("director/cargas/{$id}/editar"), $error);
        }

        if ($this->model->existeCarga(
            $datosCarga['seccion_id'],
            $datosCarga['subarea_id'],
            $datosCarga['area_id'],
            $id
        )) {
            $this->redirectWithError(
                url("director/cargas/{$id}/editar"),
                'Ya existe otra carga para esta sección con el área/subárea seleccionada.'
            );
        }

        $conflictos = $this->model->verificarConflictos(
            array_column($sesiones, 'bloque_id'),
            $datosCarga['seccion_id'],
            $datosCarga['docente_id'],
            $id
        );
        if ($conflictos) {
            $this->redirectWithError(
                url("director/cargas/{$id}/editar"),
                $this->msgConflicto($conflictos[0])
            );
        }

        try {
            $this->model->actualizarConHorario($id, $datosCarga, $sesiones);
        } catch (\Exception $e) {
            log_error('Error actualizando carga', ['id' => $id, 'msg' => $e->getMessage()]);
            $this->redirectWithError(
                url("director/cargas/{$id}/editar"),
                'Error al actualizar la carga académica.'
            );
        }

        $this->redirectWithSuccess(url('director/cargas'), 'Carga académica actualizada correctamente.');
    }

    // POST /director/cargas/{id}/estado
    public function toggleEstado(string $id): void
    {
        $this->validateCsrf();
        $id = (int) $id;

        $carga = $this->model->findById($id);
        if (!$carga) {
            $this->redirectWithError(url('director/cargas'), 'Carga no encontrada.');
        }

        $this->model->toggleEstado($id);
        $nuevo = $carga['estado'] === 'activa' ? 'desactivada' : 'activada';
        $this->redirectWithSuccess(url('director/cargas'), "Carga {$nuevo} correctamente.");
    }

    // ── Métodos privados ──────────────────────────────────────

    private function datosFormulario(int $excludeCargaId = 0): array
    {
        // area_id y subarea_id ya ocupados por seccion en el año activo.
        // Se excluye la carga actual al editar (excludeCargaId > 0).
        $rawOcupadas = $this->model->query("
            SELECT ca.seccion_id, ca.area_id, ca.subarea_id
            FROM cargas_academicas ca
            INNER JOIN secciones s        ON s.id  = ca.seccion_id
            INNER JOIN anios_academicos a ON a.id  = s.anio_id
            WHERE ca.estado  = 'activa'
              AND a.estado   IN ('planificado', 'activo')
              AND ca.id      != ?
        ", [$excludeCargaId]);

        $ocupadas = [];
        foreach ($rawOcupadas as $c) {
            $sid = (int) $c['seccion_id'];
            if (!isset($ocupadas[$sid])) {
                $ocupadas[$sid] = ['areas' => [], 'subareas' => []];
            }
            if ($c['area_id'] !== null) {
                $ocupadas[$sid]['areas'][] = (int) $c['area_id'];
            }
            if ($c['subarea_id'] !== null) {
                $ocupadas[$sid]['subareas'][] = (int) $c['subarea_id'];
            }
        }

        $rawHorarios = $this->model->query("
            SELECT ca.seccion_id, bh.dia_semana,
                   MAX(bh.hora_fin) AS ultima_fin
            FROM cargas_academicas ca
            INNER JOIN sesiones_horario sh ON sh.carga_id = ca.id
            INNER JOIN bloques_horario bh  ON bh.id       = sh.bloque_id
            INNER JOIN secciones s         ON s.id        = ca.seccion_id
            INNER JOIN anios_academicos a  ON a.id        = s.anio_id
            WHERE ca.estado = 'activa'
              AND a.estado  IN ('planificado', 'activo')
              AND ca.id     != ?
            GROUP BY ca.seccion_id, bh.dia_semana
        ", [$excludeCargaId]);

        $horarios = [];
        foreach ($rawHorarios as $h) {
            $sid = (int) $h['seccion_id'];
            $horarios[$sid][$h['dia_semana']] = substr($h['ultima_fin'], 0, 5);
        }

        $rawBloques = $this->model->query("
            SELECT ca.docente_id, bh.dia_semana,
                   bh.hora_inicio, bh.hora_fin
            FROM cargas_academicas ca
            INNER JOIN sesiones_horario sh ON sh.carga_id = ca.id
            INNER JOIN bloques_horario bh  ON bh.id       = sh.bloque_id
            INNER JOIN secciones s         ON s.id        = ca.seccion_id
            INNER JOIN anios_academicos a  ON a.id        = s.anio_id
            WHERE ca.estado = 'activa'
              AND a.estado  IN ('planificado', 'activo')
              AND ca.id     != ?
            ORDER BY ca.docente_id, bh.dia_semana, bh.hora_inicio
        ", [$excludeCargaId]);

        $bloquesDocentes = [];
        foreach ($rawBloques as $b) {
            $did   = (int) $b['docente_id'];
            $dia   = $b['dia_semana'];
            $rango = substr($b['hora_inicio'], 0, 5) . '-' . substr($b['hora_fin'], 0, 5);
            $bloquesDocentes[$did][$dia][] = $rango;
        }

        return [
            'secciones'       => $this->model->listarSecciones(),
            'docentes'        => $this->model->listarDocentes(),
            'areas'           => $this->model->listarAreas(),
            'subareas'        => $this->model->listarSubareas(),
            'dias'            => self::DIAS,
            'ocupadas'        => $ocupadas,
            'horarios'        => $horarios,
            'bloquesDocentes' => $bloquesDocentes,
        ];
    }

    /**
     * Lee el POST, valida, resuelve bloques y devuelve [datosCarga, sesiones, error].
     */
    private function procesarFormulario(): array
    {
        $seccionId      = (int) $this->input('seccion_id', 0);
        $docenteId      = (int) $this->input('docente_id', 0);
        $areaId         = (int) $this->input('area_id', 0);
        $subareaId      = (int) $this->input('subarea_id', 0);
        $diasSeleccionados = $_POST['dias_check'] ?? [];
        $horasInicio    = $_POST['hora_inicio']  ?? [];
        $horasFin       = $_POST['hora_fin']     ?? [];

        if ($seccionId <= 0) return [null, null, 'Debes seleccionar una sección.'];
        if ($docenteId <= 0) return [null, null, 'Debes seleccionar un docente.'];
        if ($areaId    <= 0) return [null, null, 'Debes seleccionar un área.'];

        $area = $this->model->queryOne("SELECT id, tipo FROM areas WHERE id = ? LIMIT 1", [$areaId]);
        if (!$area) return [null, null, 'Área no válida.'];

        $vincSubareaId = null;
        $vincAreaId    = null;

        if ($area['tipo'] === 'con_subareas') {
            if ($subareaId <= 0) return [null, null, 'El área seleccionada requiere elegir una subárea.'];
            $vincSubareaId = $subareaId;
        } else {
            $vincAreaId = $areaId;
        }

        $seccion = $this->model->queryOne(
            "SELECT anio_id FROM secciones WHERE id = ? LIMIT 1",
            [$seccionId]
        );
        if (!$seccion) return [null, null, 'Sección no válida.'];
        $anioId = (int) $seccion['anio_id'];

        if (empty($diasSeleccionados)) {
            return [null, null, 'Debes seleccionar al menos un día con horario.'];
        }

        $sesiones     = [];
        $minutosTotales = 0;
        $configId     = null;

        foreach (self::DIAS as $dia) {
            if (!in_array($dia, $diasSeleccionados, true)) continue;

            $horaInicio = trim($horasInicio[$dia] ?? '');
            $horaFin    = trim($horasFin[$dia]    ?? '');

            if ($horaInicio === '' || $horaFin === '') {
                return [null, null, "Falta hora de inicio o fin para el día " . ucfirst($dia) . "."];
            }
            if ($horaFin <= $horaInicio) {
                return [null, null, "La hora de fin debe ser mayor a la de inicio (" . ucfirst($dia) . ")."];
            }

            [$h1, $m1] = array_map('intval', explode(':', $horaInicio));
            [$h2, $m2] = array_map('intval', explode(':', $horaFin));
            $minutosTotales += ($h2 * 60 + $m2) - ($h1 * 60 + $m1);

            if ($configId === null) {
                $configId = $this->model->getOrCreateConfiguracion($anioId);
            }

            $bloqueId = $this->model->getOrCreateBloque($configId, $dia, $horaInicio, $horaFin);

            $sesiones[] = [
                'bloque_id'  => $bloqueId,
                'seccion_id' => $seccionId,
                'docente_id' => $docenteId,
            ];
        }

        if (empty($sesiones)) {
            return [null, null, 'Debes seleccionar al menos un día con horario.'];
        }

        $datosCarga = [
            'docente_id'      => $docenteId,
            'seccion_id'      => $seccionId,
            'anio_id'         => $anioId,
            'subarea_id'      => $vincSubareaId,
            'area_id'         => $vincAreaId,
            'horas_semanales' => (int) round($minutosTotales / 60),
        ];

        return [$datosCarga, $sesiones, null];
    }

    private function msgConflicto(array $c): string
    {
        $dia = ucfirst($c['dia_semana']);
        if ($c['tipo_conflicto'] === 'seccion') {
            return "Conflicto de horario: la sección ya tiene una clase el {$dia} de {$c['hora_inicio']} a {$c['hora_fin']}.";
        }
        return "Conflicto de horario: el docente ya tiene otra clase el {$dia} de {$c['hora_inicio']} a {$c['hora_fin']}.";
    }
}
