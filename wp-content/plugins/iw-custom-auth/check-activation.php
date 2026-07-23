<?php

if ( ! function_exists('wp_authenticate') ) :

    function wp_authenticate( $username, $password ) {
        $username = sanitize_user( $username );
        $password = trim( $password );

        $user = apply_filters( 'authenticate', null, $username, $password );

        if ( null == $user || is_wp_error( $user ) ) {
            // TODO: What should the error message be? (Or would these even happen?)
            // Only needed if all authentication handlers fail to return anything.
            $user = new WP_Error( 'authentication_failed', __( 'Invalid email address or incorrect password.', 'iw-theme' ) );
        } elseif ( get_user_meta( $user->ID, 'has_to_be_activated', true ) != false ) {
            $message = class_exists( 'IW_Custom_Auth_Activation' )
                ? IW_Custom_Auth_Activation::login_pending_message()
                : __( 'Your account is not activated yet. Please check your email.', 'iw-theme' );
            $user = new WP_Error('activation_failed', $message);
        }

        $ignore_codes = array( 'empty_username', 'empty_password' );

        if ( is_wp_error( $user ) && ! in_array( $user->get_error_code(), $ignore_codes, true ) ) {
            $error = $user;
            do_action( 'wp_login_failed', $username, $error );
        }

        return $user;
    }

endif;
