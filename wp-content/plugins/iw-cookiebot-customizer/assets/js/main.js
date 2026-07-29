class CustomCookieConsent {

    constructor() {
        this.config = { ...window.CustomCookieConsentConfig };
        window.addEventListener( 'CookiebotOnDialogInit', this.onDialogInit.bind( this ) );
        window.addEventListener( 'CookiebotOnLoad', () => window.dispatchEvent( new Event( 'cookies-changed' ) ) );

        document.addEventListener( 'click', e => {
            if ( e && typeof e.target.dataset.showCookiesPopup !== 'undefined' ) {
                CookieConsent.renew();
            }
        } );
    }




    onDialogInit(){
        if( ! this.dialogInited ){
            this.initDialog();
            this.dialogInited = true;
        }
        this.showBanner();
        window.dispatchEvent( new Event( 'cookies-changed' ) );
    }

    initDialog(){

        document.body.insertAdjacentHTML( 'beforeend', this.html() );
        this.popup = document.getElementById( 'cookies-preferences-popup' );
        this.banner = document.getElementById( 'cookie-consent-banner' );
        this.settingsCheckboxes = document.querySelectorAll( 'input[name="cookies_settings[]"]' );
        this.tableWrappers = this.popup.querySelectorAll( '.table-wrapper' );
        this.placeholders = document.querySelectorAll( '[data-cookiebot-text]' );
        this.tabs = this.popup.querySelector( '.cookie-categories-tabs' );
        this.tabsTab = this.popup.querySelectorAll( '.cookie-categories-tabs > div' );
        this.tabsContents = this.popup.querySelectorAll( '.tab-cont' );
        this.activeTab = 0;


        document.querySelectorAll( '#CybotCookiebotDialog table' ).forEach( ( table, index ) => {
            if ( index === this.tableWrappers.length ) return;
            let tableWrapper = this.tableWrappers[ index ];
            table.removeAttribute( 'id' );
            table.removeAttribute( 'class' );
            tableWrapper.appendChild( table );
        } );
        this.placeholders.forEach( element => {
            let el = document.querySelector( element.dataset.cookiebotText );
            if ( el ) {
                element.innerHTML = el.innerHTML.replace( /^( |<br>)*(.*?)( |<br>)*$/, "$2" );
                ;
                if ( typeof element.dataset.cookiebotTextOnly !== 'undefined' ) {
                    element.innerHTML = element.innerHTML.replace( /\([0-9]*\)/, '' );
                }
            }

        } );

        this.addEvents();

    }





    addEvents() {

        document.addEventListener( 'click', this.showPopup.bind( this ) );
        document.addEventListener( 'click', this.hidePopup.bind( this ) );
        document.addEventListener( 'keyup', this.keyPress.bind( this ) );
        this.banner.addEventListener( 'click', this.submitConsent.bind( this ) );
        this.popup.addEventListener( 'click', this.submitConsent.bind( this ) );
        this.popup.addEventListener( 'click', this.tabClick.bind( this ) );
        this.popup.addEventListener( 'click', e => {
            if ( e.target.tagName === 'LABEL' ) {
                e.target.closest( '.custom-checkbox' ).classList.toggle( 'checked' );
            }
        } );
        this.tabs.addEventListener( 'click', e => {
            if ( typeof e.target.dataset.cookiebotTab !== 'undefined' ) {
                this.tabsTab[ this.activeTab ].classList.remove( 'active' );
                this.tabsContents[ this.activeTab ].classList.remove( 'active' );
                this.activeTab = [ ...e.target.parentElement.children ].indexOf( e.target );
                this.tabsTab[ this.activeTab ].classList.add( 'active' );
                this.tabsContents[ this.activeTab ].classList.add( 'active' );

            }
        } );
    }

    showBanner() {
        if ( !CookieConsent.hasResponse ) {
            if ( this.banner.classList.contains( 'active' ) ) {
                this.banner.classList.remove( 'active' );
                setTimeout( () => {
                    this.banner.classList.add( 'active' )
                }, 500 );
            } else {
                this.banner.classList.add( 'active' )
            }
        }
    }

    hideBanner() {
        this.banner.classList.remove( 'active' );
    }

    showPopup( e = false ) {
        if ( e && typeof e.target.dataset.showCookiesPreferences === 'undefined' ) return;
        [ CookieConsent.consent.preferences, CookieConsent.consent.statistics, CookieConsent.consent.marketing ].forEach( ( active, index ) => {
            this.settingsCheckboxes[ index ].closest( '.custom-checkbox' ).classList.toggle( 'checked', active );
        } );
        this.tabsTab[ 0 ].click();
        this.popup.classList.add( 'active' );
        this.hideBanner();
    }

    hidePopup( e ) {
        if ( e && typeof e.target.dataset.hideCookiesPreferences === 'undefined' ) return;
        this.popup.classList.remove( 'active' );
        this.showBanner();
    }

    keyPress( e ) {
        if ( e.key === 'Escape' ) this.hidePopup();
    }

    tabClick() {

    }

    submitConsent( e ) {
        if ( typeof e.target.dataset.submitCookies === 'undefined' ) return;
        let consent = e.target.dataset.submitCookies;
        if ( consent === 'all' ) CookieConsent.submitCustomConsent( true, true, true );
        else if ( consent === 'none' ) {
            CookieConsent.withdraw();
            CookieConsent.submitCustomConsent( false, false, false );
        } else {
            let settings = this.settingsCheckboxes;
            CookieConsent.withdraw();
            CookieConsent.submitCustomConsent( settings[ 0 ].checked, settings[ 1 ].checked, settings[ 2 ].checked )
        }
        this.hideBanner();
        this.hidePopup();
        window.dispatchEvent( new Event( 'cookies-changed' ) );
    }

    html() {

        let dialog = CookieConsent.dialog;
        let logo = this.config.logo ? `<img src="${ this.config.logo }" alt="${ this.config.logoAlt || '' }">` : '';
        let lastUpdateDate = new Date( dialog.lastUpdatedDate );
        let emptyTable =  '<p class="p note">' + ( dialog.lastUpdatedDate ? dialog.noCookiesTypeText : dialog.lastUpdatedText ) + '</p>';


        let tabs = '';
        let tabsCont = '';

        ['Necessary', 'Preference', 'Statistics', 'Advertising', 'Unclassified'].forEach( tab => {
            let rows = '';
            if( dialog['cookieTable' + tab + 'Count'] !== 0 ){
                dialog['cookieTable' + tab ].forEach( row =>{
                    rows += `
                        <div class="p table-row">
                            <div class="note">${row[0]}</div>
                            <div>
                                <div>${row[2]}
                                    <span>${dialog.cookieTableHeaderProvider}</span>: ${row[1]}. 
                                    <span>${dialog.cookieTableHeaderExpiry}</span>: ${row[3]}.
                                    <span>${dialog.cookieTableHeaderType}</span>: ${row[4]}.
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            let cookiesInfo = dialog['cookieTable' + tab + 'Count'] === 0  ? emptyTable : rows;


            tabs += `<div data-cookiebot-tab>${dialog['cookieHeaderType'+tab].replace( '({0})', '')} <span>${dialog['cookieTable' + tab + 'Count']}</span></div>`;
            tabsCont += `
                <div class="tab-cont">
                    <div class="c-tab-title-cont">
                        <div class="c-tab-title">${dialog['cookieHeaderType'+tab].replace( '({0})', '')}</div>
                        ` +  ( tab === 'Unclassified' ? '' : `
                        <div class="custom-checkbox ${ tab === 'Necessary' ? 'inactive checked' : ''}">` + (
                            tab === 'Necessary' ?
                            `<label for="cookies_necessary"><span>${ this.config.offCheckBoxLabel || '' }</span><span>${ this.config.alwaysCheckBoxLabel || '' }</span></label>
                            <input type="checkbox" id="cookies_necessary">`
                            :
                            `<label for="cookies_${tab}"><span>${ this.config.offCheckBoxLabel || '' }</span><span>${ this.config.onCheckboxLabel || '' }</span></label>
                            <input type="checkbox" id="cookies_${tab}" name="cookies_settings[]">`
                        )+`
                        </div>`) +`
                    </div>
                    <p>${dialog['cookieIntroType'+tab]}</p>
                    ${cookiesInfo}
                </div>
            `;
        });

        return `
            <div id="cookies-preferences-popup">
                <div class="make-full" data-hide-cookies-preferences></div>
                <div id="cookie-popup" class="gdpr-popup">
                    <div class="gdpr-popup-inner-wrapper">
                        <div class="inner">
                            <div class="inner-wrapper">
                                <div class="title-wrapper">
                                    <div>${ logo }</div>
                                    <span class="cookie-consent-close color" data-hide-cookies-preferences></span>
                                </div>
                                <div class="cookie-categories-tabs-cont" data-tab-container>
                                    <div class="cookie-categories-tabs" data-tabs>
                                        <div class="active" data-cookiebot-tab>${dialog.aboutCookiesText}</div>
                                        ${tabs}
                                    </div>
                                    <div class="cookie-categories-tabs-contents" data-tabs-content>
                                        <div class="tab-cont active">
                                            <div class="c-tab-title-cont">
                                                <div class="c-tab-title">${dialog.aboutCookiesText}</div>
                                            </div>
                                            <p>${dialog.cookieIntroText}</p>
                                            <p class="note">${ dialog.lastUpdatedText.replace( '{0}', lastUpdateDate.getDate() + '/' + lastUpdateDate.getMonth() + 1 +'/' + lastUpdateDate.getFullYear() ) }</p>
                                        </div>
                                        ${tabsCont}
                                    </div>
                                </div>
                                <div class="btn-area">
                                   
                                    <span class="cookie-consent-btn" data-submit-cookies="all">${dialog.acceptText}</span>
                                    <span class="cookie-consent-btn" data-submit-cookies="custom">${dialog.loiAllowSelectionText}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="cookie-consent-banner">
                <div class="left-side">
                    <p>${dialog.text}</p>
                </div>
                <div class="right-side">
                    <span class="banner-settings-wrapper">
                        <span class="banner-settings" data-show-cookies-preferences>${dialog.showDetailsText}</span>
                    </span>
                    <span class="cookie-consent-btn" data-submit-cookies="none">${dialog.declineText}</span>
                    <span class="cookie-consent-btn" data-submit-cookies="all">${dialog.acceptText}</span>
                </div>
            </div>
        `;
    }

}


new CustomCookieConsent();
