import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);

        this.events = {
            click: { 'filter-button': 'filterButtonClick', 'search-clear': 'searchClearClick', 'page-button': 'pageButtonClick', 'seat-action': 'seatActionClick', 'logo-remove': 'logoRemoveClick' },
            submit: { 'search-form': 'searchFormSubmit', 'invite-form': 'inviteFormSubmit', 'logo-form': 'logoFormSubmit' },
            input: { 'search-input': 'searchInputChange',},
            change: { 'file': 'fileChange', 'logo-file': 'logoFileChange' },
        };

        this.config = JSON.parse(this.el.dataset.iwcmOwnerConfig || '{}');
        this.tableWrap = this.$('table-wrap')[0];
        this.tbody = this.$('table-body')[0];
        this.emptyMessage = this.$('empty-message')[0];
        this.filters = [...this.$('filter-button')];
        this.counts = [...this.$('count')];

        this.searchForm = this.$('search-form')[0];
        this.searchInput = this.$('search-input')[0];
        this.searchClear = this.$('search-clear')[0];

        this.inviteForm = this.$('invite-form')[0];
        this.fileInput = this.$('file')[0];
        this.fileName = this.$('file-name')[0];
        this.success = this.$('success')[0];
        this.error = this.$('error')[0];
        this.pagination = this.$('pagination')[0];
        this.pageInfo = this.$('pagination-info')[0];
        this.paginationPages = this.$('pagination-pages')[0];
        this.usageBar = this.$('usage-bar')[0];
        this.seatsLabel = this.$('seats-label')[0];
        this.availableLabel = this.$('available-label')[0];
        this.seatsLimit = parseInt((this.seatsLabel ? this.seatsLabel.textContent.split('/')[1] : '0') || '0', 10) || 0;
        this.page = parseInt(this.el.dataset.page || '1', 10) || 1;
        this.perPage = parseInt(this.el.dataset.perPage || '10', 10) || 10;
        this.total = parseInt(this.el.dataset.total || '0', 10) || 0;
        this.totalPages = parseInt(this.el.dataset.totalPages || '1', 10) || 1;
        this.status = 'all';
        this.search = '';
        this.messageTimer = null;
        this.logoMessageTimer = null;
        this.importTypeInputs = [...this.el.querySelectorAll('input[name="import_type"]')];
        this.importTabs = this.el.querySelector('[data-tabs="tabs"]');
        this.importTabs = this.importTabs ? [...this.importTabs.children] : [];
        this.logoForm = this.$('logo-form')[0];
        this.logoFile = this.$('logo-file')[0];
        this.logoFileName = this.$('logo-file-name')[0];
        this.logoPreviewImage = this.$('logo-preview-image')[0];
        this.logoPlaceholder = this.$('logo-placeholder')[0];
        this.logoRemove = this.$('logo-remove')[0];
        this.logoSave = this.$('logo-save')[0];
        this.logoRemoveInput = this.$('logo-remove-input')[0];
        this.logoNotice = this.$('logo-notice')[0];
        this.logoSuccess = this.$('logo-success')[0];
        this.logoError = this.$('logo-error')[0];
        this.companyLogoDisplay = this.$('company-logo-display')[0];
        this.logoDropzone = this.$('logo-dropzone')[0];
        this.logoObjectUrl = '';
        this.logoSavedUrl = this.logoPreviewImage && !this.logoPreviewImage.classList.contains('hidden') ? this.logoPreviewImage.src : '';
        this.logoSavedName = this.logoSavedUrl && this.logoFileName ? this.logoFileName.textContent : '';
        this.logoDirty = false;

        this.updateSearchClear();
        this.updateUsageBar(0);
        this.addImportTypeEvents();
        this.addLogoDropEvents();
        this.setLogoDirty(false);
        this.updateImportTypeTabs();
        this.updatePagination();
    }

    label(key) {
        return this.el.dataset[key] || this.config.labels?.[key] || '';
    }

    addImportTypeEvents() {
        this.importTypeInputs.forEach((input) => {
            input.addEventListener('change', () => this.updateImportTypeTabs());
        });
    }

    addLogoDropEvents() {
        if (!this.logoDropzone || !this.logoFile) return;

        ['dragenter', 'dragover'].forEach((eventName) => {
            this.logoDropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                this.logoDropzone.classList.add('dragging');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            this.logoDropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                this.logoDropzone.classList.remove('dragging');
            });
        });

        this.logoDropzone.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];
            if (!file) return;

            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.logoFile.files = transfer.files;
            this.logoFileChange();
        });
    }

    updateImportTypeTabs() {
        if (!this.importTabs.length) return;

        const selected = this.importTypeInputs.find((input) => input.checked)?.value || 'csv';
        const activeIndex = selected === 'data-entry' ? 1 : 0;

        this.importTabs.forEach((tab, index) => {
            tab.classList.toggle('active', index === activeIndex);
            tab.classList.toggle('force-validation', index === activeIndex);
        });

        window.dispatchEvent(new Event('resize'));
    }

    readSearch() {
        return String(this.searchInput.value || '').trim();
    }

    updateSearchClear() {
        if ( ! this.searchClear ) return;
        this.searchClear.classList.toggle('active', Boolean(this.readSearch()));
    }

    searchFormSubmit(e) {
        e.preventDefault();
        this.search = this.readSearch();
        this.updateSearchClear();
        this.searchForm?.classList.add('loading');
        this.loadEmployees(1);
    }

    searchInputChange() {
        this.updateSearchClear();
    }

    searchClearClick() {
        this.searchInput.value = '';
        this.search = '';
        this.updateSearchClear();
        this.searchForm?.classList.add('loading');
        this.loadEmployees(1);
    }






    escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
        });
    }

    setMessage(element, message) {
        window.clearTimeout(this.messageTimer);

        if (this.success) this.success.hidden = true;
        if (this.error) this.error.hidden = true;
        if (!element) return;

        element.textContent = message || '';
        element.hidden = false;
        this.messageTimer = window.setTimeout(function () {
            element.hidden = true;
        }, 3500);
    }

    setInlineMessage(successEl, errorEl, element, message) {
        window.clearTimeout(this.logoMessageTimer);

        if (this.logoNotice) this.logoNotice.hidden = true;
        if (successEl) successEl.hidden = true;
        if (errorEl) errorEl.hidden = true;
        if (!element) return;

        element.textContent = message || '';
        element.hidden = false;
        this.logoMessageTimer = window.setTimeout(function () {
            element.hidden = true;
        }, 3500);
    }

    setBusy(isBusy) {
        if (!this.tableWrap) return;

        this.tableWrap.style.opacity = isBusy ? '0.45' : '';
        this.tableWrap.style.pointerEvents = isBusy ? 'none' : '';
    }



    renderEmployees(rowsHtml, total = 0) {
        if (!this.tbody) return;

        const hasRows = total > 0 && rowsHtml;
        this.tbody.innerHTML = hasRows ? rowsHtml : '';
        this.tableWrap?.classList.toggle('hidden', !hasRows);
        if (this.emptyMessage) {
            this.emptyMessage.textContent = this.label('empty');
            this.emptyMessage.classList.toggle('hidden', Boolean(hasRows));
        }
    }

    updateCounts(nextCounts) {
        this.counts.forEach((item) => {
            const key = item.dataset.count || item.dataset.iwcmOwnerCount || '';
            item.textContent = String(nextCounts[key] || 0);
        });

        const used = parseInt(nextCounts.all || 0, 10) || 0;
        const available = Math.max(0, this.seatsLimit - used);

        if (this.seatsLabel) this.seatsLabel.textContent = `${used} / ${this.seatsLimit}`;
        if (this.availableLabel) {
            const template = this.label(available === 1 ? 'availableOne' : 'availableMany');
            this.availableLabel.textContent = template.replace('%s', String(available));
        }
        this.updateUsageBar(used);
    }

    updateUsageBar(used) {
        if (!this.usageBar) return;

        if (!used && this.seatsLabel) {
            used = parseInt((this.seatsLabel.textContent.split('/')[0] || '0'), 10) || 0;
        }

        const percent = this.seatsLimit > 0 ? Math.min(100, Math.round((used / this.seatsLimit) * 100)) : 0;
        this.usageBar.style.width = `${percent}%`;
        this.usageBar.classList.toggle('limited', percent >= 75 && percent < 90);
        this.usageBar.classList.toggle('full', percent >= 90);
    }

    getVisiblePages() {
        const start = Math.max(1, Math.min(this.page - 1, this.totalPages - 2));
        const end = Math.min(this.totalPages, start + 2);
        const pages = [];

        for (let page = start; page <= end; page += 1) {
            pages.push(page);
        }

        return pages;
    }

    pageButton(page) {
        const isCurrent = page === this.page;
        const classes = [
            'shrink-0',
            'size-40',
            'flex',
            'items-center',
            'justify-center',
            'rounded-full',
            'transition-all',
            'duration-300',
            isCurrent ? 'bg-dark text-white' : 'border border-[1.5px] border-medium hover:underline',
        ].join(' ');

        return `<button type="button" class="${classes}" data-corporate-membership="page-button" data-page-action="page" data-page="${page}"${isCurrent ? ' disabled' : ''}>${page}</button>`;
    }

    pageEllipsis() {
        return '<span class="shrink-0 text-medium">...</span>';
    }

    renderPaginationPages() {
        if (!this.paginationPages) return;

        const visiblePages = this.getVisiblePages();
        const firstVisible = visiblePages[0] || 1;
        const lastVisible = visiblePages[visiblePages.length - 1] || this.totalPages;
        const items = [];

        if (!visiblePages.includes(1)) {
            items.push(this.pageButton(1));
            if (firstVisible > 2) {
                items.push(this.pageEllipsis());
            }
        }

        visiblePages.forEach((page) => {
            items.push(this.pageButton(page));
        });

        if (!visiblePages.includes(this.totalPages)) {
            if (lastVisible < this.totalPages - 1) {
                items.push(this.pageEllipsis());
            }
            items.push(this.pageButton(this.totalPages));
        }

        this.paginationPages.innerHTML = items.join('');
    }

    updatePagination() {
        if (!this.pagination) return;

        const isHidden = this.total <= 0 || this.totalPages <= 1;
        this.pagination.hidden = isHidden;
        this.pagination.classList.toggle('hidden', isHidden);

        if (this.pageInfo) {
            this.pageInfo.textContent = this.label('page')
                .replace('%1$d', this.page)
                .replace('%2$d', this.totalPages);
        }

        this.renderPaginationPages();

        this.$('page-button').forEach((button) => {
            const action = button.dataset.pageAction;
            const isDisabled = action === 'page'
                ? parseInt(button.dataset.page || '0', 10) === this.page
                : (action === 'first' || action === 'prev') ? this.page <= 1 : this.page >= this.totalPages;
            button.disabled = isDisabled;
            button.classList.toggle('disabled', isDisabled);
        });
    }

    loadEmployees(nextPage) {
        this.page = Math.max(1, nextPage || 1);
        this.setBusy(true);

        const params = new URLSearchParams({
            action: 'iw_corporate_owner_employees',
            nonce: this.config.employeesNonce || '',
            employee_page: this.page,
            per_page: this.perPage,
            status: this.status,
            search: this.search || '',
        });

        return fetch(`${this.config.ajaxUrl}?${params.toString()}`, { credentials: 'same-origin' })
            .then((response) => response.json())
            .then((response) => {
                if (!response.success) throw new Error(response.data?.message || this.label('unableLoad'));

                this.page = response.data.page || 1;
                this.total = response.data.total || 0;
                this.totalPages = response.data.totalPages || 1;
                this.renderEmployees(response.data.rowsHtml || '', this.total);
                this.updateCounts(response.data.counts || {});
                this.updatePagination();

                this.filters.forEach((button) => {
                    button.classList.toggle('active', button.dataset.filter === (response.data.status || this.status));
                });
            })
            .catch((err) => {
                if (this.emptyMessage) {
                    this.emptyMessage.textContent = err.message;
                    this.emptyMessage.classList.remove('hidden');
                }
                this.tableWrap?.classList.add('hidden');
                if (this.pagination) {
                    this.pagination.hidden = true;
                    this.pagination.classList.add('hidden');
                }
            })
            .finally(() => {
                this.setBusy(false);
                this.searchForm?.classList.remove('loading');
            });
    }

    filterButtonClick(e) {
        this.status = e.currentTarget.dataset.filter || 'all';
        e.currentTarget.classList.add('loading');

        this.loadEmployees(1)
            .finally(() => {
                e.currentTarget.classList.remove('loading');
            });
    }



    pageButtonClick(e) {
        const button = e.currentTarget;
        if (button.disabled) return;

        const action = button.dataset.pageAction;
        if (action === 'first') this.loadEmployees(1);
        if (action === 'prev') this.loadEmployees(Math.max(1, this.page - 1));
        if (action === 'page') this.loadEmployees(parseInt(button.dataset.page || '1', 10) || 1);
        if (action === 'next') this.loadEmployees(Math.min(this.totalPages, this.page + 1));
        if (action === 'last') this.loadEmployees(this.totalPages);
    }

    fileChange() {
        if (!this.fileInput || !this.fileName) return;

        this.fileName.textContent = this.fileInput.files?.[0]?.name || this.label('noFile');
    }

    validateLogoFile(file) {
        if (!file) return '';

        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            return this.label('logoTypes');
        }

        if (file.size > 2 * 1024 * 1024) {
            return this.label('logoSize');
        }

        return '';
    }

    setLogoPreview(src) {
        if (!this.logoPreviewImage || !this.logoPlaceholder) return;

        this.logoPreviewImage.src = src || '';
        this.logoPreviewImage.classList.toggle('hidden', !src);
        this.logoPlaceholder.classList.toggle('hidden', Boolean(src));
    }

    setLogoDirty(isDirty) {
        this.logoDirty = Boolean(isDirty);
        if (this.logoSave) this.logoSave.disabled = !this.logoDirty;
    }

    updateCompanyLogoDisplay(src) {
        if (!this.companyLogoDisplay) return;

        if (src) {
            this.companyLogoDisplay.innerHTML = `<img class="block max-h-full max-w-full object-contain" src="${this.escapeHtml(src)}" alt="">`;
            return;
        }

        const companyName = this.el.querySelector('[data-corporate-membership="company-logo-display"]')?.closest('.md\\:col-span-3')?.querySelector('.font-bold')?.textContent || '';
        this.companyLogoDisplay.innerHTML = `<span class="text-H6 font-bold">${this.escapeHtml(companyName.trim().charAt(0) || '')}</span>`;
    }

    logoFileChange() {
        if (!this.logoFile) return;

        const file = this.logoFile.files?.[0];
        const error = this.validateLogoFile(file);

        if (this.logoObjectUrl) {
            URL.revokeObjectURL(this.logoObjectUrl);
            this.logoObjectUrl = '';
        }

        if (error) {
            this.logoFile.value = '';
            if (this.logoFileName) this.logoFileName.textContent = this.label('noFile');
            this.setInlineMessage(this.logoSuccess, this.logoError, this.logoError, error);
            this.setLogoDirty(Boolean(this.logoSavedUrl && this.logoRemoveInput?.value === '1'));
            return;
        }

        if (!file) {
            if (this.logoFileName) this.logoFileName.textContent = this.label('noFile');
            this.setLogoDirty(Boolean(this.logoSavedUrl && this.logoRemoveInput?.value === '1'));
            return;
        }

        this.logoObjectUrl = URL.createObjectURL(file);
        if (this.logoFileName) this.logoFileName.textContent = file.name;
        if (this.logoRemoveInput) this.logoRemoveInput.value = '0';
        this.setLogoPreview(this.logoObjectUrl);
        if (this.logoRemove) this.logoRemove.classList.remove('hidden');
        this.setLogoDirty(true);
        this.setInlineMessage(this.logoSuccess, this.logoError, null, '');
    }

    logoRemoveClick(e) {
        e.preventDefault();

        const shouldRemoveSavedLogo = Boolean(this.logoSavedUrl);

        if (this.logoFile) this.logoFile.value = '';
        if (this.logoFileName) this.logoFileName.textContent = this.label('noFile');
        if (this.logoRemoveInput) this.logoRemoveInput.value = shouldRemoveSavedLogo ? '1' : '0';
        if (this.logoObjectUrl) {
            URL.revokeObjectURL(this.logoObjectUrl);
            this.logoObjectUrl = '';
        }
        this.setLogoPreview('');
        if (this.logoRemove) this.logoRemove.classList.add('hidden');
        this.setLogoDirty(shouldRemoveSavedLogo);

        if (shouldRemoveSavedLogo) {
            this.setInlineMessage(this.logoSuccess, this.logoError, this.logoNotice, this.label('logoRemoveNotice'));
            return;
        }

        this.setInlineMessage(this.logoSuccess, this.logoError, null, '');
    }

    logoFormSubmit(e) {
        e.preventDefault();
        if (!this.logoForm) return;
        if (!this.logoDirty) return;

        const file = this.logoFile?.files?.[0];
        const error = this.validateLogoFile(file);
        if (error) {
            this.setInlineMessage(this.logoSuccess, this.logoError, this.logoError, error);
            return;
        }

        const data = new FormData(this.logoForm);
        data.set('action', 'iw_corporate_owner_update_logo');
        data.set('nonce', this.config.logoNonce || '');

        this.logoForm.classList.add('loading');
        this.setInlineMessage(this.logoSuccess, this.logoError, null, '');

        fetch(this.config.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
            .then((response) => response.json())
            .then((response) => {
                if (!response.success) throw new Error(response.data?.message || this.label('unableLogo'));

                const logo = response.data?.logo || {};
                const logoUrl = logo.url || '';
                const logoName = logo.name || '';
                if (this.logoFile) this.logoFile.value = '';
                if (this.logoFileName) this.logoFileName.textContent = logoName || this.label('noFile');
                if (this.logoRemoveInput) this.logoRemoveInput.value = '0';
                this.setLogoPreview(logoUrl);
                this.updateCompanyLogoDisplay(logoUrl);
                if (this.logoRemove) this.logoRemove.classList.toggle('hidden', !logoUrl);
                this.logoSavedUrl = logoUrl;
                this.logoSavedName = logoName;
                this.setLogoDirty(false);
                this.setInlineMessage(this.logoSuccess, this.logoError, this.logoSuccess, response.data?.message || this.label('logoUpdated'));
            })
            .catch((err) => {
                this.setInlineMessage(this.logoSuccess, this.logoError, this.logoError, err.message);
            })
            .finally(() => {
                this.logoForm.classList.remove('loading');
            });
    }

    inviteFormSubmit(e) {
        e.preventDefault();

        if (!this.inviteForm) return;
        if (this.inviteForm.dataset.moduleForm) {
            this.call('onSubmit', e, 'Form', this.inviteForm.dataset.moduleForm);
            if (this.inviteForm.classList.contains('has-errors')) return;
        }

        this.inviteForm.classList.add('loading');

        const data = new FormData(this.inviteForm);
        data.set('action', 'iw_corporate_owner_invite_employee');
        data.set('nonce', this.config.inviteNonce || '');
        this.setMessage(null, '');

        fetch(this.config.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
            .then((response) => response.json())
            .then((response) => {
                if (!response.success) throw new Error(response.data?.message || this.label('unableInvite'));

                this.inviteForm.reset();
                if (this.fileName) this.fileName.textContent = this.label('noFile');
                this.setMessage(this.success, response.data?.message || this.label('employeeInvited'));
                return this.loadEmployees(1);
            })
            .catch((err) => {
                this.setMessage(this.error, err.message);
            })
            .finally(() => {
                this.inviteForm.classList.remove('loading');
            });
    }

    setSeatMessage(button, message, type = 'success') {
        const row = button.closest('tr');
        const messageEl = row?.querySelector('[data-corporate-membership="seat-message"]');

        if (!messageEl) return;

        messageEl.textContent = message || '';
        messageEl.classList.toggle('error', type === 'error');
        messageEl.classList.toggle('hidden', !message);
    }

    wait(ms) {
        return new Promise((resolve) => window.setTimeout(resolve, ms));
    }

    seatActionClick(e) {
        const button = e.currentTarget;
        const data = new FormData();

        data.set('action', 'iw_corporate_owner_employee_action');
        data.set('nonce', this.config.employeeActionNonce || '');
        data.set('seat_id', button.dataset.seatId || '');
        data.set('employee_action', button.dataset.seatAction || '');

        button.disabled = true;
        button.classList.add( 'loading' );
        this.setSeatMessage(button, '');

        fetch(this.config.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
            .then((response) => response.json())
            .then((response) => {
                if (!response.success) throw new Error(response.data?.message || this.label('unableUpdate'));

                this.setSeatMessage(button, response.data?.message || this.label('employeeUpdated'));
                return this.wait(1200).then(() => this.loadEmployees(this.page));
            })
            .catch((err) => {
                this.setSeatMessage(button, err.message, 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.classList.remove( 'loading' );
            });
    }
}
