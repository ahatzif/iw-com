import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.tabs = document.querySelectorAll('[data-tab-number]');
        this.images = document.querySelectorAll('[data-images-number]');

        this.activeTab = this.tabs[0];
        this.activeImage = this.images[0];

        this.tabs.forEach(tab => {
            tab.addEventListener('click', () => {

                this.activeTab.classList.remove('active');

                this.activeTab = tab;
                this.activeTabNumber = this.activeTab.getAttribute('data-tab-number');
                this.activeTab.classList.add('active');

                this.images.forEach(img => {
                    if (img.getAttribute('data-images-number') === this.activeTabNumber) {
                        img.classList.add('active');
                        this.activeImage = img;
                    } else {
                        img.classList.remove('active');
                    }
                });
            });
        });
    }
}
