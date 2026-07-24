import { module } from 'modujs';
import axios from "axios";

export default class extends module {
    constructor(m) {
        super(m);
        this.openings = [];
        this.openingIds = [];
        [...this.$('opening')].forEach(opening => {
            this.openingIds.push(opening.dataset.id);
            this.openings[ opening.dataset.id ] = opening;
        });
        if( ! this.openingIds.length ){
            return;
        }

        let formData = new FormData();
        formData.append('action', 'check_opening_hours');

        this.openingIds.forEach(id => {
            formData.append('opening_ids[]', id);
        });

        axios.post(THEME_OBJ.ajaxURL, formData).then(response => {
            let data = response.data.data;
            for (let openingId in data) {
                if (data.hasOwnProperty(openingId)) {
                    let openingStatus = data[openingId];

                    let opening = this.openings[openingId];
                    opening.innerHTML = ! openingStatus.isOpen && openingStatus.nextOpen ?  openingStatus.nextOpen : openingStatus.message;
                    let parent = opening.closest( '.animate-loading' );
                    parent.classList.remove( 'animate-loading' );
                    parent.classList.add( openingStatus.isOpen ? 'open' : 'closed' );
                }
            }
        });
    }
}
