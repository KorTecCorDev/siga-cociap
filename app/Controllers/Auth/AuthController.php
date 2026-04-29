<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;
use Core\Session;

/**
 * AuthController
 * Gestiona el inicio y cierre de sesión del sistema.
 */
class AuthController extends BaseController
{
    private UsuarioModel $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * GET /login
     * Muestra el formulario de inicio de sesión.
     * Si ya hay sesión activa, redirige al dashboard.
     */
    public function showLogin(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirigirPorRol(Session::user()['rol_codigo']);
        }

        $this->view('auth/login', [
            'titulo' => 'Iniciar sesión',
        ]);
    }

    /**
     * POST /login/procesar
     * Procesa las credenciales y abre la sesión.
     */
    public function login(): void
    {
        $this->validateCsrf();

        $dni      = trim($this->input('dni', ''));
        $password = $this->input('password', '');
        $errores  = [];

        // ── Validación de campos ─────────────────────────────
        if (empty($dni)) {
            $errores['dni'] = 'El DNI es obligatorio.';
        } elseif (!preg_match('/^\d{8}$/', $dni)) {
            $errores['dni'] = 'El DNI debe tener exactamente 8 dígitos.';
        }

        if (empty($password)) {
            $errores['password'] = 'La contraseña es obligatoria.';
        }

        if ($errores) {
            $this->view('auth/login', [
                'titulo'    => 'Iniciar sesión',
                'errores'   => $errores,
                'dni_previo' => $dni,
            ]);
            return;
        }

        // ── Búsqueda del usuario ─────────────────────────────
        $usuario = $this->usuarioModel->findByDni($dni);

        // Mensaje genérico — no revelar si el DNI existe o no
        $errorCredenciales = 'DNI o contraseña incorrectos.';

        if (!$usuario) {
            $this->view('auth/login', [
                'titulo'     => 'Iniciar sesión',
                'errores'    => ['dni' => $errorCredenciales],
                'dni_previo' => $dni,
            ]);
            return;
        }

        // ── Verificar estado del usuario ─────────────────────
        if ($usuario['estado'] !== 'activo') {
            $this->view('auth/login', [
                'titulo'  => 'Iniciar sesión',
                'errores' => ['dni' => 'Tu cuenta está inactiva. Comunícate con el administrador.'],
                'dni_previo' => $dni,
            ]);
            return;
        }

        // ── Verificar contraseña ─────────────────────────────
        if (!password_verify($password, $usuario['password_hash'])) {
            $this->view('auth/login', [
                'titulo'     => 'Iniciar sesión',
                'errores'    => ['dni' => $errorCredenciales],
                'dni_previo' => $dni,
            ]);
            return;
        }

        // ── Sesión válida — registrar acceso ─────────────────
        // Token único para control de sesión única
        $token = bin2hex(random_bytes(32));
        $this->usuarioModel->registrarAcceso($usuario['id'], $token);

        // Regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);

        // Guardar datos del usuario en sesión
        Session::set('auth_user', [
            'id'              => $usuario['id'],
            'persona_id'      => $usuario['persona_id'],
            'dni'             => $usuario['dni'],
            'nombres'         => $usuario['nombres'],
            'apellido_paterno'=> $usuario['apellido_paterno'],
            'apellido_materno'=> $usuario['apellido_materno'],
            'rol_id'          => $usuario['rol_id'],
            'rol_nombre'      => $usuario['rol_nombre'],
            'rol_codigo'      => $usuario['rol_codigo'],
            'correo'          => $usuario['correo'],
        ]);
        Session::set('auth_token', $token);
        Session::set('_last_activity', time());

        // ── Redirigir según rol ──────────────────────────────
        $this->redirigirPorRol($usuario['rol_codigo']);
    }

    /**
     * GET /logout
     * Cierra la sesión y redirige al login.
     */
    public function logout(): void
    {
        $user = Session::user();
        if ($user) {
            $this->usuarioModel->cerrarSesion($user['id']);
        }

        Session::destroy();

        Session::start();
        Session::flash('success', 'Sesión cerrada correctamente. ¡Hasta pronto!');
        redirect(url('login'));
    }

    /**
     * Redirige al dashboard correspondiente según el rol del usuario.
     */
    private function redirigirPorRol(string $rol): never
    {
        $destinos = [
            'admin'             => url('dashboard'),
            'registro_academico'=> url('dashboard'),
            'director_general'  => url('director/anios'),
            'director_ebr'      => url('director/anios'),
            'secretaria'        => url('secretaria/matriculas'),
            'docente'           => url('docente/mis-cargas'),
            'padre'             => url('padre/inicio'),
        ];

        redirect($destinos[$rol] ?? url('dashboard'));
    }
}
