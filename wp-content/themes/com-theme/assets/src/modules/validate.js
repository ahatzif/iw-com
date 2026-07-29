import { module } from 'modujs';
import { Validations, ValidationMessages } from '../utils/validations';

export default class extends module {

    constructor( m ) {
        super( m );
        this.rules = this.el.dataset.rules ? this.el.dataset.rules.split( '|' ) : [];

        this.target = this.$( 'target' )[ 0 ];

        this.name = this.target.getAttribute( 'name' ).replace( /[\[\]]/g, '' );
        this.message = this.$( 'message' )[ 0 ];
        this.validate = this.validate;
        this.addEvents();
    }

    addEvents() {
        // Backend validation errors
        this.el.addEventListener( 'formError', errors => {
            if ( errors.detail.errors.hasOwnProperty( this.name ) ) {
                this.showError( errors.detail.errors[ this.name ] );
            }
        } );

        this.el.addEventListener( 'clear', this.clear.bind( this ) );


        if ( this.target.type === 'checkbox' || this.target.type === 'radio' ) {
            [...this.$( 'target' )].forEach( target => {
                target.addEventListener( 'click', e => this.validateDefault() );
            });

        } else if ( this.target.type === 'select-one' ||  this.target.type === 'select-multiple' ){
            this.target.addEventListener( 'change', () => this.validateSelect() );
        } else {
            if ( this.target.type === 'file' ) {
                this.target.addEventListener( 'change', () => this.validateDefault() );
            } else {
                this.target.addEventListener( 'focus', () => {
                    this.el.classList.remove( 'error' )
                } );
            }

            this.target.addEventListener( 'blur', () => {
                if( ! this.isValid ) {
                    //setTimeout( () => {this.validateDefault()},100);
                }
            } );
        }


    }

    validate() {
        if ( this.target.type === 'select-one' ||  this.target.type === 'select-multiple' ){
            return this.validateSelect()
        } else {
            return this.validateDefault();
        }
    }

    validateDefault(){

        if( ! this.el.closest( '.force-validation') ){
            if( this.el.closest( '.hidden:not(.hidden-step)' ) || this.el.classList.contains( 'hidden' ) ) return true;
        }
        this.isValid = true;


        for ( let ruleParts of this.rules ) {
            let parts = ruleParts.split( ':' );
            let rule = parts[ 0 ];
            let params = rule.length > 1 ? parts[ 1 ] : false;

            if ( Validations[ rule ] ) {
                this.isValid = Validations[ rule ]( this.target, params );
                this.message.textContent = this.isValid ? '\u00a0' : ValidationMessages[ rule ]( this.target, params );
                this.el.classList.toggle( 'valid-' + rule, this.isValid );
                this.el.classList.toggle( 'invalid-' + rule, ! this.isValid );
                if ( ! this.isValid ) break;
            }
        }

        this.el.classList.toggle( 'error', ! this.isValid );
        return this.isValid;
    }

    validateRule( theRule ){
        let parts = theRule.split( ':' );
        let rule = parts[ 0 ];
        let params = rule.length > 1 ? parts[ 1 ] : false;
        if ( Validations[ rule ] ) {
            this.isValid = Validations[ rule ]( this.target, params );
            this.message.textContent = this.isValid ? '\u00a0' : ValidationMessages[ rule ]( this.target, params );
            this.el.classList.toggle( 'valid-' + rule, this.isValid );
            this.el.classList.toggle( 'invalid-' + rule, ! this.isValid );
        }
        this.el.classList.toggle( 'error', ! this.isValid );
        return this.isValid;
    }

    setRules( rules ){
        this.rules = Array.isArray( rules ) ? rules : String( rules || '' ).split( '|' ).filter( Boolean );
        this.el.dataset.rules = this.rules.join( '|' );
        this.clear();
    }

    validateSelect(){


        if( ! this.el.closest( '.force-validation') ) {
            if ( this.el.closest( '.hidde:not(.hidden-step)' ) || this.el.classList.contains( 'hidden' ) ) return true;
        }
        let empty = this.target.querySelector( 'option[value=""]');
        if( empty && empty.selected ) {
            this.isValid = false;
        } else {
            if (this.target.multiple) {
                this.isValid = this.target.selectedOptions.length;
            } else {
                this.isValid = this.target.value !== '';
            }

        }
        let rule = 'required';
        this.message.textContent = this.isValid ? '\u00a0' : ValidationMessages[ rule ]( this.target );

        this.el.classList.toggle( 'error', ! this.isValid );

        return this.isValid;

    }

    showError( message ) {
        this.isValid = false;
        this.message.textContent = message;
        this.el.classList.add( 'error' );
    }

    clear( message ) {
        this.isValid = true;
        this.message.textContent = '\u00a0';
        this.el.classList.remove( 'error' );
    }
}
