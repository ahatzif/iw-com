jQuery( document ).ready( function( $ ) {
    console.log( "Password Protected: Classic visibility inline upsell script loaded!" );

    var settingsUrl = ( typeof passwordProtectedUpsell !== 'undefined' && passwordProtectedUpsell.settingsUrl )
        ? passwordProtectedUpsell.settingsUrl
        : '';

    function injectClassicInline() {
        if ( $( '#post-password-select' ).length && !$( '#password-protected-classic-inline-upsell' ).length ) {
            var $classicUpsell = $(
                '<div id="password-protected-classic-inline-upsell" style="margin-top: 8px; color: #646970; font-size: 12px; line-height: 1.4;">' +
                    'Protect your entire site for free with Password Protected. ' +
                    '<a href="' + settingsUrl + '" style="color: #2271b1; text-decoration: underline; font-weight: 500;">Protect Now</a>' +
                '</div>'
            );
            $( '#post-password-select' ).append( $classicUpsell );
        }
    }

    // Run immediately and also on edit visibility clicks
    injectClassicInline();
    $( 'a.edit-visibility' ).on( 'click', function() {
        setTimeout( injectClassicInline, 100 );
    } );
} );
