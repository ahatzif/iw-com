import { module } from 'modujs';
import axios from "axios";
import LazyLoad from 'vanilla-lazyload';
import { isMobile } from 'mobile-device-detect';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'close': 'close', } };
        this.content = this.$( 'content' )[0];
        this.onEscapeKeyBind = this.onEscapeKey.bind( this );
        this.el.addEventListener( 'click', this.closeOnLinkClick.bind( this ) );

        this.body = document.body;
        this.onPopStateBind = this.onPopState.bind( this );

    }

    open(url) {

        this.call('show', false, 'PageLoading');
        this.bodyHasWhiteHeader = this.body.classList.contains('white-header');
        this.bodyHasScrolled = this.body.classList.contains('has-scrolled');

        window.addEventListener('keydown', this.onEscapeKeyBind);
        window.addEventListener( 'popstate', this.onPopStateBind );
        window.history.pushState(
            { itemPreviewOpen: true },
            '',
            window.location.href
        );
        axios.get(url)
            .then(response => this.loadResponse(response))
            .catch(error => { });

        if (isMobile) {
            let lastY = 0;

            this._onTouchStart = e => lastY = e.touches[0].clientY;
            this._onTouchMove = e => {
                const currentY = e.touches[0].clientY;
                const diff = lastY - currentY;

                if (diff > 0) {
                    this.body.classList.add('has-scrolled');
                } else if (diff < 0 && window.scrollY === 0) {
                    this.body.classList.remove('has-scrolled');
                }
                lastY = currentY;
            };

            this._cleanupTouch = () => {
                window.removeEventListener('touchstart', this._onTouchStart);
                window.removeEventListener('touchmove', this._onTouchMove);
            };
            window.addEventListener('touchstart', this._onTouchStart);
            window.addEventListener('touchmove', this._onTouchMove);
        }
    }

    loadResponse( response ){
        this.body.classList.remove( 'white-header', 'has-scrolled' );
        let nextDocument = new DOMParser().parseFromString( response.data, "text/html" ).documentElement;
        let previewItem = nextDocument.querySelector( '[data-item-preview]' );

        this.call( 'hide', false, 'PageLoading' );
        this.el.classList.add( 'opened'  );
        this.content.appendChild( previewItem );
        this.call( 'update', this.el, 'app' );
        this.lazy = new LazyLoad({ elements_selector : "[data-lazy]", container: this.el });
        //window.history.pushState({ path: url }, '', url);
    }

    close( ev ){

        this.call( 'hide', false, 'PageLoading' );
        this.el.classList.remove( 'opened' );

        if( ev ) {
            if( this.bodyHasWhiteHeader ){
                this.body.classList.add( 'white-header' );
            }
            if( this.bodyHasScrolled ){
                this.body.classList.add( 'has-scrolled' );
            }
        }
        window.removeEventListener( 'popstate', this.onPopStateBind );
        window.removeEventListener( 'keydown', this.onEscapeKeyBind );
        setTimeout( () => { this.content.innerHTML = ''; }, 1000 );
    }
    closeOnLinkClick( e ){
        if( e.target.closest( 'a' ) || e.target.closest( '[data-item-preview="close"]') ){
            this.close( false );
        }
    }
    onEscapeKey( e ) {
        if ( e.key === "Escape" ) this.close();
    }
    onPopState() {
        if ( this.el.classList.contains( 'opened' ) ) {
            this.close( false );
        }
    }
    destroy() {
        window.removeEventListener( 'keydown', this.onEscapeKeyBind );
        window.removeEventListener( 'popstate', this.onPopStateBind );
        if (this._cleanupTouch) this._cleanupTouch();
    }
}