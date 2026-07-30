import QrScanner from '../vendor/qr-scanner.min.js';

QrScanner.WORKER_PATH = new URL( '../vendor/qr-scanner-worker.min.js', import.meta.url ).toString();

const config = window.IWScanner || {};
const scanBtn = document.getElementById( 'scanBtn' );
const preview = document.getElementById( 'preview' );
const resultBox = document.getElementById( 'result' );
const resultMessage = document.getElementById( 'result-message' );
const loading = document.getElementById( 'loading' );
const cameraPlaceholder = document.getElementById( 'camera-placeholder' );
const scannerCloseButton = document.querySelector( '[data-scanner-close]' );
const scannerStatus = document.getElementById( 'scanner-status' );
const scannerShell = document.querySelector( '[data-scanner-shell]' );
const bootLoader = document.querySelector( '[data-scanner-boot-loader]' );
const navButtons = Array.from( document.querySelectorAll( '[data-scanner-nav]' ) );
const appViews = Array.from( document.querySelectorAll( '[data-scanner-view]' ) );
const appViewNames = new Set( appViews.map( ( panel ) => panel.getAttribute( 'data-scanner-view' ) ).filter( Boolean ) );
const locationSheet = document.querySelector( '[data-location-sheet]' );
const locationOpenButtons = Array.from( document.querySelectorAll( '[data-location-open]' ) );
const locationCloseButtons = Array.from( document.querySelectorAll( '[data-location-close]' ) );
const locationSearch = document.querySelector( '[data-location-search]' );
const locationList = document.querySelector( '[data-location-list]' );
const locationDetect = document.querySelector( '[data-location-detect]' );
const locationStatus = document.querySelector( '[data-location-status]' );
const selectedBuildingNameNodes = Array.from( document.querySelectorAll( '[data-selected-building-name]' ) );
const selectedBuildingAddressNodes = Array.from( document.querySelectorAll( '[data-selected-building-address]' ) );
const scanHistoryList = document.querySelector( '[data-scan-history-list]' );
const manualSearchSheet = document.querySelector( '[data-manual-search-sheet]' );
const manualSearchOpenButtons = Array.from( document.querySelectorAll( '[data-manual-search-open]' ) );
const manualSearchCloseButtons = Array.from( document.querySelectorAll( '[data-manual-search-close]' ) );
const manualSearchForm = document.querySelector( '[data-manual-search-form]' );
const manualSearchInput = document.querySelector( '[data-manual-search-input]' );
const manualSearchMessage = document.querySelector( '[data-manual-search-message]' );
const manualSearchResults = document.querySelector( '[data-manual-search-results]' );
const memberTicketsSheet = document.querySelector( '[data-member-tickets-sheet]' );
const memberTicketsCloseButtons = Array.from( document.querySelectorAll( '[data-member-tickets-close]' ) );
const memberTicketsList = document.querySelector( '[data-member-tickets-list]' );
const accountSheet = document.querySelector( '[data-account-sheet]' );
const accountOpenButtons = Array.from( document.querySelectorAll( '[data-account-open]' ) );
const accountCloseButtons = Array.from( document.querySelectorAll( '[data-account-close]' ) );
const accountForm = document.querySelector( '[data-account-form]' );
const accountMessage = document.querySelector( '[data-account-message]' );
const accountSummaryNodes = Array.from( document.querySelectorAll( '[data-account-summary]' ) );
const accountPhoneForm = document.querySelector( '[data-account-phone-form]' );
const accountPendingPhone = document.querySelector( '[data-account-pending-phone]' );
const accountPhoneMessage = document.querySelector( '[data-account-phone-message]' );
const accountPhoneResend = document.querySelector( '[data-account-phone-resend]' );
const passwordSheet = document.querySelector( '[data-password-sheet]' );
const passwordOpenButtons = Array.from( document.querySelectorAll( '[data-password-open]' ) );
const passwordCloseButtons = Array.from( document.querySelectorAll( '[data-password-close]' ) );
const passwordForm = document.querySelector( '[data-password-form]' );
const passwordMessage = document.querySelector( '[data-password-message]' );
const fullscreenAction = document.querySelector( '[data-fullscreen-action]' );
const fullscreenStatus = document.querySelector( '[data-fullscreen-status]' );
const scannerRibbonStack = document.querySelector( '[data-scanner-ribbons]' );
const notificationsOpenButtons = Array.from( document.querySelectorAll( '[data-notifications-open]' ) );
const notificationsBadge = document.querySelector( '[data-notifications-badge]' );
const notificationsList = document.querySelector( '[data-notifications-list]' );
const notificationsRefreshButton = document.querySelector( '[data-notifications-refresh]' );
const notificationsPullHint = document.querySelector( '[data-notifications-pull-hint]' );
const notificationSheet = document.querySelector( '[data-notification-sheet]' );
const notificationCloseButtons = Array.from( document.querySelectorAll( '[data-notification-close]' ) );
const notificationDetail = document.querySelector( '[data-notification-detail]' );
const notificationDetailTitle = document.querySelector( '[data-notification-detail-title]' );
const notificationDetailMeta = document.querySelector( '[data-notification-detail-meta]' );
const notificationDetailContent = document.querySelector( '[data-notification-detail-content]' );
const notificationDetailImage = document.querySelector( '[data-notification-detail-image]' );
const notificationUnreadButton = document.querySelector( '[data-notification-unread]' );
const themeSheet = document.querySelector( '[data-theme-sheet]' );
const themeOpenButtons = Array.from( document.querySelectorAll( '[data-theme-open]' ) );
const themeCloseButtons = Array.from( document.querySelectorAll( '[data-theme-close]' ) );
const themeModeButtons = Array.from( document.querySelectorAll( '[data-theme-mode]' ) );
const themeModeSummary = document.querySelector( '[data-theme-mode-summary]' );
const themeModeIcon = document.querySelector( '[data-theme-mode-icon]' );
const languageSheet = document.querySelector( '[data-language-sheet]' );
const languageOpenButtons = Array.from( document.querySelectorAll( '[data-language-open]' ) );
const languageCloseButtons = Array.from( document.querySelectorAll( '[data-language-close]' ) );
const languageModeButtons = Array.from( document.querySelectorAll( '[data-language-mode]' ) );
const languageSummary = document.querySelector( '[data-language-summary]' );
const appUpdateAction = document.querySelector( '[data-app-update-action]' );
const appVersionStatus = document.querySelector( '[data-app-version-status]' );
const logoutSheet = document.querySelector( '[data-logout-sheet]' );
const logoutOpenButtons = Array.from( document.querySelectorAll( '[data-logout-open]' ) );
const logoutCloseButtons = Array.from( document.querySelectorAll( '[data-logout-close]' ) );
const scannerSheets = Array.from( document.querySelectorAll( '.scanner-location-sheet' ) );

let scanner;
let isStarting = false;
let activeView = 'scan';
let enteredFromLogin = false;
let selectedBuilding;
let scanHistory = [];
let accountResendTimer = null;
let deferredInstallPrompt = null;
let heartbeatTimer = null;
let notificationsAutoRefreshTimer = null;
let heartbeatInFlight = false;
let accessRevoked = false;
let scannerNotifications = [];
let notificationsLoaded = false;
let notificationsInFlight = false;
let notificationsRefreshing = false;
let manualSearchCachedQuery = '';
let manualSearchCachedMatches = [];
let manualSearchPointerSelection = null;
let manualSearchSuppressClickUntil = 0;
let currentMemberTickets = [];
let currentMemberResultContext = null;
let currentTicketReturnTarget = '';
let currentTicketReturnIndex = -1;
let currentResultType = '';
let forceTicketScanPending = false;
let resetTicketScanPending = false;
let notificationsPullStartY = null;
let notificationsPullDistance = 0;
let notificationDetailRequestId = 0;
let currentNotificationDetailId = '';
let activeSheetDrag = null;
let sessionRefreshPending = false;
let translations = {};
let currentLocale = 'el';
let currentThemeMode = 'default';
let fullscreenStatusState = { key: 'settings.fullscreenCopy', params: {} };
let appVersionStatusState = { key: 'version.ready', params: { version: config.appVersionLabel || config.appVersion || '' } };
const currentUser = { ...( config.currentUser || {} ) };

const buildingStorageKey = 'iwScannerBuildingId';
const historyStorageKey = 'iwScannerHistory';
const themeStorageKey = 'iwScannerThemeMode';
const languageStorageKey = 'iwScannerLanguage';
const activeViewStorageKey = 'iwScannerActiveView';
const dismissedNoticesStorageKey = 'iwScannerDismissedNotices';
const notificationsAutoRefreshInterval = 10 * 60 * 1000;
const notificationsPullThreshold = 112;
const notificationsPullMax = 142;
const dismissedNoticeIds = new Set( loadDismissedNoticeIds() );
const themePreferenceQuery = window.matchMedia?.( '(prefers-color-scheme: dark)' ) || null;
const sheetCloseJobs = new WeakMap();
const buildings = Array.isArray( config.buildings )
  ? config.buildings.map( normalizeBuilding ).filter( ( building ) => building.id || building.title )
  : [];

function finishBootLoader() {
  scannerShell?.classList.add( 'scanner-phone--ready' );
  scannerShell?.classList.remove( 'scanner-phone--booting' );
  bootLoader?.setAttribute( 'aria-hidden', 'true' );

  window.setTimeout( () => {
    if ( scannerShell?.classList.contains( 'scanner-phone--ready' ) ) {
      bootLoader?.setAttribute( 'hidden', '' );
    }
  }, 280 );
}

try {
  if ( window.sessionStorage.getItem( 'iwScannerEntry' ) === 'from-login' ) {
    enteredFromLogin = true;
    window.sessionStorage.removeItem( 'iwScannerEntry' );
    scannerShell?.classList.add( 'scanner-entry-from-login' );
    window.setTimeout( () => scannerShell?.classList.remove( 'scanner-entry-from-login' ), 620 );
  }
} catch ( error ) {
  // Session storage can be unavailable in strict privacy contexts.
}

const escapeHtml = ( value ) => String( value ?? '' )
  .replace( /&/g, '&amp;' )
  .replace( /</g, '&lt;' )
  .replace( />/g, '&gt;' )
  .replace( /"/g, '&quot;' )
  .replace( /'/g, '&#039;' );

function sanitizeInlineHtml( value ) {
  const template = document.createElement( 'template' );
  template.innerHTML = String( value ?? '' );
  const allowedTags = new Set( [ 'SPAN', 'STRONG', 'B', 'EM', 'BR' ] );
  const allowedClasses = new Set( [ 'font-bold' ] );

  const cleanNode = ( node ) => {
    Array.from( node.childNodes ).forEach( ( child ) => {
      if ( child.nodeType === Node.COMMENT_NODE ) {
        child.remove();
        return;
      }

      if ( child.nodeType !== Node.ELEMENT_NODE ) {
        return;
      }

      if ( ! allowedTags.has( child.tagName ) ) {
        child.replaceWith( document.createTextNode( child.textContent || '' ) );
        return;
      }

      const classes = Array.from( child.classList ).filter( ( className ) => allowedClasses.has( className ) );
      Array.from( child.attributes ).forEach( ( attr ) => child.removeAttribute( attr.name ) );
      if ( child.tagName !== 'BR' ) {
        if ( classes.length ) {
          child.className = classes.join( ' ' );
        }
      }

      cleanNode( child );
    } );
  };

  cleanNode( template.content );
  return template.innerHTML;
}

function interpolate( text, params = {} ) {
  return String( text ?? '' ).replace( /\{(\w+)\}/g, ( match, key ) => (
    Object.prototype.hasOwnProperty.call( params, key ) ? String( params[ key ] ) : match
  ) );
}

function t( key, params = {} ) {
  return interpolate( translations[ key ] || key, params );
}

function getStoredValue( key, fallback = '' ) {
  try {
    return window.localStorage.getItem( key ) || fallback;
  } catch ( error ) {
    return fallback;
  }
}

function setStoredValue( key, value ) {
  try {
    window.localStorage.setItem( key, value );
  } catch ( error ) {
    // Local storage can be unavailable in strict privacy contexts.
  }
}

function getSupportedLocale( locale ) {
  const supported = Array.isArray( config.supportedLocales ) && config.supportedLocales.length
    ? config.supportedLocales
    : [ 'el', 'en' ];

  return supported.includes( locale ) ? locale : ( config.defaultLocale || 'el' );
}

function getInitialLocale() {
  return getSupportedLocale( getStoredValue( languageStorageKey, config.defaultLocale || 'el' ) );
}

async function loadTranslations( locale ) {
  if ( ! config.i18nBaseUrl ) return {};

  const baseUrl = String( config.i18nBaseUrl ).replace( /\/$/, '' );
  const response = await fetch( `${baseUrl}/${encodeURIComponent( locale )}.json?ver=${encodeURIComponent( config.appVersion || '' )}`, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  } );

  if ( ! response.ok ) {
    throw new Error( `Could not load ${locale} translations.` );
  }

  return response.json();
}

function applyTranslations( root = document ) {
  root.querySelectorAll( '[data-i18n]' ).forEach( ( node ) => {
    node.textContent = t( node.getAttribute( 'data-i18n' ) );
  } );

  root.querySelectorAll( '[data-i18n-placeholder]' ).forEach( ( node ) => {
    node.setAttribute( 'placeholder', t( node.getAttribute( 'data-i18n-placeholder' ) ) );
  } );

  root.querySelectorAll( '[data-i18n-aria-label]' ).forEach( ( node ) => {
    node.setAttribute( 'aria-label', t( node.getAttribute( 'data-i18n-aria-label' ) ) );
  } );
}

function updateLanguageControls() {
  document.documentElement.lang = currentLocale;

  if ( languageSummary ) {
    languageSummary.textContent = t( `language.${currentLocale}` );
  }

  languageModeButtons.forEach( ( button ) => {
    const isActive = button.getAttribute( 'data-language-mode' ) === currentLocale;
    button.classList.toggle( 'is-active', isActive );
    button.setAttribute( 'aria-checked', isActive ? 'true' : 'false' );
  } );
}

async function setLanguage( locale, options = {} ) {
  const nextLocale = getSupportedLocale( locale );
  currentLocale = nextLocale;

  try {
    translations = await loadTranslations( nextLocale );
  } catch ( error ) {
    if ( nextLocale !== 'en' ) {
      currentLocale = 'en';
      translations = await loadTranslations( 'en' ).catch( () => ( {} ) );
    }
  }

  if ( options.persist !== false ) {
    setStoredValue( languageStorageKey, currentLocale );
  }

  applyTranslations();
  updateLanguageControls();
  updateThemeControls();
  renderSelectedBuilding();
  renderLocationList( locationSearch?.value || '' );
  renderScanHistory();
  renderNotificationsList();
  renderFullscreenStatus();
  renderAppVersionStatus();
}

function loadDismissedNoticeIds() {
  try {
    const value = JSON.parse( window.sessionStorage.getItem( dismissedNoticesStorageKey ) || '[]' );
    return Array.isArray( value ) ? value.map( String ) : [];
  } catch ( error ) {
    return [];
  }
}

function saveDismissedNoticeIds() {
  try {
    window.sessionStorage.setItem( dismissedNoticesStorageKey, JSON.stringify( Array.from( dismissedNoticeIds ) ) );
  } catch ( error ) {
    // Session storage can be unavailable in strict privacy contexts.
  }
}

function normalizeBuilding( building ) {
  const latitude = Number( building?.latitude );
  const longitude = Number( building?.longitude );

  return {
    id: Number( building?.id || 0 ),
    title: String( building?.title || 'Museum space' ).trim(),
    address: String( building?.address || '' ).trim(),
    image: String( building?.image || '' ).trim(),
    latitude: Number.isFinite( latitude ) ? latitude : null,
    longitude: Number.isFinite( longitude ) ? longitude : null,
  };
}

const setStatus = ( text ) => {
  if ( scannerStatus ) scannerStatus.textContent = text;
};

const setButton = ( text, disabled = false ) => {
  if ( ! scanBtn ) return;
  scanBtn.textContent = text;
  scanBtn.disabled = disabled;
};

const setScannerCloseVisible = ( isVisible ) => {
  if ( scannerCloseButton ) scannerCloseButton.hidden = ! isVisible;
};

function setSheetOpen( sheet, isOpen ) {
  if ( ! sheet ) return;

  const closeJob = sheetCloseJobs.get( sheet );
  if ( closeJob?.frame ) window.cancelAnimationFrame( closeJob.frame );
  if ( closeJob?.timer ) window.clearTimeout( closeJob.timer );
  if ( closeJob ) {
    sheetCloseJobs.delete( sheet );
  }

  if ( isOpen ) {
    resetSheetDragState( sheet );
    sheet.classList.remove( 'is-closing' );
    sheet.classList.add( 'is-open' );
    sheet.setAttribute( 'aria-hidden', 'false' );
    return;
  }

  const wasOpen = sheet.classList.contains( 'is-open' ) || sheet.classList.contains( 'is-closing' );
  sheet.setAttribute( 'aria-hidden', 'true' );

  if ( ! wasOpen ) {
    resetSheetDragState( sheet );
    return;
  }

  sheet.classList.remove( 'is-dragging' );
  sheet.classList.add( 'is-closing' );

  const frame = window.requestAnimationFrame( () => {
    if ( ! sheet.classList.contains( 'is-closing' ) ) return;

    sheet.classList.remove( 'is-open' );

    const timer = window.setTimeout( () => {
      sheet.classList.remove( 'is-closing' );
      resetSheetDragState( sheet );
      sheetCloseJobs.delete( sheet );
    }, 380 );

    sheetCloseJobs.set( sheet, { timer } );
  } );

  sheetCloseJobs.set( sheet, { frame } );
}

function resetSheetDragState( sheet ) {
  if ( ! sheet ) return;
  sheet.classList.remove( 'is-dragging' );
  sheet.style.removeProperty( '--scanner-sheet-drag-y' );
  sheet.style.removeProperty( '--scanner-sheet-backdrop-opacity' );
}

function getScrollableSheetTarget( target, panel ) {
  let node = target instanceof Element ? target : null;

  while ( node && node !== panel ) {
    const styles = window.getComputedStyle( node );
    const canScroll = /(auto|scroll)/.test( styles.overflowY ) && node.scrollHeight > node.clientHeight + 1;

    if ( canScroll ) {
      return node;
    }

    node = node.parentElement;
  }

  return null;
}

function getSheetCloseHandler( sheet ) {
  if ( sheet === notificationSheet ) return closeNotificationDetail;
  if ( sheet === locationSheet ) return closeLocationSheet;
  if ( sheet === manualSearchSheet ) return closeManualSearchSheet;
  if ( sheet === memberTicketsSheet ) return closeMemberTicketsSheet;
  if ( sheet === accountSheet ) return closeAccountSheet;
  if ( sheet === passwordSheet ) return closePasswordSheet;
  if ( sheet === themeSheet ) return closeThemeSheet;
  if ( sheet === languageSheet ) return closeLanguageSheet;
  if ( sheet === logoutSheet ) return closeLogoutSheet;

  return () => setSheetOpen( sheet, false );
}

function startSheetDrag( event, sheet, panel ) {
  if ( ! sheet?.classList.contains( 'is-open' ) || ( event.pointerType === 'mouse' && event.button !== 0 ) ) return;

  const target = event.target instanceof Element ? event.target : null;
  const isHandle = Boolean( target?.closest( '.scanner-location-panel-header' ) );
  const isFormControl = Boolean( target?.closest( 'a, button, input, textarea, select, label' ) );
  const scrollable = getScrollableSheetTarget( target, panel );

  if ( ! isHandle && isFormControl ) return;
  if ( ! isHandle && scrollable && scrollable.scrollTop > 0 ) return;

  activeSheetDrag = {
    sheet,
    panel,
    startY: event.clientY,
    pointerId: event.pointerId,
    dragY: 0,
    close: getSheetCloseHandler( sheet ),
  };

  panel.setPointerCapture?.( event.pointerId );
}

function updateSheetDrag( event ) {
  if ( ! activeSheetDrag || event.pointerId !== activeSheetDrag.pointerId ) return;

  const rawDistance = event.clientY - activeSheetDrag.startY;
  const distance = Math.max( 0, rawDistance );

  if ( distance <= 2 ) return;

  event.preventDefault();

  const panelHeight = Math.max( activeSheetDrag.panel.offsetHeight, 1 );
  const easedDistance = Math.min( Math.round( distance * 0.86 ), panelHeight );
  const progress = Math.min( easedDistance / panelHeight, 1 );

  activeSheetDrag.dragY = easedDistance;
  activeSheetDrag.sheet.classList.add( 'is-dragging' );
  activeSheetDrag.sheet.style.setProperty( '--scanner-sheet-drag-y', `${easedDistance}px` );
  activeSheetDrag.sheet.style.setProperty( '--scanner-sheet-backdrop-opacity', String( Math.max( 0.56, 1 - progress * 0.44 ) ) );
}

function completeSheetDrag( shouldClose ) {
  if ( ! activeSheetDrag ) return;

  const { sheet, close } = activeSheetDrag;

  sheet.classList.remove( 'is-dragging' );

  if ( shouldClose ) {
    sheet.style.setProperty( '--scanner-sheet-drag-y', '104%' );
    sheet.style.setProperty( '--scanner-sheet-backdrop-opacity', '0' );
    close();
  } else {
    sheet.style.setProperty( '--scanner-sheet-drag-y', '0px' );
    sheet.style.setProperty( '--scanner-sheet-backdrop-opacity', '1' );
    window.setTimeout( () => resetSheetDragState( sheet ), 380 );
  }

  activeSheetDrag = null;
}

function finishSheetDrag( event ) {
  if ( ! activeSheetDrag || event.pointerId !== activeSheetDrag.pointerId ) return;

  const panelHeight = Math.max( activeSheetDrag.panel.offsetHeight, 1 );
  const threshold = Math.min( 150, Math.max( 96, panelHeight * 0.18 ) );
  const shouldClose = activeSheetDrag.dragY >= threshold;

  activeSheetDrag.panel.releasePointerCapture?.( event.pointerId );
  completeSheetDrag( shouldClose );
}

function bindSheetDrag( sheet ) {
  const panel = sheet?.querySelector( '.scanner-location-panel' );
  if ( ! panel ) return;

  panel.addEventListener( 'pointerdown', ( event ) => startSheetDrag( event, sheet, panel ) );
  panel.addEventListener( 'pointermove', updateSheetDrag );
  panel.addEventListener( 'pointerup', finishSheetDrag );
  panel.addEventListener( 'pointercancel', () => completeSheetDrag( false ) );
}

function renderSheetTitleIcon( sheet, source ) {
  const header = sheet?.querySelector( '.scanner-location-panel-header' );
  const title = header?.querySelector( 'h2' );
  const existingIcon = header?.querySelector( '.scanner-sheet-title-icon' );

  existingIcon?.remove();

  if ( ! header || ! title || ! source ) return;

  const sourceIcon = source.querySelector( '.scanner-settings-icon, .scanner-location-icon' );
  if ( ! sourceIcon ) return;

  const titleIcon = sourceIcon.cloneNode( true );
  titleIcon.classList.remove( 'scanner-settings-icon', 'scanner-location-icon' );
  titleIcon.classList.add( 'scanner-sheet-title-icon' );
  titleIcon.setAttribute( 'aria-hidden', 'true' );
  title.insertAdjacentElement( 'beforebegin', titleIcon );
}

function resolveThemeMode( mode ) {
  if ( mode === 'light' || mode === 'dark' ) {
    return mode;
  }

  return themePreferenceQuery?.matches ? 'dark' : 'light';
}

function getThemeModeIcon( mode ) {
  const resolvedMode = mode === 'default' ? resolveThemeMode( mode ) : mode;

  if ( resolvedMode === 'light' ) {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path></svg>';
  }

  if ( resolvedMode === 'dark' ) {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 13.2A7.8 7.8 0 0 1 10.8 3 9 9 0 1 0 21 13.2Z"></path></svg>';
  }

  return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a7 7 0 0 0 0 14 5.8 5.8 0 0 1 0-14Z"></path><path d="M12 3a7 7 0 0 1 0 14"></path><path d="M12 17v4"></path><path d="M8.5 21h7"></path></svg>';
}

function updateThemeControls() {
  if ( themeModeSummary ) {
    themeModeSummary.textContent = t( `theme.${currentThemeMode}` );
  }

  if ( themeModeIcon ) {
    themeModeIcon.innerHTML = getThemeModeIcon( currentThemeMode );
  }

  const themeSheetTitleIcon = themeSheet?.querySelector( '.scanner-sheet-title-icon' );
  if ( themeSheetTitleIcon && themeModeIcon ) {
    themeSheetTitleIcon.innerHTML = themeModeIcon.innerHTML;
  }

  themeModeButtons.forEach( ( button ) => {
    const isActive = button.getAttribute( 'data-theme-mode' ) === currentThemeMode;
    button.classList.toggle( 'is-active', isActive );
    button.setAttribute( 'aria-checked', isActive ? 'true' : 'false' );
  } );
}

function setThemeMode( mode, options = {} ) {
  currentThemeMode = [ 'default', 'light', 'dark' ].includes( mode ) ? mode : 'default';
  document.documentElement.dataset.scannerThemeMode = currentThemeMode;
  document.documentElement.dataset.scannerTheme = resolveThemeMode( currentThemeMode );

  if ( options.persist !== false ) {
    setStoredValue( themeStorageKey, currentThemeMode );
  }

  updateThemeControls();
}

function openThemeSheet( source ) {
  renderSheetTitleIcon( themeSheet, source );
  setSheetOpen( themeSheet, true );
}

function closeThemeSheet() {
  setSheetOpen( themeSheet, false );
}

function openLanguageSheet( source ) {
  renderSheetTitleIcon( languageSheet, source );
  setSheetOpen( languageSheet, true );
}

function closeLanguageSheet() {
  setSheetOpen( languageSheet, false );
}

function setFormMessage( node, message = '', type = '' ) {
  if ( ! node ) return;
  node.textContent = message;
  node.classList.toggle( 'is-error', type === 'error' );
  node.classList.toggle( 'is-success', type === 'success' );
}

function setFormPending( form, isPending, pendingLabel = 'Saving...' ) {
  if ( ! form ) return;
  const submitButton = form.querySelector( '[type="submit"]' );
  if ( ! submitButton ) return;

  if ( isPending ) {
    submitButton.dataset.idleLabel = submitButton.textContent;
    submitButton.textContent = pendingLabel;
    submitButton.disabled = true;
  } else {
    submitButton.textContent = submitButton.dataset.idleLabel || submitButton.textContent;
    submitButton.disabled = false;
  }
}

function getResponseMessage( json, fallback = t( 'app.requestFailed' ) ) {
  return json?.data?.message || json?.message || fallback;
}

async function postFormData( form, options = {} ) {
  const formData = new FormData( form );

  Object.entries( options.set || {} ).forEach( ( [ key, value ] ) => {
    formData.set( key, value );
  } );

  ( options.delete || [] ).forEach( ( key ) => {
    formData.delete( key );
  } );

  const response = await fetch( form.getAttribute( 'action' ) || config.ajaxUrl || window.location.href, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
    },
    body: formData,
  } );

  const json = await response.json().catch( () => null );

  if ( ! response.ok || json?.success === false ) {
    const error = new Error( getResponseMessage( json ) );
    error.response = json;
    throw error;
  }

  return json?.data || {};
}

async function postRestJson( url, body = {} ) {
  const response = await fetch( url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-WP-Nonce': config.restNonce || '',
    },
    body: JSON.stringify( body ),
  } );

  const json = await response.json().catch( () => null );

  if ( ! response.ok ) {
    const error = new Error( json?.message || json?.error || t( 'app.requestFailed' ) );
    error.status = response.status;
    error.code = json?.code || '';
    error.response = json;
    throw error;
  }

  return json;
}

const buildUrl = ( endpoint, params ) => {
  const url = new URL( endpoint, window.location.href );
  Object.entries( params ).forEach( ( [ key, value ] ) => {
    if ( value !== undefined && value !== null && value !== '' ) {
      url.searchParams.set( key, value );
    }
  } );
  return url;
};

const getScannerContextParams = () => {
  if ( ! selectedBuilding ) return {};

  return {
    building_id: selectedBuilding.id,
    gate: selectedBuilding.title,
  };
};

function renderScannerRibbons( notices = [] ) {
  if ( ! scannerRibbonStack ) return;

  const visibleNotices = notices.filter( ( notice ) => notice?.id && ! dismissedNoticeIds.has( String( notice.id ) ) );

  scannerRibbonStack.innerHTML = visibleNotices.map( ( notice ) => {
    const variant = [ 'info', 'warning', 'urgent' ].includes( notice.variant ) ? notice.variant : 'info';
    return `
      <article class="scanner-ribbon scanner-ribbon--${escapeHtml( variant )}" data-scanner-notice="${escapeHtml( notice.id )}">
        <span>${escapeHtml( notice.message || '' )}</span>
        <button type="button" data-scanner-notice-dismiss="${escapeHtml( notice.id )}" aria-label="Dismiss notification">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
        </button>
      </article>
    `;
  } ).join( '' );

  scannerRibbonStack.querySelectorAll( '[data-scanner-notice-dismiss]' ).forEach( ( button ) => {
    button.addEventListener( 'click', () => {
      dismissedNoticeIds.add( String( button.getAttribute( 'data-scanner-notice-dismiss' ) || '' ) );
      saveDismissedNoticeIds();
      button.closest( '[data-scanner-notice]' )?.remove();
    } );
  } );
}

function renderAccessRevokedRibbon() {
  renderScannerRibbons( [
    {
      id: 'scanner_access_revoked',
      variant: 'urgent',
      message: t( 'scan.accessRevokedMessage' ),
    },
  ] );
}

function getRestErrorCode( error ) {
  return String( error?.code || error?.response?.code || '' ).toLowerCase();
}

function isScannerSessionError( error ) {
  const code = getRestErrorCode( error );

  return error?.status === 401 ||
    code === 'rest_cookie_invalid_nonce' ||
    code === 'rest_not_logged_in' ||
    code === 'iw_scanner_auth_required';
}

function refreshScannerSession() {
  if ( sessionRefreshPending ) return;

  sessionRefreshPending = true;
  scanner?.stop();
  window.setTimeout( () => window.location.reload(), 0 );
}

function handleScannerAuthFailure( error ) {
  if ( error?.status !== 401 && error?.status !== 403 ) {
    return false;
  }

  if ( isScannerSessionError( error ) ) {
    refreshScannerSession();
    return true;
  }

  disableScannerAccess();
  return true;
}

function getNotificationEndpoint( id, suffix = '' ) {
  const base = String( config.endpoints?.notifications || '' ).replace( /\/$/, '' );
  return `${base}/${encodeURIComponent( id )}${suffix}`;
}

function updateNotificationsBadge( count = 0 ) {
  if ( ! notificationsBadge ) return;

  const unreadCount = Math.max( 0, Number( count || 0 ) );
  notificationsBadge.textContent = unreadCount > 99 ? '99+' : String( unreadCount );
  notificationsBadge.hidden = unreadCount <= 0;
}

function setNotificationsRefreshing( isRefreshing ) {
  notificationsRefreshing = Boolean( isRefreshing );
  notificationsRefreshButton?.classList.toggle( 'is-refreshing', notificationsRefreshing );
  if ( notificationsRefreshButton ) {
    notificationsRefreshButton.disabled = notificationsRefreshing;
  }
}

function setNotificationsPullDistance( distance = 0 ) {
  notificationsPullDistance = Math.max( 0, Math.min( distance, notificationsPullMax ) );
  const progress = Math.min( notificationsPullDistance / notificationsPullThreshold, 1 );
  const visualDistance = Math.round( notificationsPullDistance * 0.34 );

  notificationsList?.style.setProperty( '--scanner-notifications-pull', `${visualDistance}px` );
  notificationsList?.classList.toggle( 'is-pulling', notificationsPullDistance > 0 );
  notificationsPullHint?.style.setProperty( '--scanner-notifications-pull', `${visualDistance}px` );
  notificationsPullHint?.style.setProperty( '--scanner-notifications-pull-opacity', String( progress ) );
  notificationsPullHint?.setAttribute( 'aria-hidden', notificationsPullDistance > 0 ? 'false' : 'true' );
  notificationsRefreshButton?.classList.toggle( 'is-pull-ready', notificationsPullDistance >= notificationsPullThreshold );
}

function resetNotificationsPull() {
  notificationsPullStartY = null;
  setNotificationsPullDistance( 0 );
}

function getLocalUnreadNotificationCount() {
  return scannerNotifications.filter( ( item ) => item.unread ).length;
}

function setNotificationUnreadState( id, isUnread, unreadCount = null ) {
  scannerNotifications = scannerNotifications.map( ( item ) => (
    String( item.id ) === String( id ) ? { ...item, unread: Boolean( isUnread ) } : item
  ) );
  updateNotificationsBadge( unreadCount === null ? getLocalUnreadNotificationCount() : unreadCount );
  renderNotificationsList();
}

function setNotificationsLoading() {
  if ( ! notificationsList ) return;
  notificationsList.innerHTML = `
    <article class="scanner-history-row scanner-history-row--empty">
      <span class="scanner-history-icon scanner-history-icon--scan"></span>
      <div>
        <strong>${escapeHtml( t( 'notifications.loadingTitle' ) )}</strong>
        <span>${escapeHtml( t( 'notifications.loadingCopy' ) )}</span>
      </div>
    </article>
  `;
}

function getPriorityLabel( priority = 'info' ) {
  return t( `priority.${priority}` );
}

function formatNotificationTime( item ) {
  const date = item?.date_gmt ? new Date( item.date_gmt ) : null;
  if ( date && ! Number.isNaN( date.getTime() ) ) {
    return date.toLocaleTimeString( [], { hour: '2-digit', minute: '2-digit' } );
  }

  return '';
}

function renderNotificationsList() {
  if ( ! notificationsList ) return;

  if ( ! notificationsLoaded && ! scannerNotifications.length ) {
    setNotificationsLoading();
    return;
  }

  if ( ! scannerNotifications.length ) {
    notificationsList.innerHTML = `
      <article class="scanner-history-row scanner-history-row--empty">
        <span class="scanner-history-icon scanner-history-icon--notifications" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8.8a6 6 0 1 0-12 0c0 7.2-2.5 7.2-2.5 7.2h17S18 16 18 8.8Z"></path><path d="M9.8 19a2.4 2.4 0 0 0 4.4 0"></path></svg>
        </span>
        <div>
          <strong>${escapeHtml( t( 'notifications.emptyTitle' ) )}</strong>
          <span>${escapeHtml( t( 'notifications.emptyCopy' ) )}</span>
        </div>
      </article>
    `;
    return;
  }

  notificationsList.innerHTML = scannerNotifications.map( ( item ) => {
    const priority = [ 'info', 'warning', 'urgent' ].includes( item.priority ) ? item.priority : 'info';
    const image = item.image?.thumb || item.image?.url || '';
    const marker = image
      ? `<span class="scanner-notification-media" aria-hidden="true"><img src="${escapeHtml( image )}" alt=""></span>`
      : `<span class="scanner-notification-media scanner-notification-media--placeholder" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21.9429 15.4296L19.967 13.4539C19.8988 13.3856 19.8607 13.3559 19.8395 13.3422L19.8105 13.3239C19.1998 12.947 18.4098 12.9894 17.8404 13.4449L17.8404 13.4448L14.2088 16.3504L14.2087 16.3504C12.9263 17.3762 11.1272 17.4543 9.76079 16.5434L9.06244 16.0779L9.06236 16.0779C8.42615 15.6537 7.58719 15.7028 7.00506 16.1948L2.22662 20.9732C2.5038 21.5469 3.09205 21.9429 3.77144 21.9429H20.2286C21.1754 21.9428 21.9428 21.1754 21.9429 20.2286V15.4296ZM9.59999 7.88573C9.59999 6.93895 8.83253 6.17144 7.88573 6.17143C6.93896 6.17143 6.17143 6.93896 6.17143 7.88573C6.17144 8.83253 6.93895 9.59999 7.88573 9.59999C8.83254 9.59998 9.59998 8.83254 9.59999 7.88573ZM21.9429 3.77144C21.9429 2.82466 21.1754 2.05715 20.2286 2.05714H3.77144C2.82467 2.05714 2.05714 2.82467 2.05714 3.77144V18.2335L5.57799 14.7126C5.59414 14.6965 5.61083 14.6809 5.62802 14.6659C6.91036 13.5439 8.78572 13.4211 10.2034 14.3662L10.9017 14.8316L10.9018 14.8317C11.5228 15.2457 12.3407 15.2103 12.9237 14.744L16.5553 11.8385C17.8184 10.8281 19.5735 10.7418 20.9227 11.5932L20.9545 11.6135L20.9547 11.6135C21.1512 11.7404 21.3071 11.8846 21.4221 11.9998L21.9429 12.5205V3.77144ZM11.6571 7.88573C11.6571 9.96867 9.96867 11.6571 7.88573 11.6571C5.80284 11.6571 4.1143 9.96868 4.11429 7.88573C4.11429 5.80283 5.80283 4.11429 7.88573 4.11429C9.96868 4.1143 11.6571 5.80284 11.6571 7.88573ZM24 20.2286C24 22.3115 22.3115 24 20.2286 24H3.77144C1.91345 24 0.371026 22.6574 0.0578974 20.8896L0.0507456 20.8477C0.017307 20.6457 7.57625e-07 20.4388 0 20.2286V3.77144C0 1.68854 1.68854 0 3.77144 0H20.2286C22.3115 7.46509e-06 24 1.68855 24 3.77144V20.2286Z" fill="currentColor"/></svg></span>`;

    return `
      <button class="scanner-notification-row${item.unread ? ' is-unread' : ''}" type="button" data-notification-id="${escapeHtml( item.id )}">
        ${marker}
        <span class="scanner-notification-row-body">
          <strong>${escapeHtml( item.title || t( 'notifications.title' ) )}</strong>
          <small>${escapeHtml( item.excerpt || '' )}</small>
          <span class="scanner-notification-row-meta">
            <span class="scanner-notification-pill scanner-notification-pill--${escapeHtml( priority )}">${escapeHtml( getPriorityLabel( priority ) )}</span>
            <time>${escapeHtml( formatNotificationTime( item ) )}</time>
          </span>
        </span>
        ${item.unread ? `<span class="scanner-notification-unread" aria-label="${escapeHtml( t( 'notifications.unread' ) )}"></span>` : ''}
      </button>
    `;
  } ).join( '' );

  notificationsList.querySelectorAll( '[data-notification-id]' ).forEach( ( button ) => {
    button.addEventListener( 'click', () => openNotificationDetail( button.getAttribute( 'data-notification-id' ) ) );
  } );
}

async function fetchNotifications( options = {} ) {
  if ( ! config.endpoints?.notifications || notificationsInFlight ) return;

  notificationsInFlight = true;
  if ( ! notificationsLoaded && options.silent !== true ) {
    setNotificationsLoading();
  }

  try {
    const json = await requestJson( config.endpoints.notifications );
    scannerNotifications = Array.isArray( json?.items ) ? json.items : [];
    notificationsLoaded = true;
    updateNotificationsBadge( json?.unread_count || 0 );
    renderNotificationsList();
  } catch ( error ) {
    if ( handleScannerAuthFailure( error ) ) {
      return;
    }

    if ( notificationsList ) {
      notificationsList.innerHTML = `
        <article class="scanner-history-row scanner-history-row--empty">
          <span class="scanner-history-icon scanner-history-icon--warning"></span>
          <div>
            <strong>${escapeHtml( t( 'notifications.loadErrorTitle' ) )}</strong>
            <span>${escapeHtml( error.message || t( 'notifications.loadErrorCopy' ) )}</span>
          </div>
        </article>
      `;
    }
  } finally {
    notificationsInFlight = false;
  }
}

async function refreshNotifications( options = {} ) {
  if ( notificationsRefreshing || notificationsInFlight ) return;

  resetNotificationsPull();
  const showSpinner = options.visual !== false;

  if ( showSpinner ) {
    setNotificationsRefreshing( true );
  }

  try {
    await fetchNotifications( { silent: true } );
  } finally {
    if ( showSpinner ) {
      setNotificationsRefreshing( false );
    }
  }
}

function startNotificationsAutoRefresh() {
  if ( notificationsAutoRefreshTimer || ! config.endpoints?.notifications ) return;

  notificationsAutoRefreshTimer = window.setInterval( () => {
    if ( accessRevoked || document.visibilityState !== 'visible' ) return;

    refreshNotifications( { visual: activeView === 'notifications' } );
  }, notificationsAutoRefreshInterval );
}

function handleNotificationsPullStart( event ) {
  if (
    activeView !== 'notifications' ||
    notificationsRefreshing ||
    notificationsInFlight ||
    ! notificationsList ||
    notificationsList.scrollTop > 0
  ) {
    return;
  }

  notificationsPullStartY = event.touches?.[0]?.clientY ?? null;
}

function handleNotificationsPullMove( event ) {
  if ( notificationsPullStartY === null || ! notificationsList ) return;

  const currentY = event.touches?.[0]?.clientY ?? notificationsPullStartY;
  const distance = currentY - notificationsPullStartY;

  if ( distance <= 0 || notificationsList.scrollTop > 0 ) {
    resetNotificationsPull();
    return;
  }

  if ( distance > 10 && event.cancelable ) {
    event.preventDefault();
  }

  setNotificationsPullDistance( distance );
}

function handleNotificationsPullEnd() {
  const shouldRefresh = notificationsPullDistance >= notificationsPullThreshold;
  resetNotificationsPull();

  if ( shouldRefresh ) {
    refreshNotifications();
  }
}

async function markNotificationRead( id ) {
  if ( ! id || ! config.endpoints?.notifications ) return;

  setNotificationUnreadState( id, false );

  try {
    const json = await postRestJson( getNotificationEndpoint( id, '/read' ) );
    const isStillRead = scannerNotifications.some( ( item ) => String( item.id ) === String( id ) && ! item.unread );
    if ( isStillRead ) {
      updateNotificationsBadge( json?.unread_count || 0 );
    }
  } catch ( error ) {
    handleScannerAuthFailure( error );
  }
}

async function markNotificationUnreadAndClose() {
  const id = currentNotificationDetailId;
  if ( ! id || ! config.endpoints?.notifications || ! notificationUnreadButton ) return;

  notificationUnreadButton.classList.add( 'is-closing' );
  notificationUnreadButton.disabled = true;

  try {
    const json = await postRestJson( getNotificationEndpoint( id, '/unread' ) );
    setNotificationUnreadState( id, true, Number.isFinite( Number( json?.unread_count ) ) ? Number( json.unread_count ) : null );
    closeNotificationDetail();
  } catch ( error ) {
    handleScannerAuthFailure( error );
  } finally {
    notificationUnreadButton.disabled = false;
    notificationUnreadButton.classList.remove( 'is-closing' );
  }
}

async function openNotificationDetail( id ) {
  if ( ! id || ! config.endpoints?.notifications ) return;

  const requestId = notificationDetailRequestId + 1;
  notificationDetailRequestId = requestId;
  currentNotificationDetailId = String( id );
  setSheetOpen( notificationSheet, true );
  notificationDetail?.setAttribute( 'aria-busy', 'true' );
  if ( notificationUnreadButton ) {
    notificationUnreadButton.hidden = true;
    notificationUnreadButton.disabled = false;
    notificationUnreadButton.classList.remove( 'is-closing' );
  }
  if ( notificationDetailTitle ) {
    notificationDetailTitle.textContent = t( 'notifications.detailLoading' );
    notificationDetailTitle.classList.add( 'is-loading' );
  }
  if ( notificationDetailMeta ) notificationDetailMeta.textContent = '';
  if ( notificationDetailContent ) notificationDetailContent.innerHTML = '';
  if ( notificationDetailImage ) {
    notificationDetailImage.hidden = true;
    notificationDetailImage.innerHTML = '';
  }

  try {
    const item = await requestJson( getNotificationEndpoint( id ) );
    const priority = [ 'info', 'warning', 'urgent' ].includes( item.priority ) ? item.priority : 'info';

    if ( requestId !== notificationDetailRequestId ) return;

    notificationDetail?.setAttribute( 'aria-busy', 'false' );
    notificationDetailTitle?.classList.remove( 'is-loading' );
    if ( notificationUnreadButton ) {
      notificationUnreadButton.hidden = false;
    }
    if ( notificationDetailTitle ) notificationDetailTitle.textContent = item.title || t( 'notifications.title' );
    if ( notificationDetailMeta ) {
      notificationDetailMeta.innerHTML = `
        <span class="scanner-notification-pill scanner-notification-pill--${escapeHtml( priority )}">${escapeHtml( getPriorityLabel( priority ) )}</span>
        <time>${escapeHtml( formatNotificationTime( item ) )}</time>
      `;
    }
    if ( notificationDetailImage && item.image?.url ) {
      notificationDetailImage.hidden = false;
      notificationDetailImage.innerHTML = `<img src="${escapeHtml( item.image.url )}" alt="${escapeHtml( item.image.alt || '' )}">`;
    }
    if ( notificationDetailContent ) {
      notificationDetailContent.innerHTML = item.content || `<p>${escapeHtml( t( 'notifications.noContent' ) )}</p>`;
    }

    if ( item.unread ) {
      markNotificationRead( id );
    }
  } catch ( error ) {
    if ( requestId !== notificationDetailRequestId ) return;

    notificationDetail?.setAttribute( 'aria-busy', 'false' );
    notificationDetailTitle?.classList.remove( 'is-loading' );
    if ( notificationUnreadButton ) {
      notificationUnreadButton.hidden = true;
    }
    if ( handleScannerAuthFailure( error ) ) {
      return;
    }

    if ( notificationDetailTitle ) notificationDetailTitle.textContent = t( 'notifications.detailUnavailable' );
    if ( notificationDetailMeta ) notificationDetailMeta.textContent = '';
    if ( notificationDetailContent ) {
      notificationDetailContent.innerHTML = `<p>${escapeHtml( error.message || t( 'notifications.detailUnavailableCopy' ) )}</p>`;
    }
  }
}

function closeNotificationDetail() {
  notificationDetailRequestId += 1;
  currentNotificationDetailId = '';
  notificationDetail?.setAttribute( 'aria-busy', 'false' );
  notificationDetailTitle?.classList.remove( 'is-loading' );
  if ( notificationUnreadButton ) {
    notificationUnreadButton.hidden = true;
    notificationUnreadButton.disabled = false;
    notificationUnreadButton.classList.remove( 'is-closing' );
  }
  setSheetOpen( notificationSheet, false );
}

function disableScannerAccess() {
  accessRevoked = true;
  scanner?.stop();
  resetScannerControls();
  setStatus( t( 'scan.accessRevoked' ) );
  setButton( t( 'scan.accessRevoked' ), true );
  renderAccessRevokedRibbon();
}

function getHeartbeatPayload( extra = {} ) {
  return {
    building_id: selectedBuilding?.id || 0,
    building_title: selectedBuilding?.title || '',
    active_view: activeView,
    app_version: config.appVersion || '',
    ...extra,
  };
}

async function sendScannerHeartbeat( extra = {} ) {
  if ( ! config.endpoints?.heartbeat || heartbeatInFlight || accessRevoked ) return;

  heartbeatInFlight = true;

  try {
    const json = await postRestJson( config.endpoints.heartbeat, getHeartbeatPayload( extra ) );
    renderScannerRibbons( json?.notices || [] );
    if ( json?.notifications ) {
      updateNotificationsBadge( json.notifications.unread_count || 0 );
    }
  } catch ( error ) {
    handleScannerAuthFailure( error );
  } finally {
    heartbeatInFlight = false;
  }
}

function startScannerHeartbeat() {
  if ( heartbeatTimer || ! config.endpoints?.heartbeat ) return;

  sendScannerHeartbeat();
  heartbeatTimer = window.setInterval( () => sendScannerHeartbeat(), 60000 );
}

async function requestJson( url ) {
  const response = await fetch( url, {
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-WP-Nonce': config.restNonce || '',
    },
  } );

  const json = await response.json().catch( () => null );
  if ( ! response.ok ) {
    const message = json?.message || json?.error || t( 'app.requestFailed' );
    const error = new Error( message );
    error.status = response.status;
    error.code = json?.code || '';
    error.response = json;
    throw error;
  }

  return json;
}

async function startScanner() {
  if ( accessRevoked ) {
    disableScannerAccess();
    return;
  }

  if ( isStarting ) return;
  isStarting = true;

  closeManualSearchSheet();
  resultBox?.classList.add( 'hidden' );
  cameraPlaceholder?.classList.add( 'opacity-0' );
  loading?.classList.remove( 'hidden' );
  setScannerCloseVisible( false );
  setStatus( t( 'scan.requestingCamera' ) );
  setButton( t( 'scan.allowCamera' ), true );

  try {
    await requestCameraAccess();
    setStatus( t( 'scan.cameraStarting' ) );
    setButton( t( 'scan.starting' ), true );

    if ( ! scanner ) {
      scanner = new QrScanner(
        preview,
        ( result ) => handleScan( result.data ),
        {
          highlightScanRegion: true,
          highlightCodeOutline: true,
        }
      );
    }

    await scanner.start();
    loading?.classList.add( 'hidden' );
    setScannerCloseVisible( true );
    setStatus( t( 'scan.scanning' ) );
    setButton( t( 'scan.scanningButton' ), true );
  } catch ( error ) {
    renderError( t( 'scan.cameraUnavailable' ), getCameraErrorMessage( error ), { logHistory: false } );
    resetScannerControls();
  } finally {
    isStarting = false;
  }
}

async function requestCameraAccess() {
  const isLocalhost = [ 'localhost', '127.0.0.1', '::1' ].includes( window.location.hostname );

  if ( ! window.isSecureContext && ! isLocalhost ) {
    const error = new Error( t( 'scan.cameraHttps' ) );
    error.name = 'SecurityError';
    throw error;
  }

  if ( ! navigator.mediaDevices?.getUserMedia ) {
    const error = new Error( t( 'scan.cameraMissing' ) );
    error.name = 'NotSupportedError';
    throw error;
  }

  const stream = await navigator.mediaDevices.getUserMedia( {
    audio: false,
    video: {
      facingMode: { ideal: 'environment' },
    },
  } );

  stream.getTracks().forEach( ( track ) => track.stop() );
}

function getCameraErrorMessage( error ) {
  const name = error?.name || '';

  if ( name === 'NotAllowedError' || name === 'PermissionDeniedError' ) {
    return t( 'scan.cameraBlocked' );
  }

  if ( name === 'SecurityError' ) {
    return error?.message || t( 'scan.cameraHttps' );
  }

  if ( name === 'NotFoundError' || name === 'DevicesNotFoundError' ) {
    return t( 'scan.cameraMissing' );
  }

  if ( name === 'NotReadableError' || name === 'TrackStartError' || name === 'AbortError' ) {
    return t( 'scan.cameraBusy' );
  }

  return error?.message || t( 'scan.cameraTryAgain' );
}

function resetScannerControls() {
  loading?.classList.add( 'hidden' );
  setScannerCloseVisible( false );
  cameraPlaceholder?.classList.remove( 'opacity-0' );
  setStatus( t( 'scan.ready' ) );
  setButton( t( 'scan.start' ), false );
}

function setActiveView( view, options = {} ) {
  if ( ! appViewNames.has( view ) ) return;

  activeView = view;

  if ( options.persist !== false ) {
    setStoredValue( activeViewStorageKey, view );
  }

  navButtons.forEach( ( button ) => {
    const isActive = button.getAttribute( 'data-scanner-nav' ) === view;
    button.classList.toggle( 'is-active', isActive );
    button.toggleAttribute( 'aria-current', isActive );
  } );

  appViews.forEach( ( panel ) => {
    const isActive = panel.getAttribute( 'data-scanner-view' ) === view;
    panel.classList.toggle( 'is-active', isActive );
    panel.setAttribute( 'aria-hidden', isActive ? 'false' : 'true' );
  } );

  if ( view !== 'scan' && scanner ) {
    scanner.stop();
    resetScannerControls();
  }

  if ( view !== 'scan' ) {
    closeManualSearchSheet();
    closeMemberTicketsSheet();
  }

  if ( view === 'notifications' ) {
    fetchNotifications();
  } else {
    closeNotificationDetail();
  }
}

function openLocationSheet( source ) {
  renderSheetTitleIcon( locationSheet, source );
  setSheetOpen( locationSheet, true );
  renderLocationList();
  window.setTimeout( () => locationSearch?.focus(), 80 );
}

function closeLocationSheet() {
  setSheetOpen( locationSheet, false );
}

function openManualSearchSheet( source ) {
  scanner?.stop();
  resultBox?.classList.add( 'hidden' );
  resetScannerControls();
  setSheetOpen( manualSearchSheet, true );
  window.setTimeout( () => {
    if ( ! manualSearchInput || ! manualSearchSheet?.classList.contains( 'is-open' ) ) return;

    try {
      manualSearchInput.focus( { preventScroll: true } );
    } catch ( error ) {
      manualSearchInput.focus();
    }

    const valueLength = String( manualSearchInput.value || '' ).length;
    manualSearchInput.setSelectionRange?.( valueLength, valueLength );
  }, 160 );
}

function closeManualSearchSheet() {
  setSheetOpen( manualSearchSheet, false );
}

function openMemberTicketsSheet() {
  renderMemberTicketsList( currentMemberTickets );
  setSheetOpen( memberTicketsSheet, true );
}

function closeMemberTicketsSheet() {
  setSheetOpen( memberTicketsSheet, false );
}

function clearManualSearchResults() {
  if ( ! manualSearchResults ) return;
  manualSearchResults.hidden = true;
  manualSearchResults.innerHTML = '';
}

function renderManualSearchResults( matches = [] ) {
  if ( ! manualSearchResults ) return;

  const visibleMatches = Array.isArray( matches ) ? matches.filter( ( match ) => match?.card_id ) : [];
  manualSearchCachedMatches = visibleMatches;
  manualSearchResults.hidden = visibleMatches.length === 0;
  manualSearchResults.innerHTML = visibleMatches.map( ( match ) => {
    const photo = match.photo_thumb || match.photo || '';
    const phone = match.phone || '';
    const email = match.email || '';

    return `
      <button class="scanner-manual-search-result" type="button" data-manual-search-card-id="${escapeHtml( match.card_id )}">
        <span class="scanner-manual-search-avatar" aria-hidden="true">
          ${photo ? `<img src="${escapeHtml( photo )}" alt="">` : `<span class="scanner-manual-search-avatar-placeholder">${renderNoImageSvg()}</span>`}
        </span>
        <span class="scanner-manual-search-result-copy">
          <strong>${escapeHtml( match.name || t( 'result.memberFallback' ) )}</strong>
          <span class="scanner-manual-search-result-meta">
            ${phone ? `<small>${escapeHtml( phone )}</small>` : ''}
            ${email ? `<small>${escapeHtml( email )}</small>` : ''}
          </span>
        </span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <path d="m9 18 6-6-6-6"></path>
        </svg>
      </button>
    `;
  } ).join( '' );
}

function getMemberTicketGroupKey( ticket = {} ) {
  const slotDate = parseMysqlDate( ticket.slot_start || ticket.slot_end || '' );
  if ( ! slotDate ) return 'undated';

  const todayStart = new Date();
  todayStart.setHours( 0, 0, 0, 0 );

  const ticketDayStart = new Date( slotDate );
  ticketDayStart.setHours( 0, 0, 0, 0 );

  if ( isSameLocalDay( ticketDayStart, todayStart ) ) return 'today';
  return ticketDayStart > todayStart ? 'upcoming' : 'past';
}

function getMemberTicketCheckedStatus( ticket = {} ) {
  const verificationResult = ticket.verification?.scan?.result || '';
  const rawStatus = String( ticket.status ?? '' ).trim().toLowerCase();
  const hasStoredStatus = rawStatus && ! [ 'valid', '0', 'null' ].includes( rawStatus );
  const hasVerification = Boolean( ticket.verification );
  const hasUsedAt = Boolean( ticket.used_at );

  if ( ! verificationResult && ! hasVerification && ! hasStoredStatus && ! hasUsedAt ) return null;

  const scanResult = verificationResult ||
    getTicketLocalStoredStatusScanResult( ticket ) ||
    ( ticket.verification?.valid === true ? 'ok' : 'invalid' );
  const meta = getTicketStatusMeta( scanResult, ticket.verification?.error || '' );

  return {
    result: scanResult,
    title: meta.title,
    variant: meta.historyVariant,
  };
}

function renderMemberTicketRow( ticket, index ) {
  const title = ticket.title || t( 'result.ticket' );
  const slot = formatDateTime( ticket.slot_start || '' );
  const slotEnd = formatTime( ticket.slot_end || '' );
  const category = cleanCategory( ticket.price_category || ticket.ticket_type || '' );
  const attendee = ticket.attendee_name || ticket.attendee_email || '';
  const slotLine = slot ? `${slot}${slotEnd ? ` - ${slotEnd}` : ''}` : '';
  const metaLine = [ attendee, category ].filter( Boolean ).join( ' · ' );
  const image = ticket.thumb || ticket.image || '';
  const status = getMemberTicketCheckedStatus( ticket );

  return `
    <button class="scanner-member-ticket-row" type="button" data-member-ticket-index="${index}">
      <span class="scanner-member-ticket-thumb" aria-hidden="true">
        ${image ? `<img src="${escapeHtml( image )}" alt="">` : renderTicketSvg()}
      </span>
      <span class="scanner-member-ticket-copy">
        <strong>${escapeHtml( title )}</strong>
        ${slotLine ? `<small>${escapeHtml( slotLine )}</small>` : ''}
        ${metaLine ? `<small>${escapeHtml( metaLine )}</small>` : ''}
        ${status ? `
          <span class="scanner-member-ticket-status is-${escapeHtml( status.variant )}">
            ${renderTicketStatusSvg( status.result )}
            <span>${escapeHtml( status.title )}</span>
          </span>
        ` : ''}
      </span>
      <svg class="scanner-member-ticket-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m9 18 6-6-6-6"></path>
      </svg>
    </button>
  `;
}

function renderMemberTicketSection( group, items ) {
  if ( ! items.length ) return '';

  return `
    <section class="scanner-member-tickets-section">
      <h3>${escapeHtml( t( `memberTickets.group.${group}` ) )}</h3>
      ${items.map( ( item ) => renderMemberTicketRow( item.ticket, item.index ) ).join( '' )}
    </section>
  `;
}

function renderMemberTicketsList( tickets = [] ) {
  if ( ! memberTicketsList ) return;

  const visibleTickets = Array.isArray( tickets ) ? tickets : [];
  if ( ! visibleTickets.length ) {
    memberTicketsList.innerHTML = `
      <article class="scanner-member-ticket-row scanner-member-ticket-row--empty">
        <span class="scanner-member-ticket-icon" aria-hidden="true">${renderTicketSvg()}</span>
        <span>
          <strong>${escapeHtml( t( 'memberTickets.emptyTitle' ) )}</strong>
          <small>${escapeHtml( t( 'memberTickets.emptyCopy' ) )}</small>
        </span>
      </article>
    `;
    return;
  }

  const groupedTickets = visibleTickets.reduce( ( groups, ticket, index ) => {
    const group = getMemberTicketGroupKey( ticket );
    groups[group] = groups[group] || [];
    groups[group].push( { ticket, index } );
    return groups;
  }, {} );

  const rows = [ 'today', 'upcoming', 'past', 'undated' ]
    .map( ( group ) => renderMemberTicketSection( group, groupedTickets[group] || [] ) )
    .filter( Boolean )
    .join( '' );

  memberTicketsList.innerHTML = `
    <p class="scanner-member-tickets-summary">${escapeHtml( t( 'memberTickets.count', { count: visibleTickets.length } ) )}</p>
    ${rows}
  `;
}

function buildMemberTicketVerification( ticket = {} ) {
  if ( ticket.verification ) {
    return ticket.verification;
  }

  const image = ticket.image || ticket.thumb || '';
  const thumb = ticket.thumb || ticket.image || '';
  const scanResult = getTicketLocalLocationScanResult( ticket ) ||
    getTicketLocalStoredStatusScanResult( ticket ) ||
    getTicketLocalSlotScanResult( ticket );
  const isValid = scanResult === 'ok';

  return {
    valid: isValid,
    error: isValid ? null : getTicketStatusTitle( scanResult ),
    scan: {
      result: scanResult,
      dry_run: true,
      can_force_scan: scanResult === 'outside_slot',
      gate: '',
      device_id: '',
    },
    ticket: {
      id: ticket.id || null,
      ticket_uuid: ticket.ticket_uuid || '',
      barcode_hash: ticket.barcode_hash || '',
      order_id: ticket.order_id || null,
      order_item_id: ticket.order_item_id || null,
      post_id: ticket.post_id || null,
      slot_start: ticket.slot_start || null,
      slot_end: ticket.slot_end || null,
      ticket_type: ticket.ticket_type || null,
      price_category: ticket.price_category || null,
      unit_price: ticket.unit_price || null,
      currency: ticket.currency || null,
      channel: ticket.channel || null,
      attendee_name: ticket.attendee_name || null,
      attendee_email: ticket.attendee_email || null,
      status: ticket.status || 'valid',
      issued_at: ticket.issued_at || null,
      used_at: ticket.used_at || null,
    },
    event: {
      id: ticket.post_id || null,
      type: ticket.post_type || '',
      exhibition_type: ticket.exhibition_type || '',
      is_permanent: ticket.is_permanent ?? null,
      title: ticket.title || t( 'result.ticket' ),
      url: ticket.url || '',
      image,
      thumb,
    },
    building: ticket.building || null,
    timestamp: getCurrentMysqlDateTime(),
  };
}

function selectMemberTicket( index ) {
  const ticketIndex = Number( index );
  if ( ! Number.isInteger( ticketIndex ) ) return;

  const ticket = currentMemberTickets[ ticketIndex ];
  if ( ! ticket ) return;

  const verification = buildMemberTicketVerification( ticket );
  currentMemberTickets[ ticketIndex ] = { ...ticket, verification };

  closeMemberTicketsSheet();
  renderTicketResult(
    verification,
    ticket.barcode_hash || ticket.ticket_uuid || '',
    { source: 'member-tickets', index: ticketIndex }
  );
  setStatus( t( 'scan.ready' ) );
}

async function selectManualSearchResult( cardId ) {
  if ( ! cardId || ! manualSearchForm ) return;

  const cachedMatch = manualSearchCachedMatches.find( ( match ) => String( match.card_id ) === String( cardId ) );
  if ( cachedMatch?.verification ) {
    closeManualSearchSheet();
    renderMemberCardResult( cachedMatch.verification, cardId, { source: 'manual-search' } );
    setStatus( t( 'scan.ready' ) );
    return;
  }

  if ( ! config.endpoints?.verifyMemberCard ) return;

  setFormMessage( manualSearchMessage );
  setFormPending( manualSearchForm, true, t( 'manualSearch.loadingMember' ) );
  setStatus( t( 'manualSearch.checking' ) );

  try {
    const url = buildUrl( config.endpoints.verifyMemberCard, { 'member-card-id': cardId, ...getScannerContextParams() } );
    const json = await requestJson( url );
    closeManualSearchSheet();
    renderMemberCardResult( json, cardId, { source: 'manual-search' } );
  } catch ( error ) {
    if ( handleScannerAuthFailure( error ) ) {
      return;
    }

    setFormMessage( manualSearchMessage, error.message || t( 'manualSearch.failed' ), 'error' );
  } finally {
    setFormPending( manualSearchForm, false );
    setStatus( t( 'scan.ready' ) );
  }
}

function getManualSearchResultButton( target ) {
  return target instanceof Element ? target.closest( '[data-manual-search-card-id]' ) : null;
}

function beginManualSearchResultPress( event ) {
  const button = getManualSearchResultButton( event.target );
  if ( ! button || event.pointerType === 'mouse' ) return;

  manualSearchPointerSelection = {
    cardId: button.getAttribute( 'data-manual-search-card-id' ) || '',
    pointerId: event.pointerId,
    startX: event.clientX,
    startY: event.clientY,
  };
}

function finishManualSearchResultPress( event ) {
  if ( ! manualSearchPointerSelection || manualSearchPointerSelection.pointerId !== event.pointerId ) return;

  const button = getManualSearchResultButton( event.target );
  const cardId = button?.getAttribute( 'data-manual-search-card-id' ) || '';
  const moved = Math.hypot(
    event.clientX - manualSearchPointerSelection.startX,
    event.clientY - manualSearchPointerSelection.startY
  );

  const shouldSelect = cardId && cardId === manualSearchPointerSelection.cardId && moved < 10;
  manualSearchPointerSelection = null;

  if ( ! shouldSelect ) return;

  event.preventDefault();
  manualSearchSuppressClickUntil = Date.now() + 600;
  manualSearchInput?.blur();
  selectManualSearchResult( cardId );
}

async function submitManualSearchForm( event ) {
  event.preventDefault();
  if ( ! manualSearchForm || ! config.endpoints?.searchMemberCard ) return;

  const formData = new FormData( manualSearchForm );
  const query = String( formData.get( 'q' ) || '' ).trim();

  setFormMessage( manualSearchMessage );

  if ( query.length < 3 ) {
    setFormMessage( manualSearchMessage, t( 'manualSearch.minLength' ), 'error' );
    return;
  }

  if ( query === manualSearchCachedQuery && manualSearchCachedMatches.length ) {
    renderManualSearchResults( manualSearchCachedMatches );
    setFormMessage( manualSearchMessage, t( 'manualSearch.multiple', { count: manualSearchCachedMatches.length } ), 'success' );
    return;
  }

  clearManualSearchResults();

  setFormPending( manualSearchForm, true, t( 'manualSearch.searching' ) );
  setStatus( t( 'manualSearch.checking' ) );

  try {
    const url = buildUrl( config.endpoints.searchMemberCard, { q: query, ...getScannerContextParams() } );
    const json = await requestJson( url );

    if ( json?.requires_selection && Array.isArray( json.matches ) ) {
      manualSearchCachedQuery = query;
      renderManualSearchResults( json.matches );
      setFormMessage( manualSearchMessage, t( 'manualSearch.multiple', { count: json.matches.length } ), 'success' );
      return;
    }

    if ( json?.valid === false && ! json?.user ) {
      manualSearchCachedQuery = '';
      manualSearchCachedMatches = [];
      setFormMessage( manualSearchMessage, json?.error || t( 'manualSearch.notFound' ), 'error' );
      return;
    }

    manualSearchCachedQuery = query;
    manualSearchCachedMatches = [];
    closeManualSearchSheet();
    renderMemberCardResult( json, query, { source: 'manual-search' } );
  } catch ( error ) {
    if ( handleScannerAuthFailure( error ) ) {
      return;
    }

    setFormMessage( manualSearchMessage, error.message || t( 'manualSearch.failed' ), 'error' );
  } finally {
    setFormPending( manualSearchForm, false );
    setStatus( t( 'scan.ready' ) );
  }
}

function getAccountDisplayName() {
  return [ currentUser.firstName, currentUser.lastName ].filter( Boolean ).join( ' ' ).trim() ||
    currentUser.name ||
    currentUser.email ||
    t( 'settings.operator' );
}

function renderAccountSummary() {
  const label = getAccountDisplayName();
  accountSummaryNodes.forEach( ( node ) => {
    node.textContent = label;
  } );
}

function resetAccountSheet() {
  accountForm?.classList.remove( 'is-hidden' );
  accountPhoneForm?.classList.add( 'is-hidden' );
  accountPhoneForm?.reset();
  setFormMessage( accountMessage );
  setFormMessage( accountPhoneMessage );
  clearAccountResendCountdown();
}

function openAccountSheet( source ) {
  resetAccountSheet();
  renderSheetTitleIcon( accountSheet, source );
  setSheetOpen( accountSheet, true );
  window.setTimeout( () => accountForm?.querySelector( 'input' )?.focus(), 80 );
}

function closeAccountSheet() {
  setSheetOpen( accountSheet, false );
  clearAccountResendCountdown();
}

function openPasswordSheet( source ) {
  passwordForm?.reset();
  setFormMessage( passwordMessage );
  renderSheetTitleIcon( passwordSheet, source );
  setSheetOpen( passwordSheet, true );
  window.setTimeout( () => passwordForm?.querySelector( 'input' )?.focus(), 80 );
}

function closePasswordSheet() {
  setSheetOpen( passwordSheet, false );
}

function openLogoutSheet( source ) {
  renderSheetTitleIcon( logoutSheet, source );
  setSheetOpen( logoutSheet, true );
}

function closeLogoutSheet() {
  setSheetOpen( logoutSheet, false );
}

function updateAccountFromForm() {
  if ( ! accountForm ) return;
  const formData = new FormData( accountForm );
  currentUser.firstName = String( formData.get( 'user_fields[first_name]' ) || '' ).trim();
  currentUser.lastName = String( formData.get( 'user_fields[last_name]' ) || '' ).trim();
  currentUser.email = String( formData.get( 'user_fields[user_email]' ) || '' ).trim();
  currentUser.phone = String( formData.get( 'user_fields[activation_phone]' ) || '' ).trim();
  currentUser.name = getAccountDisplayName();
  renderAccountSummary();
}

async function submitAccountForm( event ) {
  event.preventDefault();
  if ( ! accountForm ) return;

  setFormMessage( accountMessage );
  setFormPending( accountForm, true, t( 'account.saving' ) );

  try {
    const data = await postFormData( accountForm );

    if ( data.phone_verification_required ) {
      if ( accountPendingPhone ) accountPendingPhone.textContent = data.pending_phone || '';
      accountForm.classList.add( 'is-hidden' );
      accountPhoneForm?.classList.remove( 'is-hidden' );
      setFormMessage( accountPhoneMessage, data.message || t( 'account.verifyCopy' ), 'success' );
      startAccountResendCountdown( data.retry_after || 60 );
      return;
    }

    updateAccountFromForm();
    setFormMessage( accountMessage, data.message || t( 'account.updated' ), 'success' );
    window.setTimeout( closeAccountSheet, 700 );
  } catch ( error ) {
    setFormMessage( accountMessage, error.message || t( 'account.updateFailed' ), 'error' );
  } finally {
    setFormPending( accountForm, false );
  }
}

async function submitAccountPhoneForm( event ) {
  event.preventDefault();
  if ( ! accountPhoneForm ) return;

  setFormMessage( accountPhoneMessage );
  setFormPending( accountPhoneForm, true, t( 'account.verifying' ) );

  try {
    const data = await postFormData( accountPhoneForm );
    updateAccountFromForm();
    setFormMessage( accountPhoneMessage, data.message || t( 'account.phoneVerified' ), 'success' );
    window.setTimeout( closeAccountSheet, 700 );
  } catch ( error ) {
    setFormMessage( accountPhoneMessage, error.message || t( 'account.phoneFailed' ), 'error' );
  } finally {
    setFormPending( accountPhoneForm, false );
  }
}

async function resendAccountPhoneCode( event ) {
  event.preventDefault();
  if ( ! accountPhoneForm || ! accountPhoneResend || accountPhoneResend.disabled ) return;

  accountPhoneResend.disabled = true;
  setFormMessage( accountPhoneMessage );

  try {
    const data = await postFormData( accountPhoneForm, {
      set: { action: 'iw-auth-resend-account-phone' },
      delete: [ 'activation_code' ],
    } );
    setFormMessage( accountPhoneMessage, data.message || t( 'account.codeSent' ), 'success' );
    startAccountResendCountdown( data.retry_after || 60 );
  } catch ( error ) {
    const retryAfter = Number( error.response?.data?.retry_after || 0 );
    setFormMessage( accountPhoneMessage, error.message || t( 'account.codeFailed' ), 'error' );
    if ( retryAfter > 0 ) {
      startAccountResendCountdown( retryAfter );
    } else {
      accountPhoneResend.disabled = false;
    }
  }
}

function startAccountResendCountdown( seconds ) {
  if ( ! accountPhoneResend ) return;

  clearAccountResendCountdown( false );
  let remaining = Number( seconds || 60 );
  const label = accountPhoneResend.dataset.label || accountPhoneResend.textContent.trim();
  const countdownLabel = t( 'account.resendIn' );

  const update = () => {
    if ( remaining <= 0 ) {
      clearAccountResendCountdown();
      return;
    }

    accountPhoneResend.disabled = true;
    accountPhoneResend.textContent = interpolate( countdownLabel, { seconds: remaining } );
    remaining -= 1;
  };

  accountPhoneResend.dataset.label = label;
  update();
  accountResendTimer = window.setInterval( update, 1000 );
}

function clearAccountResendCountdown( enableButton = true ) {
  if ( accountResendTimer ) {
    window.clearInterval( accountResendTimer );
    accountResendTimer = null;
  }

  if ( accountPhoneResend && enableButton ) {
    accountPhoneResend.disabled = false;
    accountPhoneResend.textContent = t( 'account.resend' );
  }
}

function isValidScannerPassword( value ) {
  return /(?=^.{8,}$)(?=.*[!@#$%^&*]+)(?![.\n])(?=.*[a-z]).*$/.test( value );
}

async function submitPasswordForm( event ) {
  event.preventDefault();
  if ( ! passwordForm ) return;

  const formData = new FormData( passwordForm );
  const password = String( formData.get( 'user_fields[new_user_pass]' ) || '' );
  const confirmation = String( formData.get( 'user_fields[new_user_pass_confirmation]' ) || '' );

  setFormMessage( passwordMessage );

  if ( password !== confirmation ) {
    setFormMessage( passwordMessage, t( 'password.mismatch' ), 'error' );
    return;
  }

  if ( ! isValidScannerPassword( password ) ) {
    setFormMessage( passwordMessage, t( 'password.invalid' ), 'error' );
    return;
  }

  setFormPending( passwordForm, true, t( 'password.updating' ) );

  try {
    const data = await postFormData( passwordForm );
    passwordForm.reset();
    setFormMessage( passwordMessage, data.message || t( 'password.updated' ), 'success' );
    window.setTimeout( closePasswordSheet, 800 );
  } catch ( error ) {
    setFormMessage( passwordMessage, error.message || t( 'password.failed' ), 'error' );
  } finally {
    setFormPending( passwordForm, false );
  }
}

function isStandaloneApp() {
  return Boolean(
    window.matchMedia?.( '(display-mode: standalone)' )?.matches ||
    window.matchMedia?.( '(display-mode: fullscreen)' )?.matches ||
    window.navigator.standalone
  );
}

function renderFullscreenStatus() {
  if ( fullscreenStatus ) {
    fullscreenStatus.textContent = t( fullscreenStatusState.key, fullscreenStatusState.params );
  }
}

function setFullscreenStatus( key, params = {} ) {
  fullscreenStatusState = { key, params };
  renderFullscreenStatus();
}

function renderAppVersionStatus() {
  if ( appVersionStatus ) {
    appVersionStatus.textContent = t( appVersionStatusState.key, appVersionStatusState.params );
  }
}

function setAppVersionStatus( key, params = {} ) {
  appVersionStatusState = { key, params };
  renderAppVersionStatus();
}

async function handleFullscreenAction() {
  if ( isStandaloneApp() ) {
    setFullscreenStatus( 'fullscreen.installed' );
    return;
  }

  if ( deferredInstallPrompt ) {
    deferredInstallPrompt.prompt();
    const choice = await deferredInstallPrompt.userChoice.catch( () => null );
    deferredInstallPrompt = null;
    setFullscreenStatus( choice?.outcome === 'accepted' ? 'fullscreen.installedDone' : 'fullscreen.installSkipped' );
    return;
  }

  if ( document.fullscreenElement ) {
    await document.exitFullscreen?.();
    setFullscreenStatus( 'fullscreen.exited' );
    return;
  }

  if ( document.documentElement.requestFullscreen ) {
    try {
      await document.documentElement.requestFullscreen( { navigationUI: 'hide' } );
      setFullscreenStatus( 'fullscreen.active' );
      return;
    } catch ( error ) {
      // iOS Safari exposes no reliable requestFullscreen path for page UI.
    }
  }

  setFullscreenStatus( 'fullscreen.useMenu' );
}

async function handleAppUpdateAction() {
  if ( ! ( 'serviceWorker' in navigator ) ) {
    setAppVersionStatus( 'version.unavailable' );
    return;
  }

  setAppVersionStatus( 'version.checking' );

  try {
    const registration = await navigator.serviceWorker.getRegistration();
    if ( registration ) {
      await registration.update();
      registration.waiting?.postMessage( { type: 'SKIP_WAITING' } );
    }

    setAppVersionStatus( 'version.updated' );
    window.setTimeout( () => window.location.reload(), 650 );
  } catch ( error ) {
    setAppVersionStatus( 'version.unavailable' );
  }
}

function registerScannerServiceWorker() {
  const isLocalhost = [ 'localhost', '127.0.0.1', '::1' ].includes( window.location.hostname );

  if ( ! config.serviceWorkerUrl || ! ( 'serviceWorker' in navigator ) || ( ! window.isSecureContext && ! isLocalhost ) ) {
    return;
  }

  window.addEventListener( 'load', () => {
    navigator.serviceWorker.register( config.serviceWorkerUrl ).catch( () => {} );
  } );
}

function setLocationStatus( text ) {
  if ( locationStatus ) locationStatus.textContent = text;
}

function renderSelectedBuilding() {
  const title = selectedBuilding?.title || t( 'location.choose' );
  const address = selectedBuilding?.address || t( 'location.tap' );

  selectedBuildingNameNodes.forEach( ( node ) => {
    node.textContent = title;
  } );

  selectedBuildingAddressNodes.forEach( ( node ) => {
    node.textContent = address;
  } );
}

function setSelectedBuilding( building, options = {} ) {
  if ( ! building ) return;
  selectedBuilding = building;
  renderSelectedBuilding();
  renderLocationList( locationSearch?.value || '' );
  sendScannerHeartbeat();

  if ( options.persist !== false ) {
    try {
      window.localStorage.setItem( buildingStorageKey, String( building.id ) );
    } catch ( error ) {
      // Storage can be unavailable in strict privacy contexts.
    }
  }

  if ( options.close !== false ) closeLocationSheet();
}

function restoreSelectedBuilding() {
  let storedId = '';

  try {
    storedId = window.localStorage.getItem( buildingStorageKey ) || '';
  } catch ( error ) {
    storedId = '';
  }

  selectedBuilding =
    buildings.find( ( building ) => String( building.id ) === storedId ) ||
    buildings.find( ( building ) => building.id === Number( config.selectedBuilding?.id || 0 ) ) ||
    buildings[0] ||
    normalizeBuilding( config.selectedBuilding || {} );

  renderSelectedBuilding();
}

function renderLocationList( query = '' ) {
  if ( ! locationList ) return;

  const normalizedQuery = query.trim().toLowerCase();
  const filteredBuildings = buildings.filter( ( building ) => {
    const haystack = `${building.title} ${building.address}`.toLowerCase();
    return ! normalizedQuery || haystack.includes( normalizedQuery );
  } );

  if ( ! filteredBuildings.length ) {
    locationList.innerHTML = `
      <div class="scanner-history-row scanner-history-row--empty">
        <span class="scanner-history-icon scanner-history-icon--warning"></span>
        <div>
          <strong>${escapeHtml( t( 'location.none' ) )}</strong>
          <span>${escapeHtml( t( 'location.noneCopy' ) )}</span>
        </div>
      </div>
    `;
    return;
  }

  locationList.innerHTML = filteredBuildings.map( ( building ) => `
    <button class="scanner-location-option${selectedBuilding?.id === building.id ? ' is-selected' : ''}" type="button" data-location-option="${building.id}">
      <span class="scanner-location-radio" aria-hidden="true"></span>
      <span>
        <strong>${escapeHtml( building.title )}</strong>
        ${building.address ? `<span>${escapeHtml( building.address )}</span>` : ''}
      </span>
    </button>
  ` ).join( '' );

  locationList.querySelectorAll( '[data-location-option]' ).forEach( ( button ) => {
    button.addEventListener( 'click', () => {
      const building = buildings.find( ( item ) => String( item.id ) === button.getAttribute( 'data-location-option' ) );
      setSelectedBuilding( building );
    } );
  } );
}

function distanceInMeters( from, to ) {
  const earthRadius = 6371000;
  const lat1 = from.latitude * Math.PI / 180;
  const lat2 = to.latitude * Math.PI / 180;
  const deltaLat = ( to.latitude - from.latitude ) * Math.PI / 180;
  const deltaLng = ( to.longitude - from.longitude ) * Math.PI / 180;
  const a = Math.sin( deltaLat / 2 ) ** 2 + Math.cos( lat1 ) * Math.cos( lat2 ) * Math.sin( deltaLng / 2 ) ** 2;
  return earthRadius * 2 * Math.atan2( Math.sqrt( a ), Math.sqrt( 1 - a ) );
}

function detectNearestBuilding() {
  if ( ! navigator.geolocation ) {
    setLocationStatus( t( 'location.unavailable' ) );
    return;
  }

  const candidates = buildings.filter( ( building ) => building.latitude !== null && building.longitude !== null );
  if ( ! candidates.length ) {
    setLocationStatus( t( 'location.noCoordinates' ) );
    return;
  }

  locationDetect.disabled = true;
  setLocationStatus( t( 'location.finding' ) );

  navigator.geolocation.getCurrentPosition(
    ( position ) => {
      const current = {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
      };
      const nearest = candidates
        .map( ( building ) => ( {
          building,
          distance: distanceInMeters( current, building ),
        } ) )
        .sort( ( a, b ) => a.distance - b.distance )[0];

      if ( nearest?.building ) {
        setLocationStatus( t( 'location.selectedNearest', { distance: Math.round( nearest.distance ) } ) );
        setSelectedBuilding( nearest.building, { close: false } );
      }

      locationDetect.disabled = false;
    },
    () => {
      setLocationStatus( t( 'location.denied' ) );
      locationDetect.disabled = false;
    },
    {
      enableHighAccuracy: true,
      maximumAge: 60000,
      timeout: 9000,
    }
  );
}

function loadScanHistory() {
  try {
    const stored = JSON.parse( window.localStorage.getItem( historyStorageKey ) || '[]' );
    scanHistory = Array.isArray( stored )
      ? stored.filter( ( item ) => item?.title !== 'Camera unavailable' ).slice( 0, 20 )
      : [];
  } catch ( error ) {
    scanHistory = [];
  }
}

function saveScanHistory() {
  try {
    window.localStorage.setItem( historyStorageKey, JSON.stringify( scanHistory.slice( 0, 20 ) ) );
  } catch ( error ) {
    // Storage can be unavailable in strict privacy contexts.
  }
}

function renderScanHistory() {
  if ( ! scanHistoryList ) return;

  if ( ! scanHistory.length ) {
    scanHistoryList.innerHTML = `
      <article class="scanner-history-row scanner-history-row--empty">
        <span class="scanner-history-icon scanner-history-icon--history" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M4.75 9.25a7.6 7.6 0 1 1 1.42 6.84"></path><path d="M4.75 5.25v4h4"></path><path d="M12 8.25v4.2l2.7 1.6"></path></svg>
        </span>
        <div>
          <strong>${escapeHtml( t( 'history.emptyTitle' ) )}</strong>
          <span>${escapeHtml( t( 'history.emptyCopy' ) )}</span>
        </div>
      </article>
    `;
    return;
  }

  scanHistoryList.innerHTML = scanHistory.map( ( item ) => {
    return `
      <article class="scanner-history-row">
        ${renderHistoryMedia( item )}
        <div>
          <strong>${escapeHtml( item.title )}</strong>
          <span>${sanitizeInlineHtml( item.subtitle || '' )}</span>
          <time>${escapeHtml( item.time || '' )}</time>
        </div>
      </article>
    `;
  } ).join( '' );
}

function renderHistoryMedia( item ) {
  const image = item.image || item.thumb || '';
  const statusBadge = renderHistoryStatusBadge( item.variant );
  if ( image ) {
    return `<span class="scanner-history-avatar scanner-history-avatar--with-status" aria-hidden="true"><img src="${escapeHtml( image )}" alt="">${statusBadge}</span>`;
  }

  if ( item.media === 'member' ) {
    return `<span class="scanner-history-avatar scanner-history-avatar--placeholder scanner-history-avatar--with-status" aria-hidden="true">${renderNoImageSvg()}${statusBadge}</span>`;
  }

  if ( item.media === 'ticket' ) {
    return `<span class="scanner-history-avatar scanner-history-avatar--ticket scanner-history-avatar--with-status" aria-hidden="true">${renderTicketSvg()}${statusBadge}</span>`;
  }

  const iconClass =
    item.variant === 'ok' ? 'scanner-history-icon--success' :
    item.variant === 'bad' ? 'scanner-history-icon--error' :
    item.variant === 'warn' ? 'scanner-history-icon--warning' :
    'scanner-history-icon--scan';

  return `<span class="scanner-history-icon ${iconClass}" aria-hidden="true"></span>`;
}

function renderHistoryStatusBadge( variant ) {
  if ( ! [ 'ok', 'warn', 'bad' ].includes( variant ) ) return '';

  const icon =
    variant === 'ok' ? renderStatusSvg( true ) :
    variant === 'warn' ? renderWarningSvg() :
    renderStatusSvg( false );

  return `<span class="scanner-history-status is-${variant}" aria-hidden="true">${icon}</span>`;
}

function addScanHistory( item ) {
  scanHistory = [
    {
      title: item.title || t( 'history.scan' ),
      subtitle: item.subtitle || selectedBuilding?.title || '',
      variant: item.variant || 'neutral',
      media: item.media || '',
      image: item.image || '',
      thumb: item.thumb || '',
      time: formatHistoryTimestamp( new Date() ),
    },
    ...scanHistory,
  ].slice( 0, 20 );

  saveScanHistory();
  renderScanHistory();
  sendScannerHeartbeat( {
    last_scan_title: item.title || t( 'history.scan' ),
    last_scan_variant: item.variant || 'neutral',
  } );
}

function resolvePayload( text ) {
  const rawText = String( text || '' ).trim();
  let parsed = null;
  let isJson = false;

  try {
    parsed = JSON.parse( rawText );
    isJson = true;
  } catch ( error ) {
    parsed = null;
  }

  const memberCardId = ( () => {
    if ( isJson && parsed && typeof parsed === 'object' && parsed?.member_card_id ) {
      return String( parsed.member_card_id ).trim();
    }

    const compactMatch = rawText.match( /^m:(.+)$/i );
    return compactMatch ? compactMatch[1].trim() : '';
  } )();
  const isMemberCard = memberCardId !== '';
  const ticketHash = ( () => {
    if ( isMemberCard ) return '';
    if ( ! isJson ) return rawText;
    if ( parsed?.barcode_hash ) return String( parsed.barcode_hash ).trim();
    if ( parsed?.qr ) return String( parsed.qr ).trim();
    return '';
  } )();

  return { isMemberCard, memberCardId, ticketHash };
}

async function handleScan( text ) {
  loading?.classList.remove( 'hidden' );
  setStatus( t( 'scan.checkingCode' ) );
  setButton( t( 'scan.checking' ), true );
  scanner?.stop();
  setScannerCloseVisible( false );

  const { isMemberCard, memberCardId, ticketHash } = resolvePayload( text );

  if ( ! isMemberCard && ! ticketHash ) {
    renderError( t( 'scan.invalidQr' ), t( 'scan.invalidQrCopy' ) );
    resetScannerControls();
    return;
  }

  try {
    const url = isMemberCard
      ? buildUrl( config.endpoints.verifyMemberCard, { 'member-card-id': memberCardId, ...getScannerContextParams() } )
      : buildUrl( config.endpoints.verifyTicket, { qr: ticketHash, ...getScannerContextParams() } );

    const json = await requestJson( url );

    if ( isMemberCard ) {
      renderMemberCardResult( json, memberCardId );
    } else {
      renderTicketResult( json, ticketHash );
    }
  } catch ( error ) {
    if ( handleScannerAuthFailure( error ) ) {
      return;
    }

    renderError( t( 'scan.apiError' ), error.message );
  } finally {
    loading?.classList.add( 'hidden' );
    setStatus( t( 'scan.ready' ) );
    setButton( t( 'scan.again' ), false );
  }
}

function getCurrentTicketResultSource() {
  return currentTicketReturnTarget === 'member-tickets'
    ? { source: 'member-tickets', index: currentTicketReturnIndex }
    : {};
}

function syncCurrentMemberTicket( ticket = {} ) {
  const uuid = String( ticket.ticket_uuid || '' );
  const hash = String( ticket.barcode_hash || '' );
  if ( ! uuid && ! hash ) return;

  currentMemberTickets = currentMemberTickets.map( ( item ) => {
    const itemUuid = String( item.ticket_uuid || '' );
    const itemHash = String( item.barcode_hash || '' );
    const matches = ( uuid && itemUuid === uuid ) || ( hash && itemHash === hash );
    if ( ! matches ) return item;

    const nextTicket = { ...item, ...ticket };
    delete nextTicket.verification;
    return nextTicket;
  } );

  if ( currentMemberResultContext?.json ) {
    currentMemberResultContext = {
      ...currentMemberResultContext,
      json: {
        ...currentMemberResultContext.json,
        tickets: currentMemberTickets,
      },
    };
  }
}

async function forceConsumeTicket( ticketHash ) {
  if ( ! ticketHash || forceTicketScanPending || ! config.endpoints?.verifyTicket ) return;

  forceTicketScanPending = true;
  setStatus( t( 'result.forceTicketLoading' ) );

  try {
    const url = buildUrl( config.endpoints.verifyTicket, {
      qr: ticketHash,
      force_scan: '1',
      ...getScannerContextParams(),
    } );
    const json = await requestJson( url );
    syncCurrentMemberTicket( json?.ticket || {} );
    renderTicketResult( json, ticketHash, getCurrentTicketResultSource() );
  } catch ( error ) {
    if ( handleScannerAuthFailure( error ) ) {
      return;
    }

    renderError( t( 'scan.apiError' ), error.message );
  } finally {
    forceTicketScanPending = false;
    setStatus( t( 'scan.ready' ) );
    setButton( t( 'scan.again' ), false );
  }
}

async function resetTicketScan( ticketUuid, ticketHash ) {
  if ( resetTicketScanPending || ! config.endpoints?.resetTicket || ( ! ticketUuid && ! ticketHash ) ) return;

  resetTicketScanPending = true;
  setStatus( t( 'result.resetTicketLoading' ) );

  try {
    const json = await postRestJson( config.endpoints.resetTicket, {
      ticket_uuid: ticketUuid || '',
      qr: ticketHash || '',
      ...getScannerContextParams(),
    } );
    syncCurrentMemberTicket( json?.ticket || {} );
    renderTicketResult( json, ticketHash || json?.ticket?.barcode_hash || '', getCurrentTicketResultSource() );
  } catch ( error ) {
    if ( handleScannerAuthFailure( error ) ) {
      return;
    }

    renderError( t( 'scan.apiError' ), error.message );
  } finally {
    resetTicketScanPending = false;
    setStatus( t( 'scan.ready' ) );
    setButton( t( 'scan.again' ), false );
  }
}

function renderError( title, subtitle, options = {} ) {
  currentResultType = 'error';
  resultBox?.classList.remove( 'hidden' );
  setScannerCloseVisible( true );
  if ( options.logHistory !== false ) {
    addScanHistory( {
      title,
      subtitle: subtitle || selectedBuilding?.title || '',
      variant: options.variant || 'warn',
      media: options.media || '',
      image: options.image || '',
    } );
  }
  if ( ! resultMessage ) return;

  resultMessage.innerHTML = `
    <div class="flex h-full flex-col items-center justify-center gap-[24px] text-center">
      <div class="flex size-[132px] items-center justify-center rounded-[36px] bg-error/10 text-error">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-[56px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </div>
      <div class="space-y-[8px]">
        <div class="text-[24px] font-semibold leading-tight">${escapeHtml( title )}</div>
        ${subtitle ? `<div class="max-w-[280px] break-words text-[14px] leading-[1.35] text-muted">${escapeHtml( subtitle )}</div>` : ''}
      </div>
    </div>
  `;
  resetResultScroll();
}

function renderMemberCardResult( json, cardId, options = {} ) {
  currentTicketReturnTarget = '';
  currentTicketReturnIndex = -1;

  if ( ! json?.user ) {
    renderError( json?.error || t( 'result.invalidCard' ), cardId, { variant: 'bad' } );
    return;
  }

  const subscription = json.subscription || {};
  const hasValidSubscription = Boolean( json.valid && subscription?.valid !== false );
  const photo = json.user?.photo || '';
  const photoThumb = json.user?.photo_thumb || photo;
  const memberName = json.user?.name || t( 'result.memberFallback' );
  const memberEmail = json.user?.email || '';
  const subscriptionTitle = subscription?.name || t( 'result.noSubscription' );
  const subscriptionReason = hasValidSubscription
    ? ( subscription?.created_at ? t( 'result.memberSinceValue', { date: subscription.created_at } ) : t( 'result.subscriptionActive' ) )
    : ( subscription?.reason || json.subscription_reason || t( 'result.subscriptionMissing' ) );

  currentMemberTickets = Array.isArray( json.tickets ) ? json.tickets : [];
  currentMemberResultContext = {
    json: { ...json, tickets: currentMemberTickets },
    cardId,
    source: options.source === 'manual-search' ? 'manual-search' : '',
  };
  currentResultType = 'member';

  if ( options.logHistory !== false ) {
    addScanHistory( {
      title: memberName,
      subtitle: [
        subscriptionTitle,
        hasValidSubscription ? t( 'result.memberValidTitle' ) : t( 'result.memberInvalidTitle' ),
      ].filter( Boolean ).join( ' · ' ),
      variant: hasValidSubscription ? 'ok' : 'bad',
      media: 'member',
      image: photoThumb,
    } );
  }

  resultBox?.classList.remove( 'hidden' );
  setScannerCloseVisible( false );
  if ( ! resultMessage ) return;

  resultMessage.innerHTML = `
    <div class="scanner-member-card-result">
      <div class="scanner-member-photo-card${photo ? '' : ' is-empty'}">
        ${photo ? `<img src="${escapeHtml( photo )}" alt="">` : renderEmptyVisual( t( 'result.memberFallback' ) )}
        <button class="scanner-member-card-back" type="button" data-scanner-result-back aria-label="${escapeHtml( t( 'scan.back' ) )}">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m15 18-6-6 6-6"></path>
          </svg>
        </button>
        <span class="scanner-member-result-status ${hasValidSubscription ? 'is-valid' : 'is-invalid'}" aria-hidden="true">
          ${renderStatusSvg( hasValidSubscription )}
        </span>
        <div class="scanner-result-gradient">
          <strong>${escapeHtml( memberName )}</strong>
          ${memberEmail ? `<span>${escapeHtml( memberEmail )}</span>` : ''}
        </div>
      </div>

      <div class="scanner-member-card-actions">
        ${renderMemberCardActionRow( {
          icon: renderUserSvg(),
          title: subscriptionTitle,
          subtitle: subscriptionReason,
          variant: hasValidSubscription ? '' : 'danger',
        } )}
        ${renderMemberCardActionRow( {
          icon: renderTicketSvg(),
          title: t( 'memberTickets.actionTitle' ),
          subtitle: currentMemberTickets.length
            ? t( 'memberTickets.actionCopyCount', { count: currentMemberTickets.length } )
            : t( 'memberTickets.actionCopy' ),
          button: true,
          arrow: true,
          attrs: 'data-member-tickets-open',
        } )}
      </div>
    </div>
  `;
  resetResultScroll();
}

function renderTicketResult( json, ticketHash, options = {} ) {
  const scanResult = json?.scan?.result || ( json?.valid ? 'ok' : 'invalid' );
  const hasTicketInfo = !! ( json?.ticket || json?.event || json?.building );
  const returnTarget = options.source === 'member-tickets' ? 'member-tickets' : '';
  const returnIndex = Number.isInteger( options.index ) ? options.index : currentTicketReturnIndex;

  currentTicketReturnTarget = returnTarget === 'member-tickets' ? 'member-tickets' : '';
  currentTicketReturnIndex = currentTicketReturnTarget ? returnIndex : -1;

  if ( ! hasTicketInfo ) {
    renderError( json?.error || t( 'result.invalidTicket' ), ticketHash, { variant: 'bad' } );
    return;
  }

  currentResultType = 'ticket';

  const eventImg = json?.event?.image || '';
  const eventTitle = json?.event?.title || t( 'result.ticket' );
  const buildingTitle = json?.building?.title || '';
  const slotStart = formatDateTime( json?.ticket?.slot_start || '' );
  const slotEnd = formatTime( json?.ticket?.slot_end || '' );
  const checkedAt = formatDateTime( json?.timestamp || '' );
  const category = cleanCategory( json?.ticket?.price_category || '' );
  const price = formatTicketPrice( json?.ticket?.unit_price, json?.ticket?.currency );
  const status = getTicketStatusMeta( scanResult, json?.error || '' );
  const slotLine = slotStart ? `${slotStart}${slotEnd ? ` - ${slotEnd}` : ''}` : '';
  const attendeeTitle = json?.ticket?.attendee_name || t( 'result.attendee' );
  const attendeeSubtitle = [ category, price ].filter( Boolean ).join( ' · ' );
  const checkedLine = checkedAt ? t( 'result.checkedAtValue', { date: checkedAt } ) : '';
  const slotStatusSubtitle = [ status.title, checkedLine ].filter( Boolean ).join( ' · ' );
  const eventSubtitle = getTicketEventSubtitle( json?.event, buildingTitle );
  const canForceScan = Boolean( json?.scan?.can_force_scan || scanResult === 'outside_slot' ) && Boolean( ticketHash ) && ! forceTicketScanPending;
  const canResetScan = isTicketResetAvailable( json ) && ! resetTicketScanPending;
  const actionRows = [
    renderMemberCardActionRow( {
      icon: renderTicketSvg(),
      title: slotLine || status.title,
      subtitle: slotStatusSubtitle,
      variant: status.variant,
    } ),
    ( json?.ticket?.attendee_name || attendeeSubtitle ) ? renderMemberCardActionRow( {
      icon: renderUserSvg(),
      title: attendeeTitle,
      subtitle: attendeeSubtitle,
    } ) : '',
    canForceScan ? renderMemberCardActionRow( {
      icon: renderWarningSvg(),
      title: t( 'result.forceTicketTitle' ),
      subtitle: t( 'result.forceTicketCopy' ),
      button: true,
      attrs: `data-ticket-force-scan="${escapeHtml( ticketHash )}"`,
      variant: 'warning',
    } ) : '',
    canResetScan ? renderMemberCardActionRow( {
      icon: renderTicketSvg(),
      title: t( 'result.resetTicketTitle' ),
      subtitle: t( 'result.resetTicketCopy' ),
      button: true,
      attrs: `data-ticket-reset-uuid="${escapeHtml( json?.ticket?.ticket_uuid || '' )}" data-ticket-reset-qr="${escapeHtml( ticketHash || json?.ticket?.barcode_hash || '' )}"`,
      variant: 'warning',
    } ) : '',
  ].filter( Boolean ).join( '' );

  addScanHistory( {
    title: eventTitle,
    subtitle: [
      ( json?.ticket?.attendee_name || '' ),
      status.title,
      eventSubtitle || buildingTitle || selectedBuilding?.title || '',
    ].filter( Boolean ).join( ' · ' ),
    variant: status.historyVariant,
    media: 'ticket',
    image: json?.event?.thumb || json?.event?.image || '',
  } );

  resultBox?.classList.remove( 'hidden' );
  setScannerCloseVisible( false );
  if ( ! resultMessage ) return;

  resultMessage.innerHTML = `
    <div class="scanner-member-card-result scanner-ticket-card-result">
      <div class="scanner-member-photo-card scanner-ticket-photo-card${eventImg ? '' : ' is-empty'}">
        ${eventImg ? `<img src="${escapeHtml( eventImg )}" alt="">` : renderTicketEmptyVisual()}
        <button class="scanner-member-card-back" type="button" data-scanner-result-back aria-label="${escapeHtml( t( 'scan.back' ) )}">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m15 18-6-6 6-6"></path>
          </svg>
        </button>
        <span class="scanner-member-result-status ${status.className}" aria-hidden="true">
          ${renderTicketStatusSvg( scanResult )}
        </span>
        <div class="scanner-result-gradient">
          <strong>${escapeHtml( eventTitle )}</strong>
          ${eventSubtitle ? `<span>${sanitizeInlineHtml( eventSubtitle )}</span>` : ''}
        </div>
      </div>

      <div class="scanner-member-card-actions scanner-ticket-card-actions">
        ${actionRows}
      </div>
    </div>
  `;
  resetResultScroll();
}

function renderMemberCardActionRow( options = {} ) {
  const tag = options.button ? 'button' : 'div';
  const attrs = options.button ? `type="button" ${options.attrs || ''}` : '';
  const variantClass = options.variant ? ` scanner-member-action-row--${options.variant}` : '';

  return `
    <${tag} class="scanner-member-action-row${variantClass}" ${attrs}>
      <span class="scanner-member-action-icon" aria-hidden="true">${options.icon || ''}</span>
      <span class="scanner-member-action-copy">
        <strong>${escapeHtml( options.title || '' )}</strong>
        ${options.subtitle ? `<small>${escapeHtml( options.subtitle )}</small>` : ''}
      </span>
      ${options.arrow ? `
        <svg class="scanner-member-action-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="m9 18 6-6-6-6"></path>
        </svg>
      ` : ''}
    </${tag}>
  `;
}

function renderStatusSvg( isValid ) {
  return isValid
    ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"></path></svg>`
    : `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>`;
}

function renderWarningSvg() {
  return `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 31 31" fill="currentColor" aria-hidden="true">
      <path d="M29.6,23.2L20.2,4.8c-1-1.9-2.8-3-4.9-3s-3.9,1.1-4.9,3L1,23.2c-.9,1.8-.8,3.9.2,5.6,1,1.7,2.8,2.7,4.8,2.7h18.6c2,0,3.8-1,4.8-2.7,1-1.7,1.1-3.8.2-5.6ZM26.9,27.3c-.5.8-1.3,1.2-2.3,1.2H6c-.9,0-1.8-.5-2.3-1.2-.5-.8-.5-1.8-.1-2.6L13,6.2c.5-.9,1.3-1.4,2.3-1.4s1.9.5,2.3,1.4l9.4,18.5c.4.8.4,1.8-.1,2.6ZM16.8,12.1v7.1c0,.8-.7,1.5-1.5,1.5s-1.5-.7-1.5-1.5v-7.1c0-.8.7-1.5,1.5-1.5s1.5.7,1.5,1.5ZM16.8,24.3c0,.8-.7,1.5-1.5,1.5s-1.5-.7-1.5-1.5.7-1.5,1.5-1.5,1.5.7,1.5,1.5Z"/>
    </svg>
  `;
}

function getTicketStatusTitle( scanResult ) {
  if ( scanResult === 'ok' ) return t( 'result.ticketValidTitle' );
  if ( scanResult === 'already_used' ) return t( 'result.ticketUsedTitle' );
  if ( scanResult === 'outside_slot' || scanResult === 'too_early' || scanResult === 'expired' ) return t( 'result.ticketOutsideSlotTitle' );
  if ( scanResult === 'future_date' ) return t( 'result.ticketFutureDateTitle' );
  if ( scanResult === 'past_date' ) return t( 'result.ticketPastDateTitle' );
  if ( scanResult === 'wrong_location' ) return t( 'result.ticketWrongLocationTitle' );
  if ( scanResult === 'reset_not_allowed' ) return t( 'result.ticketResetNotAllowedTitle' );
  return t( 'result.ticketInvalidTitle' );
}

function getTicketStatusMeta( scanResult, fallback = '' ) {
  const title = getTicketStatusTitle( scanResult ) || fallback || ( scanResult || '' ).toUpperCase();
  const isValid = scanResult === 'ok';
  const isWarning = scanResult === 'already_used' || scanResult === 'outside_slot' || scanResult === 'too_early' || scanResult === 'expired';

  return {
    title,
    className: isValid ? 'is-valid' : isWarning ? 'is-warning' : 'is-invalid',
    variant: isValid ? '' : isWarning ? 'warning' : 'danger',
    historyVariant: isValid ? 'ok' : isWarning ? 'warn' : 'bad',
  };
}

function getTicketEventSubtitle( event, buildingTitle ) {
  const eventType = String( event?.type || event?.post_type || '' );
  const exhibitionType = String( event?.exhibition_type || '' ).toLowerCase();
  const isPermanent = event?.is_permanent === true ||
    String( event?.is_permanent || '' ) === '1' ||
    exhibitionType === 'permanent';

  return eventType === 'exhibition' && ! isPermanent ? buildingTitle : '';
}

function getTicketLocalSlotScanResult( ticket = {} ) {
  const start = parseMysqlDate( ticket.slot_start || '' );
  const end = parseMysqlDate( ticket.slot_end || '' );
  const now = new Date();
  const graceMs = 15 * 60 * 1000;

  if ( start ) {
    const slotDayStart = new Date( start );
    slotDayStart.setHours( 0, 0, 0, 0 );
    const nowDayStart = new Date( now );
    nowDayStart.setHours( 0, 0, 0, 0 );

    if ( nowDayStart < slotDayStart ) {
      return 'future_date';
    }

    if ( now < new Date( start.getTime() - graceMs ) ) {
      return 'outside_slot';
    }
  }

  if ( end ) {
    const slotDayEnd = new Date( end );
    slotDayEnd.setHours( 0, 0, 0, 0 );
    const nowDayStart = new Date( now );
    nowDayStart.setHours( 0, 0, 0, 0 );

    if ( nowDayStart > slotDayEnd ) {
      return 'past_date';
    }

    if ( now > new Date( end.getTime() + graceMs ) ) {
      return 'outside_slot';
    }
  }

  return 'ok';
}

function getTicketLocalStoredStatusScanResult( ticket = {} ) {
  const status = String( ticket.status || '' ).toLowerCase();
  if ( ! status || status === 'valid' || status === '0' || status === 'null' ) return '';

  if ( status === 'used' ) {
    const windowResult = getTicketLocalSlotScanResult( ticket );
    if ( windowResult === 'future_date' || windowResult === 'past_date' ) return windowResult;
    return 'already_used';
  }

  return 'invalid';
}

function getTicketLocalLocationScanResult( ticket = {} ) {
  const selectedBuildingId = Number( selectedBuilding?.id || 0 );
  const ticketBuildingId = Number( ticket.building?.id || ticket.building_id || 0 );

  if ( selectedBuildingId && ticketBuildingId && selectedBuildingId !== ticketBuildingId ) {
    return 'wrong_location';
  }

  return '';
}

function formatTicketPrice( value, currency ) {
  const rawValue = String( value ?? '' ).trim();
  if ( ! rawValue ) return '';

  const numeric = Number( rawValue );
  const displayValue = Number.isFinite( numeric )
    ? numeric.toLocaleString( document.documentElement.lang || 'el-GR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    } )
    : rawValue;

  const code = String( currency || '' ).trim().toUpperCase();
  const symbol = code === 'EUR' ? '€' : code;

  return symbol ? `${displayValue} ${symbol}` : displayValue;
}

function renderTicketStatusSvg( scanResult ) {
  if ( scanResult === 'ok' ) {
    return renderStatusSvg( true );
  }

  if ( scanResult === 'already_used' || scanResult === 'outside_slot' || scanResult === 'too_early' || scanResult === 'expired' ) {
    return renderWarningSvg();
  }

  return renderStatusSvg( false );
}

function renderUserSvg() {
  return `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 31 31" aria-hidden="true">
      <path d="M20,15.3c2.1-1.4,3.5-3.8,3.5-6.5,0-4.3-3.5-7.8-7.8-7.8s-7.8,3.5-7.8,7.8,1.4,5.1,3.5,6.5c-5,1.8-8.6,6.5-8.6,12.1v1.7c0,.6.4,1,1,1s1-.4,1-1v-1.7c0-6,4.9-10.8,10.8-10.8s10.8,4.9,10.8,10.8v1.7c0,.6.4,1,1,1s1-.4,1-1v-1.7c0-5.6-3.6-10.3-8.6-12.1ZM10,8.9c0-3.2,2.6-5.8,5.8-5.8s5.8,2.6,5.8,5.8-2.6,5.8-5.8,5.8-5.8-2.6-5.8-5.8Z"/>
    </svg>
  `;
}

function renderTicketSvg() {
  return `
    <svg width="26" height="26" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <path d="M14.7527 2.38969C14.4579 2.09618 13.9777 2.09518 13.6817 2.38969L2.09242 13.9346C1.79669 14.2294 1.79706 14.7084 2.09173 15.0022L3.75634 16.6604C5.14815 16.1457 6.77392 16.4437 7.89296 17.5585C9.01167 18.6731 9.30951 20.2916 8.79307 21.6778L10.4598 23.3381C10.7548 23.6305 11.2345 23.6303 11.5301 23.3361L23.1194 11.7912C23.4151 11.4963 23.4142 11.018 23.1194 10.7243L21.4458 9.05708C20.0607 9.55813 18.448 9.25752 17.3368 8.15078C16.2247 7.04293 15.9243 5.43669 16.4263 4.05689L14.7527 2.38969ZM18.0481 3.27257C18.3196 3.54307 18.3762 3.96115 18.1857 4.29328C17.7004 5.13841 17.8207 6.23301 18.5413 6.95087C19.2627 7.6692 20.3619 7.78919 21.2071 7.30581C21.5407 7.11498 21.9611 7.1706 22.2331 7.44158L24.3239 9.52437C25.2846 10.4814 25.2841 12.0342 24.3239 12.9911L12.7346 24.536C11.7966 25.4702 10.2901 25.4902 9.3251 24.5994C9.30125 24.5803 9.27806 24.5594 9.25592 24.5374L7.16858 22.458C6.89488 22.1854 6.83981 21.7634 7.03505 21.4304C7.53158 20.5838 7.41461 19.4806 6.68913 18.7577C5.96342 18.0347 4.8554 17.9177 4.00541 18.4124C3.6712 18.6068 3.24753 18.552 2.97385 18.2794L0.887896 16.2014C-0.0726209 15.2443 -0.0716232 13.6921 0.888587 12.7354L12.4779 1.19047C13.4384 0.234024 14.9965 0.232974 15.9573 1.18978L18.0481 3.27257Z"/>
      <path d="M10.9602 7.64199L9.09423 5.78319C8.76165 5.45189 8.76165 4.91528 9.09423 4.58397C9.42681 4.25266 9.96548 4.25266 10.2981 4.58397L12.164 6.44276C12.4965 6.77408 12.4966 7.31071 12.164 7.64199C11.8315 7.97326 11.2928 7.9732 10.9602 7.64199Z"/>
      <path d="M15.3352 12.0053L13.4692 10.1465C13.1366 9.81517 13.1366 9.27856 13.4692 8.94725C13.8018 8.61595 14.3405 8.61595 14.6731 8.94725L16.539 10.806C16.8715 11.1374 16.8716 11.674 16.539 12.0053C16.2065 12.3365 15.6678 12.3365 15.3352 12.0053Z"/>
      <path d="M19.7258 16.3725L17.8599 14.5137C17.5273 14.1824 17.5273 13.6457 17.8599 13.3144C18.1924 12.9831 18.7311 12.9831 19.0637 13.3144L20.9296 15.1732C21.2621 15.5045 21.2622 16.0412 20.9296 16.3725C20.5971 16.7037 20.0584 16.7037 19.7258 16.3725Z"/>
    </svg>
  `;
}

function renderClockSvg() {
  return `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M12 22a10 10 0 1 1 10-10 10.01 10.01 0 0 1-10 10Zm0-18.1a8.1 8.1 0 1 0 8.1 8.1A8.11 8.11 0 0 0 12 3.9Z"/>
      <path d="M15.4 15.95a.96.96 0 0 1-.48-.13l-3.42-2.03a.95.95 0 0 1-.46-.82V7.3a.95.95 0 0 1 1.9 0v5.13l2.95 1.75a.95.95 0 0 1-.49 1.77Z"/>
    </svg>
  `;
}

function renderTicketEmptyVisual() {
  return `
    <div class="scanner-empty-visual">
      <div class="scanner-empty-visual-icon" aria-hidden="true">
        ${renderTicketSvg()}
      </div>
      <div class="text-[13px]">${escapeHtml( t( 'result.ticket' ) )}</div>
    </div>
  `;
}

function renderEmptyVisual( label ) {
  return `
    <div class="scanner-empty-visual">
      <div class="scanner-empty-visual-icon" aria-hidden="true">
        ${renderNoImageSvg()}
      </div>
      <div class="text-[13px]">${escapeHtml( label )}</div>
    </div>
  `;
}

function resetResultScroll() {
  if ( resultMessage ) {
    resultMessage.scrollTop = 0;
  }
}

function parseMysqlDate( value ) {
  if ( ! value ) return null;
  const date = new Date( String( value ).replace( ' ', 'T' ) );
  return Number.isNaN( date.getTime() ) ? null : date;
}

const pad = ( number ) => String( number ).padStart( 2, '0' );

function getCurrentMysqlDateTime() {
  const now = new Date();
  return `${now.getFullYear()}-${pad( now.getMonth() + 1 )}-${pad( now.getDate() )} ${pad( now.getHours() )}:${pad( now.getMinutes() )}:${pad( now.getSeconds() )}`;
}

function formatDateTime( value ) {
  const date = parseMysqlDate( value );
  if ( ! date ) return value || '';
  return `${pad( date.getDate() )}/${pad( date.getMonth() + 1 )}/${date.getFullYear()} ${pad( date.getHours() )}:${pad( date.getMinutes() )}`;
}

function formatTime( value ) {
  const date = parseMysqlDate( value );
  if ( ! date ) return value || '';
  return `${pad( date.getHours() )}:${pad( date.getMinutes() )}`;
}

function formatHistoryTimestamp( date ) {
  if ( ! ( date instanceof Date ) || Number.isNaN( date.getTime() ) ) return '';
  return `${pad( date.getDate() )}/${pad( date.getMonth() + 1 )}/${date.getFullYear()} ${pad( date.getHours() )}:${pad( date.getMinutes() )}`;
}

function isSameLocalDay( dateA, dateB ) {
  if ( ! dateA || ! dateB ) return false;

  return dateA.getFullYear() === dateB.getFullYear() &&
    dateA.getMonth() === dateB.getMonth() &&
    dateA.getDate() === dateB.getDate();
}

function isTicketDateToday( ticket = {} ) {
  const slotDate = parseMysqlDate( ticket.slot_start || '' ) || parseMysqlDate( ticket.slot_end || '' );
  return isSameLocalDay( slotDate, new Date() );
}

function isTicketResetAvailable( json = {} ) {
  const ticket = json?.ticket || {};
  const status = String( ticket.status || '' ).toLowerCase();
  const hasIdentifier = Boolean( ticket.ticket_uuid || ticket.barcode_hash );

  return Boolean( config.endpoints?.resetTicket ) &&
    hasIdentifier &&
    status === 'used' &&
    isTicketDateToday( ticket );
}

function cleanCategory( value ) {
  if ( ! value ) return '';
  const parts = String( value ).split( ':' );
  return ( parts.length > 1 ? parts.pop() : value ).trim();
}

function renderNoImageSvg() {
  return `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 67 66" aria-hidden="true">
      <path d="M54.9819 60.2472C59.9403 60.2472 63.0392 55.0274 60.5601 50.8516L47.0947 28.1711C44.6155 23.9952 38.4175 23.9952 35.9383 28.1711L31.569 35.5306C29.8837 38.3692 25.6705 38.3692 23.9852 35.5306C22.2999 32.692 18.0867 32.692 16.4014 35.5306L7.30517 50.8519C4.82599 55.0277 7.92497 60.2475 12.8833 60.2475L38.6595 60.2474L54.9819 60.2472Z"></path>
      <ellipse cx="22.9326" cy="16.9142" rx="8.25" ry="8.125"></ellipse>
    </svg>
  `;
}

function returnToScannerStart() {
  setActiveView( 'scan' );
  scanner?.stop();
  resultBox?.classList.add( 'hidden' );
  closeManualSearchSheet();
  closeMemberTicketsSheet();
  currentMemberResultContext = null;
  currentMemberTickets = [];
  currentTicketReturnTarget = '';
  currentTicketReturnIndex = -1;
  currentResultType = '';
  resetScannerControls();
}

function returnToManualSearchResults() {
  setActiveView( 'scan' );
  scanner?.stop();
  resultBox?.classList.add( 'hidden' );
  closeMemberTicketsSheet();
  currentTicketReturnTarget = '';
  currentTicketReturnIndex = -1;
  currentResultType = '';
  resetScannerControls();
  openManualSearchSheet();
}

function returnToMemberTicketsList() {
  const memberContext = currentMemberResultContext;

  currentTicketReturnTarget = '';
  currentTicketReturnIndex = -1;

  if ( memberContext?.json?.user ) {
    renderMemberCardResult( memberContext.json, memberContext.cardId, {
      source: memberContext.source,
      logHistory: false,
    } );
  }

  openMemberTicketsSheet();
}

function handleScannerResultBack() {
  if ( currentResultType === 'ticket' && currentTicketReturnTarget === 'member-tickets' ) {
    returnToMemberTicketsList();
    return;
  }

  if ( currentResultType === 'member' && currentMemberResultContext?.source === 'manual-search' ) {
    returnToManualSearchResults();
    return;
  }

  returnToScannerStart();
}

scanBtn?.addEventListener( 'click', startScanner );

manualSearchOpenButtons.forEach( ( button ) => {
  button.addEventListener( 'click', () => openManualSearchSheet( button ) );
} );

manualSearchCloseButtons.forEach( ( button ) => {
  button.addEventListener( 'click', closeManualSearchSheet );
} );

memberTicketsCloseButtons.forEach( ( button ) => {
  button.addEventListener( 'click', closeMemberTicketsSheet );
} );

manualSearchForm?.addEventListener( 'submit', submitManualSearchForm );

manualSearchInput?.addEventListener( 'input', () => {
  const query = String( manualSearchInput.value || '' ).trim();
  if ( ! manualSearchCachedQuery || query === manualSearchCachedQuery ) return;

  manualSearchCachedQuery = '';
  manualSearchCachedMatches = [];
  setFormMessage( manualSearchMessage );
  clearManualSearchResults();
} );

manualSearchResults?.addEventListener( 'pointerdown', beginManualSearchResultPress );
manualSearchResults?.addEventListener( 'pointerup', finishManualSearchResultPress );
manualSearchResults?.addEventListener( 'pointercancel', () => {
  manualSearchPointerSelection = null;
} );

manualSearchResults?.addEventListener( 'click', ( event ) => {
  if ( Date.now() < manualSearchSuppressClickUntil ) {
    event.preventDefault();
    return;
  }

  const button = getManualSearchResultButton( event.target );
  if ( ! button ) return;
  selectManualSearchResult( button.getAttribute( 'data-manual-search-card-id' ) || '' );
} );

memberTicketsList?.addEventListener( 'click', ( event ) => {
  const target = event.target instanceof Element ? event.target : null;
  const button = target?.closest( '[data-member-ticket-index]' );
  if ( ! button ) return;

  event.preventDefault();
  selectMemberTicket( button.getAttribute( 'data-member-ticket-index' ) || '' );
} );

resultMessage?.addEventListener( 'click', ( event ) => {
  const target = event.target instanceof Element ? event.target : null;
  const forceButton = target?.closest( '[data-ticket-force-scan]' );
  if ( forceButton ) {
    event.preventDefault();
    forceConsumeTicket( forceButton.getAttribute( 'data-ticket-force-scan' ) || '' );
    return;
  }

  const resetButton = target?.closest( '[data-ticket-reset-uuid], [data-ticket-reset-qr]' );
  if ( resetButton ) {
    event.preventDefault();
    resetTicketScan(
      resetButton.getAttribute( 'data-ticket-reset-uuid' ) || '',
      resetButton.getAttribute( 'data-ticket-reset-qr' ) || ''
    );
    return;
  }

  if ( target?.closest( '[data-scanner-result-back]' ) ) {
    handleScannerResultBack();
    return;
  }

  if ( target?.closest( '[data-member-tickets-open]' ) ) {
    openMemberTicketsSheet();
  }
} );

scannerCloseButton?.addEventListener( 'click', returnToScannerStart );

scannerSheets.forEach( bindSheetDrag );

navButtons.forEach( ( button ) => {
  button.addEventListener( 'click', () => {
    const view = button.getAttribute( 'data-scanner-nav' );
    if ( ! view ) return;

    if ( view === 'scan' ) {
      returnToScannerStart();
      return;
    }

    setActiveView( view );
  } );
} );

notificationsOpenButtons.forEach( ( button ) => {
  button.addEventListener( 'click', () => setActiveView( 'notifications' ) );
} );

notificationsRefreshButton?.addEventListener( 'click', refreshNotifications );

notificationsList?.addEventListener( 'touchstart', handleNotificationsPullStart, { passive: true } );
notificationsList?.addEventListener( 'touchmove', handleNotificationsPullMove, { passive: false } );
notificationsList?.addEventListener( 'touchend', handleNotificationsPullEnd );
notificationsList?.addEventListener( 'touchcancel', resetNotificationsPull );

notificationCloseButtons.forEach( ( button ) => {
  button.addEventListener( 'click', closeNotificationDetail );
} );

notificationUnreadButton?.addEventListener( 'click', markNotificationUnreadAndClose );

locationOpenButtons.forEach( ( button ) => {
  button.addEventListener( 'click', () => openLocationSheet( button ) );
} );

locationCloseButtons.forEach( ( button ) => {
  button.addEventListener( 'click', closeLocationSheet );
} );

locationSearch?.addEventListener( 'input', () => renderLocationList( locationSearch.value ) );
locationDetect?.addEventListener( 'click', detectNearestBuilding );

accountOpenButtons.forEach( ( button ) => {
  button.addEventListener( 'click', () => openAccountSheet( button ) );
} );

accountCloseButtons.forEach( ( button ) => {
  button.addEventListener( 'click', closeAccountSheet );
} );

accountForm?.addEventListener( 'submit', submitAccountForm );
accountPhoneForm?.addEventListener( 'submit', submitAccountPhoneForm );
accountPhoneResend?.addEventListener( 'click', resendAccountPhoneCode );

passwordOpenButtons.forEach( ( button ) => {
  button.addEventListener( 'click', () => openPasswordSheet( button ) );
} );

passwordCloseButtons.forEach( ( button ) => {
  button.addEventListener( 'click', closePasswordSheet );
} );

passwordForm?.addEventListener( 'submit', submitPasswordForm );

themeOpenButtons.forEach( ( button ) => {
  button.addEventListener( 'click', () => openThemeSheet( button ) );
} );

themeCloseButtons.forEach( ( button ) => {
  button.addEventListener( 'click', closeThemeSheet );
} );

themeModeButtons.forEach( ( button ) => {
  button.addEventListener( 'click', () => {
    setThemeMode( button.getAttribute( 'data-theme-mode' ) || 'default' );
    closeThemeSheet();
  } );
} );

if ( themePreferenceQuery ) {
  const handleThemePreferenceChange = () => {
    if ( currentThemeMode !== 'default' ) return;
    document.documentElement.dataset.scannerTheme = resolveThemeMode( currentThemeMode );
    updateThemeControls();
  };

  if ( typeof themePreferenceQuery.addEventListener === 'function' ) {
    themePreferenceQuery.addEventListener( 'change', handleThemePreferenceChange );
  } else if ( typeof themePreferenceQuery.addListener === 'function' ) {
    themePreferenceQuery.addListener( handleThemePreferenceChange );
  }
}

languageOpenButtons.forEach( ( button ) => {
  button.addEventListener( 'click', () => openLanguageSheet( button ) );
} );

languageCloseButtons.forEach( ( button ) => {
  button.addEventListener( 'click', closeLanguageSheet );
} );

languageModeButtons.forEach( ( button ) => {
  button.addEventListener( 'click', () => {
    setLanguage( button.getAttribute( 'data-language-mode' ) || config.defaultLocale || 'el' );
    closeLanguageSheet();
  } );
} );

logoutOpenButtons.forEach( ( button ) => {
  button.addEventListener( 'click', () => openLogoutSheet( button ) );
} );

logoutCloseButtons.forEach( ( button ) => {
  button.addEventListener( 'click', closeLogoutSheet );
} );

fullscreenAction?.addEventListener( 'click', handleFullscreenAction );
appUpdateAction?.addEventListener( 'click', handleAppUpdateAction );

window.addEventListener( 'beforeinstallprompt', ( event ) => {
  event.preventDefault();
  deferredInstallPrompt = event;
  setFullscreenStatus( 'fullscreen.readyInstall' );
} );

window.addEventListener( 'appinstalled', () => {
  deferredInstallPrompt = null;
  setFullscreenStatus( 'fullscreen.installedDone' );
} );

async function bootScannerApp() {
  try {
    setThemeMode( getStoredValue( themeStorageKey, 'default' ), { persist: false } );
    await setLanguage( getInitialLocale(), { persist: false } );
    restoreSelectedBuilding();
    loadScanHistory();
    renderLocationList();
    renderScanHistory();
    renderAccountSummary();
    renderFullscreenStatus();
    renderAppVersionStatus();
    setActiveView( enteredFromLogin ? 'scan' : getStoredValue( activeViewStorageKey, 'scan' ), { persist: enteredFromLogin } );
    fetchNotifications( { silent: true } );
    startNotificationsAutoRefresh();
    startScannerHeartbeat();
    registerScannerServiceWorker();
  } finally {
    finishBootLoader();
  }
}

bootScannerApp();
