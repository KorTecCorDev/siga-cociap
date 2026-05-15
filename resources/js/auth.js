/**
 * auth.js — SIGA-COCIAP
 * Comportamiento del módulo de autenticación.
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── Toggle mostrar/ocultar contraseña ────────────────────
    const toggleBtn = document.querySelector('.input-toggle-pass');
    const passInput = document.getElementById('password');

    if (toggleBtn && passInput) {
        const eyeImg = document.getElementById('eye-icon-img');

        toggleBtn.addEventListener('click', () => {
            const isPassword = passInput.type === 'password';

            // Cambiar tipo del input
            passInput.type = isPassword ? 'text' : 'password';

            // Cambiar ícono según estado
            eyeImg.src = isPassword
                ? toggleBtn.dataset.iconHide
                : toggleBtn.dataset.iconShow;

            // Actualizar aria-label
            toggleBtn.setAttribute('aria-label',
                isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'
            );
        });
    }

    // ── Solo números en el campo DNI ─────────────────────────
    const dniInput = document.getElementById('dni');
    if (dniInput) {
        dniInput.addEventListener('input', () => {
            dniInput.value = dniInput.value.replace(/\D/g, '').slice(0, 8);
        });
    }

    // ── Feedback visual al enviar el formulario ───────────────
    const loginForm = document.querySelector('.login-form');
    const btnLogin  = document.getElementById('btn-login');

    if (loginForm && btnLogin) {
        loginForm.addEventListener('submit', (e) => {
            const dni      = dniInput?.value.trim() ?? '';
            const password = passInput?.value ?? '';

            // Validación mínima client-side antes de enviar
            if (dni.length !== 8 || password.length === 0) return;

            // Mostrar estado de carga
            const textSpan    = btnLogin.querySelector('.btn-login__text');
            const spinnerSpan = btnLogin.querySelector('.btn-login__spinner');

            btnLogin.disabled = true;
            if (textSpan)    textSpan.textContent = 'Verificando...';
            if (spinnerSpan) spinnerSpan.hidden = false;
        });
    }

    // ── Auto-cerrar alertas después de 6 segundos ────────────
    document.querySelectorAll('.alert--success, .alert--warning').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity .4s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 6000);
    });

});
