import { module } from 'modujs';
import axios from "axios";
import Currency from '@tadashi/currency';

export default class extends module {
    constructor(m) {
        super(m);
        this.updateInterval();
        this.updateDonationUsages();
        this.addEvents()
        this.initInput();
        this.formMesage = this.$( 'form-message' )[0];

    }

    addEvents(){
        this.el.querySelectorAll( '[data-donation-form="donation-amount"]' ).forEach( el => {
            el.addEventListener( 'click', this.updateDonationAmount.bind( this ) );
            if( el.classList.contains( 'active' ) ){
                this.selectedDonationAmount = el;
            }
        });
        this.button = this.el.querySelector( '[data-donation-form="add-to-cart"]');
        this.button.addEventListener( 'click', this.addToCart.bind( this ) );
        this.el.addEventListener('change', this.onChange.bind(this));
    }

    updateDonationAmount( e ){
        if( e.currentTarget !== this.selectedDonationAmount ) {
            if( this.selectedDonationAmount ) {
                this.selectedDonationAmount.classList.remove( 'active' );
            }
            this.selectedDonationAmount = e.currentTarget;
            this.selectedDonationAmount.classList.add( 'active' );
        }
        this.el.classList.remove( 'donation-loading' );
        if( ! this.selectedDonationAmount ){ e.currentTarget.classList.remove( 'active' ); }
        this.updateAmount();
    }



    updateAmount(){
        this.amount = this.selectedDonationAmount ? this.selectedDonationAmount.dataset.amount : 0;
        if( this.amount === 'custom' ){
            this.amount = this.input.value;
        }
        this.updatePreview();
    }
    updatePreview(){
        if( ! this.itemPreview || ! this.totalPreview ){
            this.itemPreview = this.el.querySelector( '[data-donation-form="donation-price"]');
            this.totalPreview = this.el.querySelector( '[data-donation-form="donation-price-total"]');
        }
        this.itemPreview.innerHTML = this.amount;
        this.totalPreview.innerHTML = this.amount;
        this.el.classList.toggle( 'invalid-amount', this.getAmount() <= 0);
        this.button.disabled = this.getAmount() <= 0;
    }



    onChange(e) {
        let el = e.target;
        if( el.matches( '[name="donation-interval"]' ) ){
            this.updateInterval();
        } else if ( el.matches( '[name="donation-usages[]"]' ) ) {
            this.updateDonationUsages();
        }
        this.updatePreview();
    }

    updateInterval( e ){
        this.el.querySelectorAll( '[name="donation-interval"]').forEach( checkbox => {
            if( checkbox.checked ){
                this.interval = checkbox.value;
                this.el.classList.add( 'interval-' + checkbox.value );
            } else {
                this.el.classList.remove( 'interval-' + checkbox.value );
            }

        })
        this.interval = this.el.querySelector( '[name="donation-interval"]:checked').value;
    }
    updateDonationUsages(){
        this.selectedDonationUsages = [];
        this.el.querySelectorAll('input[name="donation-usages[]"]:checked').forEach(input => {
            const label = input.closest('label')?.querySelector('[data-checkbox-label]')?.dataset.uppercaseLabel?.trim() || '';
            this.selectedDonationUsages.push( { value: input.value, label } );
        });
        this.el.classList.toggle( 'has-donation-usages', this.selectedDonationUsages.length );
        this.donationUsages = this.el.querySelector( '[data-donation-form="donation-usages"]');
        this.donationUsages.innerHTML = this.selectedDonationUsages.map(item => item.label).join(', ');
    }


    getAmount(){
        let amount = this.amount.replace(/€/g, '').replace(/\s/g, '').replace(/\./g, '').replace(',', '.').trim();
        return parseFloat( amount );
    }



    addToCart(){
        if( this.el.classList.contains( 'loading' ) ) return;
        this.el.classList.add('loading');
        this.el.classList.remove('error', 'success');
        const formData = new FormData();
        formData.append('action', this.el.dataset.action );
        formData.append('nonce', this.el.dataset.nonce );
        formData.append('amount', this.getAmount());
        formData.append('interval', this.interval );
        formData.append('named_donor', this.el.querySelector('[name="named_donor"]').checked ? 'yes' : 'no' );
        this.selectedDonationUsages.forEach(item => {
            formData.append('usages[]', item.value);
        });


        axios.post( THEME_OBJ.ajaxURL, formData  ).then( response => {
            let data = response.data.data;
            this.el.classList.remove( 'loading' );
            this.el.classList.add('success');
            this.formMesage.innerHTML = data.message;
            this.call( 'update', data.cart, 'Cart' );
            this.call( 'show', false, 'CartButton', 'main' );
            this.reset();

        }).catch( e => {
            this.el.classList.remove( 'loading' );
            let data = e.response.data.data;
            this.el.classList.add('error');
            this.formMesage.innerHTML = data.message;
        });
    }





    initInput(){
        this.input = this.el.querySelector( '[data-donation-form="custom-donation-amount"]' );
        this.input.addEventListener('input', this.setInputWidth.bind(this));
        this.input.addEventListener('keyup', this.setInputWidth.bind(this));
        new Currency( this.input , {  maskOpts: { digits: 2, prefix: '€', locales: 'de-DE', options: { style: 'currency', currency: 'EUR' } } });
        this.setInputWidth();
    }


    setInputWidth() {
        this.updateAmount();
        let measure = this.el.querySelector('.input-width-measure');
        if (!measure) {
            measure = document.createElement('span');
            measure.className = 'input-width-measure';
            const styles = window.getComputedStyle(this.input);
            Object.assign(measure.style, { position: 'absolute', visibility: 'hidden', whiteSpace: 'nowrap', fontSize: styles.fontSize, fontFamily: styles.fontFamily, fontWeight: styles.fontWeight, letterSpacing: styles.letterSpacing, textTransform: styles.textTransform, padding: styles.padding });
            this.el.appendChild(measure);
        }
        measure.textContent = this.input.value || this.input.placeholder || '';
        const minWidth = parseFloat(getComputedStyle(document.documentElement).fontSize) * 10.3;
        const newWidth = Math.max(measure.offsetWidth, minWidth);
        this.input.style.width = `${newWidth}px`;
    }

    reset(){
        this.el.querySelectorAll('input[name="donation-usages[]"]:checked').forEach(input => {
            input.click();
        });
    }

}
