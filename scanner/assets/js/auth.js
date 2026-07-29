const form = document.getElementById( 'scanner-login-form' );
const configElement = document.getElementById( 'iw-scanner-auth-config' );
let config = window.IWScannerAuth || {};

if ( configElement?.textContent ) {
  try {
    config = { ...config, ...JSON.parse( configElement.textContent ) };
  } catch ( error ) {
    // Keep the global fallback when the JSON config is unavailable.
  }
}

const scannerShell = document.querySelector( '[data-scanner-shell]' );
const bootLoader = document.querySelector( '[data-scanner-boot-loader]' );
const stack = document.querySelector( '[data-auth-stack]' );
const screenOrder = [ 'login', 'loading' ];
const screens = Array.from( document.querySelectorAll( '[data-auth-screen]' ) );
const errorBox = form?.querySelector( '[data-scanner-login-error]' );
const submitButton = form?.querySelector( 'button[type="submit"]' );

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

const showScreen = ( screenName ) => {
  const targetScreen = screenOrder.includes( screenName ) ? screenName : 'login';
  const activeIndex = screenOrder.indexOf( targetScreen );

  screens.forEach( ( screen ) => {
    const name = screen.getAttribute( 'data-auth-screen' );
    const index = screenOrder.indexOf( name );
    const isActive = name === targetScreen;

    screen.classList.toggle( 'is-active', isActive );
    screen.classList.toggle( 'is-left', index > -1 && index < activeIndex );
    screen.classList.toggle( 'is-right', index > activeIndex );
    screen.setAttribute( 'aria-hidden', isActive ? 'false' : 'true' );
  } );
};

const showError = ( message ) => {
  if ( ! errorBox ) return;
  errorBox.classList.remove( 'is-empty' );
  errorBox.setAttribute( 'aria-hidden', 'false' );
  errorBox.textContent = message || 'Login failed.';
};

const setLoading = ( isLoading ) => {
  if ( ! submitButton ) return;
  submitButton.disabled = isLoading;
  submitButton.textContent = isLoading ? 'Checking...' : 'Log in';
};

const setRedirectCookie = () => {
  const redirectTo = encodeURIComponent( config.scannerUrl || window.location.href );
  document.cookie = `redirect-login-modal=${redirectTo}; path=/; SameSite=Lax`;
};

const parseJson = ( value ) => {
  try {
    return JSON.parse( value );
  } catch ( error ) {
    return null;
  }
};

const requestJson = async ( url, options = {} ) => {
  if ( typeof window.fetch === 'function' ) {
    const response = await window.fetch( url, {
      credentials: 'same-origin',
      ...options,
    } );

    return {
      ok: response.ok,
      status: response.status,
      data: await response.json().catch( () => null ),
    };
  }

  return new Promise( ( resolve, reject ) => {
    if ( typeof window.XMLHttpRequest !== 'function' ) {
      reject( new Error( 'Browser requests are unavailable.' ) );
      return;
    }

    const request = new XMLHttpRequest();
    request.open( options.method || 'GET', url, true );
    request.withCredentials = options.credentials !== 'omit';

    request.onload = () => {
      resolve( {
        ok: request.status >= 200 && request.status < 300,
        status: request.status,
        data: parseJson( request.responseText ),
      } );
    };

    request.onerror = () => reject( new Error( 'Network request failed.' ) );
    request.onabort = () => reject( new Error( 'Network request aborted.' ) );
    request.send( options.body || null );
  } );
};

const canUseBrowserRequests = () => typeof window.fetch === 'function' || typeof window.XMLHttpRequest === 'function';

showScreen( stack?.getAttribute( 'data-initial-screen' ) || 'login' );
finishBootLoader();

form?.addEventListener( 'submit', async ( event ) => {
  setRedirectCookie();

  if ( ! canUseBrowserRequests() ) {
    return;
  }

  event.preventDefault();
  setLoading( true );
  errorBox?.classList.add( 'is-empty' );
  errorBox?.setAttribute( 'aria-hidden', 'true' );

  const payload = new FormData( form );
  payload.set( 'action', 'iw-auth-login' );

  try {
    const { data: json } = await requestJson( config.ajaxUrl, {
      method: 'POST',
      body: payload,
    } );

    if ( ! json?.success ) {
      showError( json?.data?.message || 'Invalid username or password.' );
      setLoading( false );
      return;
    }

    try {
      window.sessionStorage.setItem( 'iwScannerEntry', 'from-login' );
    } catch ( error ) {
      // Session storage can be unavailable in strict privacy contexts.
    }

    showScreen( 'loading' );
    window.setTimeout( () => {
      window.location.href = config.scannerUrl || json?.data?.redirect || window.location.href;
    }, 360 );
  } catch ( error ) {
    showError( error.message || 'Login failed.' );
    setLoading( false );
  }
} );
