import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.el.addEventListener( 'change', this.onChange.bind( this ) );
        this.fileInput = this.el.querySelector( 'input[type="file"]');
        this.filesContainer = this.$( 'files' )[0];
    }
    onChange(){
        this.filesContainer.classList.toggle( 'hidden', ! this.fileInput.files.length );
        while (this.filesContainer.firstChild) {
            this.filesContainer.removeChild(this.filesContainer.firstChild);
        }
        for( let file of this.fileInput.files ){
            let fileDiv = document.createElement('span');
            this.filesContainer.appendChild( fileDiv );
            fileDiv.outerHTML = `<span class="inline-flex text-12 leading-none items-center">${file.name}</span>`;

        }
    }
}
