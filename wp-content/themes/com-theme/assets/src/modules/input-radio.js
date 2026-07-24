import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.el.addEventListener( 'click', this.radio.bind( this ) )
    }
    radio( e ){
        if( e.target.dataset.value && ! e.target.classList.contains( 'active' ) ){
            let currentActive = e.currentTarget.querySelector( '[data-value].active' );
            if( currentActive  ){
                currentActive.classList.remove( 'active' );
            }
            e.target.classList.add( 'active' );
        }
    }
}
