import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'close': 'closeTrash', 'open' : 'openTrash' } };
        this.isOpen = false;
    }
    openTrash(){
        this.isOpen = true;
        this.el.classList.add( 'trash-open' );
    }

    closeTrash(){
        this.isOpen = false;
        this.el.classList.remove( 'trash-open' );
    }
}
