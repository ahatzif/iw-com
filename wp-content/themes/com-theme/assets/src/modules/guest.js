import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.guest = this.$('guest')[0];
        this.guest.addEventListener('click', () => {
            this.el.classList.remove('is-guest');
        });
    }
}
