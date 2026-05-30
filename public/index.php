<?php

/**
 * SIGA-COCIAP — Front Controller
 * Punto de entrada único de la aplicación.
 * Todo request HTTP pasa por aquí.
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH',  ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('VIEW_PATH', ROOT_PATH . '/resources/views');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Autoloader PSR-4 simple (sin Composer por ahora)
spl_autoload_register(function (string $class): void {
    $map = [
        'Core\\'  => CORE_PATH . '/',
        'App\\Controllers\\' => APP_PATH . '/Controllers/',
        'App\\Models\\'      => APP_PATH . '/Models/',
        'App\\Middleware\\'  => APP_PATH . '/Middleware/',
    ];

    foreach ($map as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = $base . $relative . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// Cargar configuración y helpers
require_once CONFIG_PATH . '/app.php';
require_once APP_PATH   . '/Helpers/helpers.php';

// ── Manejo de errores según entorno ─────────────────────────
// En local (debug): muestra todo. En producción: oculta los errores al
// usuario (sin stack traces) pero los registra en el log del servidor.
error_reporting(E_ALL);
if (config('debug')) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    // ── Blindaje global (solo producción) ───────────────────
    // Cualquier excepción no capturada o error fatal se registra en el log
    // y se muestra una página de error genérica, sin filtrar stack traces ni
    // errores de base de datos. En local (debug) los errores se ven completos.
    set_exception_handler(function (\Throwable $e): void {
        log_error('Excepcion no capturada', [
            'tipo'    => get_class($e),
            'mensaje' => $e->getMessage(),
            'donde'   => $e->getFile() . ':' . $e->getLine(),
        ]);
        render_error_page();
    });

    register_shutdown_function(function (): void {
        $err = error_get_last();
        if ($err !== null && in_array(
            $err['type'],
            [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR],
            true
        )) {
            log_error('Error fatal', [
                'mensaje' => $err['message'],
                'donde'   => $err['file'] . ':' . $err['line'],
            ]);
            render_error_page();
        }
    });
}

// Aplicar timezone desde config (evita que strtotime interprete fechas como UTC)
date_default_timezone_set(config('timezone'));

// Iniciar sesión de forma segura
require_once CORE_PATH . '/Session.php';
Core\Session::start();

// ── Router: cargar, registrar rutas y despachar ─────────────
require_once CORE_PATH . '/Router.php';

$router = new \Core\Router();

require_once ROOT_PATH . '/routes/web.php'; // registra las rutas en $router

$router->dispatch();
