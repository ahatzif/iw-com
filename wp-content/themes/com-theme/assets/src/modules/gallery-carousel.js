import { module } from 'modujs';
import Swiper from "swiper";
import { Navigation, Parallax, EffectFade, Autoplay, FreeMode, Pagination, Zoom, Thumbs } from 'swiper/modules';
import { isMobile } from 'mobile-device-detect';
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);
        let options = this.el.dataset.options ? JSON.parse(decodeURIComponent(this.el.dataset.options)) : {};
        this.sliderMoveBind = this.sliderMove.bind(this);

        // this.thumbSwiper = new Swiper(this.$('thumbs-swiper')[0], {
        //     slidesPerView: 'auto',
        //     freeMode: true,
        //     speed: 800,
        //     wrapperClass: 'wrapper',
        //     slideClass: 'slide',
        // });

        // this.photosSwiper = new Swiper(this.$('photos-swiper')[0], {
        //     modules: [Parallax, Thumbs, Navigation],
        //     slidesPerView: 1,
        //     freeMode: true,
        //     speed: 800,
        //     parallax: true,
        //     wrapperClass: 'wrapper',
        //     slideClass: 'slide',
        //     thumbs: {
        //         swiper: this.thumbSwiper,
        //         slideThumbActiveClass: 'opacity-50'
        //     },
        //     navigation: {
        //         nextEl: this.$('next')[0],
        //         prevEl: this.$('prev')[0],
        //         disabledClass: 'opacity-50'
        //     },
        // });

    }
    sliderMove(e) {
        if (e.progress > 0.05) {
            this.el.classList.add('swiped');
            this.swiper.off('sliderMove', this.sliderMoveBind);
        }
    }
    destroy(){}
}
