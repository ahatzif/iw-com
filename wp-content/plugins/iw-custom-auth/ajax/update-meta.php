<?php
/*
 * EDIT ACCOUNT
 */


add_action( 'wp_ajax_iw-auth-update-meta', function(){

    if( ! isset( $_REQUEST[ 'user_meta' ] ) ) {
        wp_send_json( [ 'success' => true, 'message' => '' ]  );
    }

    $fillableData = IW_Form_Validator::validate( [
        //'user_about' => '',
        //'user_company' => '',
        //'user_linkdein' => 'url',
        //'user_twitter' => 'url',
        //'user_website' => 'url',
    ], $_REQUEST[ 'user_meta' ] );



    $current_user = wp_get_current_user();


    foreach ($fillableData as $key => $value) {
        $val = is_array( $value ) ? $value : strip_tags( $value );
        update_field( $key, $val , 'user_' . $current_user->ID );
    }
    IW_Form_Validator::maybe_update_user_card( $current_user->ID );
    wp_send_json( [ 'success' => true, 'message' => '', 'reload' =>  true ] );
});


