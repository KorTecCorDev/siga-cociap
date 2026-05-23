<?php

namespace App\Middleware;

use Core\Session;

/**
 * AuthMiddleware
 * Verifica autenticación y roles antes de procesar cada request.
 */
class AuthMiddleware
{
    /** Rutas públicas que no requieren autenticación (coincidencia exacta) */
    private static array $publicRoutes = [
        '/login',
        '/login/procesar',
        '/logout',
        '/boleta-publica',
        '/boleta-publica/consultar',
    ];

    /** Prefijos de rutas públicas (cualquier URI que empiece con estos) */
    private static array $publicPrefixes = [
        '/boleta/digital/',
    ];

    public static function handle(string $uri, string $method): void
    {
        // Si es ruta pública exacta, no verificar
        foreach (self::$publicRoutes as $route) {
            if ($uri === $route) return;
        }

        // Si comienza con un prefijo público, no verificar
        foreach (self::$publicPrefixes as $prefix) {
            if (str_starts_with($uri, $prefix)) return;
        }

        // Si no está autenticado, redirigir al login
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Debes iniciar sesión para continuar.');
            redirect('/login');
        }

        // Actualizar timestamp de actividad (renueva el timeout)
        $_SESSION['_last_activity'] = time();
    }

    /**
     * Verifica que el usuario tenga acceso a rutas por rol.
     * Retorna false si no tiene permiso.
     */
    public static function can(string|array $roles): bool
    {
        return Session::hasRole($roles);
    }
}
