<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IW_Google_Wallet_Tickets_Service {

    const REST_NS = 'iw/v1';
    const ROUTE_BASE = 'google-wallet/tickets';
    const DEFAULT_ISSUER_ID = '';
    const DEFAULT_CLASS_SUFFIX = 'tickets';
    const DEFAULT_OBJECT_SUFFIX_PREFIX = 'ticket_';

    public static function register_routes(): void {
        register_rest_route(
            self::REST_NS,
            '/' . self::ROUTE_BASE . '/public-pass',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ __CLASS__, 'handle_public_pass' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'ticket_uuid' => [ 'required' => true ],
                        'exp'         => [ 'required' => true ],
                        'sig'         => [ 'required' => true ],
                    ],
                ],
            ]
        );
    }

    public static function build_public_link_signature( string $ticket_uuid, int $exp ): string {
        $ticket_uuid = trim( $ticket_uuid );
        $exp = (int) $exp;
        if ( $ticket_uuid === '' || $exp <= 0 ) {
            return '';
        }

        return hash_hmac( 'sha256', $ticket_uuid . '|' . $exp, wp_salt( 'iw_ticketing_google_wallet_public' ) );
    }

    public static function build_public_save_url( string $ticket_uuid, int $exp ): string {
        $sig = self::build_public_link_signature( $ticket_uuid, $exp );
        if ( $sig === '' ) {
            return '';
        }

        return add_query_arg(
            [
                'ticket_uuid' => rawurlencode( trim( $ticket_uuid ) ),
                'exp'         => (int) $exp,
                'sig'         => $sig,
            ],
            rest_url( self::REST_NS . '/' . self::ROUTE_BASE . '/public-pass' )
        );
    }

    public static function handle_public_pass( WP_REST_Request $request ) {
        $ticket_uuid = trim( (string) $request->get_param( 'ticket_uuid' ) );
        $exp = (int) $request->get_param( 'exp' );
        $sig = trim( (string) $request->get_param( 'sig' ) );

        if ( ! self::validate_public_link( $ticket_uuid, $exp, $sig ) ) {
            return new WP_REST_Response( [ 'error' => 'invalid_or_expired_link' ], 403 );
        }

        if ( ! class_exists( 'IW_Tickets_DB' ) || ! method_exists( 'IW_Tickets_DB', 'get_ticket_by_uuid' ) ) {
            return new WP_REST_Response( [ 'error' => 'db_not_ready' ], 500 );
        }

        $ticket = IW_Tickets_DB::get_ticket_by_uuid( $ticket_uuid );
        if ( empty( $ticket ) ) {
            return new WP_REST_Response( [ 'error' => 'not_found' ], 404 );
        }

        $save_url = self::generate_save_url_for_ticket( $ticket );
        if ( $save_url === '' ) {
            return new WP_REST_Response( [ 'error' => 'google_wallet_generation_failed' ], 500 );
        }

        wp_redirect( $save_url );
        exit;
    }

    protected static function validate_public_link( string $ticket_uuid, int $exp, string $sig ): bool {
        $ticket_uuid = trim( $ticket_uuid );
        $sig = trim( $sig );
        $exp = (int) $exp;

        if ( $ticket_uuid === '' || $sig === '' || $exp <= 0 || time() > $exp ) {
            return false;
        }

        $expected = self::build_public_link_signature( $ticket_uuid, $exp );
        return $expected !== '' && hash_equals( $expected, $sig );
    }

    public static function generate_save_url_for_ticket_uuid( string $ticket_uuid ): string {
        if ( ! class_exists( 'IW_Tickets_DB' ) || ! method_exists( 'IW_Tickets_DB', 'get_ticket_by_uuid' ) ) {
            return '';
        }

        $ticket = IW_Tickets_DB::get_ticket_by_uuid( trim( $ticket_uuid ) );
        return empty( $ticket ) ? '' : self::generate_save_url_for_ticket( $ticket );
    }

    protected static function generate_save_url_for_ticket( array $ticket ): string {
        if ( ! self::ensure_google_dependencies() ) {
            return '';
        }

        try {
            $client = new Google\Client();
            $client->setApplicationName( self::get_application_name() );
            $client->setScopes( Google\Service\Walletobjects::WALLET_OBJECT_ISSUER );
            $client->setAuthConfig( self::get_key_file_path() );

            $service = new Google\Service\Walletobjects( $client );
            $issuer_id = self::get_issuer_id();
            $class_suffix = self::get_class_suffix();
            $object_suffix = self::get_object_suffix( (string) ( $ticket['ticket_uuid'] ?? '' ) );

            self::create_or_update_class( $service, $issuer_id, $class_suffix );
            self::create_or_update_object( $service, $issuer_id, $class_suffix, $object_suffix, $ticket );

            return self::create_jwt_save_url( $service, $issuer_id, $class_suffix, $object_suffix );
        } catch ( Throwable $e ) {
            error_log( 'GOOGLE WALLET TICKET ERROR: ' . $e->getMessage() );
            return '';
        }
    }

    protected static function ensure_google_dependencies(): bool {
        $autoloads = [
            dirname( __FILE__, 2 ) . '/vendor/autoload.php',
            trailingslashit( WP_PLUGIN_DIR ) . 'iw-wallet-card/vendor/autoload.php',
            trailingslashit( WP_PLUGIN_DIR ) . 'library/iw-wallet-card/vendor/autoload.php',
        ];

        foreach ( $autoloads as $autoload ) {
            if ( file_exists( $autoload ) ) {
                require_once $autoload;
                break;
            }
        }

        $key_file = self::get_key_file_path();

        return class_exists( 'Google\Client' )
            && class_exists( 'Google\Service\Walletobjects' )
            && class_exists( 'Firebase\JWT\JWT' )
            && $key_file !== ''
            && file_exists( $key_file );
    }

    protected static function get_key_file_path(): string {
        $default = '';
        if ( class_exists( 'IW_Google_Wallet_Card' ) && method_exists( 'IW_Google_Wallet_Card', 'service_account_path' ) ) {
            $default = (string) IW_Google_Wallet_Card::service_account_path();
        }

        if ( class_exists( 'IW_Ticketing' ) ) {
            $default = (string) IW_Ticketing::get_option( 'google_wallet_service_account_path', $default );
            $default = IW_Ticketing::resolve_path( $default, dirname( __FILE__, 2 ) );
        }

        return (string) apply_filters( 'iw_ticketing_google_wallet_key_file_path', $default );
    }

    protected static function get_issuer_id(): string {
        $default = self::DEFAULT_ISSUER_ID;
        if ( class_exists( 'IW_Google_Wallet_Card' ) ) {
            if ( method_exists( 'IW_Google_Wallet_Card', 'issuer_id' ) ) {
                $default = (string) IW_Google_Wallet_Card::issuer_id();
            } elseif ( property_exists( 'IW_Google_Wallet_Card', 'issuerId' ) ) {
                $default = (string) IW_Google_Wallet_Card::$issuerId;
            }
        }

        if ( class_exists( 'IW_Ticketing' ) ) {
            $default = (string) IW_Ticketing::get_option( 'google_wallet_issuer_id', $default );
        }

        return trim( (string) apply_filters( 'iw_ticketing_google_wallet_issuer_id', $default ) );
    }

    protected static function get_class_suffix(): string {
        $default = class_exists( 'IW_Ticketing' )
            ? (string) IW_Ticketing::get_option( 'google_wallet_class_suffix', self::DEFAULT_CLASS_SUFFIX )
            : self::DEFAULT_CLASS_SUFFIX;

        return sanitize_key( trim( (string) apply_filters( 'iw_ticketing_google_wallet_class_suffix', $default ) ) );
    }

    protected static function get_object_suffix( string $ticket_uuid ): string {
        $ticket_uuid = preg_replace( '/[^A-Za-z0-9._-]/', '_', trim( $ticket_uuid ) );
        $prefix = class_exists( 'IW_Ticketing' )
            ? (string) IW_Ticketing::get_option( 'google_wallet_object_prefix', self::DEFAULT_OBJECT_SUFFIX_PREFIX )
            : self::DEFAULT_OBJECT_SUFFIX_PREFIX;

        $prefix = preg_replace( '/[^A-Za-z0-9._-]/', '_', $prefix );
        return $prefix . $ticket_uuid;
    }

    protected static function get_application_name(): string {
        if ( class_exists( 'IW_Ticketing' ) ) {
            return (string) IW_Ticketing::get_option( 'google_wallet_application_name' );
        }

        return get_bloginfo( 'name' ) ?: 'Tickets';
    }

    protected static function get_jwt_origins(): array {
        if ( class_exists( 'IW_Google_Wallet_Card' ) && method_exists( 'IW_Google_Wallet_Card', 'jwt_origins' ) ) {
            $origins = IW_Google_Wallet_Card::jwt_origins();
        } elseif ( class_exists( 'IW_Ticketing' ) ) {
            $origins = IW_Ticketing::get_option( 'google_wallet_jwt_origins', home_url() );
        } else {
            $origins = home_url();
        }

        if ( is_string( $origins ) ) {
            $origins = preg_split( '/[\r\n,]+/', $origins );
        }

        $origins = array_values( array_filter( array_map( 'trim', (array) $origins ) ) );
        if ( empty( $origins ) ) {
            $origins = [ home_url() ];
        }

        return array_values( array_unique( $origins ) );
    }

    protected static function create_or_update_class( Google\Service\Walletobjects $service, string $issuer_id, string $class_suffix ): string {
        $class_id = "{$issuer_id}.{$class_suffix}";

        try {
            $service->genericclass->get( $class_id );
            return $class_id;
        } catch ( Google\Service\Exception $ex ) {
            if ( empty( $ex->getErrors() ) || ( $ex->getErrors()[0]['reason'] ?? '' ) !== 'classNotFound' ) {
                throw $ex;
            }
        }

        $new_class = new Google\Service\Walletobjects\GenericClass( [
            'id'                => $class_id,
            'reviewStatus'      => 'UNDER_REVIEW',
            'classTemplateInfo' => [
                'cardTemplateOverride' => [
                    'cardRowTemplateInfos' => [
                        [
                            'twoItems' => [
                                'startItem' => [ 'firstValue' => [ 'fields' => [ [ 'fieldPath' => "object.textModulesData['date']" ] ] ] ],
                                'endItem'   => [ 'firstValue' => [ 'fields' => [ [ 'fieldPath' => "object.textModulesData['time']" ] ] ] ],
                            ],
                        ],
                        [
                            'twoItems' => [
                                'startItem' => [ 'firstValue' => [ 'fields' => [ [ 'fieldPath' => "object.textModulesData['attendee']" ] ] ] ],
                                'endItem'   => [ 'firstValue' => [ 'fields' => [ [ 'fieldPath' => "object.textModulesData['category']" ] ] ] ],
                            ],
                        ],
                    ],
                ],
                'detailsTemplateOverride' => [
                    'detailsItemInfos' => [
                        [ 'item' => [ 'firstValue' => [ 'fields' => [ [ 'fieldPath' => "object.textModulesData['venue']" ] ] ] ] ],
                        [ 'item' => [ 'firstValue' => [ 'fields' => [ [ 'fieldPath' => "object.textModulesData['price']" ] ] ] ] ],
                        [ 'item' => [ 'firstValue' => [ 'fields' => [ [ 'fieldPath' => "object.textModulesData['order']" ] ] ] ] ],
                    ],
                ],
            ],
        ] );

        $response = $service->genericclass->insert( $new_class );
        return (string) $response->id;
    }

    protected static function create_or_update_object( Google\Service\Walletobjects $service, string $issuer_id, string $class_suffix, string $object_suffix, array $ticket ): string {
        $object_id = "{$issuer_id}.{$object_suffix}";
        $fields = self::build_object_fields( $issuer_id, $class_suffix, $object_suffix, $ticket );
        $object = new Google\Service\Walletobjects\GenericObject( $fields );

        try {
            $service->genericobject->update( $object_id, $object );
        } catch ( Google\Service\Exception $ex ) {
            if ( ! empty( $ex->getErrors() ) && ( $ex->getErrors()[0]['reason'] ?? '' ) === 'resourceNotFound' ) {
                $response = $service->genericobject->insert( $object );
                return (string) $response->id;
            }

            throw $ex;
        }

        return $object_id;
    }

    protected static function build_object_fields( string $issuer_id, string $class_suffix, string $object_suffix, array $ticket ): array {
        $ticket_uuid = (string) ( $ticket['ticket_uuid'] ?? '' );
        $barcode_hash = (string) ( $ticket['barcode_hash'] ?? '' );
        $post_id = (int) ( $ticket['post_id'] ?? 0 );
        $event_title = $post_id ? get_the_title( $post_id ) : '';
        if ( $event_title === '' ) {
            $event_title = _x( 'Ticket', 'wallet', 'iw-theme' );
        }

        $event_label = self::get_event_label( $post_id );
        $attendee_name = trim( (string) ( $ticket['attendee_name'] ?? '' ) );
        $price_category = self::get_price_category_label( (string) ( $ticket['price_category'] ?? '' ) );
        $ticket_price = self::format_ticket_price( $ticket['unit_price'] ?? '' );
        $date_time = self::format_slot_date_time( (string) ( $ticket['slot_start'] ?? '' ) );
        $building = self::get_building_data( $post_id );
        $styles = self::get_card_styles( $post_id );

        $fields = [
            'id'          => "{$issuer_id}.{$object_suffix}",
            'classId'     => "{$issuer_id}.{$class_suffix}",
            'genericType' => 'GENERIC_ENTRY_TICKET',
            'state'       => ( (string) ( $ticket['status'] ?? 'valid' ) === 'valid' ) ? 'ACTIVE' : 'INACTIVE',
            'version'     => time(),
            'header'      => self::localized_string( $event_title, 'el-GR' ),
        ];

        if ( $event_label !== '' ) {
            $fields['subheader'] = self::localized_string( $event_label, 'el-GR' );
        }

        $fields['cardTitle'] = self::localized_string( $attendee_name !== '' ? $attendee_name : _x( 'Ticket', 'wallet', 'iw-theme' ), 'el-GR' );

        if ( ! empty( $styles['background_color'] ) ) {
            $fields['hexBackgroundColor'] = $styles['background_color'];
        }

        if ( ! empty( $styles['google_images']['hero'] ) ) {
            $fields['heroImage'] = self::image( $styles['google_images']['hero'], 'Ticket hero image' );
        } elseif ( $post_id ) {
            $thumb = get_the_post_thumbnail_url( $post_id, 'large' );
            if ( $thumb ) {
                $fields['heroImage'] = self::image( $thumb, 'Ticket hero image' );
            }
        }

        if ( ! empty( $styles['google_images']['logo'] ) ) {
            $fields['logo'] = self::image( $styles['google_images']['logo'], 'Ticket logo' );
        }

        $modules = [];
        self::add_text_module( $modules, 'date', _x( 'ΗΜ/ΝΙΑ', 'wallet', 'iw-theme' ), $date_time['date'] );
        self::add_text_module( $modules, 'time', _x( 'ΩΡΑ', 'wallet', 'iw-theme' ), $date_time['time'] );
        self::add_text_module( $modules, 'attendee', _x( 'Ονοματεπώνυμο', 'wallet', 'iw-theme' ), $attendee_name );
        self::add_text_module( $modules, 'category', _x( 'Κατηγορία Εισιτηρίου', 'wallet', 'iw-theme' ), $price_category );
        self::add_text_module( $modules, 'price', _x( 'Τιμή', 'wallet', 'iw-theme' ), $ticket_price );
        self::add_text_module( $modules, 'venue', _x( 'Τοποθεσία', 'wallet', 'iw-theme' ), (string) ( $building['title'] ?? '' ) );
        self::add_text_module( $modules, 'order', _x( 'Παραγγελία', 'wallet', 'iw-theme' ), (string) ( $ticket['order_id'] ?? '' ) );

        if ( ! empty( $modules ) ) {
            $fields['textModulesData'] = $modules;
        }

        $barcode_value = $barcode_hash !== '' ? $barcode_hash : $ticket_uuid;
        if ( $barcode_value !== '' ) {
            $fields['barcode'] = new Google\Service\Walletobjects\Barcode( [
                'type'  => 'QR_CODE',
                'value' => $barcode_value,
            ] );
        }

        if ( ! empty( $building['latitude'] ) && ! empty( $building['longitude'] ) ) {
            $fields['merchantLocations'] = [
                new Google\Service\Walletobjects\MerchantLocation( [
                    'latitude'  => (float) $building['latitude'],
                    'longitude' => (float) $building['longitude'],
                ] ),
            ];
        }

        return $fields;
    }

    protected static function create_jwt_save_url( Google\Service\Walletobjects $service, string $issuer_id, string $class_suffix, string $object_suffix ): string {
        $service_account = json_decode( file_get_contents( self::get_key_file_path() ), true );
        if ( empty( $service_account['client_email'] ) || empty( $service_account['private_key'] ) ) {
            return '';
        }

        $full_object = $service->genericobject->get( "{$issuer_id}.{$object_suffix}" )->toSimpleObject();
        $full_class = $service->genericclass->get( "{$issuer_id}.{$class_suffix}" )->toSimpleObject();

        $claims = [
            'iss'     => $service_account['client_email'],
            'aud'     => 'google',
            'typ'     => 'savetowallet',
            'origins' => (array) apply_filters( 'iw_ticketing_google_wallet_jwt_origins', self::get_jwt_origins() ),
            'payload' => [
                'genericObjects' => [ $full_object ],
                'genericClasses' => [ $full_class ],
            ],
        ];

        $token = Firebase\JWT\JWT::encode( $claims, $service_account['private_key'], 'RS256' );
        return "https://pay.google.com/gp/v/save/{$token}";
    }

    protected static function localized_string( string $value, string $language = 'en-US' ): Google\Service\Walletobjects\LocalizedString {
        return new Google\Service\Walletobjects\LocalizedString( [
            'defaultValue' => new Google\Service\Walletobjects\TranslatedString( [
                'language' => $language,
                'value'    => $value,
            ] ),
        ] );
    }

    protected static function image( string $url, string $description ): Google\Service\Walletobjects\Image {
        return new Google\Service\Walletobjects\Image( [
            'sourceUri'          => new Google\Service\Walletobjects\ImageUri( [ 'uri' => $url ] ),
            'contentDescription' => self::localized_string( $description ),
        ] );
    }

    protected static function add_text_module( array &$modules, string $id, string $header, string $body ): void {
        $body = trim( wp_strip_all_tags( $body ) );
        if ( $body === '' ) {
            return;
        }

        $modules[] = new Google\Service\Walletobjects\TextModuleData( [
            'id'     => $id,
            'header' => $header,
            'body'   => $body,
        ] );
    }

    protected static function get_card_styles( int $post_id ): array {
        if ( $post_id <= 0 || ! class_exists( 'IW_Wallet_Ticket_Card_Styles' ) || ! method_exists( 'IW_Wallet_Ticket_Card_Styles', 'get' ) ) {
            return [];
        }

        return (array) IW_Wallet_Ticket_Card_Styles::get( $post_id, true );
    }

    protected static function get_event_label( int $post_id ): string {
        if ( $post_id <= 0 ) {
            return _x( 'Event', 'wallet', 'iw-theme' );
        }

        $post_type_object = get_post_type_object( get_post_type( $post_id ) );
        if ( empty( $post_type_object->labels->singular_name ) ) {
            return _x( 'Event', 'wallet', 'iw-theme' );
        }

        $is_permanent = (bool) get_field( 'is_permanent', $post_id );
        return ( $is_permanent ? __( 'Μόνιμη', 'iw-theme' ) : __( 'Περιοδική', 'iw-theme' ) ) . ' ' . $post_type_object->labels->singular_name;
    }

    protected static function get_price_category_label( string $price_category ): string {
        $parts = explode( ':', $price_category );
        $label = array_pop( $parts );
        return trim( (string) $label );
    }

    protected static function format_ticket_price( $unit_price ): string {
        if ( function_exists( 'wc_price' ) ) {
            $price = wc_price( (float) $unit_price );
            $price = str_replace( [ '&nbsp;', '&euro;' ], [ ' ', '€' ], $price );
            return wp_strip_all_tags( html_entity_decode( $price, ENT_QUOTES, get_bloginfo( 'charset' ) ) );
        }

        return is_numeric( $unit_price ) ? number_format_i18n( (float) $unit_price, 2 ) : '';
    }

    protected static function format_slot_date_time( string $slot_start ): array {
        $out = [ 'date' => '', 'time' => '' ];
        if ( trim( $slot_start ) === '' ) {
            return $out;
        }

        $tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
        try {
            $dt = new DateTime( $slot_start, $tz );
        } catch ( Throwable $e ) {
            return $out;
        }

        $day = wp_date( 'j', $dt->getTimestamp(), $tz );
        $mon = rtrim( wp_date( 'M', $dt->getTimestamp(), $tz ), '.' );
        $out['date'] = trim( $day . ' ' . ( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $mon, 'UTF-8' ) : strtoupper( $mon ) ) );
        $out['time'] = wp_date( 'H:i', $dt->getTimestamp(), $tz );

        return $out;
    }

    protected static function get_building_data( int $post_id ): array {
        if ( $post_id <= 0 || ! function_exists( 'get_field' ) ) {
            return [];
        }

        $building_location = get_field( 'building_location', $post_id );
        if ( empty( $building_location ) ) {
            return [];
        }

        $building_id = is_object( $building_location ) ? (int) $building_location->ID : (int) $building_location;
        if ( $building_id <= 0 ) {
            return [];
        }

        $data = [ 'title' => (string) get_the_title( $building_id ) ];
        $address = get_field( 'address', $building_id );
        if ( is_array( $address ) ) {
            $data['latitude'] = $address['lat'] ?? ( $address['latitude'] ?? null );
            $data['longitude'] = $address['lng'] ?? ( $address['longitude'] ?? null );
            if ( empty( $data['title'] ) && ! empty( $address['title'] ) ) {
                $data['title'] = (string) $address['title'];
            }
        }

        return $data;
    }
}
