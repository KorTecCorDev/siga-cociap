<?php

namespace App\Models;

/**
 * DirectorEbrModel
 * Gestiona el historial de asignaciones del Director EBR.
 * El cargo puede cambiar de titular a mitad de año; cada registro
 * tiene un rango de fechas (desde / hasta). hasta=NULL significa vigente.
 */
class DirectorEbrModel extends BaseModel
{
    protected string $table = 'director_ebr_historial';

    /**
     * Director EBR vigente en una fecha de referencia para un año académico.
     * Si $fecha es NULL usa la fecha actual.
     * Incluye firma_path y sello_path para renderizado en documentos.
     */
    public function getVigenteEnFecha(int $anioId, ?string $fecha = null): ?array
    {
        $ref = $fecha ?? date('Y-m-d');

        return $this->queryOne("
            SELECT
                h.id,
                h.usuario_id,
                h.desde,
                h.hasta,
                h.asignado_en,
                h.firma_path,
                h.sello_path,
                CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres)
                    AS nombre_completo
            FROM director_ebr_historial h
            INNER JOIN usuarios u ON u.id = h.usuario_id
            INNER JOIN personas p ON p.id = u.persona_id
            WHERE h.anio_id  = ?
              AND h.desde   <= ?
              AND (h.hasta IS NULL OR h.hasta >= ?)
            ORDER BY h.desde DESC
            LIMIT 1
        ", [$anioId, $ref, $ref]);
    }

    /**
     * Historial completo de un año académico, más reciente primero.
     */
    public function getHistorialPorAnio(int $anioId): array
    {
        return $this->query("
            SELECT
                h.id,
                h.desde,
                h.hasta,
                h.asignado_en,
                h.firma_path,
                h.sello_path,
                CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres)
                    AS nombre_completo,
                CONCAT(pa.apellido_paterno, ' ', pa.apellido_materno, ', ', pa.nombres)
                    AS asignado_por_nombre
            FROM director_ebr_historial h
            INNER JOIN usuarios u  ON u.id  = h.usuario_id
            INNER JOIN personas p  ON p.id  = u.persona_id
            INNER JOIN usuarios ua ON ua.id = h.asignado_por
            INNER JOIN personas pa ON pa.id = ua.persona_id
            WHERE h.anio_id = ?
            ORDER BY h.desde DESC
        ", [$anioId]);
    }

    /**
     * Años académicos con el nombre del Director EBR vigente hoy.
     * Incluye firma_path y sello_path del registro vigente.
     */
    public function getAniosConDirector(): array
    {
        return $this->query("
            SELECT
                a.id,
                a.anio,
                a.estado,
                (
                    SELECT CONCAT(p2.apellido_paterno, ' ', p2.apellido_materno, ', ', p2.nombres)
                    FROM director_ebr_historial h2
                    INNER JOIN usuarios u2 ON u2.id = h2.usuario_id
                    INNER JOIN personas p2 ON p2.id = u2.persona_id
                    WHERE h2.anio_id = a.id
                      AND h2.hasta IS NULL
                    ORDER BY h2.desde DESC
                    LIMIT 1
                ) AS director_actual,
                (
                    SELECT h3.usuario_id
                    FROM director_ebr_historial h3
                    WHERE h3.anio_id = a.id
                      AND h3.hasta IS NULL
                    ORDER BY h3.desde DESC
                    LIMIT 1
                ) AS director_usuario_id,
                (
                    SELECT h4.id
                    FROM director_ebr_historial h4
                    WHERE h4.anio_id = a.id
                      AND h4.hasta IS NULL
                    ORDER BY h4.desde DESC
                    LIMIT 1
                ) AS historial_id_vigente,
                (
                    SELECT h5.firma_path
                    FROM director_ebr_historial h5
                    WHERE h5.anio_id = a.id
                      AND h5.hasta IS NULL
                    ORDER BY h5.desde DESC
                    LIMIT 1
                ) AS firma_path_vigente,
                (
                    SELECT h6.sello_path
                    FROM director_ebr_historial h6
                    WHERE h6.anio_id = a.id
                      AND h6.hasta IS NULL
                    ORDER BY h6.desde DESC
                    LIMIT 1
                ) AS sello_path_vigente
            FROM anios_academicos a
            ORDER BY a.anio DESC
        ");
    }

    /**
     * Usuarios activos con rol director_ebr disponibles para ser asignados.
     */
    public function getUsuariosDisponibles(): array
    {
        return $this->query("
            SELECT
                u.id,
                CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres)
                    AS nombre_completo
            FROM usuarios u
            INNER JOIN personas p ON p.id = u.persona_id
            INNER JOIN roles r    ON r.id = u.rol_id
            WHERE r.codigo  = 'director_ebr'
              AND u.estado  = 'activo'
            ORDER BY p.apellido_paterno, p.apellido_materno, p.nombres
        ");
    }

    /**
     * Asigna un nuevo Director EBR para un año académico a partir de $desde.
     * Cierra el registro vigente anterior y crea el nuevo. Retorna el ID
     * del registro insertado (0 en caso de error) para permitir subir imágenes.
     */
    public function asignar(
        int    $usuarioId,
        int    $anioId,
        string $desde,
        int    $asignadoPor
    ): int {
        $this->beginTransaction();
        try {
            $this->execute("
                UPDATE director_ebr_historial
                SET hasta = DATE_SUB(?, INTERVAL 1 DAY)
                WHERE anio_id = ?
                  AND hasta   IS NULL
                  AND desde   < ?
            ", [$desde, $anioId, $desde]);

            $this->execute("
                DELETE FROM director_ebr_historial
                WHERE anio_id = ?
                  AND desde   = ?
            ", [$anioId, $desde]);

            $newId = $this->create([
                'usuario_id'   => $usuarioId,
                'anio_id'      => $anioId,
                'desde'        => $desde,
                'hasta'        => null,
                'asignado_por' => $asignadoPor,
            ]);

            $this->commit();
            return $newId;
        } catch (\Exception $e) {
            $this->rollback();
            log_error('Error asignando Director EBR', [
                'usuario_id' => $usuarioId,
                'anio_id'    => $anioId,
                'error'      => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Actualiza las rutas de firma y/o sello de un registro del historial.
     * Pasa null en cualquiera de los paths para no modificarlo.
     */
    public function actualizarImagenes(
        int     $id,
        ?string $firmaPath,
        ?string $selloPath
    ): bool {
        $sets   = [];
        $params = [];

        if ($firmaPath !== null) {
            $sets[]   = 'firma_path = ?';
            $params[] = $firmaPath;
        }
        if ($selloPath !== null) {
            $sets[]   = 'sello_path = ?';
            $params[] = $selloPath;
        }

        if (empty($sets)) {
            return false;
        }

        $params[] = $id;
        return $this->execute(
            'UPDATE director_ebr_historial SET ' . implode(', ', $sets) . ' WHERE id = ?',
            $params
        );
    }
}
