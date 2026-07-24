import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'switch': 'switch', } };
        this.source = this.$( 'source')[0];
        this.currentValue = 'default';
        this.defaultPaymentMethod = this.el.querySelector( '[data-module-payment-method]' ); ;

    }
    switch( e ){
        this.el.classList.remove( this.currentValue );
        this.currentValue = e.currentTarget.dataset.value;
        this.el.classList.add( this.currentValue );
        if( this.currentValue === 'new' ){
            this.source.value = '';
        } else {
            this.source.value = this.defaultPaymentMethod.dataset.token;
        }
    }

}
