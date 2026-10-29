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

document.querySelectorAll('[data-select-all]').forEach((selectAll) => {
  const group = selectAll.dataset.selectAll;
  const items = () => document.querySelectorAll(`[data-select-item="${group}"]`);
  selectAll.addEventListener('change', () => {
    items().forEach((item) => { item.checked = selectAll.checked; });
  });
  items().forEach((item) => {
    item.addEventListener('change', () => {
      const currentItems = [...items()];
      selectAll.checked = currentItems.length > 0 && currentItems.every((entry) => entry.checked);
      selectAll.indeterminate = currentItems.some((entry) => entry.checked) && !selectAll.checked;
    });
  });
});

const auditChangeModal = document.getElementById('audit-change-modal');
const auditChangeAction = document.getElementById('audit-change-action');
const auditChangeBefore = document.getElementById('audit-change-before');
const auditChangeAfter = document.getElementById('audit-change-after');
const auditChangeClose = document.getElementById('audit-change-close');
if (auditChangeModal && auditChangeAction && auditChangeBefore && auditChangeAfter && auditChangeClose) {
  const renderAuditValue = (container, value) => {
    container.replaceChildren();
    const entries = String(value || 'Not available').split(';').map((entry) => entry.trim()).filter(Boolean);
    entries.forEach((entry) => {
      const separator = entry.indexOf(':');
      const row = document.createElement('div');
      row.className = 'audit-change-row';
      if (separator > 0) {
        const label = document.createElement('span');
        label.textContent = entry.slice(0, separator).trim();
        const detail = document.createElement('strong');
        detail.textContent = entry.slice(separator + 1).trim();
        row.append(label, detail);
      } else {
        row.textContent = entry;
      }
      container.appendChild(row);
    });
  };
  const closeAuditChange = () => auditChangeModal.close();
  document.querySelectorAll('[data-change-dialog]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      auditChangeAction.textContent = trigger.dataset.changeAction || 'Record change';
      renderAuditValue(auditChangeBefore, trigger.dataset.changeBefore);
      renderAuditValue(auditChangeAfter, trigger.dataset.changeAfter);
      auditChangeModal.showModal();
    });
  });
  auditChangeClose.addEventListener('click', closeAuditChange);
  auditChangeModal.addEventListener('cancel', (event) => {
    event.preventDefault();
    closeAuditChange();
  });
}

const popupTimers = new WeakMap();
const setPopupState = (popover, toggle, open) => {
  const timer = popupTimers.get(popover);
  if (timer) window.clearTimeout(timer);
  toggle.setAttribute('aria-expanded', String(open));
  if (open) {
    popover.removeAttribute('hidden');
    popover.classList.remove('is-closing');
    window.requestAnimationFrame(() => popover.classList.add('is-open'));
    return;
  }
  popover.classList.remove('is-open');
  popover.classList.add('is-closing');
  popupTimers.set(popover, window.setTimeout(() => {
    popover.setAttribute('hidden', '');
    popover.classList.remove('is-closing');
  }, 220));
};
const closeAlertMenus = () => {
  document.querySelectorAll('[data-alert-menu]').forEach((menu) => {
    const toggle = menu.parentElement.querySelector('[data-alert-toggle]');
    if (toggle) setPopupState(menu, toggle, false);
  });
};
const closeCalendarPopovers = () => {
  document.querySelectorAll('[data-calendar-popover]').forEach((popover) => {
    const toggle = popover.parentElement.querySelector('[data-calendar-toggle]');
    if (toggle) setPopupState(popover, toggle, false);
  });
};

document.querySelectorAll('[data-alert-toggle]').forEach((toggle) => {
  const menu = toggle.parentElement.querySelector('[data-alert-menu]');
  if (!menu) return;
  toggle.addEventListener('click', (event) => {
    event.stopPropagation();
    const open = menu.hasAttribute('hidden');
    if (open) closeCalendarPopovers();
    setPopupState(menu, toggle, open);
  });
  menu.addEventListener('click', (event) => event.stopPropagation());
});

const calendarDateKey = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};
const calendarDateFromKey = (value) => {
  const [year, month, day] = value.split('-').map(Number);
  return new Date(year, month - 1, day);
};
const calendarDateFormatter = new Intl.DateTimeFormat(undefined, { month: 'long', day: 'numeric', year: 'numeric' });
const calendarMonthFormatter = new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' });
const escapeCalendarText = (value) => String(value).replace(/[&<>"']/g, (character) => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
}[character]));

document.querySelectorAll('[data-calendar]').forEach((calendar) => {
  let events = [];
  try {
    events = JSON.parse(calendar.dataset.calendarEvents || '[]');
  } catch (error) {
    console.error('Unable to load calendar reminders.', error);
  }
  const eventsByDate = new Map();
  events.forEach((event) => {
    const date = event.expected_farrowing_date;
    if (!date) return;
    if (!eventsByDate.has(date)) eventsByDate.set(date, []);
    eventsByDate.get(date).push(event);
  });
  const popover = calendar.querySelector('[data-calendar-popover]');
  const toggle = calendar.querySelector('[data-calendar-toggle]');
  const grid = calendar.querySelector('[data-calendar-grid]');
  const monthLabel = calendar.querySelector('[data-calendar-month]');
  const title = calendar.querySelector('[data-calendar-title]');
  const selectedDateLabel = calendar.querySelector('[data-calendar-selected-date]');
  const eventsList = calendar.querySelector('[data-calendar-events-list]');
  const today = new Date();
  let monthCursor = new Date(today.getFullYear(), today.getMonth(), 1);
  let selectedDate = calendarDateKey(today);

  const render = (direction = 0) => {
    monthLabel.textContent = calendarMonthFormatter.format(monthCursor);
    title.textContent = 'Farrowing calendar';
    const firstDayOffset = (monthCursor.getDay() + 6) % 7;
    const daysInMonth = new Date(monthCursor.getFullYear(), monthCursor.getMonth() + 1, 0).getDate();
    const totalCells = Math.ceil((firstDayOffset + daysInMonth) / 7) * 7;
    grid.innerHTML = '';
    grid.classList.remove('is-month-prev', 'is-month-next');
    for (let index = 0; index < totalCells; index += 1) {
      const date = new Date(monthCursor.getFullYear(), monthCursor.getMonth(), index - firstDayOffset + 1);
      const key = calendarDateKey(date);
      const dayEvents = eventsByDate.get(key) || [];
      const day = document.createElement('button');
      day.type = 'button';
      day.className = 'calendar-day';
      if (date.getMonth() !== monthCursor.getMonth()) day.classList.add('is-adjacent');
      if (key === calendarDateKey(today)) day.classList.add('is-today');
      if (key === selectedDate) day.classList.add('is-selected');
      day.setAttribute('role', 'gridcell');
      day.setAttribute('aria-label', `${calendarDateFormatter.format(date)}${dayEvents.length ? `, ${dayEvents.length} farrowing reminder${dayEvents.length === 1 ? '' : 's'}` : ''}`);
      day.setAttribute('aria-selected', String(key === selectedDate));
      day.dataset.calendarDate = key;
      day.innerHTML = `<span>${date.getDate()}</span>${dayEvents.length ? '<i class="calendar-event-dot" aria-hidden="true"></i>' : ''}`;
      grid.appendChild(day);
    }
    if (direction) {
      grid.classList.add(direction < 0 ? 'is-month-prev' : 'is-month-next');
      window.setTimeout(() => grid.classList.remove('is-month-prev', 'is-month-next'), 260);
    }
    const selectedDateObject = calendarDateFromKey(selectedDate);
    selectedDateLabel.textContent = calendarDateFormatter.format(selectedDateObject);
    const selectedEvents = eventsByDate.get(selectedDate) || [];
    eventsList.innerHTML = selectedEvents.length
      ? selectedEvents.map((event) => `<a class="calendar-event-item" href="${escapeCalendarText(event.edit_href)}"><i data-lucide="heart-pulse" aria-hidden="true"></i><span><strong>Farrowing reminder</strong><small>Sow ${escapeCalendarText(event.tag_number)} · Expected ${escapeCalendarText(calendarDateFormatter.format(calendarDateFromKey(event.expected_farrowing_date)))}</small></span></a>`).join('')
      : '<p class="calendar-empty">No farrowing reminders for this day.</p>';
    if (window.lucide) window.lucide.createIcons();
  };

  const setOpen = (open) => {
    setPopupState(popover, toggle, open);
    if (open) render();
  };

  toggle.addEventListener('click', (event) => {
    event.stopPropagation();
    const open = popover.hasAttribute('hidden');
    if (open) closeAlertMenus();
    setOpen(open);
  });
  popover.addEventListener('click', (event) => {
    event.stopPropagation();
    const day = event.target.closest('[data-calendar-date]');
    if (day) {
      selectedDate = day.dataset.calendarDate;
      const date = calendarDateFromKey(selectedDate);
      monthCursor = new Date(date.getFullYear(), date.getMonth(), 1);
      render();
    }
    if (event.target.closest('[data-calendar-close]')) setOpen(false);
    if (event.target.closest('[data-calendar-prev]')) {
      monthCursor.setMonth(monthCursor.getMonth() - 1);
      render(-1);
    }
    if (event.target.closest('[data-calendar-next]')) {
      monthCursor.setMonth(monthCursor.getMonth() + 1);
      render(1);
    }
  });
  render();
});

document.addEventListener('click', () => {
  closeAlertMenus();
  closeCalendarPopovers();
  document.querySelectorAll('[data-filter-select] .filter-select-menu.is-open').forEach((menu) => {
    menu.classList.remove('is-open');
    window.setTimeout(() => menu.setAttribute('hidden', ''), 180);
    const toggle = menu.parentElement.querySelector('[data-filter-select-toggle]');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  });
});
document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  closeAlertMenus();
  document.querySelectorAll('[data-calendar-popover]').forEach((popover) => {
    if (!popover.hasAttribute('hidden')) {
      const toggle = popover.parentElement.querySelector('[data-calendar-toggle]');
      if (toggle) setPopupState(popover, toggle, false);
      if (toggle) toggle.focus();
    }
  });
});

function bindFilterSelects() {
  document.querySelectorAll('[data-filter-select]').forEach((select) => {
    if (select.dataset.bound) return;
    select.dataset.bound = 'true';
    const toggle = select.querySelector('[data-filter-select-toggle]');
    const menu = select.querySelector('.filter-select-menu');
    const input = select.querySelector('input[type="hidden"]');
    if (!toggle || !menu || !input) return;
    let closeTimer;
    const close = () => {
      window.clearTimeout(closeTimer);
      menu.classList.remove('is-open');
      closeTimer = window.setTimeout(() => menu.setAttribute('hidden', ''), 180);
      toggle.setAttribute('aria-expanded', 'false');
    };
    const open = () => {
      window.clearTimeout(closeTimer);
      menu.removeAttribute('hidden');
      window.requestAnimationFrame(() => menu.classList.add('is-open'));
      toggle.setAttribute('aria-expanded', 'true');
    };
    toggle.addEventListener('click', (event) => {
      event.stopPropagation();
      if (menu.hasAttribute('hidden')) open();
      else close();
    });
    menu.addEventListener('click', (event) => {
      event.stopPropagation();
      const option = event.target.closest('[data-filter-option]');
      if (!option) return;
      input.value = option.dataset.filterOption || '';
      input.dispatchEvent(new Event('change', { bubbles: true }));
      toggle.firstChild.textContent = option.textContent;
      menu.querySelectorAll('[data-filter-option]').forEach((item) => item.setAttribute('aria-selected', String(item === option)));
      close();
      select.closest('form')?.dispatchEvent(new Event('filterchange', { bubbles: true }));
    });
    select.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') close();
    });
  });
}

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
    currentResults.classList.add('is-updating');
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
      replacement.style.minHeight = `${currentResults.offsetHeight}px`;
      currentResults.replaceWith(replacement);
      replacement.classList.add('is-entering');
      window.requestAnimationFrame(() => replacement.classList.remove('is-entering'));
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
        if (status) status.textContent = 'Unable to update inventory. Try again.';
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
  form.addEventListener('filterchange', () => update(buildUrl()));
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
      target.classList.add('is-updating');
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
          replacement.style.minHeight = `${target.offsetHeight}px`;
          replacement.classList.add('is-entering');
          target.replaceWith(replacement);
          window.requestAnimationFrame(() => replacement.classList.remove('is-entering'));
          window.history.replaceState({}, '', url.toString());
          bindAjaxFilters();
          if (window.lucide) window.lucide.createIcons();
        })
        .catch((error) => {
          if (error.name !== 'AbortError') console.error(error);
          const currentTarget = document.querySelector(targetSelector);
          if (currentTarget) {
            currentTarget.setAttribute('aria-busy', 'false');
            currentTarget.classList.remove('is-updating');
          }
        })
        .finally(() => {
          form.classList.remove('is-loading');
          const currentTarget = document.querySelector(targetSelector);
          if (currentTarget) currentTarget.setAttribute('aria-busy', 'false');
        });
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
    form.addEventListener('filterchange', () => update(buildUrl()));
  });
}

function bindAuditFilter() {
  const form = document.querySelector('[data-audit-filter]');
  const target = document.querySelector('#audit-results');
  if (!form || !target || form.dataset.bound) return;
  form.dataset.bound = 'true';
  const search = form.querySelector('input[name="search"]');
  const staffToggle = document.querySelector('[data-audit-staff-toggle]');
  let request;
  let timer;
  const buildUrl = () => {
    const url = new URL(form.action, window.location.href);
    new FormData(form).forEach((value, key) => {
      if (value) url.searchParams.set(key, value);
      else url.searchParams.delete(key);
    });
    return url;
  };
  const update = (url) => {
    if (request) request.abort();
    request = new AbortController();
    target.setAttribute('aria-busy', 'true');
    target.classList.add('is-updating');
    form.classList.add('is-loading');
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: request.signal })
      .then((response) => {
        if (!response.ok) throw new Error(`Audit filter request failed (${response.status})`);
        return response.text();
      })
      .then((html) => {
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const replacement = parsed.querySelector('#audit-results-fragment');
        if (!replacement) throw new Error('Filtered audit results were not returned.');
        target.replaceChildren(...replacement.childNodes);
        window.history.replaceState({}, '', url.toString());
        if (window.lucide) window.lucide.createIcons();
      })
      .catch((error) => { if (error.name !== 'AbortError') console.error(error); })
      .finally(() => {
        form.classList.remove('is-loading');
        target.setAttribute('aria-busy', 'false');
      });
  };
  const apply = () => {
    window.clearTimeout(timer);
    update(buildUrl());
  };
  if (search) {
    search.addEventListener('input', () => {
      window.clearTimeout(timer);
      timer = window.setTimeout(apply, 300);
    });
  }
  form.addEventListener('filterchange', apply);
  if (staffToggle) {
    staffToggle.addEventListener('click', (event) => {
      event.preventDefault();
      const url = buildUrl();
      url.searchParams.set('staff_only', url.searchParams.get('staff_only') === '1' ? '0' : '1');
      update(url);
      staffToggle.classList.toggle('active');
      staffToggle.lastChild.textContent = url.searchParams.get('staff_only') === '1' ? 'Staff only' : 'All users';
    });
  }
}

bindFilterSelects();
bindPigFilter();
bindAjaxFilters();
bindAuditFilter();

document.querySelectorAll('button, .btn, .icon-button, .sidebar-nav a, .mobile-nav-link').forEach((element) => {
  element.addEventListener('pointerdown', () => element.classList.add('is-pressed'));
  element.addEventListener('pointerup', () => element.classList.remove('is-pressed'));
  element.addEventListener('pointerleave', () => element.classList.remove('is-pressed'));
});
