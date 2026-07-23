let themeConfig = require('./theme.config.json');
function get_theme_colors( ){
    const modifiedObj = {};
    for (const key in themeConfig.colors) {
        modifiedObj[ key ] = themeConfig.colors[key];
    }
    return modifiedObj;
}

function get_theme_text_styles( ){
    const modifiedObj = {};
    for (const key in themeConfig.text) {
        modifiedObj[ key ] = themeConfig.text[key];
    }
    return modifiedObj;
}

function get_blocks_spacing( ){
    const modifiedObj = {};
    for (const key in themeConfig.spacing) {
        modifiedObj[ key ] = (themeConfig.spacing[key]/10) + 'rem';
    }
    return modifiedObj;
}

function get_spacing(columns, padding = '' ) {
    let obj = {};
    for (let i = 1; i <= columns; i++) {
        let pad = padding !== '' ? ` - ${padding}*${i}/${columns}` : '';
        obj[`${i}/${columns}`] = `calc(var(--vw)*${i}/${columns}${pad})`;
    }
    return obj;
}

function generateSpacings(increment, max) {
    const result = {
        0 : '0', 1 : '1px', 2 : '2px'
    };
    for (let i = 0; i <= max; i += increment) {
        result[ i ] = (i/10 + 'rem');
    }
    return result;
}

module.exports = { get_theme_colors, get_spacing, get_theme_text_styles, generateSpacings, get_blocks_spacing };
