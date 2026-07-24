import { module } from 'modujs';
import axios from 'axios';

export default class extends module {
    constructor(m) {
        super(m);

        if( ! wc_country_select_params ) return;
        this.countries = JSON.parse(wc_country_select_params.countries);
        this.form = this.el.closest( 'form' );
        this.stateFieldName = this.el.name.split( '_' )[0] + "_state";
        this.type = this.form.querySelector('input[name="address_type"]');

        this.stateFieldName = this.type ? `user_fields[${this.type.value}_state]` : this.stateFieldName;



        this.statesInput = this.form.querySelector( `input[name="${this.stateFieldName}"]` );
        this.statesSelect = this.form.querySelector( `select[name="${this.stateFieldName}"]` );

        if( this.statesInput ){
            this.statesInputField = this.statesInput.closest( '.field' );
        }
        if( this.statesSelect ){
            this.statesSelectField = this.statesSelect.closest( '.custom-select' );
        }
        this.el.addEventListener( 'change', this.onChange.bind( this) );


    }
    init(){
        this.onChange( false );
    }

    onChange( e ){

        let states = this.countries[ this.el.value ];
        let hasStates = states && Object.keys(states).length > 0;
        if( this.statesInput && this.statesInputField){
            this.statesInputField.classList.toggle( 'hidden', hasStates );
            this.statesInput.disabled = hasStates;
        }

        if( this.statesSelectField && this.statesSelect){
            this.statesSelectField.classList.toggle( 'hidden', ! hasStates );
            this.statesSelect.disabled = ! hasStates;
        }

        if( ! hasStates ){
            this.statesInput.value = '';
        } else if( e ) { //
            let moduleName = this.statesSelectField.dataset.moduleSelectField;
            this.call( 'updateOptions', states, 'SelectField', moduleName );
        }
    }
}
