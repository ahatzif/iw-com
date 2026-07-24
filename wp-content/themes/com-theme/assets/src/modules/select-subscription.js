import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'toggle': 'togglePeriod', 'type': 'toggleType' } };
        this.inputs = this.el.querySelectorAll('input[name="attribute_pa_period"]')
    }

    togglePeriod(e) {
        const period = e?.period;
        if (!period) return;

        this.el.classList.toggle( 'monthly' );

        [...this.inputs].forEach(input => input.value = period);
    }


    toggleType(e) {
        const selectedLabel = e.currentTarget.closest('.accordion');
        const parentGroup = selectedLabel.closest('.gift-for');
        if (!parentGroup || !selectedLabel) return;

        const selectedType = selectedLabel.dataset.type;
        parentGroup.classList.remove('me', 'friend');

        if (selectedType === 'me' || selectedType === 'friend') {
            parentGroup.classList.add(selectedType);
        }
    }
}
