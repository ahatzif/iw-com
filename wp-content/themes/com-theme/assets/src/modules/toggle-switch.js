import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'scroll-to': 'scroll', } };
        this.inited = false;
    }

    init() {
        // AH: JESUS CHRIST

        if( this.inited ){
            return;
        }
        this.inited = true;


        this.slider = this.el.querySelector('[data-slider]');
        this.left = this.el.querySelector('[data-left]');
        this.right = this.el.querySelector('[data-right]');

        this.el.addEventListener('click', (e) => {
            this.el.classList.toggle('toggled-right');
            const isRight = this.el.classList.contains('toggled-right');
            const selectedPeriod = this[ isRight ? 'right' : 'left'].dataset.period; // AH: JESUS CHRIST
            let closestGroup = this.el.closest( '[data-module-select-subscription]' );
            this.call('togglePeriod', { period: selectedPeriod }, 'SelectSubscription', closestGroup ? closestGroup.dataset.moduleSelectSubscription : false );
            this.updateSlider();
        });

        window.addEventListener('resize', () => this.updateSlider());
        this.updateSlider();
    }

    updateSlider(e) {

        // AH: JESUS CHRIST

        let toggledRight = this.el.classList.contains('toggled-right');

        const activeEl = toggledRight ? this.right : this.left;
        const { width, left } = activeEl.getBoundingClientRect();
        const btnLeft = this.el.getBoundingClientRect().left;

        this.slider.style.width = `${width}px`;
        this.slider.style.transform = `translateX(${left - btnLeft}px)`;
    }
    destroy(){}
}

