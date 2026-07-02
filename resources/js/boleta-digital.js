/**
 * boleta-digital.js — Vista digital de boleta SIGA-COCIAP
 * Acordeones de área, QR, descarga PDF, collapse móvil.
 */
(function () {
    'use strict';

    // ── Acordeones de área ─────────────────────────────────────
    document.querySelectorAll('[data-area-toggle]').forEach(function (btn) {
        var bodyId = btn.getAttribute('aria-controls');
        var body   = document.getElementById(bodyId);
        if (!body) return;

        btn.addEventListener('click', function () {
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', String(!expanded));
            if (expanded) {
                body.setAttribute('hidden', '');
            } else {
                body.removeAttribute('hidden');
            }
        });
    });

    // ── Colapsar áreas en móvil al cargar ──────────────────────
    if (window.innerWidth < 640) {
        document.querySelectorAll('[data-area-toggle]').forEach(function (btn) {
            var bodyId = btn.getAttribute('aria-controls');
            var body   = document.getElementById(bodyId);
            if (!body) return;
            btn.setAttribute('aria-expanded', 'false');
            body.setAttribute('hidden', '');
        });
    }

    // ── Botón PDF ──────────────────────────────────────────────
    var btnPdf = document.getElementById('btn-pdf');
    if (btnPdf) {
        btnPdf.addEventListener('click', function () {
            var toast = document.createElement('div');
            toast.className   = 'bd-toast';
            toast.textContent = 'En el diálogo de impresión selecciona "Guardar como PDF"';
            document.body.appendChild(toast);

            // Forzar reflow para que la transición CSS funcione
            void toast.offsetWidth;
            toast.classList.add('bd-toast--visible');

            // Expandir todas las áreas antes de imprimir
            document.querySelectorAll('[data-area-toggle]').forEach(function (btn) {
                var bodyId = btn.getAttribute('aria-controls');
                var body   = document.getElementById(bodyId);
                if (body) {
                    btn.setAttribute('aria-expanded', 'true');
                    body.removeAttribute('hidden');
                }
            });

            setTimeout(function () {
                window.print();
                setTimeout(function () {
                    toast.classList.remove('bd-toast--visible');
                    setTimeout(function () { toast.remove(); }, 300);
                }, 600);
            }, 900);
        });
    }

    // ── QR de acceso permanente ───────────────────────────────
    var qrContainer = document.getElementById('bd-qr-code');
    if (qrContainer && typeof QRCode !== 'undefined') {
        var qrUrl = qrContainer.getAttribute('data-url');
        if (qrUrl) {
            new QRCode(qrContainer, {
                text: qrUrl,
                width: 80,
                height: 80,
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    }

    // ── Expandir todo al imprimir (Ctrl+P nativo) ─────────────
    window.addEventListener('beforeprint', function () {
        document.querySelectorAll('[data-area-toggle]').forEach(function (btn) {
            var bodyId = btn.getAttribute('aria-controls');
            var body   = document.getElementById(bodyId);
            if (body) {
                btn.setAttribute('aria-expanded', 'true');
                body.removeAttribute('hidden');
            }
        });
    });

    // ── Boton "Cerrar" del documento ──────────────────────────
    // La boleta digital se abre en ventana nueva por script (window.open en
    // app.js), asi que window.close() la cierra de forma fiable y se vuelve a la
    // ventana de origen. Fallback por si NO fue abierta por script (pestana
    // abierta a mano) y close() queda bloqueado: historial -> referrer -> inicio.
    var cerrar = document.getElementById('bdCerrar');
    if (cerrar) {
        cerrar.addEventListener('click', function (e) {
            e.preventDefault();

            window.close();

            setTimeout(function () {
                if (window.history.length > 1) {
                    window.history.back();
                    return;
                }
                var ref = document.referrer;
                if (ref) {
                    try {
                        if (new URL(ref).origin === window.location.origin) {
                            window.location.href = ref;
                            return;
                        }
                    } catch (err) { /* referrer invalido: seguir al fallback */ }
                }
                var meta = document.querySelector('meta[name="base-url"]');
                window.location.href = (meta && meta.getAttribute('content')) || '/';
            }, 120);
        });
    }

})();
