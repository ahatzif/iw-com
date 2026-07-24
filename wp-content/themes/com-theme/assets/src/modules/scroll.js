import { module } from 'modujs';
import Lenis from '@studio-freight/lenis'
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import LazyLoad from 'vanilla-lazyload';
gsap.registerPlugin(ScrollTrigger);

import Ukiyo from "ukiyojs";


export default class extends module {
    constructor(m) {
        super(m);
        this.velocity = 0;
        this.listeners = [];

        this.addScrollListener = module => {
            this.listeners.push( module )
        }

        this.removeScrollListener = module => {
            this.listeners = this.listeners.filter( listener => listener !== module );
        }
    }
    init() {
        this.lenis = new Lenis( { wrapper: this.el, content: this.$( 'content' )[0], smoothTouch: true, syncTouch: true });
        this.lenis.on('scroll', args => {

            document.body.classList.toggle( 'direction-down', args.direction > 0 );

            ScrollTrigger.update();
            this.progress = args.progress;
            this.y = args.animatedScroll;
            this.listeners.forEach( l => l.onScroll( this.y ));
        });

        this.parallaxInstances = [];
        this.addParallaxImages( document.querySelectorAll("[data-parallax]") );

        const scrollFn = (time) => {
            this.lenis.raf(time);
            this.parallaxInstances.forEach( instance => instance.animate() );
            requestAnimationFrame(scrollFn);
        };
        requestAnimationFrame(scrollFn);
        // LAZY LOAD
        this.lazy = new LazyLoad({ elements_selector : "[data-lazy]", container: this.el });
        // PARALLAX
        this.appendModalsToBody();
    }

    appendModalsToBody(){
        this.modals = [...this.el.querySelectorAll( '[data-module-modal]' )];
        this.modals.forEach( modal => { document.body.appendChild( modal ); });
    }

    update(){
        ScrollTrigger.update();
        this.lenis.dimensions.onContentResize();
    }

    start(){ this.lenis.start(); }

    stop(){ this.lenis.stop(); }

    updateLazy(){ this.lazy.update(); }

    scrollTo( args ){

        this.lenis.scrollTo( args.target, args.options || {} ); }

    addParallaxImages( images ) {
        this.parallaxInstances.push( new Ukiyo( images, { scale: 1.2, willChange: true, externalRAF: true, wrapperClass: "ukiyo-wrapper" } ) );
    }

    destroy(){
        this.lenis.destroy();
        this.parallaxInstances.forEach(instance => instance.destroy());
        this.modals.forEach( modal => {
            modal.remove()
        } );
    }
}
