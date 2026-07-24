import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'button': 'expand' } };
        this.items = [...this.el.querySelectorAll( '.hidden[data-expand-grid="item"]')];
        this.itemsPerClick = this.el.dataset.itemsPerClick ? parseInt( this.el.dataset.itemsPerClick ) : 2;
    }
    expand() {
        const nextItems = this.items.splice(0, this.itemsPerClick);
        nextItems.forEach(item => item.classList.remove('hidden'));
        if (this.items.length === 0) {
            this.$( 'button' )[0].remove();
        }
    }

}
