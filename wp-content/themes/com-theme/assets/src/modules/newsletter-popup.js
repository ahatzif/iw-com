import {module} from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = {click: {'button': 'copyEmailToModal',}};
        this.selector = 'input[name="user_email"]';
        this.input = this.el.querySelector(this.selector);
        this.targetInput = document.querySelector('[data-module-modal="edit-newsletter"] ' + this.selector);
    }

    copyEmailToModal() {
        this.targetInput.value = this.input.value;
    }
}