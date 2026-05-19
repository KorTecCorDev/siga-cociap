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

    // ── Generar QR (librería local sin internet) ───────────────
    var doc         = document.getElementById('boleta-documento');
    var qrContainer = document.getElementById('qr-container');

    if (qrContainer && doc) {
        var url = (doc.dataset.url || '').trim();
        if (url) {
            if (typeof QRCode !== 'undefined') {
                new QRCode(qrContainer, {
                    text:         url,
                    width:        120,
                    height:       120,
                    correctLevel: QRCode.CorrectLevel.M
                });
            } else {
                var img      = document.createElement('img');
                img.src      = 'https://chart.googleapis.com/chart?chs=120x120&cht=qr&chl='
                             + encodeURIComponent(url) + '&choe=UTF-8';
                img.alt      = 'Código QR de verificación';
                img.className = 'bd-qr__img';
                img.onerror  = function () {
                    qrContainer.closest('.bd-qr').classList.add('bd-qr--offline');
                    qrContainer.remove();
                };
                qrContainer.appendChild(img);
            }
        }
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

})();
