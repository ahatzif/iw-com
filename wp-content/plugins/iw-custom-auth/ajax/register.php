<?php

/*
 * USER REGISTRATION
 */


add_action('wp_ajax_nopriv_iw-auth-register-check-mail', function () {
    if ( ! isset($_REQUEST['security']) || ! wp_verify_nonce($_REQUEST['security'], 'iw-auth-register') ) {
        wp_send_json_error( [ 'message' => __('Invalid request.', 'iw-theme') ], 403);
    }

    $email = isset($_REQUEST['user_email']) ? trim($_REQUEST['user_email']) : '';
    if (empty($email) || !is_email($email)) {
        wp_send_json_error([ 'message' => __('Email not valid.', 'iw-theme'), 'errors'  => ['user_email' => __('Το email δεν είναι έγκυρο', 'iw-theme')]], 400);
    }
    if (email_exists($email)) {
        wp_send_json_error( [ 'message' => __('Το email χρησιμοποιείται ήδη.', 'iw-theme'), 'errors'  => ['user_email' => __('Λυπούμαστε, αυτή η ηλ. διεύθυνση χρησιμοποιείται ήδη.', 'iw-theme')]], 409);
    }
    wp_send_json_success( [ 'message' => __('Email is available.', 'iw-theme') ] );
});

add_action('wp_ajax_nopriv_iw-auth-register', function () {

    do { // create random user name with prefix
        $userLogin = 'subscriber_' . sprintf("%010d", mt_rand(1, 999999));
        $user_exists = username_exists($userLogin);
    } while ($user_exists > 0);




    $userData = [
        'role' => 'subscriber',
        'user_email' => $_REQUEST['user_email'],
        'user_login' => $userLogin,
        'user_pass' =>  $_REQUEST['user_password'],
        'first_name' => trim($_REQUEST['first_name']),
        'last_name' => trim($_REQUEST['last_name']),
    ];

    $error = false;
    $activation_method = IW_Custom_Auth_Activation::get_method();
    $activation_phone = '';

    $school_unit_id = $school_unit = false;
    $errors = [];

    if( isset( $_REQUEST[ 'is_teacher' ] ) ){
        if( empty( $school_unit_id = $_REQUEST['user_school_autocomplete'] ) ){
            $error = true;
            $errors = [ 'user_school' => __("Παρακαλούμε επιλέξτε σχολείο.", "iw-theme")];
        } else {
            $school_unit = get_post( (int) $school_unit_id );
            if ( ! $school_unit || 'school-unit' !== $school_unit->post_type ) {
                $error = true;
                $errors = [ 'user_school' => __("Παρακαλούμε επιλέξτε σχολείο.", "iw-theme")];
            }
        }
    }
    if ( ! $error && ! preg_match("/(?=^.{8,}$)(?=.*[!@#$%^&*]+)(?![.\n])(?=.*[a-z]).*$/", $_REQUEST['user_password'])) {
        $error = true;
        $errors = [ 'password' => __("Invalid password.", "iw-theme")];
    }
    if ( ! $error &&  ( ! is_email($userData['user_email']) || empty($userData['first_name']) || empty($userData['last_name']) ) ) {
        $error = true;
        $errors = [ 'first_name' => __("Please provide all required fields.", "iw-theme")];
    }

    if ( ! $error && IW_Custom_Auth_Activation::uses_sms( $activation_method ) ) {
        $activation_phone = IW_Custom_Auth_Activation::normalize_phone( $_REQUEST['activation_phone'] ?? '' );

        if ( empty( $activation_phone ) ) {
            $error = true;
            $errors = [ 'activation_phone' => __( 'Παρακαλούμε συμπληρώστε κινητό τηλέφωνο.', 'iw-theme' ) ];
        } elseif ( ! IW_Custom_Auth_Activation::is_valid_phone( $activation_phone ) ) {
            $error = true;
            $errors = [ 'activation_phone' => __( 'Συμπληρώστε έναν έγκυρο αριθμό κινητού με διεθνές πρόθεμα, π.χ. +3069XXXXXXXX.', 'iw-theme' ) ];
        } elseif ( ! IW_Custom_Auth_Activation::twilio_is_configured() ) {
            $error = true;
            $errors = [ 'activation_phone' => __( 'Η ενεργοποίηση με SMS δεν έχει ρυθμιστεί ακόμα.', 'iw-theme' ) ];
        }
    }


    $birthday = "";



    if( ! empty( $_REQUEST['user_birthday'] ) ){
        $birthday = $_REQUEST['user_birthday'];
        if ( ! preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $birthday ) ) {
            $error = true;
            $errors['user_birthday'] = __("Η ημερομηνία γέννησης δεν είναι έγκυρη. Χρησιμοποιήστε μορφή ηη/μμ/εεεε.", "iw-theme");
        } else {
            $date = DateTime::createFromFormat('d/m/Y', $birthday);
            $now = new DateTime();
            if (!$date || $date > $now) {
                $error = true;
                $errors['user_birthday'] = __("Η ημερομηνία γέννησης δεν μπορεί να είναι στο μέλλον.", "iw-theme");
            } else {
                $age = $now->diff($date)->y;
                if ($age > 120) {
                    $error = true;
                    $errors['user_birthday'] = __("Παρακαλούμε ελέγξτε την ημερομηνία γέννησης.", "iw-theme");
                }
            }
        }
    }

    if ($error) {
        wp_send_json_error(['message' => $error, 'errors' => $errors ], 400);
    } else {
        $userId = wp_insert_user($userData);
        if (is_wp_error($userId)) {
            $error = $userId->errors["existing_user_email"] ?? __("Error creating account.", "iw-theme");
            if( $userId->errors["existing_user_email"] ){
                $errors[ 'user_email' ] = __( 'Λυπούμαστε, αυτή η ηλ. διεύθυνση χρησιμοποιείται ήδη.', 'iw-theme' );
            }
            wp_send_json_error(['message' => $error, 'errors' => $errors ], 400);
        }



        $key = wp_hash_password(wp_generate_password(20, false));
        $activation_link = add_query_arg(array('account-activation-key' => $key, 'id' => $userId), home_url( '/' ) );
        add_user_meta($userId, 'has_to_be_activated', $key, true);

        if ( $activation_phone ) {
            update_user_meta( $userId, IW_Custom_Auth_Activation::META_PHONE, $activation_phone );
            update_user_meta( $userId, 'billing_phone', $activation_phone );
        }


        $mailSent = false;
        $smsSent = false;

        if ( IW_Custom_Auth_Activation::uses_email( $activation_method ) ) {
            add_filter('iw_email_template_cover', function () {
                return get_field('iw_custom_auth_email_user_activation_cover', 'option');
            });

            if (empty($subject = get_field('iw_custom_auth_email_user_activation_subject', 'option'))) {
                $subject = acf_get_field('iw_custom_auth_email_user_activation_subject', 'option')['default_value'];
            }

            if (empty($message = get_field('iw_custom_auth_email_user_activation_text', 'option'))) {
                $message = acf_get_field('iw_custom_auth_email_user_activation_text', 'option')['default_value'];
            }

            $message = str_replace('[First Name]', $userData['first_name'], $message);
            $message = str_replace( '[Account Activation Link]', $activation_link, $message);
            $message = str_replace('[Account Activation Key]', $key, $message);
            $message = str_replace('[Account Activation Code]', $key, $message);

            $mailSent = wp_mail($userData['user_email'], $subject, $message);
        }

        if ( IW_Custom_Auth_Activation::uses_sms( $activation_method ) ) {
            $smsResult = IW_Custom_Auth_Activation::start_sms_verification( $activation_phone );
            $smsSent = ! empty( $smsResult['success'] );

            if ( $smsSent ) {
                IW_Custom_Auth_Activation::mark_sms_sent( $userId );
            }
        }


        IW_Form_Validator::maybe_update_user_card($userId);
        if( ! empty( $birthday ) ){
            if ( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $birthday ) ) {
                $birthday = DateTime::createFromFormat('d/m/Y', $birthday);
                $birthday = $birthday->format('Ymd');
                update_field( 'user_birthday', $birthday, 'user_' . $userId );
            }
        }

        if( isset( $_REQUEST[ 'newsletter' ] ) ){
            IW_Mailchimp_Integration::insert_or_update_subscriber( false );
        }

        if( ! empty( $school_unit_id ) ) {
            update_field('is_teacher', true, 'user_' . $userId);
            update_field('user_school_unit', $school_unit_id, 'user_' . $userId);
        }



        if( ! $mailSent && ! $smsSent ){
            //wp_delete_user($userId);
            wp_send_json_error([  'message' => __('Αυτή τη στιγμή δεν είναι δυνατή η εγγραφή σας για τεχνικούς λόγους. <br/>Παρακαλούμε προσπαθήστε αργότερα.', 'iw-theme') ] );
        } else {

            // USER REGISTERED

            wp_send_json_success([
                'message' => IW_Custom_Auth_Activation::registration_success_message( $activation_method ),
                'resend_after' => IW_Custom_Auth_Activation::uses_sms( $activation_method ) ? IW_Custom_Auth_Activation::get_sms_cooldown() : 0,
            ]);
        }

    }
});
