import { module } from 'modujs';
import {info} from "autoprefixer";
import axios from "axios";

export default class extends module {
    constructor(m) {
        super(m);
        let input = this.el.querySelector( 'input[type="checkbox"]' );
        input.addEventListener( 'change', e => {
            this.el.closest( 'form' ).classList.add( 'loading' );
            let name = input.name;
            axios.post(THEME_OBJ.ajaxURL, new URLSearchParams({ action: this.el.dataset.action, nonce : this.el.dataset.nonce, [name]: input.checked ? '1' : '0' })).then(response => {
                this.el.closest( 'form' ).classList.remove( 'loading' );
                let responseData = response.data;
                if( responseData.success ) {
                    this.call( 'update', responseData.data.cart, 'Cart' );
                }
            });
        });

    }
    init() {}
    destroy(){}
}
