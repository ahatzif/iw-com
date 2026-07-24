import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'scroll-to': 'scroll', } };
        console.info( this );
    }
    init() {}
    destroy(){}
}
