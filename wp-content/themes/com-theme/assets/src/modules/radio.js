import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { change: { 'input': 'onChange' } };
        this.inputs = this.$( 'input' );}
    onChange( e ){
        this.inputs.forEach( input => {
            if( input.hasAttribute( 'data-toggle-target' ) ){
                let target = document.querySelector( input.dataset.toggleTarget );
                if( target ){
                    target.classList.toggle( 'hidden',  !input.checked  );
                    target.querySelectorAll('[data-module-validate].error').forEach( el => el.classList.remove( 'error' ) );
                }
            }
        })

    }
}
