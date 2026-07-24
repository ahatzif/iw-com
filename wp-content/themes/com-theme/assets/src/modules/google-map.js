import { module } from 'modujs';
// import {Client} from "@googlemaps/google-maps-services-js";


export default class extends module {
    constructor(m) {
        super(m);
        const client = new Client({});
        client
            .elevation({ params: { locations: [{ lat: this.el.dataset.lat, lng: this.el.dataset.lng }], key: 'AIzaSyA9U5gmTaZSlZH2San8-q9DdzUkDh5l53I', }, timeout: 1000 })
            .then((r) => {
                console.log(r.data.results[0].elevation);
            })
            .catch((e) => {
                console.log(e);
            });
    }
}
