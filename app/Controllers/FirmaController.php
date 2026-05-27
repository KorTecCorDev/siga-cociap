<?php

namespace App\Controllers;

/**
 * FirmaController
 * Sirve las imágenes de firma/sello del Director EBR desde el almacenamiento
 * externo (fuera del repo, para sobrevivir a los deploys de Hostinger).
 *
 * Es PÚBLICO sin login: el sello aparece en la boleta pública. Solo entrega
 * archivos PNG del directorio configurado, con el nombre validado de forma
 * estricta para evitar path traversal.
 */
class FirmaController extends BaseController
{
    // GET /firmas/{archivo}
    public function servir(string $archivo): void
    {
        // Solo nombres tipo "firma_3_1700000000.png" — sin barras ni puntos extra
        if (!preg_match('/^[A-Za-z0-9_-]+\.png$/', $archivo)) {
            http_response_code(404);
            return;
        }

        $base = config('firmas_path');
        $real = realpath($base . '/' . $archivo);
        $baseReal = realpath($base);

        // Confirma que el archivo resuelto está realmente dentro del directorio base
        if ($real === false || $baseReal === false
            || !str_starts_with($real, $baseReal . DIRECTORY_SEPARATOR)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($real));
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($real);
    }
}
