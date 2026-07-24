import { module } from 'modujs';

export default class extends module {

    constructor( m ) {
        super( m );
        this.isActive = false;
        this.onEscapeKeyBind = this.onEscapeKey.bind( this );
        window.addEventListener( 'keydown', this.onEscapeKeyBind );

        this.el.addEventListener( 'click', () => {
            this.call( 'forceHide', this.el, "ToggleState" );
            this.isActive = !this.isActive;
            this.toggleTargets();
            this.el.classList.toggle( 'is-active' );
        } );

    }

    toggleTargets() {
        [ ...document.querySelectorAll( this.el.dataset.target ) ].forEach( ( target ) => {
            [ ...target.dataset.toggleStateClass.split( ' ' ) ].map( className => {
                target.classList.toggle( className );
            } );
        } );
    }

    forceHide( currentElement ) {
        if ( this.el !== currentElement && this.isActive ) this.hide();
    }

    hide() {
        if ( this.isActive ) this.el.click();
    }

    onEscapeKey( e ) {
        if ( e.key === "Escape" ) this.hide();
    }

    init() {
    }

    destroy() {
        window.removeEventListener( 'keydown', this.onEscapeKeyBind );
    }

}
