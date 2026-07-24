import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        // Parse the JSON index data
        this.entries = JSON.parse(this.el.dataset.json);
        // Find the index container and pages collection
        this.index = this.$('index')[0];
        this.pages = this.el.querySelectorAll('[data-page]');
    }

    init() {
        this.scrollBar = this.el.querySelector('[data-module-scrollbar="manuscript-indexing"]');
        if (this.scrollBar) {
            this.scrollBar = this.scrollBar.dataset.moduleScrollbar;
            this.index.addEventListener('click', (event) => {
                const ref = event.target.closest('span.index-ref');
                if ( ! ref) return;
                const pageIndex = parseInt(ref.dataset.pageNumber, 10) - 1;
                this.goToPage(pageIndex);
            });
        }
    }



    goToPage(pageNumber) {
        const targetPageEl = this.pages[pageNumber];
        if (targetPageEl) {
            this.call('scrollIntoView', targetPageEl, 'Scrollbar', this.scrollBar);
        }
    }
}
