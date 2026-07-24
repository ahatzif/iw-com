import { module } from 'modujs';
import { normalizeText } from 'normalize-text';


export default class extends module {
    constructor(m) {
        super(m);


        let inputs = this.$( 'input' );
        let results = [...this.$( 'result' )];
        if( inputs.length ){

            inputs[0].addEventListener( 'keyup', e => {
                const normalizedQuery = normalizeText(e.currentTarget.value);
                let foundOne = false;
                results.forEach( result => {
                    const normalizedTitle = normalizeText( result.getAttribute('title') );
                    let match = normalizedTitle.includes(normalizedQuery);
                    result.style.display = match ? 'block' : 'none';
                    if( match ) { foundOne = true; }
                });

                this.el.classList.toggle( 'no-results', ! foundOne );
            });
        }
    }


}
