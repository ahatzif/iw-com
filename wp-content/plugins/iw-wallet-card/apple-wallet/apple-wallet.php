<?php
require_once( 'iw-apple-wallet-service.php' );



use PKPass\PKPass;

class IW_Apple_Wallet_Card {

    public static  $applePassEndpoint = "/add-to-apple-wallet";
    public static $passTypeIdentifier = '';
    public static $teamIdentifier    = '';
    public static function get_private_key_path() { return self::credential_path( 'apple_private_key_path' ); }
    public static function  get_certificate_path() { return self::credential_path( 'apple_certificate_path' ); }

    public static function get_wwdr_path() { return self::credential_path( 'apple_wwdr_path' ); }
    public static $certificatePassword   = '';


    // Tickets must use their own Pass Type ID + certificate (do not reuse membership certificate).
    public static $ticketsPassTypeIdentifier = '';

    public static function get_tickets_certificate_path() { return self::credential_path( 'apple_tickets_certificate_path' ); }

    public static $ticketsCertificatePassword = '';

    private static function option( string $key, $default = '' ) {
        return class_exists( 'IW_Member_Card' ) ? IW_Member_Card::get_option( $key, $default ) : $default;
    }

    private static function credential_path( string $key ): string {
        $path = self::option( $key, '' );
        return class_exists( 'IW_Member_Card' ) ? IW_Member_Card::resolve_path( $path, plugin_dir_path( __FILE__ ) ) : $path;
    }

    public static function pass_type_identifier(): string {
        return (string) self::option( 'apple_pass_type_identifier', self::$passTypeIdentifier );
    }

    public static function tickets_pass_type_identifier(): string {
        return (string) self::option( 'apple_tickets_pass_type_identifier', self::$ticketsPassTypeIdentifier );
    }

    public static function team_identifier(): string {
        return (string) self::option( 'apple_team_identifier', self::$teamIdentifier );
    }

    public static function key_id(): string {
        return (string) self::option( 'apple_key_id', '' );
    }

    public static function certificate_password(): string {
        return (string) self::option( 'apple_certificate_password', self::$certificatePassword );
    }

    public static function tickets_certificate_password(): string {
        return (string) self::option( 'apple_tickets_certificate_password', self::$ticketsCertificatePassword );
    }



    public static function init(){
        add_action( 'rest_api_init', function (){
            register_rest_route('iw/v1', self::$applePassEndpoint, [ 'methods'  => 'GET', 'callback' => [ 'IW_Apple_Wallet_Card', 'rest_download_apple_pass' ], 'permission_callback' => '__return_true']);
        } );
    }

    public static function rest_download_apple_pass( WP_REST_Request $request ){
        $card_number = sanitize_text_field( $request->get_param('member-card-id') );
        if( empty($card_number) ) { return new WP_REST_Response([ 'error' => 'Missing member-card-id' ], 400); }
        $users = get_users( [ 'meta_key' => 'member_card_id', 'meta_value' => $card_number, 'number' => 1, 'fields' => 'ID']);
        if( empty($users) ){ return new WP_REST_Response([ 'error' => 'Card not found!' ], 404); }
        $user_id = $users[0];
        $result = self::generate_apple_pass( $user_id, $card_number );
        if ( false === $result ) {
            return new WP_REST_Response([ 'error' => 'Apple Wallet is not configured.' ], 500);
        }
    }

    public static function download_apple_pass_url() {
        $user_id = get_current_user_id();
        /*if ( ! $user_id ) return false;

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
        return home_url( '/wp-json/iw/v1' . self::$applePassEndpoint . '?member-card-id=' . $card_number );
    }



    public static function prepare_pass_images( $image_arr, $tmp_dir ) {

        $images = [];


        foreach ( (array) $image_arr as $name => $path3x ) {
            if ( empty( $path3x ) || ! file_exists( $path3x ) ) {
                continue;
            }

            $out_1x = $tmp_dir . "{$name}.png";
            $out_2x = $tmp_dir . "{$name}@2x.png";
            $out_3x = $tmp_dir . "{$name}@3x.png";

            copy($path3x, $out_3x);
            $images[] = $out_3x;
            $images[] = $out_1x;
            $images[] = $out_2x;

            self::resize_image($path3x, $out_1x, 1/3);
            self::resize_image($path3x, $out_2x, 2/3);


        }

        return $images;
    }

    public static function resize_image($src, $dest, $scale) {

        $info = getimagesize($src);
        list($w, $h) = $info;

        $new_w = intval($w * $scale);
        $new_h = intval($h * $scale);

        $src_img = imagecreatefromstring(file_get_contents($src));
        $dst_img = imagecreatetruecolor($new_w, $new_h);

        imagealphablending($dst_img, false);
        imagesavealpha($dst_img, true);

        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $w, $h);

        $ext = strtolower(pathinfo($dest, PATHINFO_EXTENSION));
        if ($ext === 'png') {
            imagepng($dst_img, $dest);
        }
        imagedestroy($src_img);
        imagedestroy($dst_img);
    }


    public static function generate_apple_pass( $user_id, $card_number = false ){
        if ( ! class_exists( PKPass::class ) ) {
            error_log( 'Apple Wallet pass generation requires composer install.' );
            return false;
        }

        $certificate_path = self::get_certificate_path();
        if (
            '' === self::pass_type_identifier()
            || '' === self::team_identifier()
            || '' === $certificate_path
            || ! file_exists( $certificate_path )
        ) {
            error_log( 'Apple Wallet is not configured. Set iw_wallet_card_apple_pass_type_identifier, iw_wallet_card_apple_team_identifier, and iw_wallet_card_apple_certificate_path.' );
            return false;
        }

        $current_user = get_user( $user_id );
        if( ! $card_number ) {
            $card_number = get_user_meta( $user_id, 'member_card_id', true );
            if ( empty( $card_number ) ) return false;
        }
        $card_styles = IW_Member_Card_Styles::get( $user_id );
        $subscription = IW_Wallet_Common::get_active_or_last_subscription( $user_id );
        $type = $subscription ? IW_Wallet_Common::get_subscription_type( $subscription ) : '';
        $voided = get_user_meta($user_id, 'apple_wallet_pass_voided', true) ? true : false;

        // Normalize next payment date to DateTime object
        $next_renewal = null;
        if ( $subscription ) {
            $raw_next = $subscription->get_date('next_payment');
            if ( $raw_next ) {
                if ( $raw_next instanceof DateTime ) {
                    $next_renewal = $raw_next;
                } else {
                    $next_renewal = new DateTime( $raw_next );
                }
            }
        }

        $locations = self::get_enabled_location();

        $subscription_item      = $subscription ? IW_Wallet_Common::get_subscription_membership_item( $subscription ) : null;
        $product_id             = $subscription_item ? $subscription_item->get_product_id() : null;
        $subscriptionFeatures   = $product_id ? get_field( 'features', $product_id ) : [];



        $features = "\n";
        foreach ($subscriptionFeatures as $subscriptionFeature) {
            $features .= "• " . $subscriptionFeature["feature"] . "\n\n";
        }


        $pass = [
            "locations" => $locations,
            "voided" => $voided,
            "formatVersion" => 1,
            "teamIdentifier" => self::team_identifier(),
            "passTypeIdentifier" => self::pass_type_identifier(),
            "organizationName" => IW_Member_Card::orgName(),
            "description" => IW_Member_Card::programDescription(),
            "version" => get_user_meta($user_id, 'apple_wallet_pass_version', true),
            "serialNumber" => $card_number,
            "expirationDate" => $next_renewal ? $next_renewal->format('Y-m-d\TH:i:sP') : null,
            "webServiceURL"       => home_url('/wp-json/iw-apple-wallet'),
            "authenticationToken" => wp_hash($card_number . self::option( 'apple_auth_salt', wp_salt( 'auth' ) ) ),
            "foregroundColor" => $card_styles[ 'text_color' ],
            "backgroundColor" => $card_styles[ "background_color" ],
            "labelColor" => $card_styles[ 'text_color' ],
            "logoText" => "",
            "storeCard" => [
                "headerFields"      => [ [ "key" => "type", "label" => _x( "TYPE", 'wallet', 'iw-theme' ), "value" => $type, "textStyle"=> "PKTextStyleCaption2" ] ],
                "primaryFields"     => [ ],
                "secondaryFields"   => [
                    [ "key" => "name",  "textStyle"=> "PKTextStyleCaption2"         , "label" => "Name",            "value" => get_user_meta($user_id, 'first_name', true) . ' ' . get_user_meta($user_id, 'last_name', true) ],
                    [
                        "key" => "member_since",
                        "label" => _x( 'MEMBER SINCE', 'wallet', 'iw-theme' ),
                        "value" => $subscription ? $subscription->get_date_created()->date_i18n( 'd/m/Y' ) : '—',
                          "textStyle"=> "PKTextStyleCaption2"

                    ],
                    [
                        "key" => "expires",
                        "label" => _x( 'RENEWS ON', 'wallet', 'iw-theme' ),
                        "value" => $next_renewal ? $next_renewal->format('d/m/Y') : '—',
                        "textStyle" => "PKTextStyleCaption2"
                    ],
                ],
                "backFields" => [
                      [
                          "key" => "card-number",
                          "label" => _x( 'Card Number', 'wallet', 'iw-theme' ),
                          "value" => "\n" . $card_number . "\n"
                      ],
                    [
                        "key" => "membership",
                        "label" => _x( 'Features', 'wallet', 'iw-theme' ),
                        "value" => $features
                    ]
                ]
            ],
            'barcodes' => [
                [
                    'format'          => 'PKBarcodeFormatQR',
                    'message'         => IW_Member_Card::build_member_qr_payload( $card_number ),
                    'messageEncoding' => 'utf-8'
                ]
            ]
        ];
        $default_images_path = plugin_dir_path(__FILE__) . 'default-images/';



        $tmp_dir = plugin_dir_path(__FILE__) . 'tmp/' . uniqid() . '/';
        wp_mkdir_p($tmp_dir);

        $images = self::prepare_pass_images( $card_styles[ 'apple_images' ], $tmp_dir );

        $pk_pass = new PKPass($certificate_path, self::certificate_password() );
        $wwdr = self::get_wwdr_path();
        if (file_exists($wwdr)) {
            $pk_pass->setWwdrCertificatePath($wwdr);
        }

        $pk_pass->setData($pass);

        foreach ($images as $image) {
         $pk_pass->addFile($image);
        }



        $pk_pass->create(true);

        array_map('unlink', glob("$tmp_dir/*"));
        @rmdir($tmp_dir);
        exit;

    }


    /**
     * Fetch all posts (building, food-beverage, space) that have
     * "enable_location_for_wallet_cards" = true and valid lat/long.
     *
     * @return array PKPass-ready locations
     */
    public static function get_enabled_location() {

        $post_types = [ 'building'];

        $query = new WP_Query([ 'post_type' => $post_types, 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_query' => [ [ 'key' => 'enable_location_for_wallet_cards', 'value'  => '1', 'compare' => '=', ] ] ]);
        $locations = [];
        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {

                $enabled = get_field( 'enable_location_for_wallet_cards', $post->ID );
                if ( ! $enabled ) continue;
                $lat  = get_field( 'lat',  $post->ID );
                $long = get_field( 'long', $post->ID );

                if ( is_numeric( $lat ) && is_numeric( $long ) ) {
                    $relevant_text = get_field( 'relevant_text', $post->ID );
                    $locations[] = [
                        'latitude'  => (float) $lat,
                        'longitude' => (float) $long,
                        'relevantText' => ! empty( $relevant_text) ?  $relevant_text : strip_tags( apply_filters( 'the_title', $post->post_title, $post->ID ) ),
                    ];
                }
            }
        }

        return $locations;
    }


}

IW_Apple_Wallet_Card::init();
