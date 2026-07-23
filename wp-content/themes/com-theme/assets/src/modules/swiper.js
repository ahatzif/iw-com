import { module } from 'modujs';
import Swiper  from "swiper";
import { Navigation, EffectFade, Autoplay, FreeMode, Pagination } from 'swiper/modules';

import { isMobile } from 'mobile-device-detect';

export default class extends module {
    constructor(m) {
        super(m);

        this.swiperEl = this.$( 'swiper' )[0];
        this.touchStartBind = this.onTouchStart.bind( this );
        this.touchMoveBind =  this.onTouchMove.bind( this );
        this.touchEndBind = this.onTouchEnd.bind(this);
        this.addEvents();

        let options = this.el.dataset.options ? JSON.parse(decodeURIComponent( this.el.dataset.options )) : {};
        this.sliderMoveBind = this.sliderMove.bind( this );


        this.swiper = new Swiper( this.$( 'swiper' )[0], { ...{
                modules: [Navigation,EffectFade,Autoplay, FreeMode],
                freeMode: true,
                mousewheel: true,
                slidesPerView: "auto",
                wrapperClass: 'wrapper',
                slideClass: 'slide',
                navigation: {
                    hiddenClass : 'inactive',
                    disabledClass : 'inactive',
                    prevEl: this.el.querySelector( "[data-arrow-prev]" ),
                    nextEl: this.el.querySelector( "[data-arrow-next]" ),
                }
            }, ...options } );

        this.swiper.on( 'sliderMove',this.sliderMoveBind);

    }

    sliderMove(e ){
        if( e.progress > 0.05 ){
            this.el.classList.add( 'swiped' );
            this.swiper.off( 'sliderMove',this.sliderMoveBind);
        }

    }





    onTouchStart( ev ){
        this.touchStartEv = ev.touches[0];
        this.swiperEl.addEventListener( 'touchmove', this.touchMoveBind , { passive: true });

    }

    onTouchMove( ev ){
        this.touchMoveEv = ev.touches[0];
        this.swiperEl.removeEventListener( 'touchmove', this.touchMoveBind );
        let dx = Math.abs( this.touchMoveEv.pageX - this.touchStartEv.pageX );
        let dy = Math.abs( this.touchMoveEv.pageY - this.touchStartEv.pageY );
        if( dx > dy ){
            this.call( 'stop', false, 'Scroll' );
            this.swiperEl.addEventListener( 'touchend', this.touchEndBind , { passive: true });
            this.swiperEl.addEventListener( 'touchcancel', this.touchEndBind , { passive: true });
        }
    }

    onTouchEnd( ev ){
        this.call( 'start', false, 'Scroll' );
        this.swiperEl.removeEventListener( 'touchend', this.touchEndBind , { passive: true });
        this.swiperEl.removeEventListener( 'touchcancel', this.touchEndBind , { passive: true });
    }

    addEvents() {
        if( isMobile ){
            this.swiperEl.addEventListener( 'touchstart', this.touchStartBind , { passive: true });
        }
    }




}
