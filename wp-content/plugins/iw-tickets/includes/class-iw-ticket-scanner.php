<?php
/**
 * Ticket Scanner / Verification (REST)
 *
 * Endpoint:
 *   GET /wp-json/iw/v1/verify-ticket?qr=<barcode_hash>[&dry_run=1][&gate=...][&device_id=...]
 *
 * Uses custom tables:
 * - {$wpdb->prefix}iw_tickets
 * - {$wpdb->prefix}iw_ticket_scans (written via IW_Tickets_DB::scan_ticket)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IW_Ticket_Scanner {

    public static $verificationTicketRouteEndpoint = '/verify-ticket';
    public static $resetTicketRouteEndpoint = '/reset-ticket';
    private const SCAN_EARLY_GRACE_MINUTES = 15;
    private const SCAN_LATE_GRACE_MINUTES = 15;

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
    }

    public static function register_rest_routes(): void {
        register_rest_route( 'iw/v1', self::$verificationTicketRouteEndpoint, [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'verify_ticket' ],
            'permission_callback' => [ __CLASS__, 'can_verify_tickets' ],
            'args'                => [ 'qr' => [ 'required' => true, 'type' => 'string' ],],
        ] );

        register_rest_route( 'iw/v1', self::$resetTicketRouteEndpoint, [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'reset_ticket' ],
            'permission_callback' => [ __CLASS__, 'can_verify_tickets' ],
        ] );
    }

    public static function can_verify_tickets(): bool {
        return function_exists( 'iw_scanner_user_can_scan' )
            ? iw_scanner_user_can_scan()
            : ( is_user_logged_in() && current_user_can( 'manage_options' ) );
    }

    public static function verify_ticket( WP_REST_Request $request ): array {

        if ( ! class_exists( 'IW_Tickets_DB' ) ) {
            return self::error( 'Ticketing module not available.' );
        }

        $qr = sanitize_text_field( wp_unslash( (string) $request->get_param( 'qr' ) ) );
        if ( $qr === '' ) return self::error( 'Missing QR value.' );

        $len = strlen( $qr );
        if ( $len < 16 || $len > 512 ) return self::error( 'Invalid QR value.' );

        $ticket = IW_Tickets_DB::get_ticket_by_barcode_hash( $qr );
        if ( ! $ticket ) {
            IW_Tickets_DB::insert_scan_log( 0, [
                'result'    => 'invalid',
                'gate'      => self::param_str( $request, 'gate' ),
                'device_id' => self::param_str( $request, 'device_id' ),
                'user_id'   => get_current_user_id() ?: self::param_int( $request, 'user_id' ),
                'note'      => 'Ticket not found for barcode_hash',
            ] );

            return self::error( 'Ticket not found.' );
        }

        $context = self::get_scan_context( $request );
        $status = (string) ( $ticket['status'] ?? '' );

        $location_error = self::get_ticket_location_error( $ticket, $request );
        if ( $location_error ) {
            return self::reject_ticket_scan( $ticket, $location_error['result'], $location_error['message'], $context, $request );
        }

        $window_error = self::get_ticket_scan_window_error( $ticket );

        if ( $status === 'used' ) {
            if ( $window_error && in_array( $window_error['result'], [ 'future_date', 'past_date' ], true ) ) {
                return self::reject_ticket_scan( $ticket, $window_error['result'], $window_error['message'], $context, $request );
            }

            $scan_result = IW_Tickets_DB::scan_ticket( (string) $ticket['ticket_uuid'], $context );
            $ticket = IW_Tickets_DB::get_ticket_by_uuid( (string) $ticket['ticket_uuid'] ) ?: $ticket;

            return self::build_response( false, 'Ticket already used.', $ticket, $scan_result, $request );
        }

        if ( ! self::is_valid_ticket_status( $status ) ) {
            $scan_result = IW_Tickets_DB::scan_ticket( (string) $ticket['ticket_uuid'], $context );
            $ticket = IW_Tickets_DB::get_ticket_by_uuid( (string) $ticket['ticket_uuid'] ) ?: $ticket;

            return self::build_response( false, 'Ticket not valid.', $ticket, $scan_result, $request );
        }

        $force_scan = (string) $request->get_param( 'force_scan' ) === '1';
        if ( $window_error && ! ( $force_scan && $window_error['result'] === 'outside_slot' ) ) {
            return self::reject_ticket_scan( $ticket, $window_error['result'], $window_error['message'], $context, $request );
        }

        $scan_result = IW_Tickets_DB::scan_ticket( (string) $ticket['ticket_uuid'], $context );
        $ticket = IW_Tickets_DB::get_ticket_by_uuid( (string) $ticket['ticket_uuid'] ) ?: $ticket;

        $error_message = '';
        if ( $scan_result === 'already_used' ) {
            $error_message = 'Ticket already used.';
        } else if ( $scan_result === 'not_valid' ) {
            $error_message = 'Ticket not valid.';
        } else if ( $scan_result === 'invalid' ) {
            $error_message = 'Ticket not found.';
        }

        if ( ! empty( $error_message ) ) {
            return self::build_response( false, $error_message, $ticket, $scan_result, $request );
        }

        return self::build_response( true, null, $ticket, $scan_result, $request );
    }

    public static function reset_ticket( WP_REST_Request $request ): array {

        if ( ! class_exists( 'IW_Tickets_DB' ) ) {
            return self::error( 'Ticketing module not available.' );
        }

        $ticket_uuid = sanitize_text_field( wp_unslash( (string) $request->get_param( 'ticket_uuid' ) ) );
        $qr = sanitize_text_field( wp_unslash( (string) $request->get_param( 'qr' ) ) );

        $ticket = $ticket_uuid !== ''
            ? IW_Tickets_DB::get_ticket_by_uuid( $ticket_uuid )
            : null;

        if ( ! $ticket && $qr !== '' ) {
            $ticket = IW_Tickets_DB::get_ticket_by_barcode_hash( $qr );
        }

        if ( ! $ticket ) {
            return self::error( 'Ticket not found.' );
        }

        $context = self::get_scan_context( $request );
        $status = (string) ( $ticket['status'] ?? '' );

        $location_error = self::get_ticket_location_error( $ticket, $request );
        if ( $location_error ) {
            return self::reject_ticket_scan( $ticket, $location_error['result'], $location_error['message'], $context, $request );
        }

        if ( $status !== 'used' ) {
            return self::build_response( false, 'Ticket is not used.', $ticket, 'reset_not_allowed', $request );
        }

        if ( ! self::is_ticket_for_today( $ticket ) ) {
            return self::build_response( false, 'Ticket can only be reset on its event day.', $ticket, 'reset_not_allowed', $request );
        }

        $scan_result = IW_Tickets_DB::reset_ticket_scan( (string) $ticket['ticket_uuid'], $context );
        $ticket = IW_Tickets_DB::get_ticket_by_uuid( (string) $ticket['ticket_uuid'] ) ?: $ticket;

        if ( $scan_result !== 'reset' ) {
            return self::build_response( false, 'Ticket could not be reset.', $ticket, $scan_result, $request );
        }

        return self::build_response( true, null, $ticket, 'ok', $request );
    }

    private static function build_response( bool $valid, ?string $error, array $ticket, ?string $scan_result, WP_REST_Request $request ): array {

        $post_id = (int) ( $ticket['post_id'] ?? 0 );
        $event   = $post_id ? get_post( $post_id ) : null;

        $event_payload = null;
        $building_payload = null;

        if ( $event && $event instanceof WP_Post ) {
            $is_permanent = $event->post_type === 'exhibition' && self::is_event_permanent_exhibition( $event->ID );

            $event_payload = [
                'id'              => $event->ID,
                'type'            => $event->post_type,
                'title'           => get_the_title( $event ),
                'url'             => get_permalink( $event ),
                'image'           => get_the_post_thumbnail_url( $event, 'large' ) ?: null,
                'thumb'           => get_the_post_thumbnail_url( $event, 'thumbnail' ) ?: get_the_post_thumbnail_url( $event, 'medium' ) ?: null,
                'is_permanent'    => $is_permanent,
                'exhibition_type' => $is_permanent ? 'permanent' : '',
            ];

            $building_id = self::get_event_building_id( $event->ID );

            if ( $building_id ) {
                $building_payload = [
                    'id'    => $building_id,
                    'title' => get_the_title( $building_id ),
                    'url'   => get_permalink( $building_id ),
                    'image' => get_the_post_thumbnail_url( $building_id, 'large' ) ?: null,
                    'address' => function_exists('get_field') ? get_field( 'address', $building_id ) : get_post_meta( $building_id, 'address', true ),
                ];
            }
        }

        $ticket_payload = [
            'id'             => isset( $ticket['id'] ) ? (int) $ticket['id'] : null,
            'ticket_uuid'    => (string) ( $ticket['ticket_uuid'] ?? '' ),
            'barcode_hash'   => (string) ( $ticket['barcode_hash'] ?? '' ),
            'order_id'       => ! empty( $ticket['order_id'] ) ? (int) $ticket['order_id'] : null,
            'order_item_id'  => ! empty( $ticket['order_item_id'] ) ? (int) $ticket['order_item_id'] : null,
            'post_id'        => ! empty( $ticket['post_id'] ) ? (int) $ticket['post_id'] : null,
            'slot_start'     => ! empty( $ticket['slot_start'] ) ? (string) $ticket['slot_start'] : null,
            'slot_end'       => ! empty( $ticket['slot_end'] ) ? (string) $ticket['slot_end'] : null,
            'ticket_type'    => ! empty( $ticket['ticket_type'] ) ? (string) $ticket['ticket_type'] : null,
            'price_category' => ! empty( $ticket['price_category'] ) ? (string) $ticket['price_category'] : null,
            'unit_price'     => isset( $ticket['unit_price'] ) ? (string) $ticket['unit_price'] : null,
            'currency'       => ! empty( $ticket['currency'] ) ? (string) $ticket['currency'] : null,
            'channel'        => ! empty( $ticket['channel'] ) ? (string) $ticket['channel'] : null,
            'attendee_name'  => ! empty( $ticket['attendee_name'] ) ? (string) $ticket['attendee_name'] : null,
            'attendee_email' => ! empty( $ticket['attendee_email'] ) ? (string) $ticket['attendee_email'] : null,
            // Note: status may be stored as '0' in older rows; do not treat it as empty.
            'status'         => array_key_exists( 'status', $ticket ) ? (string) $ticket['status'] : null,
            'issued_at'      => ! empty( $ticket['issued_at'] ) ? (string) $ticket['issued_at'] : null,
            'used_at'        => ! empty( $ticket['used_at'] ) ? (string) $ticket['used_at'] : null,
        ];

        $out = [
            'valid'      => $valid,
            'error'      => $error,
            'scan'       => [
                'result'   => $scan_result,
                'dry_run'  => (string) $request->get_param( 'dry_run' ) === '1',
                'force_scan' => (string) $request->get_param( 'force_scan' ) === '1',
                'can_force_scan' => $scan_result === 'outside_slot',
                'gate'     => self::param_str( $request, 'gate' ),
                'device_id'=> self::param_str( $request, 'device_id' ),
            ],
            'ticket'     => $ticket_payload,
            'event'      => $event_payload,
            'building'   => $building_payload,
            'timestamp'  => current_time( 'mysql' ),
        ];

        return $out;
    }

    private static function get_scan_context( WP_REST_Request $request ): array {
        return [
            'gate'      => self::param_str( $request, 'gate' ),
            'device_id' => self::param_str( $request, 'device_id' ),
            'user_id'   => get_current_user_id() ?: self::param_int( $request, 'user_id' ),
        ];
    }

    private static function is_valid_ticket_status( string $status ): bool {
        return $status === 'valid' || $status === '' || $status === '0' || strtoupper( $status ) === 'NULL';
    }

    private static function reject_ticket_scan( array $ticket, string $result, string $message, array $context, WP_REST_Request $request ): array {
        IW_Tickets_DB::insert_scan_log( (int) ( $ticket['id'] ?? 0 ), [
            'result'    => $result,
            'gate'      => $context['gate'] ?? null,
            'user_id'   => $context['user_id'] ?? null,
            'device_id' => $context['device_id'] ?? null,
            'note'      => $message,
        ] );

        return self::build_response( false, $message, $ticket, $result, $request );
    }

    private static function get_ticket_location_error( array $ticket, WP_REST_Request $request ): ?array {
        $selected_building_id = self::param_int( $request, 'building_id' );
        $ticket_building_id = self::get_ticket_building_id( $ticket );

        if ( $selected_building_id && $ticket_building_id && (int) $selected_building_id !== (int) $ticket_building_id ) {
            return [
                'result'  => 'wrong_location',
                'message' => 'Ticket belongs to another location.',
            ];
        }

        return null;
    }

    private static function get_ticket_scan_window_error( array $ticket ): ?array {
        $start = self::parse_ticket_datetime( (string) ( $ticket['slot_start'] ?? '' ) );
        $end = self::parse_ticket_datetime( (string) ( $ticket['slot_end'] ?? '' ) );

        if ( ! $start && ! $end ) {
            return null;
        }

        $timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
        $now = new DateTimeImmutable( 'now', $timezone );

        $now_day = $now->setTime( 0, 0, 0 );
        $start_day = $start ? $start->setTime( 0, 0, 0 ) : null;
        $end_day = $end ? $end->setTime( 0, 0, 0 ) : null;

        if ( $start_day && $now_day < $start_day ) {
            return [
                'result'  => 'future_date',
                'message' => 'Ticket date has not arrived yet.',
            ];
        }

        if ( $end_day && $now_day > $end_day ) {
            return [
                'result'  => 'past_date',
                'message' => 'Ticket date has passed.',
            ];
        }

        if ( $start ) {
            $allowed_start = $start->modify( '-' . self::SCAN_EARLY_GRACE_MINUTES . ' minutes' );

            if ( $now < $allowed_start ) {
                return [
                    'result'  => 'outside_slot',
                    'message' => 'Ticket is outside its entry slot.',
                ];
            }
        }

        if ( $end ) {
            $allowed_end = $end->modify( '+' . self::SCAN_LATE_GRACE_MINUTES . ' minutes' );

            if ( $now > $allowed_end ) {
                return [
                    'result'  => 'outside_slot',
                    'message' => 'Ticket is outside its entry slot.',
                ];
            }
        }

        return null;
    }

    private static function is_ticket_for_today( array $ticket ): bool {
        $start = self::parse_ticket_datetime( (string) ( $ticket['slot_start'] ?? '' ) );
        $end = self::parse_ticket_datetime( (string) ( $ticket['slot_end'] ?? '' ) );

        $slot_date = $start ?: $end;
        if ( ! $slot_date ) {
            return false;
        }

        $timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
        $today = new DateTimeImmutable( 'now', $timezone );

        return $slot_date->format( 'Y-m-d' ) === $today->format( 'Y-m-d' );
    }

    private static function parse_ticket_datetime( string $value ): ?DateTimeImmutable {
        $value = trim( $value );
        if ( $value === '' ) {
            return null;
        }

        try {
            $timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
            return new DateTimeImmutable( $value, $timezone );
        } catch ( Exception $e ) {
            return null;
        }
    }

    private static function get_ticket_building_id( array $ticket ): int {
        $post_id = (int) ( $ticket['post_id'] ?? 0 );
        return $post_id ? self::get_event_building_id( $post_id ) : 0;
    }

    private static function get_event_building_id( int $event_id ): int {
        if ( $event_id <= 0 ) {
            return 0;
        }

        $post = get_post( $event_id );
        if ( $post instanceof WP_Post && $post->post_type === 'building' ) {
            return $event_id;
        }

        $building = function_exists( 'get_field' ) ? get_field( 'building_location', $event_id ) : null;
        $building_id = self::normalize_post_id( $building );

        if ( ! $building_id ) {
            $building_id = self::normalize_post_id( get_post_meta( $event_id, 'building_location', true ) );
        }

        return $building_id;
    }

    private static function is_event_permanent_exhibition( int $event_id ): bool {
        if ( $event_id <= 0 ) {
            return false;
        }

        $value = function_exists( 'get_field' ) ? get_field( 'is_permanent', $event_id ) : get_post_meta( $event_id, 'is_permanent', true );

        return $value === true || $value === 1 || $value === '1' || $value === 'yes' || $value === 'true';
    }

    private static function normalize_post_id( $value ): int {
        if ( $value instanceof WP_Post ) {
            return (int) $value->ID;
        }

        if ( is_object( $value ) && ! empty( $value->ID ) ) {
            return (int) $value->ID;
        }

        if ( is_array( $value ) ) {
            if ( ! empty( $value['ID'] ) ) {
                return (int) $value['ID'];
            }

            $first = reset( $value );
            return self::normalize_post_id( $first );
        }

        return is_numeric( $value ) ? (int) $value : 0;
    }

    private static function error( string $message ): array {
        return [ 'valid'=> false, 'error' => $message, 'timestamp' => current_time( 'mysql' ) ];
    }

    private static function param_str( WP_REST_Request $request, string $key ): ?string {
        $v = $request->get_param( $key );
        if ( $v === null ) return null;
        $v = trim( sanitize_text_field( wp_unslash( (string) $v ) ) );
        return $v !== '' ? $v : null;
    }

    private static function param_int( WP_REST_Request $request, string $key ): ?int {
        $v = $request->get_param( $key );
        if ( $v === null || $v === '' ) return null;
        return (int) $v;
    }
}

IW_Ticket_Scanner::init();
