import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        let callParts = ( this.el.dataset.attrs ? this.el.dataset.attrs : this.el.dataset.moduleCall).split(',');
        this.moduleName = callParts[0];
        this.methodName = callParts[1];
        this.args = callParts.slice(2);
        this.el.addEventListener( 'click', (e) => {
            this.call(this.methodName, this.args, this.moduleName );
        });
    }

}
