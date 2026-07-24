import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.container = this.el;
        this.emptyMessage = this.$( 'empty-message' )[0];
    }

    addMethod( html ) {
        if ( ! html ) return;
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        const element = template.content.firstElementChild;
        if ( ! element ) return;
        this.container.insertBefore( element, this.emptyMessage );
        this.call( 'update', this.el, 'app' );
        this.modals = [...element.querySelectorAll( '[data-module-modal]' )];
        this.modals.forEach( modal => { document.body.appendChild( modal ); });
        this.call('scrollTo', { target : element, options: { offset: -150 } },  'Scroll');
        element.classList.add( 'animate-loading' );
        setTimeout(() => element.classList.remove('animate-loading'  ) , 500);

    }
}
