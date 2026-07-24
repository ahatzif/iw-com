import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.clearButton = this.$( 'clear' )[0];
        this.buttons = [...this.$( 'button' )];
        this.inited = false;
    }

    init(){
        if( this.inited ) return;
        this.inited = true;

        this.el.querySelectorAll( '[data-select-tickets-category="button"]' ).forEach( button => button.addEventListener( 'click' , this.button.bind( this )));
        this.el.querySelectorAll( '[data-select-tickets-category="clear"]' ).forEach( button => button.addEventListener( 'click' , this.clear.bind( this )));
    }

    button( e ) {
        let current = e.currentTarget;
        current.classList.toggle( 'active' );
        let hasActive = false;
        this.buttons.forEach( button => {
           if( button.classList.contains( 'active' ) ){
               hasActive = true;
           }
        });
        this.clearButton.classList.toggle( 'active', ! hasActive );
    }
    clear( ){

        if( this.clearButton.classList.contains( 'active' ) ){
            return;
        }

        this.clearButton.classList.add( 'active' );
        this.buttons.forEach( button => button.classList.remove( 'active' ) );
        this.call( 'updateFilters', false, 'HappeningNow' );

    }
    destroy(){}
}
