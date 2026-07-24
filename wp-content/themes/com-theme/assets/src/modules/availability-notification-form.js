import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'open-button': 'openModal' } };
        this.ids = this.$('ids')[0];

        this.modalForm = document.querySelector('[data-module-modal="' + modalName + '"]');
        this.form = this.modalForm?.querySelector('form');

    }


}
