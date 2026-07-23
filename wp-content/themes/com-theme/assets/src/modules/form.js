import { module } from 'modujs';
import axios from 'axios';

export default class extends module {
    constructor( m ) {
        super( m );

        this.elements = [ ...this.el.querySelectorAll( '[data-module-validate]' ) ];

        this.controls = [ ...this.el.querySelectorAll( '[data-form-control]' ) ];

        this.parentModal = this.el.closest( '[data-module-modal] ');

        this.el.addEventListener( 'clear', this.clear.bind( this ) );

        this.el.addEventListener( 'submit', this.onSubmit.bind( this ) );

        this.successMessage = this.$( 'success-message' );
        this.loadTurnstile();




        this.messages = this.$( 'messages' );
        if( this.el.dataset.modalId && this.messages.length ){
            this.messagesEl = this.$( 'messages' )[0];
            this.modal = document.querySelector( '[data-module-modal="' + this.el.dataset.modalId + '"]')
            if( this.modal ){
                this.modalContent = this.modal.querySelector( '[data-modal="content"]' );
                this.modalContent.appendChild( this.messagesEl );

            }
        }

    }

    loadTurnstile(){
        this.turnstileContainer = this.el.querySelector( '[data-form="turnstile"]' );
        if( ! this.turnstileContainer ) return;
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
        if( this.turnstileWidgetId ){
            turnstile.reset( this.turnstileWidgetId );
        } else {
            this.turnstileWidgetId = turnstile.render(this.turnstileContainer, { sitekey: this.turnstileContainer.dataset.siteKey, theme: 'light', callback: (token) => {} });
        }

    }


    onSubmit( e ){

        if( ! this.isValid() ) {
            e.preventDefault();
            this.scrollToFirstError();
            return;
        }

        if( this.el.dataset.ajax !== 'false'){
            e.preventDefault();
            this.el.classList.remove( 'success', 'error' )
            this.el.classList.add( 'loading' );
            axios.post( this.el.getAttribute( 'action' ), new FormData( this.el ) ).then( response => {
                this.el.classList.remove( 'loading' );
                response.data.success === true ? this.onSuccess( response ) : this.onError( response );
            } ).catch( error => {
                this.el.classList.remove( 'loading' );
                this.elements.forEach( element => element.dispatchEvent( new CustomEvent( 'formError', { detail : error.response.data.data } ) ) );
                this.scrollToFirstError();
            } );
        }
    }

    onSuccess( response ) {
        if( response.data.data.redirect ){
            window.location.href = response.data.data.redirect;
            return;
        }


        if( this.successMessage.length ) {
            this.successMessage[0].innerHTML = response.data.data.message;
        }


        this.el.classList.add( 'success' );
        this.call('scrollTo', { target : this.el.closest( 'section' ), options: { offset: -150 } },  'Scroll');
        this.el.reset();
        this.renderTurnstile();
        this.controls.forEach( element => element.dispatchEvent( new CustomEvent('form-reset') ) );

        if( this.el.dataset.resetOnSuccess ){
            setTimeout( () => this.el.classList.remove( 'success' ) , 4000 );
        }


        if( this.modal ){
            this.modal.classList.add( 'active' );
            this.messagesEl.classList.remove( 'error' );
            this.messagesEl.classList.add( 'success' );
        }

        setTimeout( () => {
            if( this.parentModal ){
                this.call( 'hideModal', false, 'Modal', this.parentModal.dataset.moduleModal );
            }
        }, 4000 ) ;





    }

    onError( response ){
        this.$( 'error-message' )[0].innerHTML = response.data.data.message;
        this.el.classList.add( 'error' );
        this.el.classList.add( 'error' );

        this.call('scrollTo', { target : this.el, options: { offset: -150 } },  'Scroll');


        if( this.modal ){
            this.call( 'showModal', false, 'Modal', this.el.dataset.modalId);
            this.messagesEl.classList.remove( 'success' );
            this.messagesEl.classList.add( 'error' );
        }
    }

    scrollToFirstError(){
        let firstError = this.el.querySelector( '[data-module-validate].error' );
        if( this.parentModal ){
            this.parentModal.scrollTop = firstError.offsetTop;
        } else {
            this.call('scrollTo', { target : firstError, options: { offset: -150 } },  'Scroll');
        }

    }

    isValid() {
        this.hasError = false;
        for ( let element of this.elements ) {
            let skipValidationGroup = element.closest( '[data-skip-validation-when-hidden]' );
            if( skipValidationGroup && ! skipValidationGroup.classList.contains( 'active' ) ){
                console.info('skiping');
                continue;
            }
            if( element.classList.contains( 'error' )  ){
                this.hasError = true;
            } else if ( ! this.modules.Validate[ element.dataset.moduleValidate ].validate() ) {
                this.hasError = true;
            }
        }

        return ! this.hasError;
    }

    clear(){
        this.el.classList.remove( 'success', 'error' );
        this.el.reset();
        this.elements.forEach( element => element.dispatchEvent( new CustomEvent( 'clear' ) ) );
    }

}
