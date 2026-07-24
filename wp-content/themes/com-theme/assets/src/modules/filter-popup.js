import { module } from 'modujs';
export default class extends module {
    constructor(m) {
        super(m);
        this.content = this.$( 'content' )[ 0 ];
        [...this.$( 'toggle' )].forEach( toggle => {
            toggle.addEventListener( 'click', this.toggle.bind( this ) );
        } )
        this.onEscapeKeyBind = this.onEscapeKey.bind( this );
        window.addEventListener( 'keydown', this.onEscapeKeyBind );


    }

    onEscapeKey( e ) {
        if ( e.key === "Escape" && this.content.classList.contains( 'active' )) {
            this.toggle();
        }
    }


    toggle(){
        this.content.classList.toggle( 'active' );
        this.call( this.content.classList.contains( 'active' ) ? 'stop' : 'start', false, 'Scroll' );
        this.call( 'setFilterPopup', this.content.classList.contains( 'active' ) ? this.el.dataset.name : null, 'CollectionsAdvancedSearch' );
    }


    destroy(){
        this.content.remove();
        window.removeEventListener( 'keydown', this.onEscapeKeyBind );
    }
}
