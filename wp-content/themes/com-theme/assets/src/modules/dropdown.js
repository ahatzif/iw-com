import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'option': 'optionClick' } };
        this.selectedValue = this.$( 'selected' )[0];
        this.el.addEventListener( 'click', e =>{
            e.stopPropagation();
           this.el.classList.toggle( 'active' );
        });
        this.clickOutsideBind = this.clickOutside.bind( this );

        Emitter.on('click-outside', this.clickOutsideBind );

    }
    optionClick( e ){
        this.el.querySelector( '.active'  ).classList.remove( 'active' );
        let current = e.target;
        this.selectedValue.textContent = current.textContent;
        current.classList.add( 'active' );

        if( this.el.dataset.change ){
            let parts = this.el.dataset.change.split( ',' );
            this.call( parts[0], [current.dataset.value,parts[1]], parts[2] );
        }
    }

    clickOutside(){
        this.el.classList.remove( 'active' );
    }

    destroy(){
        Emitter.off('click-outside', this.clickOutsideBind );
    }

}
