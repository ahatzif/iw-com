import { module } from 'modujs';
import axios from 'axios';
import Emitter from "tiny-emitter/instance";
const { slideUp } = window.domSlider;
export default class extends module {
    constructor(m) {
        super(m);

        this.events = { click: { 'validate': 'isValid' } };
        this.elements = [...this.el.querySelectorAll('[data-module-validate]')];

        this.controls = [...this.el.querySelectorAll('[data-form-control]')];

        this.parentModal = this.el.closest('[data-module-modal]:not([data-success-persistent])');


        this.el.addEventListener('clear', this.clear.bind(this));
        this.el.addEventListener('submit', this.onSubmit.bind(this));

        this.successMessage = this.$('success-message');
        this.turnstileContainer = this.el.querySelector('[data-form="turnstile"]');
        this.loadTurnstile();

        this.messages = this.$('messages');
        if (this.el.dataset.modalId && this.messages.length) {
            this.messagesEl = this.$('messages')[0];
            this.modal = document.querySelector('[data-module-modal="' + this.el.dataset.modalId + '"]')
            if (this.modal) {
                this.modalContent = this.modal.querySelector('[data-modal="content"]');
                this.modalContent.appendChild(this.messagesEl);
            }
        }

        this.scrollBar = this.el.closest('[data-module-scrollbar]')
    }
    loadTurnstile() {

        if (!this.turnstileContainer) return;
        if (typeof turnstile === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
            script.async = true;
            script.defer = true;
            script.onload = () => this.renderTurnstile();
            document.body.appendChild(script);
        } else {
            this.renderTurnstile();
        }
    }

    renderTurnstile() {
        if (!this.turnstileContainer) return;
        if (this.turnstileWidgetId) {
            turnstile.reset(this.turnstileWidgetId);
        } else {
            this.turnstileWidgetId = turnstile.render(this.turnstileContainer, { sitekey: this.turnstileContainer.dataset.siteKey, theme: 'light', callback: (token) => { } });
        }

    }

    onSubmit(e) {
        if (!this.isValid( false )) {
            this.el.classList.add( 'has-errors' );
            e.preventDefault();
            return;
        }
        this.el.classList.remove( 'has-errors' );

        if (this.el.dataset.ajax !== 'false') {
            e.preventDefault();
            this.el.classList.remove('success', 'error')
            this.el.classList.add('loading');
            axios.post(this.el.getAttribute('action'), new FormData(this.el), { withCredentials: true } ).then(response => {
                this.el.classList.remove('loading');
                response.data.success === true ? this.onSuccess(response) : this.onError(response);
            } ).catch(error => {
                Emitter.emit('form-validation-error', this.el);
                this.el.classList.remove('loading');
                if(  error.response && error.response.data && error.response.data.data && error.response.data.data.errors ) {
                    this.elements.forEach( element => element.dispatchEvent( new CustomEvent( 'formError', { detail : error.response.data.data } ) ) );
                    this.scrollToFirstError();
                }
            });
        }
    }

    onSuccess(response) {




        try{
            Emitter.emit('form-success', { el : this.el, response : response});
        } catch( e ){

        }


        this.el.classList.add('success');


        if (this.el.hasAttribute('data-slide-up-on-success') ) {
            slideUp( { element : this.el, duration : 600 } );
            return;
        }

        if (response.data.data.redirect) {
            window.location.href = response.data.data.redirect;
            return;
        }

        if (response.data.data.reload) {
            window.location.reload();
            return;
        }
        if (this.successMessage.length && response.data.data.message) {
            this.successMessage[0].innerHTML = response.data.data.message;
        }



        this.call('scrollTo', { target: this.el.closest('section'), options: { offset: -150 } }, 'Scroll');
        if( this.el.dataset.noReset === undefined ) {
            this.el.reset();
            this.controls.forEach(element => element.dispatchEvent(new CustomEvent('form-reset')));
        }
        this.renderTurnstile();

        if (this.el.hasAttribute('data-reset-on-success') ) {
            setTimeout(() => this.el.classList.remove('success'), 4000);
        }


        if (this.modal) {
            this.modal.classList.add('active');
            this.messagesEl.classList.remove('error');
            this.messagesEl.classList.add('success');
        }


        setTimeout(() => {
            if (this.parentModal) {
                this.call('hideModal', false, 'Modal', this.parentModal.dataset.moduleModal);
            }
        }, 7000);





    }

    onError(response) {

        Emitter.emit('form-error', { el: this.el, response : response.data });

        this.$('error-message')[0].innerHTML = response.data.data.message;
        this.el.classList.add('error');
        this.el.classList.add('error');



        if (this.scrollBar) {
            Emitter.emit('scroll-to', { el: this.el ,container: this.scrollBar });
        } else {
            this.call('scrollTo', { target: this.el, options: { offset: -150 } }, 'Scroll');
        }




        if (this.modal) {
            this.call('showModal', false, 'Modal', this.el.dataset.modalId);
            this.messagesEl.classList.remove('success');
            this.messagesEl.classList.add('error');
        }
    }

    scrollToFirstError() {
        let firstError = this.el.querySelector('[data-module-validate].error');

        if( this.parentModal ){
            this.parentModal.scrollTop = firstError.offsetTop;
        }
        if (this.scrollBar) {
            Emitter.emit('scroll-to', { el:firstError ,container: this.scrollBar });
        } else {
            this.call('scrollTo', { target: firstError, options: { offset: -150 } }, 'Scroll');
        }
    }
    isValid( sendEvent = true  ) {
        this.hasError = false;
        this.elements = [...this.el.querySelectorAll('[data-module-validate]')];


        for (let element of this.elements) {
            let skipValidationGroup = element.closest('[data-skip-validation-when-hidden]');
            if (skipValidationGroup && skipValidationGroup.classList.contains( 'hidden' ) && !skipValidationGroup.classList.contains('active') ) {
                continue;
            }
            if (element.classList.contains('error')) {
                this.hasError = true;
            } else if (!this.modules.Validate[element.dataset.moduleValidate].validate()) {
                this.hasError = true;
            }

        }


        if( sendEvent ){
            if( this.hasError ){
                this.scrollToFirstError();
                Emitter.emit('form-validation-error', this.el);

            } else{
                Emitter.emit('form-validation-success', this.el);
            }
        }

        if( this.hasError ){
            Emitter.emit('form-submit-validation-error', this.el);
        } else {
            Emitter.emit('form-submit-validation-success', this.el);
        }

        return !this.hasError;
    }



    clear() {
        this.el.classList.remove('success', 'error');

        if( this.el.dataset.noReset === undefined ){
            this.el.reset();
            this.elements.forEach(element => element.dispatchEvent(new CustomEvent('clear')));
        }



    }

}


