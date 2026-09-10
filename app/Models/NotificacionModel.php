<?php

namespace App\Models;

/**
 * NotificacionModel
 *
 * Bandeja interna por usuario. Dos orígenes:
 *   - 'sistema'    → las genera un evento de la aplicación (hoy: llegan notas
 *                    del colegio de origen de un estudiante de tu sección).
 *   - 'comunicado' → las redacta admin / registro académico.
 *
 * ⚠️ NO es la tabla `alertas`. Aquella va TUTOR → PADRE (lleva `tutor_id` y
 * `matricula_id`, y solo la lee /padre/alertas); nadie le escribe y tiene 0
 * filas. Otra dirección, otro público: se deja intacta. Ver migración 058.
 *
 * Los TIPOS son una lista cerrada EN PHP, no un ENUM en base de datos: añadir
 * uno nuevo no debe exigir una migración.
 */
class NotificacionModel extends BaseModel
{
    protected string $table = 'notificaciones';

    /** Notas que el estudiante trae de su colegio de origen. */
    public const TIPO_NOTAS_ORIGEN = 'notas_origen';
    /** Mensaje redactado por admin / registro académico. */
    public const TIPO_COMUNICADO   = 'comunicado';

    public const TIPOS = [
        self::TIPO_NOTAS_ORIGEN => 'Notas del colegio de origen',
        self::TIPO_COMUNICADO   => 'Comunicado',
    ];

    /**
     * Roles que RECIBEN notificaciones. Los de dirección entran a VER (son de
     * solo lectura, ver ROLES_DIRECCION en helpers.php), pero no se les generan
     * avisos automáticos: no tienen cargas.
     */
    public const ROLES_RECEPTORES = ['docente', 'registro_academico', 'admin'];

    /**
     * Roles que pueden REDACTAR comunicados. Deliberadamente NO incluye a los
     * tres de ROLES_DIRECCION: son SOLO LECTURA en todo el sistema.
     */
    public const ROLES_EMISORES = ['admin', 'registro_academico'];

    /** Alta de una notificación suelta. Devuelve el id creado. */
    public function crear(array $data): int
    {
        $this->execute("
            INSERT INTO notificaciones
                (usuario_id, tipo, origen, titulo, mensaje, enlace, emisor_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [
            (int) $data['usuario_id'],
            (string) $data['tipo'],
            (string) ($data['origen'] ?? 'sistema'),
            (string) $data['titulo'],
            (string) $data['mensaje'],
            $data['enlace']    ?? null,
            isset($data['emisor_id']) ? (int) $data['emisor_id'] : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Notifica a TODOS los docentes con carga activa en la sección de una
     * matrícula. Un solo aviso por docente aunque tenga varias cargas allí.
     *
     * ⚠️ `cargas_academicas.docente_id` apunta DIRECTO a `usuarios.id` (no hay
     * tabla `docentes`); comprobado antes de escribir esta consulta.
     *
     * @return int Cuántos docentes quedaron notificados.
     */
    public function crearParaDocentesDeSeccion(
        int $matriculaId,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?string $enlace = null,
        ?int $emisorId = null
    ): int {
        $docentes = $this->query("
            SELECT DISTINCT ca.docente_id
            FROM matriculas m
            INNER JOIN cargas_academicas ca
                    ON ca.seccion_id = m.seccion_id
                   AND ca.anio_id    = m.anio_id
                   AND ca.estado     = 'activa'
            WHERE m.id = ?
        ", [$matriculaId]);

        foreach ($docentes as $d) {
            $this->crear([
                'usuario_id' => (int) $d['docente_id'],
                'tipo'       => $tipo,
                'origen'     => $emisorId === null ? 'sistema' : 'comunicado',
                'titulo'     => $titulo,
                'mensaje'    => $mensaje,
                'enlace'     => $enlace,
                'emisor_id'  => $emisorId,
            ]);
        }

        return count($docentes);
    }

    /** Usuarios de un rol (para dirigir un comunicado a todo un rol). */
    public function usuariosDeRol(string $rolCodigo): array
    {
        return $this->query("
            SELECT u.id
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE r.codigo = ? AND u.estado = 'activo'
        ", [$rolCodigo]);
    }

    /** Docentes con carga activa en una sección del año activo. */
    public function docentesDeSeccion(int $seccionId): array
    {
        return $this->query("
            SELECT DISTINCT ca.docente_id AS id
            FROM cargas_academicas ca
            INNER JOIN anios_academicos aa ON aa.id = ca.anio_id AND aa.estado = 'activo'
            WHERE ca.seccion_id = ? AND ca.estado = 'activa'
        ", [$seccionId]);
    }

    /** Bandeja del usuario: primero las no leídas, y dentro, las más nuevas. */
    public function paraUsuario(int $usuarioId, int $limite = 100): array
    {
        return $this->query("
            SELECT
                n.id, n.tipo, n.origen, n.titulo, n.mensaje, n.enlace,
                n.leida_en, n.created_at,
                TRIM(CONCAT(p.apellido_paterno, ' ', p.nombres)) AS emisor
            FROM notificaciones n
            LEFT JOIN usuarios u ON u.id = n.emisor_id
            LEFT JOIN personas p ON p.id = u.persona_id
            WHERE n.usuario_id = ?
            ORDER BY (n.leida_en IS NULL) DESC, n.created_at DESC
            LIMIT " . (int) $limite . "
        ", [$usuarioId]);
    }

    /**
     * Cuántas no leídas tiene el usuario. Es la consulta que se ejecuta en CADA
     * página (alimenta la campana de la barra), así que va servida por
     * `idx_bandeja (usuario_id, leida_en, created_at)`.
     */
    public function contarNoLeidas(int $usuarioId): int
    {
        $fila = $this->queryOne("
            SELECT COUNT(*) AS n
            FROM notificaciones
            WHERE usuario_id = ? AND leida_en IS NULL
        ", [$usuarioId]);

        return (int) ($fila['n'] ?? 0);
    }

    /**
     * Marca una notificación como leída. Filtra por `usuario_id` a propósito:
     * nadie puede marcar la de otro aunque adivine el id.
     */
    public function marcarLeida(int $id, int $usuarioId): bool
    {
        return $this->execute("
            UPDATE notificaciones
            SET leida_en = NOW()
            WHERE id = ? AND usuario_id = ? AND leida_en IS NULL
        ", [$id, $usuarioId]);
    }

    /** Marca como leídas todas las del usuario. */
    public function marcarTodasLeidas(int $usuarioId): bool
    {
        return $this->execute("
            UPDATE notificaciones
            SET leida_en = NOW()
            WHERE usuario_id = ? AND leida_en IS NULL
        ", [$usuarioId]);
    }
}
