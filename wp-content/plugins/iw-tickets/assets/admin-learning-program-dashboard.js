const config = window.IWTicketsLearningProgramDashboard || {};

const root = document.getElementById('iw-learning-program-dashboard-root');

if (!root) {
    throw new Error('Learning program dashboard root not found.');
}

const contextFilters = {
    school_id: Number(config.schoolId || 0),
    user_id: Number(config.userId || 0),
};

function getContextSearchDefault() {
    return contextFilters.school_id && config.schoolLabel ? String(config.schoolLabel) : '';
}

const state = {
    loading: true,
    error: '',
    baseRows: [],
    rows: [],
    programs: [],
    summary: {
        total_rows: 0,
        total_tickets: 0,
        total_revenue: 0,
        reservations_count: 0,
        purchases_count: 0,
    },
    selectedId: null,
    filters: {
        program_id: Number(config.programId || 0),
        school_id: contextFilters.school_id,
        user_id: contextFilters.user_id,
        date_from: '',
        date_to: '',
        view: 'table',
    },
    context: {
        programLabel: config.programLabel || '',
        schoolLabel: config.schoolLabel || '',
        userLabel: config.userLabel || '',
    },
    ui: {
        search: getContextSearchDefault(),
        status: 'all',
        source: 'all',
        filtersOpen: false,
        detailsOpen: false,
        datePicker: {
            key: '',
            cursor: '',
        },
        page: 1,
        perPage: 10,
    },
    calendar: [],
    monthCursor: null,
};

const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function htmlOrFallback(value, fallback = '—') {
    const html = String(value ?? '').trim();
    return html !== '' ? html : fallback;
}

function wrapHtmlLink(html, href, className = '') {
    const content = String(html ?? '').trim();
    const link = String(href ?? '').trim();

    if (!content) {
        return '—';
    }

    if (!link) {
        return content;
    }

    return `<a href="${escapeHtml(link)}" class="${escapeHtml(className)}">${content}</a>`;
}

function formatMoney(value) {
    const amount = Number(value || 0);
    return new Intl.NumberFormat('el-GR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount) + ' €';
}

function formatDateLabel(value) {
    if (!value) return '';
    const dt = new Date(`${value}T00:00:00`);
    return new Intl.DateTimeFormat('el-GR', {
        weekday: 'long',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(dt);
}

function formatMonthTitle(date) {
    return new Intl.DateTimeFormat('el-GR', {
        month: 'long',
        year: 'numeric',
    }).format(date);
}

function badgeClass(status) {
    return `iw-lp-badge iw-lp-badge-${status}`;
}

function getSelectedRow() {
    return getVisibleRows().find((row) => row.id === state.selectedId) || state.rows.find((row) => row.id === state.selectedId) || null;
}

function getVisibleRows() {
    const q = state.ui.search.trim().toLowerCase();

    return state.rows.filter((row) => {
        if (state.ui.status !== 'all' && row.status !== state.ui.status) {
            return false;
        }

        if (state.ui.source !== 'all' && row.source !== state.ui.source) {
            return false;
        }

        if (!q) {
            return true;
        }

        const haystack = [
            row.program_title,
            row.school,
            row.teacher_name,
            row.teacher_email,
            row.teacher_phone,
            row.building,
            row.status_label,
            row.amount_label,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(q);
    });
}

function getPaginatedRows(rows) {
    const start = (state.ui.page - 1) * state.ui.perPage;
    return rows.slice(start, start + state.ui.perPage);
}

function getPageCount(total) {
    return Math.max(1, Math.ceil(total / state.ui.perPage));
}

function buildUiSummary(rows) {
    return rows.reduce(
        (acc, row) => {
            acc.total += 1;
            acc.tickets += Number(row.tickets_count || 0);
            acc.revenue += Number(row.amount || 0);
            acc.statuses[row.status] = (acc.statuses[row.status] || 0) + 1;
            acc.sources[row.source] = (acc.sources[row.source] || 0) + 1;
            return acc;
        },
        {
            total: 0,
            tickets: 0,
            revenue: 0,
            statuses: {},
            sources: {},
        }
    );
}

function buildSummaryFromRows(rows) {
    return {
        total_rows: rows.length,
        total_tickets: rows.reduce((sum, row) => sum + Number(row.tickets_count || 0), 0),
        total_revenue: rows.reduce((sum, row) => sum + Number(row.amount || 0), 0),
        reservations_count: rows.filter((row) => row.source === 'reservation').length,
        purchases_count: rows.filter((row) => row.source === 'purchase').length,
    };
}

function buildCalendarPayload(rows) {
    const grouped = new Map();

    rows.forEach((row) => {
        if (!row.date) return;

        if (!grouped.has(row.date)) {
            grouped.set(row.date, {
                date: row.date,
                count: 0,
                tickets_count: 0,
                revenue: 0,
                items: [],
            });
        }

        const entry = grouped.get(row.date);
        entry.count += 1;
        entry.tickets_count += Number(row.tickets_count || 0);
        entry.revenue += Number(row.amount || 0);
        entry.items.push(row);
    });

    return Array.from(grouped.values()).sort((a, b) => String(a.date).localeCompare(String(b.date)));
}

function buildCalendarCells() {
    const base = state.monthCursor ? new Date(state.monthCursor) : new Date();
    const year = base.getFullYear();
    const month = base.getMonth();
    const first = new Date(year, month, 1);
    const last = new Date(year, month + 1, 0);
    const startOffset = (first.getDay() + 6) % 7;
    const cells = [];
    const visibleRows = getVisibleRows();
    const calendarEntries = [];
    const grouped = new Map();
    visibleRows.forEach((row) => {
        const date = row.date;
        if (!date) return;
        if (!grouped.has(date)) {
            grouped.set(date, {
                date,
                count: 0,
                tickets_count: 0,
                revenue: 0,
                items: [],
            });
        }
        const entry = grouped.get(date);
        entry.count += 1;
        entry.tickets_count += Number(row.tickets_count || 0);
        entry.revenue += Number(row.amount || 0);
        entry.items.push(row);
    });
    grouped.forEach((value) => calendarEntries.push(value));
    const dayMap = new Map(calendarEntries.map((entry) => [entry.date, entry]));
    const today = new Date();

    for (let i = 0; i < startOffset; i += 1) {
        const d = new Date(year, month, i - startOffset + 1);
        cells.push({ date: d, outside: true, payload: null, isToday: false });
    }

    for (let day = 1; day <= last.getDate(); day += 1) {
        const d = new Date(year, month, day);
        const iso = toIsoDate(d);
        cells.push({
            date: d,
            outside: false,
            payload: dayMap.get(iso) || null,
            isToday:
                d.getFullYear() === today.getFullYear() &&
                d.getMonth() === today.getMonth() &&
                d.getDate() === today.getDate(),
        });
    }

    while (cells.length % 7 !== 0) {
        const day = cells.length - (startOffset + last.getDate()) + 1;
        const d = new Date(year, month + 1, day);
        cells.push({ date: d, outside: true, payload: null, isToday: false });
    }

    return cells;
}

function buildQuery() {
    const params = new URLSearchParams();

    if (state.filters.program_id) {
        params.set('program_id', String(state.filters.program_id));
    }

    if (state.filters.school_id) {
        params.set('school_id', String(state.filters.school_id));
    }

    if (state.filters.user_id) {
        params.set('user_id', String(state.filters.user_id));
    }

    if (state.filters.date_from) {
        params.set('date_from', state.filters.date_from);
    }

    if (state.filters.date_to) {
        params.set('date_to', state.filters.date_to);
    }

    params.set('view', state.filters.view);

    return params.toString();
}

async function loadData() {
    state.loading = true;
    state.error = '';
    render();

    try {
        const response = await fetch(`${config.restUrl}?${buildQuery()}`, {
            headers: {
                'X-WP-Nonce': config.nonce,
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        const data = await response.json();
        state.programs = Array.isArray(data.programs) ? data.programs : [];
        state.baseRows = Array.isArray(data.rows) ? data.rows : [];
        state.rows = [...state.baseRows];
        state.summary = data.summary || state.summary;
        state.calendar = Array.isArray(data.calendar) ? data.calendar : [];

        if (!state.monthCursor) {
            const referenceDate = state.rows[0]?.date || new Date().toISOString().slice(0, 10);
            state.monthCursor = `${referenceDate}T00:00:00`;
        }

        if (!getSelectedRow()) {
            state.selectedId = state.rows[0]?.id || null;
            state.ui.detailsOpen = false;
        }
    } catch (error) {
        state.error = config.strings?.error || 'Loading failed.';
        console.error(error);
    } finally {
        state.loading = false;
        render();
    }
}

function exportCsv() {
    const visibleRows = getVisibleRows();
    if (!visibleRows.length) {
        return;
    }

    const rows = [
        [
            'Date',
            'Time',
            'Program',
            'School',
            'Contact',
            'Phone',
            'Email',
            'Building',
            'Tickets',
            'Amount',
            'Status',
            'Type',
        ],
        ...visibleRows.map((row) => [
            row.date || '',
            row.time_range || '',
            row.program_title || '',
            row.school || '',
            row.teacher_name || '',
            row.teacher_phone || '',
            row.teacher_email || '',
            row.building || '',
            row.tickets_count || 0,
            row.amount || 0,
            row.status_label || '',
            row.source === 'reservation' ? 'Reservation' : 'Purchase',
        ]),
    ];

    const csv = rows
        .map((line) => line.map((cell) => `"${String(cell).replaceAll('"', '""')}"`).join(','))
        .join('\n');

    const blob = new Blob([`\ufeff${csv}`], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `learning-program-dashboard-${new Date().toISOString().slice(0, 10)}.csv`;
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 0);
}

function focusSearchInput(selectionStart = null, selectionEnd = null) {
    const input = root.querySelector('[data-ui-filter="search"]');
    if (!input) {
        return;
    }

    try {
        input.focus({ preventScroll: true });
    } catch (error) {
        input.focus();
    }

    if (selectionStart !== null && typeof input.setSelectionRange === 'function') {
        input.setSelectionRange(selectionStart, selectionEnd ?? selectionStart);
    }
}

function pad2(value) {
    return String(value).padStart(2, '0');
}

function toIsoDate(date) {
    return `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}`;
}

function todayIsoDate() {
    return toIsoDate(new Date());
}

function safeDateFromIso(value) {
    const raw = String(value || '');
    if (!/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        return null;
    }

    const [year, month, day] = raw.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    return Number.isNaN(date.getTime()) ? null : date;
}

function formatDateInputValue(value) {
    const date = safeDateFromIso(value);
    if (!date) {
        return '';
    }

    return `${pad2(date.getDate())}/${pad2(date.getMonth() + 1)}/${date.getFullYear()}`;
}

function shiftMonthIso(value, delta) {
    const date = safeDateFromIso(value) || new Date();
    date.setDate(1);
    date.setMonth(date.getMonth() + delta);
    return toIsoDate(date);
}

function openDatePicker(key) {
    state.ui.datePicker.key = key;
    state.ui.datePicker.cursor = state.filters[key] || todayIsoDate();
    render();
}

function closeDatePicker() {
    state.ui.datePicker.key = '';
    state.ui.datePicker.cursor = '';
}

function renderTable() {
    const visibleRows = getVisibleRows();
    const totalPages = getPageCount(visibleRows.length);
    const currentPage = Math.min(state.ui.page, totalPages);
    const paginatedRows = getPaginatedRows(visibleRows);

    if (state.ui.page !== currentPage) {
        state.ui.page = currentPage;
    }

    if (!visibleRows.length) {
        return `<div class="iw-lp-empty">${escapeHtml(config.strings?.empty || 'No rows found.')}</div>`;
    }

    const body = paginatedRows
        .map((row) => {
            const selectedClass = row.id === state.selectedId ? ' style="background: rgba(31,111,235,.06)"' : '';
            return `
                <tr data-row-id="${row.id}"${selectedClass}>
                    <td>
                        <div class="iw-lp-program-cell">
                            ${row.program_featured_image
                                ? `<a href="${escapeHtml(row.program_url || '#')}" target="_blank">
                                       <img class="iw-lp-program-thumb" src="${escapeHtml(row.program_featured_image)}" alt="${escapeHtml(row.program_title || '')}">
                                   </a>`
                                : `<a href="${escapeHtml(row.program_url || '#')}" target="_blank">
                                       <div class="iw-lp-program-thumb iw-lp-program-thumb--placeholder"></div>
                                   </a>`}
                            <div class="iw-lp-program-copy">
                                <a class="iw-lp-program-link" href="${escapeHtml(row.program_url || '#')}" target="_blank">
                                    ${escapeHtml(row.program_title || '')}
                                </a>
                                <div>${htmlOrFallback(row.building_html, '—')}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div><strong>${escapeHtml(formatDateLabel(row.date))}</strong></div>
                        <div class="iw-lp-meta"><span>${escapeHtml(row.time_range || '')}</span></div>
                    </td>
                    <td>${wrapHtmlLink(htmlOrFallback(row.school_html, ''), row.school_edit_url, 'iw-lp-inline-link')}</td>
                    <td>
                        <div><strong>${wrapHtmlLink(escapeHtml(row.teacher_name || '—'), row.teacher_edit_url, 'iw-lp-inline-link')}</strong></div>
                        <div>${escapeHtml(row.teacher_phone || '')}</div>
                    </td>
                    <td>${escapeHtml(String(row.tickets_count || 0))}</td>
                    <td>${htmlOrFallback(row.amount_label_html, escapeHtml(formatMoney(row.amount)))}</td>
                    <td><span class="${badgeClass(row.status)}">${escapeHtml(row.status_label || row.status || '')}</span></td>
                    <td>${escapeHtml(row.source === 'reservation' ? 'Reservation' : 'Purchase')}</td>
                </tr>
            `;
        })
        .join('');

    return `
        <div class="iw-lp-table-wrap">
            <table class="iw-lp-table">
                <thead>
                    <tr>
                        <th>Program</th>
                        <th>Date</th>
                        <th>School</th>
                        <th>Contact</th>
                        <th>Seats</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        </div>
        <div class="iw-lp-pagination">
            <span>Page ${currentPage} of ${totalPages}</span>
            <div class="iw-lp-pagination-actions">
                <button type="button" class="iw-lp-page-button" data-action="page-first" aria-label="First page" ${currentPage <= 1 ? 'disabled' : ''}>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 12 12l6 6M11 6l-6 6 6 6"/></svg>
                </button>
                <button type="button" class="iw-lp-page-button" data-action="page-prev" aria-label="Previous page" ${currentPage <= 1 ? 'disabled' : ''}>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
                </button>
                <button type="button" class="iw-lp-page-button" data-action="page-next" aria-label="Next page" ${currentPage >= totalPages ? 'disabled' : ''}>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                </button>
                <button type="button" class="iw-lp-page-button" data-action="page-last" aria-label="Last page" ${currentPage >= totalPages ? 'disabled' : ''}>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 6 6-6 6M13 6l6 6-6 6"/></svg>
                </button>
            </div>
        </div>
    `;
}

function renderCalendar() {
    const cells = buildCalendarCells();
    const visibleRows = getVisibleRows();
    const uiSummary = buildUiSummary(visibleRows);
    const activeFilterText = getActiveFilterText();
    const busiestDay = buildCalendarPayload(visibleRows).reduce((max, day) => Math.max(max, Number(day.count || 0)), 0);
    const monthTitle = formatMonthTitle(new Date(state.monthCursor));

    return `
        <div class="iw-lp-panel">
            <div class="iw-lp-toolbar iw-lp-calendar-toolbar">
                <div class="iw-lp-calendar-nav" aria-label="Calendar navigation">
                    <div class="iw-lp-calendar-heading">
                        <div class="iw-lp-calendar-title-row">
                            <h3 class="iw-lp-panel-title iw-lp-calendar-title">${escapeHtml(monthTitle)}</h3>
                            <div class="iw-lp-calendar-controls">
                                <button type="button" class="iw-lp-calendar-nav-button" data-action="prev-month" aria-label="Previous month">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m15 6-6 6 6 6"/></svg>
                                </button>
                                <button type="button" class="iw-lp-calendar-nav-button" data-action="next-month" aria-label="Next month">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"/></svg>
                                </button>
                                <button type="button" class="iw-lp-calendar-today" data-action="this-month">Today</button>
                            </div>
                        </div>
                        <p class="iw-lp-panel-copy iw-lp-calendar-count">${visibleRows.length} entries in the current view${activeFilterText ? `<span class="iw-lp-active-filter">Showing ${escapeHtml(activeFilterText)}</span>` : ''}</p>
                    </div>
                </div>
                ${renderToolbarActions()}
            </div>
            ${renderFilterControls(uiSummary)}
            <div class="iw-lp-calendar-grid">
                ${weekdays.map((label) => `<div class="iw-lp-calendar-weekday">${escapeHtml(label)}</div>`).join('')}
                ${cells.map((cell) => {
                    const iso = cell.date.toISOString().slice(0, 10);
                    const payload = cell.payload;
                    const events = (payload?.items || [])
                        .slice(0, 3)
                        .map((item) => `
                            <button type="button" class="iw-lp-calendar-event" data-row-id="${item.id}">
                                <strong>${escapeHtml(item.time_range || '')}</strong>
                                <span>${escapeHtml(item.program_title || '')}</span>
                                <small>${escapeHtml(`Seats ${payload.tickets_count} • Amount ${formatMoney(payload.revenue || 0)}`)}</small>
                            </button>
                        `)
                        .join('');

                    const density = payload && busiestDay > 0 ? Math.max(0.12, payload.count / busiestDay) : 0;
                    return `
                        <div class="iw-lp-calendar-cell ${cell.outside ? 'is-outside' : ''} ${cell.isToday ? 'is-today' : ''}" style="${payload ? `--iw-density:${density};` : ''}">
                            <div class="iw-lp-calendar-date">
                                <span>${cell.date.getDate()}</span>
                                ${payload ? `<span class="${badgeClass(payload.count > 0 ? 'confirmed' : 'pending')}">${payload.count}</span>` : ''}
                            </div>
                            ${payload ? `
                                <div class="iw-lp-calendar-list">${events}</div>
                                ${(payload.items || []).length > 3 ? `<small>+${payload.items.length - 3} more</small>` : ''}
                            ` : `<div class="iw-lp-calendar-list"></div>`}
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
}

function renderViewSwitch() {
    return `
        <div class="iw-lp-view-switch">
            <button type="button" data-action="switch-view" data-view="table" class="${state.filters.view === 'table' ? 'is-active' : ''}">Table</button>
            <button type="button" data-action="switch-view" data-view="calendar" class="${state.filters.view === 'calendar' ? 'is-active' : ''}">Calendar</button>
        </div>
    `;
}

function renderToolbarActions(extraActions = '') {
    const hasVisibleRows = getVisibleRows().length > 0;

    return `
        <div class="iw-lp-toolbar-actions">
            ${renderViewSwitch()}
            <button type="button" class="iw-lp-button iw-lp-button-primary iw-lp-export-button" data-action="export-csv" ${hasVisibleRows ? '' : 'disabled'}>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 19h14"/></svg>
                <span>CSV</span>
            </button>
            ${extraActions}
        </div>
    `;
}

function renderFilterControls(uiSummary) {
    const renderDateField = (key, label, id) => `
        <div class="iw-lp-field iw-lp-date-field">
            <label for="${id}">${label}</label>
            <button type="button" id="${id}" class="iw-lp-date-input" data-action="open-date-picker" data-filter-key="${key}">
                <span>${escapeHtml(formatDateInputValue(state.filters[key]) || 'dd/mm/yyyy')}</span>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 2v4m8-4v4M4 10h16M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>
            </button>
            ${renderDatePicker(key)}
        </div>
    `;

    return `
        <div class="iw-lp-filter-bar">
            <div class="iw-lp-status-tabs">
                <button type="button" class="${state.ui.status === 'all' ? 'is-active' : ''}" data-action="set-status" data-status="all">All <span>${state.summary.total_rows}</span></button>
                <button type="button" class="${state.ui.status === 'reserved' ? 'is-active' : ''}" data-action="set-status" data-status="reserved">Reserved <span>${uiSummary.statuses.reserved || 0}</span></button>
                <button type="button" class="${state.ui.status === 'confirmed' ? 'is-active' : ''}" data-action="set-status" data-status="confirmed">Confirmed <span>${uiSummary.statuses.confirmed || 0}</span></button>
                <button type="button" class="${state.ui.status === 'completed' ? 'is-active' : ''}" data-action="set-status" data-status="completed">Completed <span>${uiSummary.statuses.completed || 0}</span></button>
                <button type="button" class="${state.ui.status === 'cancelled' ? 'is-active' : ''}" data-action="set-status" data-status="cancelled">Cancelled <span>${uiSummary.statuses.cancelled || 0}</span></button>
                <button type="button" class="iw-lp-search-toggle ${state.ui.filtersOpen ? 'is-active' : ''}" data-action="toggle-filters" aria-label="Search bookings">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m21 21-4.35-4.35m2.35-5.15a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z"/></svg>
                </button>
                <button type="button" class="iw-lp-preset-chip" data-action="preset-range" data-range="next-30">Next 30 days</button>
                <button type="button" class="iw-lp-preset-chip" data-action="preset-range" data-range="this-month">This month</button>
            </div>
        </div>

        <div class="iw-lp-search-box" ${state.ui.filtersOpen ? '' : 'hidden'}>
            <div class="iw-lp-search-head">
                <div>
                    <h3>Search bookings</h3>
                    <p>Search and filter learning program bookings by program, date, type, school, or contact.</p>
                </div>
            </div>
            <div class="iw-lp-search-row">
                <div class="iw-lp-field">
                    <label for="iw-lp-search">Search</label>
                    <input id="iw-lp-search" type="search" data-ui-filter="search" value="${escapeHtml(state.ui.search || '')}" placeholder="program, school, contact">
                </div>
                <div class="iw-lp-field">
                    <label for="iw-lp-source">Type</label>
                    <select id="iw-lp-source" data-ui-filter="source">
                        <option value="all" ${state.ui.source === 'all' ? 'selected' : ''}>All</option>
                        <option value="reservation" ${state.ui.source === 'reservation' ? 'selected' : ''}>Reservations</option>
                        <option value="purchase" ${state.ui.source === 'purchase' ? 'selected' : ''}>Purchases</option>
                    </select>
                </div>
                ${renderDateField('date_from', 'From', 'iw-lp-date-from')}
                ${renderDateField('date_to', 'To', 'iw-lp-date-to')}
                <div class="iw-lp-search-actions">
                    <button type="button" class="iw-lp-button iw-lp-button-secondary" data-action="reset-filters">Reset</button>
                    <button type="button" class="iw-lp-button iw-lp-button-primary iw-lp-search-submit" data-action="apply-filters">Search bookings</button>
                </div>
            </div>
        </div>
    `;
}

function renderDatePicker(key) {
    if (state.ui.datePicker.key !== key) {
        return '';
    }

    const selected = state.filters[key] || '';
    const cursorDate = safeDateFromIso(state.ui.datePicker.cursor || selected) || new Date();
    const year = cursorDate.getFullYear();
    const month = cursorDate.getMonth();
    const first = new Date(year, month, 1);
    const startOffset = (first.getDay() + 6) % 7;
    const start = new Date(year, month, 1 - startOffset);
    const today = todayIsoDate();
    const cells = [];

    for (let index = 0; index < 42; index += 1) {
        const cellDate = new Date(start);
        cellDate.setDate(start.getDate() + index);
        const iso = toIsoDate(cellDate);
        cells.push({
            iso,
            day: cellDate.getDate(),
            outside: cellDate.getMonth() !== month,
            selected: iso === selected,
            today: iso === today,
        });
    }

    return `
        <div class="iw-lp-date-popover">
            <div class="iw-lp-date-head">
                <strong>${escapeHtml(formatMonthTitle(first))}</strong>
                <div class="iw-lp-date-nav">
                    <button type="button" data-action="date-prev-month" data-filter-key="${key}" aria-label="Previous month">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
                    </button>
                    <button type="button" data-action="date-next-month" data-filter-key="${key}" aria-label="Next month">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                    </button>
                </div>
            </div>
            <div class="iw-lp-date-weekdays">
                ${weekdays.map((day) => `<span>${escapeHtml(day.slice(0, 1))}</span>`).join('')}
            </div>
            <div class="iw-lp-date-grid">
                ${cells.map((cell) => `
                    <button
                        type="button"
                        class="${cell.outside ? 'is-outside' : ''} ${cell.selected ? 'is-selected' : ''} ${cell.today ? 'is-today' : ''}"
                        data-action="pick-date"
                        data-filter-key="${key}"
                        data-date="${cell.iso}"
                    >${cell.day}</button>
                `).join('')}
            </div>
            <div class="iw-lp-date-footer">
                <button type="button" data-action="clear-date" data-filter-key="${key}">Clear</button>
                <button type="button" data-action="pick-date" data-filter-key="${key}" data-date="${today}">Today</button>
            </div>
        </div>
    `;
}

function renderDetails() {
    const row = getSelectedRow();

    if (!state.ui.detailsOpen || !row) {
        return '';
    }

    const visitors = Array.isArray(row.visitors) ? row.visitors : [];

    return `
        <div class="iw-lp-modal-overlay">
            <button type="button" class="iw-lp-modal-backdrop" data-action="close-details" aria-label="Close details"></button>
            <section class="iw-lp-modal" role="dialog" aria-modal="true" aria-labelledby="iw-lp-details-title">
                <button type="button" class="iw-lp-modal-close" data-action="close-details" aria-label="Close details">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            <div class="iw-lp-side-section">
               
                <h3 class="iw-lp-panel-title" id="iw-lp-details-title">${escapeHtml(row.program_title || '')}</h3>
                <p class="iw-lp-panel-copy">${escapeHtml(formatDateLabel(row.date))} • ${escapeHtml(row.time_range || '')}</p>
            </div>

            <div class="iw-lp-side-section">
                <div class="iw-lp-detail-card">
                    <h4>${wrapHtmlLink(escapeHtml(row.teacher_name || 'No name'), row.teacher_edit_url, 'iw-lp-inline-link')}</h4>
                    <div class="iw-lp-meta">
                        <span class="${badgeClass(row.status)}">${escapeHtml(row.status_label || '')}</span>
                        <span class="iw-lp-badge">${escapeHtml(row.source === 'reservation' ? 'Reservation' : 'Purchase')}</span>
                    </div>
                </div>
            </div>

            <div class="iw-lp-side-section">
                <h4 class="iw-lp-side-title">Details</h4>
                <div class="iw-lp-detail-grid">
                    <div class="iw-lp-detail-item"><small>School</small><strong>${wrapHtmlLink(htmlOrFallback(row.school_html, ''), row.school_edit_url, 'iw-lp-inline-link')}</strong></div>
                    <div class="iw-lp-detail-item"><small>Building</small><strong>${htmlOrFallback(row.building_html, '—')}</strong></div>
                    <div class="iw-lp-detail-item"><small>Phone</small><strong>${escapeHtml(row.teacher_phone || '—')}</strong></div>
                    <div class="iw-lp-detail-item"><small>Email</small><strong>${escapeHtml(row.teacher_email || '—')}</strong></div>
                    <div class="iw-lp-detail-item"><small>Seats</small><strong>${escapeHtml(String(row.tickets_count || 0))}</strong></div>
                    <div class="iw-lp-detail-item"><small>Amount</small><strong>${htmlOrFallback(row.amount_label_html, escapeHtml(formatMoney(row.amount)))}</strong></div>
                    <div class="iw-lp-detail-item"><small>Order Status</small><strong>${escapeHtml(row.order_status_label || row.order_status || '—')}</strong></div>
                    <div class="iw-lp-detail-item"><small>Order ID</small><strong>#${escapeHtml(String(row.order_id || '—'))}</strong></div>
                </div>
            </div>

            <div class="iw-lp-side-section">
                <h4 class="iw-lp-side-title">Participants</h4>
                <div class="iw-lp-detail-card">
                    ${visitors.length ? visitors.map((visitor, index) => {
                        const visitorPrice = Number(visitor.price || 0);
                        const fallbackUnitPrice = visitors.length > 0 ? Number(row.amount || 0) / visitors.length : 0;
                        const priceLabel = visitorPrice > 0
                            ? formatMoney(visitorPrice)
                            : (fallbackUnitPrice > 0 ? formatMoney(fallbackUnitPrice) : '—');
                        const categoryLabel = visitor['category-name'] || visitor['category-id'] || '';
                        const rightParts = [categoryLabel, priceLabel].filter(Boolean);
                        return `
                        <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:${index < visitors.length - 1 ? '1px solid rgba(23,32,51,.08)' : '0'}">
                            <span>${escapeHtml(`${visitor.first || ''} ${visitor.last || ''}`.trim() || `Ticket ${index + 1}`)}</span>
                            <span>${escapeHtml(rightParts.join(' • '))}</span>
                        </div>
                    `;
                    }).join('') : '<span>No participant details available.</span>'}
                </div>
            </div>

            ${row.notes ? `
                <div class="iw-lp-side-section">
                    <h4 class="iw-lp-side-title">Notes</h4>
                    <div class="iw-lp-detail-card">${escapeHtml(row.notes)}</div>
                </div>
            ` : ''}
            </section>
        </div>
    `;
}

function getActiveFilterText() {
    const activeProgramTitle = state.filters.program_id
        ? state.context.programLabel || state.rows.find((row) => Number(row.program_id) === Number(state.filters.program_id))?.program_title || `program #${state.filters.program_id}`
        : 'All programs';
    const activeFilterParts = [
        state.filters.program_id ? `program: ${activeProgramTitle}` : '',
        state.filters.school_id ? `school: ${state.context.schoolLabel || `#${state.filters.school_id}`}` : '',
        state.filters.user_id ? `user: ${state.context.userLabel || `#${state.filters.user_id}`}` : '',
        state.filters.date_from ? `from ${state.filters.date_from}` : '',
        state.filters.date_to ? `to ${state.filters.date_to}` : '',
        state.ui.status !== 'all' ? `status: ${state.ui.status}` : '',
        state.ui.source !== 'all' ? `type: ${state.ui.source}` : '',
    ].filter(Boolean);

    return activeFilterParts.join(' / ');
}

function render() {
    const visibleRows = getVisibleRows();
    const uiSummary = buildUiSummary(visibleRows);
    const activeFilterText = getActiveFilterText();

    if (state.loading) {
        root.innerHTML = `<div class="iw-lp-state">${escapeHtml(config.strings?.loading || 'Loading…')}</div>`;
        return;
    }

    if (state.error) {
        root.innerHTML = `<div class="iw-lp-error">${escapeHtml(state.error)}</div>`;
        return;
    }

    root.innerHTML = `
        <div class="iw-lp-dashboard">
            <section class="iw-lp-summary" aria-label="Learning program overview">
                <article class="iw-lp-card">
                    <p class="iw-lp-card-label">Visible Entries</p>
                    <p class="iw-lp-card-value">${uiSummary.total}</p>
                    <div class="iw-lp-card-sub">Out of ${state.summary.total_rows} total</div>
                </article>
                <article class="iw-lp-card">
                    <p class="iw-lp-card-label">Seats</p>
                    <p class="iw-lp-card-value">${uiSummary.tickets}</p>
                    <div class="iw-lp-card-sub">Total visible bookings</div>
                </article>
                <article class="iw-lp-card">
                    <p class="iw-lp-card-label">Visible Revenue</p>
                    <p class="iw-lp-card-value">${escapeHtml(formatMoney(uiSummary.revenue))}</p>
                    <div class="iw-lp-card-sub">${uiSummary.sources.purchase || 0} purchases</div>
                </article>
                <article class="iw-lp-card">
                    <p class="iw-lp-card-label">Open Reservations</p>
                    <p class="iw-lp-card-value">${(uiSummary.statuses.reserved || 0) + (uiSummary.statuses.confirmed || 0)}</p>
                    <div class="iw-lp-card-sub">${uiSummary.statuses.completed || 0} completed</div>
                </article>
            </section>

            <section class="iw-lp-layout">
                ${state.filters.view === 'calendar'
                    ? renderCalendar()
                    : `<div class="iw-lp-panel">
                        <div class="iw-lp-toolbar">
                            <div>
                                <h2 class="iw-lp-panel-title">Results table</h2>
                                <p class="iw-lp-panel-copy">Review the filtered results in a compact operational table with quick status scanning.${activeFilterText ? `<span class="iw-lp-active-filter">Showing ${escapeHtml(activeFilterText)}</span>` : ''}</p>
                            </div>
                            ${renderToolbarActions()}
                        </div>
                        ${renderFilterControls(uiSummary)}
                        ${renderTable()}
                    </div>`}
            </section>
            ${renderDetails()}
        </div>
    `;
}

root.addEventListener('click', (event) => {
    if (event.target.closest('a')) {
        return;
    }

    const target = event.target.closest('[data-action], [data-row-id]');
    const action = target?.dataset.action || '';
    const insideDateField = event.target.closest('.iw-lp-date-field');

    if (state.ui.datePicker.key && !insideDateField) {
        closeDatePicker();
        if (!target) {
            render();
            return;
        }
    }

    if (!target) return;

    if (target.dataset.rowId) {
        state.selectedId = Number(target.dataset.rowId);
        state.ui.detailsOpen = true;
        render();
        return;
    }

    if (action === 'close-details') {
        state.ui.detailsOpen = false;
        render();
        return;
    }

    if (action === 'switch-view') {
        const nextView = target.dataset.view || 'table';
        state.filters.view = nextView;
        if (nextView === 'calendar') {
            state.monthCursor = `${todayIsoDate()}T00:00:00`;
        }
        render();
        return;
    }

    if (action === 'toggle-filters') {
        const shouldFocusSearch = !state.ui.filtersOpen;
        state.ui.filtersOpen = shouldFocusSearch;
        closeDatePicker();
        render();
        if (shouldFocusSearch) {
            window.requestAnimationFrame(() => focusSearchInput());
        }
        return;
    }

    if (action === 'open-date-picker') {
        openDatePicker(target.dataset.filterKey || '');
        return;
    }

    if (action === 'date-prev-month' || action === 'date-next-month') {
        const delta = action === 'date-prev-month' ? -1 : 1;
        state.ui.datePicker.key = target.dataset.filterKey || state.ui.datePicker.key;
        state.ui.datePicker.cursor = shiftMonthIso(state.ui.datePicker.cursor || todayIsoDate(), delta);
        render();
        return;
    }

    if (action === 'pick-date') {
        const key = target.dataset.filterKey || '';
        if (key) {
            state.filters[key] = target.dataset.date || '';
        }
        closeDatePicker();
        render();
        return;
    }

    if (action === 'clear-date') {
        const key = target.dataset.filterKey || '';
        if (key) {
            state.filters[key] = '';
        }
        closeDatePicker();
        render();
        return;
    }

    if (action === 'apply-filters') {
        state.filters.view = 'table';
        state.ui.detailsOpen = false;
        closeDatePicker();
        loadData();
        return;
    }

    if (action === 'reset-filters') {
        state.filters.program_id = 0;
        state.filters.school_id = contextFilters.school_id;
        state.filters.user_id = contextFilters.user_id;
        state.filters.date_from = '';
        state.filters.date_to = '';
        state.ui.search = getContextSearchDefault();
        state.ui.status = 'all';
        state.ui.source = 'all';
        state.ui.detailsOpen = false;
        closeDatePicker();
        state.monthCursor = null;
        loadData();
        return;
    }

    if (action === 'export-csv') {
        exportCsv();
        return;
    }

    if (action === 'page-first') {
        state.ui.page = 1;
        render();
        return;
    }

    if (action === 'page-prev') {
        state.ui.page = Math.max(1, state.ui.page - 1);
        render();
        return;
    }

    if (action === 'page-next') {
        state.ui.page = Math.min(getPageCount(getVisibleRows().length), state.ui.page + 1);
        render();
        return;
    }

    if (action === 'page-last') {
        state.ui.page = getPageCount(getVisibleRows().length);
        render();
        return;
    }

    if (action === 'prev-month') {
        const current = new Date(state.monthCursor);
        current.setMonth(current.getMonth() - 1);
        state.monthCursor = current.toISOString();
        render();
        return;
    }

    if (action === 'next-month') {
        const current = new Date(state.monthCursor);
        current.setMonth(current.getMonth() + 1);
        state.monthCursor = current.toISOString();
        render();
        return;
    }

    if (action === 'this-month') {
        state.monthCursor = `${todayIsoDate()}T00:00:00`;
        render();
        return;
    }

    if (action === 'set-status') {
        state.ui.status = target.dataset.status || 'all';
        state.ui.page = 1;
        closeDatePicker();
        if (!getSelectedRow()) {
            state.selectedId = getVisibleRows()[0]?.id || null;
        }
        render();
        return;
    }

    if (action === 'preset-range') {
        const range = target.dataset.range || '';
        const now = new Date();
        closeDatePicker();
        if (range === 'next-30') {
            const from = toIsoDate(now);
            const toDate = new Date(now);
            toDate.setDate(toDate.getDate() + 30);
            state.filters.date_from = from;
            state.filters.date_to = toIsoDate(toDate);
        }

        if (range === 'this-month') {
            const from = new Date(now.getFullYear(), now.getMonth(), 1);
            const to = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            state.filters.date_from = toIsoDate(from);
            state.filters.date_to = toIsoDate(to);
        }

        loadData();
    }
});

root.addEventListener('change', (event) => {
    const target = event.target.closest('[data-filter], [data-ui-filter]');
    if (!target) return;

    const serverKey = target.dataset.filter;
    const uiKey = target.dataset.uiFilter;

    if (serverKey) {
        state.filters[serverKey] = serverKey === 'program_id' ? Number(target.value || 0) : target.value;
    }

    if (uiKey) {
        state.ui[uiKey] = target.value;
        state.ui.page = 1;
        if (!getSelectedRow()) {
            state.selectedId = getVisibleRows()[0]?.id || null;
        }
        render();
    }
});

root.addEventListener('keydown', (event) => {
    const dateInput = event.target.closest('.iw-lp-date-input');
    if (!dateInput) {
        return;
    }

    if (event.key === 'Tab') {
        return;
    }

    if (event.key === 'Escape') {
        closeDatePicker();
        render();
        return;
    }

    event.preventDefault();

    openDatePicker(dateInput.dataset.filterKey || '');
});

root.addEventListener('input', (event) => {
    const target = event.target.closest('[data-ui-filter="search"]');
    if (!target) return;

    const selectionStart = target.selectionStart;
    const selectionEnd = target.selectionEnd;
    state.ui.search = target.value;
    state.ui.page = 1;
    if (!getSelectedRow()) {
        state.selectedId = getVisibleRows()[0]?.id || null;
    }
    render();
    window.requestAnimationFrame(() => focusSearchInput(selectionStart, selectionEnd));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && state.ui.datePicker.key) {
        closeDatePicker();
        render();
        return;
    }

    if (event.key !== 'Escape' || !state.ui.detailsOpen) {
        return;
    }

    state.ui.detailsOpen = false;
    render();
});

loadData();
