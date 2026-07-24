import { module } from 'modujs';
import LazyLoad from "vanilla-lazyload";

export default class extends module {
    constructor(m) {
        super(m);
        this.lazy = new LazyLoad({ elements_selector : "[data-lazy]", container: this.el });
    }
    update(){
        this.lazy.update();
    }
}
