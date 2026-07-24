import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.field = this.el.querySelector( '[data-validate="target"]');
        this.field.addEventListener( 'focus', () => {
            this.el.classList.add( 'focus' );
        });
        this.field.addEventListener( 'blur', () => {
            this.el.classList.remove( 'focus' );
        });
    }
}
