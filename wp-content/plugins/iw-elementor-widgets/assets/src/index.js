import modular from 'modujs';
import * as modules from './modules/_all';
const app = new modular( { modules : modules } );

window.onload = () => {
    app.init( app );
};
