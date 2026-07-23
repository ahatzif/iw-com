<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IW_Zebra_Print_Queue {
    const POST_TYPE       = 'iw_zebra_print_job';
    const OPTION_KEY      = 'iw_zebra_print_agent_settings';
    const STATUS_QUEUED   = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_DONE     = 'done';
    const STATUS_FAILED   = 'failed';
    const DEFAULT_PRINTER = 'default';

    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'register_post_type' ] );
    }

    public static function register_post_type(): void {
        register_post_type(
            self::POST_TYPE,
            [
                'labels' => [
                    'name'          => __( 'Zebra print jobs', 'iw-theme' ),
                    'singular_name' => __( 'Zebra print job', 'iw-theme' ),
                ],
                'public'              => false,
                'show_ui'             => false,
                'show_in_menu'        => false,
                'show_in_rest'        => false,
                'exclude_from_search' => true,
                'supports'            => [ 'title', 'editor' ],
                'capability_type'     => 'post',
            ]
        );
    }

    public static function get_settings(): array {
        $stored = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $stored ) ) {
            $stored = [];
        }

        $acf_settings = self::get_acf_settings();
        if ( ! empty( $acf_settings ) ) {
            $stored = array_merge( $stored, $acf_settings );
        }

        $mode = defined( 'IW_ZEBRA_PRINT_MODE' )
            ? (string) IW_ZEBRA_PRINT_MODE
            : (string) ( $stored['mode'] ?? 'direct' );
        $mode = sanitize_key( $mode );
        if ( ! in_array( $mode, [ 'direct', 'queue' ], true ) ) {
            $mode = 'direct';
        }

        $token = defined( 'IW_ZEBRA_PRINT_AGENT_TOKEN' )
            ? (string) IW_ZEBRA_PRINT_AGENT_TOKEN
            : (string) ( $stored['agent_token'] ?? '' );

        $websocket_notify_url = defined( 'IW_ZEBRA_PRINT_WS_NOTIFY_URL' )
            ? (string) IW_ZEBRA_PRINT_WS_NOTIFY_URL
            : (string) ( $stored['websocket_notify_url'] ?? '' );

        $websocket_token = defined( 'IW_ZEBRA_PRINT_WS_TOKEN' )
            ? (string) IW_ZEBRA_PRINT_WS_TOKEN
            : (string) ( $stored['websocket_token'] ?? '' );
        if ( trim( $websocket_token ) === '' ) {
            $websocket_token = $token;
        }

        $default_printer_id = defined( 'IW_ZEBRA_DEFAULT_PRINTER_ID' )
            ? (string) IW_ZEBRA_DEFAULT_PRINTER_ID
            : (string) ( $stored['default_printer_id'] ?? self::DEFAULT_PRINTER );

        $raw_targets = [];
        if ( defined( 'IW_ZEBRA_PRINT_TARGETS' ) && is_array( IW_ZEBRA_PRINT_TARGETS ) ) {
            $raw_targets = IW_ZEBRA_PRINT_TARGETS;
        } elseif ( isset( $stored['targets'] ) && is_array( $stored['targets'] ) ) {
            $raw_targets = $stored['targets'];
        }

        $targets = self::normalize_targets( $raw_targets, $default_printer_id );

        $raw_building_printers = [];
        if ( defined( 'IW_ZEBRA_BUILDING_PRINTERS' ) && is_array( IW_ZEBRA_BUILDING_PRINTERS ) ) {
            $raw_building_printers = IW_ZEBRA_BUILDING_PRINTERS;
        } elseif ( isset( $stored['building_printers'] ) && is_array( $stored['building_printers'] ) ) {
            $raw_building_printers = $stored['building_printers'];
        }

        return [
            'mode'               => $mode,
            'agent_token'        => trim( $token ),
            'websocket_notify_url' => esc_url_raw( trim( $websocket_notify_url ) ),
            'websocket_token'    => trim( $websocket_token ),
            'default_printer_id' => self::sanitize_printer_id( $default_printer_id ),
            'targets'            => $targets,
            'building_printers'  => self::normalize_building_printers( $raw_building_printers, $targets ),
        ];
    }

    public static function should_queue(): bool {
        return self::get_settings()['mode'] === 'queue';
    }

    public static function get_public_config(): array {
        $settings = self::get_settings();

        return [
            'mode'             => $settings['mode'],
            'defaultPrinterId' => $settings['default_printer_id'],
            'targets'          => array_values(
                array_map(
                    function( array $target ): array {
                        return [
                            'id'       => $target['id'],
                            'label'    => $target['label'],
                            'subtitle' => $target['subtitle'],
                        ];
                    },
                    $settings['targets']
                )
            ),
        ];
    }

    public static function sanitize_printer_id( string $printer_id ): string {
        $printer_id = sanitize_key( trim( $printer_id ) );
        return $printer_id !== '' ? $printer_id : self::DEFAULT_PRINTER;
    }

    public static function printer_id_from_request( WP_REST_Request $request ): string {
        $printer_id = self::requested_printer_id( $request );
        if ( $printer_id === '' ) {
            $printer_id = self::get_settings()['default_printer_id'];
        }

        return self::sanitize_printer_id( $printer_id );
    }

    public static function resolve_printer_id( WP_REST_Request $request, int $order_id = 0, array $tickets = [] ): string {
        $requested = self::requested_printer_id( $request );
        if ( $requested !== '' ) {
            return self::sanitize_printer_id( $requested );
        }

        $settings    = self::get_settings();
        $building_id = self::resolve_building_id( $order_id, $tickets );
        if ( $building_id > 0 ) {
            $filtered = (string) apply_filters(
                'iw_zebra_print_printer_id_for_building',
                '',
                $building_id,
                $order_id,
                $tickets
            );
            if ( trim( $filtered ) !== '' ) {
                return self::sanitize_printer_id( $filtered );
            }

            $mapped = (string) ( $settings['building_printers'][ $building_id ] ?? '' );
            if ( $mapped !== '' ) {
                return self::sanitize_printer_id( $mapped );
            }
        }

        return self::sanitize_printer_id( $settings['default_printer_id'] );
    }

    public static function create_job( string $zpl, array $args = [] ) {
        $zpl = trim( $zpl );
        if ( $zpl === '' ) {
            return new WP_Error( 'iw_zebra_print_job_empty', __( 'Το print job δεν έχει ZPL.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $order_id    = absint( $args['order_id'] ?? 0 );
        $printer_id  = self::sanitize_printer_id( (string) ( $args['printer_id'] ?? self::get_settings()['default_printer_id'] ) );
        $ticket_uuid = sanitize_text_field( (string) ( $args['ticket_uuid'] ?? '' ) );
        $count       = max( 1, absint( $args['count'] ?? 1 ) );
        $source      = sanitize_key( (string) ( $args['source'] ?? 'cashier' ) );

        $job_id = wp_insert_post(
            [
                'post_type'    => self::POST_TYPE,
                'post_status'  => 'private',
                'post_title'   => $order_id > 0
                    ? sprintf( 'Zebra print order #%d', $order_id )
                    : sprintf( 'Zebra print job %s', wp_date( 'Y-m-d H:i:s' ) ),
                'post_content' => $zpl . "\n",
                'meta_input'   => [
                    '_iw_zebra_print_status'      => self::STATUS_QUEUED,
                    '_iw_zebra_print_printer_id'  => $printer_id,
                    '_iw_zebra_print_order_id'    => $order_id,
                    '_iw_zebra_print_ticket_uuid' => $ticket_uuid,
                    '_iw_zebra_print_count'       => $count,
                    '_iw_zebra_print_source'      => $source,
                    '_iw_zebra_print_attempts'    => 0,
                    '_iw_zebra_print_created_at'  => time(),
                ],
            ],
            true
        );

        if ( is_wp_error( $job_id ) ) {
            return $job_id;
        }

        $payload = self::job_payload( (int) $job_id, false );
        self::notify_websocket_hub( $payload );

        return $payload;
    }

    public static function claim_next_job( string $printer_id, string $agent_id = '' ) {
        self::release_stale_jobs();

        $printer_id = self::sanitize_printer_id( $printer_id );
        $query      = new WP_Query(
            [
                'post_type'      => self::POST_TYPE,
                'post_status'    => 'private',
                'posts_per_page' => 1,
                'orderby'        => 'date',
                'order'          => 'ASC',
                'fields'         => 'ids',
                'meta_query'     => [
                    [
                        'key'   => '_iw_zebra_print_status',
                        'value' => self::STATUS_QUEUED,
                    ],
                    [
                        'key'   => '_iw_zebra_print_printer_id',
                        'value' => $printer_id,
                    ],
                ],
            ]
        );

        $job_id = (int) ( $query->posts[0] ?? 0 );
        if ( $job_id <= 0 ) {
            return null;
        }

        $attempts = absint( get_post_meta( $job_id, '_iw_zebra_print_attempts', true ) );
        update_post_meta( $job_id, '_iw_zebra_print_status', self::STATUS_PROCESSING );
        update_post_meta( $job_id, '_iw_zebra_print_agent_id', sanitize_text_field( $agent_id ) );
        update_post_meta( $job_id, '_iw_zebra_print_claimed_at', time() );
        update_post_meta( $job_id, '_iw_zebra_print_attempts', $attempts + 1 );

        return self::job_payload( $job_id, true );
    }

    public static function complete_job( int $job_id, bool $success, string $message = '', array $result = [] ) {
        $post = get_post( $job_id );
        if ( ! $post || $post->post_type !== self::POST_TYPE ) {
            return new WP_Error( 'iw_zebra_print_job_not_found', __( 'Δεν βρέθηκε print job.', 'iw-theme' ), [ 'status' => 404 ] );
        }

        update_post_meta( $job_id, '_iw_zebra_print_status', $success ? self::STATUS_DONE : self::STATUS_FAILED );
        update_post_meta( $job_id, '_iw_zebra_print_completed_at', time() );
        update_post_meta( $job_id, '_iw_zebra_print_message', sanitize_text_field( $message ) );
        update_post_meta( $job_id, '_iw_zebra_print_result', wp_json_encode( $result ) );

        return self::job_payload( $job_id, false );
    }

    public static function get_job_status( int $job_id ) {
        $post = get_post( $job_id );
        if ( ! $post || $post->post_type !== self::POST_TYPE ) {
            return new WP_Error( 'iw_zebra_print_job_not_found', __( 'Δεν βρέθηκε print job.', 'iw-theme' ), [ 'status' => 404 ] );
        }

        return self::job_payload( $job_id, false );
    }

    public static function can_agent_access( WP_REST_Request $request ): bool {
        $expected = self::get_settings()['agent_token'];
        if ( $expected === '' ) {
            return false;
        }

        $provided = (string) $request->get_header( 'x_iw_print_agent_token' );
        if ( $provided === '' ) {
            $authorization = (string) $request->get_header( 'authorization' );
            if ( stripos( $authorization, 'Bearer ' ) === 0 ) {
                $provided = trim( substr( $authorization, 7 ) );
            }
        }
        if ( $provided === '' ) {
            $provided = (string) $request->get_param( 'token' );
        }

        return $provided !== '' && hash_equals( $expected, $provided );
    }

    protected static function get_acf_settings(): array {
        if ( ! function_exists( 'get_field' ) ) {
            return [];
        }

        $settings = get_field( 'iw_zebra_print_settings', 'option' );
        $settings = is_array( $settings ) ? $settings : [];

        $mode = get_field( 'iw_zebra_print_mode', 'option' );
        if ( is_string( $mode ) && trim( $mode ) !== '' ) {
            $settings['mode'] = $mode;
        }

        $queue_settings = get_field( 'iw_zebra_print_queue_settings', 'option' );
        if ( is_array( $queue_settings ) ) {
            $settings = array_merge( $settings, $queue_settings );
        }

        return $settings;
    }

    protected static function normalize_targets( array $raw_targets, string $default_printer_id ): array {
        $targets = [];

        foreach ( $raw_targets as $key => $target ) {
            if ( is_array( $target ) ) {
                $id       = self::sanitize_printer_id( (string) ( $target['id'] ?? $key ) );
                $label    = sanitize_text_field( (string) ( $target['label'] ?? $id ) );
                $subtitle = sanitize_text_field( (string) ( $target['subtitle'] ?? '' ) );
                $building_ids = self::normalize_building_ids( $target['building_ids'] ?? ( $target['building_id'] ?? [] ) );
            } else {
                $id       = self::sanitize_printer_id( is_string( $key ) ? $key : (string) $target );
                $label    = sanitize_text_field( (string) $target );
                $subtitle = '';
                $building_ids = [];
            }

            if ( $id === '' ) {
                continue;
            }

            $targets[ $id ] = [
                'id'       => $id,
                'label'    => $label !== '' ? $label : $id,
                'subtitle' => $subtitle,
                'building_ids' => $building_ids,
            ];
        }

        $default_id = self::sanitize_printer_id( $default_printer_id );
        if ( empty( $targets[ $default_id ] ) ) {
            $targets[ $default_id ] = [
                'id'       => $default_id,
                'label'    => __( 'Default printer', 'iw-theme' ),
                'subtitle' => '',
                'building_ids' => [],
            ];
        }

        return (array) apply_filters( 'iw_zebra_print_targets', $targets );
    }

    protected static function normalize_building_printers( array $raw_building_printers, array $targets ): array {
        $mapping = [];

        foreach ( $raw_building_printers as $building_id => $printer_id ) {
            $building_id = absint( $building_id );
            if ( $building_id <= 0 ) {
                continue;
            }

            $mapping[ $building_id ] = self::sanitize_printer_id( (string) $printer_id );
        }

        foreach ( $targets as $target ) {
            $printer_id = self::sanitize_printer_id( (string) ( $target['id'] ?? '' ) );
            foreach ( self::normalize_building_ids( $target['building_ids'] ?? [] ) as $building_id ) {
                if ( $building_id > 0 && empty( $mapping[ $building_id ] ) ) {
                    $mapping[ $building_id ] = $printer_id;
                }
            }
        }

        return (array) apply_filters( 'iw_zebra_print_building_printers', $mapping, $targets );
    }

    protected static function requested_printer_id( WP_REST_Request $request ): string {
        $printer_id = trim( (string) $request->get_param( 'printer_id' ) );
        if ( $printer_id === '' ) {
            $printer_id = trim( (string) $request->get_header( 'x_iw_printer_id' ) );
        }

        if ( in_array( strtolower( $printer_id ), [ 'auto', 'building' ], true ) ) {
            return '';
        }

        return $printer_id;
    }

    protected static function resolve_building_id( int $order_id = 0, array $tickets = [] ): int {
        if ( $order_id > 0 && function_exists( 'wc_get_order' ) ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $building_id = absint( $order->get_meta( '_iw_cashier_building_id', true ) );
                if ( $building_id > 0 ) {
                    return $building_id;
                }
            }
        }

        foreach ( $tickets as $ticket ) {
            if ( ! is_array( $ticket ) ) {
                continue;
            }

            $building_id = absint( $ticket['building_id'] ?? 0 );
            if ( $building_id > 0 ) {
                return $building_id;
            }

            $post_id = absint( $ticket['post_id'] ?? 0 );
            if ( $post_id > 0 ) {
                $building_id = self::resolve_post_building_id( $post_id );
                if ( $building_id > 0 ) {
                    return $building_id;
                }
            }
        }

        return 0;
    }

    protected static function resolve_post_building_id( int $post_id ): int {
        if ( get_post_type( $post_id ) === 'building' ) {
            return $post_id;
        }

        foreach ( [ 'building_location', 'building' ] as $field ) {
            $building_id = self::normalize_building_reference_id(
                function_exists( 'get_field' ) ? get_field( $field, $post_id ) : get_post_meta( $post_id, $field, true )
            );

            if ( $building_id > 0 ) {
                return $building_id;
            }
        }

        return 0;
    }

    protected static function normalize_building_reference_id( $value ): int {
        if ( is_object( $value ) && isset( $value->ID ) ) {
            return absint( $value->ID );
        }

        if ( is_numeric( $value ) ) {
            return absint( $value );
        }

        if ( is_array( $value ) ) {
            if ( isset( $value['ID'] ) ) {
                return absint( $value['ID'] );
            }

            foreach ( $value as $item ) {
                $building_id = self::normalize_building_reference_id( $item );
                if ( $building_id > 0 ) {
                    return $building_id;
                }
            }
        }

        return 0;
    }

    protected static function normalize_building_ids( $value ): array {
        if ( is_numeric( $value ) ) {
            return [ absint( $value ) ];
        }

        $ids = [];
        foreach ( (array) $value as $item ) {
            $id = self::normalize_building_reference_id( $item );
            if ( $id > 0 ) {
                $ids[] = $id;
            }
        }

        return array_values( array_unique( $ids ) );
    }

    protected static function job_payload( int $job_id, bool $include_zpl ): array {
        $post = get_post( $job_id );

        return [
            'id'          => $job_id,
            'status'      => (string) get_post_meta( $job_id, '_iw_zebra_print_status', true ),
            'printer_id'  => (string) get_post_meta( $job_id, '_iw_zebra_print_printer_id', true ),
            'order_id'    => absint( get_post_meta( $job_id, '_iw_zebra_print_order_id', true ) ),
            'ticket_uuid' => (string) get_post_meta( $job_id, '_iw_zebra_print_ticket_uuid', true ),
            'count'       => absint( get_post_meta( $job_id, '_iw_zebra_print_count', true ) ),
            'attempts'    => absint( get_post_meta( $job_id, '_iw_zebra_print_attempts', true ) ),
            'created_at'  => absint( get_post_meta( $job_id, '_iw_zebra_print_created_at', true ) ),
            'agent_id'    => (string) get_post_meta( $job_id, '_iw_zebra_print_agent_id', true ),
            'claimed_at'  => absint( get_post_meta( $job_id, '_iw_zebra_print_claimed_at', true ) ),
            'completed_at' => absint( get_post_meta( $job_id, '_iw_zebra_print_completed_at', true ) ),
            'message'     => (string) get_post_meta( $job_id, '_iw_zebra_print_message', true ),
            'zpl'         => $include_zpl && $post ? (string) $post->post_content : '',
        ];
    }

    protected static function notify_websocket_hub( array $job ): void {
        $settings = self::get_settings();
        $url      = (string) ( $settings['websocket_notify_url'] ?? '' );
        $token    = (string) ( $settings['websocket_token'] ?? '' );

        if ( $url === '' || $token === '' ) {
            return;
        }

        $body = [
            'type'       => 'job',
            'job_id'     => absint( $job['id'] ?? 0 ),
            'printer_id' => self::sanitize_printer_id( (string) ( $job['printer_id'] ?? '' ) ),
            'order_id'   => absint( $job['order_id'] ?? 0 ),
            'count'      => max( 1, absint( $job['count'] ?? 1 ) ),
            'created_at' => absint( $job['created_at'] ?? time() ),
        ];

        if ( $body['job_id'] <= 0 || $body['printer_id'] === '' ) {
            return;
        }

        $response = wp_remote_post(
            $url,
            [
                'timeout'  => (float) apply_filters( 'iw_zebra_print_ws_notify_timeout', 0.75 ),
                'blocking' => false,
                'headers'  => [
                    'content-type'          => 'application/json',
                    'x-iw-print-ws-token'  => $token,
                ],
                'body'     => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            do_action( 'iw_zebra_print_ws_notify_failed', $response, $body );
        }
    }

    protected static function release_stale_jobs(): void {
        $stale_before = time() - (int) apply_filters( 'iw_zebra_print_processing_timeout', 180 );
        $query        = new WP_Query(
            [
                'post_type'      => self::POST_TYPE,
                'post_status'    => 'private',
                'posts_per_page' => 20,
                'fields'         => 'ids',
                'meta_query'     => [
                    [
                        'key'   => '_iw_zebra_print_status',
                        'value' => self::STATUS_PROCESSING,
                    ],
                    [
                        'key'     => '_iw_zebra_print_claimed_at',
                        'value'   => $stale_before,
                        'compare' => '<',
                        'type'    => 'NUMERIC',
                    ],
                ],
            ]
        );

        foreach ( $query->posts as $job_id ) {
            update_post_meta( (int) $job_id, '_iw_zebra_print_status', self::STATUS_QUEUED );
            update_post_meta( (int) $job_id, '_iw_zebra_print_message', 'Released stale processing job.' );
        }
    }
}
