import { module } from 'modujs';
import axios from 'axios';
require( 'dom-slider' );
const { slideUp } = window.domSlider;

export default class extends module {
    constructor(m) {
        super(m);


        this.$( 'change' ).forEach( div => div.addEventListener( 'click', this.change.bind( this ) ) );
        this.input = this.$('input')[0];
        this.min = parseInt(this.input.min) || 0;
        this.max = parseInt(this.input.max) || Infinity;
        this.value = this.input.value;
        this.decBtn = this.el.querySelector('[data-direction="-1"]');
        this.incBtn = this.el.querySelector('[data-direction="1"]');
        this.preview = this.$( 'preview' )[0];

        this.onResizeBind = this.onResize.bind( this );
        window.addEventListener( 'resize', this.onResizeBind );

        this.toggleButtons();
    }

    setPreviewTrans(){
        let trans = ( this.value-this.min ) *  this.preview.parentNode.offsetHeight;
        this.preview.style.transform = `translateY(-${trans}px)`;
    }
    onResize(){
        this.setPreviewTrans();
    }

    change(e) {
        const direction = parseInt(e.currentTarget.dataset.direction, 10) || 0;
        let value = parseInt(this.input.value) || this.min;
        this.value = value + direction;
        if ( this.value >= this.min && this.value <= this.max) {
            this.input.value = this.value;
            this.input.dispatchEvent(new Event('change', { bubbles: true }));
            this.input.dispatchEvent(new Event('input', { bubbles: true }));
            this.setPreviewTrans()
            this.toggleButtons();
        }
    }
    toggleButtons() {
        const value = parseInt(this.input.value) || this.min;
        if (this.decBtn) this.decBtn.classList.toggle('disabled', value <= this.min);
        if (this.incBtn) this.incBtn.classList.toggle('disabled', value >= this.max);
    }

    destroy(){
        window.removeEventListener( 'resize', this.onResizeBind );
    }

}
