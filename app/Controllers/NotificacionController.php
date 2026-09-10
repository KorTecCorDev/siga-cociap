<?php

namespace App\Controllers;

use App\Models\NotificacionModel;
use App\Models\SeccionModel;
use Core\Session;

/**
 * NotificacionController
 * Bandeja interna de notificaciones (migración 058).
 *
 * DOS SUPERFICIES:
 *   - Bandeja: la lee cualquier usuario autenticado; solo ve LAS SUYAS
 *     (el modelo filtra siempre por usuario_id, también al marcar leída).
 *   - Comunicados: los redacta admin / registro académico.
 *
 * ⚠️ El gate de escritura va POR MÉTODO, no en el constructor: los tres roles
 * de ROLES_DIRECCION son SOLO LECTURA y tienen que poder ENTRAR a su bandeja,
 * pero nunca redactar. Mismo criterio que el resto del sistema.
 */
class NotificacionController extends BaseController
{
    private NotificacionModel $model;

    public function __construct()
    {
        $this->requireAuth();
        $this->model = new NotificacionModel();
    }

    /** GET /notificaciones — bandeja del usuario en sesión. */
    public function index(): void
    {
        $uid = (int) (Session::user()['id'] ?? 0);

        $this->view('notificaciones/index', [
            'titulo'        => 'Notificaciones',
            'notificaciones' => $this->model->paraUsuario($uid),
            'noLeidas'      => $this->model->contarNoLeidas($uid),
            'puedeEmitir'   => has_role(NotificacionModel::ROLES_EMISORES),
            'page_scripts'  => ['notificaciones'],
        ]);
    }

    /**
     * POST /notificaciones/leer — marca UNA como leída.
     * Responde JSON: la bandeja la marca sin recargar.
     */
    public function leer(): void
    {
        $this->validateCsrf();

        $uid = (int) (Session::user()['id'] ?? 0);
        $id  = (int) $this->input('id');

        $this->json([
            'success'  => $this->model->marcarLeida($id, $uid),
            'noLeidas' => $this->model->contarNoLeidas($uid),
        ]);
    }

    /** POST /notificaciones/leer-todas — marca todas las del usuario. */
    public function leerTodas(): void
    {
        $this->validateCsrf();

        $uid = (int) (Session::user()['id'] ?? 0);
        $this->model->marcarTodasLeidas($uid);

        $this->redirectWithSuccess(url('notificaciones'), 'Notificaciones marcadas como leídas.');
    }

    /** GET /notificaciones/comunicado — formulario de comunicado. */
    public function comunicado(): void
    {
        $this->requireRole(NotificacionModel::ROLES_EMISORES);

        $secciones = (new SeccionModel())->listarConTutor();

        $this->view('notificaciones/comunicado', [
            'titulo'    => 'Nuevo comunicado',
            'secciones' => $secciones,
        ]);
    }

    /**
     * POST /notificaciones/comunicado — envía el comunicado.
     * Destinatarios: todo un ROL, o los docentes de UNA sección.
     */
    public function guardarComunicado(): void
    {
        $this->requireRole(NotificacionModel::ROLES_EMISORES);
        $this->validateCsrf();

        $emisorId = (int) (Session::user()['id'] ?? 0);
        $destino  = (string) $this->input('destino', '');
        $titulo   = trim((string) $this->input('titulo', ''));
        $mensaje  = trim((string) $this->input('mensaje', ''));
        $volver   = url('notificaciones/comunicado');

        if ($titulo === '' || $mensaje === '') {
            $this->redirectWithError($volver, 'El título y el mensaje son obligatorios.');
        }

        if ($destino === 'docentes') {
            $destinatarios = $this->model->usuariosDeRol('docente');
        } elseif ($destino === 'seccion') {
            $seccionId = (int) $this->input('seccion_id');
            if ($seccionId <= 0) {
                $this->redirectWithError($volver, 'Elige la sección a la que va el comunicado.');
            }
            $destinatarios = $this->model->docentesDeSeccion($seccionId);
        } else {
            $this->redirectWithError($volver, 'Elige a quién va dirigido el comunicado.');
        }

        if ($destinatarios === []) {
            $this->redirectWithError($volver, 'No hay destinatarios para esa selección.');
        }

        $this->model->beginTransaction();
        try {
            foreach ($destinatarios as $d) {
                $this->model->crear([
                    'usuario_id' => (int) ($d['id'] ?? $d['docente_id']),
                    'tipo'       => NotificacionModel::TIPO_COMUNICADO,
                    'origen'     => 'comunicado',
                    'titulo'     => $titulo,
                    'mensaje'    => $mensaje,
                    'enlace'     => null,
                    'emisor_id'  => $emisorId,
                ]);
            }
            $this->model->commit();
        } catch (\Exception $e) {
            $this->model->rollback();
            log_error('Error al enviar comunicado', [
                'emisor' => $emisorId, 'destino' => $destino,
                'error'  => $e->getMessage(),
            ]);
            $this->redirectWithError($volver, 'No se pudo enviar el comunicado.');
        }

        $n = count($destinatarios);
        $this->redirectWithSuccess(
            url('notificaciones'),
            'Comunicado enviado a ' . $n . ($n === 1 ? ' persona.' : ' personas.')
        );
    }
}
