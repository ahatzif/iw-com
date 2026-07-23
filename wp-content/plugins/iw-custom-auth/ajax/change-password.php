<?php
/*
 * EDIT ACCOUNT
 */


add_action( 'wp_ajax_iw-auth-change-password', function(){

    if( ! isset( $_REQUEST[ 'user_fields' ] ) ) {
        wp_send_json_error(['message' => __('Bad request.', 'iw-theme')]);
    }

    $current_user = wp_get_current_user();
    $fields = $_REQUEST['user_fields'];

    $fillableData = IW_Form_Validator::validate( [
        //'current_user_pass' => 'required',
        'new_user_pass' => 'required|min:8|lowercase|special'
    ], $fields );


    $current_user_pass = $fillableData['current_user_pass'] ?? '';

    if ( $current_user_pass !== '' && !wp_check_password($current_user_pass, $current_user->user_pass, $current_user->ID)) {
        //wp_send_json_error(['message' => __('Ο τρέχων κωδικός είναι λανθασμένος.', 'iw-theme')]);
    }
    wp_set_password($fillableData['new_user_pass'], $current_user->ID);

    wp_signon( ['user_login' => $current_user->user_login, 'user_password' => $fillableData['new_user_pass'], 'remember' => true] );



    wp_send_json_success( [ 'message' => __( 'Ο κωδικός άλλαξε.', 'iw-theme' ) ] );
});
