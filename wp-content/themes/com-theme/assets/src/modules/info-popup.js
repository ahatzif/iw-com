import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'scroll-to': 'scroll', } };
        this.toggleOpenIcon = this.$( 'toggle' )[ 0 ];
        this.bg = this.$( 'bg' )[ 0 ];
        this.text = this.$( 'text' )[ 0 ];
        this.infoPopup = this.el;
        this.toggleBind = this.toggle.bind(this);
        this.handleClickOutsideBind = this.handleClickOutside.bind(this);
        this.infoPopup.style.width = '70px'
    }

    init() {
        this.toggleOpenIcon.addEventListener('click', this.toggleBind);
        document.addEventListener('click', this.handleClickOutsideBind);
    }

    toggle( e ){
        e.stopPropagation(); 
        this.infoPopup.classList.toggle( 'active' );
        if(this.infoPopup.classList.contains( 'active' )){
            this.showEl();
            this.hideOtherElements();
        }else{
            this.hideEl();
            this.showOtherElements();
        }
    }

    handleClickOutside(e) {
        if (this.infoPopup.classList.contains('active') && !this.infoPopup.contains(e.target)) {
            this.closeInfoPopUp();
            this.hideEl();
            this.showOtherElements();
        }
    }

    hideEl(){
        setTimeout(() => { 
            this.bg.classList.add('hidden');
            this.text.classList.add('hidden');
            this.infoPopup.style.width = '70px'
        }, '300');   
    }

    showEl(){
        this.bg.classList.remove('hidden');
        this.text.classList.remove('hidden');
        this.infoPopup.style.width = '';
    }

    hideOtherElements(closeAllHotspots = true){
        let parent = this.infoPopup.closest('[data-parent]');
        if(!parent) return;
        parent.classList.add('has-popup-open');
        if(closeAllHotspots){
            this.call( 'closeAllHotspots', false, 'Hotspots' );
        }
    }

    showOtherElements(){
        let parent = this.infoPopup.closest('[data-parent]');
        if(!parent) return;
        parent.classList.remove('has-popup-open');
    }

    closeInfoPopUp(){
        this.infoPopup.classList.remove( 'active' );        
    }


    destroy(){
        this.toggleOpenIcon.removeEventListener('click', this.toggleBind);
        document.removeEventListener('click', this.handleClickOutsideBind);
    }
}




