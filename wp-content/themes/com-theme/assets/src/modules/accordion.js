import { module } from 'modujs';
require( 'dom-slider' );
const {slideUp, slideToggle} = window.domSlider;

export default class extends module {
    constructor(m) {
        super(m);
        this.opened = this.el.querySelector( '[data-accordion="target"]' );
        this.isFirstOpen = false;
        this.el.addEventListener( 'click', e => {
           if(e.target.dataset.accordion === 'toggle' || e.target.closest( '[data-accordion="toggle"]' ) ){
               let parent = e.target.closest( '[data-accordion="parent"]' );
               let target = parent.querySelector( '[data-accordion="target"]' );
               if( target === this.opened){
                   this.isFirstOpen = false;
               }
               if( target !== this.opened && this.isFirstOpen){
                   slideUp( { element : this.opened, duration : 300 } );
                   this.isFirstOpen = false;
                   let cross = this.opened.closest( '[data-accordion="parent"]' ).querySelector( '[data-class-toggle]' );
                   this.toggleClassCross(cross);
               }
               slideToggle( { element : target, duration : 300 } );
               this.opened = target;
               let cross = parent.querySelector( '[data-class-toggle]' );
               this.toggleClassCross(cross);
               setTimeout( () => {
                   this.call( 'update', false, 'Scroll' );

               }, 400 );
           }
        });
    }
    toggleClassCross(cross){
        [...cross.dataset.classToggle.split( ' ')].forEach( className => {
            cross.classList.toggle( className );
        });
    }
    init() {}
    destroy(){}
}
