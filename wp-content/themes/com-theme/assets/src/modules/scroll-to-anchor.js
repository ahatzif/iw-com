import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.el.addEventListener( 'click', e => {
            e.preventDefault();
            let offset = document.querySelector( 'header').offsetHeight;
            this.call( 'scrollTo',  {target : this.el.dataset.selector, options : { offset : -offset } }, 'Scroll' );
        })
    }
}
