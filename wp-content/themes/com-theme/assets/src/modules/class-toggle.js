import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'button': 'toggle'  } };
    }

    toggle( e ){
        let btn = e.currentTarget;
        this.el.classList.toggle( btn.dataset.class );
    }
}
