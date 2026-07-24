import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'toggle-group': 'toggleGroup', } };
        this.el.addEventListener('click', this.cardClick.bind(this));
        this.field = this.$('field')[0];
    }

    toggleGroup( e ){
        let group = e.currentTarget.closest( '.group.radio' );
        if( group.classList.contains( 'active' ) ) return;
        group.querySelector( '[data-user-benaki-card]' ).click();
    }

    cardClick(e) {
        const card = e.target.closest('[data-user-benaki-card]');
        if (!card || !this.el.contains(card)) return;

        [...this.el.querySelectorAll('[data-card-type]')].forEach(toggle => toggle.classList.remove('active', 'no-selection'));
        card.closest('[data-card-type]').classList.add('active');
        [...this.el.querySelectorAll('[data-user-benaki-card].active')].forEach(toggle => toggle.classList.remove('active'));
        card.classList.add('active');

        this.field.value = card.dataset.id;
    }
}
