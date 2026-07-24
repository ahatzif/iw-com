import { module } from 'modujs';
import { infotext } from "../../../../../../wp-includes/js/codemirror/csslint.js";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'button': 'buttonClick', } };
        this.parameter = this.el.dataset.parameter;
        this.callArgs = [];
        if( this.el.dataset.call ){
            this.callArgs = this.el.dataset.call.split( ',' );
        }

    }
    buttonClick( e ){
        this.active = this.el.querySelector( '.active');
        this.active.classList.remove( 'active' );
        e.currentTarget.classList.add( 'active' );
        this.active = this.el.querySelector( '.active');
        let url = new URL(window.location);
        url.searchParams.set(this.parameter, this.active.dataset.value );
        window.history.replaceState({}, '', url);
        if( this.callArgs.length ){
            this.call( this.callArgs[0], this.callArgs[1], this.callArgs[2] )
        }
    }
}
