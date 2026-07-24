import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.copiedText = this.el.dataset.copiedText;
        this.copyText = this.el.dataset.copyText;
        this.failedText = this.el.dataset.failedText;
        this.text = this.el.querySelector( '[data-button-text]' );

        this.el.addEventListener( 'click', () => {

            let url = this.el.dataset.href;
            if( !  url ){
                url = window.location.href;
            }

            navigator.clipboard.writeText( url ).then(() => {
                this.text.textContent = this.copiedText;
                setTimeout(() => { this.text.textContent = this.copyText; }, 1000);
            }).catch(err => {
                this.text.textContent = this.copiedText;
                this.text.textContent = this.failedText;
                setTimeout(() => { this.text.textContent = this.copyText; }, 1000);
            });


        });
    }

}
