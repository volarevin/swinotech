if (window.lucide) {
  window.lucide.createIcons();
}

document.documentElement.classList.add('js');

const sidebarKey = 'swinotech-sidebar-collapsed';
const sidebar = document.querySelector('.app-sidebar');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
if (sidebar && sidebarToggle) {
  const setSidebarCollapsed = (collapsed, persist = false) => {
    sidebar.classList.toggle('is-collapsed', collapsed);
    document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
    sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
    sidebarToggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    const icon = sidebarToggle.querySelector('[data-lucide], svg');
    if (icon) {
      const replacement = document.createElement('i');
      replacement.setAttribute('data-lucide', collapsed ? 'panel-left-open' : 'panel-left-close');
      replacement.setAttribute('aria-hidden', 'true');
      icon.replaceWith(replacement);
    }
    if (persist) {
      try { window.localStorage.setItem(sidebarKey, String(collapsed)); } catch (error) { /* unavailable storage should not block the UI */ }
    }
    if (window.lucide) window.lucide.createIcons();
  };
  let initiallyCollapsed = false;
  try { initiallyCollapsed = window.localStorage.getItem(sidebarKey) === 'true'; } catch (error) { /* unavailable storage should not block the UI */ }
  setSidebarCollapsed(initiallyCollapsed);
  sidebarToggle.addEventListener('click', () => setSidebarCollapsed(!sidebar.classList.contains('is-collapsed'), true));
}

document.querySelectorAll('[data-chart-switch]').forEach((toggle) => {
  toggle.addEventListener('click', () => {
    const card = toggle.closest('.card');
    const bars = card?.querySelector('[data-population-bars]');
    const donut = card?.querySelector('[data-population-donut]');
    if (!bars || !donut) return;
    const showDonut = donut.hasAttribute('hidden');
    bars.toggleAttribute('hidden', showDonut);
    donut.toggleAttribute('hidden', !showDonut);
    toggle.setAttribute('aria-pressed', String(showDonut));
    toggle.textContent = showDonut ? 'Bar view' : 'Donut view';
  });
});

const themeKey = 'swinotech-theme';
const validThemes = new Set(['light', 'dark']);
const systemTheme = () => window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
const savedTheme = () => {
  try {
    const value = window.localStorage.getItem(themeKey);
    return validThemes.has(value) ? value : null;
  } catch (error) {
    return null;
  }
};

function updateThemeControls(theme) {
  document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
    const dark = theme === 'dark';
    toggle.setAttribute('aria-pressed', String(dark));
    toggle.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
    const label = toggle.querySelector('[data-theme-label]');
    if (label) label.textContent = dark ? 'Switch to light mode' : 'Enable dark mode';
    const icon = toggle.querySelector('[data-lucide], svg');
    if (icon) {
      const replacement = document.createElement('i');
      replacement.setAttribute('data-lucide', dark ? 'sun' : 'moon');
      replacement.setAttribute('aria-hidden', 'true');
      icon.replaceWith(replacement);
    }
  });
  if (window.lucide) window.lucide.createIcons();
}

function applyTheme(theme, persist = false) {
  document.documentElement.dataset.theme = theme;
  if (persist) {
    try { window.localStorage.setItem(themeKey, theme); } catch (error) { /* unavailable storage should not block the UI */ }
  }
  updateThemeControls(theme);
}

applyTheme(savedTheme() || document.documentElement.dataset.theme || systemTheme());
document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
  toggle.addEventListener('click', () => applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark', true));
});
const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
mediaQuery.addEventListener?.('change', () => { if (!savedTheme()) applyTheme(systemTheme()); });

document.querySelectorAll('.content > *, .login-card, .login-brand > div').forEach((element, index) => {
  element.classList.add('reveal');
  element.style.setProperty('--reveal-delay', `${Math.min(index, 6) * 55}ms`);
});

const confirmationModalElement = document.getElementById('confirmation-modal');
const confirmationModalMessage = document.getElementById('confirmation-modal-message');
const confirmationModalSubmit = document.getElementById('confirmation-modal-submit');
const confirmationModalCancel = document.getElementById('confirmation-modal-cancel');
if (confirmationModalElement && confirmationModalMessage && confirmationModalSubmit) {
  let confirmedAction = null;
  const closeConfirmation = () => {
    confirmedAction = null;
    confirmationModalElement.classList.add('is-closing');
    window.setTimeout(() => {
      confirmationModalElement.close();
      confirmationModalElement.classList.remove('is-closing');
    }, 150);
  };
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-confirm-dialog]');
    if (!trigger) return;
    event.preventDefault();
    confirmedAction = trigger;
    confirmationModalMessage.textContent = trigger.dataset.confirmDialog;
    confirmationModalSubmit.textContent = trigger.dataset.confirmAction || 'Continue';
    confirmationModalElement.showModal();
  });
  confirmationModalSubmit.addEventListener('click', () => {
    if (!confirmedAction) return;
    const action = confirmedAction;
    confirmedAction = null;
    confirmationModalElement.classList.add('is-closing');
    window.setTimeout(() => {
      confirmationModalElement.close();
      confirmationModalElement.classList.remove('is-closing');
    }, 150);
    if (action.form) {
      action.form.requestSubmit(action);
    } else if (action.href) {
      window.location.assign(action.href);
    }
  });
  confirmationModalCancel.addEventListener('click', closeConfirmation);
  confirmationModalElement.addEventListener('cancel', (event) => {
    event.preventDefault();
    closeConfirmation();
  });
  confirmationModalElement.addEventListener('close', () => { confirmedAction = null; });
}

document.querySelectorAll('[data-alert-toggle]').forEach((toggle) => {
  const menu = toggle.parentElement.querySelector('[data-alert-menu]');
  if (!menu) return;
  toggle.addEventListener('click', (event) => {
    event.stopPropagation();
    const open = menu.hasAttribute('hidden');
    menu.toggleAttribute('hidden', !open);
    toggle.setAttribute('aria-expanded', String(open));
  });
  menu.addEventListener('click', (event) => event.stopPropagation());
});

document.addEventListener('click', () => {
  document.querySelectorAll('[data-alert-menu]').forEach((menu) => {
    menu.setAttribute('hidden', '');
    const toggle = menu.parentElement.querySelector('[data-alert-toggle]');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  });
});
document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  document.querySelectorAll('[data-alert-menu]').forEach((menu) => {
    menu.setAttribute('hidden', '');
    const toggle = menu.parentElement.querySelector('[data-alert-toggle]');
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
      toggle.focus();
    }
  });
});

async function refreshFilteredList(form, url) {
  const target = document.querySelector('.table-shell');
  if (!target) return;
  form.classList.add('is-loading');
  try {
    const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    if (!response.ok) throw new Error(`Filter request failed (${response.status})`);
    const html = await response.text();
    const parsed = new DOMParser().parseFromString(html, 'text/html');
    const replacement = parsed.querySelector('.table-shell');
    if (!replacement) throw new Error('Filtered response did not contain a results table.');
    target.replaceWith(replacement);
    const nextForm = parsed.querySelector('.filter-bar');
    if (nextForm) form.replaceWith(nextForm);
    window.history.replaceState({}, '', url.toString());
    bindAjaxFilters();
  } catch (error) {
    console.error(error);
    let message = form.parentElement.querySelector('[data-filter-error]');
    if (!message) {
      message = document.createElement('div');
      message.dataset.filterError = 'true';
      message.className = 'alert alert-danger mt-2';
      message.setAttribute('role', 'alert');
      form.insertAdjacentElement('afterend', message);
    }
    message.textContent = 'Unable to apply this filter. Please try again.';
  } finally {
    form.classList.remove('is-loading');
  }
}

function bindPigFilter() {
  const form = document.querySelector('[data-pig-filter]');
  if (!form || !document.querySelector('#pig-results') || form.dataset.ajaxBound) return;
  form.dataset.ajaxBound = 'true';
  const statusToggle = form.querySelector('[data-pig-status-toggle]');
  let request;
  let timer;

  const update = async (url) => {
    if (request) request.abort();
    request = new AbortController();
    const currentResults = document.querySelector('#pig-results');
    if (!currentResults) return;
    currentResults.setAttribute('aria-busy', 'true');
    form.classList.add('is-loading');
    const status = form.querySelector('[data-pig-filter-status]');
    if (status) status.textContent = 'Updating inventory…';
    try {
      const response = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        signal: request.signal
      });
      if (!response.ok) throw new Error(`Pig filter request failed (${response.status})`);
      const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
      const replacement = parsed.querySelector('#pig-results');
      if (!replacement) {
        window.location.assign(url.toString());
        return;
      }
      currentResults.replaceWith(replacement);
      if (statusToggle) {
        statusToggle.textContent = url.searchParams.has('all') ? 'Active only' : 'Show all';
        statusToggle.href = url.searchParams.has('all') ? url.pathname : `${url.pathname}?all=1`;
      }
      window.history.replaceState({}, '', url.toString());
      if (status) status.textContent = 'Inventory updated.';
      bindPigFilter();
      if (window.lucide) window.lucide.createIcons();
    } catch (error) {
      if (error.name !== 'AbortError') {
        console.error(error);
        if (status) status.textContent = 'Unable to update inventory. Try Apply again.';
      }
    } finally {
      form.classList.remove('is-loading');
      const latestResults = document.querySelector('#pig-results');
      if (latestResults) latestResults.setAttribute('aria-busy', 'false');
    }
  };

  const buildUrl = () => {
    const url = new URL(form.action, window.location.href);
    new FormData(form).forEach((value, key) => {
      if (value) url.searchParams.set(key, value);
      else url.searchParams.delete(key);
    });
    const current = new URL(window.location.href);
    if (current.searchParams.has('all')) url.searchParams.set('all', '1');
    else url.searchParams.delete('all');
    return url;
  };

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    update(buildUrl());
  });
  form.querySelector('[name="category"]').addEventListener('change', () => update(buildUrl()));
  form.querySelector('[name="search"]').addEventListener('input', () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => update(buildUrl()), 300);
  });
  form.querySelector('[data-pig-status-toggle]').addEventListener('click', (event) => {
    event.preventDefault();
    const url = buildUrl();
    if (url.searchParams.has('all')) url.searchParams.delete('all');
    else url.searchParams.set('all', '1');
    update(url);
  });
}

function bindAjaxFilters() {
  document.querySelectorAll('[data-ajax-filter]').forEach((form) => {
    const targetSelector = form.dataset.results;
    if (!targetSelector || form.dataset.ajaxBound) return;
    form.dataset.ajaxBound = 'true';
    let request;
    let timer;
    const buildUrl = () => {
      const url = new URL(form.action || window.location.href, window.location.href);
      new FormData(form).forEach((value, key) => {
        if (value) url.searchParams.set(key, value);
        else url.searchParams.delete(key);
      });
      return url;
    };
    const update = (url) => {
      if (request) request.abort();
      request = new AbortController();
      const target = document.querySelector(targetSelector);
      if (!target) return;
      target.setAttribute('aria-busy', 'true');
      form.classList.add('is-loading');
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: request.signal })
        .then((response) => {
          if (!response.ok) throw new Error(`Filter request failed (${response.status})`);
          return response.text();
        })
        .then((html) => {
          const parsed = new DOMParser().parseFromString(html, 'text/html');
          const replacement = parsed.querySelector(targetSelector);
          if (!replacement) throw new Error('Filtered results were not returned.');
          target.replaceWith(replacement);
          window.history.replaceState({}, '', url.toString());
          bindAjaxFilters();
          if (window.lucide) window.lucide.createIcons();
        })
        .catch((error) => {
          if (error.name !== 'AbortError') console.error(error);
          const currentTarget = document.querySelector(targetSelector);
          if (currentTarget) currentTarget.setAttribute('aria-busy', 'false');
        })
        .finally(() => form.classList.remove('is-loading'));
    };
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      update(buildUrl());
    });
    const search = form.querySelector('input[name="search"]');
    if (search) search.addEventListener('input', () => {
      window.clearTimeout(timer);
      timer = window.setTimeout(() => update(buildUrl()), 300);
    });
    form.querySelectorAll('select').forEach((select) => {
      select.addEventListener('change', () => update(buildUrl()));
    });
  });
}

bindPigFilter();
bindAjaxFilters();

document.querySelectorAll('button, .btn, .icon-button, .sidebar-nav a, .mobile-nav-link').forEach((element) => {
  element.addEventListener('pointerdown', () => element.classList.add('is-pressed'));
  element.addEventListener('pointerup', () => element.classList.remove('is-pressed'));
  element.addEventListener('pointerleave', () => element.classList.remove('is-pressed'));
});
