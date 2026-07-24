import { module } from 'modujs';
import Swiper  from "swiper";
import { Navigation, EffectFade, Autoplay, FreeMode, Pagination } from 'swiper/modules';

export default class extends module {
    constructor(m) {
        super(m);

        new Swiper( this.$( 'swiper' )[0] , {
            modules: [Navigation,EffectFade,Autoplay],
            loop: true,
            speed: 1000,
            navigation: {
                prevEl: this.$( 'prev-button' )[0],
                nextEl: this.$( 'next-button' )[0],
            },
        });
    }


    destroy(){
    }
}
