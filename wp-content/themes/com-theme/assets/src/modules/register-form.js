import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";
import axios from 'axios';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'prev': 'prev', 'next': 'next', 'reset' : 'reset', 'resend': 'resendActivationCode' } };
        const stepClass = Array.from(this.el.classList).find(c => c.startsWith('step-'));
        this.screen = stepClass ? parseInt(stepClass.replace('step-', ''), 10) : 1;
        Emitter.on('form-validation-success', this.onFormValidationSuccess.bind(this));
        Emitter.on('form-success', this.onFormSuccess.bind(this));
        Emitter.on('form-validation-error', this.onFormValidationError.bind(this));
        Emitter.on('form-error', this.onFormError.bind(this));
        this.form = this.el.querySelector('form');
        this.activationForm = this.$('activation-form')[0];
        this.resendButton = this.$('resend')[0];
        this.resendMessage = this.$('resend-message')[0];
        this.resendTimer = null;
        this.resendDelay = this.activationForm ? parseInt(this.activationForm.dataset.resendDelay || '60', 10) : 60;
        this.scrollbar = this.el.closest( '[data-module-scrollbar]' );

    }

    prev() { this.toggleScreen(true); }

    next() { this.toggleScreen(); }

    reset() {
        setTimeout( ()=> {
            this.el.classList.remove('step-' + this.screen);
            this.screen = 1;
            this.el.classList.add('step-' + this.screen);
            this.clearResendCountdown();
        }, 1000);

    }

    toggleScreen(isPrev = false) {
        if( this.screen === 1 && isPrev ) return;
        this.el.classList.remove('step-' + this.screen);
        this.screen += isPrev ? -1 : 1;
        if( this.scrollbar ){
            this.call( 'scrollTop', 200, 'Scrollbar', this.scrollbar.dataset.moduleScrollbar );
        }
        this.el.classList.add('step-' + this.screen);
    }
    onFormValidationError(form) {
        if (this.form === form) {
            this.prev();
        }
    }
    onFormSuccess( args ) {

        let form = args.el;
        if (this.form === form) {
            let userRegisterEmail = this.activationForm.querySelector('[name="login_email"]');
            if (userRegisterEmail) {
                userRegisterEmail.value = this.form.querySelector('[name="user_email"]').value;
            }
            let resendAfter = args.response && args.response.data && args.response.data.data ? args.response.data.data.resend_after : 0;
            this.startResendCountdown(resendAfter || this.resendDelay);
            this.next();
        }
        if (this.activationForm === form) {
            this.clearResendCountdown();
            this.next();
        }
    }

    resendActivationCode(e) {
        e.preventDefault();
        if (!this.activationForm || !this.resendButton || this.resendButton.disabled) return;

        let formData = new FormData(this.activationForm);
        formData.set('action', 'iw-auth-resend-activation');
        formData.delete('activation_code');

        this.resendButton.disabled = true;
        this.setResendMessage('');

        axios.post(this.activationForm.getAttribute('action'), formData, { withCredentials: true })
            .then((response) => {
                let data = response.data && response.data.data ? response.data.data : {};
                this.setResendMessage(data.message || '');
                this.startResendCountdown(data.retry_after || this.resendDelay);
            })
            .catch((error) => {
                let data = error.response && error.response.data && error.response.data.data ? error.response.data.data : {};
                this.setResendMessage(data.message || '');
                if (data.retry_after) {
                    this.startResendCountdown(data.retry_after);
                } else {
                    this.enableResendButton();
                }
            });
    }

    startResendCountdown(seconds) {
        if (!this.resendButton) return;

        this.clearResendCountdown(false);
        let remaining = parseInt(seconds || this.resendDelay, 10);
        if (!remaining || remaining < 1) {
            this.enableResendButton();
            return;
        }

        let label = this.resendButton.dataset.label || this.resendButton.textContent.trim();
        let countdownLabel = this.resendButton.dataset.countdownLabel || label + ' ({seconds}s)';

        let update = () => {
            if (remaining <= 0) {
                this.enableResendButton();
                return;
            }

            this.resendButton.disabled = true;
            this.resendButton.textContent = countdownLabel.replace('{seconds}', remaining);
            remaining -= 1;
        };

        update();
        this.resendTimer = window.setInterval(update, 1000);
    }

    clearResendCountdown(enableButton = true) {
        if (this.resendTimer) {
            window.clearInterval(this.resendTimer);
            this.resendTimer = null;
        }

        if (enableButton) {
            this.enableResendButton();
        }
    }

    enableResendButton() {
        if (!this.resendButton) return;

        if (this.resendTimer) {
            window.clearInterval(this.resendTimer);
            this.resendTimer = null;
        }

        this.resendButton.disabled = false;
        this.resendButton.textContent = this.resendButton.dataset.label || this.resendButton.textContent;
    }

    setResendMessage(message) {
        if (this.resendMessage) {
            this.resendMessage.textContent = message;
        }
    }

    onFormValidationSuccess(form){
        if( form === this.form ) {
            let emailInput = this.form.querySelector('[name="user_email"]');
            let nonce = this.$( 'nonce' )[0];
            this.el.classList.add( 'loading' );
            axios.post(THEME_OBJ.ajaxURL, new URLSearchParams(  { action: 'iw-auth-register-check-mail', user_email: emailInput.value, security: nonce.value }))
            .then((response) => {
                this.el.classList.remove( 'loading' );
                if (response.data && response.data.success) {
                    this.next();
                }
            })
            .catch((error) => {
                this.el.classList.remove( 'loading' );
                if(  error.response && error.response.data && error.response.data.data && error.response.data.data.errors ) {
                    emailInput.closest( '[data-module-validate]' ).dispatchEvent( new CustomEvent( 'formError', { detail : error.response.data.data } ) );
                    Emitter.emit('scroll-to', { el: emailInput ,container: this.el.closest('[data-module-scrollbar]') });
                }
            });
        }
    }

    onFormError( args ){
        if( args.el === this.form ){

        } else{

        }
    }
}
