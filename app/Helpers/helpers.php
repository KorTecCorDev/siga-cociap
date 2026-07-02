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

/**
 * Etiqueta del docente de aula (unidocente) segun sexo. En primaria 1°-3° un
 * solo docente dicta TODAS las areas de su seccion y es su tutor: la interfaz
 * lo nombra "Tutor(a) de aula" para reflejar esa identidad de aula completa.
 */
function rol_aula(?string $sexo): string
{
    return match ($sexo) {
        'M'     => 'Tutor de aula',
        'F'     => 'Tutora de aula',
        default => 'Tutor(a) de aula',
    };
}

/**
 * ¿El DNI es un código PROVISIONAL? Un DNI real son 8 dígitos numéricos; el
 * alta provisional (estudiante sin DNI todavía) usa 'P' + 7 dígitos (P0000042).
 * Punto único de verdad para distinguirlos en controladores y vistas.
 */
function es_dni_provisional(?string $dni): bool
{
    return $dni !== null && $dni !== '' && strtoupper($dni[0]) === 'P';
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

    $full = rtrim($base, '/') . '/' . ltrim($path, '/');

    // Cache-busting: si la ruta es un archivo estatico real, le anexa la fecha
    // de modificacion como ?v=... para que el navegador re-descargue al cambiar.
    return asset_version($full, $path);
}

/**
 * Cache-busting de assets estaticos.
 * Si $relPath apunta a un archivo real bajo public/ (css, js, imagenes,
 * fuentes, etc.) devuelve la URL con ?v=<filemtime>; asi el navegador y la
 * PWA instalada vuelven a descargar el archivo cada vez que cambia, sin que
 * el usuario tenga que limpiar la cache. Las rutas de la app (sin extension,
 * ej. /login, /boleta/123/1) se devuelven sin tocar.
 */
function asset_version(string $absoluteUrl, string $relPath): string
{
    static $cache = [];

    // Ya trae query string propia -> no interferir.
    if (str_contains($absoluteUrl, '?')) {
        return $absoluteUrl;
    }

    $rel = ltrim($relPath, '/');
    if ($rel === '') {
        return $absoluteUrl;
    }

    // Solo extensiones de archivos versionables. Las rutas de la app no tienen
    // extension, asi que se descartan aqui sin tocar el disco.
    static $exts = [
        'css', 'js', 'map', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'ico',
        'woff', 'woff2', 'ttf', 'eot', 'json', 'mp4', 'webm', 'pdf',
    ];
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    if ($ext === '' || !in_array($ext, $exts, true)) {
        return $absoluteUrl;
    }

    if (!array_key_exists($rel, $cache)) {
        $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
        $file = $root . '/public/' . $rel;
        $cache[$rel] = is_file($file) ? filemtime($file) : null;
    }

    return $cache[$rel] === null
        ? $absoluteUrl
        : $absoluteUrl . '?v=' . $cache[$rel];
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

    // Ruta del log fuera del docroot en produccion (config 'log_path'). El
    // logging nunca debe tumbar la app: mkdir y escritura son defensivos, con
    // fallback silencioso si el directorio no es escribible.
    $destino = config('log_path') ?: (STORAGE_PATH . '/logs/siga.log');
    $dir     = dirname($destino);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @error_log($linea . PHP_EOL, 3, $destino);
}

/**
 * Estado de la boleta de un bimestre, DERIVADO del periodo (no es una columna):
 *   'registro' -> activo y boletas NO aprobadas       -> aun no hay boleta visible
 *   'borrador' -> activo y boletas aprobadas (Hito A)  -> vista previa (docentes)
 *   'oficial'  -> bimestre cerrado (Hito B)            -> oficial (docentes + padres)
 * La reapertura (cerrado -> activo conservando el flag) vuelve a 'borrador'.
 */
function boleta_estado_bimestre(?string $estadoPeriodo, ?string $boletasAprobadasEn): string
{
    if ($estadoPeriodo === 'cerrado') {
        return 'oficial';
    }
    if ($estadoPeriodo === 'activo' && !empty($boletasAprobadasEn)) {
        return 'borrador';
    }
    return 'registro';
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
