import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.items = this.$('item');
        this.container = this.el;
    }

    init() {
        this.findRightItems();
        window.addEventListener('resize', () => this.findRightItems());
    }

    findRightItems(){
        if (window.innerWidth < 500) {
            this.items.forEach((el) => {
                el.style.backgroundColor = '';
                el.style.flexDirection = '';
            });
            return;
        }

        const containerRect = this.container.getBoundingClientRect();
        const columnWidth = this.container.clientWidth / 2;
        this.items.forEach((el) => {
            const itemX = el.getBoundingClientRect().x;
            const relativeX = itemX - containerRect.x;
            const isRight = relativeX >= columnWidth;
            el.style.flexDirection = '';
            if (isRight) {
				el.style.flexDirection = 'column-reverse';
            }
        });
    }

    destroy(){
        window.removeEventListener('resize', this.findRightItems);
    }
}
