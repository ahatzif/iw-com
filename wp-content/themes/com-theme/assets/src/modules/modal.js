
import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";
import LazyLoad from 'vanilla-lazyload';
import Cookies from "js-cookie";

export default class extends module {

    constructor(m) {
        super(m);
        this.events = { click: { close: 'hideModal', 'confirm-close' : 'confirmedClose' } };

        this.forms = [...this.el.querySelectorAll( '[data-module-form]')];
        this.isOpen = false;
        this.onKeyUpBind = this.onKeyUp.bind( this );
        document.body.addEventListener('keydown', this.onKeyUpBind );
        this.lazy = new LazyLoad({ elements_selector : "[data-lazy]", container: this.el });

        Emitter.on( 'modal-opened', async (el) => {
            if( el !== this.el ){
                await this.hideModal();
            }
        });

        this.hasToConfirm = this.$( 'confirm-close' ).length > 0;
        this.confirmedClosed = false;
    }

    init(){
        this.modalName = this.el.dataset.moduleModal;
        document.body.querySelectorAll( `[data-module-modal="${this.modalName}"]` ).forEach( modal => {
            if( modal !== this.el ) modal.remove();
        });

        if( this.el.parentElement !== document.body ){
            document.body.appendChild( this.el );
        }

        let cookie = Cookies.get( 'open-modal' );
        if(  cookie && cookie === this.modalName ){
            this.showModal();
            Cookies.remove( 'open-modal' );
        }


    }
    showModal(  ){
        Emitter.emit( 'modal-opened', this.el );
        this.isOpen = ! this.isOpen;
        this.el.classList.add( 'active' );
    }

    addCloseListener( el ){
        if( el ){
            this.closeListener = el;
        }
    }


    waitForTransitionEnd(el) {
        return new Promise(resolve => {
            const handler = e => {
                if (e.target !== el) return;
                el.removeEventListener('transitionend', handler);
                resolve();
            };
            el.addEventListener('transitionend', handler);
        });
    }

    async hideModal(){

        if( this.hasToConfirm ){
            if( this.confirmedClosed ){
                this.confirmedClosed = false;
            } else {
                return;
            }
        }

        this.isOpen = ! this.isOpen;
        this.el.classList.remove( 'active' );

        await this.waitForTransitionEnd(this.el);

        let cookie = Cookies.get( 'redirect-' + this.modalName );
        if(  cookie ){
            Cookies.remove( 'redirect-' + this.modalName );
        }



        this.forms.forEach( form => {
            form.dispatchEvent( new CustomEvent( 'clear' ) )
        }  );

        if( this.closeListener ){
            this.closeListener.onHideModal()
        }
        Emitter.emit( 'modal-closed', this.el );

    }

    async confirmedClose(){
        this.confirmedClosed = true;
        this.hideModal();
    }

    onKeyUp( e ){
        if( e.key === 'Escape' && this.isOpen ){
            this.hideModal();
        }
    }

    destroy(){
        window.removeEventListener( 'keyup', this.onKeyUpBind );
    }

}
