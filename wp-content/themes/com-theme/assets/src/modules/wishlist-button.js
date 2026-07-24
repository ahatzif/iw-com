import { module } from 'modujs';
import axios from 'axios';
export default class extends module {
    constructor(m) {
        super(m);

        let formData = new FormData();
        formData.append( 'action', 'toggle_wishlist' );
        formData.append( 'product_id', this.el.dataset.productId );


        this.el.addEventListener( 'click', e => {
            this.el.classList.add( 'animate-heart' );
            axios.post(THEME_OBJ.ajaxURL, formData ).then( response => {
                this.el.classList.toggle( 'active' );
                this.el.classList.remove( 'animate-heart' );
                if ( response.data?.data?.action === 'removed' ) {
                    window.dispatchEvent( new CustomEvent( 'iw:wishlist-updated', { detail: { productId: this.el.dataset.productId } } ) );
                }
            });
        });
    }
}
