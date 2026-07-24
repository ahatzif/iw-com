import { module } from 'modujs';
import {isMobile} from "mobile-device-detect";



export default class extends module {
    constructor(m) {
        super(m);
        if( isMobile ) return;
        document.addEventListener( 'mousemove', this.mouseMove.bind( this ) );
        this.mouse = { x: 0, y: 0};
        this.pos = { x: 0, y: 0};

        this.hoverScale = 1;
        this.scale = 1;

        this.speed = 0.3;
        this.rafBind = this.raf.bind( this );

        this.offset = document.body.getBoundingClientRect().top;

        window.requestAnimationFrame( this.rafBind );

        this.addHoverElements( document );
    }

    addHoverElements( container ){
        [... container.querySelectorAll( 'a,.cursor-pointer' ) ].forEach( el => {
            el.addEventListener( 'mouseenter', () => this.on() );
            el.addEventListener( 'mouseleave', () => this.off() );
        });
    }

    mouseMove( e ){
        this.mouse.x = e.clientX;
        this.mouse.y = e.clientY - this.offset;
    }

    on(){
        this.hoverScale = 2;
    }

    off(){
        this.hoverScale = 1;
    }

    raf(){
        this.pos.x += ( this.mouse.x - this.pos.x ) * this.speed;
        this.pos.y += ( this.mouse.y - this.pos.y ) * this.speed;
        this.scale += ( this.hoverScale - this.scale ) * this.speed;

        this.el.style.transform = `translate3d(-50%,-50%,0) translate3d(${this.pos.x}px,${this.pos.y}px,0) scale(${this.scale})`;
        window.requestAnimationFrame( this.rafBind );
    }


}
