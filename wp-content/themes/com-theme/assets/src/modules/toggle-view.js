import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'button': 'toggle', } };
        this.target = document.querySelector( this.el.dataset.target );
    }
    toggle( e ){
        this.active = this.el.querySelector( '.active');
        if( this.active ){
            this.active.classList.remove( 'active' );
            this.target.classList.remove( this.active.dataset.view );
        }
        e.currentTarget.classList.add( 'active' );
        this.target.classList.add( e.currentTarget.dataset.view );
        let isListView = e.currentTarget.dataset.view === 'list-view';
        let url = new URL(window.location);
        isListView ? url.searchParams.set('list-view', '1') : url.searchParams.delete('list-view');
        window.history.replaceState({}, '', url);
    }
}
