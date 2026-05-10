<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;
use Core\Session;

class UsuarioController extends BaseController
{
    private UsuarioModel $model;

    public function __construct()
    {
        $this->requireRole('admin');
        $this->model = new UsuarioModel();
    }

    // GET /admin/usuarios
    public function index(): void
    {
        $this->view('admin/usuarios/index', [
            'titulo'   => 'Gestión de Usuarios',
            'usuarios' => $this->model->listarTodos(),
        ]);
    }

    // GET /admin/usuarios/crear
    public function create(): void
    {
        $this->view('admin/usuarios/crear', [
            'titulo' => 'Nuevo Usuario',
            'roles'  => $this->model->listarRoles(),
        ]);
    }

    // POST /admin/usuarios/crear
    public function store(): void
    {
        $this->validateCsrf();

        $datos = $this->recogerInput();
        $error = $this->validar($datos);

        if (!$error && $this->model->existeDni($datos['dni'])) {
            $error = 'Ya existe un usuario registrado con ese DNI.';
        }

        if ($error) {
            $this->redirectWithError(url('admin/usuarios/crear'), $error);
        }

        try {
            $this->model->crearConPersona(
                $this->datosPersona($datos),
                $datos['password'],
                (int) $datos['rol_id']
            );
        } catch (\Exception $e) {
            log_error('Error creando usuario', ['msg' => $e->getMessage()]);
            $this->redirectWithError(url('admin/usuarios/crear'), 'Error al crear el usuario. Verifica que el DNI no esté duplicado.');
        }

        $this->redirectWithSuccess(url('admin/usuarios'), 'Usuario creado correctamente.');
    }

    // GET /admin/usuarios/{id}/editar
    public function edit(int $id): void
    {
        $usuario = $this->model->findById($id);
        if (!$usuario) {
            $this->redirectWithError(url('admin/usuarios'), 'Usuario no encontrado.');
        }

        $this->view('admin/usuarios/editar', [
            'titulo'  => 'Editar Usuario',
            'usuario' => $usuario,
            'roles'   => $this->model->listarRoles(),
        ]);
    }

    // POST /admin/usuarios/{id}/editar
    public function update(int $id): void
    {
        $this->validateCsrf();

        $usuario = $this->model->findById($id);
        if (!$usuario) {
            $this->redirectWithError(url('admin/usuarios'), 'Usuario no encontrado.');
        }

        $datos = $this->recogerInput();
        $error = $this->validar($datos, esEdicion: true);

        if (!$error && $this->model->existeDni($datos['dni'], $id)) {
            $error = 'Ya existe otro usuario con ese DNI.';
        }

        // No permitir cambiar el propio rol si es el único admin activo
        $authId = (int) (Session::user()['id'] ?? 0);
        if ($authId === $id && (int) $datos['rol_id'] !== (int) $usuario['rol_id']) {
            if ($this->model->contarPorRolCodigo('admin') <= 1) {
                $error = 'No puedes cambiar tu propio rol: eres el único administrador activo.';
            }
        }

        if ($error) {
            $this->redirectWithError(url("admin/usuarios/{$id}/editar"), $error);
        }

        try {
            $this->model->actualizarConPersona(
                $id,
                (int) $usuario['persona_id'],
                $this->datosPersona($datos),
                (int) $datos['rol_id'],
                $datos['password'] !== '' ? $datos['password'] : null
            );
        } catch (\Exception $e) {
            log_error('Error actualizando usuario', ['id' => $id, 'msg' => $e->getMessage()]);
            $this->redirectWithError(url("admin/usuarios/{$id}/editar"), 'Error al actualizar el usuario.');
        }

        $this->redirectWithSuccess(url('admin/usuarios'), 'Usuario actualizado correctamente.');
    }

    // POST /admin/usuarios/{id}/estado
    public function toggleEstado(int $id): void
    {
        $this->validateCsrf();

        if ((int) (Session::user()['id'] ?? 0) === $id) {
            $this->redirectWithError(url('admin/usuarios'), 'No puedes desactivar tu propia cuenta.');
        }

        $usuario = $this->model->findById($id);
        if (!$usuario) {
            $this->redirectWithError(url('admin/usuarios'), 'Usuario no encontrado.');
        }

        // No desactivar al último admin activo
        if ($usuario['rol_codigo'] === 'admin' && $usuario['estado'] === 'activo') {
            if ($this->model->contarPorRolCodigo('admin') <= 1) {
                $this->redirectWithError(url('admin/usuarios'), 'No puedes desactivar al único administrador activo.');
            }
        }

        $this->model->toggleEstado($id);

        $nuevoEstado = $usuario['estado'] === 'activo' ? 'desactivado' : 'activado';
        $this->redirectWithSuccess(url('admin/usuarios'), "Usuario {$nuevoEstado} correctamente.");
    }

    // ── Métodos privados ──────────────────────────────────────────

    private function recogerInput(): array
    {
        return [
            'dni'              => trim($this->input('dni', '')),
            'apellido_paterno' => trim($this->input('apellido_paterno', '')),
            'apellido_materno' => trim($this->input('apellido_materno', '')),
            'nombres'          => trim($this->input('nombres', '')),
            'correo'           => trim($this->input('correo', '')),
            'telefono'         => trim($this->input('telefono', '')),
            'sexo'             => $this->input('sexo', ''),
            'rol_id'           => $this->input('rol_id', 0),
            'password'         => $this->input('password', ''),
            'password_confirm' => $this->input('password_confirm', ''),
        ];
    }

    private function datosPersona(array $d): array
    {
        return [
            'dni'              => $d['dni'],
            'apellido_paterno' => mb_strtoupper($d['apellido_paterno']),
            'apellido_materno' => mb_strtoupper($d['apellido_materno']),
            'nombres'          => mb_strtoupper($d['nombres']),
            'correo'           => $d['correo'] !== '' ? $d['correo'] : null,
            'telefono'         => $d['telefono'] !== '' ? $d['telefono'] : null,
            'sexo'             => $d['sexo'],
        ];
    }

    private function validar(array $d, bool $esEdicion = false): ?string
    {
        if (!ctype_digit($d['dni']) || strlen($d['dni']) !== 8) {
            return 'El DNI debe tener exactamente 8 dígitos numéricos.';
        }
        if (empty($d['apellido_paterno'])) return 'El apellido paterno es requerido.';
        if (empty($d['apellido_materno'])) return 'El apellido materno es requerido.';
        if (empty($d['nombres']))          return 'Los nombres son requeridos.';
        if (!in_array($d['sexo'], ['M', 'F'])) return 'El sexo es requerido.';
        if ((int) $d['rol_id'] <= 0)       return 'Debe seleccionar un rol.';

        $pass = $d['password'];
        $conf = $d['password_confirm'];

        if (!$esEdicion && strlen($pass) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($esEdicion && $pass !== '' && strlen($pass) < 8) {
            return 'La nueva contraseña debe tener al menos 8 caracteres.';
        }
        if ($pass !== $conf) {
            return 'Las contraseñas no coinciden.';
        }

        return null;
    }
}
