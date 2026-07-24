import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'provider': 'onClick', } };

    }
    onClick( e ){
        console.info(document.querySelector( '[data-plugin="nsl"][data-provider="' + e.currentTarget.dataset.provider + '"]' ));
        document.querySelector( '[data-plugin="nsl"][data-provider="' + e.currentTarget.dataset.provider + '"]' ).click();
        //this.el.querySelector( '[data-plugin="nsl"][data-provider="' + e.target.dataset.provider + '"]').click();
    }
}
