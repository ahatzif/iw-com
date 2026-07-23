import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click : { 'close' : 'close', 'button' : 'openModal' } };
        this.iFrame = this.$('iframe')[0];
        this.modal = this.$( 'modal' )[0];
        this.iframeContainer = this.$( 'iframe-container' )[0];
        this.modalInner = this.$( 'modal-inner' )[0];
        this.$( 'close' )[0].addEventListener( 'click', this.close.bind( this ) );
        document.body.append( this.modal );
        this.onResizeBind = this.onResize.bind( this );
        this.keyPressBind = this.keyPress.bind(this);
        this.ratio = 9/16;
        window.addEventListener( 'resize', this.onResizeBind );
        this.onResize();
        document.addEventListener('keyup', this.keyPressBind );
    }
    keyPress(e) {
        if (e.code === 'Escape') {
            this.close();
        }
    }
    close(){
        this.iFrame.src = '';
        this.modal.classList.remove( 'active' );
    }
    openModal() {
        this.el.classList.add('autoplay');
        this.iFrame.src = this.iFrame.dataset.src + '?autoplay=1';
        this.modal.classList.add( 'active' );
    }
    onResize(){
        this.iframeContainer.style.height = 'auto';
        let diff = this.iframeContainer.clientHeight - (window.innerHeight -100);
        if( diff > 0 ){
            diff+=100
            this.iframeContainer.style.height = ( this.iframeContainer.clientWidth * this.ratio - diff  ) + 'px';
        }
    }
    destroy(){
        document.removeEventListener('keyup', this.keyPressBind );
        window.removeEventListener( 'resize', this.onResizeBind );
        this.modal.remove();
    }
}
