import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";
export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'toggle': 'toggle', } };
        this.clickOutsideBind = this.clickOutside.bind( this );
        this.keydownBind = this.onKeydown.bind(this);
        Emitter.on('click-outside', this.clickOutsideBind );
        document.addEventListener('keydown', this.keydownBind);
    }

    clickOutside( ev ){
        if( ev.target === this.el || this.el.contains(ev.target) ){
            return ;
        }
        this.el.classList.remove( 'active' );
    }

    onKeydown(ev) {
        if (ev.key === 'Escape') {
            // simulate outside click
            this.clickOutsideBind({ target: document.body });
        }
    }

    toggle(){
        this.el.classList.toggle( 'active' );
    }

    destroy(){
        Emitter.off('click-outside', this.clickOutsideBind );
    }

}
