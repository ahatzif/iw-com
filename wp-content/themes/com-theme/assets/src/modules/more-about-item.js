export default class extends module {
    constructor(m) {
        super(m);
        this.el.addEventListener( 'click', e => {
            document.documentElement.classList.add( 'scroll-item' );
        });
    }
}

import { module } from 'modujs';
