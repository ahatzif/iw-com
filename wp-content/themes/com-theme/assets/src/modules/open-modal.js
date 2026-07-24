import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.modalName = this.el.dataset.modalName;
        this.returnOn = this.el.dataset.returnOn;
        if( ! this.modalName ) return;
        this.parentModal = this.el.closest( '[data-module-modal]' );
        this.el.addEventListener( 'click', () => {
            if( this.returnOn && document.body.classList.contains( this.returnOn ) ) return;
            this.modules.app.app.currentModules['Modal-' + this.modalName ].showModal();
        });
    }
}
