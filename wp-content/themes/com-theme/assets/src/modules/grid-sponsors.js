import { module } from 'modujs';
import { cos } from 'three/tsl';

export default class extends module {
    constructor(m) {
        super(m);
        this.updateSeparators = this.updateSeparators.bind(this);
        this.onResize = this.onResize.bind(this);
    }
    init() {
        this.grid = this.el;
        this.itemSelector = '.sponsor-grid-item';
        this.sepClass = 'col-span-full border-t border-dashed border-medium';
        this.updateSeparators();
        window.addEventListener('resize', this.onResize);
    }

    onResize() {
        this.updateSeparators();
    }

    updateSeparators() {
        const grid = this.grid;
        if (!grid) return;

        grid.querySelectorAll('[data-row-sep="1"]').forEach(el => el.remove());
        const items = Array.from(grid.querySelectorAll(this.itemSelector));
        items.forEach(el => el.classList.remove('no-border-r'));
        if (!items.length) return;

        const rowEnds = [];
        const tol = 0.5;
        let currentTop = Math.round(items[0].getBoundingClientRect().top);

        for (let i = 1; i < items.length; i++) {
            const top = Math.round(items[i].getBoundingClientRect().top);
            if (Math.abs(top - currentTop) > tol) {
            rowEnds.push(i - 1); // τέλος προηγούμενης σειράς
            currentTop = top;
            }
        }
        rowEnds.push(items.length - 1); // τέλος τελευταίας σειράς ✔

        // (1) no-border-r σε ΟΛΑ τα τέλη σειρών (περιλαμβάνει και την τελευταία)
        rowEnds.forEach(idx => {
            items[idx].classList.add('no-border-r');
        });

        // (2) separators μόνο στις ενδιάμεσες σειρές
        rowEnds.slice(0, -1).forEach(idx => {
            const item = items[idx];
            const sep = document.createElement('div');
            sep.setAttribute('data-row-sep', '1');
            sep.className = `${this.sepClass} h-0 pointer-events-none`;
            grid.insertBefore(sep, item.nextSibling);
        });
    }

    destroy() {
        window.removeEventListener('resize', this.onResize);

        if (this.grid) {
            this.grid.querySelectorAll('[data-row-sep="1"]').forEach(el => el.remove());
            this.grid.querySelectorAll('.no-border-r').forEach(el => el.classList.remove('no-border-r'));
        }
    }
}
