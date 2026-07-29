<?php
/*
 * USER LOGIN
 */
add_action( 'wp_ajax_nopriv_iw-auth-login', function(){
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid request.', 'iw-theme' ) ], 405 );
    }

    $security = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
    if ( ! wp_verify_nonce( $security, 'iw-auth-login' ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid request.', 'iw-theme' ) ], 403 );
    }

    $user_email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
    $user_password = isset( $_POST['user_password'] ) ? (string) wp_unslash( $_POST['user_password'] ) : '';

    if ( ! is_email( $user_email ) || '' === $user_password ) {
        wp_send_json_error( [ 'message' => __( 'Invalid email address / password', 'iw-theme' ) ], 400 );
    }

    $user = wp_signon( [
        'user_login'    => $user_email,
        'user_password' => $user_password,
        'remember'      => ! empty( $_POST['rememberme'] ),
    ], is_ssl() );

    if( is_wp_error( $user) ){
        wp_send_json_error( [ 'message' => $user->get_error_code() === 'activation_failed' ? $user->get_error_message() : __( 'Invalid email address / password', 'iw-theme' ) ], 401 );
    } else {
        $redirect_to = function_exists( 'wc_get_page_permalink' )
            ? wc_get_page_permalink( 'myaccount' )
            : get_permalink( iw_get_user_page( 'my-account' ) );

        if ( ! empty( $_COOKIE['redirect-login-modal'] ) ) {
            $candidate = esc_url_raw( urldecode( $_COOKIE['redirect-login-modal'] ) );
            if (str_starts_with($candidate, home_url())) {
                $redirect_to = $candidate;
            }
        }

        $redirect_to = apply_filters( 'iw_custom_auth_login_redirect', $redirect_to, $user );

        wp_send_json_success( [ 'redirect' => wp_validate_redirect( $redirect_to, home_url( '/' ) ) ] );
    }

});
