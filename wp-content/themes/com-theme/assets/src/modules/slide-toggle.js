import { module } from 'modujs';
const { slideToggle } = window.domSlider;
export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'toggle': 'toggle', 'sub-accordion': 'subAccordion' } };
        this.content = this.$('content')[0];
    }

    subAccordion(e) {
        e.currentTarget.classList.toggle('showed');
    }

    toggle() {
        this.el.classList.toggle('opened');

        slideToggle({ element: this.content, slideSpeed: 420, easing: 'cubic-bezier(0.22, 1, 0.36, 1)'});
    }

}
