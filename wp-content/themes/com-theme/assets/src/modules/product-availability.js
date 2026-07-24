import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'btn': 'btnOnClick' } };
    }

    btnOnClick(){
        let modal = document.querySelector( '[data-module-modal="product-availability-notification"]' );
            if( modal ){
                this.call( 'showModal', false, 'Modal' );
                let productIdInput = modal.querySelector( 'input[name="product_id"]' );
                let variationIdInput = modal.querySelector( 'input[name="variation_id"]' );
                if( productIdInput && productIdInput ){
                    productIdInput.value = this.productId;
                    variationIdInput.value = this.variation_id;
                }
            }
    }

}
