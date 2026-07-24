import { module } from 'modujs';
const { slideToggle, slideDown, slideUp} = window.domSlider;
export default class extends module {
    constructor(m) {
        super(m);
        this.content = this.$('content');
        this.events = { click: { 'toggle': 'toggleClick' } };

    }
    toggleClick(e) {
        let clickedEl = e.currentTarget;
        if( clickedEl.classList.contains( 'active' ) ) return;
        let active = this.el.querySelector( '[data-radio-accordion="toggle"].active' );
        this.toggleGroup( active, false );
        this.toggleGroup( clickedEl, true );
    }

    toggleGroup( el, active ){
        if( ! el ) return;
        el.classList.toggle( 'active', active );
        let content = el.closest( '.group').querySelector( '[data-radio-accordion="content"]');
        if( content ){
            active ? slideDown({ element: content, duration: 300 }) : slideUp({ element: content, duration: 300 });
        }
    }
}
