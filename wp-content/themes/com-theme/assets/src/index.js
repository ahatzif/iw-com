//require( './signature' );

import { isMobile } from 'mobile-device-detect';
import modular from 'modujs';
import * as modules from './modules/_all';
import Emitter from "tiny-emitter/instance";

const app = new modular( { modules : modules } );

const html = document.documentElement;

document.addEventListener( 'click', () => {
    Emitter.emit('click-outside', false );
});

window.onload = () => {
    app.init( app );
    html.classList.add('is-loaded');
    html.classList.add('is-ready');
    html.classList.remove('is-loading');
    html.classList.toggle( 'is-mobile', isMobile );
};
