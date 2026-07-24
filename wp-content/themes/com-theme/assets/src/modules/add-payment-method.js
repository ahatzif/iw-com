
import { module } from 'modujs';
const { slideToggle, slideDown, slideUp } = window.domSlider;
import Emitter from "tiny-emitter/instance";
import axios from "axios";
import { loadStripeJs } from "../utils/stripe";
export default class extends module {
    constructor(m) {
        super(m);
        this.form = this.el;
        this.submitBind = this.onSubmit.bind( this );
        this.el.addEventListener( 'submit', this.submitBind );
        this.nonce = this.el.dataset.nonce;
        this.errorMessage = this.$('error-message')[0];
        this.stripeLoaderPromise = null;
        this.stripePaymentMethodInput = this.el.querySelector('input[name="stripe_source"]');
        if ( ! this.stripePaymentMethodInput ) return;

        this.createStripeForm();

    }

    async onSubmit(e) {
        e.preventDefault();
        this.errorMessage.textContent = '';
        this.el.classList.remove('success', 'error')
        this.el.classList.add('loading');

        try {

            const intentFormData = new FormData();
            intentFormData.append('action', 'iw_create_setup_intent');
            intentFormData.append('nonce', this.nonce);

            const intentResponse = await axios.post( THEME_OBJ.ajaxURL, intentFormData, { withCredentials: true });

            if ( ! intentResponse.data || !intentResponse.data.success || !intentResponse.data.data || !intentResponse.data.data.client_secret || !intentResponse.data.data.intent_id ) {
                throw new Error( intentResponse.data?.data?.message ??  'Αποτυχία δημιουργίας Setup Intent');
            }
            const { client_secret, intent_id } = intentResponse.data.data;
            const result = await this.stripe.confirmCardSetup(client_secret, { payment_method: { card: this.card, },});

            if (result.error) { throw result.error; }

            let setupIntentInput = this.el.querySelector('input[name="wc-stripe-setup-intent"]');
            if (!setupIntentInput) {
                setupIntentInput = document.createElement('input');
                setupIntentInput.type = 'hidden';
                setupIntentInput.name = 'wc-stripe-setup-intent';
                this.el.appendChild(setupIntentInput);
            }
            setupIntentInput.value = intent_id;
            this.stripePaymentMethodInput.value = result.setupIntent.payment_method;
            this.sendForm();

        } catch (error) {
            this.el.classList.remove('loading');
            this.el.classList.add('error');
            this.errorMessage.innerHTML = error.message || 'Σφάλμα κατά την αποθήκευση κάρτας';
        }
    }

    async loadStripeJs() {
        return loadStripeJs();
    }
    async createStripeForm(){
        try {
            const StripeConstructor = await this.loadStripeJs();
            const stripeKey = window.wc_stripe_upe_params?.key || window.wc_stripe_params?.key || this.el.dataset.stripeKey;
            if ( ! stripeKey )  throw new Error('Stripe publishable key not found');
            this.stripe = StripeConstructor( stripeKey );
            const elements = this.stripe.elements({ locale: document.documentElement.lang || 'en', fonts: [ { cssSrc: 'https://use.typekit.net/pzv0iuf.css' }]});
            const rootFontSizePx = parseFloat( window.getComputedStyle(document.documentElement).fontSize);

            this.card = elements.create('card', { hidePostalCode: true, style: { base: { fontFamily: '"tt-commons-pro", sans-serif', color: '#31312F', fontSize: `${rootFontSizePx * 1.6}px`  , '::placeholder': { color: '#31312F' } }, invalid: { color: '#FF0F00' } }});
            this.card.mount('[data-add-payment-method="stripe-card-element"]');

        } catch (e) {
            this.errorMessage.textContent = 'Αποτυχία φόρτωσης Stripe. Παρακαλώ ανανεώστε τη σελίδα.';
        }
    }

    sendForm(){

        const formData = new FormData(this.el);
        formData.append('action', 'iw_add_payment_method' );
        formData.append('nonce', this.nonce );
        axios.post(THEME_OBJ.ajaxURL, formData, { withCredentials: true } ).then(response => {
            this.el.classList.remove('loading');
            let data = response.data.data;
            if (data.result === 'success' ) {
                if (this.card) {
                    this.card.clear();
                }
                this.call( 'addMethod', data.html, 'AvailablePaymentMethods' );
            } else {
                this.el.classList.add('error');
                this.errorMessage.innerHTML =  data.messages ;
            }
        } ).catch( error => {
            this.el.classList.remove('loading');
            this.el.classList.add('error')
            this.errorMessage.innerHTML =  error.response.data.data.messages ;
        } );
    }
}
