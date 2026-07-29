import { module } from 'modujs';

const EU_VAT_COUNTRIES = [
    'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU',
    'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'XI',
];

export default class extends module {
    constructor(m) {
        super(m);
        this.form = this.el.closest('form');
        this.input = this.el.querySelector('input[name="billing_tax_number"]');
        this.searchButton = this.$('search')[0];
        this.clearButton = this.$('clear')[0];
        this.nonce = this.$('nonce')[0];
        this.description = this.$('description')[0];
        this.error = this.$('error')[0];
        this.preview = this.$('preview')[0];
        this.companyName = this.$('company-name')[0];
        this.companyInfo = this.$('company-info')[0];
        this.isApplying = false;
        this.currentError = '';
        this.lastCountry = '';
        this.verifiedTaxNumber = this.el.classList.contains('is-verified')
            ? this.normalizeTaxNumber(this.input?.value)
            : '';

        this.fieldMap = {
            company_name: ['company_name'],
            company_profession: ['company_profession'],
            tax_number: ['tax_number'],
            tax_office: ['tax_office'],
            address_1: ['address_1'],
            address_2: ['address_2'],
            postcode: ['postcode', 'post_code'],
            city: ['city'],
            state: ['state'],
            country: ['country'],
        };

        this.onInput = this.onInput.bind(this);
        this.onInputKeydown = this.onInputKeydown.bind(this);
        this.onFormChange = this.onFormChange.bind(this);
        this.onCountryChange = this.onCountryChange.bind(this);
        this.onReceiptTypeChange = this.onReceiptTypeChange.bind(this);
        this.onWooCountryChanged = this.onWooCountryChanged.bind(this);
        this.search = this.search.bind(this);
        this.clear = this.clear.bind(this);
    }

    init() {
        if (!this.form || !this.input || !this.searchButton || !this.clearButton) return;

        this.input.addEventListener('input', this.onInput);
        this.input.addEventListener('keydown', this.onInputKeydown);
        this.form.addEventListener('change', this.onFormChange);
        this.searchButton.addEventListener('click', this.search);
        this.clearButton.addEventListener('click', this.clear);
        if (window.jQuery) {
            window.jQuery(document.body).on(
                'country_to_state_changed.comTaxNumberLookup updated_checkout.comTaxNumberLookup',
                this.onWooCountryChanged
            );
        }
        this.lastCountry = this.getCountry();
        this.syncMode();
        this.syncFormMode();
    }

    destroy() {
        this.input?.removeEventListener('input', this.onInput);
        this.input?.removeEventListener('keydown', this.onInputKeydown);
        this.form?.removeEventListener('change', this.onFormChange);
        this.searchButton?.removeEventListener('click', this.search);
        this.clearButton?.removeEventListener('click', this.clear);
        if (window.jQuery) {
            window.jQuery(document.body).off(
                'country_to_state_changed.comTaxNumberLookup updated_checkout.comTaxNumberLookup',
                this.onWooCountryChanged
            );
        }
    }

    getInput(name) {
        return this.form?.querySelector(`[name="billing_${name}"]`) ?? null;
    }

    getCountry() {
        return (this.form?.querySelector('[name="billing_country"]')?.value || 'GR').toUpperCase();
    }

    getMode() {
        const country = this.getCountry();
        if (country === 'GR') return 'aade';
        if (EU_VAT_COUNTRIES.includes(country)) return 'vies';
        return 'manual';
    }

    getReceiptType() {
        return this.form?.querySelector('[name="billing_receipt_type"]:checked')?.value || 'receipt';
    }

    normalizeTaxNumber(value) {
        return String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
    }

    validateAfm(value) {
        const afm = String(value || '').replace(/\D/g, '');
        if (afm.length !== 9 || afm === '000000000') return false;

        let sum = 0;
        for (let index = 0; index < 8; index += 1) {
            sum += Number(afm[index]) * (1 << (8 - index));
        }

        return (sum % 11) % 10 === Number(afm[8]);
    }

    onInput() {
        if (this.isApplying) return;
        if (this.normalizeTaxNumber(this.input.value) !== this.verifiedTaxNumber) {
            this.setVerified(false);
        }
        this.clearError();
    }

    onInputKeydown(event) {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        this.search();
    }

    onFormChange(event) {
        if (event.target?.name === 'billing_country') {
            this.onCountryChange();
            return;
        }
        if (event.target?.name === 'billing_receipt_type') {
            this.onReceiptTypeChange();
        }
    }

    onCountryChange() {
        const country = this.getCountry();
        const changed = country !== this.lastCountry;

        if (changed && !this.isApplying) {
            this.verifiedTaxNumber = '';
            this.setVerified(false);
            this.clearError();
        }
        this.lastCountry = country;
        this.syncMode();
        this.syncFormMode();
    }

    onReceiptTypeChange() {
        this.clearError();
        this.syncFormMode();
    }

    onWooCountryChanged() {
        this.onCountryChange();
        this.restoreAddressLabels();
    }

    restoreAddressLabels() {
        const labels = {
            billing_country_field: 'ΧΩΡΑ / ΠΕΡΙΟΧΗ',
            billing_state_field: 'ΠΕΡΙΦΕΡΕΙΑ',
            billing_address_1_field: 'ΔΙΕΥΘΥΝΣΗ',
            billing_postcode_field: 'ΤΑΧΥΔΡΟΜΙΚΟΣ ΚΩΔΙΚΑΣ',
            billing_city_field: 'ΠΟΛΗ',
            billing_phone_field: 'ΤΗΛΕΦΩΝΟ',
        };

        Object.entries(labels).forEach(([fieldId, text]) => {
            const label = this.form?.querySelector(`#${fieldId} > label`);
            if (!label) return;

            const marker = label.querySelector('.required, .optional');
            label.replaceChildren(document.createTextNode(`${text} `));
            if (marker) label.append(marker);
        });
    }

    syncMode() {
        const mode = this.getMode();
        const descriptionKey = `${mode}Description`;
        const labelKey = `${mode}Label`;

        this.el.dataset.mode = mode;
        this.searchButton.hidden = mode === 'manual';
        this.input.inputMode = mode === 'aade' ? 'numeric' : 'text';
        this.input.autocomplete = 'off';

        if (this.description) {
            this.description.textContent = this.el.dataset[`taxNumberLookup${descriptionKey[0].toUpperCase()}${descriptionKey.slice(1)}`] || '';
        }
        if (mode !== 'manual') {
            this.searchButton.setAttribute(
                'aria-label',
                this.el.dataset[`taxNumberLookup${labelKey[0].toUpperCase()}${labelKey.slice(1)}`] || ''
            );
        }
    }

    syncFormMode() {
        const isInvoice = this.getReceiptType() === 'invoice';
        const isGreekInvoice = isInvoice && this.getCountry() === 'GR';

        this.form.classList.toggle('is-invoice', isInvoice);
        this.form.classList.toggle('is-greek-invoice', isGreekInvoice);
        this.input.required = isInvoice;

        const companyName = this.getInput('company_name');
        const companyProfession = this.getInput('company_profession');
        const taxOffice = this.getInput('tax_office');
        if (companyName) companyName.required = isInvoice && !isGreekInvoice;
        if (companyProfession) companyProfession.required = false;
        if (taxOffice) taxOffice.required = false;
        this.syncVerificationValidity();

        this.form.dispatchEvent(new CustomEvent('com:invoice-mode-change', {
            bubbles: true,
            detail: {
                isInvoice,
                isGreekInvoice,
                country: this.getCountry(),
            },
        }));
    }

    setLoading(loading) {
        this.el.classList.toggle('is-loading', loading);
        this.searchButton.disabled = loading;
        this.input.readOnly = loading;
    }

    setVerified(verified) {
        this.el.classList.toggle('is-verified', verified);
        this.syncVerificationValidity();
    }

    syncVerificationValidity() {
        const requiresVerification = this.getReceiptType() === 'invoice' && this.getMode() !== 'manual';
        const verificationMessage = this.getMode() === 'aade'
            ? 'Παρακαλούμε επαληθεύστε το ΑΦΜ μέσω ΑΑΔΕ.'
            : 'Παρακαλούμε επαληθεύστε το VAT number μέσω VIES.';
        const message = this.currentError || (
            requiresVerification && !this.el.classList.contains('is-verified')
                ? verificationMessage
                : ''
        );

        this.input.setCustomValidity(message);
    }

    showError(message) {
        if (!this.error) return;
        this.currentError = message || '';
        this.error.textContent = message || '';
        this.error.hidden = !message;
        this.syncVerificationValidity();
        if (message) this.input.reportValidity();
    }

    clearError() {
        this.currentError = '';
        if (this.error) {
            this.error.textContent = '';
            this.error.hidden = true;
        }
        this.syncVerificationValidity();
    }

    setCompanyInfo(value) {
        if (!this.companyInfo) return;
        this.companyInfo.replaceChildren();
        String(value || '').split(/<br\s*\/?>/i).forEach((line, index) => {
            if (index > 0) this.companyInfo.append(document.createElement('br'));
            this.companyInfo.append(document.createTextNode(line.replace(/<[^>]*>/g, '')));
        });
    }

    setInputFields(data = {}, clearMissing = false) {
        this.isApplying = true;

        Object.entries(this.fieldMap).forEach(([name, sourceNames]) => {
            let value;
            for (const sourceName of sourceNames) {
                if (Object.prototype.hasOwnProperty.call(data, sourceName) && data[sourceName] !== null) {
                    value = data[sourceName];
                    break;
                }
            }
            if (value === undefined && !clearMissing) return;

            const input = this.getInput(name);
            if (!input) return;

            const nextValue = value ?? '';
            const changed = input.value !== String(nextValue);
            input.value = nextValue;
            input.dispatchEvent(new Event('input', { bubbles: true }));

            if (changed) {
                input.dispatchEvent(new Event('change', { bubbles: true }));
                if (window.jQuery && input.tagName === 'SELECT') {
                    window.jQuery(input).val(nextValue).trigger('change.select2');
                }
            }
        });

        this.isApplying = false;
    }

    applyVerifiedData(data) {
        this.setInputFields(data);
        if (this.companyName) this.companyName.textContent = data.company_name || '';
        this.setCompanyInfo(data.company_info || '');
        this.verifiedTaxNumber = this.normalizeTaxNumber(data.tax_number || this.input.value);
        this.setVerified(true);
        this.clearError();
        this.syncMode();
        this.syncFormMode();

        if (window.jQuery) {
            window.jQuery(document.body).trigger('update_checkout');
        }
    }

    async search() {
        const mode = this.getMode();
        const taxNumber = this.input.value.trim();

        if (mode === 'manual') return;
        if (!taxNumber) {
            this.showError(mode === 'aade' ? 'Συμπληρώστε ΑΦΜ.' : 'Συμπληρώστε VAT number.');
            return;
        }
        if (mode === 'aade' && !this.validateAfm(taxNumber)) {
            this.showError('Το ΑΦΜ δεν είναι έγκυρο.');
            return;
        }

        const body = new FormData();
        body.append('action', 'search_tax_number');
        body.append('tax_number', taxNumber);
        body.append('country', this.getCountry());
        body.append('security', this.nonce?.value || '');

        this.setLoading(true);
        this.clearError();

        try {
            const response = await fetch(window.THEME_OBJ?.ajaxURL || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body,
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!payload.success) {
                throw new Error(payload.data?.message || 'Η επαλήθευση δεν ολοκληρώθηκε.');
            }
            this.applyVerifiedData(payload.data || {});
        } catch (error) {
            this.verifiedTaxNumber = '';
            this.setVerified(false);
            this.showError(error.message || 'Η επαλήθευση δεν ολοκληρώθηκε.');
        } finally {
            this.setLoading(false);
        }
    }

    clear() {
        const country = this.getCountry();
        this.setInputFields({
            company_name: '',
            company_profession: '',
            tax_number: '',
            tax_office: '',
            address_1: '',
            address_2: '',
            postcode: '',
            city: '',
            state: '',
            country,
        }, true);
        this.verifiedTaxNumber = '';
        this.setVerified(false);
        this.clearError();
        this.syncMode();
        this.syncFormMode();
    }
}
