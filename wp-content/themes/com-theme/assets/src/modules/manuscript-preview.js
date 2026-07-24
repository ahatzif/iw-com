import { module } from 'modujs';
import axios from "axios";
import LazyLoad from 'vanilla-lazyload';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'close': 'close', } };
        this.content = this.$( 'content' )[0];
        this.onEscapeKeyBind = this.onEscapeKey.bind( this );
        this.el.addEventListener( 'click', this.closeOnLinkClick.bind( this ) );

        this.body = document.body;


    }

    open( url ){
        this.call( 'show', false, 'PageLoading' );
        window.addEventListener( 'keydown', this.onEscapeKeyBind );
        axios.get( url ).then( response => { this.loadResponse( response ); } );

    }

    loadResponse( response ){
        let nextDocument = new DOMParser().parseFromString( response.data, "text/html" ).documentElement;
        let previewItem = nextDocument.querySelector( '[data-manuscript-preview]' );
        this.call( 'hide', false, 'PageLoading' );
        this.el.classList.add( 'opened'  );
        this.content.appendChild( previewItem );
        this.call( 'update', this.el, 'app' );
        this.lazy = new LazyLoad({ elements_selector : "[data-lazy]", container: this.el });
    }

    close( ev ){
        this.call( 'hide', false, 'PageLoading' );
        this.el.classList.remove( 'opened' );
        window.removeEventListener( 'keydown', this.onEscapeKeyBind );
        setTimeout( () => { this.content.innerHTML = ''; }, 1000 );
    }

    closeOnLinkClick( e ){
        if( e.target.closest( 'a' ) || e.target.closest( '[data-manuscript-preview="close"]') ){
            this.close( false );
        }
    }



    onEscapeKey( e ) {
        if ( e.key === "Escape" ) this.close();
    }
    destroy() {
        window.removeEventListener( 'keydown', this.onEscapeKeyBind );
    }

}
