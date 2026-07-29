import { module } from 'modujs';
import axios from "axios";

export default class extends module {
  constructor(m) {
    super(m);

    // Accept either data-end (preferred) or legacy data-expires.
    this.expiresRaw = (this.el.dataset.end || this.el.dataset.expires || '').trim();
    this.expiresTimestamp = Number(this.el.dataset.expiresTimestamp) || 0;

    // Start datetime comes from HTML (e.g. when hold was created)
    this.startRaw = (this.el.dataset.start || '').trim();
    this.startTimestamp = Number(this.el.dataset.startTimestamp) || 0;
    this.expiryRedirect = (this.el.dataset.expiryRedirect || '').trim();

    // Optional fallback duration (ms) if start is not provided.
    const durationAttr = (this.el.dataset.durationMs || '').trim();
    this.fallbackDurationMs = durationAttr ? parseInt(durationAttr, 10) : null;
    this.displayEl = (this.$('display') && this.$('display')[0]) ? this.$('display')[0] : null;


    // Unit suffixes come from the template so they can be translated.
    this.units = {
      day: this.el.dataset.dayUnit || '',
      hour: this.el.dataset.hourUnit || '',
      minute: this.el.dataset.minuteUnit || '',
    };

    this._tickHandle = null;
    this._refreshHandle = null;


    // If we don't have an expiry, do nothing.
    if (!this.expiresTimestamp && !this.expiresRaw) {
      this.displayEl.textContent = '';
      return;
    }

    // Prefer the absolute server timestamp so fixed-offset WordPress timezones
    // stay correct across browser daylight-saving changes.
    this.expiresAt = this.expiresTimestamp
      ? new Date(this.expiresTimestamp * 1000)
      : new Date(this.expiresRaw.replace(' ', 'T'));

    if (Number.isNaN(this.expiresAt.getTime())) {
      this.displayEl.textContent = '';
      return;
    }

    this.startAt = null;
    if (this.startTimestamp || this.startRaw) {
      const parsedStart = this.startTimestamp
        ? new Date(this.startTimestamp * 1000)
        : new Date(this.startRaw.replace(' ', 'T'));
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
      this.el.classList.add('text-error');
      this.el.classList.remove('is-blinking');

      if (this._tickHandle) {
        window.clearInterval(this._tickHandle);
        this._tickHandle = null;
      }

      if (this.expiryRedirect) {
        window.location.assign(this.expiryRedirect);
        return;
      }

      this.scheduleCartRefresh();

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
      this.displayEl.textContent = `${hours}${this.units.hour} ${String(minutes).padStart(2, '0')}${this.units.minute}`;
    } else {
      this.displayEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }
  }

  scheduleCartRefresh() {
    const cart = this.el.closest('[data-module-cart]');
    const cartModuleId = cart?.dataset.moduleCart;

    if (!cart || !cartModuleId || cart.dataset.timerRefreshScheduled === '1') {
      return;
    }

    cart.dataset.timerRefreshScheduled = '1';
    this._refreshHandle = window.setTimeout(() => {
      try {
        this.call('refresh', false, 'Cart', cartModuleId);
      } catch (error) {
        window.location.reload();
      }
    }, 0);
  }

  destroy() {
    if (this._tickHandle) {
      window.clearInterval(this._tickHandle);
      this._tickHandle = null;
    }
    if (this._refreshHandle) {
      window.clearTimeout(this._refreshHandle);
      this._refreshHandle = null;
    }
  }
}
