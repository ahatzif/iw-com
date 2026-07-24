import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);

        this.modal = this.el.closest( '[data-module-modal]' );

        Emitter.on('form-success', args => {
            if( args.el === this.el ) {
                this.modal.classList.add( 'has-message' );
            }
        });

        Emitter.on( 'modal-closed', modal => {
            if( modal === this.modal ){
                this.modal.classList.remove( 'has-message' );
            }
        } );

    }
    init() {}
    destroy(){}
}
