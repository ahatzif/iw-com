import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);

        Emitter.on('form-success', args => {
            if( args.el === this.el ) {
                this.call( 'update', args.response.data.data.cart, 'Cart' );
            }
        });

    }

}
