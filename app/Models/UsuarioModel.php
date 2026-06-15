<?php

namespace App\Models;

/**
 * UsuarioModel
 * Gestión de usuarios del sistema SIGA-COCIAP.
 */
class UsuarioModel extends BaseModel
{
    protected string $table = 'usuarios';

    /**
     * Busca un usuario por el DNI de su persona asociada.
     * Retorna todos los datos necesarios para la sesión.
     */
    public function findByDni(string $dni): ?array
    {
        return $this->queryOne("
            SELECT
                u.id,
                u.password_hash,
                u.estado,
                u.sesion_token,
                p.id        AS persona_id,
                p.dni,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno,
                p.correo,
                p.sexo,
                r.id        AS rol_id,
                r.nombre    AS rol_nombre,
                r.codigo    AS rol_codigo
            FROM usuarios u
            INNER JOIN personas p ON p.id = u.persona_id
            INNER JOIN roles r    ON r.id = u.rol_id
            WHERE p.dni = ?
            LIMIT 1
        ", [$dni]);
    }

    /**
     * Registra el token de sesión activa y el último acceso.
     * Garantiza sesión única: si otro dispositivo inicia sesión,
     * el token del anterior queda inválido.
     */
    public function registrarAcceso(int $id, string $token): void
    {
        $this->execute("
            UPDATE usuarios
            SET sesion_token = ?,
                ultimo_acceso = NOW()
            WHERE id = ?
        ", [$token, $id]);
    }

    /**
     * Invalida el token de sesión al hacer logout.
     */
    public function cerrarSesion(int $id): void
    {
        $this->execute("
            UPDATE usuarios
            SET sesion_token = NULL
            WHERE id = ?
        ", [$id]);
    }

    /**
     * Verifica que el token guardado en sesión coincida con el de la BD.
     * Protege contra sesiones duplicadas.
     */
    public function tokenValido(int $id, string $token): bool
    {
        $resultado = $this->queryOne("
            SELECT sesion_token FROM usuarios WHERE id = ? LIMIT 1
        ", [$id]);

        return $resultado && hash_equals($resultado['sesion_token'] ?? '', $token);
    }

    /**
     * Cambia la contraseña de un usuario (solo Administrador / Registro Académico).
     */
    public function cambiarPassword(int $id, string $nuevaPassword): bool
    {
        $hash = password_hash($nuevaPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        return $this->update($id, ['password_hash' => $hash]);
    }

    /**
     * Lista todos los usuarios con datos de persona y rol.
     */
    public function listarTodos(): array
    {
        return $this->query("
            SELECT
                u.id,
                u.estado,
                u.ultimo_acceso,
                p.dni,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno,
                p.correo,
                p.telefono,
                r.nombre AS rol_nombre,
                r.codigo AS rol_codigo
            FROM usuarios u
            INNER JOIN personas p ON p.id = u.persona_id
            INNER JOIN roles r    ON r.id = u.rol_id
            ORDER BY r.id, p.apellido_paterno, p.apellido_materno
        ");
    }

    /**
     * Nombre completo formateado: APELLIDOS, Nombres
     */
    public static function nombreCompleto(array $usuario): string
    {
        return strtoupper($usuario['apellido_paterno'] . ' ' . $usuario['apellido_materno'])
            . ', ' . ucwords(strtolower($usuario['nombres']));
    }

    // ── Métodos para gestión CRUD (Admin) ────────────────────────

    public function findById(int $id): ?array
    {
        return $this->queryOne("
            SELECT
                u.id,
                u.rol_id,
                u.estado,
                u.ultimo_acceso,
                p.id               AS persona_id,
                p.dni,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno,
                p.correo,
                p.telefono,
                p.sexo,
                r.nombre           AS rol_nombre,
                r.codigo           AS rol_codigo
            FROM usuarios u
            INNER JOIN personas p ON p.id = u.persona_id
            INNER JOIN roles r    ON r.id = u.rol_id
            WHERE u.id = ?
            LIMIT 1
        ", [$id]);
    }

    public function listarRoles(): array
    {
        return $this->query("SELECT id, nombre, codigo FROM roles ORDER BY id");
    }

    public function existeDni(string $dni, ?int $excluirUsuarioId = null): bool
    {
        if ($excluirUsuarioId !== null) {
            $r = $this->queryOne("
                SELECT u.id FROM usuarios u
                INNER JOIN personas p ON p.id = u.persona_id
                WHERE p.dni = ? AND u.id != ?
                LIMIT 1
            ", [$dni, $excluirUsuarioId]);
        } else {
            $r = $this->queryOne("SELECT id FROM personas WHERE dni = ? LIMIT 1", [$dni]);
        }
        return $r !== null;
    }

    public function crearConPersona(array $datosPersona, string $password, int $rolId): int
    {
        $this->beginTransaction();
        try {
            $cols  = implode(', ', array_keys($datosPersona));
            $ph    = implode(', ', array_fill(0, count($datosPersona), '?'));
            $this->execute("INSERT INTO personas ({$cols}) VALUES ({$ph})", array_values($datosPersona));
            $personaId = (int) $this->db->lastInsertId();

            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $this->execute(
                "INSERT INTO usuarios (persona_id, rol_id, password_hash, estado) VALUES (?, ?, ?, 'activo')",
                [$personaId, $rolId, $hash]
            );
            $usuarioId = (int) $this->db->lastInsertId();

            $this->commit();
            return $usuarioId;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function actualizarConPersona(
        int $usuarioId,
        int $personaId,
        array $datosPersona,
        int $rolId,
        ?string $password
    ): void {
        $this->beginTransaction();
        try {
            $set = implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($datosPersona)));
            $this->execute(
                "UPDATE personas SET {$set} WHERE id = ?",
                [...array_values($datosPersona), $personaId]
            );
            $this->execute("UPDATE usuarios SET rol_id = ? WHERE id = ?", [$rolId, $usuarioId]);

            if ($password !== null && $password !== '') {
                $this->cambiarPassword($usuarioId, $password);
            }

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function toggleEstado(int $id): void
    {
        $this->execute("
            UPDATE usuarios
            SET estado = IF(estado = 'activo', 'inactivo', 'activo')
            WHERE id = ?
        ", [$id]);
    }

    public function contarPorRolCodigo(string $codigoRol): int
    {
        $r = $this->queryOne("
            SELECT COUNT(*) AS total
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE r.codigo = ? AND u.estado = 'activo'
        ", [$codigoRol]);
        return (int) ($r['total'] ?? 0);
    }
}
