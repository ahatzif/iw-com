import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'radio': 'filterClick', } };

        this.currentActive = this.el.dataset.currentActive;        
    }

    init() {}

    filterClick(e) {
        
        e.currentTarget.classList.toggle( 'active' );

        this.currentActive = this.currentActive == '0' ? '1' : '0';

        this.el.querySelector( `[data-radio-id="${this.currentActive}"]`).click();

    }
    destroy(){}
}
