<?php
/*
 * EDIT ACCOUNT
 */


add_action( 'wp_ajax_iw-auth-edit-account', function(){

    if( ! isset( $_REQUEST[ 'user_fields' ] ) ) {
        // wp_send_json( [ 'success' => false, 'message' => 'Bad request' ], 422 );
        wp_send_json_error( [ 'message' => __( 'Bad request', 'iw-theme' )] );
    }

    $fillableData = IW_Form_Validator::validate( [
        'first_name' => 'required',
        'last_name' => 'required',
        'user_email' => 'email|required',
        'activation_phone' => '',
    ], $_REQUEST[ 'user_fields' ] );


    $current_user = wp_get_current_user();
    $fillableData[ 'ID' ] = $current_user->ID;


    if( isset( $fillableData['user_email']) ){
        $existingEmailUserId = email_exists( $fillableData['user_email'] );
        if( $existingEmailUserId && $current_user->ID !== $existingEmailUserId) {
            wp_send_json_error( [ 'message' => __( 'Email is already in use', 'iw-theme' )] );
        }
    }

    $current_phone = get_user_meta( $current_user->ID, IW_Custom_Auth_Activation::META_PHONE, true );
    $current_phone = $current_phone ?: get_user_meta( $current_user->ID, 'billing_phone', true );
    $current_phone = IW_Custom_Auth_Activation::normalize_phone( $current_phone );
    $activation_phone = '';
    $phone_changed = false;

    if ( array_key_exists( 'activation_phone', $fillableData ) ) {
        $activation_phone = IW_Custom_Auth_Activation::normalize_phone( $fillableData['activation_phone'] );
        unset( $fillableData['activation_phone'] );

        if ( $activation_phone !== '' && ! IW_Custom_Auth_Activation::is_valid_phone( $activation_phone ) ) {
            wp_send_json_error( [
                'message' => __( 'Συμπληρώστε έναν έγκυρο αριθμό κινητού με διεθνές πρόθεμα, π.χ. +3069XXXXXXXX.', 'iw-theme' ),
                'errors' => [ 'user_fieldsactivation_phone' => __( 'Συμπληρώστε έναν έγκυρο αριθμό κινητού με διεθνές πρόθεμα, π.χ. +3069XXXXXXXX.', 'iw-theme' ) ],
            ], 400 );
        }

        $phone_changed = $activation_phone !== $current_phone;
    }

    if ( $phone_changed && $activation_phone !== '' ) {
        if ( ! IW_Custom_Auth_Activation::twilio_is_configured() ) {
            wp_send_json_error( [
                'message' => __( 'Η επιβεβαίωση κινητού δεν έχει ρυθμιστεί ακόμα.', 'iw-theme' ),
                'errors' => [ 'user_fieldsactivation_phone' => __( 'Η επιβεβαίωση κινητού δεν έχει ρυθμιστεί ακόμα.', 'iw-theme' ) ],
            ], 400 );
        }

        $smsResult = IW_Custom_Auth_Activation::start_sms_verification( $activation_phone );

        if ( empty( $smsResult['success'] ) ) {
            wp_send_json_error( [
                'message' => ! empty( $smsResult['message'] ) ? $smsResult['message'] : __( 'Δεν ήταν δυνατή η αποστολή SMS αυτή τη στιγμή.', 'iw-theme' ),
                'errors' => [ 'user_fieldsactivation_phone' => __( 'Δεν ήταν δυνατή η αποστολή SMS αυτή τη στιγμή.', 'iw-theme' ) ],
            ], 400 );
        }
    }

    $updated_user = wp_update_user( $fillableData );

    if ( is_wp_error( $updated_user ) ) {
        wp_send_json_error( [ 'message' => $updated_user->get_error_message() ], 400 );
    }

    if ( $phone_changed && $activation_phone !== '' ) {
        update_user_meta( $current_user->ID, IW_Custom_Auth_Activation::META_PENDING_PHONE, $activation_phone );
        IW_Custom_Auth_Activation::mark_phone_change_sms_sent( $current_user->ID );

        wp_send_json_success( [
            'message' => __( 'Στείλαμε κωδικό επιβεβαίωσης στο νέο κινητό. Συμπληρώστε τον για να ολοκληρωθεί η αλλαγή.', 'iw-theme' ),
            'phone_verification_required' => true,
            'pending_phone' => $activation_phone,
            'retry_after' => IW_Custom_Auth_Activation::get_sms_cooldown(),
        ] );
    }

    if ( array_key_exists( 'activation_phone', $_REQUEST['user_fields'] ) ) {
        if ( $activation_phone === '' ) {
            delete_user_meta( $current_user->ID, IW_Custom_Auth_Activation::META_PHONE );
            delete_user_meta( $current_user->ID, 'billing_phone' );
        } else {
            update_user_meta( $current_user->ID, IW_Custom_Auth_Activation::META_PHONE, $activation_phone );
            update_user_meta( $current_user->ID, 'billing_phone', $activation_phone );
        }
    }

    wp_send_json_success( [ 'message' => __( 'Profile updated', 'iw-theme' ), 'reload' => true ] );
});

add_action( 'wp_ajax_iw-auth-verify-account-phone', function() {
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'iw-auth-account-phone' ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid request.', 'iw-theme' ) ], 403 );
    }

    $current_user = wp_get_current_user();
    $pending_phone = get_user_meta( $current_user->ID, IW_Custom_Auth_Activation::META_PENDING_PHONE, true );
    $submitted_code = sanitize_text_field( $_POST['activation_code'] ?? '' );

    if ( ! $pending_phone || ! IW_Custom_Auth_Activation::is_valid_phone( $pending_phone ) ) {
        wp_send_json_error( [ 'message' => __( 'Δεν υπάρχει κινητό σε αναμονή για επιβεβαίωση.', 'iw-theme' ) ], 400 );
    }

    if ( ! preg_match( '/^\d{4,10}$/', $submitted_code ) ) {
        wp_send_json_error( [
            'message' => __( 'Ο κωδικός επιβεβαίωσης δεν είναι έγκυρος', 'iw-theme' ),
            'errors' => [ 'activation_code' => __( 'Ο κωδικός επιβεβαίωσης δεν είναι έγκυρος', 'iw-theme' ) ],
        ], 400 );
    }

    $smsCheck = IW_Custom_Auth_Activation::check_sms_verification( $pending_phone, $submitted_code );

    if ( empty( $smsCheck['success'] ) ) {
        wp_send_json_error( [
            'message' => ! empty( $smsCheck['message'] ) ? $smsCheck['message'] : __( 'Ο κωδικός επιβεβαίωσης δεν είναι έγκυρος', 'iw-theme' ),
            'errors' => [ 'activation_code' => __( 'Ο κωδικός επιβεβαίωσης δεν είναι έγκυρος', 'iw-theme' ) ],
        ], 400 );
    }

    update_user_meta( $current_user->ID, IW_Custom_Auth_Activation::META_PHONE, $pending_phone );
    update_user_meta( $current_user->ID, 'billing_phone', $pending_phone );
    delete_user_meta( $current_user->ID, IW_Custom_Auth_Activation::META_PENDING_PHONE );
    delete_user_meta( $current_user->ID, IW_Custom_Auth_Activation::META_PHONE_CHANGE_LAST_SENT );

    wp_send_json_success( [
        'message' => __( 'Το κινητό σας επιβεβαιώθηκε.', 'iw-theme' ),
        'reload' => true,
    ] );
});

add_action( 'wp_ajax_iw-auth-resend-account-phone', function() {
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'iw-auth-account-phone' ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid request.', 'iw-theme' ) ], 403 );
    }

    $current_user = wp_get_current_user();
    $pending_phone = get_user_meta( $current_user->ID, IW_Custom_Auth_Activation::META_PENDING_PHONE, true );

    if ( ! $pending_phone || ! IW_Custom_Auth_Activation::is_valid_phone( $pending_phone ) ) {
        wp_send_json_error( [ 'message' => __( 'Δεν υπάρχει κινητό σε αναμονή για επιβεβαίωση.', 'iw-theme' ) ], 400 );
    }

    $retry_after = IW_Custom_Auth_Activation::get_phone_change_retry_after( $current_user->ID );

    if ( $retry_after > 0 ) {
        wp_send_json_error( [
            'message' => sprintf( __( 'Μπορείτε να ζητήσετε νέο SMS σε %d δευτερόλεπτα.', 'iw-theme' ), $retry_after ),
            'retry_after' => $retry_after,
        ], 429 );
    }

    $smsResult = IW_Custom_Auth_Activation::start_sms_verification( $pending_phone );

    if ( empty( $smsResult['success'] ) ) {
        wp_send_json_error( [
            'message' => ! empty( $smsResult['message'] ) ? $smsResult['message'] : __( 'Δεν ήταν δυνατή η αποστολή SMS αυτή τη στιγμή.', 'iw-theme' ),
        ], 400 );
    }

    IW_Custom_Auth_Activation::mark_phone_change_sms_sent( $current_user->ID );

    wp_send_json_success( [
        'message' => __( 'Ο κωδικός στάλθηκε ξανά με SMS.', 'iw-theme' ),
        'retry_after' => IW_Custom_Auth_Activation::get_sms_cooldown(),
    ] );
});
