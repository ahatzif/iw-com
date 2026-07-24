import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = {
            click: {
                next: 'next',
                back: 'back',
            },
            change: {
                'receipt-type': 'toggleInvoiceFields',
            },
        };
        this.currentStep = 1;
        this.steps = [...this.$('step')];
        this.indicators = [...this.$('indicator')];
        this.invoiceFields = this.$('invoice-fields')?.[0] ?? null;
        this.toggleInvoiceFields();
    }

    next() {
        if (!this.validateStep(1)) return;
        this.goTo(2);
    }

    back() {
        this.goTo(1);
    }

    goTo(stepNumber) {
        this.currentStep = stepNumber;
        this.steps.forEach(step => {
            step.classList.toggle('hidden', Number(step.dataset.step) !== stepNumber);
        });
        this.indicators.forEach(indicator => {
            const number = Number(indicator.dataset.step);
            indicator.classList.toggle('active', number === stepNumber);
            indicator.classList.toggle('complete', number < stepNumber);
        });
        this.el.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
        const requiredFields = country === 'GR'
            ? ['billing_tax_number', 'billing_company_name', 'billing_company_profession', 'billing_tax_office']
            : ['billing_tax_number', 'billing_company_name'];

        this.invoiceFields.classList.toggle('hidden', !isInvoice);
        this.invoiceFields.querySelectorAll('input, select, textarea').forEach(field => {
            field.required = isInvoice && requiredFields.includes(field.name);
        });
    }
}
