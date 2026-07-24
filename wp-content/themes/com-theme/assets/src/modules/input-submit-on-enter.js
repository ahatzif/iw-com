import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.el.addEventListener( 'keydown', this.submitOnEnter.bind( this ) ) ;
    }
    submitOnEnter( e ) {
        if ( e.key === 'Enter' ) {
            this.el.closest( 'form' ).dispatchEvent( new Event('submit', { cancelable: true }) );
        }
    }
}
