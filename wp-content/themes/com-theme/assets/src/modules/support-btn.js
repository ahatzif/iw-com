import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.supportbtn = this.$('info')[0];

        this.supportbtn.addEventListener('click', () => {
            this.el.classList.toggle('active');
        });

    }

}
