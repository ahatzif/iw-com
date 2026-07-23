jQuery($ => {

    let $btn = $('#wp-admin-export-iw-form');
    let $btnText = $btn.find( '.txt');
    let $btnPrevText = $btnText.text();

    $btn.on('click', function(e) {
        if( $btn.hasClass( 'loading' ) ) return;
        $btn.addClass( 'loading' );
        e.preventDefault();
        $btnText.text( 'Starting Export' );
        $.ajax({ url: ajaxurl, type: 'POST', data: { action: 'export-submissions', 'form-id' : $btn.data( 'form-id' ) },
            success: function(response) {
                $btn.addClass( 'success' );
                $btnText.text( response.message );
                location.reload();
            },
            error: function(xhr, status, error) {
                $btn.addClass( 'error' );
                $btnText.text( response.message );
                location.reload();
            },
        }).always( function(){

            $btn.removeClass( 'loading' );
            setTimeout( ()=> {
                $btn.removeClass( 'error success' );
                $btnText.text( $btnPrevText );
            },2000);
        });
    });
});
