import { module } from 'modujs';
const { slideToggle } = window.domSlider;
export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'toggle': 'toggleGroup','accordion-title': 'dropdownGroup' } };
    }
    toggleGroup( e ){
        this.el.classList.toggle( 'active' );
        slideToggle({ element: this.$('content')[0], duration: 300 });
        [...this.el.querySelectorAll( '.field.error' ) ].forEach( el => el.classList.remove( 'error' ) );
    }
    dropdownGroup(){
        this.el.classList.toggle( 'opened' );
        slideToggle({ element: this.$('accordion-content')[0], duration: 300 });
    }
}
