import { module } from 'modujs';

const pad = value => String(value).padStart(2, '0');

export default class extends module {
    constructor(m) {
        super(m);

        this.abortController = new AbortController();
        this.config = this.readConfig();
        this.form = this.el.querySelector('[data-ticket-form]');
        this.schedule = this.config?.schedule || {};
        this.allowDates = Array.isArray(this.schedule.allowDates) ? this.schedule.allowDates : [];
        this.soldOutDates = new Set(Array.isArray(this.schedule.soldOutDates) ? this.schedule.soldOutDates : []);
        this.limitedDates = new Set(Array.isArray(this.schedule.limitedDates) ? this.schedule.limitedDates : []);
        this.timesByDate = this.schedule.timesByDate && typeof this.schedule.timesByDate === 'object'
            ? this.schedule.timesByDate
            : {};
        this.categories = new Map(
            (Array.isArray(this.config?.categories) ? this.config.categories : [])
                .map(category => [category.key, category]),
        );
        this.isSubmitting = false;

        const selectedDate = this.allowDates.includes(this.config?.selectedDate)
            ? this.config.selectedDate
            : '';
        const selectedTime = this.getSlotsForDate(selectedDate)
            .some(slot => slot.time === this.config?.selectedTime && slot.status !== 'sold-out')
            ? this.config.selectedTime
            : '';

        this.state = {
            step: selectedTime ? 3 : (selectedDate ? 2 : 1),
            date: selectedDate,
            dateLabel: selectedDate ? this.formatDateLabel(selectedDate) : '',
            time: selectedTime,
            quantities: Object.fromEntries(
                [...this.categories.keys()].map(key => [key, 0]),
            ),
        };

        const initialMonthDate = this.parseDate(
            selectedDate || this.allowDates[0] || this.toYmd(new Date()),
        );
        this.currentMonth = new Date(initialMonthDate.getFullYear(), initialMonthDate.getMonth(), 1);

        this.applyInitialMinimums();
        this.syncAllVisitorRows();

        this.el.addEventListener('click', this.onClick.bind(this), {
            signal: this.abortController.signal,
        });
        this.el.addEventListener('input', this.onVisitorFieldChange.bind(this), {
            signal: this.abortController.signal,
        });
        this.el.addEventListener('change', this.onVisitorFieldChange.bind(this), {
            signal: this.abortController.signal,
        });
        this.form?.addEventListener('submit', this.onSubmit.bind(this), {
            signal: this.abortController.signal,
        });

        this.render();
    }

    destroy() {
        this.abortController.abort();
    }

    readConfig() {
        const configElement = this.el.querySelector('[data-ticket-config]');

        if (!configElement) return null;

        try {
            return JSON.parse(configElement.textContent || '{}');
        } catch (error) {
            return null;
        }
    }

    applyInitialMinimums() {
        let remaining = Number(this.config?.maxTickets) || 10;

        this.categories.forEach(category => {
            const minimum = Math.max(0, Number(category.minTickets) || 0);
            const maximum = Math.max(minimum, Number(category.maxTickets) || remaining);
            const quantity = Math.min(minimum, maximum, remaining);

            this.state.quantities[category.key] = quantity;
            remaining = Math.max(0, remaining - quantity);
        });
    }

    onClick(event) {
        const previousMonthButton = event.target.closest('[data-ticket-month-previous]');
        if (previousMonthButton && !previousMonthButton.disabled) {
            this.changeMonth(-1);
            return;
        }

        const nextMonthButton = event.target.closest('[data-ticket-month-next]');
        if (nextMonthButton && !nextMonthButton.disabled) {
            this.changeMonth(1);
            return;
        }

        const dateButton = event.target.closest('[data-ticket-date]');
        if (dateButton && !dateButton.disabled) {
            this.selectDate(dateButton.dataset.value);
            return;
        }

        const timeButton = event.target.closest('[data-ticket-time]');
        if (timeButton && !timeButton.disabled) {
            this.selectTime(timeButton.dataset.value);
            return;
        }

        const quantityButton = event.target.closest('[data-ticket-quantity]');
        if (quantityButton && !quantityButton.disabled) {
            this.updateQuantity(
                quantityButton.dataset.ticketQuantity,
                Number(quantityButton.dataset.delta),
            );
            return;
        }

        const stepButton = event.target.closest('[data-ticket-step]');
        if (stepButton) {
            const requestedStep = Number(stepButton.dataset.ticketStep);

            if (this.canOpenStep(requestedStep)) {
                this.setStep(requestedStep);
            }
            return;
        }

        if (event.target.closest('[data-ticket-next]')) {
            event.preventDefault();
            this.goNext();
        }
    }

    onSubmit(event) {
        event.preventDefault();
        this.goNext();
    }

    onVisitorFieldChange(event) {
        const field = event.target.closest('[data-ticket-visitor-field]');

        if (!field) return;

        const visitor = field.closest('[data-ticket-visitor]');
        if (visitor && field.dataset.ticketVisitorField === 'category-id') {
            this.updateVisitorPrice(visitor);
        }

        this.clearError();
        this.renderTotals();
        this.renderSubmitButton();
    }

    changeMonth(offset) {
        this.currentMonth = new Date(
            this.currentMonth.getFullYear(),
            this.currentMonth.getMonth() + offset,
            1,
        );
        this.renderCalendar();
    }

    selectDate(date) {
        if (!this.allowDates.includes(date) || this.soldOutDates.has(date)) return;

        this.state.date = date;
        this.state.dateLabel = this.formatDateLabel(date);
        this.state.time = '';
        this.clearError();
        this.setStep(2);
    }

    selectTime(time) {
        const slot = this.getSlotsForDate(this.state.date)
            .find(candidate => candidate.time === time);

        if (!slot || slot.status === 'sold-out' || Number(slot.availability) === 0) return;

        this.state.time = time;
        this.enforceEffectiveMaximum();
        this.clearError();
        this.setStep(3);
    }

    updateQuantity(categoryKey, delta) {
        const category = this.categories.get(categoryKey);
        if (!category) return;

        const current = Number(this.state.quantities[categoryKey]) || 0;
        const categoryMinimum = Math.max(0, Number(category.minTickets) || 0);
        const categoryMaximum = Math.max(categoryMinimum, Number(category.maxTickets) || 0);
        const effectiveMaximum = this.getEffectiveMaximum();
        const totalWithoutCategory = this.getTotalTickets() - current;
        const allowedForCategory = Math.max(
            categoryMinimum,
            Math.min(categoryMaximum, effectiveMaximum - totalWithoutCategory),
        );
        const next = Math.max(
            categoryMinimum,
            Math.min(allowedForCategory, current + delta),
        );

        if (next === current) return;

        this.state.quantities[categoryKey] = next;
        this.syncVisitorRows(categoryKey);
        this.clearError();
        this.render();
    }

    enforceEffectiveMaximum() {
        let overflow = this.getTotalTickets() - this.getEffectiveMaximum();

        if (overflow <= 0) return;

        [...this.categories.keys()].reverse().forEach(categoryKey => {
            if (overflow <= 0) return;

            const category = this.categories.get(categoryKey);
            const minimum = Math.max(0, Number(category?.minTickets) || 0);
            const current = Number(this.state.quantities[categoryKey]) || 0;
            const removable = Math.max(0, current - minimum);
            const remove = Math.min(removable, overflow);

            this.state.quantities[categoryKey] = current - remove;
            overflow -= remove;
            this.syncVisitorRows(categoryKey);
        });
    }

    syncAllVisitorRows() {
        this.categories.forEach(category => this.syncVisitorRows(category.key));
    }

    syncVisitorRows(categoryKey) {
        const container = this.el.querySelector(`[data-ticket-visitors="${categoryKey}"]`);
        const template = this.el.querySelector(`[data-ticket-visitor-template="${categoryKey}"]`);
        const quantity = Number(this.state.quantities[categoryKey]) || 0;

        if (!container || !template) return;

        while (container.children.length > quantity) {
            container.lastElementChild?.remove();
        }

        while (container.children.length < quantity) {
            const fragment = template.content.cloneNode(true);
            const visitor = fragment.querySelector('[data-ticket-visitor]');
            const categoryField = visitor?.querySelector('[data-ticket-visitor-field="category-id"]');
            const validOptions = categoryField?.tagName === 'SELECT'
                ? [...categoryField.options].filter(option => option.value !== '')
                : [];

            if (categoryField?.tagName === 'SELECT' && validOptions.length === 1) {
                categoryField.value = validOptions[0].value;
            }

            if (visitor) {
                this.updateVisitorPrice(visitor);
            }

            container.appendChild(fragment);
            this.call('update', container, 'app');
        }

        [...container.querySelectorAll('[data-ticket-visitor]')].forEach((visitor, index) => {
            const indexElement = visitor.querySelector('[data-ticket-visitor-index]');

            visitor.dataset.ticketVisitorIndex = String(index);
            if (indexElement) indexElement.textContent = String(index + 1);
        });
    }

    updateVisitorPrice(visitor) {
        const categoryField = visitor.querySelector('[data-ticket-visitor-field="category-id"]');
        const priceElement = visitor.querySelector('[data-ticket-visitor-price]');
        const category = this.categories.get(visitor.dataset.categoryKey);
        const subcategory = category?.subcategories?.find(
            item => item.value === categoryField?.value,
        );
        const price = subcategory ? Number(subcategory.price) || 0 : 0;

        visitor.dataset.ticketPrice = String(price);
        if (priceElement) {
            priceElement.textContent = subcategory ? this.formatPrice(price) : '';
        }
    }

    getSlotsForDate(date) {
        const slots = this.timesByDate?.[date];

        return Array.isArray(slots) ? slots : [];
    }

    getSelectedSlot() {
        return this.getSlotsForDate(this.state.date)
            .find(slot => slot.time === this.state.time) || null;
    }

    getEffectiveMaximum() {
        const configuredMaximum = Math.max(1, Number(this.config?.maxTickets) || 10);
        const selectedSlot = this.getSelectedSlot();

        if (
            !selectedSlot
            || selectedSlot.availability === undefined
            || selectedSlot.availability === null
        ) {
            return configuredMaximum;
        }

        return Math.max(0, Math.min(configuredMaximum, Number(selectedSlot.availability) || 0));
    }

    getTotalTickets() {
        return Object.values(this.state.quantities)
            .reduce((total, quantity) => total + (Number(quantity) || 0), 0);
    }

    getCategoryCost(categoryKey) {
        const container = this.el.querySelector(`[data-ticket-visitors="${categoryKey}"]`);

        if (!container) return 0;

        return [...container.querySelectorAll('[data-ticket-visitor]')]
            .reduce((total, visitor) => total + (Number(visitor.dataset.ticketPrice) || 0), 0);
    }

    getTotalPrice() {
        return [...this.categories.keys()]
            .reduce((total, categoryKey) => total + this.getCategoryCost(categoryKey), 0);
    }

    getVisitors() {
        return [...this.el.querySelectorAll('[data-ticket-visitor]')].map(visitor => {
            const value = fieldName => (
                visitor.querySelector(`[data-ticket-visitor-field="${fieldName}"]`)?.value || ''
            ).trim();

            return {
                first: value('first'),
                last: value('last'),
                'category-id': value('category-id'),
            };
        });
    }

    canOpenStep(step) {
        if (step === 1) return true;
        if (step === 2) return Boolean(this.state.date);

        return Boolean(this.state.date && this.state.time);
    }

    canContinue() {
        if (this.state.step === 1) return Boolean(this.state.date);
        if (this.state.step === 2) return Boolean(this.state.time);

        return this.getTotalTickets() >= Math.max(1, Number(this.config?.minTickets) || 1);
    }

    goNext() {
        if (this.isSubmitting) return;

        if (!this.canContinue()) {
            if (this.state.step === 3) {
                this.showError(
                    this.interpolate(
                        this.config?.strings?.minimumTickets,
                        Math.max(1, Number(this.config?.minTickets) || 1),
                    ),
                );
            }
            return;
        }

        if (this.state.step < 3) {
            this.setStep(this.state.step + 1);
            return;
        }

        this.submitTickets();
    }

    setStep(step) {
        if (!this.canOpenStep(step)) return;

        this.state.step = step;
        this.render();

        if (step === 2) {
            requestAnimationFrame(() => {
                this.call('update', false, 'Scroll');
                this.call(
                    'scrollTo',
                    { target: this.form || this.el, options: { offset: 0 } },
                    'Scroll',
                );
            });
            return;
        }

        const steps = this.el.querySelector('[data-ticket-steps]');
        const stepsAnchor = this.el.querySelector('[data-ticket-steps-anchor]');
        if (!steps) return;

        requestAnimationFrame(() => {
            const header = document.querySelector('header');
            const offset = -(header?.offsetHeight || 0);

            this.call('update', false, 'Scroll');
            this.call(
                'scrollTo',
                { target: stepsAnchor || steps, options: { offset } },
                'Scroll',
            );
        });
    }

    async submitTickets() {
        const minimum = Math.max(1, Number(this.config?.minTickets) || 1);
        const maximum = this.getEffectiveMaximum();
        const total = this.getTotalTickets();

        if (total < minimum) {
            this.showError(this.interpolate(this.config?.strings?.minimumTickets, minimum));
            return;
        }

        if (total > maximum) {
            this.showError(this.interpolate(this.config?.strings?.maximumTickets, maximum));
            return;
        }

        const visitors = this.getVisitors();

        if (visitors.some(visitor => !visitor['category-id'])) {
            this.showError(this.config?.strings?.selectTicketType);
            return;
        }

        if (!this.form?.checkValidity()) {
            this.form?.reportValidity();
            this.showError(this.config?.strings?.selectTicketType);
            return;
        }

        const formData = new FormData();

        formData.append('action', this.config.action);
        formData.append('nonce', this.config.nonce);
        formData.append('tickets-for-id', String(this.config.ticketId));
        formData.append('day', this.state.date);
        formData.append('time', this.state.time);
        formData.append('visitors', JSON.stringify(visitors));

        this.isSubmitting = true;
        this.clearError();
        this.renderSubmitButton();

        try {
            const response = await fetch(window.THEME_OBJ?.ajaxURL || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData,
            });
            const payload = await response.json();

            if (!response.ok || !payload?.success) {
                throw new Error(payload?.data?.message || this.config?.strings?.genericError);
            }

            const redirectUrl = payload.data?.redirect_url
                || payload.data?.cart_url
                || this.config?.cartUrl;

            if (redirectUrl) {
                window.location.assign(redirectUrl);
                return;
            }

            throw new Error(this.config?.strings?.genericError);
        } catch (error) {
            this.isSubmitting = false;
            this.showError(error?.message || this.config?.strings?.genericError);
            this.renderSubmitButton();
        }
    }

    render() {
        this.renderPanels();
        this.renderCalendar();
        this.renderTimeSlots();
        this.renderQuantities();
        this.renderTotals();
        this.renderPreview();
        this.renderSubmitButton();
    }

    renderPanels() {
        this.el.querySelectorAll('[data-ticket-panel]').forEach(panel => {
            panel.classList.toggle(
                'hidden',
                Number(panel.dataset.ticketPanel) !== this.state.step,
            );
        });

        this.el.querySelectorAll('[data-ticket-step]').forEach(step => {
            const stepNumber = Number(step.dataset.ticketStep);

            step.classList.toggle('is-current', stepNumber === this.state.step);
            step.classList.toggle('is-complete', stepNumber < this.state.step);
            step.disabled = !this.canOpenStep(stepNumber);
            step.setAttribute(
                'aria-current',
                stepNumber === this.state.step ? 'step' : 'false',
            );
        });

        const summary = this.el.querySelector('[data-ticket-summary]');
        summary?.classList.toggle('lg:sticky', this.state.step !== 2);
        summary?.classList.toggle('lg:top-[16rem]', this.state.step !== 2);
    }

    renderCalendar() {
        const calendar = this.el.querySelector('[data-ticket-calendar]');
        const monthLabel = this.el.querySelector('[data-ticket-month-label]');

        if (!calendar || !monthLabel) return;

        const monthName = this.config?.months?.[this.currentMonth.getMonth()] || '';
        monthLabel.textContent = `${monthName} ${this.currentMonth.getFullYear()}`.trim();

        calendar.querySelectorAll('[data-ticket-calendar-cell]').forEach(cell => cell.remove());

        const year = this.currentMonth.getFullYear();
        const month = this.currentMonth.getMonth();
        const firstWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        for (let index = 0; index < firstWeekday; index += 1) {
            const spacer = document.createElement('span');
            spacer.dataset.ticketCalendarCell = '';
            spacer.className = 'aspect-square';
            calendar.appendChild(spacer);
        }

        for (let day = 1; day <= daysInMonth; day += 1) {
            const value = `${year}-${pad(month + 1)}-${pad(day)}`;
            const isAllowed = this.allowDates.includes(value);
            const isSoldOut = this.soldOutDates.has(value);
            const isLimited = this.limitedDates.has(value);
            const isDisabled = !isAllowed || isSoldOut;
            const button = document.createElement('button');

            button.type = 'button';
            button.dataset.ticketCalendarCell = '';
            button.dataset.ticketDate = '';
            button.dataset.value = value;
            button.disabled = isDisabled;
            button.textContent = String(day);
            button.className = [
                'flex aspect-square items-center justify-center rounded-[.3rem] border text-[1.4rem] leading-none transition-colors sm:text-[2rem]',
                '[&.active]:border-white [&.active]:bg-white [&.active]:text-blue',
                isDisabled
                    ? 'cursor-not-allowed border-transparent text-blue-soft'
                    : (isLimited
                        ? 'border-[#ff8686] text-[#ff8686] hover:bg-white hover:text-blue'
                        : 'border-white text-white hover:bg-white hover:text-blue'),
            ].join(' ');
            button.classList.toggle('active', value === this.state.date);
            button.setAttribute('aria-pressed', value === this.state.date ? 'true' : 'false');
            button.setAttribute(
                'aria-label',
                `${this.formatDateSpoken(value)}, ${
                    isDisabled
                        ? this.config?.strings?.unavailableDate
                        : (isLimited
                            ? this.config?.strings?.limitedDate
                            : this.config?.strings?.availableDate)
                }`,
            );
            calendar.appendChild(button);
        }

        if (!this.allowDates.length) {
            const emptyMessage = document.createElement('p');

            emptyMessage.dataset.ticketCalendarCell = '';
            emptyMessage.className = 'col-span-7 py-40 text-center text-[1.4rem] text-blue-soft';
            emptyMessage.textContent = this.config?.strings?.noDates || '';
            calendar.appendChild(emptyMessage);
        }

        const monthKeys = this.allowDates.map(date => date.slice(0, 7)).sort();
        const currentMonthKey = `${year}-${pad(month + 1)}`;
        const previousButton = this.el.querySelector('[data-ticket-month-previous]');
        const nextButton = this.el.querySelector('[data-ticket-month-next]');

        if (previousButton) {
            previousButton.disabled = !monthKeys.length || currentMonthKey <= monthKeys[0];
        }
        if (nextButton) {
            nextButton.disabled = !monthKeys.length || currentMonthKey >= monthKeys[monthKeys.length - 1];
        }
    }

    renderTimeSlots() {
        const container = this.el.querySelector('[data-ticket-time-slots]');
        if (!container) return;

        container.replaceChildren();
        const slots = this.getSlotsForDate(this.state.date);

        slots.forEach(slot => {
            const availability = slot.availability === undefined || slot.availability === null
                ? null
                : Number(slot.availability);
            const isSoldOut = slot.status === 'sold-out' || availability === 0;
            const isLimited = slot.status === 'limited';
            const button = document.createElement('button');
            const label = document.createElement('span');
            const availabilityLabel = document.createElement('span');

            button.type = 'button';
            button.dataset.ticketTime = '';
            button.dataset.value = slot.time || '';
            button.disabled = isSoldOut;
            button.className = [
                'flex min-h-[8rem] flex-col items-center justify-center gap-5 rounded-[.5rem] border px-20 text-[1.8rem] transition-colors',
                '[&.active]:border-white [&.active]:bg-white [&.active]:text-blue [&.active_.slot-availability]:text-blue-soft',
                isSoldOut
                    ? 'cursor-not-allowed border-blue-soft text-blue-soft opacity-50'
                    : (isLimited
                        ? 'border-[#ff8686] text-[#ff8686] hover:bg-white hover:text-blue'
                        : 'border-white text-white hover:bg-white hover:text-blue'),
            ].join(' ');
            button.classList.toggle('active', slot.time === this.state.time);
            button.setAttribute('aria-pressed', slot.time === this.state.time ? 'true' : 'false');

            label.textContent = slot.end ? `${slot.time} – ${slot.end}` : slot.time;
            availabilityLabel.className = 'slot-availability text-[1.1rem] leading-none';

            if (isSoldOut) {
                availabilityLabel.textContent = this.config?.strings?.soldOut || '';
            } else if (availability === 1) {
                availabilityLabel.textContent = this.config?.strings?.remainingOne || '';
            } else if (availability !== null) {
                availabilityLabel.textContent = this.interpolate(
                    this.config?.strings?.remainingMany,
                    availability,
                );
            }

            button.append(label, availabilityLabel);
            container.appendChild(button);
        });

        if (!slots.length) {
            const message = document.createElement('p');

            message.className = 'sm:col-span-2 py-40 text-center text-[1.4rem] text-blue-soft';
            message.textContent = this.config?.strings?.noTimes || '';
            container.appendChild(message);
        }
    }

    renderQuantities() {
        const effectiveMaximum = this.getEffectiveMaximum();
        const total = this.getTotalTickets();

        this.categories.forEach(category => {
            const categoryKey = category.key;
            const quantity = Number(this.state.quantities[categoryKey]) || 0;
            const minimum = Math.max(0, Number(category.minTickets) || 0);
            const maximum = Math.max(minimum, Number(category.maxTickets) || 0);

            this.el.querySelectorAll(`[data-ticket-quantity-value="${categoryKey}"]`)
                .forEach(element => {
                    element.textContent = String(quantity);
                });
            this.el.querySelectorAll(`[data-ticket-quantity="${categoryKey}"][data-delta="-1"]`)
                .forEach(button => {
                    button.disabled = quantity <= minimum || this.isSubmitting;
                });
            this.el.querySelectorAll(`[data-ticket-quantity="${categoryKey}"][data-delta="1"]`)
                .forEach(button => {
                    button.disabled = quantity >= maximum
                        || total >= effectiveMaximum
                        || this.isSubmitting;
                });
            this.el.querySelectorAll(`[data-ticket-category-cost="${categoryKey}"]`)
                .forEach(element => {
                    element.textContent = this.formatPrice(this.getCategoryCost(categoryKey));
                });
        });
    }

    renderTotals() {
        this.renderCategorySummary();

        const totalTickets = this.getTotalTickets();
        const totalCount = this.el.querySelector('[data-ticket-total-count]');
        const totalPrice = this.el.querySelector('[data-ticket-total-price]');
        const ticketLabel = totalTickets === 1
            ? this.config?.strings?.ticketSingular
            : this.config?.strings?.ticketPlural;

        if (totalCount) totalCount.textContent = `${totalTickets} ${ticketLabel || ''}`;
        if (totalPrice) totalPrice.textContent = this.formatPrice(this.getTotalPrice());

        this.categories.forEach(category => {
            this.el.querySelectorAll(`[data-ticket-category-cost="${category.key}"]`)
                .forEach(element => {
                    element.textContent = this.formatPrice(this.getCategoryCost(category.key));
                });
        });
    }

    renderCategorySummary() {
        const summary = this.el.querySelector('[data-ticket-category-summary]');
        if (!summary) return;

        summary.replaceChildren();

        this.categories.forEach(category => {
            const quantity = Number(this.state.quantities[category.key]) || 0;
            if (!quantity) return;

            const row = document.createElement('div');
            const label = document.createElement('span');
            const price = document.createElement('strong');

            row.className = 'flex items-start justify-between gap-20';
            label.textContent = `${quantity} × ${category.label}`;
            price.className = 'whitespace-nowrap';
            price.textContent = this.formatPrice(this.getCategoryCost(category.key));
            row.append(label, price);
            summary.appendChild(row);
        });

        summary.classList.toggle('hidden', !summary.childElementCount);
    }

    renderPreview() {
        const datePreview = this.el.querySelector('[data-ticket-date-preview]');
        const timePreview = this.el.querySelector('[data-ticket-time-preview]');
        const timeRow = this.el.querySelector('[data-ticket-time-row]');
        const selectedSlot = this.getSelectedSlot();

        if (datePreview) datePreview.textContent = this.state.dateLabel || '—';
        if (timePreview) {
            timePreview.textContent = selectedSlot?.end
                ? `${selectedSlot.time} – ${selectedSlot.end}`
                : (selectedSlot?.time || '—');
        }
        if (timeRow) timeRow.classList.toggle('hidden', !this.state.time);
    }

    renderSubmitButton() {
        const button = this.el.querySelector('[data-ticket-next]');
        if (!button) return;

        const isPurchaseStep = this.state.step === 3;
        const label = button.querySelector('[data-button-label]');

        button.disabled = this.isSubmitting || !this.canContinue();
        button.classList.toggle('is-purchase', isPurchaseStep && this.canContinue());
        button.classList.toggle('loading', this.isSubmitting);
        if (label) {
            label.textContent = isPurchaseStep
                ? (this.config?.strings?.addToCart || '')
                : (this.config?.strings?.next || '');
        }
        button.setAttribute('aria-busy', this.isSubmitting ? 'true' : 'false');
    }

    showError(message) {
        const errorElement = this.el.querySelector('[data-ticket-error]');
        if (!errorElement) return;

        errorElement.textContent = message || this.config?.strings?.genericError || '';
        errorElement.classList.remove('hidden');
    }

    clearError() {
        const errorElement = this.el.querySelector('[data-ticket-error]');
        if (!errorElement) return;

        errorElement.textContent = '';
        errorElement.classList.add('hidden');
    }

    formatPrice(value) {
        return new Intl.NumberFormat(this.config?.locale || 'el-GR', {
            style: 'currency',
            currency: this.config?.currency || 'EUR',
            minimumFractionDigits: 2,
        }).format(Number(value) || 0);
    }

    formatDateLabel(value) {
        return new Intl.DateTimeFormat(this.config?.locale || 'el-GR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).format(this.parseDate(value));
    }

    formatDateSpoken(value) {
        return new Intl.DateTimeFormat(this.config?.locale || 'el-GR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(this.parseDate(value));
    }

    parseDate(value) {
        const [year, month, day] = String(value).split('-').map(Number);

        return new Date(year, Math.max(0, month - 1), day || 1, 12);
    }

    toYmd(date) {
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    }

    interpolate(template, value) {
        return String(template || '').replace('%s', String(value));
    }
}
