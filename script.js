/* ============================================================
   NovelNest — Main JavaScript
   ============================================================ */
const App = (function () {
  const THEME_KEY = 'novelnest_theme_v1';
  const FONT_KEY  = 'novelnest_fontsize_v1';

  /* ── helpers ── */
  const qs  = (s, ctx) => (ctx || document).querySelector(s);
  const qsa = (s, ctx) => Array.from((ctx || document).querySelectorAll(s));

  /* ============================================================
     THEME MANAGEMENT
     ============================================================ */
  function getTheme()    { return localStorage.getItem(THEME_KEY) || 'light'; }
  function setTheme(t)   { localStorage.setItem(THEME_KEY, t); }

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    setTheme(theme);
    qsa('#theme-toggle').forEach(b => {
      b.textContent = theme === 'dark' ? '🌙' : '☀️';
      b.setAttribute('title', theme === 'dark' ? 'الوضع النهاري' : 'الوضع الليلي');
    });
    updateMakerLabel(theme);
  }

  function toggleTheme() {
    applyTheme(getTheme() === 'dark' ? 'light' : 'dark');
  }

  function updateMakerLabel(theme) {
    qsa('.maker-label').forEach(el => {
      el.textContent = theme === 'dark' ? 'Dark King' : 'Sun King';
    });
  }

  /* ============================================================
     SEARCH / FILTER
     ============================================================ */
  function initSearch() {
    const input = qs('#search-input');
    const clear = qs('#search-clear');
    const count = qs('#search-count');
    const cards = qsa('.novel-card');
    const noRes = qs('.no-results');
    if (!input) return;

    function filter(q) {
      const query = q.trim().toLowerCase();
      let visible = 0;
      cards.forEach(card => {
        const title   = (card.dataset.title   || '').toLowerCase();
        const summary = (card.dataset.summary || '').toLowerCase();
        const match   = !query || title.includes(query) || summary.includes(query);
        card.style.display = match ? '' : 'none';
        if (match) visible++;
      });
      if (noRes) noRes.classList.toggle('visible', visible === 0 && query !== '');
      if (count) {
        count.textContent = query
          ? (visible === 0 ? 'لا توجد نتائج' : `${visible} رواية`)
          : '';
      }
      if (clear) clear.classList.toggle('visible', query.length > 0);
    }

    input.addEventListener('input', e => filter(e.target.value));
    if (clear) {
      clear.addEventListener('click', () => {
        input.value = '';
        filter('');
        input.focus();
      });
    }
  }

  /* ============================================================
     READING PROGRESS BAR
     ============================================================ */
  function initReadingProgress() {
    const bar = qs('#reading-progress-bar');
    if (!bar) return;
    function update() {
      const scrollTop = window.scrollY;
      const docH = document.documentElement.scrollHeight - window.innerHeight;
      const pct  = docH > 0 ? Math.round((scrollTop / docH) * 100) : 0;
      bar.style.width = pct + '%';
    }
    window.addEventListener('scroll', update, { passive: true });
    update();
  }

  /* ============================================================
     BACK TO TOP BUTTON
     ============================================================ */
  function initBackToTop() {
    const btn = qs('#back-to-top');
    if (!btn) return;
    window.addEventListener('scroll', () => {
      btn.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
    btn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ============================================================
     TOAST NOTIFICATIONS
     ============================================================ */
  function toast(msg, type = 'info', duration = 3000) {
    let container = qs('#toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }
    const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<span>${icons[type] || ''}</span><span>${msg}</span>`;
    container.appendChild(el);
    setTimeout(() => {
      el.classList.add('hide');
      setTimeout(() => el.remove(), 350);
    }, duration);
  }

  /* ============================================================
     MODAL / CONFIRM DIALOG
     ============================================================ */
  function initModals() {
    // Delete confirmation
    qsa('[data-confirm]').forEach(el => {
      el.addEventListener('click', e => {
        e.preventDefault();
        const msg    = el.dataset.confirm || 'هل أنت متأكد؟';
        const action = el.href || el.dataset.action;
        const method = el.dataset.method || 'GET';
        showConfirm(msg, () => {
          if (method === 'POST' && el.dataset.form) {
            const f = qs('#' + el.dataset.form);
            if (f) f.submit();
          } else {
            window.location.href = action;
          }
        });
      });
    });
  }

  function showConfirm(message, onConfirm, onCancel) {
    let backdrop = qs('#confirm-modal');
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.id = 'confirm-modal';
      backdrop.className = 'modal-backdrop';
      backdrop.innerHTML = `
        <div class="modal">
          <div class="modal-icon">⚠️</div>
          <h3>تأكيد العملية</h3>
          <p id="confirm-msg"></p>
          <div class="modal-actions">
            <button class="btn btn-ghost" id="confirm-cancel">إلغاء</button>
            <button class="btn btn-danger" id="confirm-ok">تأكيد</button>
          </div>
        </div>`;
      document.body.appendChild(backdrop);
    }
    qs('#confirm-msg', backdrop).textContent = message;
    backdrop.classList.add('open');

    const ok     = qs('#confirm-ok',     backdrop);
    const cancel = qs('#confirm-cancel', backdrop);

    const close = () => backdrop.classList.remove('open');

    ok.onclick = () => { close(); onConfirm && onConfirm(); };
    cancel.onclick = () => { close(); onCancel && onCancel(); };
    backdrop.onclick = e => { if (e.target === backdrop) close(); };
  }

  /* ============================================================
     ADMIN TABS
     ============================================================ */
  function initAdminTabs() {
    const tabs   = qsa('.admin-tab');
    const panels = qsa('.tab-panel');
    if (!tabs.length) return;

    // persist active tab
    const savedTab = sessionStorage.getItem('nn_admin_tab') || '0';
    activateTab(parseInt(savedTab, 10));

    tabs.forEach((tab, i) => {
      tab.addEventListener('click', () => activateTab(i));
    });

    function activateTab(i) {
      tabs.forEach((t, idx) => t.classList.toggle('active', idx === i));
      panels.forEach((p, idx) => p.classList.toggle('active', idx === i));
      sessionStorage.setItem('nn_admin_tab', String(i));
    }
  }

  /* ============================================================
     FILE INPUT PREVIEW
     ============================================================ */
  function initFileInputs() {
    qsa('input[type="file"]').forEach(input => {
      const label  = input.previousElementSibling || input.parentElement.querySelector('label');
      const preview = qs(`[data-preview="${input.name}"]`);
      input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        if (label) {
          label.querySelector('.file-name') && (label.querySelector('.file-name').textContent = file.name);
        }
        if (preview && file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
          };
          reader.readAsDataURL(file);
        }
      });
    });
  }

  /* ============================================================
     READING FONT SIZE
     ============================================================ */
  function initReadingSettings() {
    const content = qs('.chapter-content');
    if (!content) return;

    const savedSize = parseInt(localStorage.getItem(FONT_KEY) || '20', 10);
    content.style.fontSize = savedSize + 'px';

    const toolbar = qs('.reading-toolbar');
    if (toolbar) {
      // show toolbar after scroll
      window.addEventListener('scroll', () => {
        toolbar.classList.toggle('visible', window.scrollY > 200);
      }, { passive: true });

      qs('#font-up', toolbar) && qs('#font-up', toolbar).addEventListener('click', () => {
        const cur = parseInt(content.style.fontSize, 10);
        if (cur < 28) {
          content.style.fontSize = (cur + 1) + 'px';
          localStorage.setItem(FONT_KEY, cur + 1);
        }
      });

      qs('#font-down', toolbar) && qs('#font-down', toolbar).addEventListener('click', () => {
        const cur = parseInt(content.style.fontSize, 10);
        if (cur > 14) {
          content.style.fontSize = (cur - 1) + 'px';
          localStorage.setItem(FONT_KEY, cur - 1);
        }
      });

      qs('#font-reset', toolbar) && qs('#font-reset', toolbar).addEventListener('click', () => {
        content.style.fontSize = '20px';
        localStorage.setItem(FONT_KEY, 20);
      });
    }
  }

  /* ============================================================
     CHAPTER SELECT AUTO-LOAD
     ============================================================ */
  function initChapterSelect() {
    const sel = qs('#chapter-novel-select');
    if (!sel) return;
    // update chapter list when novel changes (admin panel)
    sel.addEventListener('change', function () {
      const slug = this.value;
      if (!slug) return;
      fetch(`?action=get_chapters&slug=${encodeURIComponent(slug)}`)
        .then(r => r.json())
        .then(data => {
          const editSel = qs('#edit-chapter-select');
          if (editSel) {
            editSel.innerHTML = '<option value="">— اختر الفصل —</option>' +
              (data.chapters || []).map((c, i) =>
                `<option value="${i}">${i + 1}. ${c.title}</option>`).join('');
          }
        });
    });
  }

  /* ============================================================
     SCROLL REVEAL ANIMATIONS
     ============================================================ */
  function initScrollReveal() {
    if (!window.IntersectionObserver) return;
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.style.opacity = '1';
          e.target.style.transform = 'translateY(0)';
          obs.unobserve(e.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    qsa('.novel-card').forEach((card, i) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(24px)';
      card.style.transition = `opacity 0.5s ease ${i * 0.06}s, transform 0.5s ease ${i * 0.06}s`;
      obs.observe(card);
    });
  }

  /* ============================================================
     FORM VALIDATION HELPERS
     ============================================================ */
  function initFormValidation() {
    qsa('form[data-validate]').forEach(form => {
      form.addEventListener('submit', function (e) {
        let valid = true;
        qsa('[required]', form).forEach(field => {
          if (!field.value.trim()) {
            valid = false;
            field.style.borderColor = 'var(--red)';
            field.addEventListener('input', () => field.style.borderColor = '', { once: true });
          }
        });
        if (!valid) {
          e.preventDefault();
          toast('يرجى ملء جميع الحقول المطلوبة', 'error');
        }
      });
    });
  }

  /* ============================================================
     TOPBAR SCROLL EFFECT
     ============================================================ */
  function initTopbarScroll() {
    const topbar = qs('.topbar');
    if (!topbar) return;
    window.addEventListener('scroll', () => {
      topbar.style.boxShadow = window.scrollY > 10 ? 'var(--shadow-md)' : '';
    }, { passive: true });
  }

  /* ============================================================
     EXPOSE toast globally
     ============================================================ */
  window.NovelNestToast = toast;
  window.NovelNestConfirm = showConfirm;

  /* ============================================================
     INIT
     ============================================================ */
  function init() {
    applyTheme(getTheme());

    qsa('#theme-toggle').forEach(btn => {
      btn.addEventListener('click', toggleTheme);
    });

    initSearch();
    initReadingProgress();
    initBackToTop();
    initModals();
    initAdminTabs();
    initFileInputs();
    initReadingSettings();
    initChapterSelect();
    initScrollReveal();
    initFormValidation();
    initTopbarScroll();
  }

  return { init, toast, showConfirm, applyTheme, getTheme };
})();

document.addEventListener('DOMContentLoaded', () => App.init());
