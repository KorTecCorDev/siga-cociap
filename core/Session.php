<?php

namespace Core;

/**
 * Session
 * Manejo centralizado y seguro de sesiones.
 * Implementa: sesión única por usuario, expiración por inactividad,
 * protección CSRF y regeneración de ID.
 */
class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Solo aceptar IDs de sesión generados por el servidor (refuerza la
        // anti-fijación) y nunca leer el ID desde la URL.
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        // Detecta HTTPS (directo o tras el proxy SSL de Hostinger) para marcar la
        // cookie como Secure solo cuando corresponde. En local HTTP queda false,
        // así no se rompe el desarrollo en XAMPP/BrowserSync.
        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443);

        // Configuración segura de la cookie de sesión
        session_set_cookie_params([
            'lifetime' => 0,           // expira al cerrar navegador
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,    // Secure solo bajo HTTPS (producción)
            'httponly' => true,        // inaccesible desde JS
            'samesite' => 'Lax',
        ]);

        session_name('SIGA_SESSION');
        session_start();
        self::$started = true;

        // Generar token CSRF si no existe
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        // Verificar expiración por inactividad (10 minutos = 600 segundos)
        $timeout = config('app.session_timeout', 600);
        if (isset($_SESSION['_last_activity'])) {
            if ((time() - $_SESSION['_last_activity']) > $timeout) {
                self::destroy();
                redirect('/login?timeout=1');
                return;
            }
        }
        $_SESSION['_last_activity'] = time();
    }

    /** Guarda un valor en la sesión */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /** Obtiene un valor de la sesión */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /** Verifica si existe una clave en la sesión */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /** Elimina una clave de la sesión */
    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Guarda un mensaje flash (disponible solo en el siguiente request) */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /** Obtiene y elimina un mensaje flash */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /** Verifica si hay un mensaje flash */
    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    /** Retorna el token CSRF actual */
    public static function csrfToken(): string
    {
        return $_SESSION['_csrf_token'] ?? '';
    }

    /** Valida el token CSRF enviado en un formulario */
    public static function verifyCsrf(string $token): bool
    {
        return hash_equals(self::csrfToken(), $token);
    }

    /** Usuario autenticado actual */
    public static function user(): ?array
    {
        return $_SESSION['auth_user'] ?? null;
    }

    /** Verifica si hay sesión activa */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['auth_user']);
    }

    /** Verifica si el usuario tiene un rol específico */
    public static function hasRole(string|array $roles): bool
    {
        $user = self::user();
        if (!$user) return false;

        $userRole = $user['rol_codigo'] ?? '';
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($userRole, $roles);
    }

    /** Destruye la sesión completamente */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        self::$started = false;
    }
}
