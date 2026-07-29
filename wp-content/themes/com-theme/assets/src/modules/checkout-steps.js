import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = {
            click: {
                next: 'next',
                indicator: 'onIndicatorClick',
            },
            change: {
                'receipt-type': 'toggleInvoiceFields',
                country: 'toggleInvoiceFields',
            },
        };
        this.currentStep = 1;
        this.steps = [...this.$('step')];
        this.indicators = [...this.$('indicator')];
        this.stepsContainer = this.steps[0]?.parentElement ?? null;
        this.stepsAnchor = this.$('steps-anchor')?.[0] ?? null;
        this.stepper = this.el.querySelector('.com-checkout-stepper');
        this.invoiceFields = this.$('invoice-fields')?.[0] ?? null;
        this.onUpdateCheckoutBind = this.onUpdateCheckout.bind(this);
        this.onUpdatedCheckoutBind = this.onUpdatedCheckout.bind(this);
        this.onCheckoutErrorBind = this.onCheckoutError.bind(this);
        this.onSubmitBind = this.onSubmit.bind(this);
        this.isUpdating = false;
        this.isSubmitting = false;
        this.toggleInvoiceFields();
    }

    init() {
        const body = window.jQuery?.(document.body);

        body?.on('update_checkout.comCheckoutSteps', this.onUpdateCheckoutBind);
        body?.on('updated_checkout.comCheckoutSteps', this.onUpdatedCheckoutBind);
        body?.on('checkout_error.comCheckoutSteps', this.onCheckoutErrorBind);
        this.el.addEventListener('submit', this.onSubmitBind, true);

        this.isUpdating = Boolean(this.el.querySelector('.blockUI.blockOverlay'));
        this.renderBusyState();
    }

    onUpdateCheckout() {
        this.isUpdating = true;
        this.renderBusyState();
    }

    onUpdatedCheckout() {
        const review = this.el.querySelector('.woocommerce-checkout-review-order-table');
        if (review) {
            this.call('update', review, 'app');
        }

        this.isUpdating = false;
        this.renderBusyState();
    }

    onCheckoutError() {
        this.isUpdating = false;
        this.isSubmitting = false;
        this.call('start', false, 'Scroll');
        this.unlockStepHeight();
        this.goTo(2);
        this.renderBusyState();
    }

    onSubmit() {
        if (this.currentStep !== 2) return;

        this.isSubmitting = true;
        this.rememberScrollPosition();
        this.call('stop', false, 'Scroll');
        this.lockStepHeight();
        this.goTo(3, { shouldScroll: false });
        this.renderBusyState();
    }

    renderBusyState() {
        const isBusy = this.isUpdating || this.isSubmitting;

        this.el.classList.toggle('is-updating', this.isUpdating);
        this.el.classList.toggle('is-processing', this.isSubmitting);
        this.el.toggleAttribute('inert', isBusy);
        this.el.setAttribute('aria-busy', isBusy ? 'true' : 'false');

        const orderButton = this.el.querySelector('#place_order');
        if (!orderButton) return;

        orderButton.disabled = isBusy;
        orderButton.classList.toggle('is-loading', isBusy);
        orderButton.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    }

    next() {
        if (!this.validateStep(1)) return;
        this.goTo(2);
    }

    onIndicatorClick(event) {
        const stepNumber = Number(event.currentTarget.dataset.step);

        if (stepNumber === 1) {
            this.goTo(1);
            return;
        }

        if (stepNumber === 2 && this.currentStep !== 2) {
            this.next();
        }
    }

    goTo(stepNumber, { shouldScroll = true } = {}) {
        this.currentStep = stepNumber;
        this.steps.forEach(step => {
            step.classList.toggle('hidden', Number(step.dataset.step) !== stepNumber);
        });
        this.indicators.forEach(indicator => {
            const number = Number(indicator.dataset.step);
            indicator.classList.toggle('active', number === stepNumber);
            indicator.classList.toggle('complete', number < stepNumber);
            indicator.setAttribute('aria-current', number === stepNumber ? 'step' : 'false');
        });

        if (!shouldScroll) return;

        requestAnimationFrame(() => {
            const header = document.querySelector('header');
            const target = this.steps.find(item => Number(item.dataset.step) === stepNumber)
                || this.stepsAnchor
                || this.stepper
                || this.el;
            const stepperIsSticky = this.stepper
                && window.getComputedStyle(this.stepper).position === 'sticky';
            const headerHeight = header?.offsetHeight || 0;
            const stepperHeight = stepperIsSticky ? this.stepper.offsetHeight : 0;
            const offset = -(headerHeight + stepperHeight + 24);

            this.call('update', false, 'Scroll');
            this.call('scrollTo', { target, options: { offset, immediate: true } }, 'Scroll');
        });
    }

    rememberScrollPosition() {
        const scrollContainer = document.querySelector('[data-module-scroll="main"]');
        if (!scrollContainer) return;

        try {
            window.sessionStorage.setItem('comCheckoutScrollTop', String(scrollContainer.scrollTop));
        } catch (error) {
            // Checkout remains usable when storage is unavailable.
        }
    }

    lockStepHeight() {
        if (!this.stepsContainer) return;

        const currentStep = this.steps.find(item => Number(item.dataset.step) === this.currentStep);
        if (!currentStep) return;

        this.stepsContainer.style.minHeight = `${Math.ceil(currentStep.getBoundingClientRect().height)}px`;
    }

    unlockStepHeight() {
        if (this.stepsContainer) {
            this.stepsContainer.style.removeProperty('min-height');
        }
    }

    validateStep(stepNumber) {
        const step = this.steps.find(item => Number(item.dataset.step) === stepNumber);
        if (!step) return true;

        const fields = [...step.querySelectorAll('input, select, textarea')].filter(field => {
            return !field.disabled && field.type !== 'hidden' && field.offsetParent !== null;
        });
        const invalid = fields.find(field => !field.checkValidity());

        fields.forEach(field => {
            field.closest('.form-row')?.classList.toggle('woocommerce-invalid', !field.checkValidity());
        });

        if (!invalid) return true;
        invalid.reportValidity();
        invalid.focus({ preventScroll: true });
        invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }

    toggleInvoiceFields() {
        if (!this.invoiceFields) return;
        const selected = this.el.querySelector('input[name="billing_receipt_type"]:checked');
        const isInvoice = selected?.value === 'invoice';
        const country = this.el.querySelector('[name="billing_country"]')?.value || 'GR';
        const isGreekInvoice = isInvoice && country === 'GR';
        const requiredFields = isGreekInvoice
            ? ['billing_tax_number']
            : ['billing_tax_number', 'billing_company_name'];

        this.el.classList.toggle('is-invoice', isInvoice);
        this.el.classList.toggle('is-greek-invoice', isGreekInvoice);
        this.invoiceFields.classList.toggle('hidden', !isInvoice);
        this.invoiceFields.querySelectorAll('input, select, textarea').forEach(field => {
            field.required = isInvoice && requiredFields.includes(field.name);
        });
    }

    destroy() {
        const body = window.jQuery?.(document.body);

        body?.off('update_checkout.comCheckoutSteps', this.onUpdateCheckoutBind);
        body?.off('updated_checkout.comCheckoutSteps', this.onUpdatedCheckoutBind);
        body?.off('checkout_error.comCheckoutSteps', this.onCheckoutErrorBind);
        this.el.removeEventListener('submit', this.onSubmitBind, true);
        this.isUpdating = false;
        this.isSubmitting = false;
        this.unlockStepHeight();
        this.renderBusyState();
    }
}
