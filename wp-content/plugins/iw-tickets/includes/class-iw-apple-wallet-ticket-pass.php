<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use PKPass\PKPass;

/**
 * Generates and outputs an Apple Wallet (.pkpass) for a specific ticket UUID.
 *
 * Ticket data source: wp_iw_tickets (via IW_Tickets_DB::get_ticket_by_uuid()).
 * Apple web service auth token: deterministic HMAC derived from ticket_uuid.
 */
class IW_Apple_Wallet_Ticket_Pass {

    /**
     * Simple per-request cache for card styles, keyed by "{post_id}|{hex}".
     */
    private static array $card_styles_cache = [];

    private static function get_card_styles_cached( int $post_id, bool $hex = false ): array {
        if ( $post_id <= 0 ) {
            return [];
        }
        $key = $post_id . '|' . ( $hex ? '1' : '0' );
        if ( isset( self::$card_styles_cache[ $key ] ) ) {
            return self::$card_styles_cache[ $key ];
        }

        $styles = [];
        if ( class_exists( 'IW_Wallet_Ticket_Card_Styles' ) && method_exists( 'IW_Wallet_Ticket_Card_Styles', 'get' ) ) {
            $styles = (array) IW_Wallet_Ticket_Card_Styles::get( $post_id, $hex );
        }

        self::$card_styles_cache[ $key ] = $styles;
        return $styles;
    }

    /**
     * Ticket passTypeIdentifier.
     * Single source of truth lives in IW_Apple_Wallet_Card (ticketsPassTypeIdentifier), but can be overridden.
     */
    public static function get_pass_type_identifier(): string {
        $default = 'pass.com.site.tickets';
        if ( class_exists( 'IW_Apple_Wallet_Card' ) ) {
            if ( method_exists( 'IW_Apple_Wallet_Card', 'tickets_pass_type_identifier' ) ) {
                $default = (string) IW_Apple_Wallet_Card::tickets_pass_type_identifier();
            } elseif ( property_exists( 'IW_Apple_Wallet_Card', 'ticketsPassTypeIdentifier' ) ) {
                $default = (string) IW_Apple_Wallet_Card::$ticketsPassTypeIdentifier;
            }
        }
        $val = (string) apply_filters( 'iw_ticketing_apple_wallet_pass_type_identifier', $default );
        return trim( $val );
    }

    public static function get_org_name(): string {
        $default = '';
        if ( class_exists( 'IW_Member_Card' ) && method_exists( 'IW_Member_Card', 'orgName' ) ) {
            $default = (string) IW_Member_Card::orgName();
        }
        $val = (string) apply_filters( 'iw_ticketing_apple_wallet_org_name', $default );
        return trim( $val );
    }

    public static function get_pass_description(): string {
        $default = '';
        if ( class_exists( 'IW_Member_Card' ) && method_exists( 'IW_Member_Card', 'ticketsProgramDescription' ) ) {
            $default = (string) IW_Member_Card::ticketsProgramDescription();
        }
        $val = (string) apply_filters( 'iw_ticketing_apple_wallet_ticket_description', $default );
        return trim( $val );
    }

    public static function get_team_identifier(): string {
        $default = '';
        if ( class_exists( 'IW_Apple_Wallet_Card' ) ) {
            if ( method_exists( 'IW_Apple_Wallet_Card', 'team_identifier' ) ) {
                $default = (string) IW_Apple_Wallet_Card::team_identifier();
            } elseif ( property_exists( 'IW_Apple_Wallet_Card', 'teamIdentifier' ) ) {
                $default = (string) IW_Apple_Wallet_Card::$teamIdentifier;
            }
        }
        $val = (string) apply_filters( 'iw_ticketing_apple_wallet_team_identifier', $default );
        return trim( $val );
    }

    /**
     * Certificate path for signing.
     */
    public static function get_certificate_path(): string {
        $default = '';
        if ( class_exists( 'IW_Apple_Wallet_Card' ) ) {
            if ( method_exists( 'IW_Apple_Wallet_Card', 'get_tickets_certificate_path' ) ) {
                $default = (string) IW_Apple_Wallet_Card::get_tickets_certificate_path();
            }
        }

        $val = (string) apply_filters( 'iw_ticketing_apple_wallet_certificate_path', $default );
        return trim( $val );
    }

    /**
     * Certificate password for signing.
     */
    public static function get_certificate_password(): string {
        $default = '';
        if ( class_exists( 'IW_Apple_Wallet_Card' ) ) {
            if ( method_exists( 'IW_Apple_Wallet_Card', 'tickets_certificate_password' ) ) {
                $default = (string) IW_Apple_Wallet_Card::tickets_certificate_password();
            } elseif ( property_exists( 'IW_Apple_Wallet_Card', 'ticketsCertificatePassword' ) ) {
                $default = (string) IW_Apple_Wallet_Card::$ticketsCertificatePassword;
            }
        }
        $val = (string) apply_filters( 'iw_ticketing_apple_wallet_certificate_password', $default );
        return trim( $val );
    }

    /**
     * WWDR certificate path.
     */
    public static function get_wwdr_path(): string {
        $default = '';
        if ( class_exists( 'IW_Apple_Wallet_Card' ) && method_exists( 'IW_Apple_Wallet_Card', 'get_wwdr_path' ) ) {
            $default = (string) IW_Apple_Wallet_Card::get_wwdr_path();
        }
        $val = (string) apply_filters( 'iw_ticketing_apple_wallet_wwdr_path', $default );
        return trim( $val );
    }







    /**
     * Base webService URL used by the pass to contact our Apple Wallet endpoints.
     */
    public static function get_web_service_url(): string {
        $default = home_url( '/wp-json/' . IW_Apple_Wallet_Tickets_Service::REST_NS . '/' . IW_Apple_Wallet_Tickets_Service::ROUTE_BASE );
        return (string) apply_filters( 'iw_ticketing_apple_wallet_web_service_url', $default );
    }

    /**
     * Build a deterministic authentication token (must match the service validation).
     */
    public static function get_authentication_token( string $ticket_uuid ): string {
        if ( class_exists( 'IW_Apple_Wallet_Tickets_Service' ) && method_exists( 'IW_Apple_Wallet_Tickets_Service', 'get_expected_auth_token_for_ticket' ) ) {
            return IW_Apple_Wallet_Tickets_Service::get_expected_auth_token_for_ticket( $ticket_uuid );
        }
        $ticket_uuid = trim( $ticket_uuid );
        return $ticket_uuid === '' ? '' : hash_hmac( 'sha256', $ticket_uuid, wp_salt( 'iw_ticketing_wallet' ) );
    }

    /**
     * Output a pkpass for the given ticket UUID.
     * This method prints the file and exits.
     */
    public static function output_pkpass_for_ticket_uuid( string $ticket_uuid, ?string $pass_type_identifier = null ): void {
        $ticket_uuid = trim( $ticket_uuid );
        if ( $ticket_uuid === '' ) {
            self::send_json_error_and_exit( 'missing_ticket_uuid', 400 );
        }

        if ( ! class_exists( 'IW_Tickets_DB' ) || ! method_exists( 'IW_Tickets_DB', 'get_ticket_by_uuid' ) ) {
            self::send_json_error_and_exit( 'db_not_ready', 500 );
        }

        $ticket = IW_Tickets_DB::get_ticket_by_uuid( $ticket_uuid );
        if ( empty( $ticket ) ) {
            self::send_json_error_and_exit( 'ticket_not_found', 404 );
        }

        if ( ! class_exists( PKPass::class ) ) {
            self::send_json_error_and_exit( 'pkpass_library_missing', 500 );
        }

        $pass_type_identifier = $pass_type_identifier ? trim( $pass_type_identifier ) : self::get_pass_type_identifier();
        if ( $pass_type_identifier === '' ) {
            self::send_json_error_and_exit( 'pass_type_identifier_missing', 500 );
        }

        $cert_path = self::get_certificate_path();
        $cert_pass = self::get_certificate_password();
        if ( $cert_path === '' || ! file_exists( $cert_path ) ) {
            self::send_json_error_and_exit( 'certificate_missing', 500, [ 'certificate_path' => $cert_path ] );
        }
        if ( $cert_pass === '' ) {
            self::send_json_error_and_exit( 'certificate_password_missing', 500 );
        }

        $tmp_dir = trailingslashit( WP_CONTENT_DIR ) . 'uploads/iw-wallet-tickets/tmp/' . uniqid( 'ticket_', true ) . '/';
        wp_mkdir_p( $tmp_dir );

        try {
            $pass = self::build_pass_payload( $ticket, $pass_type_identifier );

            $pk_pass = new PKPass( $cert_path, $cert_pass );
            $pk_pass->setName( 'ticket-' . $ticket_uuid . '.pkpass' );

            $wwdr = self::get_wwdr_path();
            if ( $wwdr !== '' && file_exists( $wwdr ) ) {
                $pk_pass->setWwdrCertificatePath( $wwdr );
            }

            $pk_pass->setData( $pass );

            $image_files = self::get_pass_image_files( $ticket, $tmp_dir );
            foreach ( $image_files as $img_file ) {
                if ( is_string( $img_file ) && $img_file !== '' && file_exists( $img_file ) ) {
                    $pk_pass->addFile( $img_file );
                }
            }



            $pk_pass->create( true );




            self::cleanup_tmp_dir( $tmp_dir );
            exit;

        } catch ( \Throwable $e ) {
            self::cleanup_tmp_dir( $tmp_dir );
            self::send_json_error_and_exit( 'pkpass_generation_failed', 500, [ 'message' => $e->getMessage() ] );
        }
    }

    /**
     * Build Apple pass payload array for a ticket.
     */
    protected static function build_pass_payload( array $ticket, string $pass_type_identifier ): array {
        $ticket_price  = wc_price( (string) ( $ticket['unit_price'] ?? '' ) );
        $ticket_price = str_replace( '&nbsp;', ' ', $ticket_price );
        $ticket_price = str_replace( '&euro;', '€', $ticket_price );
        $ticket_uuid   = (string) ( $ticket['ticket_uuid'] ?? '' );
        $barcode_hash  = (string) ( $ticket['barcode_hash'] ?? '' );
        $post_id       = (int) ( $ticket['post_id'] ?? 0 );
        $slot_start    = (string) ( $ticket['slot_start'] ?? '' );
        $slot_end      = (string) ( $ticket['slot_end'] ?? '' );
        $attendee_name = (string) ( $ticket['attendee_name'] ?? '' );
        $price_cat     = (string) ( $ticket['price_category'] ?? '' );
        $price_cat = explode( ':', $price_cat );
        $price_cat = array_pop( $price_cat );
        $order_id      = (string) ( $ticket['order_id'] ?? '' );

        $event_title = $post_id ? get_the_title( $post_id ) : '';
        if ( $event_title === '' ) {
            $event_title = self::get_pass_description();
        }

        if( ( $post_id && ( $pto = get_post_type_object( get_post_type( $post_id ) ) ) && ! empty( $pto->labels->singular_name ) )  ){
            $event_label = $pto->labels->singular_name;

            $isPermanent = get_field( 'is_permanent', $post_id );
            $event_label = ( $isPermanent ? __( 'Μόνιμη', 'iw-theme')  : __( 'Περιοδική', 'iw-theme')  ) . ' ' . $event_label;


        } else {
            $event_label = _x( 'Event', 'wallet', 'iw-theme' );
        }


        $tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
        $dt_start = null;
        $dt_end = null;
        try {
            if ( $slot_start !== '' ) {
                $dt_start = new DateTime( $slot_start, $tz );
            }
            if ( $slot_end !== '' ) {
                $dt_end = new DateTime( $slot_end, $tz );
            }
        } catch ( \Throwable $e ) {
            // ignore date parse failures
        }

        $date_line = '';
        $time_line = '';
        if ( $dt_start ) {
            $day = wp_date( 'j', $dt_start->getTimestamp(), $tz );
            $mon = wp_date( 'M', $dt_start->getTimestamp(), $tz );
            $mon = rtrim( $mon, '.' );
            if ( function_exists( 'mb_strtoupper' ) ) {
                $mon = mb_strtoupper( $mon, 'UTF-8' );
            } else {
                $mon = strtoupper( $mon );
            }
            $date_line = trim( $day . ' ' . $mon );

            $time_line = wp_date( 'H:i', $dt_start->getTimestamp(), $tz );
        }



        // Location (optional): derive from ACF building_location -> address.
        $location_name = '';
        $location_url = '';
        if ( $post_id  ) {
            $building_location = get_field( 'building_location', $post_id );
            if ( ! empty( $building_location ) ) {
                $building_id = is_object( $building_location ) ? (int) $building_location->ID : (int) $building_location;
                if ( $building_id ) {
                    $building_title = get_the_title( $building_id );
                    if ( is_string( $building_title ) && $building_title !== '' ) {
                        $location_name = $building_title;
                    }

                    $address = get_field( 'address', $building_id );
                    if ( is_array( $address ) ) {
                        $location_url = (string) ( $address['url'] ?? '' );
                        if ( $location_name === '' ) {
                            $location_name = (string) ( $address['title'] ?? '' );
                        }
                    }
                }
            }
        }

        $auth_token = self::get_authentication_token( $ticket_uuid );

        $card_styles = self::get_card_styles_cached( $post_id, false );

        $pass = [
            'formatVersion'       => 1,
            'passTypeIdentifier'  => $pass_type_identifier,
            'serialNumber'        => $ticket_uuid,
            'teamIdentifier'      => self::get_team_identifier(),
            'organizationName'    => self::get_org_name(),
            'description'         => self::get_pass_description(),
            'webServiceURL'       => self::get_web_service_url(),
            'authenticationToken' => $auth_token,

            "foregroundColor" => $card_styles[ 'text_color' ],
            "backgroundColor" => $card_styles[ "background_color" ],
            "labelColor" => $card_styles[ 'text_color' ],

            // Use eventTicket to better match ticket semantics.
            'eventTicket' => [
                'headerFields' => array_values( array_filter( [
                    $date_line !== '' ? [
                        'key'   => 'date',
                        'label' => _x( 'ΗΜ/ΝΙΑ', 'wallet', 'iw-theme' ),
                        'value' => $date_line,
                    ] : null,
                    $time_line !== '' ? [
                        'key'   => 'time',
                        'label' => _x( 'ΩΡΑ', 'wallet', 'iw-theme' ),
                        'value' => $time_line,
                    ] : null,
                ] ) ),
                'primaryFields' => [

                ],
                'secondaryFields' => array_values( array_filter( [
                    [
                        'key'   => 'event',
                        'label' => IW_Ticketing::remove_accents( strip_tags( str_replace( '<span>', ' / <span>', $location_name ) ) ),
                        'value' => $event_title,
                    ],

                ] ) ),
                'auxiliaryFields' => array_values( array_filter( [
                    $attendee_name !== '' ? [
                        'key'   => 'attendee',
                        'label' => IW_Ticketing::remove_accents( _x( 'Ονοματεπώνυμο', 'wallet', 'iw-theme' ) ),
                        'value' => $attendee_name,
                    ] : null,
                    $location_name !== '' ? [
                        'key'   => 'venue',
                        'label' => $price_cat,
                        'value' =>  strip_tags( $ticket_price ),
                    ] : null,

                ] ) ),
                'backFields' => array_values( array_filter( [
                    $location_url !== '' ? [
                        'key'   => 'maps',
                        'label' => _x( 'Οδηγίες', 'wallet', 'iw-theme' ),
                        'value' => $location_url,
                    ] : null,
                    $price_cat !== '' ? [
                        'key'   => 'category',
                        'label' => _x( 'Κατηγορία Εισιτηρίου', 'wallet', 'iw-theme' ),
                        'value' => $price_cat,
                    ] : null,
                ] ) ),
            ],
        ];

        // Barcode (QR). Use the opaque barcode_hash when available; fallback to ticket_uuid.
        $barcode_value = $barcode_hash !== '' ? $barcode_hash : $ticket_uuid;
        $pass['barcodes'] = [
            [
                'format'          => 'PKBarcodeFormatQR',
                'message'         => $barcode_value,
                'messageEncoding' => 'utf-8',
            ],
        ];

        return $pass;
    }

    /**
     * Provide required pass images.
     *
     * IMPORTANT: Apple passes typically require at least icon.png.
     * You can override using filter `iw_ticketing_apple_wallet_pass_images`.
     * The filter may return:
     *  - an array of local file paths, OR
     *  - an array of image arrays supported by IW_Wallet_Common::prepare_pass_images.
     */
    protected static function get_pass_image_files( array $ticket, string $tmp_dir ): array {
        $plugin_root = plugin_dir_path( dirname( __FILE__ ) );

        $post_id = (int) ( $ticket['post_id'] ?? 0 );

        // Wallet card styles may provide custom Apple images (file paths).
        // Ask for HEX so style resolver doesn't convert colors to RGB; images are unaffected.
        $card_styles = $post_id ? self::get_card_styles_cached( $post_id, true ) : [];
        $style_apple_images = ( isset( $card_styles['apple_images'] ) && is_array( $card_styles['apple_images'] ) ) ? $card_styles['apple_images'] : [];

        // Allow full override via filter.
        if ( ! empty( $images = apply_filters( 'iw_ticketing_apple_wallet_pass_images', [], $ticket, $tmp_dir ) ) ) {
            $all_are_paths = true;
            foreach ( $images as $img ) {
                if ( ! is_string( $img ) ) {
                    $all_are_paths = false;
                    break;
                }
            }
            return $all_are_paths ? $images : [];
        }

        // Defaults.
        $default_images = [
            $plugin_root . 'assets/apple-wallet/icon.png',
            $plugin_root . 'assets/apple-wallet/icon@2x.png',
            $plugin_root . 'assets/apple-wallet/logo.png',
            $plugin_root . 'assets/apple-wallet/logo@2x.png',
            $plugin_root . 'assets/apple-wallet/strip.png',
            $plugin_root . 'assets/apple-wallet/strip@2x.png',
        ];

        // If card styles provide custom images, prefer them over bundled defaults.
        // (Filter override above still has highest precedence.)
        if ( ! empty( $style_apple_images ) ) {
            try {
                // icon.png / icon@2x.png (29x29 and 58x58 typical)
                $icon_src = isset( $style_apple_images['icon'] ) ? (string) $style_apple_images['icon'] : '';
                if ( $icon_src !== '' && file_exists( $icon_src ) ) {
                    $icon_1x = trailingslashit( $tmp_dir ) . 'icon.png';
                    $icon_2x = trailingslashit( $tmp_dir ) . 'icon@2x.png';

                    $ed1 = wp_get_image_editor( $icon_src );
                    if ( ! is_wp_error( $ed1 ) ) {
                        $ed1->resize( 29, 29, true );
                        if ( method_exists( $ed1, 'set_quality' ) ) {
                            $ed1->set_quality( 90 );
                        }
                        $ed1->save( $icon_1x, 'image/png' );
                    }

                    $ed2 = wp_get_image_editor( $icon_src );
                    if ( ! is_wp_error( $ed2 ) ) {
                        $ed2->resize( 58, 58, true );
                        if ( method_exists( $ed2, 'set_quality' ) ) {
                            $ed2->set_quality( 90 );
                        }
                        $ed2->save( $icon_2x, 'image/png' );
                    }

                    if ( file_exists( $icon_1x ) ) {
                        $default_images[0] = $icon_1x;
                    }
                    if ( file_exists( $icon_2x ) ) {
                        $default_images[1] = $icon_2x;
                    }
                }

                // logo.png / logo@2x.png (160x50 and 320x100 typical)
                $logo_src = isset( $style_apple_images['logo'] ) ? (string) $style_apple_images['logo'] : '';
                if ( $logo_src !== '' && file_exists( $logo_src ) ) {
                    $logo_1x = trailingslashit( $tmp_dir ) . 'logo.png';
                    $logo_2x = trailingslashit( $tmp_dir ) . 'logo@2x.png';

                    $ed1 = wp_get_image_editor( $logo_src );
                    if ( ! is_wp_error( $ed1 ) ) {
                        $ed1->resize( 160, 50, true );
                        if ( method_exists( $ed1, 'set_quality' ) ) {
                            $ed1->set_quality( 90 );
                        }
                        $ed1->save( $logo_1x, 'image/png' );
                    }

                    $ed2 = wp_get_image_editor( $logo_src );
                    if ( ! is_wp_error( $ed2 ) ) {
                        $ed2->resize( 320, 100, true );
                        if ( method_exists( $ed2, 'set_quality' ) ) {
                            $ed2->set_quality( 90 );
                        }
                        $ed2->save( $logo_2x, 'image/png' );
                    }

                    if ( file_exists( $logo_1x ) ) {
                        $default_images[2] = $logo_1x;
                    }
                    if ( file_exists( $logo_2x ) ) {
                        $default_images[3] = $logo_2x;
                    }
                }

                // strip.png / strip@2x.png (375x123 and 750x246 typical)
                $strip_src = isset( $style_apple_images['strip'] ) ? (string) $style_apple_images['strip'] : '';
                if ( $strip_src !== '' && file_exists( $strip_src ) ) {
                    $strip_1x = trailingslashit( $tmp_dir ) . 'strip.png';
                    $strip_2x = trailingslashit( $tmp_dir ) . 'strip@2x.png';

                    $ed1 = wp_get_image_editor( $strip_src );
                    if ( ! is_wp_error( $ed1 ) ) {
                        $ed1->resize( 375, 123, true );
                        if ( method_exists( $ed1, 'set_quality' ) ) {
                            $ed1->set_quality( 90 );
                        }
                        $ed1->save( $strip_1x, 'image/png' );
                    }

                    $ed2 = wp_get_image_editor( $strip_src );
                    if ( ! is_wp_error( $ed2 ) ) {
                        $ed2->resize( 750, 246, true );
                        if ( method_exists( $ed2, 'set_quality' ) ) {
                            $ed2->set_quality( 90 );
                        }
                        $ed2->save( $strip_2x, 'image/png' );
                    }

                    if ( file_exists( $strip_1x ) ) {
                        $default_images[4] = $strip_1x;
                    }
                    if ( file_exists( $strip_2x ) ) {
                        $default_images[5] = $strip_2x;
                    }
                }

            } catch ( \Throwable $e ) {
                // Ignore and fall back to bundled assets / featured image logic below.
            }
        }

        // If event post has featured image, use it for strip images.
        // (But only if card styles did not already provide a strip.)
        if ( $post_id ) {
            $thumb_id   = (int) get_post_thumbnail_id( $post_id );
            $thumb_path = $thumb_id ? (string) get_attached_file( $thumb_id ) : '';

            if ( $thumb_path !== '' && file_exists( $thumb_path ) ) {
                // Apple strip.png usually: 375x123 (1x) and 750x246 (2x)
                $strip_1x = trailingslashit( $tmp_dir ) . 'strip.png';
                $strip_2x = trailingslashit( $tmp_dir ) . 'strip@2x.png';

                try {
                    $editor1 = wp_get_image_editor( $thumb_path );
                    if ( ! is_wp_error( $editor1 ) ) {
                        $editor1->resize( 375, 123, true );
                        if ( method_exists( $editor1, 'set_quality' ) ) {
                            $editor1->set_quality( 90 );
                        }
                        $saved1 = $editor1->save( $strip_1x, 'image/png' );

                        $editor2 = wp_get_image_editor( $thumb_path );
                        if ( ! is_wp_error( $editor2 ) ) {
                            $editor2->resize( 750, 246, true );
                            if ( method_exists( $editor2, 'set_quality' ) ) {
                                $editor2->set_quality( 90 );
                            }
                            $saved2 = $editor2->save( $strip_2x, 'image/png' );

                            if (
                                is_array( $saved1 ) && file_exists( $strip_1x )
                                && is_array( $saved2 ) && file_exists( $strip_2x )
                            ) {
                                // Replace default strip images with generated ones,
                                // but only if card styles did not already provide them.
                                if (
                                    ! ( isset( $style_apple_images['strip'] ) && file_exists( (string) $style_apple_images['strip'] ) )
                                ) {
                                    $default_images[4] = $strip_1x;
                                    $default_images[5] = $strip_2x;
                                }
                            }
                        }
                    }
                } catch ( \Throwable $e ) {
                    // ignore and fall back to bundled assets
                }
            }
        }

        return $default_images;
    }

    protected static function cleanup_tmp_dir( string $tmp_dir ): void {
        if ( $tmp_dir === '' || ! is_dir( $tmp_dir ) ) {
            return;
        }
        foreach ( glob( rtrim( $tmp_dir, '/' ) . '/*' ) as $file ) {
            if ( is_file( $file ) ) {
                @unlink( $file );
            }
        }
        @rmdir( $tmp_dir );
    }

    protected static function send_json_error_and_exit( string $code, int $status = 500, array $extra = [] ): void {
        status_header( $status );
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        echo wp_json_encode( array_merge( [ 'error' => $code ], $extra ) );
        exit;
    }
}
