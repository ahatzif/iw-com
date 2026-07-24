import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.el.addEventListener( 'input', this.onlyNumbers.bind( this ) ) ;
    }
    onlyNumbers( e ){
        if ( e.key === 'Enter' ) {
            return;
        }
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
    }
}
