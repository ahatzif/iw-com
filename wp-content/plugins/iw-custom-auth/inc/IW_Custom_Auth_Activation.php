<?php

class IW_Custom_Auth_Activation {

    const META_PENDING = 'has_to_be_activated';
    const META_PHONE = 'iw_custom_auth_activation_phone';
    const META_SMS_LAST_SENT = 'iw_custom_auth_sms_last_sent';
    const META_PENDING_PHONE = 'iw_custom_auth_pending_activation_phone';
    const META_PHONE_CHANGE_LAST_SENT = 'iw_custom_auth_phone_change_sms_last_sent';

    public static function get_method() {
        $method = function_exists( 'get_field' ) ? (string) get_field( 'iw_custom_auth_activation_method', 'option' ) : '';
        $method = $method ?: 'email';

        return in_array( $method, [ 'email', 'sms', 'email_sms' ], true ) ? $method : 'email';
    }

    public static function uses_email( $method = null ) {
        $method = $method ?: self::get_method();

        return in_array( $method, [ 'email', 'email_sms' ], true );
    }

    public static function uses_sms( $method = null ) {
        $method = $method ?: self::get_method();

        return in_array( $method, [ 'sms', 'email_sms' ], true );
    }

    public static function normalize_phone( $phone ) {
        $phone = trim( (string) $phone );
        $phone = preg_replace( '/[\s\-\.\(\)]/', '', $phone );

        if ( str_starts_with( $phone, '00' ) ) {
            $phone = '+' . substr( $phone, 2 );
        }

        if ( str_starts_with( $phone, '+' ) ) {
            return '+' . preg_replace( '/\D/', '', substr( $phone, 1 ) );
        }

        $digits = preg_replace( '/\D/', '', $phone );

        if ( preg_match( '/^69\d{8}$/', $digits ) ) {
            return '+30' . $digits;
        }

        if ( preg_match( '/^30\d{10}$/', $digits ) ) {
            return '+' . $digits;
        }

        return $phone;
    }

    public static function is_valid_phone( $phone ) {
        return (bool) preg_match( '/^\+[1-9]\d{7,14}$/', (string) $phone );
    }

    public static function get_sms_cooldown() {
        $cooldown = function_exists( 'get_field' ) ? absint( get_field( 'iw_custom_auth_twilio_resend_cooldown', 'option' ) ) : 0;

        return $cooldown > 0 ? $cooldown : 60;
    }

    public static function get_twilio_config() {
        return [
            'account_sid' => function_exists( 'get_field' ) ? trim( (string) get_field( 'iw_custom_auth_twilio_account_sid', 'option' ) ) : '',
            'auth_token' => function_exists( 'get_field' ) ? trim( (string) get_field( 'iw_custom_auth_twilio_auth_token', 'option' ) ) : '',
            'service_sid' => function_exists( 'get_field' ) ? trim( (string) get_field( 'iw_custom_auth_twilio_verify_service_sid', 'option' ) ) : '',
        ];
    }

    public static function twilio_is_configured() {
        $config = self::get_twilio_config();

        return $config['account_sid'] !== '' && $config['auth_token'] !== '' && $config['service_sid'] !== '';
    }

    public static function start_sms_verification( $phone ) {
        return self::twilio_request( 'Verifications', [
            'To' => $phone,
            'Channel' => 'sms',
            'Locale' => self::get_twilio_locale(),
        ] );
    }

    public static function check_sms_verification( $phone, $code ) {
        $result = self::twilio_request( 'VerificationCheck', [
            'To' => $phone,
            'Code' => $code,
        ] );

        if ( ! $result['success'] ) {
            return $result;
        }

        $result['success'] = isset( $result['data']['status'] ) && $result['data']['status'] === 'approved';

        if ( ! $result['success'] ) {
            $result['message'] = __( 'Ο κωδικός επιβεβαίωσης δεν είναι έγκυρος', 'iw-theme' );
        }

        return $result;
    }

    public static function mark_sms_sent( $user_id ) {
        update_user_meta( $user_id, self::META_SMS_LAST_SENT, time() );
    }

    public static function get_retry_after( $user_id ) {
        $last_sent = absint( get_user_meta( $user_id, self::META_SMS_LAST_SENT, true ) );
        $cooldown = self::get_sms_cooldown();

        if ( ! $last_sent ) {
            return 0;
        }

        return max( 0, $cooldown - ( time() - $last_sent ) );
    }

    public static function mark_phone_change_sms_sent( $user_id ) {
        update_user_meta( $user_id, self::META_PHONE_CHANGE_LAST_SENT, time() );
    }

    public static function get_phone_change_retry_after( $user_id ) {
        $last_sent = absint( get_user_meta( $user_id, self::META_PHONE_CHANGE_LAST_SENT, true ) );
        $cooldown = self::get_sms_cooldown();

        if ( ! $last_sent ) {
            return 0;
        }

        return max( 0, $cooldown - ( time() - $last_sent ) );
    }

    public static function get_pending_user_from_request( $request ) {
        if ( ! empty( $request['id'] ) ) {
            return get_user_by( 'id', absint( $request['id'] ) );
        }

        if ( ! empty( $request['login_email'] ) ) {
            return get_user_by( 'email', sanitize_email( $request['login_email'] ) );
        }

        return false;
    }

    public static function registration_success_message( $method = null ) {
        $method = $method ?: self::get_method();

        if ( $method === 'sms' ) {
            return __( 'Ο λογαριασμός σας δημιουργήθηκε. Συμπληρώστε τον κωδικό που λάβατε με SMS για να τον ενεργοποιήσετε.', 'iw-theme' );
        }

        if ( $method === 'email_sms' ) {
            return __( 'Ο λογαριασμός σας δημιουργήθηκε. Συμπληρώστε τον κωδικό που λάβατε με email ή SMS για να τον ενεργοποιήσετε.', 'iw-theme' );
        }

        return __( 'Your account has been created. Please check your inbox for instructions to activate your account', 'iw-theme' );
    }

    public static function activation_instructions( $method = null ) {
        $method = $method ?: self::get_method();

        if ( $method === 'sms' ) {
            return __( 'Για να ολοκληρωθεί η διαδικασία συμπληρώστε τον κωδικό που σας έχει έρθει με SMS στο παρακάτω πεδίο.', 'iw-theme' );
        }

        if ( $method === 'email_sms' ) {
            return __( 'Για να ολοκληρωθεί η διαδικασία συμπληρώστε τον κωδικό που σας έχει έρθει στο email ή με SMS στο παρακάτω πεδίο.', 'iw-theme' );
        }

        return __( 'Για να ολοκληρωθεί η διαδικασία συμπληρώστε τον κωδικό που σας έχει έρθει στο email σας στο παρακάτω πεδίο.', 'iw-theme' );
    }

    public static function login_pending_message() {
        $method = self::get_method();

        if ( $method === 'sms' ) {
            return __( 'Your account is not activated yet. Please check your SMS.', 'iw-theme' );
        }

        if ( $method === 'email_sms' ) {
            return __( 'Your account is not activated yet. Please check your email or SMS.', 'iw-theme' );
        }

        return __( 'Your account is not activated yet. Please check your email.', 'iw-theme' );
    }

    private static function twilio_request( $path, $body ) {
        $config = self::get_twilio_config();

        if ( ! self::twilio_is_configured() ) {
            return [
                'success' => false,
                'message' => __( 'Το Twilio Verify δεν έχει ρυθμιστεί.', 'iw-theme' ),
            ];
        }

        $url = sprintf(
            'https://verify.twilio.com/v2/Services/%s/%s',
            rawurlencode( $config['service_sid'] ),
            $path
        );

        $response = wp_remote_post( $url, [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( $config['account_sid'] . ':' . $config['auth_token'] ),
            ],
            'body' => $body,
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $status_code = (int) wp_remote_retrieve_response_code( $response );
        $data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

        if ( $status_code >= 200 && $status_code < 300 ) {
            return [
                'success' => true,
                'data' => is_array( $data ) ? $data : [],
            ];
        }

        return [
            'success' => false,
            'message' => is_array( $data ) && ! empty( $data['message'] ) ? $data['message'] : __( 'Δεν ήταν δυνατή η αποστολή SMS αυτή τη στιγμή.', 'iw-theme' ),
            'data' => is_array( $data ) ? $data : [],
        ];
    }

    private static function get_twilio_locale() {
        $language = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '';

        if ( ! $language && function_exists( 'apply_filters' ) ) {
            $language = (string) apply_filters( 'wpml_current_language', null );
        }

        return $language === 'el' ? 'el' : 'en';
    }
}
