import { module } from 'modujs';
import axios from "axios";

export default class extends module {
    constructor(m) {
        super(m);

        let shippingDetails = this.el.closest( 'form' ).querySelector( '[data-checkout-form="shipping-details"]' );
        this.el.querySelectorAll( 'input[type="radio"]').forEach(input => {
            input.addEventListener('change', (e) => {
                this.method_id = e.target.value;
                if( shippingDetails ){
                    shippingDetails.classList.toggle( 'hidden', this.el.dataset.localPickupMethodId === this.method_id );
                }
                this.onChange();
            });
        });
    }
    onChange(){
        this.el.classList.add( 'loading' );

        this.call( 'shippingLoading', false , 'Cart' );

        axios.post(THEME_OBJ.ajaxURL, new URLSearchParams({ action: 'iw_cart_update_shipping_method', method_id: this.method_id })).then(response => {
            this.el.classList.remove( 'loading' );
            let responseData = response.data;
            if( responseData.success ) {
                this.call( 'update', responseData.data.cart, 'Cart' );
            }
        });
    }
}
