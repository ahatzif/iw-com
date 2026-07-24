import { module } from 'modujs';
import axios from "axios";
import {info} from "autoprefixer";
require( 'dom-slider' );
const {slideUp, slideToggle} = window.domSlider;

export default class extends module {
    constructor(m) {
        super(m);
        if( this.el.classList.contains( 'initted' ) ) return;

        this.el.classList.add( 'initted' );

        this.events = { click: { 'set-as-default': 'setAsDefault', 'delete': 'delete' } };
        this.parentContainer = this.el.closest( '.payment-methods' );
        this.id = this.el.dataset.id;
        this.nonce = this.el.dataset.nonce;

        this.confirmDelete = this.el.querySelector( '[data-payment-method="delete-confirm"]');
        if( this.confirmDelete ){
            this.confirmDelete.addEventListener( 'click', e => {
                e.preventDefault()
                this.el.classList.add( 'cursor-wait' );
                let el = this.el.querySelector( '[data-module-open-modal]' );
                el.classList.add( 'loading' );
                this.parentContainer.classList.add( 'is-loading' );
                axios.post(THEME_OBJ.ajaxURL, new URLSearchParams({ action: 'iw_delete_payment_method', 'id': this.id, 'nonce' : this.nonce  })).then(response => {
                    let responseData = response.data;
                    el.classList.remove( 'loading' );
                    this.el.classList.remove( 'cursor-wait' );
                    this.parentContainer.classList.remove( 'is-loading' );
                    if( responseData.success ){
                        slideUp( { element : this.el, duration : 300 } );
                    }
                }).catch( e => {
                    el.classList.remove( 'loading' );
                    this.el.classList.remove( 'cursor-wait' );
                    this.parentContainer.classList.remove( 'is-loading' );
                } );
            } );
        }

    }

    setAsDefault( e ){
        e.preventDefault()
        this.el.classList.add( 'cursor-wait' );
        let el = e.currentTarget;
        el.classList.add( 'loading' );
        this.parentContainer.classList.add( 'is-loading' );
        axios.post(THEME_OBJ.ajaxURL, new URLSearchParams({ action: 'iw_set_as_default_payment_method', 'id': this.id, 'nonce' : this.nonce  })).then(response => {
            let responseData = response.data;
            el.classList.remove( 'loading' );
            this.el.classList.remove( 'cursor-wait' );
            this.parentContainer.classList.remove( 'is-loading' );
            if( responseData.success ){
                document.querySelector( '.is-default[data-module-payment-method]').classList.remove( 'is-default' );
                this.el.classList.add( 'is-default' );

            }
        }).catch( e => {
            el.classList.remove( 'loading' );
            this.el.classList.remove( 'cursor-wait' );
            this.parentContainer.classList.remove( 'is-loading' );
        } );
    }



}
