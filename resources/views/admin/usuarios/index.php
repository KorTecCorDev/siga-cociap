<?php
/**
 * @var array  $usuarios
 * @var array  $auth_user
 */

$badgeRol = fn(string $codigo): string => match($codigo) {
    'admin'                             => 'badge--error',
    'director_general', 'director_ebr'  => 'badge--warning',
    default                             => 'badge--info',
};
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Gestión de Usuarios</h1>
        <p class="page-subtitle">
            <?= count($usuarios) ?> usuario<?= count($usuarios) !== 1 ? 's' : '' ?> registrado<?= count($usuarios) !== 1 ? 's' : '' ?>
        </p>
    </div>
    <a href="<?= url('admin/usuarios/crear') ?>" class="btn btn--primary">
        + Nuevo usuario
    </a>
</div>

<?php if (empty($usuarios)): ?>
    <div class="card">
        <div class="card__body">
            <div class="empty-state">
                <p>No hay usuarios registrados todavía.</p>
            </div>
        </div>
    </div>
<?php else: ?>

<div class="card">
    <div class="tabla-notas-wrapper">
        <table class="tabla-notas">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>DNI</th>
                    <th>Rol</th>
                    <th class="text-center">Estado</th>
                    <th>Último acceso</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u):
                    $iniciales   = mb_strtoupper(mb_substr($u['apellido_paterno'], 0, 1) . mb_substr($u['nombres'], 0, 1));
                    $esYo        = (int) $u['id'] === (int) ($auth_user['id'] ?? 0);
                    $estaActivo  = $u['estado'] === 'activo';
                    $labelEstado = $estaActivo ? 'Activo' : 'Inactivo';
                    $badgeEstado = $estaActivo ? 'badge--activo' : 'badge--error';
                    $nombre      = \App\Models\UsuarioModel::nombreCompleto($u);
                ?>
                <tr class="<?= !$estaActivo ? 'fila-inactiva' : '' ?>">

                    <td>
                        <div class="td-usuario">
                            <div class="usuario-avatar usuario-avatar--<?= e($u['rol_codigo']) ?>">
                                <?= e($iniciales) ?>
                            </div>
                            <div>
                                <div class="td-usuario__nombre"><?= e($nombre) ?></div>
                                <?php if ($u['correo']): ?>
                                    <div class="td-usuario__sub"><?= e($u['correo']) ?></div>
                                <?php endif; ?>
                                <?php if ($esYo): ?>
                                    <div class="td-usuario__sub">(tú)</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>

                    <td class="text-sm"><?= e($u['dni']) ?></td>

                    <td>
                        <span class="badge <?= $badgeRol($u['rol_codigo']) ?>">
                            <?= e($u['rol_nombre']) ?>
                        </span>
                    </td>

                    <td class="text-center">
                        <span class="badge <?= $badgeEstado ?>"><?= $labelEstado ?></span>
                    </td>

                    <td class="text-sm text-muted">
                        <?= $u['ultimo_acceso'] ? fecha_es($u['ultimo_acceso']) : '—' ?>
                    </td>

                    <td>
                        <div class="td-acciones">
                            <a href="<?= url('admin/usuarios/' . $u['id'] . '/editar') ?>"
                               class="btn btn--secondary btn--sm">
                                Editar
                            </a>
                            <?php if (!$esYo): ?>
                                <form method="POST"
                                      action="<?= url('admin/usuarios/' . $u['id'] . '/estado') ?>"
                                      onsubmit="return confirm('¿<?= $estaActivo ? 'Desactivar' : 'Activar' ?> a <?= e(addslashes($nombre)) ?>?')">
                                    <?= csrf_field() ?>
                                    <button type="submit"
                                            class="btn btn--sm <?= $estaActivo ? 'btn--danger' : 'btn--secondary' ?>">
                                        <?= $estaActivo ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
