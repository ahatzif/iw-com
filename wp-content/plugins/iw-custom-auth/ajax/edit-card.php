<?php
/*
 * EDIT CARD
 */


add_action( 'wp_ajax_iw-auth-edit-card', function(){
    IW_Form_Validator::maybe_update_user_card( wp_get_current_user()->ID );
    wp_send_json_success( [ 'message' => __( 'Profile updated.', 'iw-theme' ) , 'reload' => true ] );
});
