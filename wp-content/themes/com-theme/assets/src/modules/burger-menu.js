import { module } from 'modujs';
import { gsap } from "gsap";

export default class extends module {
    constructor(m) {
        super(m);
        this.tl = gsap.timeline({ repeat: 0  });
        gsap.set( this.el, { clipPath: "polygon(0% 0%, 100vw 0%, 100vw 0%, 0% 0%)" } );
        this.el.classList.add( 'ready' );
        this.el.addEventListener( 'click', e => {
            if( e.metaKey || e.target.tagName !== 'A') return;
            if( e.ctrlKey ) { }
            this.toggle( e );
        } );
        document.addEventListener('keyup', e => { if ( e.code === 'Escape') this.isOpen && this.toggle() } );
    }
    toggle( e ){
        document.body.classList.toggle( 'burger-open' );
        document.body.classList.contains( 'burger-open' ) ? this.open() : this.close();
    }
    open(){
        this.isOpen = true;
        if ( this.tl ) this.tl.kill();
        this.tl = gsap.timeline();
        this.tl.to( this.el, { duration: 0.7, ease: "expo.out", clipPath: "polygon(0% 0%, 100vw 0%, 100vw 100%, 0% 100%)" })
    }
    close(){
        this.isOpen = false;
        if ( this.tl ) this.tl.kill();
        this.tl = gsap.timeline();
        this.tl.to( this.el, {duration: 0.87, ease: "expo.out", clipPath: "polygon(0% 0%, 100vw 0%, 100vw 0%, 0% 0%)" })
    }
}
