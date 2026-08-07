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

    /**
     * Respuesta 404 estandar del proyecto. PUNTO UNICO: cualquier controlador
     * que quiera cortar con "no existe" llama aqui.
     *
     * ⚠️ NACIO EL 07/08/2026 PORQUE NO EXISTIA. Varios controladores ya
     * llamaban `$this->notFound()` sin que estuviera definido en ningun sitio
     * (solo `Router` y `RectificacionController` tenian el suyo, ambos PRIVADOS
     * y por tanto inalcanzables desde fuera). El resultado era el peor posible:
     * en local reventaba con "Call to undefined method" y en produccion el
     * blindaje global lo capturaba como excepcion y devolvia la pagina de error
     * GENERICA — nunca un 404. No se noto antes porque los unicos caminos que
     * lo invocaban exigian un periodo inexistente; los gates de
     * /consulta-notas fueron los primeros en dispararlo de verdad.
     *
     * ⚠️ `require` DIRECTO, no `$this->view()`: `shared/404.php` es una pagina
     * HTML completa (con su propio <!DOCTYPE>), asi que pasarla por el layout
     * anida un documento dentro de otro. Es el mismo mecanismo que usa
     * `Router::notFound()`.
     */
    protected function notFound(): never
    {
        http_response_code(404);
        require VIEW_PATH . '/shared/404.php';
        exit;
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
