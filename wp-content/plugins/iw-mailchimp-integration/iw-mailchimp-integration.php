<?php
/**
 * Plugin Name:     IW Mailchimp Integration
 * Plugin URI:      https://interweaveagency.com/
 * Description:     Class‑based plugin that subscribes or updates users on Mailchimp through AJAX.
 * Author:          Interweave Agency
 * Version:         1.0.1
 *
 * @package         Iw_Mailchimp_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IW_Mailchimp_Integration {


    public const VERSION = '1.0.1';
    public static  $AJAX_ACTION = 'iw_mailchimp_subscribe';
    public static  $AJAX_UNSUBSCRIBE_ACTION = 'iw_mailchimp_unsubscribe';
    public static $interests_field = 'INTERESTS';
    public function __construct() {
        add_action( 'wp_ajax_' . self::$AJAX_ACTION, [ $this, 'insert_or_update_subscriber_ajax' ] );
        add_action( 'wp_ajax_nopriv_' . self::$AJAX_ACTION, [ $this, 'insert_or_update_subscriber_ajax' ] );

        add_action( 'wp_ajax_'      . self::$AJAX_UNSUBSCRIBE_ACTION, [ $this, 'unsubscribe_ajax' ] );
        add_action( 'wp_ajax_nopriv_' . self::$AJAX_UNSUBSCRIBE_ACTION, [ $this, 'unsubscribe_ajax' ] );
    }

    public static function get_mc_config() {
        $api_key = trim( get_option( 'iw_form_submissions_mailchimp_api_key', '' ) );
        $list_id = trim( get_option( 'iw_form_submissions_mailchimp_registration_list', '' ) );
        $dc = substr( $api_key, strrpos( $api_key, '-' ) + 1 ); // e.g. "us21", "eu3".
        return [ 'api_key' => $api_key, 'list_id' => $list_id, 'dc' => $dc,];
    }



    public static function update_list_interests( $list_id = ''  ){

        $config  = self::get_mc_config();
        $api_key = $config['api_key'];
        $dc      = $config['dc'];

        if( empty( $list_id ) ){
            $list_id = trim( get_option( 'iw_form_submissions_mailchimp_registration_list', '' ) );
        }


        $cats_endpoint = sprintf( 'https://%1$s.api.mailchimp.com/3.0/lists/%2$s/interest-categories', $dc, $list_id );
        $cats_request  = wp_remote_get( $cats_endpoint, [ 'headers' => [ 'Authorization' => 'apikey ' . $api_key ], 'timeout' => 15,] );
        if ( is_wp_error( $cats_request ) ) {
            return [];
        }

        $cats_body = json_decode( wp_remote_retrieve_body( $cats_request ), true );
        $cat_id    = null;
        foreach ( $cats_body['categories'] ?? [] as $cat ) {
            if ( strtoupper( $cat['title'] ) === strtoupper( self::$interests_field ) ) {
                $cat_id = $cat['id'];
                break;
            }
        }
        if ( ! $cat_id ) {
            return [];
        }
        $ints_endpoint = sprintf( 'https://%1$s.api.mailchimp.com/3.0/lists/%2$s/interest-categories/%3$s/interests', $dc, $list_id, $cat_id );
        $ints_request  = wp_remote_get( $ints_endpoint, [ 'headers' => [ 'Authorization' => 'apikey ' . $api_key ], 'timeout' => 15,] );
        if ( is_wp_error( $ints_request ) ) {
            return [];
        }

        $ints_body = json_decode( wp_remote_retrieve_body( $ints_request ), true );
        $map = [];
        foreach ( $ints_body['interests'] ?? [] as $int ) {
            $map[ $int['id'] ] = $int['name'];
        }
        update_option( 'list_interests_' . $list_id , $map);
        return $map;
    }

    public static function get_list_interests( $list_id = '', $category_title = 'INTERESTS' ){
        if( empty( $list_id ) ){
            $list_id = trim( get_option( 'iw_form_submissions_mailchimp_registration_list', '' ) );
        }

        $list_interests = get_option( 'list_interests_' . $list_id );
        if( empty( $list_interests ) ){
            $list_interests = self::update_list_interests();
        }

        return $list_interests ?? [];
    }



    private static function send_mailchimp_request( string $endpoint, string $method = 'POST', array $body = [] ) {
        $config  = self::get_mc_config();
        $api_key = $config['api_key'];

        $args = [
            'method'      => $method,
            'timeout'     => 15,
            'headers'     => [
                'Authorization' => 'apikey ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'        => wp_json_encode( $body ),
            'data_format' => 'body',
        ];

        $url = "https://{$config['dc']}.api.mailchimp.com/3.0" . $endpoint;
        return wp_remote_request( $url, $args );
    }

    public static function insert_or_update_subscriber_ajax()
    {
        self::insert_or_update_subscriber();
    }

    public static function unsubscribe_ajax(){
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::$AJAX_UNSUBSCRIBE_ACTION)) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'iw-theme' ) ], 403);
        }
        $config  = self::get_mc_config();
        $list_id = $config['list_id'];

        $email = '';


        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $email        = $current_user->user_email;
        } else if ( isset( $_POST['user_email'] ) ) {
            $email = sanitize_email( wp_unslash( $_POST['user_email'] ) );
        }

        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid email.', 'iw-theme' ) ], 400 );
        }


        $request = self::send_mailchimp_request( "/lists/{$list_id}/members/" . md5( strtolower( $email ) ), 'PUT', [ 'email_address' => $email, 'status' => 'unsubscribed'] );


        if ( is_wp_error( $request ) ) {
            wp_send_json_error( [ 'message' => __( 'Δεν είναι δυνατή η διαγραφή αυτή τη στιγμή.', 'iw-theme' ) ], 403 );
        }

        update_user_meta(   $current_user->ID, 'subscriber_info' , [ 'interests' => false, 'merge_fields' => false] ) ;

        $status = wp_remote_retrieve_response_code( $request );
        if ( $status >= 200 && $status < 300 ) {
            wp_send_json_success([ 'user_status' => 'unsubscribed', 'message' => __( 'Έχετε διαγραφεί από το newsletter.', 'iw-theme' )]);
        }


        wp_send_json_error( [ 'message' => __( 'Δεν βρέθηκε ενεργή εγγραφή με αυτό το email.', 'iw-theme' )  ], 403);
    }

    /* ---------------------------------------------------------------------
     *  AJAX callback
     * ------------------------------------------------------------------ */

    /**
     * Subscribe or update a Mailchimp member via AJAX request.
     *
     * Expects `user_email`, optional `language` and `interests[]` in POST.
     * If user is logged‑in, falls back to their WP profile data.
     */
    public static function insert_or_update_subscriber( $ajax = true  ) {
        $config  = self::get_mc_config();
        $list_id = $config['list_id'];

        $first_name = $last_name  = $email = '';

        $endpoint = "/lists/{$list_id}/members/";
        $method = 'POST';

        if ( is_user_logged_in() ) {
            $method = 'PUT';
            $current_user = wp_get_current_user();
            $first_name   = $current_user->user_firstname;
            $last_name    = $current_user->user_lastname;
            $email        = $current_user->user_email;
            $endpoint = "/lists/{$list_id}/members/" . md5( strtolower( $email ) );
        } else if ( isset( $_POST['user_email'] ) ) {
            $email          = sanitize_email( wp_unslash( $_POST[ 'user_email' ] ) );
            $first_name     = sanitize_text_field(  $_POST[ 'first_name' ] ?? '' ) ;;
            $last_name      = sanitize_text_field(  $_POST[ 'last_name' ] ?? '' ) ;;
        }

        if ( empty( $email ) || ! is_email( $email ) || empty( $first_name ) || empty( $last_name) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid data.', 'iw-mailchimp-integration' ) ], 400 );
        }

        $language   = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'Ελληνικά';
        $chosen      = isset( $_POST['interests'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['interests'] ) ) : [];

        /* --------------------------------------------------------------
         * Build interests payload (true / false for ALL interests)
         * ----------------------------------------------------------- */
        $all_interests = self::get_list_interests();
        $int_payload   = [];
        foreach ( $all_interests as $id => $name ) {
            $int_payload[ $id ] = in_array( $id, $chosen, true );
        }

        $body = [ 'email_address' => $email, $method === 'POST' ? 'status' : 'status_if_new' => 'pending', 'merge_fields'  => [ 'FNAME' => $first_name, 'LNAME'  => $last_name, 'LANGUAGE' => $language ], 'interests' => $int_payload ];
        $request = self::send_mailchimp_request( $endpoint, $method, $body );


        if ( is_wp_error( $request ) ) {
            $message = __( 'Δεν είναι δυνατή η εγγραφή σας αυτή τη στιγμή. Παρακαλούμε προσπαθήστε αργότερα', 'iw-theme' ) ;
            if( $ajax ){
                wp_send_json_error( [ 'message' => $message], 403 );
            } else {
                return [ 'message' => $message, 'success' => false ];
            }
        }

        $status = wp_remote_retrieve_response_code( $request );
        $responseBody = json_decode(wp_remote_retrieve_body($request) );


        if ( $status >= 200 && $status < 300 ) {

            if ( is_user_logged_in() || ! $ajax ) {
                update_user_meta(   $current_user->ID, 'subscriber_info' , [ 'interests' => $int_payload, 'merge_fields' => $body[ 'merge_fields']] ) ;
            }


            $userStatus = $responseBody->status;
            if( $ajax ) {
                wp_send_json_success(['user_status' => $userStatus  ]);
            } else {
                return [ 'success' => true ];

            }
        }


        $title   = $responseBody->title ?? '';
        $detail  = $responseBody->detail ?? __( 'Παρουσιάστηκε ένα πρόβλημα.', 'iw-theme' );

        if ( $title === 'Member Exists' ) {
            $message = __( 'Έχετε ήδη εγγραφεί με αυτό το email', 'iw-theme' );
        } elseif ( $title === 'Forgotten Email Not Subscribed' ) {
            $message = __( 'Αυτό το email είχε διαγραφεί οριστικά από τη λίστα. Για να εγγραφείτε ξανά, παρακαλούμε χρησιμοποιήστε τη φόρμα εγγραφής newsletter και επιβεβαιώστε το email σας μέσω του σχετικού email επιβεβαίωσης που θα λάβετε.', 'iw-theme' );
        } else {
            $message = $detail;
        }

        if ( $ajax ) {
            wp_send_json_error( ['message' => $responseBody, 'title'   => $title, 'errors'  => ['user_email' => $message ]], 403 );
        } else {
            return [ 'message' => $message, 'success' => false,];
        }

    }



    /* ---------------------------------------------------------------------
     *  Get language and interests for a given Mailchimp subscriber.
     * ------------------------------------------------------------------ */

    public static function get_subscriber_info( ){
        $currentUser = wp_get_current_user();
        return get_field( 'subscriber_info' , 'user_' . $currentUser->ID);
    }
    public static function _get_subscriber_info__( string $email ) {
        $config      = self::get_mc_config();
        $api_key     = $config['api_key'];
        $list_id     = $config['list_id'];
        $dc          = $config['dc'];

        $email       = strtolower( trim( $email ) );
        $email_hash  = md5( $email );

        $endpoint    = "https://{$dc}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$email_hash}";



        $response = wp_remote_get( $endpoint, [ 'headers' => [ 'Authorization' => 'apikey ' . $api_key, 'Content-Type'  => 'application/json' ], 'timeout' => 15,]);

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'mailchimp_error', $response->get_error_message() );
        }

        $status = wp_remote_retrieve_response_code( $response );
        if ( $status < 200 || $status >= 300 ) {
            return new WP_Error( 'mailchimp_error', wp_remote_retrieve_body( $response ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return [
            'email'        => $body['email_address'] ?? $email,
            'interests'    => $body['interests'] ?? [],
            'merge_fields' => $body['merge_fields'] ?? [], // Includes LANGUAGE if custom
        ];
    }





}

new IW_Mailchimp_Integration();
