<?php

namespace App\Controllers;

use Core\View;
use Core\Session;

/**
 * BaseController
 * Controlador base con métodos comunes.
 * Todos los controladores de la app extienden de este.
 */
abstract class BaseController
{
    /**
     * Renderiza una vista pasando datos automáticamente.
     * Los flashes de sesión siempre están disponibles en las vistas.
     */
    protected function view(string $view, array $data = []): void
    {
        // Datos globales disponibles en todas las vistas
        $globals = [
            'auth_user'     => Session::user(),
            'flash_success' => Session::getFlash('success'),
            'flash_error'   => Session::getFlash('error'),
            'flash_info'    => Session::getFlash('info'),
            'flash_warning' => Session::getFlash('warning'),
            'app_name'      => config('app.name'),
            'institucion'   => config('app.institucion'),
        ];

        View::render($view, array_merge($globals, $data));
    }

    /** Respuesta JSON */
    protected function json(mixed $data, int $status = 200): void
    {
        View::json($data, $status);
    }

    /** Redirige con mensaje flash de éxito */
    protected function redirectWithSuccess(string $url, string $mensaje): never
    {
        Session::flash('success', $mensaje);
        redirect($url);
    }

    /** Redirige con mensaje flash de error */
    protected function redirectWithError(string $url, string $mensaje): never
    {
        Session::flash('error', $mensaje);
        redirect($url);
    }

    /** Valida el token CSRF del request actual */
    protected function validateCsrf(): void
    {
        $token = $_POST['_csrf_token'] ?? '';
        if (!Session::verifyCsrf($token)) {
            http_response_code(403);
            exit('Token de seguridad inválido. Recarga la página e intenta de nuevo.');
        }
    }

    /** Verifica que el usuario esté autenticado */
    protected function requireAuth(): void
    {
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Debes iniciar sesión para acceder.');
            redirect('/login');
        }
    }

    /** Verifica que el usuario tenga alguno de los roles indicados */
    protected function requireRole(string|array $roles): void
    {
        $this->requireAuth();
        if (!Session::hasRole($roles)) {
            http_response_code(403);
            $this->view('shared/403');
            exit;
        }
    }

    /** Obtiene y sanitiza un valor POST */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /** Obtiene y sanitiza un valor GET */
    protected function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /** Verifica si el request es AJAX */
    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
