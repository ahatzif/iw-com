import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";


export default class extends module {
    constructor(m) {
        super(m);

        Emitter.on('click-outside', () => {
            this.el.classList.remove( 'active' )
        } );
        this.el.addEventListener( 'click', e => {
            if( ! e.target.closest( 'a' ) ) e.stopPropagation();
        });

        [...this.$( 'button' )].forEach( btn => btn.addEventListener( 'click', this.toggleMenu.bind( this )));

    }

    init(){
        this.isOpen = false;
        this.call( 'addScrollListener', this, 'Scroll' );
    }

    onScroll( e ){
        if( this.isOpen ){
            this.isOpen = ! this.isOpen;
            this.el.classList.toggle( 'active' );
        }
    }

    toggleMenu(){
        if( ! document.body.classList.contains( 'logged-in' ) ) return;
        this.isOpen = ! this.isOpen ;
        this.el.classList.toggle( 'active' );
    }

}
