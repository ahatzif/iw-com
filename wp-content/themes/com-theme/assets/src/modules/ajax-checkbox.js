import { module } from 'modujs';
import axios from "axios";
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'input': 'onChange', } };
        this.payload = this.el.dataset.payload ? JSON.parse(this.el.dataset.payload) : {};
        this.input = this.$( 'input' )[0];
        this.container = this.el.closest( '.ajax-checkbox-container' );

    }
    onChange( e ){
        this.el.classList.add( 'loading' );
        const statePayload = this.input.checked ? (this.payload.on || {}) : (this.payload.off || {});
        const payload = { ...this.payload, ...statePayload };
        delete payload.on;
        delete payload.off;
        axios.post(THEME_OBJ.ajaxURL, new URLSearchParams(payload)).then(response => {
            let responseData = response.data;
            this.el.classList.remove( 'loading' );
            Emitter.emit( 'ajax-checkbox', payload );
            if( this.container ){
                this.container.classList.toggle( 'checked', this.input.checked );
            }
        });
    }
}
//WCSViewSubscription