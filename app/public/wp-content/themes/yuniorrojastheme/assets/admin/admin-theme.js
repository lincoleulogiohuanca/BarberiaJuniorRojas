/**
 * Interruptor claro/oscuro del admin Junior Rojas.
 */
(function () {
  'use strict';

  var cfg = window.yuniorrojasAdminTheme || {};
  if (!cfg.ajaxUrl || !cfg.nonce) {
    return;
  }

  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function getAnchor() {
    return qs('#wp-admin-bar-jr-admin-theme-toggle > a');
  }

  function applyTheme(theme) {
    var body = document.body;
    if (!body) {
      return;
    }
    body.classList.remove('jr-admin-theme-dark', 'jr-admin-theme-light');
    body.classList.add(theme === 'dark' ? 'jr-admin-theme-dark' : 'jr-admin-theme-light');

    var a = getAnchor();
    if (!a || !cfg.i18n) {
      return;
    }
    var icon = a.querySelector('.ab-icon');
    var label = a.querySelector('.ab-label');
    if (theme === 'dark') {
      if (icon) {
        icon.className = 'ab-icon dashicons dashicons-lightbulb';
      }
      if (label) {
        label.textContent = cfg.i18n.toLight || 'Claro';
      }
      a.setAttribute('title', cfg.i18n.titleLight || 'Cambiar a tema claro');
    } else {
      if (icon) {
        icon.className = 'ab-icon dashicons dashicons-admin-appearance';
      }
      if (label) {
        label.textContent = cfg.i18n.toDark || 'Oscuro';
      }
      a.setAttribute('title', cfg.i18n.titleDark || 'Cambiar a tema oscuro');
    }
  }

  function saveTheme(theme) {
    var body = new FormData();
    body.append('action', 'yuniorrojas_admin_theme_save');
    body.append('nonce', cfg.nonce);
    body.append('theme', theme);

    return fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: body,
    }).then(function (r) {
      return r.json();
    });
  }

  function onToggle(e) {
    e.preventDefault();
    e.stopPropagation();

    var next = document.body.classList.contains('jr-admin-theme-dark') ? 'light' : 'dark';
    applyTheme(next);
    cfg.theme = next;

    saveTheme(next).catch(function () {
      /* La preferencia visual ya cambió; el meta se reintenta al recargar o al click de nuevo. */
    });
  }

  function bind() {
    var node = qs('#wp-admin-bar-jr-admin-theme-toggle');
    if (!node) {
      return;
    }
    node.addEventListener('click', onToggle, true);
    var a = getAnchor();
    if (a) {
      a.addEventListener('click', onToggle, true);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();
