<?php

/*
 * LOST PASSWORD
*/



add_action( 'wp_ajax_nopriv_iw-auth-lost-password', function(){
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid request.', 'iw-theme' ) ], 405 );
    }

    $security = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
    if ( ! wp_verify_nonce( $security, 'iw-auth-lost-password' ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid request.', 'iw-theme' ) ], 403 );
    }

    $user_email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
    if ( ! is_email( $user_email ) ) {
        wp_send_json_error( [ 'message' => __( 'Please enter a valid email address.', 'iw-theme' ) ], 400 );
    }

    $success_message = __( 'If an account exists for this email, password reset instructions have been sent.', 'iw-theme' );
    $user = get_user_by( 'email', $user_email );

    if( $user ) {

        $key = get_password_reset_key( $user ) ;

        if ( is_wp_error( $key ) ) {
            wp_send_json_success( [ 'message' => $success_message ] );
        }

        $url = add_query_arg( [
            'reset-password-key' => $key,
            'id'                 => $user->ID,
        ], home_url( '/' ) );

        add_filter( 'iw_email_template_cover', function(){ return get_field( 'iw_custom_auth_email_password_reset_cover', 'option' ); } );

        if( empty( $subject = get_field( 'iw_custom_auth_email_password_reset_subject', 'option' ) )   ){
            $subject = acf_get_field('iw_custom_auth_email_password_reset_subject', 'option')['default_value'];
        }

        if( empty( $message = get_field( 'iw_custom_auth_email_password_reset_text', 'option' ) )   ){
            $message = acf_get_field('iw_custom_auth_email_password_reset_text', 'option')['default_value'];
        }

        $message = str_replace( '[First Name]', $user->first_name, $message );
        $message = str_replace( '[Password Reset Link]', $url, $message);
        $message = str_replace( '[Password Reset Key]', $key , $message);


        wp_mail( $user->data->user_email, $subject, $message );
    }
    // Always return the same response so the form cannot be used to enumerate accounts.
    wp_send_json_success( [ 'message' => $success_message ] );

});
