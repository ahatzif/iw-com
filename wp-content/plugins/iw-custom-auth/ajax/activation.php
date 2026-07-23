<?php
/*
 * USER ACTIVATION
 */
add_action('wp_ajax_nopriv_iw-auth-account-activation', function () {
    if ((isset($_POST['login_email']) || isset($_POST['id'])) && isset($_POST['activation_code'])) {
        $user = IW_Custom_Auth_Activation::get_pending_user_from_request( $_POST );

        if ($user) {
            $user_id = $user->ID;
            $submitted_code = sanitize_text_field($_POST['activation_code']);
            $code = get_user_meta($user_id, 'has_to_be_activated', true);
            $method = IW_Custom_Auth_Activation::get_method();
            $is_valid_email_code = IW_Custom_Auth_Activation::uses_email( $method ) && $code === $submitted_code;
            $is_valid_sms_code = false;

            if ( ! $is_valid_email_code && IW_Custom_Auth_Activation::uses_sms( $method ) && preg_match( '/^\d{4,10}$/', $submitted_code ) ) {
                $phone = get_user_meta( $user_id, IW_Custom_Auth_Activation::META_PHONE, true );

                if ( $phone ) {
                    $smsCheck = IW_Custom_Auth_Activation::check_sms_verification( $phone, $submitted_code );
                    $is_valid_sms_code = ! empty( $smsCheck['success'] );
                }
            }

            if ($is_valid_email_code || $is_valid_sms_code) {
                delete_user_meta($user_id, 'has_to_be_activated');
                wp_send_json_success(['message' => __('Ο λογαριασμός σας ενεργοποιήθηκε', 'iw-theme')]);
            } else {

                wp_send_json_error([ 'errors' => [ 'activation_code' => __('Ο κωδικός επιβεβαίωσης δεν είναι έγκυρος', 'iw-theme')] ], 400 );
            }
        }
    }

    wp_send_json_error(['message' => __('Συμπληρώσετε κωδικό επιβεβαίωσης και email', 'iw-theme')]);
});

add_action('wp_ajax_nopriv_iw-auth-resend-activation', function () {
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'iw-auth-activation' ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid request.', 'iw-theme' ) ], 403 );
    }

    $method = IW_Custom_Auth_Activation::get_method();

    if ( ! IW_Custom_Auth_Activation::uses_sms( $method ) ) {
        wp_send_json_error( [ 'message' => __( 'Η ενεργοποίηση με SMS δεν είναι ενεργή.', 'iw-theme' ) ], 400 );
    }

    $user = IW_Custom_Auth_Activation::get_pending_user_from_request( $_POST );

    if ( ! $user || ! get_user_meta( $user->ID, 'has_to_be_activated', true ) ) {
        wp_send_json_error( [ 'message' => __( 'Δεν βρέθηκε λογαριασμός για ενεργοποίηση.', 'iw-theme' ) ], 404 );
    }

    $retry_after = IW_Custom_Auth_Activation::get_retry_after( $user->ID );

    if ( $retry_after > 0 ) {
        wp_send_json_error( [
            'message' => sprintf( __( 'Μπορείτε να ζητήσετε νέο SMS σε %d δευτερόλεπτα.', 'iw-theme' ), $retry_after ),
            'retry_after' => $retry_after,
        ], 429 );
    }

    $phone = get_user_meta( $user->ID, IW_Custom_Auth_Activation::META_PHONE, true );

    if ( ! $phone || ! IW_Custom_Auth_Activation::is_valid_phone( $phone ) ) {
        wp_send_json_error( [ 'message' => __( 'Δεν βρέθηκε έγκυρο κινητό τηλέφωνο για αυτόν τον λογαριασμό.', 'iw-theme' ) ], 400 );
    }

    $smsResult = IW_Custom_Auth_Activation::start_sms_verification( $phone );

    if ( empty( $smsResult['success'] ) ) {
        wp_send_json_error( [
            'message' => ! empty( $smsResult['message'] ) ? $smsResult['message'] : __( 'Δεν ήταν δυνατή η αποστολή SMS αυτή τη στιγμή.', 'iw-theme' ),
        ], 400 );
    }

    IW_Custom_Auth_Activation::mark_sms_sent( $user->ID );

    wp_send_json_success( [
        'message' => __( 'Ο κωδικός στάλθηκε ξανά με SMS.', 'iw-theme' ),
        'retry_after' => IW_Custom_Auth_Activation::get_sms_cooldown(),
    ] );
});
