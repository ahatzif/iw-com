<?php

require_once 'iw-google-wallet-service.php';
require_once 'iw-google-pass.php';

class IW_Google_Wallet_Card {
    public $google_client_email = "";
    public static $issuerId = "";
    public static $classSuffix = 'membership';
    public static $objectSuffix = 'member_';
    public static  $googlePassEndpoint = "/add-to-google-wallet-card";

    private static function option( string $key, $default = '' ) {
        return class_exists( 'IW_Member_Card' ) ? IW_Member_Card::get_option( $key, $default ) : $default;
    }

    public static function issuer_id(): string {
        return (string) self::option( 'google_issuer_id', self::$issuerId );
    }

    public static function class_suffix(): string {
        return sanitize_key( (string) self::option( 'google_class_suffix', self::$classSuffix ) );
    }

    public static function object_prefix(): string {
        $prefix = (string) self::option( 'google_object_prefix', self::$objectSuffix );
        return preg_replace( '/[^A-Za-z0-9._-]/', '_', $prefix );
    }

    public static function service_account_path(): string {
        $path = (string) self::option( 'google_service_account_path', '' );
        return class_exists( 'IW_Member_Card' ) ? IW_Member_Card::resolve_path( $path, plugin_dir_path( __FILE__ ) ) : $path;
    }

    public static function application_name(): string {
        return (string) self::option( 'google_application_name', get_bloginfo( 'name' ) ?: 'Wallet' );
    }

    public static function jwt_origins(): array {
        $origins = self::option( 'google_jwt_origins', home_url() );
        if ( is_string( $origins ) ) {
            $origins = preg_split( '/[\r\n,]+/', $origins );
        }

        $origins = array_values( array_filter( array_map( 'trim', (array) $origins ) ) );
        if ( empty( $origins ) ) {
            $origins = [ home_url() ];
        }

        return array_values( array_unique( $origins ) );
    }

    public static function is_configured(): bool {
        $key_path = self::service_account_path();
        return '' !== self::issuer_id()
            && '' !== $key_path
            && file_exists( $key_path )
            && class_exists( 'Google\Client' )
            && class_exists( 'Google\Service\Walletobjects' )
            && class_exists( 'Firebase\JWT\JWT' );
    }

    public function __construct(){
        add_action( 'rest_api_init', function (){
            register_rest_route('iw/v1', self::$googlePassEndpoint, [ 'methods'  => 'GET', 'callback' => [ 'IW_Google_Wallet_Card', 'rest_download_google_pass' ], 'permission_callback' => '__return_true']);
        } );
    }

    public static function rest_download_google_pass( WP_REST_Request $request ){
        $card_number = sanitize_text_field( $request->get_param('member-card-id') );
        if( empty($card_number) ) { return new WP_REST_Response([ 'error' => 'Missing member-card-id' ], 400); }
        $users = get_users( [ 'meta_key' => 'member_card_id', 'meta_value' => $card_number, 'number' => 1, 'fields' => 'ID']);
        if( empty($users) ){ return new WP_REST_Response([ 'error' => 'Card not found!' ], 404); }
        $user_id = $users[0];
        $result = self::generate_google_pass( $user_id, $card_number );
        if ( false === $result ) {
            return new WP_REST_Response([ 'error' => 'Google Wallet is not configured.' ], 500);
        }
    }

    public static function generate_google_pass( $user_id, $card_number = false, $redirect = true ){
        if ( ! self::is_configured() ) {
            error_log( 'Google Wallet is not configured. Set iw_wallet_card_google_issuer_id and iw_wallet_card_google_service_account_path.' );
            return false;
        }

        if( ! $card_number ) {
            $card_number = get_user_meta( $user_id, 'member_card_id', true );

            if ( empty( $card_number ) ) return false;
        }

        $subscription  = IW_Wallet_Common::get_active_or_last_subscription($user_id);
        $product_id    = IW_Wallet_Common::get_subscription_product_id($subscription);
        $googlePass = new IWGooglePass( self::service_account_path(), self::application_name() );
        $googlePass->createClass( self::issuer_id(), self::class_suffix(),  );
        $objectSuffix = self::get_object_suffix( $card_number );

        $card_data = [
            'user_id' => $user_id,
            'name' => IW_Wallet_Common::get_member_name($user_id),
            'card_number' => $card_number,
            'type' => IW_Wallet_Common::get_subscription_type($subscription),
            'features' => IW_Wallet_Common::get_subscription_features_string($product_id),
            'next_renewal' => IW_Wallet_Common::normalize_next_payment_date($subscription),
            'subscription' => $subscription,
            'locations' => IW_Wallet_Common::get_enabled_locations(),
            'qr_value' => IW_Member_Card::build_member_qr_payload( $card_number ),
        ];

        $googlePass->create_or_update_object( self::issuer_id(), self::class_suffix(), $objectSuffix, IW_Member_Card_Styles::get( $user_id, true ), $card_data );
        if( $redirect ){
            $link = $googlePass->createJwtNewObjects( self::issuer_id(), self::class_suffix(), $objectSuffix );
            if ( ! $link ) {
                return false;
            }
            wp_redirect($link);
            exit;
        }

        return true;
    }

    public static function get_object_suffix( $card_number ) {
        return self::object_prefix() . preg_replace( '/[^A-Za-z0-9._-]/', '_', (string) $card_number );
    }


    public static $instance = null;

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function download_google_pass_url() {
        $user_id = get_current_user_id();
        /*
        if ( ! $user_id ) return false;

        $subscriptions = wcs_get_users_subscriptions( $user_id );
        $active = false;
        foreach ( $subscriptions as $s ) {
            if ( $s->has_status( [ 'active' ] ) ) {
                $active = true;
                break;
            }
        }
        if ( ! $active ) return false;*/
        $card_number = get_user_meta( $user_id, 'member_card_id', true );
        if ( empty( $card_number ) ) return false;
        return home_url( '/wp-json/iw/v1' . self::$googlePassEndpoint . '?member-card-id=' . $card_number );
    }
}

$IW_Google_Wallet_Card = IW_Google_Wallet_Card::instance();
