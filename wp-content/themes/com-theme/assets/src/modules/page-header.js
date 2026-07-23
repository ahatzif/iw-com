import { module } from 'modujs';


export default class extends module {
    constructor(m) {
        super(m);
    }
    init(){
        document.body.classList.remove( 'has-scrolled' );
        this.modules.Scroll.main.addScrollListener( this );
    }
    onScroll( y ) {
        document.body.classList.toggle( 'has-scrolled', y > 10 );
    }
}
