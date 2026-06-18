<?php
// Esta vista usa el layout 'auth' (sin navbar ni sidebar)
\Core\View::setLayout('auth');
?>

<div class="login-box">

    <div class="login-box__header">
        <h2 class="login-box__title">Bienvenido</h2>
        <p class="login-box__subtitle">Ingresa tus credenciales para continuar</p>
    </div>

    <!-- Alertas de sesión -->
    <?php if ($flash_error): ?>
        <div class="alert alert--error">
            <?= e($flash_error) ?>
        </div>
    <?php endif; ?>

    <?php if ($flash_success): ?>
        <div class="alert alert--success">
            <?= e($flash_success) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['timeout'])): ?>
        <div class="alert alert--warning">
            Tu sesión cerró por inactividad. Vuelve a ingresar.
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <form class="login-form" method="POST" action="<?= url('login/procesar') ?>" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" for="dni">Número de DNI</label>
            <div class="input-wrapper">
                <span class="input-icon">
                        <img src="<?= url('assets/icons/user.svg') ?>" alt="Usuario">
                </span>
                <input
                    class="form-input <?= isset($errores['dni']) ? 'form-input--error' : '' ?>"
                    type="text"
                    id="dni"
                    name="dni"
                    value="<?= e($dni_previo ?? '') ?>"
                    placeholder="Ingresa tu DNI"
                    maxlength="8"
                    pattern="[0-9]{8}"
                    inputmode="numeric"
                    autocomplete="username"
                    autofocus
                    required
                >
            </div>
            <?php if (isset($errores['dni'])): ?>
                <span class="form-error"><?= e($errores['dni']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Contraseña</label>
            <div class="input-wrapper">
                <span class="input-icon">
                    <img src="<?= url('assets/icons/pass.svg') ?>" alt="Contraseña">
                </span>
                <input
                    class="form-input <?= isset($errores['password']) ? 'form-input--error' : '' ?>"
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Ingresa tu contraseña"
                    autocomplete="current-password"
                    required
                >
                <button type="button" class="input-toggle-pass"
                    aria-label="Mostrar contraseña"
                    data-icon-show="<?= url('assets/icons/eye_open.svg') ?>"
                    data-icon-hide="<?= url('assets/icons/eye_close.svg') ?>">
                    <span class="eye-icon">
                        <img id="eye-icon-img" src="<?= url('assets/icons/eye_open.svg') ?>" alt="Mostrar contraseña">
                    </span>
                </button>
            </div>
            <?php if (isset($errores['password'])): ?>
                <span class="form-error"><?= e($errores['password']) ?></span>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-login" id="btn-login">
            <span class="btn-login__text">Iniciar Sesión</span>
            <span class="btn-login__spinner" hidden><span class="btn-icon btn-icon--wait" aria-hidden="true"></span></span>
        </button>

    </form>

    <div class="login-box__footer">
        <p class="login-box__help">
            ¿Olvidaste tu contraseña?<br>
            <span class="login-box__help-contact">Comunícate con el personal de Registro Académico.</span>
        </p>
    </div>

</div>
