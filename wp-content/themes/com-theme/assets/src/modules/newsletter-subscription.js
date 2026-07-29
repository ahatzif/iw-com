import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";
import axios from "axios";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'reset': 'reset', 'unsubscribe' : 'unsubscribe' } };
        Emitter.on('form-success', this.onFormSuccess.bind(this));
        Emitter.on('modal-closed', this.onModalClosed.bind( this ) );
        this.modal = this.el.closest( '[data-module-modal]' );
        this.modalName = this.modal.dataset.moduleModal;
        this.form = this.el;
        this.errorElement = this.form.querySelector( '[data-form="error-message"]' );

    }

    onModalClosed( el ){

        if (el && el.contains(this.form)) {
            this.form.classList.remove('check-mail', 'error');
        }
    }


    onFormSuccess( args ){
        let response = args.response;
        let userStatus = response.data.data.user_status;
        if( userStatus !== 'subscribed' ){
            this.el.classList.add( 'check-mail' );
        } else {
            this.call( 'hideModal', false, 'Modal', this.modalName );
        }
    }

    reset(){
        this.call( 'hideModal', false, 'Modal', this.modalName );
        setTimeout( () => {
            this.el.classList.remove( 'check-mail' );
        }, 1000);
    }

    unsubscribe( e ){
        let btn = e.currentTarget;
        btn.classList.add( 'loading' );
        this.form.classList.remove( 'error' );



        const formData = new FormData();
        formData.append('action', btn.dataset.action );
        formData.append('nonce', btn.dataset.nonce);

        axios.post(THEME_OBJ.ajaxURL, formData, { withCredentials: true }).then(res => {
            btn.classList.remove( 'loading' );
            if (res.data.success) {
                this.form.classList.add( 'unsubscribed' );
                this.call( 'hideModal', false, 'Modal', this.modalName );
                setTimeout( () => { this.form.classList.remove( 'unsubscribed' ); }, 1000);
            } else {
                console.info(res.data);
                this.form.classList.add( 'error' );
                this.errorElement.textContent = this.el.dataset.errorMessage || '';
            }
        }).catch(err => {
            btn.classList.remove( 'loading' );
            this.form.classList.add( 'error' );
            this.errorElement.innerHTML = err.response.data.data.message;
        } );


    }

}
