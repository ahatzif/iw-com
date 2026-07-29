const SCANNER_CACHE = 'com-scanner-assets-v1';

self.addEventListener( 'install', ( event ) => {
  event.waitUntil( self.skipWaiting() );
} );

self.addEventListener( 'activate', ( event ) => {
  event.waitUntil( self.clients.claim() );
} );

self.addEventListener( 'message', ( event ) => {
  if ( event.data?.type === 'SKIP_WAITING' ) {
    self.skipWaiting();
  }
} );

self.addEventListener( 'fetch', ( event ) => {
  const request = event.request;

  if ( request.method !== 'GET' ) {
    return;
  }

  const url = new URL( request.url );
  const isScannerAsset = url.origin === self.location.origin && url.pathname.includes( '/scanner/assets/' );

  if ( ! isScannerAsset ) {
    return;
  }

  event.respondWith(
    caches.open( SCANNER_CACHE ).then( async ( cache ) => {
      const cached = await cache.match( request );
      const network = fetch( request )
        .then( ( response ) => {
          if ( response.ok ) {
            cache.put( request, response.clone() );
          }
          return response;
        } )
        .catch( () => cached );

      return cached || network;
    } )
  );
} );
