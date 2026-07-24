import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";
export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'scroll-to': 'scroll', } };
        this.forgotPasswordForm = this.$( 'forgot-password' )[0];
        this.resetPasswordForm = this.$( 'reset-password' )[0];
        Emitter.on( 'form-success', this.onForgotPasswordSuccess.bind( this ) )
        Emitter.on( 'modal-closed', this.onModalClosed.bind( this ) )

    }

    onForgotPasswordSuccess( args ){
        let form = args.el;
        if( form === this.forgotPasswordForm ){
            let login = this.resetPasswordForm.querySelector( '[name="login"]' );
            if( login ){
                login.value = this.forgotPasswordForm.querySelector( '[name="user_email"]' ).value;
            }
            this.el.classList.add( 'reset-password' );
        } else if( form === this.resetPasswordForm ){
            this.el.classList.remove( 'forgot-password', 'reset-password' );
            this.el.classList.add( 'reset-password-success' );
            const cleanUrl = window.location.origin + window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
        }
    }

    onModalClosed( modal ){
        if( this.el.closest( '[data-module-modal]') === modal ){
            setTimeout( () => {

            })
            this.el.classList.remove( 'forgot-password', 'reset-password', 'reset-password-success' );
        }
    }
}
