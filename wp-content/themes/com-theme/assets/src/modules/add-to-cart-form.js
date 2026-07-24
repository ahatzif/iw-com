import { module } from 'modujs';
import axios from "axios";

export default class extends module {
    constructor(m) {
        super(m);
        this.form = this.el;
        this.events = { click: { 'hide-error': 'hideError' } };
        this.form.addEventListener( 'submit', this.onSubmit.bind( this ) );
        this.message = this.$( 'message' );
        if( this.message ){
            this.message = this.message[0];
        }
        this.parentContainer = this.el.closest( '.group[data-add-to-cart-form-container]');
        this.containterMessage = this.parentContainer?.querySelector( '[data-add-to-cart-form-container-message]' );

    }

    hideError(){
        this.form.classList.remove( 'has-error' );
    }

    onSubmit( e){
        e.preventDefault();
        let attributes = {};
        this.form.querySelectorAll('select[name^="attribute_"]').forEach( input => {
            if (input.value) {
                attributes[ input.getAttribute( 'name' ) ] = input.value;
            }
        } );
        this.form.querySelectorAll('input[type="radio"][name^="attribute_"]:checked').forEach( input =>  attributes[ input.getAttribute( 'name' ) ] = input.value );
        this.form.querySelectorAll('[data-product-color-variation="product-variation"][data-attr]').forEach(group => {
            const activeOption = group.querySelector('[data-product-color-variation="option"].active');
            if (activeOption?.dataset.value) {
                attributes[ 'attribute_' + group.dataset.attr ] = activeOption.dataset.value;
            }
        });
        if( this.parentContainer ) {
            this.parentContainer.classList.remove( 'has-error' );
        }
        this.form.classList.add( 'loading' );
        this.form.classList.remove( 'has-error' );
        let quantityEl = this.form.querySelector('input[name=quantity]');

        axios.post(THEME_OBJ.ajaxURL, new URLSearchParams(  {
            action: 'iw_cart_add_to_cart',
            product_id: this.form.dataset.product_id,
            variation_id: this.form.querySelector('input[name=variation_id]').value,
            quantity: quantityEl ? quantityEl.value : 1,
            attributes: JSON.stringify(attributes)
        } )).then( response => {
            this.form.classList.remove( 'loading' );
            let responseData = response.data;
            if( responseData.success ) {
                this.call( 'show', false, 'CartButton', 'main' );
                this.call( 'update', responseData.data.cart, 'Cart' );
                this.call( 'hideModal', false, 'Modal' );
            } else if( responseData.data?.message) {
                if( this.message ) {
                    this.form.classList.add( 'has-error' );
                    this.message.innerHTML = responseData.data.message;
                    setTimeout( () => this.form.classList.remove( 'has-error' ), 7000 )
                } else if( this.parentContainer && this.containterMessage) {
                    this.parentContainer.classList.add( 'has-error' );
                    this.containterMessage.innerHTML = responseData.data.message;
                    setTimeout( () => this.parentContainer.classList.remove( 'has-error' ), 7000 )
                }
            }
        }).catch( e => {
            this.form.classList.remove( 'loading' );

            if( e.response.data?.data?.message ){
                let message = e.response.data?.data?.message;
                if( message && this.containterMessage ){
                    this.containterMessage.innerHTML = message;
                }
                if( this.parentContainer ) {
                    this.parentContainer.classList.add( 'has-error' );
                }
            }

        } );
    }
}
