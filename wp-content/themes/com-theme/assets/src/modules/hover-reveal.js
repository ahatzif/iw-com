/*
 * HOVER REVEAL EFFECT
 * Effect for a menu where images appear with an animation on each item.
 *
 *
 */

import { module } from 'modujs';
import Emitter from 'tiny-emitter/instance';

export default class extends module {
    constructor(m) {
        super(m);
        this.items = [...this.$( 'item' )];
        this.preview = this.$( 'preview' )[0];
        this.mouse = {x:0,y:0};

        this.initBind = this.init.bind( this );
        this.onScrollBind = this.onScroll.bind( this );
        this.rafBind = this.raf.bind( this );
        this.el.addEventListener( 'mouseenter', this.initBind );
    }
    init() {

        this.modules.Scroll.main.addScrollListener( this );
        this.el.removeEventListener( 'mouseenter', this.initBind );
        this.el.addEventListener( 'mousemove', this.onMouseMove.bind( this ) );
        this.render();
        this.items.forEach( item => {
            let img = document.createElement( 'img' );
            img.src = item.dataset.src;
            item.addEventListener( 'mouseenter', this.itemEnter.bind( this ) );
            item.addEventListener( 'mouseleave', this.itemLeave.bind( this ) );
        } );
    }

    render(){
        this.rafID = window.requestAnimationFrame( this.rafBind );
    }

    onScroll( y ){
        this.y = y;
    }

    onMouseMove( e ){
        this.mouse = { x: e.clientX, y: e.clientY };
    }
    itemEnter( e ){
        let target = e.target;
        target.classList.add( 'z-2' );
        this.preview.style.backgroundImage = `url(${target.dataset.src})`;
        this.preview.style.opacity = 0.9;
    }
    itemLeave( e ){
        let target = e.target;
        this.preview.style.opacity = 0;
        target.classList.remove( 'z-2' );
    }
    raf(){
        this.preview.style.transform = `translate(${this.mouse.x}px,${this.mouse.y +  this.y}px) translate(-50%,-50%)`;
        this.render();
    }

    destroy(){
        Emitter.off( 'page-scroll', this.onScrollBind );
        window.cancelAnimationFrame( this.rafID );
    }
}
