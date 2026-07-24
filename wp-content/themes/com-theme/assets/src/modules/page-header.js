import { module } from 'modujs';


export default class extends module {
    constructor(m) {
        super(m);
    }
    init(){
        document.body.classList.remove( 'has-scrolled' );
        this.barbaContainer = document.querySelector('[data-barba="container"]');

        if (this.modules.Scroll && this.modules.Scroll.main) {
            this.modules.Scroll.main.addScrollListener( this );
        } else {
            this.onWindowScroll = () => this.onScroll(window.scrollY);
            window.addEventListener('scroll', this.onWindowScroll, { passive: true });
        }

        this.onScroll(0);

    }
    onScroll( y ) {
        if (this.barbaContainer && !this.barbaContainer.classList.contains('page-404')) {
            document.body.classList.toggle( 'has-scrolled', y > 10 );
        }
    }

    destroy() {
        if (this.onWindowScroll) {
            window.removeEventListener('scroll', this.onWindowScroll);
        }
    }
}
