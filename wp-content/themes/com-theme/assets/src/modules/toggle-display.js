import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);

        this.targetField = this.el.closest( 'form' ).querySelector( this.el.dataset.target );
        this.el.querySelector( 'input' ).addEventListener( 'change', e => {
            let isChecked = e.currentTarget.checked;
            this.targetField.classList.toggle( 'hidden', ! isChecked );
        });
    }

}
