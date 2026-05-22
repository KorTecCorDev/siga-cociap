<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DirectorEbrModel;
use Core\Session;

/**
 * DirectorEbrController
 * Administra el historial de titulares del cargo de Director EBR,
 * incluyendo la carga de firma y sello en formato PNG.
 */
class DirectorEbrController extends BaseController
{
    private DirectorEbrModel $model;

    /** Directorio relativo a public/ donde se almacenan las imágenes */
    private const IMG_DIR = 'assets/img/firmas';

    public function __construct()
    {
        $this->requireRole('admin');
        $this->model = new DirectorEbrModel();
    }

    /**
     * GET /admin/director-ebr
     */
    public function index(): void
    {
        $anios    = $this->model->getAniosConDirector();
        $usuarios = $this->model->getUsuariosDisponibles();

        $historiales = [];
        foreach ($anios as $anio) {
            $historiales[$anio['id']] = $this->model->getHistorialPorAnio($anio['id']);
        }

        $this->view('admin/director-ebr/index', [
            'titulo'      => 'Director EBR — Historial de asignaciones',
            'anios'       => $anios,
            'historiales' => $historiales,
            'usuarios'    => $usuarios,
        ]);
    }

    /**
     * POST /admin/director-ebr/{anio_id}/asignar
     * Asigna un nuevo Director EBR. Acepta opcionalmente firma y sello PNG.
     */
    public function asignar(int $anioId): void
    {
        $this->validateCsrf();

        $usuarioId = (int) $this->input('usuario_id');
        $desde     = trim($this->input('desde', ''));
        if (!$desde) $desde = date('Y-m-d');

        if (!$usuarioId) {
            $this->redirectWithError(url('admin/director-ebr'), 'Debe seleccionar un usuario.');
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $desde);
        if (!$dt || $dt->format('Y-m-d') !== $desde) {
            $this->redirectWithError(url('admin/director-ebr'), 'La fecha de inicio no es válida.');
        }

        if (!$this->model->queryOne("SELECT id FROM anios_academicos WHERE id = ?", [$anioId])) {
            $this->redirectWithError(url('admin/director-ebr'), 'Año académico no encontrado.');
        }

        if (!$this->model->queryOne("
            SELECT u.id FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.id = ? AND r.codigo = 'director_ebr' AND u.estado = 'activo'
        ", [$usuarioId])) {
            $this->redirectWithError(
                url('admin/director-ebr'),
                'El usuario no tiene el rol Director EBR o no está activo.'
            );
        }

        $nuevoId = $this->model->asignar($usuarioId, $anioId, $desde, (int) Session::user()['id']);

        if (!$nuevoId) {
            $this->redirectWithError(url('admin/director-ebr'), 'Error al registrar la asignación.');
        }

        // Subir imágenes opcionales
        try {
            $firmaPath = $this->subirImagen('firma', 'firma', $nuevoId);
            $selloPath = $this->subirImagen('sello', 'sello', $nuevoId);

            if ($firmaPath !== null || $selloPath !== null) {
                $this->model->actualizarImagenes($nuevoId, $firmaPath, $selloPath);
            }
        } catch (\RuntimeException $e) {
            // La asignación fue exitosa; el error es solo de imagen
            $this->redirectWithError(
                url('admin/director-ebr'),
                'Director asignado, pero hubo un problema con las imágenes: ' . $e->getMessage()
            );
        }

        $this->redirectWithSuccess(url('admin/director-ebr'), 'Director EBR asignado correctamente.');
    }

    /**
     * POST /admin/director-ebr/{id}/imagenes
     * Actualiza la firma y/o sello de un registro del historial ya existente.
     * {id} es el director_ebr_historial.id.
     */
    public function actualizarImagenes(int $id): void
    {
        $this->validateCsrf();

        $registro = $this->model->find($id);
        if (!$registro) {
            $this->redirectWithError(url('admin/director-ebr'), 'Registro no encontrado.');
        }

        try {
            $firmaPath = $this->subirImagen('firma', 'firma', $id, $registro['firma_path'] ?? null);
            $selloPath = $this->subirImagen('sello', 'sello', $id, $registro['sello_path'] ?? null);

            if ($firmaPath === null && $selloPath === null) {
                $this->redirectWithError(
                    url('admin/director-ebr'),
                    'No se seleccionó ninguna imagen para actualizar.'
                );
            }

            $this->model->actualizarImagenes($id, $firmaPath, $selloPath);
        } catch (\RuntimeException $e) {
            $this->redirectWithError(url('admin/director-ebr'), $e->getMessage());
        }

        $this->redirectWithSuccess(url('admin/director-ebr'), 'Imágenes actualizadas correctamente.');
    }

    // ── Helpers privados ─────────────────────────────────────────

    /**
     * Procesa un archivo PNG subido desde $_FILES[$inputName].
     * Retorna la ruta relativa a public/ guardada, o null si no se subió nada.
     * Si $rutaAnterior se proporciona, elimina el archivo anterior al reemplazar.
     *
     * @throws \RuntimeException si el archivo es inválido o no se puede guardar.
     */
    private function subirImagen(
        string  $inputName,
        string  $prefijo,
        int     $historialId,
        ?string $rutaAnterior = null
    ): ?string {
        $file = $_FILES[$inputName] ?? null;

        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(
                "Error al recibir el archivo \"{$inputName}\" (código {$file['error']})."
            );
        }

        // Validar que sea realmente PNG por contenido (no solo extensión)
        $info = @\getimagesize($file['tmp_name']);
        if (!$info || $info[2] !== IMAGETYPE_PNG) {
            throw new \RuntimeException("El archivo \"{$inputName}\" debe estar en formato PNG.");
        }

        // Límite de 2 MB
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new \RuntimeException("El archivo \"{$inputName}\" no debe superar 2 MB.");
        }

        $dir = ROOT_PATH . '/public/' . self::IMG_DIR . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $nombre  = $prefijo . '_' . $historialId . '_' . time() . '.png';
        $destino = $dir . $nombre;

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            throw new \RuntimeException("No se pudo guardar el archivo \"{$inputName}\".");
        }

        // Eliminar archivo anterior si existe y es diferente al nuevo
        if ($rutaAnterior && $rutaAnterior !== self::IMG_DIR . '/' . $nombre) {
            $rutaAbsoluta = ROOT_PATH . '/public/' . $rutaAnterior;
            if (file_exists($rutaAbsoluta)) {
                @unlink($rutaAbsoluta);
            }
        }

        return self::IMG_DIR . '/' . $nombre;
    }
}
