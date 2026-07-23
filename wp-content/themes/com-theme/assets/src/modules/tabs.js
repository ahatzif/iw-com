import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'switch': 'switch', } };
        this.tabs = this.$( 'tabs' )[0];
        this.activeTabIndex = 0;
        if( this.tabs ){
            this.tabs = [...this.tabs.querySelectorAll( '& > div')];
        }
    }
    switch( e ){
        if( e.target.dataset.tab ){
            let newTabIndex = parseInt( e.target.dataset.tab ) - 1;
            this.tabs[ this.activeTabIndex ].classList.add( 'hidden' );
            this.tabs[ newTabIndex ].classList.remove( 'hidden' );
            this.activeTabIndex = newTabIndex;
        }
    }
}
