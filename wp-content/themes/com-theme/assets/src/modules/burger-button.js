import { module } from 'modujs';
import { gsap} from "gsap";

export default class extends module {
    constructor(m) {
        super(m);
        this.el.addEventListener( 'click', () => {
            this.call( 'close', false, 'HeaderSearchForm' );
            this.call( 'toggle', false, 'BurgerMenu' );
        } );
    }
}
