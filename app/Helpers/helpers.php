<?php

/**
 * Helpers globales — SIGA-COCIAP
 * Funciones disponibles en toda la aplicación.
 */

/** Lee un valor de la configuración de la app */
function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require CONFIG_PATH . '/app.php';
    }
    return $config[$key] ?? $default;
}

/** Redirige a una URL y detiene la ejecución */
function redirect(string $url): never
{
    // Ruta relativa de app (/login, /dashboard…) → URL absoluta con base dinámica
    if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
        $url = url(ltrim($url, '/'));
    }
    header("Location: {$url}");
    exit;
}

/**
 * Formatea un datetime de la BD (guardado en hora Lima por la conexión).
 * Devuelve '—' si el valor es nulo o vacío.
 */
function fechaLima(?string $dt, string $formato = 'd/m/Y H:i'): string
{
    if ($dt === null || $dt === '') {
        return '—';
    }
    return (new DateTime($dt))->format($formato);
}

/**
 * Nombre corto para mostrar en la interfaz (saludo, navbar): primer nombre +
 * apellido paterno. SOLO presentación del usuario en pantalla — NUNCA usar en
 * listas oficiales, firmas, reportes impresos ni boletas (esos requieren el
 * nombre completo legal).
 */
function nombre_corto(?string $nombres, ?string $apellidoPaterno = ''): string
{
    $primerNombre = explode(' ', trim($nombres ?? ''))[0];
    return trim($primerNombre . ' ' . trim($apellidoPaterno ?? ''));
}

/** Escapa HTML para prevenir XSS */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** Genera el campo oculto CSRF para formularios */
function csrf_field(): string
{
    $token = \Core\Session::csrfToken();
    return "<input type=\"hidden\" name=\"_csrf_token\" value=\"{$token}\">";
}

/** Genera la URL base del proyecto */
function url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $appUrl = config('app_url');
        if (!empty($appUrl)) {
            // URL fija configurada (ej. IP LAN para pruebas en red local).
            // Tiene prioridad sobre la detección automática.
            $base = rtrim($appUrl, '/');
        } elseif (!empty($_SERVER['HTTP_HOST'])) {
            // Detecta el host real del request (incluye puerto si no es 80/443).
            // Cuando BrowserSync proxea, Apache recibe Host: localhost:3000
            // y PHP lo refleja aquí, manteniendo todas las URLs en el mismo origen.
            $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                ? 'https' : 'http';
            $host     = $_SERVER['HTTP_HOST'];
            $script   = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
            $basePath = rtrim(dirname($script), '/\\');
            $base     = $scheme . '://' . $host . $basePath;
        } else {
            // Fallback para contextos CLI o cuando $_SERVER no está disponible.
            $base = rtrim(config('url', 'http://localhost'), '/');
        }
    }

    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

/** Genera la URL de un asset público (css, js, imágenes) */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Formatea una nota (0-20) con cero a la izquierda: 5 → "05", 15 → "15" */
function fmt_nota(int|null $nota): string
{
    if ($nota === null) return '—';
    return sprintf('%02d', $nota);
}

/**
 * Umbrales de la escala literal — PUNTO ÚNICO DE VERDAD.
 * AD: 18-20 · A: 14-17 · B: 11-13 · C: 00-10.
 * Toda conversión (PHP o SQL interpolado) debe salir de estas constantes.
 */
const NOTA_MIN_AD = 18;
const NOTA_MIN_A  = 14;
const NOTA_MIN_B  = 11;

/** Convierte nota numérica (0-20) a literal. Misma escala en ambos niveles. */
function nota_a_literal(int $nota, string $nivel = 'secundaria'): string
{
    return match(true) {
        $nota >= NOTA_MIN_AD => 'AD',
        $nota >= NOTA_MIN_A  => 'A',
        $nota >= NOTA_MIN_B  => 'B',
        default              => 'C',
    };
}

/** Rangos numéricos de cada literal para leyendas (presentación) */
function escala_rangos(): array
{
    return [
        'AD' => sprintf('%02d–20', NOTA_MIN_AD),
        'A'  => sprintf('%02d–%02d', NOTA_MIN_A, NOTA_MIN_AD - 1),
        'B'  => sprintf('%02d–%02d', NOTA_MIN_B, NOTA_MIN_A - 1),
        'C'  => sprintf('00–%02d', NOTA_MIN_B - 1),
    ];
}

/** Descripción completa de la escala literal */
function descripcion_literal(string $literal): string
{
    return match($literal) {
        'AD' => 'Logro destacado',
        'A'  => 'Logro esperado',
        'B'  => 'En proceso',
        'C'  => 'En inicio',
        default => '—',
    };
}

/** Verifica si la conclusión descriptiva es obligatoria */
function conclusion_es_obligatoria(string $literal, string $nivel): bool
{
    if ($nivel === 'primaria') {
        return in_array($literal, ['B', 'C']);
    }
    return $literal === 'C'; // Secundaria solo en C
}

/** Formatea una fecha en español peruano */
function fecha_es(string $fecha): string
{
    $meses = [
        1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',
        5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',
        9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'
    ];
    $ts = strtotime($fecha);
    return date('d', $ts) . ' de ' . $meses[(int)date('m', $ts)] . ' de ' . date('Y', $ts);
}

/** Retorna el usuario autenticado actual */
function auth(): ?array
{
    return \Core\Session::user();
}

/** Verifica si el usuario tiene un rol dado */
function has_role(string|array $roles): bool
{
    return \Core\Session::hasRole($roles);
}

/** Log de errores simple */
function log_error(string $mensaje, array $context = []): void
{
    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;
    if ($context) {
        $linea .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    error_log($linea . PHP_EOL, 3, STORAGE_PATH . '/logs/siga.log');
}

/**
 * Renderiza una página de error genérica y detiene el flujo normal. La usa el
 * manejador global de errores en producción para no filtrar stack traces ni
 * errores de base de datos al usuario. Idempotente: nunca imprime dos veces.
 */
function render_error_page(int $code = 500): void
{
    static $rendered = false;
    if ($rendered) {
        return;
    }
    $rendered = true;

    // Descarta cualquier salida parcial para que la página de error salga limpia.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code($code);
    }

    $vista = VIEW_PATH . '/shared/500.php';
    if (is_file($vista)) {
        require $vista;
    } else {
        echo 'Ha ocurrido un error. Intenta de nuevo mas tarde.';
    }
}
