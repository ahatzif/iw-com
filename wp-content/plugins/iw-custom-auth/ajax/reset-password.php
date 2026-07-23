<?php

/*
 * RESET PASSWORD
 */



add_action( 'wp_ajax_nopriv_iw-auth-reset-password', function(){

    $error = false;

    if( ! isset( $_POST[ "key" ] ) || ( ! isset( $_POST[ "login" ] ) && ! isset( $_POST[ "id" ] )) || ! isset( $_POST['user_password'] ) ) {
        $error = __( "Please provide all required fields.", "iw-theme" );
    } else {
        $key         = isset( $_POST['key'] )   ? sanitize_text_field( $_POST['key'] )   : '';
        $login_input = false;
        if ( isset( $_POST['id'] ) && is_numeric( $_POST['id'] ) ) {
            $user_id = absint( wp_unslash( $_POST['id'] ) );


            if ( $user_id ) {
                $user = get_user_by( 'id', $user_id );
                if ( $user && ! empty( $user->user_login ) ) {
                    $login_input = $user->user_login;
                }
            }
        } elseif ( isset( $_POST['login'] ) ) {
            $login_input = sanitize_text_field( $_POST['login'] );

        }

        

        if ( is_email( $login_input ) ) {
            $user_obj = get_user_by( 'email', $login_input );
            if ( $user_obj ) {
                $login = $user_obj->user_login;
            } else {
                $user = new WP_Error( 'invalid_email', __( 'No user found with that e-mail address.' ) );
                return;
            }
        } else {
            $login = $login_input;
        }
        $user = check_password_reset_key( $key, $login );
        if ( ! $user || is_wp_error( $user ) ) {
            $error = __( "Ο κωδικός δεν είναι έγκυρος", "iw-theme" );
        } else {
            if( ! preg_match("/(?=^.{8,}$)(?=.*[!@#$%^&*]+)(?![.\n])(?=.*[a-z]).*$/", $_POST[ 'user_password' ]) ){
                $error = __( "Το password δεν είναι έγκυρο", "iw-theme" );
            } else {
                reset_password( $user, $_POST['user_password'] );
            }
        }
    }
    if( $error ){
        wp_send_json_error( [ 'message' => $error ]  );
    } else {
        wp_send_json_success( [  'message' => __( "Ο password σας άλλαξε επιτυχώς", "iw-theme" ) ] );
    }

});
