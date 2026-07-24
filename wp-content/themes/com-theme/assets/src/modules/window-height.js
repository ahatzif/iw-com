import { module } from 'modujs';

export default class extends module {
    constructor( m ) {
        super( m );
        if( this.getData( 'subtract') ){
            this.subtract = document.querySelector( this.getData( 'subtract') );
        }
    }

    init() {
        this.updateBind = this.update.bind( this );
        this.update();
        window.addEventListener( 'resize', this.updateBind );
    }

    update() {
        let subtract = this.subtract ? this.subtract.getBoundingClientRect().height : 0;
        this.el.style.height = ( this.el.dataset.h ? this.el.dataset.h : 100 ) * ( document.documentElement.offsetHeight - subtract ) / 100 + 'px';
        this.el.classList.add( 'ready' );
        this.call( 'update', false, 'Scroll' );
    }

    destroy() {
        window.removeEventListener( 'resize', this.updateBind );
    }
}
