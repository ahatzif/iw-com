import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.radios = this.el.querySelectorAll('input[type="radio"]');
        this.form = this.el.closest( 'form' );
    }


    getField( name ){
        return this.form.querySelector(`[name="${name}"],[name="user_fields[${name}]"]`);
    }

    getFieldWrapper( name ){
        let el = this.getField( name );
        return el ? el.closest( '[data-module-field]' ) : null;
    }

    getSelectedReceiptType(){
        let selected = this.el.querySelector('input[type="radio"][name="billing_receipt_type"]:checked,input[type="radio"][name="user_fields[billing_receipt_type]"]:checked ');
        return selected ? selected.value : 'receipt';
    }

    getBillingCountry(){
        let country = this.getField( 'billing_country' );
        return country && country.value ? country.value.toUpperCase() : 'GR';
    }

    toggleFields( names, hidden ){
        names.forEach( name => {
            let el = this.getFieldWrapper( name );
            if( el ) el.classList.toggle( 'hidden', hidden );
        });
    }

    onChange( e, refresh = true ){
        if( e && e.target && e.target.type === 'radio' && ! e.target.checked ) return;

        let receiptType = this.getSelectedReceiptType();
        let isInvoice = receiptType === 'invoice';
        let isGreekInvoice = isInvoice && this.getBillingCountry() === 'GR';

        this.toggleFields( [ 'billing_tax_number' ], ! isInvoice );
        this.toggleFields( [ 'billing_country' ], false );
        this.toggleFields( [ 'billing_address_1', 'billing_address_2', 'billing_postcode', 'billing_city', 'billing_state' ], isGreekInvoice );
        this.toggleFields( [ 'billing_company_name' ], ! isInvoice || isGreekInvoice );
        this.toggleFields( [ 'billing_company_profession', 'billing_tax_office' ], true );

        this.form.classList.toggle( 'is-invoice', isInvoice );
        this.form.classList.toggle( 'is-greek-invoice', isGreekInvoice );

        if( refresh ){
            this.call( 'sendRefreshRequest', false, 'CheckoutForm' );
        }
    }

    init(){
        this.radios.forEach(radio => radio.addEventListener('change', this.onChange.bind( this )) );
        let country = this.getField( 'billing_country' );
        if( country ){
            country.addEventListener( 'change', this.onChange.bind( this ) );
        }

        this.onChange( false, false );
    }
}
