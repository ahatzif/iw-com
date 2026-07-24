import { module } from 'modujs';
import axios from 'axios';
export default class extends module {
    constructor(m) {
        super(m);
        let productId = this.el.dataset.productId;
        axios.get( 'https://backend.benakishop.gr/el/api/v1/products/' + productId  ).then( ( response) => {
            let product = response.data;
            let url = product.metaTags.canonical_url;
            if( this.el.tagName=== 'A') {
                this.el.href = url;
                return;
            }
            this.$( 'title' )[0].innerHTML = `<a href="${url}" target="_blank">${product.title}</a>`;
            this.$( 'description' )[0].textContent = this.getFirstParagraphContent( product.description );
            this.$( 'photo' )[0].style.backgroundImage = `url("${product.default_variation.variation_media[0].url}")`;
            this.$( 'photo' )[0].innerHTML = `<a href="${url}" target="_blank" title="${product.title}"></a>`;
        });
    }


    getFirstParagraphContent(html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const firstParagraph = doc.querySelector('p');
        return firstParagraph ? firstParagraph.textContent.trim() : null;
    }
}
