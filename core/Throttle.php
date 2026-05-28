<?php

namespace Core;

/**
 * Throttle
 * Limitador de intentos simple basado en archivos (sin dependencias externas).
 * Pensado para frenar el escaneo/fuerza bruta en endpoints públicos como la
 * consulta de boletas por código. La ventana se almacena por clave (p. ej. IP).
 *
 * Nota: los contadores viven en storage/throttle/. Si un deploy limpia ese
 * directorio, los contadores se reinician (comportamiento aceptable y raro).
 */
class Throttle
{
    private static function dir(): string
    {
        $dir = STORAGE_PATH . '/throttle';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Registra un intento para $key y devuelve true si se SUPERÓ el límite
     * dentro de la ventana de $decaySeconds segundos.
     */
    public static function hit(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $file = self::dir() . '/' . hash('sha256', $key) . '.json';
        $now  = time();

        $hits = [];
        if (is_file($file)) {
            $hits = json_decode((string) @file_get_contents($file), true) ?: [];
        }

        // Conservar solo los intentos dentro de la ventana de tiempo
        $hits = array_values(array_filter(
            $hits,
            static fn ($t): bool => is_int($t) && ($now - $t) < $decaySeconds
        ));

        $hits[] = $now;
        @file_put_contents($file, json_encode($hits), LOCK_EX);

        return count($hits) > $maxAttempts;
    }
}
