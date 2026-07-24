import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
    }
    init() {
        let html = document.documentElement;
        if( html.classList.contains( 'scroll-item' ) ) {
            html.classList.remove( 'scroll-item' );
            this.call( 'scrollTo', { target: 200 }, 'Scroll' );
        }

    }

}
