//require( './signature' );

import { isMobile, isIOS, isMacOs } from 'mobile-device-detect';
import modular from 'modujs';
import * as modules from './modules/_all';
import Emitter from "tiny-emitter/instance";

const app = new modular({ modules: modules });

const normalizeActiveModuleIds = () => {
    if (!app.currentModules || !app.activeModules) return;

    Object.entries(app.currentModules).forEach(([moduleKey, moduleInstance]) => {
        const separatorIndex = moduleKey.indexOf('-');
        if (separatorIndex === -1) return;

        const moduleName = moduleKey.slice(0, separatorIndex);
        const moduleId = moduleKey.slice(separatorIndex + 1);
        if (!moduleName || !moduleId) return;

        if (!app.activeModules[moduleName]) {
            app.activeModules[moduleName] = {};
        }

        app.activeModules[moduleName][moduleId] = moduleInstance;
    });
};

const update = app.update.bind(app);
app.update = (scope) => {
    app.newModules = {};
    const result = update(scope);
    normalizeActiveModuleIds();
    app.newModules = {};

    return result;
};

window.app = app;

const html = document.documentElement;

document.addEventListener( 'click', e => Emitter.emit('click-outside', e ) );

window.onload = () => {
    app.init( app );
    normalizeActiveModuleIds();
    html.classList.add('is-loaded');
    html.classList.add('is-ready');
    html.classList.remove('is-loading');
    html.classList.toggle( 'is-mobile', isMobile );
    html.classList.toggle( 'ios', isIOS  );
    html.classList.toggle( 'MacOs', isMacOs  );

};
