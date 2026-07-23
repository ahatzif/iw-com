import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";

const Scrollbar = require("smooth-scrollbar");
Scrollbar.detachStyle();

export default class extends module {
    constructor(m) {
        super(m);
    }

    init() {
        this.scrollBar = Scrollbar.init( this.el, { damping: 0.05, alwaysShowTracks: true });
        ['mouseenter', 'touchstart'].forEach(evt => this.el.addEventListener(evt, this.stopScrollbar.bind(this), false));
        ['mouseleave', 'touchend'].forEach(evt => this.el.addEventListener(evt, this.startScrollBar.bind(this), false));
        Emitter.on( 'scroll-to', ( args ) => {
            if( args.container === this.el && ! this.scrollBar.isVisible( args.el )) {
                let offset = { offsetTop: this.el.getBoundingClientRect().height  - args.el.getBoundingClientRect().height };
                this.scrollBar.scrollIntoView( args.el, offset );
            }
        });
    }

    startScrollBar( e ){
        this.call( 'start', false, 'Scroll' );
    }

    stopScrollbar( e ){
        this.scrollBar.limit.y > 0  && this.call( 'stop', false, 'Scroll' );
    }

    destroy(){}
}
