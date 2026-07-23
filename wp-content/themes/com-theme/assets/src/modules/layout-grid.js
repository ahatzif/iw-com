import { module } from 'modujs';
import Cookies from 'js-cookie';  // Import js-cookie library

export default class LayoutController extends module {
    constructor(m) {
        super(m);
        document.addEventListener('keydown', (event) => this.handleKeydown(event));
    }

    handleKeydown(event) {
        const isMetaOrCtrlPressed = event.metaKey || event.ctrlKey;
        if (isMetaOrCtrlPressed) {
            switch (event.code) {
                case 'Slash': this.toggleLayoutGrid(); break;
                case 'KeyB': this.toggleAdminBar(); break;
                default: break;
            }
        }

    }
    toggleLayoutGrid() {
        const isActive = document.body.classList.toggle('show-layout-grid');
        Cookies.set('layout-grid', isActive ? 'true' : 'false', { expires: 1 });
    }

    toggleAdminBar() {
        const isHidden = document.body.classList.toggle('hide-admin-bar');
        Cookies.set('hide-admin-bar', isHidden ? 'true' : 'false', { expires: 1 });
    }

}
