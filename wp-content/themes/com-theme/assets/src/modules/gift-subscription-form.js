import { module } from 'modujs';
import axios from "axios";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'add-item': 'addItem', 'submit' : 'onSubmit' } };
        this.itemTemplate = this.$( 'template' )[0].cloneNode( true );
        this.errorMessage = this.$( 'error' )[0];
        this.addItemBtn  = this.$( 'add-item' )[0];
        this.scrollBar = this.el.closest( '[data-module-scrollbar]');
        this.modal = this.el.closest('[data-module-modal]');

        this.updateCount();
    }

    addItem( e ){
        const trigger = this.addItemBtn.parentNode;
        const item = this.itemTemplate.cloneNode(true);
        trigger.parentNode.insertBefore(item, trigger);
        this.call( 'update', this.el, 'app' );
        this.updateCount();
        item.querySelector( 'input[type="text"]').focus();


        if( e ){
            setTimeout( () => {
                this.call( 'scrollIntoView', item, 'Scrollbar', this.scrollBar.dataset.moduleScrollbar );
            }, 300 );
        }


    }

    updateCount(){
        this.elements = this.el.querySelectorAll( '[data-module-gift-subscription-item]' );
        this.el.classList.toggle( 'has-multiple', this.elements.length > 1 );
    }

    onSubmit( e ){
        e.preventDefault();

        this.forms = this.el.querySelectorAll( 'form' );


        let hasErrors = false;
        let emails = [];
        this.forms.forEach( form => {
            this.call( 'onSubmit', e, 'Form', form.dataset.moduleForm );
            if( form.classList.contains( 'has-errors' ) ){
                hasErrors = true;
            } else {
                let email = form.querySelector( '[name="email"]' );
                if( emails.includes( email.value ) ){
                    let validate = email.closest( '[data-module-validate]' );
                    this.call( 'showError', this.el.dataset.uniqueEmailMessage, 'Validate', validate.dataset.moduleValidate );
                    form.classList.add( 'has-errors' );
                    hasErrors = true;
                } else {
                    emails.push( email.value );
                }
            }
        } );

        if( ! hasErrors ){
            this.submitForm();
        } else {
            this.showError( );
        }


    }

    submitForm(){
        this.el.classList.remove( 'response-error' );
        let subscriptions = [];
        this.forms.forEach( form => {
            const subscription = {};
            [ 'variation_id', 'first_name', 'last_name', 'email', 'message' ].forEach( name => {
                const input = form.querySelector( `[name="${name}"]` );
                if ( input ) {
                    subscription[ name ] = input.value;
                }
            } );
            subscriptions.push( subscription );
        } );


        const params = new URLSearchParams();
        params.append( 'action', this.el.dataset.action );
        params.append( 'subscriptions', JSON.stringify( subscriptions ) );

        this.el.classList.add( 'loading' );
        axios.post( THEME_OBJ.ajaxURL, params ).then( response => {
            this.el.classList.remove( 'loading' );
            let responseData = response.data;
            if( responseData.success ) {
                this.call( 'update', responseData.data.cart, 'Cart' );
                this.call( 'show', false, 'CartButton', 'main' );
                this.call( 'confirmedClose', false, 'Modal' );
            } else if( responseData.data?.message ) {
                this.el.classList.add( 'response-error' );
                this.errorMessage.innerHTML = responseData.data?.message;

            }
        }).catch( e => {

            this.el.classList.remove( 'loading' );
            this.el.classList.add( 'error' );
            if(  e.response?.data?.data?.errors instanceof Array){
                let errors = e.response.data.data.errors;
                errors.forEach( (errorObj,index) => {
                    if( errorObj ){
                        let form = this.forms[ index ];
                        Object.keys(errorObj).forEach(name => {
                            let validate = form.querySelector( `[name="${name}` ).closest('[data-module-validate]');
                            this.call( 'showError', errorObj[name], 'Validate', validate.dataset.moduleValidate );
                        });
                        form.classList.add( 'has-errors' );
                    }
                });
                this.showError();
            }
        } );


    }

    showError( ){
        let form = this.el.querySelector( 'form.has-errors' );
        let item = form.closest( '[data-module-gift-subscription-item]');
        let moduleName = 'GiftSubscriptionItem';
        this.call( 'open', false,moduleName, item.dataset[ 'module' + moduleName]);
        setTimeout(() => {
            this.call( 'scrollIntoView', form, 'Scrollbar', this.scrollBar.dataset.moduleScrollbar );
        }, 100 );

    }

    open( variationId ){
        [...this.elements].forEach( el => el.remove() );
        this.addItem();

        if( variationId ){

            let variation = this.el.querySelector( `[data-variation-period][data-id="${variationId}"]` );
            if( variation ){

                let period = variation.dataset.variationPeriod;
                let product = variation.closest( '[data-gift-subscription-item="product"]' );
                let periodCheckbox = variation.closest( 'form' ).querySelector( '[data-gift-subscription-item="product-period"]');
                product.click();
                periodCheckbox.checked = period === 'month';
                periodCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
        this.call( 'scrollTop', 0, 'Scrollbar', this.scrollBar.dataset.moduleScrollbar );
        this.call( 'showModal', false, 'Modal', this.modal.dataset.moduleModal );
    }

}
