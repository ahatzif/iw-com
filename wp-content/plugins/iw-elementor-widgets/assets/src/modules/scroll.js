import { module } from 'modujs';
import LazyLoad from 'vanilla-lazyload';


export default class extends module {
    constructor(m) {
        super(m);
    }
    init() {
        this.lazy = new LazyLoad({ elements_selector : "[data-lazy]", container: this.el });
        this.header = document.querySelector( 'header' );
        window.addEventListener( 'scroll', e => {
            this.header.classList.toggle( 'scrolled', window.scrollY > 0 );
        });
    }
    destroy(){

    }
}
