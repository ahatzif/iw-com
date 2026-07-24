import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);
        this.input = this.el.querySelector( 'input' );
        this.btn = this.el.querySelector( '[data-happening-now="update-filters-button"]' );
        this.input.addEventListener( 'keydown', this.keyDown.bind( this ) ) ;
        this.btn.addEventListener( 'click', this.updateHappeningNowSearchField.bind( this ) );
        Emitter.on( 'happening-now-pill-click', pill => {
            if( pill.dataset.name && pill.dataset.name === 'search' ){
                this.input.value = '';
            }
        } );
    }
    keyDown( e ){
        if ( e.key === 'Enter' ) {
            this.updateHappeningNowSearchField();
        }
    }
    updateHappeningNowSearchField(){
        this.call( 'updateSearchField', this.input.value , 'HappeningNow' );
    }
}
