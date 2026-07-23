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

            this.target.addEventListener( 'blur', () => this.validateDefault() );
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
        this.isValid = true;
        for ( let ruleParts of this.rules ) {
            let parts = ruleParts.split( ':' );
            let rule = parts[ 0 ];
            let params = rule.length > 1 ? parts[ 1 ] : false;
            if ( Validations[ rule ] ) {
                this.isValid = Validations[ rule ]( this.target, params );
                this.message.innerHTML = this.isValid ? '&nbsp;' : ValidationMessages[ rule ]( this.target, params );
                if ( !this.isValid ) break;
            }
        }
        this.el.classList.toggle( 'error', ! this.isValid );

        return this.isValid;
    }

    validateSelect(){

        if( this.target.querySelector( 'option[value=""]').selected ) {
            this.isValid = false;
        } else {
            if (this.target.multiple) {
                this.isValid = this.target.selectedOptions.length;
            } else {
                this.isValid = this.target.value !== '';
            }

        }
        let rule = 'required';
        this.message.innerHTML = this.isValid ? '&nbsp;' : ValidationMessages[ rule ]( this.target );

        this.el.classList.toggle( 'error', ! this.isValid );

        return this.isValid;

    }

    showError( message ) {
        this.isValid = false;
        this.message.innerHTML = message;
        this.el.classList.add( 'error' );
    }

    clear( message ) {
        this.isValid = true;
        this.message.innerHTML = '&nbsp;';
        this.el.classList.remove( 'error' );
    }




}
