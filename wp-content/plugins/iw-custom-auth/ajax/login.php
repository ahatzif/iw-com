<?php
/*
 * USER LOGIN
 */
add_action( 'wp_ajax_nopriv_iw-auth-login', function(){
    $user = wp_signon([ 'user_login' => $_REQUEST[ 'user_email' ], 'user_password' => $_REQUEST[ 'user_password' ], 'rememberme' => isset( $_REQUEST[ 'rememberme' ] )], is_ssl()  );
    if( is_wp_error( $user) ){
        wp_send_json_error( [ 'message' => $user->get_error_code() === 'activation_failed' ? $user->get_error_message() : __( 'Invalid email address /  password', 'iw-theme' )] );
    } else {
        $redirect_to = get_permalink( iw_get_user_page( 'my-account' ) );

        if ( ! empty( $_COOKIE['redirect-login-modal'] ) ) {
            $candidate = esc_url_raw( urldecode( $_COOKIE['redirect-login-modal'] ) );
            if (str_starts_with($candidate, home_url())) {
                $redirect_to = $candidate;
            }
        }

        $redirect_to = apply_filters( 'iw_custom_auth_login_redirect', $redirect_to, $user );

        wp_send_json_success( [ 'redirect' => $redirect_to ] );
    }

});
