<?php
/**
 * Vista pública — formulario de consulta por código de acceso.
 * Layout: digital (mobile-first, sin navbar).
 *
 * @var string      $institucion
 * @var string|null $error
 * @var string|null $codigo
 */
?>

<div class="bpf-wrap">

    <header class="bpf-header">
        <img src="<?= url('assets/img/logo_cociap.png') ?>"
             alt="Logo COCIAP"
             class="bpf-header__logo">
        <h1 class="bpf-header__colegio"><?= e($institucion ?? '') ?></h1>
        <p class="bpf-header__ugel">MINEDU · DRE Áncash · UGEL Huaraz</p>
        <p class="bpf-header__titulo">Consulta de Boleta de Calificaciones</p>
    </header>

    <main class="bpf-main">
        <div class="bpf-card">
            <p class="bpf-card__desc">
                Ingresa el <strong>código de acceso</strong> que encontrarás en el
                comprobante entregado por el colegio.
            </p>

            <?php if (!empty($error)): ?>
            <div class="bpf-error" role="alert">
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <form class="bpf-form"
                  method="POST"
                  action="<?= url('boleta-publica/consultar') ?>"
                  novalidate>
                <?= csrf_field() ?>

                <label class="bpf-form__label" for="codigo_acceso">
                    Código de acceso
                </label>
                <input class="bpf-form__input <?= !empty($error) ? 'bpf-form__input--error' : '' ?>"
                       type="text"
                       id="codigo_acceso"
                       name="codigo_acceso"
                       value="<?= e($codigo ?? '') ?>"
                       placeholder="COCIAP-2026-B1-XXXXXX"
                       autocomplete="off"
                       autocapitalize="characters"
                       spellcheck="false"
                       maxlength="30"
                       required>

                <button class="bpf-form__btn" type="submit">
                    Consultar boleta →
                </button>
            </form>
        </div>

        <p class="bpf-ayuda">
            ¿Problemas con tu código? Acércate a la secretaría del colegio.
        </p>
    </main>

    <footer class="bpf-footer">
        <p>SIGA-COCIAP &mdash; Sistema de Gestión Académica</p>
    </footer>

</div>
