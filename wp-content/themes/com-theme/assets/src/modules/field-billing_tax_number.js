import { module } from 'modujs';
import axios from "axios";

export default class extends module {
    constructor(m) {
        super(m);

        this.container = this.$('container')[0];

        this.button = this.el.querySelector( '[data-button]' );
        this.input = this.el.querySelector( 'input[type="text"]');

        this.companyName = this.$( 'company-name' )[0];
        this.companyMainKad = this.$( 'company-main-kad' )[0];
        this.companyInfo = this.$( 'company-info' )[0];
        this.companyAddress = this.$( 'company-address' )[0];

        this.inputField = this.input.closest( '.group.field' );
        this.clearBtn = this.$( 'clear' )[0];
        this.form = this.el.closest( 'form' );
        this.defaultRules = this.inputField.dataset.rules || '';
        this.euRules = 'required|has_class:tax-number-validated';
        this.manualRules = 'required';


        this.button.addEventListener( 'click', this.searchTaxNumber.bind( this ));
        this.clearBtn.addEventListener( 'click', this.clear.bind( this ));
        this.input.addEventListener('change', (e) => {
            this.el.classList.remove( 'tax-number-validated' );
        });
        this.input.addEventListener('keydown', (e) => {
            let allowedCharacter = this.getBillingCountry() === 'GR' ? /^[0-9]$/.test(e.key) : /^[a-zA-Z0-9]$/.test(e.key);
            if (  !allowedCharacter && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'ArrowLeft' && e.key !== 'ArrowRight' && e.key !== 'Tab' && e.key !== 'Enter' &&  !( (e.ctrlKey || e.metaKey)  ) ) {
                e.preventDefault();
            }
            if (e.key === 'Enter') {
                e.preventDefault();
                this.searchTaxNumber();
            }
        });

        this.countryField = this.getInput( 'country' );
        if( this.countryField ){
            this.countryField.addEventListener( 'change', () => {
                this.el.classList.remove( 'tax-number-validated' );
                this.syncCountryMode();
            } );
        }

        this.fieldMap = {
            company_name: [ 'company_name' ],
            company_profession: [ 'company_profession' ],
            tax_number: [ 'tax_number' ],
            tax_office: [ 'tax_office' ],
            address_1: [ 'address_1' ],
            address_2: [ 'address_2' ],
            postcode: [ 'postcode', 'post_code' ],
            city: [ 'city' ],
            state: [ 'state' ],
            country: [ 'country' ],
        };

    }

    init(){
        this.syncCountryMode();
    }

    getInput( name ){
        return this.form.querySelector(`[name="billing_${name}"],[name="user_fields[billing_${name}]"]`);
    }

    getBillingCountry(){
        let country = this.getInput( 'country' );
        return country && country.value ? country.value.toUpperCase() : 'GR';
    }

    isEuVatCountry( country ){
        return [
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU',
            'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'XI',
        ].includes( ( country || '' ).toUpperCase() );
    }

    getBillingMode(){
        let country = this.getBillingCountry();
        if( country === 'GR' ) return 'aade';
        if( this.isEuVatCountry( country ) ) return 'vies';
        return 'manual';
    }

    syncCountryMode(){
        let mode = this.getBillingMode();
        let rules = mode === 'aade' ? this.defaultRules : ( mode === 'vies' ? this.euRules : this.manualRules );

        this.inputField.dataset.rules = rules;
        this.call( 'setRules', rules, 'Validate', this.inputField.dataset.moduleValidate );
        this.button.classList.toggle( 'hidden', mode === 'manual' );
        this.el.classList.toggle( 'tax-number-manual', mode === 'manual' );
        this.el.classList.toggle( 'tax-number-vies', mode === 'vies' );
    }


    searchTaxNumber(){
        let mode = this.getBillingMode();
        if( mode === 'manual' ){
            this.call( 'showError', this.el.dataset.manualValidationMessage || '', 'Validate', this.inputField.dataset.moduleValidate );
            return;
        }

        setTimeout(() => {

            this.call( 'validateRule', mode === 'aade' ? 'tax_number' : 'required', 'Validate', this.inputField.dataset.moduleValidate );
            setTimeout( () => {
                if( this.inputField.classList.contains( 'error' ) ){
                    return;
                }
                const formData = new FormData();
                formData.append('action', 'search_tax_number' );
                formData.append('tax_number', this.input.value );
                formData.append('country', this.getBillingCountry() );
                // Append nonce from hidden input
                const nonceField = this.$( 'nonce' )[0];
                if (nonceField) {
                    formData.append('security', nonceField.value);
                }
                this.inputField.classList.add( 'loading' );
                axios.post(THEME_OBJ.ajaxURL, formData).then(response => {
                    let responseData = response.data;
                    this.inputField.classList.remove( 'loading' );
                    this.el.classList.toggle( 'tax-number-validated', responseData.success );
                    if( responseData.success ){
                        responseData = responseData.data;
                        this.companyName.textContent = responseData.company_name;
                        this.companyInfo.innerHTML = responseData.company_info;
                        this.setInputFields( responseData );
                        this.call( 'sendRefreshRequest', false, 'CheckoutForm' );
                    } else {
                        responseData = responseData.data;
                        this.call( 'showError', responseData.message, 'Validate', this.inputField.dataset.moduleValidate );
                    }
                });
            }, 100 );

        },100);


    }

    setInputFields( data = {} ){
        Object.entries( this.fieldMap ).forEach( ( [ name, sourceNames ] ) => {
            let value = '';
            for( let sourceName of sourceNames ){
                if( data[ sourceName ] ){
                    value = data[ sourceName ];
                    break;
                }
            }

            const input = this.getInput( name );
            if (input) {
                let previousValue = input.value;
                input.value = value;
                input.dispatchEvent( new Event( 'input', { bubbles: true, cancelable: true } ) );

                if( input.tagName === 'SELECT' && previousValue !== value ){
                    input.dispatchEvent( new CustomEvent( 'update', { detail: { value }, bubbles: true, cancelable: true } ) );
                    input.dispatchEvent( new Event( 'change', { bubbles: true, cancelable: true } ) );
                }
            }
        })
    }

    clear(){
        this.el.classList.remove( 'tax-number-validated' );
        this.setInputFields( { country: this.getBillingCountry() || 'GR' } );
        this.input.value = '';
        this.syncCountryMode();
        this.call( 'sendRefreshRequest', false, 'CheckoutForm' );
    }
}
