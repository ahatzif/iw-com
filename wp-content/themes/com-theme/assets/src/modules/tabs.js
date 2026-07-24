import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'switch': 'switch', } };
        this.switches = [...this.$( 'switch' )];
        this.tabs = this.$( 'tabs' )[0];
        this.activeIndex = 0;
        if( this.tabs ){
            this.tabs = [...this.tabs.querySelectorAll( '& > div')];
        }
    }
    switch( e ){
        let newActiveIndex = this.switches.indexOf( e.currentTarget);
        this.tabs[ this.activeIndex ].classList.remove( 'active' );
        this.switches[ this.activeIndex ].classList.remove( 'active' );
        this.tabs[ newActiveIndex ].classList.add( 'active' );
        this.switches[ newActiveIndex ].classList.add( 'active' );
        this.activeIndex = newActiveIndex;
        window.dispatchEvent(new Event('resize'));

    }
}
