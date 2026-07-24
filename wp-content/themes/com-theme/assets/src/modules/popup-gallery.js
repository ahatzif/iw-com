import { module } from 'modujs';
export default class extends module {
    constructor(m) {
        super(m);
        this.popup = this.$( 'popup' )[ 0 ];

        [...this.$( 'toggle' )].forEach( toggle => {
            toggle.addEventListener( 'click', this.toggle.bind( this ) );
        } )
        this.onEscapeKeyBind = this.onEscapeKey.bind( this );
        window.addEventListener( 'keydown', this.onEscapeKeyBind );
        document.body.append( this.popup );
    }


    init(){
        this.swipername = this.popup.querySelector( '[data-module-swiper]' ).dataset.moduleSwiper;
    }


    onEscapeKey( e ) {
        if ( e.key === "Escape" && this.popup.classList.contains( 'active' )) {
            this.toggle();
        }
    }

    playVideo( e ){
        let slide = this.popup.querySelector(`[data-index-popup="${e.currentTarget.dataset.index}"]`);
        let iframe = slide.querySelector('iframe');
        if(iframe){
            iframe.src = iframe.dataset.src + '?autoplay=1';
        }
    }

    stopVideo(){
        [...this.popup.querySelectorAll('iframe')].map((iframe) => {
            iframe.src = iframe.dataset.src;
        });
    }

    toggle( e ){

        if( e && e.currentTarget.dataset.index ){
            this.call( 'gotToIndex', e.currentTarget.dataset.index, 'Swiper',  this.swipername );
            this.playVideo(e);
        }

        this.popup.classList.toggle( 'active' );
        if(!this.popup.classList.contains('active')){
            this.stopVideo();
        }


    }


    destroy(){
        this.popup.remove();
        window.removeEventListener( 'keydown', this.onEscapeKeyBind );
    }
}
