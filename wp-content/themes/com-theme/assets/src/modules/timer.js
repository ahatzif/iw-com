import { module } from 'modujs';
import axios from "axios";

export default class extends module {
  constructor(m) {
    super(m);

    // Accept either data-end (preferred) or legacy data-expires.
    this.expiresRaw = (this.el.dataset.end || this.el.dataset.expires || '').trim();

    // Start datetime comes from HTML (e.g. when hold was created)
    this.startRaw = (this.el.dataset.start || '').trim();

    // Optional fallback duration (ms) if start is not provided.
    const durationAttr = (this.el.dataset.durationMs || '').trim();
    this.fallbackDurationMs = durationAttr ? parseInt(durationAttr, 10) : null;
    this.displayEl = (this.$('display') && this.$('display')[0]) ? this.$('display')[0] : null;


    // Detect document language (e.g. <html lang="el">)
    const docLang = (document.documentElement && document.documentElement.lang) ? document.documentElement.lang : '';
    this.lang = (docLang || 'en').toLowerCase();

    // Unit suffixes (keep them short for the small circle UI)
    this.units = { day: this.lang.startsWith('el') ? 'ημ' : 'd',  hour: this.lang.startsWith('el') ? 'ω' : 'h',};

    this._tickHandle = null;


    // If we don't have an expiry, do nothing.
    if (!this.expiresRaw) {
      this.displayEl.textContent = '';
      return;
    }

    // Parse "YYYY-MM-DD HH:mm:ss" as local time.
    const isoLocalEnd = this.expiresRaw.replace(' ', 'T');
    this.expiresAt = new Date(isoLocalEnd);

    if (Number.isNaN(this.expiresAt.getTime())) {
      this.displayEl.textContent = '';
      return;
    }

    this.startAt = null;
    if (this.startRaw) {
      const isoLocalStart = this.startRaw.replace(' ', 'T');
      const parsedStart = new Date(isoLocalStart);
      if (!Number.isNaN(parsedStart.getTime())) {
        this.startAt = parsedStart;
      }
    }



    this.tick();
    this._tickHandle = window.setInterval(() => this.tick(), 1000);
  }



  tick() {
    const now = new Date();
    const diffMs = this.expiresAt.getTime() - now.getTime();

    // Circular progress update (if SVG exists)

    if (diffMs <= 0) {
      this.displayEl.textContent = '00:00';

      if (this.circleProgress && this.circumference !== null) {
        this.circleProgress.style.strokeDashoffset = String(this.circumference);
      }

      this.el.classList.add('is-expired');
      this.el.classList.add('error');
      this.el.classList.remove('is-blinking');

      if (this._tickHandle) {
        window.clearInterval(this._tickHandle);
        this._tickHandle = null;
      }

        let cart = this.el.closest( '[data-module-cart]' );
        if( cart ){
            let moduleName = cart.dataset.moduleCart;
            if( moduleName === 'main' ){
                this.call( 'refresh', false, 'Cart', moduleName )
            }

        }

      return;
    }

    const totalSeconds = Math.max(0, Math.floor(diffMs / 1000));

    // Warning state: under 5 minutes remaining
    const fiveMinutesMs = 5 * 60 * 1000;
    if (diffMs <= fiveMinutesMs) {
      this.el.classList.add('text-error');
    } else {
      this.el.classList.remove('text-error');
    }

    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    this.el.classList.remove('is-expired');

    if (days > 0) {
      // e.g. "2d 04:12:33"
      this.displayEl.textContent = `${days}${this.units.day} ${String(hours).padStart(2, '0')}${this.units.hour}`;
    } else if (hours > 0) {
      const minuteUnit = this.lang.startsWith('el') ? 'λ' : 'm';
      this.displayEl.textContent = `${hours}${this.units.hour} ${String(minutes).padStart(2, '0')}${minuteUnit}`;
    } else {
      this.displayEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }
  }

  destroy() {
    if (this._tickHandle) {
      window.clearInterval(this._tickHandle);
      this._tickHandle = null;
    }
  }
}
