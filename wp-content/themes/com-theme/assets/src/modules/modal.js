import { module } from 'modujs';

export default class extends module {

    constructor(m) {
        super(m);
        this.events = { click: { close: 'hideModal' } };
        this.forms = [...this.el.querySelectorAll( '[data-module-form]')];
        this.isOpen = false;
        this.modalId = this.el.dataset.moduleModal;
        this.onKeyUpBind = this.onKeyUp.bind( this );
        document.body.addEventListener('keydown', this.onKeyUpBind );



    }
    showModal( id = false){
        if( id && this.modalId.toString() !== id.toString() ){
            return;
        }
        this.isOpen = ! this.isOpen;
        this.el.classList.add( 'active' );
    }

    hideModal(){
        this.isOpen = ! this.isOpen;
        this.el.classList.remove( 'active' );
        this.forms.forEach( form => form.dispatchEvent( new CustomEvent( 'clear' ) )  );
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
