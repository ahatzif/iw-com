jQuery( document ).ready( function( $ ) {
    console.log( "Password Protected: Gutenberg visibility inline upsell script loaded!" );

    var settingsUrl = ( typeof passwordProtectedUpsell !== 'undefined' && passwordProtectedUpsell.settingsUrl )
        ? passwordProtectedUpsell.settingsUrl
        : '';

    function checkGutenbergInline() {
        // Find all active documents (top level and Gutenberg editor canvas iframe)
        var $contexts = $( document );
        var $iframe = $( 'iframe[name="editor-canvas"]' );
        if ( $iframe.length ) {
            try {
                var iframeContents = $iframe.contents();
                if ( iframeContents && iframeContents.length ) {
                    $contexts = $contexts.add( iframeContents );
                }
            } catch ( e ) {
                // Cross-origin fallback
            }
        }

        // Search for the Gutenberg help class element
        $contexts.find( '.components-checkbox-control__help, .components-radio-control__help' ).each( function() {
            var $help = $( this );
            
            // Check if the element contains the target Gutenberg description string
            if ( $help.text().trim() === 'Only visible to those who know the password.' ) {
                
                // 1. Direct search for the visible password input container
                var $passwordField = $contexts.find( '.editor-change-status__password-fieldset' ).filter( ':visible' );
                
                // 2. Select target based on whether the password input is active/visible
                var $target = $help;
                if ( $passwordField.length ) {
                    $target = $passwordField;
                }

                // 3. Search for existing upsell globally in the active contexts
                var $existingUpsell = $contexts.find( '.password-protected-gutenberg-inline-upsell' );

                if ( !$existingUpsell.length ) {
                    // Create and insert after the target
                    var $gutenbergUpsell = $(
                        '<div class="password-protected-gutenberg-inline-upsell" style="margin-top: 6px; color: #646970; font-size: 12px; line-height: 1.4; font-weight: normal; display: block; clear: both;">' +
                            'Protect your entire site for free with Password Protected. ' +
                            '<a href="' + settingsUrl + '" style="color: #2271b1; text-decoration: underline; font-weight: 500;">Protect Now</a>' +
                        '</div>'
                    );
                    $target.after( $gutenbergUpsell );
                } else {
                    // Move it dynamically if it's not positioned directly after our target
                    if ( $existingUpsell.prev()[0] !== $target[0] ) {
                        $existingUpsell.detach().insertAfter( $target );
                    }
                }
            }
        } );
    }

    // 1. Run check immediately on load
    setTimeout( checkGutenbergInline, 500 );

    // 2. Bind to click events (fully event-driven, no polling)
    $( document ).on( 'click', function() {
        setTimeout( checkGutenbergInline, 100 );
    } );

    // 3. Bind to keyup and change events for form inputs
    $( document ).on( 'keyup change', function() {
        setTimeout( checkGutenbergInline, 100 );
    } );
} );
