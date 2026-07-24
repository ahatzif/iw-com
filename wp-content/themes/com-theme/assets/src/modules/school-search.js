import { module } from 'modujs';
import axios from "axios";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'result': 'resultClick', } };
        this.resultTemplate = this.$('result')[0];
        if( ! this.resultTemplate ) return;
        this.resultTemplate.remove();
        this.resultTemplate.classList.remove('hidden');
        this.resultsContainer = this.$('results-container')[0];
        this.inputField = this.el.querySelector('input[type="text"]');
        this.inputFieldContainer = this.inputField.closest('.field');
        this.inputFieldSelection = this.el.querySelector('input[type="hidden"]');

        this.inputField.addEventListener('keyup', () => {
            let search = this.inputField.value;
            this.resultsContainer.classList.remove('hidden');
            if (search.length < 3) {
                this.resultsContainer.innerHTML = this.el.dataset.typeLettersPrompt;
            } else {
                this.resultsContainer.innerHTML = this.el.dataset.messageWait;
                this.inputFieldContainer.classList.add('loading');
                let formData = new FormData();
                formData.append('search', search);
                formData.append('action', this.el.dataset.action);
                axios.post(THEME_OBJ.ajaxURL, formData).then(response => {
                    let responseData = response.data;
                    this.inputFieldContainer.classList.remove('loading');

                    if (!responseData.success) {
                        this.resultsContainer.innerHTML = responseData.data.message;
                    } else {
                        this.resultsContainer.innerHTML = '';

                        for (let key in responseData.data.results) {
                            let clone = this.resultTemplate.cloneNode(true);
                            clone.dataset.id = key;
                            clone.dataset.name = responseData.data.results[key].name; // diko mou
                            clone.innerHTML = responseData.data.results[key].name;
                            clone.addEventListener('click', this.resultClick.bind(this))
                            clone.querySelectorAll('input[name^="school_"]').forEach(field => {
                                const fieldKey = field.name.replace('school_', '');
                                if (fieldKey in data) {
                                    field.value = data[fieldKey];
                                }
                            });
                            this.resultsContainer.appendChild(clone);

                        }
                    }

                });
            }

        });

        // this.inputField.addEventListener( 'blur', () => {
        //     this.resultsContainer.classList.add( 'hidden' );
        // });



    }

    resultClick(e) {
        this.inputFieldSelection.value = e.currentTarget.dataset.id; //diko mou
        this.inputField.value = e.currentTarget.dataset.name; //diko mou
        this.resultsContainer.classList.add('hidden'); // diko mou
    }
}
