import { module } from 'modujs';
import filmNode from "three/addons/tsl/display/FilmNode.js";
import axios from "axios";
import { forestgreen } from "../../../../../../wp-includes/js/codemirror/csslint.js";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'button': 'buttonClick' } };
        this.modal = this.$('modal')[0];
        this.showCreateForm = this.$( 'show-create-form')[0];
        this.checkboxTemplate = this.$( 'checkbox-template' )[0];
        this.checkboxTemplateClone = this.checkboxTemplate.cloneNode( true );
        this.checkboxTemplate.remove();
        this.collectionsList = this.$( 'list' )[0];
        this.itemId = parseInt( this.el.dataset.itemId );


        this.collectionsList.addEventListener( 'change', e => {
            if (e.target.type === 'checkbox') {

                let checkbox = e.target;
                let field = checkbox.closest( '.field' );
                let label = field.querySelector( 'label' );
                let labelText = field.querySelector( '[data-checkbox-label]' );


                field.classList.add( 'pointer-events-none' );
                labelText.classList.add( 'animate-loading' );


                let formData = new FormData();
                formData.append( 'action', this.collectionsList.dataset.action );
                formData.append( 'action_type', e.target.checked ? 'add' : 'remove' );
                formData.append( 'item_id', this.itemId );
                formData.append( 'collection_id', e.target.value );

                axios.post(THEME_OBJ.ajaxURL, formData ).then( response => {
                    let resp = response.data;

                    field.classList.remove( 'pointer-events-none' );
                    labelText.classList.remove( 'animate-loading' );
                });

            }
        });

        document.body.appendChild( this.modal);

        this.showCreateForm.addEventListener( 'click', () => {
            this.modal.classList.add( 'show-create-form' );
        })
    }

    buttonClick( e ) {
        let button = e.target.closest( '.btn' );
        button.classList.add( 'loading' );
        let formData = new FormData();
        formData.append( 'action', button.dataset.action );
        axios.post(THEME_OBJ.ajaxURL, formData ).then( response => {
            let resp = response.data;
            button.classList.remove( 'loading' );
            this.modal.classList.toggle( 'logged-in', resp.success );
            if (resp.success) {
                this.showCollections( resp.data.collections );
            }
            this.openModal();
        });
    }

    showCollections( collections ){
        this.collectionsList.innerHTML = '';
        collections.forEach( collection => {

            let clone = this.checkboxTemplateClone.cloneNode( true );
            clone.querySelector( '[data-checkbox-label]' ).textContent = collection.name;
            let input = clone.querySelector( 'input[name="collection-id"]' );
            input.value = collection.id;
            if( collection.items.includes( this.itemId )  ){
                input.checked = 'checked';
            }
            this.collectionsList.appendChild( clone );
        });
    }

    openModal(){
        this.modal.classList.remove( 'show-create-form' );
        this.call('showModal', false, 'Modal', this.modal.dataset.moduleModal );
    }

    destroy() { this.modal.remove(); }
}
