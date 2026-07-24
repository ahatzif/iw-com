import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.form = this.el.closest( 'form' );
        this.el.addEventListener( 'change', e => this.form.submit() );
    }
}
