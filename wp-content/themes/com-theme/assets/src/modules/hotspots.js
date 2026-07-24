import { module } from 'modujs';
import { isMobile } from "mobile-device-detect";

export default class extends module {
    constructor(m) {
        super(m);
        this.inited = false;
        this.handleClickOutsideBind = this.handleClickOutside.bind(this);

    }
    init() {
        if( this.inited ) return;
        this.inited = true;
        if( this.el.classList.contains( 'inactive' ) ) return;
        [...this.$('hotspot')].forEach(hotspot => hotspot.addEventListener('click', this.toggleHotspot.bind(this)));
        this.active = this.el.querySelector('[data-hotspots="hotspot"].active')
        document.addEventListener('click', this.handleClickOutsideBind);





    }

    toggleHotspot(e) {
        e.stopPropagation(); 
        this.current = e.currentTarget;
        if (this.active && this.active !== this.current) {
            this.active.classList.remove('active');
        }
        
        this.current.classList.toggle('active');
        this.active = this.current;

        if(this.active.classList.contains('active')){
            this.call( 'hideOtherElements', false, 'InfoPopup' );
            this.call( 'closeInfoPopUp', false, 'InfoPopup' );
            this.call( 'hideEl', false, 'InfoPopup' );

            

        this.swiperName = this.el.closest('[data-module-swiper]').dataset.swiperName;
        
        // this.call( 'buildZoom', false, 'Swiper', this.swiperName )


        }else{
            this.call( 'showOtherElements', false, 'InfoPopup' );
        }
    }


    closeAllHotspots(){
        [...this.$('hotspot')].forEach(hotspot => {
            hotspot.classList.remove('active');
        });
    }

    handleClickOutside(e) {
        [...this.$('hotspot')].forEach(hotspot => {
            if(!hotspot.contains(e.target)){
                hotspot.classList.remove('active');
            }
        });
    }


    destroy(){
        document.removeEventListener('click', this.handleClickOutsideBind);
    }


}
