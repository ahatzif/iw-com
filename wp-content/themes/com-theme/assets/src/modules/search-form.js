import { module } from 'modujs';
import barba from "@barba/core";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'option': 'optionClick', 'pill' : 'pillClick' } };
        this.buttons = this.$( 'buttons' );
        this.input = this.$( 'input' )[0];
        this.form = this.$( 'form' )[0];
        this.clearButton = this.$( 'clear-button' )[0];
        this.href = this.form.action;
        this.form.addEventListener('submit', this.onSubmit.bind(this));
    }

    optionClick(){
        this.getUrl();
    }

    onSubmit( e ){
        e.preventDefault();
        this.getUrl();
    }


    getActiveButtons(){
        this.activeButtons = this.el.querySelectorAll( '[data-search-form="option"].active');
    }

    getUrl(){
        this.getActiveButtons();
        this.types = [];
        [...this.activeButtons].forEach( option => this.types.push( option.dataset.value ) );

        let searchValue = this.input.value;

        let newurl  = this.href;
        if( searchValue ){
            newurl +=  'search/' + searchValue + '/' ;
        }
        if( this.types.length ){
            newurl += 'types/' + this.types.join( ',' ) + '/';
        }
        barba.go( newurl );
    }

    buildQuery(params) {
        return Object.keys(params).map((key) =>  encodeURIComponent(key) + '=' + encodeURIComponent(params[key]) ).join('&');
    }

    pillClick( e ){
        let pill = e.currentTarget;
        let name = pill.dataset.name;

        if( pill.dataset.type === 'input' ){
            let filter = this.el.querySelector(`input[type="text"][name="${name}"]`);
            if( filter ){
                filter.value = '';
                filter.closest( 'form' ).dispatchEvent( new Event('submit', { cancelable: true }) );
            }
        } else {
            let filterName = name ? `[data-name="${name}"]` : '';
            let selector = `${filterName}[data-value="${pill.dataset.value}"][data-search-form="option"]`;
            let element = this.el.querySelector(selector );
            if( element ) element.click();
        }
        pill.remove();
    }
}
