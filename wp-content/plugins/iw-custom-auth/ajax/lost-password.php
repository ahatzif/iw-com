<?php

/*
 * LOST PASSWORD
*/



add_action( 'wp_ajax_nopriv_iw-auth-lost-password', function(){
    $mailSent = false;
    if( $user = get_user_by( 'email', sanitize_email( trim( $_REQUEST[ 'user_email' ] ) ) ) ) {

        $key = get_password_reset_key( $user ) ;

        $url = home_url( '/' ) . "?reset-password-key=" . $key . "&amp;id=" . $user->data->user_id;

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


        $mailSent = wp_mail( $user->data->user_email, $subject, $message );
    }
    if( $user ){
        if( $mailSent ){
            wp_send_json_success( [ 'message' => ''  ] );
        } else {
            wp_send_json_error( [ 'errors' => [ 'user_email' => __('Δεν είναι δυνατή η αποστολή του κωδικού αυτή τη στιγμή. Παρακαλούμε προσπαθήστε αργότερα.') ] ], 400  );
        }
    } else {
        wp_send_json_error( [ 'errors' => [ 'user_email' => __('Το Email δεν βρέθηκε') ] ], 400  );
    }

});
