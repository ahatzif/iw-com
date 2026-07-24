import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'btn': 'toggleClass', } };
        this.btn = this.$('btn');
    }
    init() {}
    toggleClass(e) {

        let elBtn = e.currentTarget;

        this.btn.forEach((btn) => {
        
            if(btn == elBtn){
                btn.classList.toggle('active');
            }else{
                btn.classList.remove('active');
            }
        
        });
        
    }
    destroy(){}
}
