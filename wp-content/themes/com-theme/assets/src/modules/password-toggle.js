import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.field = this.el.closest( '.field' );
        this.input = this.field.querySelector( 'input' );
        this.el.addEventListener( 'click', () => {
            this.field.classList.toggle( 'show-password' );
            this.input.type = this.field.classList.contains( 'show-password' ) ? 'text' : 'password';
        } );
    }
}
