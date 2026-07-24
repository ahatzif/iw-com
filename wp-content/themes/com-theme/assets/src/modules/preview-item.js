import { module } from 'modujs';
import axios from 'axios';
export default class extends module {
    constructor(m) {
        super(m);
        this.el.addEventListener( 'click', this.clickDelegation.bind( this ) );

    }
    clickDelegation( e ){
        let item = e.target.closest( '[data-preview-item]' );
        if( item  ){
            e.stopPropagation();
            e.preventDefault();
            this.call( 'open', item.dataset.previewItem + 'preview-item/', 'ItemPreview' );
        }
    }
}
