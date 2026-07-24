import { module } from 'modujs';
import QRCode from 'qrcode';

export default class extends module {
    constructor(m) {
        super(m);
        this.makeQR();

    }

    async makeQR(){
        const value = this.el.dataset.value;
        const dark = '#31312F';
        const light = '#ffffff';
        if ( ! value ) return;
        const svg = await QRCode.toString(value, {type: 'svg', errorCorrectionLevel: 'M', margin: 0, color: { dark, light },});
        this.el.innerHTML = svg;
    }
}