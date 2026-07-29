import axios from 'axios';
import { module } from 'modujs';
import Info from "three/src/renderers/common/Info.js";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'link': 'linkClick', } };
        this.content = this.$( 'content' )[0];
        this.notices = this.$( 'notices' )[0];
        this.html = document.getElementsByTagName('html')[0];
        this.currentEndpoint = this.getEndpointClass(this.el);
        this.onWishlistUpdated = this.onWishlistUpdated.bind(this);
        window.addEventListener('iw:wishlist-updated', this.onWishlistUpdated);

    }
    linkClick(e) {

        e.preventDefault();

        const link = e.currentTarget;
        let targetLink = link.href;

        this.loadPage(targetLink);
    }
    loadPage(targetLink, updateHistory = true, scrollToAccount = true) {
        this.html.classList.add('is-loading');

        axios.get( targetLink, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, responseType: 'text'}).then(response => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(response.data, 'text/html');
            const newContent = doc.querySelector('[data-account-pages="content"]');
            const newNotices = doc.querySelector('[data-account-pages="notices"]');
            const newAccountPages = doc.querySelector('[data-module-account-pages]');
            const newContentActiveLink = doc.querySelector('[data-account-pages="link-li"].is-active a');
            if (!newContent) throw new Error('Account content not found in response');

            let href = newContentActiveLink ? newContentActiveLink.href : targetLink;


            this.content.innerHTML = newContent.innerHTML;
            if (this.notices) {
                this.notices.innerHTML = newNotices?.innerHTML ?? '';
            }
            this.call('update', this.content, 'app');
            this.call('updateLazy', false, 'Scroll');
            if (scrollToAccount) {
                this.call('scrollTo', { target: this.content, options: { offset: -document.querySelector( 'header' ).offsetHeight - 20 } }, 'Scroll');
            }
            this.el.querySelectorAll('.is-active[data-account-pages="link-li"]').forEach(li => li.classList.remove('is-active'));
            this.el.querySelectorAll(`[href="${href}"]`).forEach(a => a.closest('li')?.classList.add('is-active'));
            this.updateEndpointClass(newAccountPages);
            if (updateHistory) {
                this.call( 'addToHistoryOpts', {url : targetLink, trigger : 'barba', action : 'push'}, 'Load' );
            } else if (this.currentEndpoint === 'wishlist') {
                this.replaceWishlistUrl(newContent);
            }
        }).catch(err => {
            window.location.href = targetLink;

        })
        .finally(() => {
            this.html.classList.remove('is-loading');
        });
    }
    onWishlistUpdated() {
        if (this.currentEndpoint !== 'wishlist') return;

        this.loadPage(this.getWishlistRefreshUrl(), false, false);
    }
    getWishlistRefreshUrl() {
        const wishlistPage = this.content.querySelector('[data-wishlist-page]');
        if (!wishlistPage) return window.location.href;

        const baseUrl = wishlistPage.dataset.baseUrl;
        const currentPage = parseInt(wishlistPage.dataset.currentPage, 10) || 1;
        const visibleItems = this.content.querySelectorAll('#ajax-results-wishlist-products .product-card').length;
        if (!baseUrl || currentPage <= 1 || visibleItems > 1) return window.location.href;

        return currentPage > 2 ? `${baseUrl.replace(/\/+$/, '')}/page/${currentPage - 1}/` : baseUrl;
    }
    replaceWishlistUrl(content) {
        const wishlistPage = content.querySelector('[data-wishlist-page]');
        if (!wishlistPage) return;

        const baseUrl = wishlistPage.dataset.baseUrl;
        const currentPage = parseInt(wishlistPage.dataset.currentPage, 10) || 1;
        if (!baseUrl) return;

        const url = currentPage > 1 ? `${baseUrl.replace(/\/+$/, '')}/page/${currentPage}/` : baseUrl;
        window.history.replaceState({ path: url }, '', url);
    }
    updateEndpointClass(source) {
        const endpoint = this.getEndpointClass(source);
        if (!endpoint) return;

        if (this.currentEndpoint) {
            this.el.classList.remove(this.currentEndpoint);
        }

        this.el.classList.add('endpoint', endpoint);
        this.currentEndpoint = endpoint;
    }
    getEndpointClass(el) {
        if (!el) return null;

        const classes = Array.from(el.classList);
        const endpointIndex = classes.indexOf('endpoint');

        return endpointIndex === -1 ? null : classes[endpointIndex + 1];
    }
    toRelative(url){
        try {
            const u = new URL(url, window.location.origin);
            return `${u.pathname}${u.search}${u.hash}`;
        } catch (err) {
            return url;
        }
    };
    destroy() {
        window.removeEventListener('iw:wishlist-updated', this.onWishlistUpdated);
    }
}
