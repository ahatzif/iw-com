import { module } from 'modujs';


export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'toggle-search': 'toggleSearch' } };
        this.input = this.$( 'search-input' )[0];
    }
    toggleSearch(){
        document.body.classList.contains( 'search-open' ) ?  this.close() : this.open();
    }
    close(){
        this.input.blur();
        document.body.classList.remove( 'search-open'  );
    }
    open(){
        this.input.focus();
        document.body.classList.add( 'search-open'  );
    }
}
