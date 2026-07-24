import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'target': 'toggleState' } };
    }

    toggleState(e) {
        
        e.stopPropagation();
        const label = e.currentTarget;
        const checkbox = label.querySelector('input[type="checkbox"]');
        if (checkbox) {
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            label.classList.toggle('checked', checkbox.checked);
        }
    }
}
