import { module } from 'modujs';
const { slideToggle, slideDown, slideUp } = window.domSlider;
import Emitter from "tiny-emitter/instance";
import axios from "axios";
import { loadStripeJs } from "../utils/stripe";
export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'toggle-step': 'toggleStep' } };
        Emitter.on('form-submit-validation-error', this.onFormError.bind( this ) );
        this.form = this.el;
        this.submitBind = this.onSubmit.bind( this );
        this.el.addEventListener( 'submit', this.submitBind );
        this.createStripeForm();
        this.handleShippingAddressChanges();
        this.shippingMethods = this.$( 'shipping-methods' );
        this.shippingMethodsContainer = this.$( 'shipping-methods-container' );
        this.notices = this.$( 'notices' )[0];

        if( this.shippingMethods && this.shippingMethodsContainer ){
            this.shippingMethods = this.shippingMethods[0];
            this.shippingMethodsContainer = this.shippingMethodsContainer[0];
        }

    }

    handleShippingAddressChanges(){
        const inputs = this.form.querySelectorAll( 'input[name^="billing_receipt_type"], input[name^="billing_address_1"],  input[name^="billing_postcode"], input[name^="billing_city"], [name^="billing_country"], [name^="billing_state"],' +
            ' input[name^="shipping_address_"], input[name^="shipping_postcode"], input[name^="shipping_city"], select[name^="shipping_country"], [name^="shipping_state"], input[name="ship_to_different_address"]' );
        inputs.forEach(input => input.addEventListener('change', e => {
            this.sendRefreshRequest();
        }) );


    }

    sendRefreshRequest() {
        const formData = new FormData(this.form);
        formData.append('action', 'iw_cart_refresh_totals');
        this.shippingMethodsContainer.classList.add( 'loading' );
        this.shippingMethods.innerHTML = '';
        this.call( 'shippingLoading', false , 'Cart' );
        axios.post(THEME_OBJ.ajaxURL, formData, { withCredentials: true }).then(res => {
            this.shippingMethodsContainer.classList.remove( 'loading' );
            if (res.data.success) {
                const { cart, shipping_methods_html } = res.data.data;
                this.shippingMethodsContainer.classList.remove( 'loading' );
                if( this.shippingMethods && shipping_methods_html ){
                    this.shippingMethods.innerHTML = shipping_methods_html;
                    this.call('update', this.shippingMethods, 'app');
                }
                this.call( 'update', cart, 'Cart' );
            }
        }).catch(err => console.error( 'Refresh error:', err ) );
    };



    onSubmit(e) {

        if( this.el.classList.contains( 'has-errors') ){
            return;
        }
        e.preventDefault();
        if( this.stripeCardElementHolder ) {
            this.stripeCardElementHolder.classList.remove('error');
        }
        this.paymentMethod = this.el.querySelector('input[name="payment_method"]:checked');
        if( this.paymentMethod ){
            this.paymentMethod  = this.paymentMethod.value;
        }

        this.notices.innerHTML = '';
        if(  this.paymentMethod === 'stripe' ){
            if( this.stripePaymentMethodInput.value ){
                this.sendForm();
                return;
            }

            this.stripe.createPaymentMethod({ type: 'card', card: this.card }).then(result => {
                if( result.paymentMethod ){
                    this.setStripePaymentMethod(result.paymentMethod.id);
                    this.sendForm();
                } else if( result.error ) {
                    this.stripeCardElementHolder.classList.add( 'error'  );
                    this.stripeCardElementErrorMessage.textContent = result.error.message;
                    this.call( 'scrollTo', { target: this.stripeCardElementHolder, options : { duration: 0.01, offset: -60 -  document.querySelector( 'header' ).offsetHeight  } }, 'Scroll');
                }
            });
        } else {
            this.sendForm();
        }
    }

    sendForm(){
        this.el.classList.remove('success', 'error')
        this.el.classList.add('loading');
        const formData = new FormData(this.el);
        formData.append('action', 'iw_cart_checkout');
        axios.post(THEME_OBJ.ajaxURL, formData, { withCredentials: true } ).then(response => {
            this.el.classList.remove('loading');
            if (response.data.result === 'success' && response.data.redirect) {
                window.location.href = response.data.redirect;
            } else {
                this.notices.innerHTML =  response.data.messages ;

            }
        } ).catch( error => console.info(error) );
    }

    async createStripeForm(){
        const StripeConstructor = await this.loadStripeJs();
        const stripeKey = window.wc_stripe_upe_params?.key || window.wc_stripe_params?.key || this.el.dataset.stripeKey;
        if ( ! stripeKey )  throw new Error('Stripe publishable key not found');
        this.stripe = StripeConstructor( stripeKey );

        this.stripePaymentMethodInput = this.el.querySelector('input[name="stripe_source"]');
        this.wcStripePaymentMethodInput = this.el.querySelector('input[name="wc-stripe-payment-method"]');
        this.wcStripeSelectedPaymentTypeInput = this.el.querySelector('input[name="wc_stripe_selected_upe_payment_type"]');
        if( ! this.stripePaymentMethodInput ) return;

        this.stripeCardElementHolder = this.$('stripe-card-element-holder')[0];
        this.stripeCardElementErrorMessage = this.$('stripe-card-element-error-message')[0];


        const elements = this.stripe.elements( { locale: document.documentElement.lang || 'en' });
        this.card = elements.create('card', { hidePostalCode: true, style: {
                base: { color: '#31312F', fontSize: '20px', '::placeholder': { color: '#31312F' }, },
                invalid: { color: '#FF0F00' },
            }, });
        this.card.mount('[data-checkout-form="stripe-card-element"]');
    }



    async loadStripeJs() {
        return loadStripeJs();
    }

    setStripePaymentMethod( paymentMethodId ) {
        this.stripePaymentMethodInput.value = paymentMethodId;
        if ( this.wcStripePaymentMethodInput ) {
            this.wcStripePaymentMethodInput.value = paymentMethodId;
        }
        if ( this.wcStripeSelectedPaymentTypeInput ) {
            this.wcStripeSelectedPaymentTypeInput.value = 'card';
        }
    }


    onFormError( form ){
        if (!this.el.contains(form)) return;
        let firstError = form.querySelector('.StripeElement--invalid,[data-module-validate].error');
        if( firstError ){
            let step = firstError.closest( '[data-checkout-form="step"]' );
            let active = this.el.querySelector( '.active[data-checkout-form="step"]');
            if( step !== active ){
                active.classList.remove( 'active' );
                step.classList.add( 'active' );
            }
            setTimeout( () => this.call('scrollTo', { target: firstError, options: { duration: 0.01, offset: -60 -  document.querySelector( 'header' ).offsetHeight  } }, 'Scroll'), 100 )
        }
    }

    toggleStep( e ) {

        console.info(e)

        let toggle = e.currentTarget;
        let step = toggle.closest( '.group' );
        if( step.classList.contains( 'active' ) ) return;

        let active = this.el.querySelector( '.active[data-checkout-form="step"]');
        active.classList.remove( 'active' );
        step.classList.add( 'active' );
        this.call( 'scrollTo', { target: step, options : { offset: -window.innerHeight/2  } }, 'Scroll');
    }

    update( cart ){
        this.el.classList.toggle( 'no-shipping', ! cart.needs_shipping )
    }
}
