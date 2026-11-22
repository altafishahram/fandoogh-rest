/**
 * Fondogh Online Menu - Design System Studio Bridge
 * Handles: live preview reactive updates, save/reset AJAX, device/theme switching.
 * No jQuery dependency. Runs inside the studio page (no iframe separation).
 *
 * Fix: applyTokensToPreview no longer early-returns on missing iframe.
 *      Tokens are applied to document.documentElement AND #rmm-preview-root.
 *      If an iframe #rmm-preview-frame exists (future), it is also handled defensively.
 */

(function () {
  'use strict';

  /* ── Config injected by PHP ──────────────────────────── */
  const CFG = window.RMM_STUDIO_CONFIG || {};
  const AJAX_URL    = CFG.ajaxUrl   || '';
  const SAVE_NONCE  = CFG.saveNonce  || '';
  const RESET_NONCE = CFG.resetNonce || '';

  /* ── State ───────────────────────────────────────────── */
  let currentSettings = CFG.settings ? deepClone(CFG.settings) : {};
  let defaults        = CFG.defaults ? deepClone(CFG.defaults) : {};
  let activeMode      = currentSettings.activeMode || 'light';
  let isDirty         = false;

  /* ── Helpers ─────────────────────────────────────────── */
  function deepClone(obj) {
    return JSON.parse(JSON.stringify(obj));
  }

  function tokenVar(key) {
    return '--rmm-ds-' + key.replace(/_/g, '-');
  }

  /**
   * Applies design-token CSS variables to the live preview.
   *
   * Strategy (no early-return):
   *  1. Always apply to document.documentElement so :root picks them up.
   *  2. If #rmm-preview-root exists (the in-page canvas), set directly on it.
   *  3. If an iframe #rmm-preview-frame exists (defensive, future-proof), try
   *     to reach its contentDocument with a try/catch for cross-origin safety.
   */
  function applyTokensToPreview(tokens) {
    const entries = Object.entries(tokens);

    // 1. Apply to :root of this document (always)
    entries.forEach(([key, val]) => {
      document.documentElement.style.setProperty(tokenVar(key), val);
    });

    // 2. Apply directly on the in-page preview root (if present)
    const previewRoot = document.getElementById('rmm-preview-root');
    if (previewRoot) {
      entries.forEach(([key, val]) => {
        previewRoot.style.setProperty(tokenVar(key), val);
      });
      applyPreviewAttributes(previewRoot, tokens);
    }

    // 3. Defensive: if an iframe exists, try to reach its document
    const iframe = document.getElementById('rmm-preview-frame');
    if (iframe) {
      try {
        if (iframe.contentDocument) {
          const iframeRoot = iframe.contentDocument.documentElement;
          entries.forEach(([key, val]) => {
            iframeRoot.style.setProperty(tokenVar(key), val);
          });
        }
      } catch (e) { /* cross-origin guard */ }
    }
  }

  /**
   * Enum tokens are not CSS variables: they switch a data-attribute on the
   * preview canvas so the preview reacts exactly like the front-end classes.
   */
  const ENUM_TOKENS = {
    card_layout: 'cardLayout',
    btn_style:   'btnStyle',
    cat_style:   'catStyle',
    cart_dock:   'cartDock'
  };

  function applyPreviewAttributes(previewRoot, tokens) {
    Object.keys(ENUM_TOKENS).forEach((key) => {
      if (tokens[key] !== undefined && tokens[key] !== null && tokens[key] !== '') {
        previewRoot.dataset[ENUM_TOKENS[key]] = String(tokens[key]);
      }
    });
  }

  function markDirty() {
    isDirty = true;
    const btn = document.getElementById('rmm-save-btn');
    if (btn) {
      btn.classList.add('rmm-s-dirty');
      btn.textContent = '💾 ذخیره تغییرات *';
    }
  }

  function markClean() {
    isDirty = false;
    const btn = document.getElementById('rmm-save-btn');
    if (btn) {
      btn.classList.remove('rmm-s-dirty');
      btn.textContent = '💾 ذخیره تغییرات';
    }
  }

  function showToast(message, type) {
    type = type || 'success';
    const toast = document.createElement('div');
    toast.className = 'rmm-s-toast rmm-s-toast--' + type;
    toast.textContent = message;
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('rmm-s-toast--show'));
    setTimeout(() => {
      toast.classList.remove('rmm-s-toast--show');
      setTimeout(() => toast.remove(), 400);
    }, 3000);
  }

  /* ── Control → Token Binding ─────────────────────────── */
  function bindControls() {
    document.querySelectorAll('[data-rmm-token]').forEach((ctrl) => {
      ctrl.addEventListener('input', onControlChange);
      ctrl.addEventListener('change', onControlChange);
    });
  }

  function onControlChange(e) {
    const ctrl     = e.currentTarget;
    const tokenKey = ctrl.dataset.rmmToken;
    const val      = ctrl.value;

    // Sync companion display (hex swatch ↔ text, range ↔ number)
    const companion = document.querySelector('[data-rmm-companion="' + tokenKey + '"]');
    if (companion && companion !== ctrl) {
      if (!(companion.type === 'color' && !/^#[0-9a-fA-F]{3,8}$/.test(String(val)))) {
        companion.value = val;
      }
    }

    // Update state (guard: keep both modes separate)
    if (!currentSettings[activeMode]) currentSettings[activeMode] = {};
    currentSettings[activeMode][tokenKey] = val;

    // Live preview — apply only current mode tokens
    const liveTokens = Object.assign({}, currentSettings[activeMode]);
    applyTokensToPreview(liveTokens);

    markDirty();
  }

  /* ── Populate Controls from Settings ────────────────── */
  function populateControls(mode) {
    const tokens = currentSettings[mode] || defaults[mode] || {};
    document.querySelectorAll('[data-rmm-token]').forEach((ctrl) => {
      const key = ctrl.dataset.rmmToken;
      if (tokens[key] !== undefined) {
        const raw = String(tokens[key]);
        const isHex = /^#[0-9a-fA-F]{3,8}$/.test(raw);

        // A native color input cannot hold rgba(); keep its swatch as-is.
        if (!(ctrl.type === 'color' && !isHex)) {
          ctrl.value = raw;
        }
        // Sync companion
        const companion = document.querySelector('[data-rmm-companion="' + key + '"]');
        if (companion && companion !== ctrl && !(companion.type === 'color' && !isHex)) {
          companion.value = raw;
        }
      }
    });
    const defaultThemeSelect = document.getElementById('rmm-default-theme-select');
    if (defaultThemeSelect) {
      defaultThemeSelect.value = currentSettings.default_theme || 'dark';
    }
    applyTokensToPreview(tokens);
  }

  /* ── Tab Switching (Sidebar component tabs) ──────────── */
  function switchSidebarTab(tabId) {
    // Hide all panels
    document.querySelectorAll('[data-rmm-panel]').forEach((panel) => {
      panel.style.display = 'none';
    });
    // Show target panel (inline block: the CSS default is none)
    const target = document.querySelector('[data-rmm-panel="' + tabId + '"]');
    if (target) target.style.display = 'block';

    // Update tab button states
    document.querySelectorAll('[data-rmm-sidebar-tab]').forEach((tab) => {
      const isActive = tab.dataset.rmmSidebarTab === tabId;
      tab.classList.toggle('rmm-s-sidebar-tab--active', isActive);
    });
  }

  /* ── Mode Switching (Light / Dark) ───────────────────── */
  // Layout/typography choices are structural: they should survive a theme
  // switch instead of snapping back to that mode's older value.
  const STRUCTURAL_TOKENS = [
    'card_layout', 'card_image_ratio', 'card_image_height', 'card_image_fit',
    'card_image_radius', 'card_image_list_size', 'btn_style', 'btn_pad_y', 'btn_pad_x',
    'cat_style', 'cat_gap', 'cat_icon', 'cart_dock',
    'lang_modal_radius', 'lang_modal_blur',
    'font_family', 'font_weight', 'title_size', 'desc_size', 'price_size',
    'radius_card', 'radius_btn', 'radius_input', 'pad_card', 'gap_grid'
  ];

  function switchMode(newMode) {
    const previousMode = activeMode;

    // Save current mode tokens before switching
    activeMode = newMode;
    currentSettings.activeMode = newMode;

    // Carry structural choices into the mode being opened
    if (previousMode !== newMode &&
        currentSettings[previousMode] && currentSettings[newMode]) {
      STRUCTURAL_TOKENS.forEach((key) => {
        if (currentSettings[previousMode][key] !== undefined) {
          currentSettings[newMode][key] = currentSettings[previousMode][key];
        }
      });
    }

    // Update tab UI
    document.querySelectorAll('[data-rmm-mode-tab]').forEach((tab) => {
      tab.classList.toggle('rmm-s-tab--active', tab.dataset.rmmModeTab === newMode);
    });

    // Update preview theme attribute (triggers CSS dark mode)
    const previewRoot = document.getElementById('rmm-preview-root');
    if (previewRoot) {
      previewRoot.setAttribute('data-theme', newMode);
    }

    // Re-populate controls with mode-specific tokens and apply to preview
    populateControls(newMode);
  }

  /* ── Device Switcher ─────────────────────────────────── */
  function switchDevice(device) {
    const previewWrap = document.getElementById('rmm-preview-wrap');
    if (!previewWrap) return;

    document.querySelectorAll('[data-rmm-device-tab]').forEach((tab) => {
      tab.classList.toggle('rmm-s-tab--active', tab.dataset.rmmDeviceTab === device);
    });

    const widths = { desktop: '100%', tablet: '768px', mobile: '390px' };
    previewWrap.style.maxWidth = widths[device] || '100%';
    previewWrap.style.margin   = device === 'desktop' ? '0' : '0 auto';
  }

  /* ── Save ────────────────────────────────────────────── */
  function saveSettings() {
    const btn = document.getElementById('rmm-save-btn');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ در حال ذخیره...'; }

    const payload = JSON.stringify(currentSettings);
    const body = new URLSearchParams({
      action:  'rmm_save_design_system',
      nonce:   SAVE_NONCE,
      payload: payload,
    });

    fetch(AJAX_URL, { method: 'POST', credentials: 'same-origin', body })
      .then((r) => r.json())
      .then((res) => {
        if (res.success) {
          currentSettings = deepClone(res.data.settings);
          markClean();
          showToast('✅ تنظیمات با موفقیت ذخیره شد.', 'success');
        } else {
          showToast('❌ خطا در ذخیره: ' + (res.data && res.data.message ? res.data.message : 'خطای ناشناخته'), 'error');
        }
      })
      .catch(() => showToast('❌ ارتباط با سرور برقرار نشد.', 'error'))
      .finally(() => {
        if (btn) { btn.disabled = false; btn.textContent = '💾 ذخیره تغییرات'; }
      });
  }

  /* ── Reset ───────────────────────────────────────────── */
  function resetSettings() {
    if (!confirm('آیا مطمئن هستید؟ تمام تنظیمات به حالت پیش‌فرض بازمی‌گردد.')) return;

    const btn = document.getElementById('rmm-reset-btn');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ در حال بازنشانی...'; }

    const body = new URLSearchParams({
      action: 'rmm_reset_design_system',
      nonce:  RESET_NONCE,
    });

    fetch(AJAX_URL, { method: 'POST', credentials: 'same-origin', body })
      .then((r) => r.json())
      .then((res) => {
        if (res.success) {
          currentSettings = deepClone(res.data.settings);
          defaults        = deepClone(res.data.settings);
          markClean();
          populateControls(activeMode);
          showToast('↩️ تنظیمات به حالت پیش‌فرض بازگشت.', 'success');
        } else {
          showToast('❌ خطا در بازنشانی.', 'error');
        }
      })
      .catch(() => showToast('❌ ارتباط با سرور برقرار نشد.', 'error'))
      .finally(() => {
        if (btn) { btn.disabled = false; btn.textContent = '↩️ بازنشانی'; }
      });
  }

  /* ── Event Wiring ────────────────────────────────────── */
  function wireButtons() {
    const saveBtn  = document.getElementById('rmm-save-btn');
    const resetBtn = document.getElementById('rmm-reset-btn');
    if (saveBtn)  saveBtn.addEventListener('click', saveSettings);
    if (resetBtn) resetBtn.addEventListener('click', resetSettings);

    // Mode tabs (light/dark)
    document.querySelectorAll('[data-rmm-mode-tab]').forEach((tab) => {
      tab.addEventListener('click', () => switchMode(tab.dataset.rmmModeTab));
    });

    // Device tabs
    document.querySelectorAll('[data-rmm-device-tab]').forEach((tab) => {
      tab.addEventListener('click', () => switchDevice(tab.dataset.rmmDeviceTab));
    });

    // Sidebar component tabs
    document.querySelectorAll('[data-rmm-sidebar-tab]').forEach((tab) => {
      tab.addEventListener('click', () => switchSidebarTab(tab.dataset.rmmSidebarTab));
    });

    // Default theme dropdown change listener
    const defaultThemeSelect = document.getElementById('rmm-default-theme-select');
    if (defaultThemeSelect) {
      defaultThemeSelect.addEventListener('change', () => {
        currentSettings.default_theme = defaultThemeSelect.value;
        markDirty();
      });
    }

    // Modal open/close in preview canvas
    const modalOpenBtn  = document.getElementById('rmm-preview-modal-open');
    const modalOverlay  = document.getElementById('rmm-preview-modal-overlay');
    const modalCloseBtn = document.getElementById('rmm-preview-modal-close');
    if (modalOpenBtn && modalOverlay) {
      modalOpenBtn.addEventListener('click', () => {
        modalOverlay.style.display = 'flex';
      });
    }
    if (modalCloseBtn && modalOverlay) {
      modalCloseBtn.addEventListener('click', () => {
        modalOverlay.style.display = 'none';
      });
    }
    if (modalOverlay) {
      modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) modalOverlay.style.display = 'none';
      });
    }

    // Language modal open/close in preview canvas
    const langModalOpenBtn  = document.getElementById('rmm-preview-lang-modal-open');
    const langModalOverlay  = document.getElementById('rmm-preview-lang-modal-overlay');
    const langModalCloseBtn = document.getElementById('rmm-preview-lang-modal-close');
    if (langModalOpenBtn && langModalOverlay) {
      langModalOpenBtn.addEventListener('click', () => {
        langModalOverlay.style.display = 'flex';
      });
    }
    if (langModalCloseBtn && langModalOverlay) {
      langModalCloseBtn.addEventListener('click', () => {
        langModalOverlay.style.display = 'none';
      });
    }
    if (langModalOverlay) {
      langModalOverlay.addEventListener('click', (e) => {
        if (e.target === langModalOverlay) langModalOverlay.style.display = 'none';
      });
    }
  }

  /* ── Init ────────────────────────────────────────────── */
  function init() {
    // Show first sidebar tab by default
    switchSidebarTab('base');
    populateControls(activeMode);
    bindControls();
    wireButtons();
    switchMode(activeMode);
    switchDevice('desktop');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
