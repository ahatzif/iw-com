import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'thumb': 'thumbClick', 'zoom-in' : 'zoomIn',  'zoom-out' : 'zoomOut', 'expand' : 'expand'} };
        this.swiperName = this.el.dataset.swiperName

    }
    thumbClick( ev ){
        this.call( 'setSlide', ev.currentTarget.dataset.index, 'Swiper', this.swiperName )
    }

    zoomIn(){
        this.call( 'zoomIn', false, 'Swiper', this.swiperName )
    }

    zoomOut(){
        this.call( 'zoomOut', false, 'Swiper', this.swiperName )
    }


    expand(){
        this.el.classList.toggle( 'expanded' );
    }

}
