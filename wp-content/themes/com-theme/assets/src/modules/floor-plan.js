import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.floorItem = [...this.$('floor-item')];
        this.active = this.floorItem[0];

        this.activeImage = document.querySelector('[data-floor-plan="floor-image"]')

        this.floorItem.forEach(item => {
            item.addEventListener('click', () => {
                this.active.classList.remove('active');
                this.active = item;
                this.active.classList.add('active');
                this.image = this.active.getAttribute('data-image');
                if(this.image){
                    this.activeImage.src = this.image;
                }
            });
        });
    }
}
