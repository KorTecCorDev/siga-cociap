<?php

/**
 * Configuración principal — SIGA-COCIAP
 * En producción estos valores vendrán de variables de entorno.
 */

return [
    'name'            => 'SIGA-COCIAP',
    'nombre_completo' => 'Sistema Integrado de Gestión Académica',
    'institucion'     => 'Colegio de Aplicación "Víctor Valenzuela Guardia"',
    'version'         => '1.0.0',
    // debug activo SOLO en entornos locales/privados (XAMPP, LAN). En cualquier
    // host publico (produccion o Host inyectado) queda en false → nunca expone
    // errores. El default seguro es OFF: si el host no es local, no hay debug.
    'debug'           => (static function (): bool {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return str_starts_with($host, 'localhost')
            || str_starts_with($host, '127.0.0.1')
            || str_starts_with($host, '192.168.')
            || str_starts_with($host, '10.');
    })(),
    'session_timeout' => 600,            // 10 minutos en segundos
    'timezone'        => 'America/Lima',
    'locale'          => 'es_PE',
    'url'             => 'http://localhost/siga-cociap/public', // fallback CLI únicamente
    // En producción (host sigacociap.net) fuerza la base limpia sin prefijo /public.
    // En local/LAN queda '' → autodetección por HTTP_HOST (BrowserSync :3000, IP DHCP).
    'app_url'         => str_contains($_SERVER['HTTP_HOST'] ?? '', 'sigacociap.net')
        ? 'https://sigacociap.net'
        : '',
];
