import { module } from 'modujs';
require( 'dom-slider' );
const {slideUp, slideDown, slideToggle} = window.domSlider;
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);
        if( this.el.classList.contains( 'is-constructed' ) ) return;
        this.el.classList.add( 'is-constructed' );
        this.events = { click: { 'edit': 'edit', 'close-trash': 'closeTrash', 'open-trash' : 'openTrash' ,'trash': 'trash', 'product' : 'selectProduct' } };
        this.name = this.el.dataset.moduleGiftSubscriptionItem;
        this.form = this.$( 'form' )[0];

        this.displays = [];
        this.$( 'input' ).forEach( input => input.addEventListener( 'input', this.valueChanged.bind( this ) ) );
        this.$( 'display' ).forEach( el => this.displays[ el.dataset.name ] = el );
        this.products = this.$( 'product' );
        this.activeProduct = this.products[0];
        this.productPeriod = this.$( 'product-period' )[0];
        this.productPeriod.addEventListener( 'change', this.setProductPeriod.bind( this ) );
        this.periodNames = this.productPeriod.dataset.values.split( ',' );


        this.form.addEventListener( 'submit', e => {
            e.preventDefault()
        } );
        this.variationField = this.$( 'variation-id')[0];


        this.isOpen = false;
        this.onClickOutsideBind = this.onClickOutside.bind( this )
    }

    init(){
        if( this.el.classList.contains( 'is-inited' ) ) return;
        this.el.classList.add( 'is-inited' );
        if( this.activeProduct ){
            this.activeProduct.click();
        }

    }

    valueChanged( e ){
        let input = e.currentTarget;
        this.setDisplay( input.dataset.name, input.value, input.dataset.defaultValue );
    }

    setDisplay( name, value, defaultValue = '' ){
        let display = this.displays[ name ];
        let val = value.trim();
        if( display ){
            display.textContent = val !== '' ? val : defaultValue;
        }
    }

    selectProduct( e ){
        this.activeProduct.classList.remove( 'active' );
        this.activeProduct = e.currentTarget;
        this.activeProduct.classList.add( 'active' );
        this.setProductData();
    }

    setProductPeriod( e ){
        this.el.classList.toggle( 'show-year', ! this.productPeriod.checked );
        this.el.classList.toggle( 'show-month', this.productPeriod.checked );
        this.setProductData();
    }

    setProductData(){
        this.period = this.productPeriod.checked ? 'month' : 'year';
        this.variation = this.activeProduct.querySelector( `[data-variation-period="${this.period}"]` );
        this.variationField.value = this.variation.dataset.id;
        this.setDisplay( 'product-name', this.activeProduct.dataset.name );
        this.setDisplay( 'product-price', this.variation.dataset.price );
        this.setDisplay( 'product-period', this.periodNames[ this.productPeriod.checked ? 1 : 0 ] );
    }




    edit(){
        this.el.classList.toggle( 'edit' );
        slideToggle( { element : this.form, duration : 300 } );
    }

    open(){
        this.el.classList.remove( 'edit' );
        slideDown( { element : this.form, duration : 0 } );
    }

    openTrash(){
        this.isOpen = true;
        this.el.classList.add( 'trash-open' );
        Emitter.on( 'click-outside',  this.onClickOutsideBind );
    }

    closeTrash(){
        this.isOpen = false;
        this.el.classList.remove( 'trash-open' );
        Emitter.off( 'click-outside', this.onClickOutsideBind );
    }

    onClickOutside( e ){
        if(e.target.closest( '.trash-button' ) || ! this.isOpen ){
            return;
        }
        this.closeTrash();
    }

    trash( e ){
        this.closeTrash();
        slideUp( { element : this.el, duration : 300 } ).then( e => {
            this.el.remove();
            this.call( 'destroy', false, 'GiftSubscriptionItem', this.el.dataset.moduleGiftSubscriptionItem  );
            this.call( 'updateCount', false, 'GiftSubscriptionForm' );
        });
    }

    destroy(){

    }

}
