/**
 * EventoSaaS — app.js
 * JavaScript global del sistema. Sin dependencias externas.
 * @version 1.0.0
 */

/* ── Flash messages auto-dismiss ─────────────────────────────────────────── */
(function () {
  'use strict';

  document.querySelectorAll('.alert').forEach(function (alert) {
    // Auto-dismiss para success/info después de 4s
    if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
      setTimeout(function () {
        alert.style.transition = 'opacity .4s ease';
        alert.style.opacity = '0';
        setTimeout(function () { alert.remove(); }, 400);
      }, 4000);
    }
  });
})();

/* ── Sidebar mobile toggle ───────────────────────────────────────────────── */
(function () {
  const sidebar = document.getElementById('sidebar');
  if (!sidebar) return;

  // Cerrar sidebar al hacer click fuera (en móvil)
  document.addEventListener('click', function (e) {
    if (
      sidebar.classList.contains('open') &&
      !sidebar.contains(e.target) &&
      !e.target.closest('.sidebar-toggle')
    ) {
      sidebar.classList.remove('open');
    }
  });
})();

/* ── Confirm delete forms ────────────────────────────────────────────────── */
(function () {
  // Los formularios con data-confirm piden confirmación antes de enviar
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      const msg = form.dataset.confirm || '¿Estás seguro de que deseas realizar esta acción?';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });
})();

/* ── Method override helper ─────────────────────────────────────────────── */
// Ya gestionado en PHP con el campo _method, pero este helper
// garantiza que el valor se envíe correctamente.
(function () {
  document.querySelectorAll('input[name="_method"]').forEach(function (input) {
    const form = input.closest('form');
    if (form && form.method.toLowerCase() !== 'post') {
      form.method = 'POST';
    }
  });
})();

/* ── Table row click ─────────────────────────────────────────────────────── */
(function () {
  // Filas con data-href se hacen clickeables completamente
  document.querySelectorAll('tr[data-href]').forEach(function (row) {
    row.style.cursor = 'pointer';
    row.addEventListener('click', function (e) {
      // No navegar si el click fue en un botón/enlace/form
      if (e.target.closest('a, button, form, input, select, textarea')) return;
      window.location.href = row.dataset.href;
    });
  });
})();

/* ── Loading state on forms ─────────────────────────────────────────────── */
(function () {
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      const btn = form.querySelector('button[type="submit"]:not([data-no-loading])');
      if (btn && !btn.disabled) {
        btn.dataset.originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Procesando...';

        // Safety: re-enable after 10s in case something goes wrong
        setTimeout(function () {
          if (btn.disabled) {
            btn.disabled = false;
            btn.textContent = btn.dataset.originalText || 'Enviar';
          }
        }, 10000);
      }
    });
  });
})();

/* ── QR Auto-submit (cuando el scanner envía un código largo) ────────────── */
(function () {
  const codeInput = document.getElementById('checkin-code');
  if (!codeInput) return;

  // Para lectores físicos de QR que envían un Enter al final
  codeInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      const form = document.getElementById('checkin-form');
      if (form && codeInput.value.trim().length > 0) {
        form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
      }
    }
  });
})();

/* ── Tooltip simple ─────────────────────────────────────────────────────── */
(function () {
  // Elementos con data-tooltip muestran un tooltip al hover
  const style = document.createElement('style');
  style.textContent = `
    [data-tooltip] { position: relative; }
    [data-tooltip]::after {
      content: attr(data-tooltip);
      position: absolute;
      bottom: calc(100% + 6px);
      left: 50%;
      transform: translateX(-50%);
      background: #1E293B;
      color: #F1F5F9;
      font-size: 12px;
      padding: 5px 10px;
      border-radius: 6px;
      white-space: nowrap;
      pointer-events: none;
      opacity: 0;
      transition: opacity .15s;
      z-index: 9999;
    }
    [data-tooltip]:hover::after { opacity: 1; }
  `;
  document.head.appendChild(style);
})();

/* ── Copy to clipboard ──────────────────────────────────────────────────── */
window.copyToClipboard = function (text, btn) {
  navigator.clipboard.writeText(text).then(function () {
    const orig = btn ? btn.textContent : '';
    if (btn) {
      btn.textContent = '✅ Copiado';
      setTimeout(function () { btn.textContent = orig; }, 2000);
    }
  }).catch(function () {
    alert('No se pudo copiar. Copia manualmente: ' + text);
  });
};

/* ── Date formatter (helpers de UI) ─────────────────────────────────────── */
window.EventoSaaS = {
  version: '1.0.0',

  formatTime: function (dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
  },

  formatDate: function (dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
  },

  /** Escapa HTML para uso seguro en innerHTML */
  escHtml: function (str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }
};

console.info('EventoSaaS v' + EventoSaaS.version + ' loaded.');
