import { module } from 'modujs';
import axios from 'axios';


export default class extends module {
    constructor(m) {
        super(m);
        this.resultsSelector = this.el.dataset.target;
        this.resultsDiv = document.querySelector(this.el.dataset.target);
        this.buildLinks();
    }
    buildLinks() {
        [...this.el.querySelectorAll('a')].forEach(a => a.addEventListener('click', this.onClick.bind(this)));
    }
    onClick(e) {
        e.preventDefault();
        let target = e.currentTarget;

        // let url = this.el.href.split("?")[0] + 'page/' + this.page + '/' + (this.el.dataset.params || '');
        axios.get(target.href).then((response) => {
            target.classList.remove('animate-loading');
            const doc = document.createElement("div");
            doc.innerHTML = response.data;
            doc.paging = doc.querySelector("#" + this.el.id).innerHTML;
            this.el.innerHTML = doc.paging;
            this.buildLinks();
            doc.innerHTML = doc.querySelector(this.resultsSelector).innerHTML;

            let newDivs = [];
            this.resultsDiv.innerHTML = '';
            [...doc.querySelectorAll('& > div')].forEach(div => {
                div.classList.add('ajax-loaded');
                newDivs.push(div);
                this.resultsDiv.append(div);
            });
            this.call('update', false, 'Scroll');
            this.call('updateLazy', false, 'Scroll');
            this.call('addParallaxImages', this.resultsDiv.querySelectorAll(".ajax-loaded [data-parallax]"), 'Scroll');

            newDivs.forEach(div => div.classList.remove('ajax-loaded'));
        });
    }
}
