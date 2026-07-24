import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = {
            touchstart: { 'hover': 'onMouseEnter' },
            mouseenter: { 'hover': 'onMouseEnter' }
        };
        this.hoverElements = [...this.el.querySelectorAll('[data-do-not-miss-slider="hover"]')];
        this.images = [...this.el.querySelectorAll('[data-do-not-miss-slider="images"]')];
    }

    onMouseEnter(ev) {
        if( window.innerWidth < 768 ) return;
        const hoverEl = ev.currentTarget;
        const index = hoverEl.dataset.index;
        this.hoverElements.forEach(el => el.classList.remove('swiper-slide-active'));
        hoverEl.classList.add('swiper-slide-active');
        this.images.forEach(img => img.classList.remove('active'));
        const targetImage = this.images.find(img => img.dataset.index === index);
        if (targetImage) {
            targetImage.classList.add('active');
        }
    }

    slideChange(args){
        const targetImage = this.images.find(img => {
            return parseInt( img.dataset.index ) === args.activeIndex
        } );
        if (targetImage) {
            this.images.forEach(img => img.classList.remove('active'));
            targetImage.classList.add('active');
        }
    }
}