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
    'debug'           => true,           // false en producción
    'session_timeout' => 600,            // 10 minutos en segundos
    'timezone'        => 'America/Lima',
    'locale'          => 'es_PE',
    'url'             => 'http://localhost/siga-cociap/public', // fallback CLI únicamente
    'app_url'         => 'http://192.168.1.31/siga-cociap/public', // URL LAN — vacío = detección automática
];
