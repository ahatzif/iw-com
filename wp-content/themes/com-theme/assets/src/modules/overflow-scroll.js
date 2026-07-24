import { module } from 'modujs';
import { isMobile } from 'mobile-device-detect';

export default class extends module {

    constructor(m) {
        super(m);
        this.touchStartBind = this.onTouchStart.bind( this );
        this.touchMoveBind =  this.onTouchMove.bind( this );
        this.touchEndBind = this.onTouchEnd.bind(this);
        this.addEvents();
    }

    onTouchStart( ev ){
        this.touchStartEv = ev.touches[0];
        this.el.addEventListener( 'touchmove', this.touchMoveBind , { passive: true });

    }

    onTouchMove( ev ){
        this.touchMoveEv = ev.touches[0];
        this.el.removeEventListener( 'touchmove', this.touchMoveBind );
        let dx = Math.abs( this.touchMoveEv.pageX - this.touchStartEv.pageX );
        let dy = Math.abs( this.touchMoveEv.pageY - this.touchStartEv.pageY );
        if( dx > dy ){
            this.call( 'stop', false, 'Scroll' );
            this.el.addEventListener( 'touchend', this.touchEndBind , { passive: true });
            this.el.addEventListener( 'touchcancel', this.touchEndBind , { passive: true });
        }
    }

    onTouchEnd( ev ){
        this.call( 'start', false, 'Scroll' );
        this.el.removeEventListener( 'touchend', this.touchEndBind , { passive: true });
        this.el.removeEventListener( 'touchcancel', this.touchEndBind , { passive: true });
    }

    addEvents() {
        this.el.addEventListener( 'touchstart', this.touchStartBind , { passive: true });
    }

}
