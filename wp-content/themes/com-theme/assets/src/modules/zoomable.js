import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.zoomLevel = 1;
        this.image = null;
        this.mouseX = 0;
        this.mouseY = 0;
    }

    init() {
        this.image = this.el.querySelector('img');
        if( ! this.image ) return;
        this.el.addEventListener('mousemove', this.handleMouseMove.bind(this));
        this.el.addEventListener('click', this.handleZoom.bind(this));
    }

    handleMouseMove(event) {
        const rect = this.el.getBoundingClientRect();
        this.mouseX = event.clientX - rect.left;
        this.mouseY = event.clientY - rect.top;
        this.updateImagePosition();
    }

    handleZoom() {
        this.zoomLevel = this.zoomLevel === 1 ? 2 : 1;
        this.updateImagePosition();
    }

    updateImagePosition() {
        this.image.style.transform = `scale(${this.zoomLevel})`;
        const offsetX = (this.mouseX / this.el.offsetWidth) * 100;
        const offsetY = (this.mouseY / this.el.offsetHeight) * 100;
        this.image.style.transformOrigin = `${offsetX}% ${offsetY}%`;
    }


}
