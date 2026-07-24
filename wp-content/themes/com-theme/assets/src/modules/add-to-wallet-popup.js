import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);


        this.el.addEventListener('click', (e) => {
            if( e.target.closest( '[data-btn]') ){
                e.stopPropagation();
                this.el.classList.toggle('active');
            } else if( ! e.target.closest( '[data-add-to-wallet-popup="close"]')){
                e.stopPropagation();
                this.el.classList.add('active');
            }

        });

        this.onClickOutsideBind = this.onClickOutside.bind( this );
        document.addEventListener('click', this.onClickOutsideBind );
    }

    onClickOutside() {
        this.el.classList.remove('active');
    };

    destroy() {
        document.removeEventListener('click', this.onClickOutsideBind );
    }
}
