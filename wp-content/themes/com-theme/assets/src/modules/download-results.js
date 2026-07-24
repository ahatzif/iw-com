import { module } from 'modujs';
import axios from 'axios';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'button': 'downloadResults' } };
        this.resultsLimit = parseInt(this.el.dataset.resultsLimit);
        this.timeout = false;
    }

    downloadResults() {
        if (this.el.classList.contains('animate-loading')) {
            return;
        }

        let resultsFoundElement = document.querySelector('[data-collections-advanced-search="results-found"]');
        let resultsFound = resultsFoundElement ? parseInt(resultsFoundElement.textContent) : 0;
        if (this.timeout) window.clearTimeout(this.timeout);
        if (resultsFound > this.resultsLimit) {
            this.el.classList.add('error');
            this.timeout = window.setTimeout(() => { this.el.classList.remove('error'); }, 5000);
        } else {
            this.el.classList.add('animate-loading');
            const currentUrl = new URL(window.location.href);
            const queryString = currentUrl.search.replace('?', '&');

            const ajaxUrlWithParams = `${THEME_OBJ.ajaxURL}?action=download_item_results${queryString}`;

            axios.get(ajaxUrlWithParams, { responseType: 'blob' })
                .then(response => {
                    const contentDisposition = response.headers['content-disposition'];
                    let filename = 'results.csv';
                    if (contentDisposition) {
                        const matches = contentDisposition.match(/filename="(.+)"/);
                        if (matches && matches[1]) {
                            filename = matches[1];
                        }
                    }
                    const url = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', filename);
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    this.el.classList.remove('animate-loading');
                })
                .catch(error => {
                    this.el.classList.remove('animate-loading');
                });
        }
    }
}
