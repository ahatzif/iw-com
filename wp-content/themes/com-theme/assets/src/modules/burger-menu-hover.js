import { module } from 'modujs';
import { isMobile} from "mobile-device-detect";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'toggle-submenu': 'toggleSubmenu' } };
        this.checkClickOutsideBind = this.checkClickOutside.bind( this );

        this.fixLinks();
        window.addEventListener('resize', this.fixLinks.bind( this ) );
    }

    fixLinks(){
        this.$( 'mail-li' ).forEach( li => li.classList.toggle( 'has-hover' , ! isMobile && window.innerWidth > 768 ) );
    }

    toggleSubmenu( e ){
        e.preventDefault();
        this.mainLink = e.currentTarget.closest( '.main-link' );
        this.mainLink.classList.toggle( 'active' );
        if( this.mainLink.classList.contains( 'active' ) ){
            document.addEventListener('click', this.checkClickOutsideBind );
        } else {
            document.removeEventListener('click', this.checkClickOutsideBind );
        }
    }

    checkClickOutside( e ){
        if ( this.mainLink.classList.contains('active') && ! this.mainLink.contains(e.target)) {
            this.mainLink.classList.remove('active');
        }
    }
}
