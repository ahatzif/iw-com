<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IW_Cashier_Ticketing {
    const REST_NS   = 'iw/v1';
    const REST_BASE = 'cashier';
    const CHANNEL   = 'cashier';
    const MEMBER_TICKET_TYPE       = '4306960';
    const MEMBER_TICKET_SLOT_LIMIT = 2;

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
        add_action( 'template_redirect', [ __CLASS__, 'maybe_render_public_order' ], 0 );
    }

    public static function register_rest_routes(): void {
        register_rest_route(
            self::REST_NS,
            '/' . self::REST_BASE . '/config',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ __CLASS__, 'handle_config' ],
                    'permission_callback' => [ __CLASS__, 'can_use_cashier' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NS,
            '/' . self::REST_BASE . '/tickets',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ __CLASS__, 'handle_ticket_search' ],
                    'permission_callback' => [ __CLASS__, 'can_use_cashier' ],
                    'args'                => [
                        'search' => [ 'required' => false, 'type' => 'string' ],
                        'type'   => [ 'required' => false ],
                        'limit'  => [ 'required' => false, 'type' => 'integer' ],
                        'building_id' => [ 'required' => false, 'type' => 'integer' ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NS,
            '/' . self::REST_BASE . '/tickets/(?P<ticket_id>\d+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ __CLASS__, 'handle_ticket_detail' ],
                    'permission_callback' => [ __CLASS__, 'can_use_cashier' ],
                    'args'                => [
                        'ticket_id' => [ 'required' => true, 'type' => 'integer' ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NS,
            '/' . self::REST_BASE . '/issue',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ __CLASS__, 'handle_issue' ],
                    'permission_callback' => [ __CLASS__, 'can_use_cashier' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NS,
            '/' . self::REST_BASE . '/orders/(?P<order_id>\d+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ __CLASS__, 'handle_order' ],
                    'permission_callback' => [ __CLASS__, 'can_use_cashier' ],
                    'args'                => [
                        'order_id' => [ 'required' => true, 'type' => 'integer' ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NS,
            '/' . self::REST_BASE . '/zebra/test-print',
            [
                [
                    'methods'             => [ WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ],
                    'callback'            => [ __CLASS__, 'handle_zebra_test_print' ],
                    'permission_callback' => [ __CLASS__, 'can_use_cashier' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NS,
            '/' . self::REST_BASE . '/orders/(?P<order_id>\d+)/zebra-print',
            [
                [
                    'methods'             => [ WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ],
                    'callback'            => [ __CLASS__, 'handle_order_zebra_print' ],
                    'permission_callback' => [ __CLASS__, 'can_use_cashier' ],
                    'args'                => [
                        'order_id'    => [ 'required' => true, 'type' => 'integer' ],
                        'ticket_uuid' => [ 'required' => false, 'type' => 'string' ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NS,
            '/' . self::REST_BASE . '/zebra/jobs/(?P<job_id>\d+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ __CLASS__, 'handle_zebra_print_job_status' ],
                    'permission_callback' => [ __CLASS__, 'can_use_cashier' ],
                    'args'                => [
                        'job_id' => [ 'required' => true, 'type' => 'integer' ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NS,
            '/' . self::REST_BASE . '/zebra/agent/jobs/next',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ __CLASS__, 'handle_zebra_agent_next_job' ],
                    'permission_callback' => [ 'IW_Zebra_Print_Queue', 'can_agent_access' ],
                    'args'                => [
                        'printer_id' => [ 'required' => false, 'type' => 'string' ],
                        'agent_id'   => [ 'required' => false, 'type' => 'string' ],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NS,
            '/' . self::REST_BASE . '/zebra/agent/jobs/(?P<job_id>\d+)/complete',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ __CLASS__, 'handle_zebra_agent_complete_job' ],
                    'permission_callback' => [ 'IW_Zebra_Print_Queue', 'can_agent_access' ],
                    'args'                => [
                        'job_id' => [ 'required' => true, 'type' => 'integer' ],
                    ],
                ],
            ]
        );
    }

    public static function handle_zebra_print_job_status( WP_REST_Request $request ) {
        if ( ! class_exists( 'IW_Zebra_Print_Queue' ) ) {
            return new WP_Error( 'iw_cashier_zebra_queue_missing', __( 'Η ουρά εκτύπωσης δεν είναι διαθέσιμη.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $job_id = absint( $request->get_param( 'job_id' ) );
        if ( $job_id <= 0 ) {
            return new WP_Error( 'iw_cashier_zebra_job_required', __( 'Λείπει η εργασία εκτύπωσης.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $job = IW_Zebra_Print_Queue::get_job_status( $job_id );
        if ( is_wp_error( $job ) ) {
            return $job;
        }

        return rest_ensure_response(
            [
                'success' => true,
                'job'     => $job,
            ]
        );
    }

    public static function can_use_cashier(): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        if ( function_exists( 'iw_scanner_user_can_scan' ) ) {
            return (bool) apply_filters(
                'iw_cashier_user_can_issue',
                iw_scanner_user_can_scan(),
                get_current_user_id()
            );
        }

        $scanner_cap = defined( 'IW_SCANNER_CAP' ) ? (string) IW_SCANNER_CAP : 'iw_use_scanner';
        $can = current_user_can( 'manage_options' ) || current_user_can( $scanner_cap );

        return (bool) apply_filters( 'iw_cashier_user_can_issue', $can, get_current_user_id() );
    }

    protected static function get_current_operator_label(): string {
        $user = wp_get_current_user();
        if ( ! $user || ! $user->exists() ) {
            return '';
        }

        $name = trim( (string) $user->display_name );
        if ( $name === '' ) {
            $name = trim( (string) $user->user_login );
        }

        $email = trim( (string) $user->user_email );
        if ( $name !== '' && $email !== '' ) {
            return sprintf( '%s (%s)', $name, $email );
        }

        return $name !== '' ? $name : $email;
    }

    public static function handle_config( WP_REST_Request $request ) {
        return rest_ensure_response(
            [
                'channel'              => self::CHANNEL,
                'rest_namespace'       => self::REST_NS,
                'rest_base'            => self::REST_BASE,
                'supported_post_types' => class_exists( 'IW_Ticketing' ) ? IW_Ticketing::get_supported_post_types() : [],
                'ticket_filters'        => array_values( self::get_ticket_filters() ),
                'payment_methods'      => array_values( self::get_payment_methods() ),
                'zebra_print'          => class_exists( 'IW_Zebra_Print_Queue' ) ? IW_Zebra_Print_Queue::get_public_config() : null,
                'current_user'         => [
                    'id'           => get_current_user_id(),
                    'display_name' => wp_get_current_user()->display_name,
                ],
            ]
        );
    }

    public static function handle_ticket_search( WP_REST_Request $request ) {
        if ( ! class_exists( 'IW_Ticketing' ) ) {
            return new WP_Error( 'iw_cashier_ticketing_missing', __( 'Το ticketing δεν είναι διαθέσιμο.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $search = sanitize_text_field( (string) $request->get_param( 'search' ) );
        $limit  = (int) $request->get_param( 'limit' );
        $max_limit = max( 50, (int) apply_filters( 'iw_cashier_ticket_search_limit_max', 200 ) );
        if ( $limit < 1 ) {
            $limit = 20;
        } elseif ( $limit > $max_limit ) {
            $limit = $max_limit;
        }

        $building_id = absint( $request->get_param( 'building_id' ) );

        $items = self::get_cashier_ticket_entries(
            $search,
            $limit,
            self::normalize_ticket_type_filter( $request->get_param( 'type' ) ),
            $building_id
        );

        return rest_ensure_response( [ 'items' => $items ] );
    }

    public static function handle_ticket_detail( WP_REST_Request $request ) {
        $ticket_id = absint( $request->get_param( 'ticket_id' ) );
        if ( $ticket_id <= 0 || ! class_exists( 'IW_Ticketing' ) ) {
            return new WP_Error( 'iw_cashier_invalid_ticket', __( 'Δεν βρέθηκε εισιτήριο.', 'iw-theme' ), [ 'status' => 404 ] );
        }

        $resolved_ticket_id = self::resolve_cashier_ticket_post_id( $ticket_id );
        if ( is_wp_error( $resolved_ticket_id ) ) {
            return $resolved_ticket_id;
        }

        $tickets_data = IW_Ticketing::get_tickets_data( $resolved_ticket_id );
        if ( ! $tickets_data ) {
            return new WP_Error( 'iw_cashier_ticket_not_sellable', __( 'Το εισιτήριο δεν είναι διαθέσιμο για πώληση.', 'iw-theme' ), [ 'status' => 404 ] );
        }

        $list_item = self::normalize_cashier_ticket_entry( $ticket_id );

        return rest_ensure_response(
            [
                'id'              => $resolved_ticket_id,
                'source_id'       => $ticket_id,
                'title'           => self::cashier_title( get_the_title( $resolved_ticket_id ) ),
                'source_title'    => self::cashier_title( get_the_title( $ticket_id ) ),
                'post_type'       => get_post_type( $resolved_ticket_id ),
                'source_post_type' => get_post_type( $ticket_id ),
                'list_item'       => $list_item,
                'ticket_data'     => self::normalize_for_response( $tickets_data ),
                'payment_methods' => array_values( self::get_payment_methods() ),
            ]
        );
    }

    public static function handle_issue( WP_REST_Request $request ) {
        $method = sanitize_key( (string) $request->get_param( 'payment_method' ) );
        if ( $method === '' ) {
            return new WP_Error( 'iw_cashier_payment_method_required', __( 'Επιλέξτε τρόπο πληρωμής.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $methods = self::get_payment_methods();
        if ( empty( $methods[ $method ] ) ) {
            return new WP_Error( 'iw_cashier_payment_method_invalid', __( 'Μη έγκυρος τρόπος πληρωμής.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        if ( empty( $methods[ $method ]['enabled'] ) ) {
            return new WP_Error( 'iw_cashier_payment_method_disabled', __( 'Ο τρόπος πληρωμής δεν είναι ενεργός.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $ticket_id = absint( $request->get_param( 'ticket_id' ) ?: $request->get_param( 'tickets_for_id' ) );
        $day       = sanitize_text_field( (string) $request->get_param( 'day' ) );
        $time      = sanitize_text_field( (string) $request->get_param( 'time' ) );
        $visitors  = $request->get_param( 'visitors' );

        $resolved_ticket_id = self::resolve_cashier_ticket_post_id( $ticket_id );
        if ( is_wp_error( $resolved_ticket_id ) ) {
            return $resolved_ticket_id;
        }

        $prepared = self::prepare_ticket_purchase( (int) $resolved_ticket_id, $day, $time, $visitors );
        if ( is_wp_error( $prepared ) ) {
            return $prepared;
        }

        $order = self::create_cashier_order( $prepared, $method, $methods[ $method ], $request );
        if ( is_wp_error( $order ) ) {
            return $order;
        }

        $payment_result = self::process_payment( $order, $prepared, $method, $methods[ $method ], $request );
        if ( is_wp_error( $payment_result ) ) {
            $order->update_status( 'failed', $payment_result->get_error_message() );
            return $payment_result;
        }

        $payment_status = isset( $payment_result['status'] ) ? sanitize_key( (string) $payment_result['status'] ) : 'approved';
        if ( $payment_status !== 'approved' ) {
            $order->update_status( 'on-hold', __( 'Η πληρωμή POS είναι σε αναμονή επιβεβαίωσης.', 'iw-theme' ) );
            $order->update_meta_data( '_iw_cashier_payment_result', wp_json_encode( $payment_result ) );
            $order->save();

            $response = rest_ensure_response(
                [
                    'status'     => 'pending_payment',
                    'order_id'   => (int) $order->get_id(),
                    'message'    => __( 'Η πληρωμή είναι σε αναμονή. Τα εισιτήρια θα εκδοθούν μετά την έγκριση.', 'iw-theme' ),
                    'payment'    => $payment_result,
                ]
            );
            $response->set_status( 202 );
            return $response;
        }

        $issued = self::issue_order_tickets( $order, $prepared, $payment_result );
        if ( is_wp_error( $issued ) ) {
            $confirm = self::confirm_nexi_pos_payment( $payment_result, 'CASHIER_ISSUE_FAILED', $issued->get_error_message() );
            if ( is_wp_error( $confirm ) ) {
                $order->update_meta_data( '_iw_cashier_pos_confirm_failed_response', $confirm->get_error_message() );
                $order->add_order_note( sprintf( __( 'Nexi POS confirm after failed issue did not complete: %s', 'iw-theme' ), $confirm->get_error_message() ) );
            } elseif ( $confirm !== true ) {
                $order->update_meta_data( '_iw_cashier_pos_confirm_failed_response', wp_json_encode( $confirm ) );
            }
            $order->update_status( 'failed', $issued->get_error_message() );
            $order->save();
            return $issued;
        }

        if ( ! empty( $payment_result['transaction_id'] ) ) {
            $order->set_transaction_id( (string) $payment_result['transaction_id'] );
        }

        $confirm = self::confirm_nexi_pos_payment( $payment_result, 'SUCCESS' );
        if ( is_wp_error( $confirm ) ) {
            $payment_result['confirm_pending'] = true;
            $payment_result['confirm_error']   = $confirm->get_error_message();
            $order->update_meta_data( '_iw_cashier_pos_confirm_pending', 1 );
            $order->update_meta_data( '_iw_cashier_pos_confirm_error', $confirm->get_error_message() );
            $order->add_order_note( sprintf( __( 'Nexi POS confirm is pending: %s', 'iw-theme' ), $confirm->get_error_message() ) );
        } elseif ( $confirm !== true ) {
            $payment_result['confirm_response'] = $confirm;
            $order->update_meta_data( '_iw_cashier_pos_confirm_pending', 0 );
            $order->update_meta_data( '_iw_cashier_pos_confirm_response', wp_json_encode( $confirm ) );
        }

        $order->update_meta_data( '_iw_cashier_payment_result', wp_json_encode( $payment_result ) );
        $order->payment_complete( ! empty( $payment_result['transaction_id'] ) ? (string) $payment_result['transaction_id'] : '' );
        $operator_label = self::get_current_operator_label();
        $order->add_order_note(
            $operator_label !== ''
                ? sprintf( __( 'Τα εισιτήρια εκδόθηκαν από το cashier. Χειριστής: %s.', 'iw-theme' ), wp_strip_all_tags( $operator_label ) )
                : __( 'Τα εισιτήρια εκδόθηκαν από το cashier.', 'iw-theme' ),
            false,
            true
        );
        $order->save();

        if ( class_exists( 'IW_Ticket_PDF_Service' ) && method_exists( 'IW_Ticket_PDF_Service', 'send_tickets_email_for_order' ) ) {
            IW_Ticket_PDF_Service::send_tickets_email_for_order( (int) $order->get_id() );
        }
        self::cashier_subscribe_newsletter_for_order( $order );
        self::cashier_register_members_for_order( $order );
        self::cashier_send_ticket_sms_for_order( $order );

        return rest_ensure_response(
            [
                'status'        => 'issued',
                'order_id'      => (int) $order->get_id(),
                'order_item_id' => (int) $issued['order_item_id'],
                'created_at'    => self::cashier_order_created_at( $order ),
                'ticket_id'     => (int) $prepared['ticket_id'],
                'tickets_count' => count( $issued['tickets'] ),
                'total_price'   => (float) $prepared['total_price'],
                'currency'      => $order->get_currency(),
                'payment'       => $payment_result,
                'tickets'       => $issued['tickets'],
            ] + self::cashier_order_delivery_context( $order ) + self::cashier_ticket_event_context( (int) $prepared['ticket_id'], $order )
        );
    }

    protected static function cashier_order_created_at( $order ): string {
        if ( ! $order || ! method_exists( $order, 'get_date_created' ) ) {
            return '';
        }

        $date = $order->get_date_created();
        return $date && method_exists( $date, 'date' ) ? (string) $date->date( 'Y-m-d\TH:i:s' ) : '';
    }

    protected static function cashier_building_address( int $building_id ): string {
        if ( $building_id <= 0 ) {
            return '';
        }

        $address = function_exists( 'get_field' ) ? get_field( 'address', $building_id ) : get_post_meta( $building_id, 'address', true );

        if ( is_array( $address ) ) {
            return self::plain_text(
                (string) (
                    $address['title']
                    ?? $address['address']
                    ?? $address['formatted_address']
                    ?? ''
                )
            );
        }

        return is_string( $address ) ? self::plain_text( $address ) : '';
    }

    protected static function cashier_ticket_event_context( int $ticket_id, $order = null ): array {
        $entry = $ticket_id > 0 ? self::normalize_cashier_ticket_entry( $ticket_id ) : null;
        $entry = is_array( $entry ) ? $entry : [];

        $building_id    = 0;
        $building_title = '';

        if ( $order && method_exists( $order, 'get_meta' ) ) {
            $building_id    = absint( $order->get_meta( '_iw_cashier_building_id', true ) );
            $building_title = self::plain_text( $order->get_meta( '_iw_cashier_building_name', true ) );
        }

        if ( $building_id <= 0 && ! empty( $entry['building_id'] ) ) {
            $building_id = absint( $entry['building_id'] );
        }

        if ( $building_title === '' && ! empty( $entry['building_title'] ) ) {
            $building_title = self::plain_text( $entry['building_title'] );
        }

        if ( $building_title === '' && $building_id > 0 ) {
            $building_title = self::plain_text( get_the_title( $building_id ) );
        }

        $image = isset( $entry['image'] ) && is_array( $entry['image'] )
            ? $entry['image']
            : [
                'thumb' => '',
                'url'   => '',
                'alt'   => '',
            ];

        return [
            'source_id'        => absint( $entry['source_id'] ?? 0 ),
            'thumb'            => (string) ( $entry['thumb'] ?? ( $image['thumb'] ?? '' ) ),
            'image'            => $image,
            'building_id'      => $building_id,
            'building_title'   => $building_title,
            'building_address' => self::cashier_building_address( $building_id ),
            'location'         => $building_title,
        ];
    }

    public static function handle_order( WP_REST_Request $request ) {
        $order_id = absint( $request->get_param( 'order_id' ) );
        $order    = $order_id > 0 && function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : false;

        if ( ! $order ) {
            return new WP_Error( 'iw_cashier_order_not_found', __( 'Δεν βρέθηκε η παραγγελία.', 'iw-theme' ), [ 'status' => 404 ] );
        }

        $tickets = self::get_order_tickets( $order_id );
        if ( ! $tickets && (string) $order->get_meta( '_iw_cashier_channel', true ) !== self::CHANNEL ) {
            return new WP_Error( 'iw_cashier_order_not_available', __( 'Η παραγγελία δεν είναι διαθέσιμη στο cashier.', 'iw-theme' ), [ 'status' => 404 ] );
        }

        $title         = '';
        $order_item_id = 0;
        $ticket_id     = 0;

        foreach ( $order->get_items( 'line_item' ) as $item ) {
            if ( (string) $item->get_meta( 'iw_item_type', true ) !== 'tickets' && ! (int) $item->get_meta( 'tickets_for_id', true ) ) {
                continue;
            }

            $title         = self::cashier_title( (string) ( $item->get_meta( 'iw_title', true ) ?: $item->get_name() ) );
            $order_item_id = (int) $item->get_id();
            $ticket_id     = (int) $item->get_meta( 'tickets_for_id', true );
            break;
        }

        if ( ! $order_item_id && ! empty( $tickets[0]['order_item_id'] ) ) {
            $order_item_id = (int) $tickets[0]['order_item_id'];
        }

        if ( ! $ticket_id && ! empty( $tickets[0]['post_id'] ) ) {
            $ticket_id = (int) $tickets[0]['post_id'];
        }

        $payment_result = [
            'status'         => $order->is_paid() ? 'approved' : $order->get_status(),
            'provider'       => (string) ( $order->get_meta( '_iw_cashier_payment_method', true ) ?: $order->get_payment_method() ),
            'transaction_id' => (string) $order->get_transaction_id(),
        ];

        $terminal_id = (string) $order->get_meta( '_iw_cashier_terminal_id', true );
        if ( $terminal_id !== '' ) {
            $payment_result['terminal_id'] = $terminal_id;
        }

        return rest_ensure_response(
            [
                'status'        => 'issued',
                'order_id'      => (int) $order->get_id(),
                'order_item_id' => $order_item_id,
                'created_at'    => self::cashier_order_created_at( $order ),
                'ticket_id'     => $ticket_id,
                'title'         => $title,
                'tickets_count' => count( $tickets ),
                'total_price'   => (float) $order->get_total(),
                'currency'      => $order->get_currency(),
                'payment'       => $payment_result,
                'tickets'       => $tickets,
            ] + self::cashier_order_delivery_context( $order ) + self::cashier_ticket_event_context( $ticket_id, $order )
        );
    }

    public static function maybe_render_public_order(): void {
        if ( empty( $_GET['ticket_order'] ) ) {
            return;
        }

        $order_id = absint( wp_unslash( $_GET['ticket_order'] ) );
        $exp      = isset( $_GET['e'] ) ? absint( wp_unslash( $_GET['e'] ) ) : 0;
        $sig      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

        if ( $order_id <= 0 || $exp <= 0 || $sig === '' || ! self::cashier_validate_public_order_link( $order_id, $exp, $sig ) ) {
            status_header( 403 );
            nocache_headers();
            echo esc_html__( 'Ο σύνδεσμος δεν είναι έγκυρος ή έχει λήξει.', 'iw-theme' );
            exit;
        }

        $order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : false;
        if ( ! $order ) {
            status_header( 404 );
            nocache_headers();
            echo esc_html__( 'Η παραγγελία δεν βρέθηκε.', 'iw-theme' );
            exit;
        }

        $tickets = self::get_order_tickets( $order_id );
        if ( empty( $tickets ) ) {
            status_header( 404 );
            nocache_headers();
            echo esc_html__( 'Δεν βρέθηκαν εισιτήρια για αυτή την παραγγελία.', 'iw-theme' );
            exit;
        }

        nocache_headers();
        header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
        echo self::cashier_render_public_order_html( $order, $tickets );
        exit;
    }

    protected static function cashier_public_order_expiry(): int {
        return time() + ( 365 * DAY_IN_SECONDS );
    }

    protected static function cashier_public_order_signature( int $order_id, int $exp ): string {
        if ( $order_id <= 0 || $exp <= 0 ) {
            return '';
        }

        return hash_hmac( 'sha256', $order_id . '|' . $exp, wp_salt( 'iw_cashier_public_order' ) );
    }

    protected static function cashier_validate_public_order_link( int $order_id, int $exp, string $sig ): bool {
        if ( $order_id <= 0 || $exp <= 0 || $sig === '' || time() > $exp ) {
            return false;
        }

        $expected = self::cashier_public_order_signature( $order_id, $exp );
        return $expected !== '' && hash_equals( $expected, $sig );
    }

    protected static function cashier_public_order_url( $order, int $exp = 0 ): string {
        if ( ! $order || ! method_exists( $order, 'get_id' ) ) {
            return '';
        }

        $order_id = (int) $order->get_id();
        $exp      = $exp > 0 ? $exp : self::cashier_public_order_expiry();
        $sig      = self::cashier_public_order_signature( $order_id, $exp );
        if ( $sig === '' ) {
            return '';
        }

        return add_query_arg(
            [
                'ticket_order' => $order_id,
                'e'            => $exp,
                's'            => $sig,
            ],
            home_url( '/' )
        );
    }

    protected static function cashier_order_ticket_item_context( $order, array $tickets = [] ): array {
        $title         = '';
        $order_item_id = 0;
        $ticket_id     = 0;

        if ( $order && method_exists( $order, 'get_items' ) ) {
            foreach ( $order->get_items( 'line_item' ) as $item ) {
                if ( (string) $item->get_meta( 'iw_item_type', true ) !== 'tickets' && ! (int) $item->get_meta( 'tickets_for_id', true ) ) {
                    continue;
                }

                $title         = self::cashier_title( (string) ( $item->get_meta( 'iw_title', true ) ?: $item->get_name() ) );
                $order_item_id = (int) $item->get_id();
                $ticket_id     = (int) $item->get_meta( 'tickets_for_id', true );
                break;
            }
        }

        if ( ! $order_item_id && ! empty( $tickets[0]['order_item_id'] ) ) {
            $order_item_id = (int) $tickets[0]['order_item_id'];
        }

        if ( ! $ticket_id && ! empty( $tickets[0]['post_id'] ) ) {
            $ticket_id = (int) $tickets[0]['post_id'];
        }

        return [
            'title'         => $title,
            'order_item_id' => $order_item_id,
            'ticket_id'     => $ticket_id,
        ];
    }

    protected static function cashier_public_order_date_label( string $slot_start ): string {
        if ( $slot_start === '' ) {
            return '';
        }

        try {
            $date = new DateTimeImmutable( $slot_start, wp_timezone() );
            return wp_date( 'd/m/Y', $date->getTimestamp(), wp_timezone() );
        } catch ( Exception $e ) {
            return '';
        }
    }

    protected static function cashier_public_order_time_label( string $slot_start, string $slot_end ): string {
        if ( $slot_start === '' ) {
            return '';
        }

        try {
            $start = new DateTimeImmutable( $slot_start, wp_timezone() );
            $label = wp_date( 'H:i', $start->getTimestamp(), wp_timezone() );
            if ( $slot_end !== '' ) {
                $end = new DateTimeImmutable( $slot_end, wp_timezone() );
                $label .= ' - ' . wp_date( 'H:i', $end->getTimestamp(), wp_timezone() );
            }
            return $label;
        } catch ( Exception $e ) {
            return '';
        }
    }

    protected static function cashier_public_ticket_category_label( string $price_category ): string {
        $parts = explode( ':', $price_category );
        $label = isset( $parts[1] ) && $parts[1] !== '' ? $parts[1] : $price_category;
        return self::plain_text( $label );
    }

    protected static function cashier_render_public_order_html( $order, array $tickets ): string {
        $context      = self::cashier_order_ticket_item_context( $order, $tickets );
        $event        = self::cashier_ticket_event_context( (int) $context['ticket_id'], $order );
        $title        = (string) ( $context['title'] ?: get_bloginfo( 'name' ) );
        $slot_start   = (string) ( $tickets[0]['slot_start'] ?? '' );
        $slot_end     = (string) ( $tickets[0]['slot_end'] ?? '' );
        $date_label   = self::cashier_public_order_date_label( $slot_start );
        $time_label   = self::cashier_public_order_time_label( $slot_start, $slot_end );
        $location     = self::plain_text( (string) ( $event['building_title'] ?? $event['location'] ?? '' ) );
        $address      = self::plain_text( (string) ( $event['building_address'] ?? '' ) );
        $image        = is_array( $event['image'] ?? null ) ? $event['image'] : [];
        $thumb        = (string) ( $event['thumb'] ?? $image['thumb'] ?? $image['url'] ?? '' );
        $order_number = method_exists( $order, 'get_order_number' ) ? (string) $order->get_order_number() : (string) $order->get_id();

        $meta_parts = array_filter( [ $date_label, $time_label, $location, $address ] );
        $ticket_rows = '';
        foreach ( $tickets as $index => $ticket ) {
            $attendee = self::plain_text( (string) ( $ticket['attendee_name'] ?? '' ) );
            if ( $attendee === '' ) {
                $attendee = sprintf( __( 'Εισιτήριο %d', 'iw-theme' ), $index + 1 );
            }

            $category = self::cashier_public_ticket_category_label( (string) ( $ticket['price_category'] ?? '' ) );
            $pdf_url  = (string) ( $ticket['pdf_url'] ?? '' );

            $ticket_rows .= '<article class="ticket-card">';
            $ticket_rows .= '<div class="ticket-index">' . esc_html( (string) ( $index + 1 ) ) . '</div>';
            $ticket_rows .= '<div class="ticket-copy">';
            $ticket_rows .= '<strong>' . esc_html( $category !== '' ? $category : __( 'Εισιτήριο', 'iw-theme' ) ) . '</strong>';
            $ticket_rows .= '<span>' . esc_html( $attendee ) . '</span>';
            $ticket_rows .= '</div>';
            if ( $pdf_url !== '' ) {
                $ticket_rows .= '<a class="ticket-action" href="' . esc_url( $pdf_url ) . '">' . esc_html__( 'PDF', 'iw-theme' ) . '</a>';
            }
            $ticket_rows .= '</article>';
        }

        $hero_image = $thumb !== ''
            ? '<img class="event-image" src="' . esc_url( $thumb ) . '" alt="">'
            : '<div class="event-image event-image--empty" aria-hidden="true"></div>';

        return '<!doctype html>
<html ' . get_language_attributes() . '>
<head>
<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . esc_html( $title ) . '</title>
<style>
:root{color-scheme:light;--brand:#d87155;--ink:#31312f;--muted:#8e8e8b;--panel:#f6f6f5;--line:#e8e3dc}
*{box-sizing:border-box}body{margin:0;background:#eef0f4;color:var(--ink);font-family:Inter,Arial,sans-serif}
.page{min-height:100vh;padding:28px 16px}.shell{width:min(760px,100%);margin:0 auto;background:#fff;border-radius:28px;padding:24px;box-shadow:0 24px 70px rgba(38,38,38,.12)}
.header{display:flex;gap:18px;align-items:center;border-bottom:1px dashed var(--line);padding-bottom:20px}.event-image{width:82px;height:82px;flex:0 0 82px;border-radius:18px;object-fit:cover;background:var(--panel)}
.event-image--empty{display:block}.kicker{margin:0 0 6px;color:var(--muted);font-size:13px}.title{margin:0;font-size:24px;line-height:1.05;font-weight:800}.meta{margin:8px 0 0;color:var(--muted);font-size:14px;line-height:1.35}
.tickets{display:grid;gap:12px;margin-top:22px}.ticket-card{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:14px;border:1px dashed var(--line);border-radius:18px;padding:15px}
.ticket-index{display:grid;width:32px;height:32px;place-items:center;border-radius:999px;background:var(--panel);color:var(--muted);font-size:12px}.ticket-copy{display:grid;gap:5px;min-width:0}.ticket-copy strong{font-size:14px;line-height:1.15;text-transform:uppercase}.ticket-copy span{color:var(--muted);font-size:14px;line-height:1.2;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ticket-action{display:inline-flex;min-height:38px;align-items:center;border-radius:999px;background:var(--panel);color:var(--ink);font-size:13px;font-weight:700;text-decoration:none;padding:0 16px}.footer{margin-top:20px;color:var(--muted);font-size:12px;text-align:center}
@media(max-width:560px){.shell{border-radius:22px;padding:18px}.header{align-items:flex-start}.event-image{width:64px;height:64px;flex-basis:64px;border-radius:14px}.title{font-size:20px}.ticket-card{grid-template-columns:auto minmax(0,1fr)}.ticket-action{grid-column:2;justify-self:start}}
</style>
</head>
<body>
<main class="page">
<section class="shell">
<header class="header">' . $hero_image . '<div><p class="kicker">' . esc_html( sprintf( __( 'Παραγγελία #%s', 'iw-theme' ), $order_number ) ) . '</p><h1 class="title">' . esc_html( $title ) . '</h1><p class="meta">' . esc_html( implode( ' · ', $meta_parts ) ) . '</p></div></header>
<section class="tickets">' . $ticket_rows . '</section>
<p class="footer">' . esc_html__( 'Παρακαλώ έχετε διαθέσιμο το εισιτήριο κατά την είσοδο.', 'iw-theme' ) . '</p>
</section>
</main>
</body>
</html>';
    }

    public static function handle_zebra_test_print( WP_REST_Request $request ) {
        if ( ! class_exists( 'IW_Zebra_Ticket_Printer' ) ) {
            return new WP_Error( 'iw_cashier_zebra_missing', __( 'Ο Zebra printer δεν είναι διαθέσιμος.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $printer = new IW_Zebra_Ticket_Printer();
        $ticket  = IW_Zebra_Ticket_Printer::default_test_ticket();
        $payload = $request->get_json_params();
        if ( ! is_array( $payload ) ) {
            $payload = [];
        }

        foreach ( [ 'ticket_type', 'category', 'exhibition', 'attendee', 'price', 'visit', 'issued_at', 'code', 'qr_value' ] as $field ) {
            $value = $request->get_param( $field );
            if ( $value === null && array_key_exists( $field, $payload ) ) {
                $value = $payload[ $field ];
            }

            if ( $value !== null ) {
                $ticket[ $field ] = sanitize_text_field( wp_unslash( (string) $value ) );
            }
        }

        $debug_zpl = self::is_zebra_debug_request( $request );
        $zpl       = $printer->renderZpl( $ticket );

        if ( $debug_zpl ) {
            return rest_ensure_response(
                [
                    'success' => true,
                    'debug'   => true,
                    'printed' => false,
                    'zpl'     => $zpl,
                ]
            );
        }

        if ( class_exists( 'IW_Zebra_Print_Queue' ) && IW_Zebra_Print_Queue::should_queue() ) {
            $printer_id = IW_Zebra_Print_Queue::resolve_printer_id( $request );
            $job        = IW_Zebra_Print_Queue::create_job(
                $zpl,
                [
                    'printer_id' => $printer_id,
                    'count'      => 1,
                    'source'     => 'test',
                ]
            );

            if ( is_wp_error( $job ) ) {
                return $job;
            }

            return rest_ensure_response(
                [
                    'success'    => true,
                    'queued'     => true,
                    'printed'    => false,
                    'count'      => 1,
                    'printer_id' => $printer_id,
                    'job'        => $job,
                ]
            );
        }

        try {
            $result = $printer->printTicket( $ticket );
        } catch ( Throwable $e ) {
            return new WP_Error( 'iw_cashier_zebra_print_failed', $e->getMessage(), [ 'status' => 502 ] );
        }

        return rest_ensure_response(
            [
                'success' => true,
                'printed' => true,
                'result'  => $result,
            ]
        );
    }

    public static function handle_order_zebra_print( WP_REST_Request $request ) {
        if ( ! class_exists( 'IW_Zebra_Ticket_Printer' ) ) {
            return new WP_Error( 'iw_cashier_zebra_missing', __( 'Ο Zebra printer δεν είναι διαθέσιμος.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $order_id = absint( $request->get_param( 'order_id' ) );
        if ( $order_id <= 0 ) {
            return new WP_Error( 'iw_cashier_order_required', __( 'Λείπει η παραγγελία.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $ticket_uuid = sanitize_text_field( wp_unslash( (string) $request->get_param( 'ticket_uuid' ) ) );
        $tickets     = IW_Zebra_Ticket_Printer::get_order_tickets( $order_id, $ticket_uuid );

        if ( empty( $tickets ) ) {
            return new WP_Error( 'iw_cashier_zebra_no_tickets', __( 'Δεν βρέθηκαν εισιτήρια για εκτύπωση.', 'iw-theme' ), [ 'status' => 404 ] );
        }

        $printer   = new IW_Zebra_Ticket_Printer();
        $debug_zpl = self::is_zebra_debug_request( $request );
        $zpl_parts = array_map( [ $printer, 'renderZpl' ], $tickets );

        if ( $debug_zpl ) {
            return rest_ensure_response(
                [
                    'success' => true,
                    'debug'   => true,
                    'printed' => false,
                    'count'   => count( $tickets ),
                    'zpl'     => implode( "\n", $zpl_parts ),
                ]
            );
        }

        if ( class_exists( 'IW_Zebra_Print_Queue' ) && IW_Zebra_Print_Queue::should_queue() ) {
            $printer_id = IW_Zebra_Print_Queue::resolve_printer_id( $request, $order_id, $tickets );
            $job        = IW_Zebra_Print_Queue::create_job(
                implode( "\n", $zpl_parts ),
                [
                    'order_id'    => $order_id,
                    'ticket_uuid' => $ticket_uuid,
                    'count'       => count( $tickets ),
                    'printer_id'  => $printer_id,
                    'source'      => 'cashier',
                ]
            );

            if ( is_wp_error( $job ) ) {
                return $job;
            }

            return rest_ensure_response(
                [
                    'success'    => true,
                    'queued'     => true,
                    'printed'    => 0,
                    'count'      => count( $tickets ),
                    'printer_id' => $printer_id,
                    'job'        => $job,
                ]
            );
        }

        $printed = 0;
        try {
            foreach ( $tickets as $ticket ) {
                $printer->printTicket( $ticket );
                $printed++;
            }
        } catch ( Throwable $e ) {
            return new WP_Error(
                'iw_cashier_zebra_print_failed',
                $e->getMessage(),
                [
                    'status'  => 502,
                    'printed' => $printed,
                    'count'   => count( $tickets ),
                ]
            );
        }

        return rest_ensure_response(
            [
                'success' => true,
                'printed' => $printed,
                'count'   => count( $tickets ),
            ]
        );
    }

    public static function handle_zebra_agent_next_job( WP_REST_Request $request ) {
        if ( ! class_exists( 'IW_Zebra_Print_Queue' ) ) {
            return new WP_Error( 'iw_cashier_zebra_queue_missing', __( 'Η ουρά εκτύπωσης δεν είναι διαθέσιμη.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $printer_id = IW_Zebra_Print_Queue::printer_id_from_request( $request );
        $agent_id   = sanitize_text_field( (string) ( $request->get_param( 'agent_id' ) ?: $request->get_header( 'x_iw_print_agent_id' ) ) );
        $job        = IW_Zebra_Print_Queue::claim_next_job( $printer_id, $agent_id );

        return rest_ensure_response(
            [
                'success'    => true,
                'printer_id' => $printer_id,
                'job'        => $job,
            ]
        );
    }

    public static function handle_zebra_agent_complete_job( WP_REST_Request $request ) {
        if ( ! class_exists( 'IW_Zebra_Print_Queue' ) ) {
            return new WP_Error( 'iw_cashier_zebra_queue_missing', __( 'Η ουρά εκτύπωσης δεν είναι διαθέσιμη.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $payload = $request->get_json_params();
        if ( ! is_array( $payload ) ) {
            $payload = [];
        }

        $job_id  = absint( $request->get_param( 'job_id' ) );
        $success = rest_sanitize_boolean( $request->get_param( 'success' ) ?? ( $payload['success'] ?? false ) );
        $message = sanitize_text_field( (string) ( $request->get_param( 'message' ) ?? ( $payload['message'] ?? '' ) ) );
        $result  = $payload['result'] ?? [];
        if ( ! is_array( $result ) ) {
            $result = [];
        }

        $job = IW_Zebra_Print_Queue::complete_job( $job_id, $success, $message, $result );
        if ( is_wp_error( $job ) ) {
            return $job;
        }

        return rest_ensure_response(
            [
                'success' => true,
                'job'     => $job,
            ]
        );
    }

    protected static function is_zebra_debug_request( WP_REST_Request $request ): bool {
        if ( strtoupper( (string) $request->get_method() ) !== 'POST' ) {
            return true;
        }

        return rest_sanitize_boolean( $request->get_param( 'debug_zpl' ) )
            || rest_sanitize_boolean( $request->get_param( 'debug' ) );
    }

    protected static function get_ticket_filters(): array {
        $filters = [
            'all' => [
                'id'    => 'all',
                'label' => __( 'Όλα', 'iw-theme' ),
            ],
        ];

        $supported_post_types = class_exists( 'IW_Ticketing' )
            ? IW_Ticketing::get_supported_post_types()
            : [];

        if ( in_array( 'museum', $supported_post_types, true ) ) {
            $filters['museum'] = [
                'id'    => 'museum',
                'label' => __( 'Μουσεία', 'iw-theme' ),
            ];
        }

        if ( in_array( 'building', $supported_post_types, true ) || in_array( 'exhibition', $supported_post_types, true ) ) {
            $filters['permanent-exhibition'] = [
                'id'    => 'permanent-exhibition',
                'label' => __( 'Μόνιμες Εκθέσεις', 'iw-theme' ),
            ];
            $filters['temporary-exhibition'] = [
                'id'    => 'temporary-exhibition',
                'label' => __( 'Περιοδικές Εκθέσεις', 'iw-theme' ),
            ];
        }

        $filter_labels = [
            'guided-tour'     => __( 'Ξεναγήσεις', 'iw-theme' ),
            'event'           => __( 'Εκδηλώσεις', 'iw-theme' ),
            'learning-program'=> __( 'Εκπαιδευτικά Προγράμματα', 'iw-theme' ),
            'experience'      => __( 'Experiences', 'iw-theme' ),
        ];
        foreach ( $filter_labels as $post_type => $label ) {
            if ( in_array( $post_type, $supported_post_types, true ) ) {
                $filters[ $post_type ] = [
                    'id'    => $post_type,
                    'label' => $label,
                ];
            }
        }

        return (array) apply_filters( 'iw_cashier_ticket_filters', $filters );
    }

    protected static function normalize_ticket_type_filter( $raw ): array {
        if ( is_string( $raw ) ) {
            $raw = preg_split( '/[,\s]+/', $raw );
        }

        if ( ! is_array( $raw ) ) {
            return [];
        }

        $allowed = array_keys( self::get_ticket_filters() );
        $values  = [];
        foreach ( $raw as $value ) {
            $value = sanitize_key( (string) $value );
            if ( $value === '' || $value === 'all' || ! in_array( $value, $allowed, true ) ) {
                continue;
            }
            $values[] = $value;
        }

        return array_values( array_unique( $values ) );
    }

    protected static function get_cashier_ticket_entries( string $search, int $limit, array $types, int $building_id = 0 ): array {
        if ( ! class_exists( 'IW_Ticketing' ) ) {
            return [];
        }

        $selected_types = ! empty( $types )
            ? $types
            : array_values( array_diff( array_keys( self::get_ticket_filters() ), [ 'all' ] ) );

        $has_permanent_exhibitions = in_array( 'permanent-exhibition', $selected_types, true );
        $has_temporary_exhibitions = in_array( 'temporary-exhibition', $selected_types, true );
        $post_types = [];
        if ( in_array( 'museum', $selected_types, true ) ) {
            $post_types[] = 'museum';
        }
        if ( $has_permanent_exhibitions ) {
            $post_types[] = 'building';
            if ( $search !== '' ) {
                $post_types[] = 'exhibition';
            }
        }
        if ( $has_temporary_exhibitions ) {
            $post_types[] = 'exhibition';
        }
        foreach ( [ 'guided-tour', 'event', 'learning-program', 'experience' ] as $post_type ) {
            if ( in_array( $post_type, $selected_types, true ) ) {
                $post_types[] = $post_type;
            }
        }

        $supported_post_types = IW_Ticketing::get_supported_post_types();
        $post_types = array_values( array_intersect( array_unique( $post_types ), $supported_post_types ) );
        if ( empty( $post_types ) ) {
            return [];
        }

        $query_limit = $search !== ''
            ? min( 500, max( 60, $limit * 8 ) )
            : min( 500, max( 120, $limit * 4 ) );

        if ( $building_id > 0 ) {
            $query_limit = max( $query_limit, min( 800, max( 120, $limit * 12 ) ) );
        }

        $has_museums = in_array( 'museum', $post_types, true );
        $meta_query  = [
            'relation' => 'AND',
        ];

        if ( ! $has_museums ) {
            $meta_query['cashier_is_abroad'] = [
                'key'     => 'is_abroad',
                'compare' => '=',
                'value'   => '0',
            ];
        }

        if ( $search === '' ) {
            $today = current_time( 'Ymd' );
            $meta_query['cashier_min_date'] = [
                'key'     => 'min_date',
                'compare' => '<=',
                'value'   => $today,
                'type'    => 'NUMERIC',
            ];
            $meta_query['cashier_max_date'] = [
                'key'     => 'max_date',
                'compare' => '>=',
                'value'   => $today,
                'type'    => 'NUMERIC',
            ];
            if ( ! $has_museums ) {
                $meta_query['cashier_is_permanent'] = [
                    'key'     => 'is_permanent',
                    'compare' => '=',
                    'value'   => '0',
                ];
            }
        }

        $orderby = [
            'meta_value_num' => 'ASC',
            'ID'             => 'ASC',
        ];
        if ( $search === '' ) {
            $orderby = [
                'meta_value_num'   => 'ASC',
                'cashier_min_date' => 'ASC',
                'ID'               => 'ASC',
            ];
        }

        $query = new WP_Query(
            [
                'post_type'        => $post_types,
                'post_status'      => 'publish',
                'posts_per_page'   => $query_limit,
                's'                => $search,
                'meta_key'         => 'max_date',
                'orderby'          => $orderby,
                'order'            => 'ASC',
                'meta_type'        => 'NUMERIC',
                'meta_query'       => $meta_query,
                'fields'           => 'ids',
                'no_found_rows'    => true,
                'suppress_filters' => false,
            ]
        );

        $items = [];
        $seen  = [];
        foreach ( $query->posts as $source_id ) {
            $source_id = (int) $source_id;

            $entry = self::normalize_cashier_ticket_entry( $source_id );
            if ( ! $entry ) {
                continue;
            }

            if ( ! empty( $types ) && ! in_array( $entry['type'], $types, true ) ) {
                continue;
            }

            if ( $building_id > 0 && empty( $entry['all_locations'] ) && (int) ( $entry['building_id'] ?? 0 ) !== $building_id ) {
                continue;
            }

            $seen_key = $entry['type'] . ':' . $entry['ticket_id'];
            if ( isset( $seen[ $seen_key ] ) ) {
                continue;
            }

            $seen[ $seen_key ] = true;
            $items[] = $entry;

            if ( count( $items ) >= $limit ) {
                break;
            }
        }

        return $items;
    }

    protected static function normalize_cashier_ticket_entry( int $source_id ): ?array {
        $ticket_id = self::resolve_cashier_ticket_post_id( $source_id );
        if ( is_wp_error( $ticket_id ) ) {
            return null;
        }

        $ticket_id = (int) $ticket_id;
        if ( $ticket_id <= 0 || ! IW_Ticketing::is_sellable( $ticket_id ) ) {
            return null;
        }

        if (
            get_post_type( $ticket_id ) === 'building'
            && function_exists( 'iw_ticketing_post_has_ticket_prices' )
            && ! iw_ticketing_post_has_ticket_prices( $ticket_id )
        ) {
            return null;
        }

        $source_title = self::cashier_title( get_the_title( $source_id ) );
        $ticket_title = self::cashier_title( get_the_title( $ticket_id ) );
        $type         = self::cashier_ticket_entry_type( $source_id, $ticket_id );
        $all_locations = self::is_all_locations_ticket_post( $ticket_id );
        $building_id  = self::cashier_ticket_entry_building_id( $source_id, $ticket_id );
        $image        = self::cashier_ticket_entry_image( $source_id, $ticket_id );
        $dates        = self::cashier_ticket_entry_dates( $source_id, $type );
        $price        = self::cashier_ticket_entry_price( $ticket_id );
        $display      = $ticket_title;

        if ( $source_id !== $ticket_id && $source_title !== '' && $source_title !== $ticket_title ) {
            $display = sprintf( '%s / %s', $source_title, $ticket_title );
        }

        return [
            'id'               => $ticket_id,
            'ticket_id'        => $ticket_id,
            'source_id'        => $source_id,
            'title'            => $ticket_title,
            'display_title'    => $display,
            'source_title'     => $source_title,
            'post_type'        => get_post_type( $source_id ),
            'ticket_post_type' => get_post_type( $ticket_id ),
            'type'             => $type,
            'type_label'       => self::ticket_filter_label( $type ),
            'all_locations'    => $all_locations,
            'building_id'      => $building_id,
            'building_title'   => $building_id > 0 ? self::plain_text( get_the_title( $building_id ) ) : '',
            'thumb'            => $image['thumb'],
            'image'            => $image,
            'date_from'        => $dates['from'],
            'date_to'          => $dates['to'],
            'date_label'       => $dates['label'],
            'price_min'        => $price['min'],
            'price_max'        => $price['max'],
            'price_label'      => $price['label'],
            'permalink'        => get_permalink( $source_id ),
            'ticket_permalink' => function_exists( 'get_tickets_permalink' ) ? get_tickets_permalink( $source_id ) : get_permalink( $ticket_id ),
        ];
    }

    protected static function cashier_ticket_entry_image( int $source_id, int $ticket_id ): array {
        $thumbnail_id = get_post_thumbnail_id( $source_id );
        if ( ! $thumbnail_id && $ticket_id !== $source_id ) {
            $thumbnail_id = get_post_thumbnail_id( $ticket_id );
        }

        if ( ! $thumbnail_id && function_exists( 'get_field' ) ) {
            foreach ( [ 'card_image', 'hero_image' ] as $field_name ) {
                $image = get_field( $field_name, $source_id );
                if ( is_numeric( $image ) ) {
                    $thumbnail_id = absint( $image );
                } elseif ( is_object( $image ) && isset( $image->ID ) ) {
                    $thumbnail_id = absint( $image->ID );
                } elseif ( is_array( $image ) ) {
                    $thumbnail_id = absint( $image['ID'] ?? ( $image['id'] ?? 0 ) );
                }

                if ( $thumbnail_id ) {
                    break;
                }
            }
        }

        if ( ! $thumbnail_id ) {
            return [
                'thumb' => '',
                'url'   => '',
                'alt'   => '',
            ];
        }

        $thumb = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );
        $full  = wp_get_attachment_image_src( $thumbnail_id, 'medium_large' );

        return [
            'thumb' => esc_url_raw( $thumb[0] ?? '' ),
            'url'   => esc_url_raw( $full[0] ?? ( $thumb[0] ?? '' ) ),
            'alt'   => self::plain_text( get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) ),
        ];
    }

    protected static function cashier_ticket_entry_dates( int $source_id, string $type ): array {
        if ( $type === 'permanent-exhibition' ) {
            return [
                'from'  => '',
                'to'    => '',
                'label' => '',
            ];
        }

        $from = self::format_cashier_date( function_exists( 'get_field' ) ? get_field( 'min_date', $source_id ) : get_post_meta( $source_id, 'min_date', true ) );
        $to   = self::format_cashier_date( function_exists( 'get_field' ) ? get_field( 'max_date', $source_id ) : get_post_meta( $source_id, 'max_date', true ) );

        if ( $from !== '' && $to !== '' ) {
            $label = sprintf( '%1$s · %2$s', $from, $to );
        } elseif ( $from !== '' ) {
            $label = $from;
        } elseif ( $to !== '' ) {
            $label = $to;
        } else {
            $label = '';
        }

        return [
            'from'  => $from,
            'to'    => $to,
            'label' => $label,
        ];
    }

    protected static function format_cashier_date( $value ): string {
        $value = trim( (string) $value );
        if ( $value === '' ) {
            return '';
        }

        foreach ( [ 'd/m/Y', 'Ymd', 'Y-m-d' ] as $format ) {
            $date = DateTime::createFromFormat( $format, $value );
            if ( $date instanceof DateTime ) {
                return $date->format( 'd/m/Y' );
            }
        }

        return $value;
    }

    protected static function cashier_ticket_entry_price( int $ticket_id ): array {
        $regular_raw = function_exists( 'get_field' ) ? get_field( '_price', $ticket_id ) : get_post_meta( $ticket_id, '_price', true );
        $reduced_raw = function_exists( 'get_field' ) ? get_field( '_price_reduced', $ticket_id ) : get_post_meta( $ticket_id, '_price_reduced', true );
        $regular     = self::normalize_cashier_price( $regular_raw );
        $reduced     = self::normalize_cashier_price( $reduced_raw );

        if ( $regular !== null ) {
            if ( $regular <= 0.0 ) {
                return [
                    'min'   => 0.0,
                    'max'   => 0.0,
                    'label' => __( 'Ελεύθερη είσοδος', 'iw-theme' ),
                ];
            }

            $prices = [ $regular ];
            if ( $reduced !== null && $reduced > 0.0 && $reduced !== $regular ) {
                $prices[] = $reduced;
            }

            return [
                'min'   => min( $prices ),
                'max'   => max( $prices ),
                'label' => implode( ' - ', array_map( [ __CLASS__, 'format_cashier_price' ], $prices ) ),
            ];
        }

        $category_prices = self::cashier_ticket_category_prices( $ticket_id );
        if ( empty( $category_prices ) ) {
            return [
                'min'   => null,
                'max'   => null,
                'label' => '',
            ];
        }

        $min = min( $category_prices );
        $max = max( $category_prices );

        return [
            'min'   => $min,
            'max'   => $max,
            'label' => $min === $max
                ? self::format_cashier_price( $min )
                : sprintf( '%s - %s', self::format_cashier_price( $min ), self::format_cashier_price( $max ) ),
        ];
    }

    protected static function cashier_ticket_category_prices( int $ticket_id ): array {
        $prices = [];
        $add_price = static function ( $price ) use ( &$prices ) {
            $price = self::normalize_cashier_price( $price );
            if ( $price !== null && $price > 0.0 ) {
                $prices[] = $price;
            }
        };

        $ticket_categories_info = (array) ( function_exists( 'get_field' ) ? get_field( 'iw_ticket_categories', 'option' ) : [] );
        $post_ticket_categories = (array) ( function_exists( 'get_field' ) ? get_field( 'ticket_categories', $ticket_id ) : get_post_meta( $ticket_id, 'ticket_categories', true ) );

        foreach ( $ticket_categories_info as $cat_info ) {
            if ( empty( $cat_info ) || ! is_array( $cat_info ) ) {
                continue;
            }

            $cat_key = sanitize_key( (string) ( $cat_info['key'] ?? '' ) );
            if ( $cat_key === '' || empty( $post_ticket_categories[ $cat_key ] ) || ! is_array( $post_ticket_categories[ $cat_key ] ) ) {
                continue;
            }

            foreach ( (array) ( $cat_info['subcategories'] ?? [] ) as $sub_info ) {
                if ( ! is_array( $sub_info ) ) {
                    continue;
                }

                $sub_key = sanitize_key( (string) ( $sub_info['key'] ?? '' ) );
                if ( $sub_key !== '' && array_key_exists( $sub_key, $post_ticket_categories[ $cat_key ] ) ) {
                    $add_price( $post_ticket_categories[ $cat_key ][ $sub_key ] );
                }
            }
        }

        foreach ( (array) ( function_exists( 'get_field' ) ? get_field( 'custom_ticket_categories', $ticket_id ) : [] ) as $custom_cat ) {
            foreach ( (array) ( $custom_cat['subcategories'] ?? [] ) as $custom_sub ) {
                if ( is_array( $custom_sub ) && array_key_exists( 'price', $custom_sub ) ) {
                    $add_price( $custom_sub['price'] );
                }
            }
        }

        return array_values( array_unique( $prices ) );
    }

    protected static function normalize_cashier_price( $price ): ?float {
        if ( is_string( $price ) ) {
            $price = trim( str_replace( ',', '.', $price ) );
        }

        if ( $price === '' || $price === null || ! is_numeric( $price ) ) {
            return null;
        }

        return (float) $price;
    }

    protected static function plain_text( $value ): string {
        return html_entity_decode(
            wp_strip_all_tags( (string) $value ),
            ENT_QUOTES | ENT_HTML5,
            get_bloginfo( 'charset' )
        );
    }

    protected static function cashier_title( $value ): string {
        $title = self::plain_text( $value );
        $title = preg_replace( '/\bcodex\b/i', '', $title );
        $title = preg_replace( '/\s+/u', ' ', (string) $title );
        $title = preg_replace( '/^\s*[-–—:|\/]+\s*/u', '', (string) $title );
        $title = preg_replace( '/\s*[-–—:|\/]+\s*$/u', '', (string) $title );

        return trim( (string) $title );
    }

    protected static function format_cashier_price( float $price ): string {
        if ( function_exists( 'wc_price' ) ) {
            $html = wc_price( $price, [ 'decimals' => floor( $price ) === $price ? 0 : 2 ] );
            return trim( str_replace( [ "\xc2\xa0", '&nbsp;' ], ' ', wp_strip_all_tags( html_entity_decode( $html, ENT_QUOTES, get_bloginfo( 'charset' ) ) ) ) );
        }

        return sprintf( '%s €', number_format_i18n( $price, floor( $price ) === $price ? 0 : 2 ) );
    }

    protected static function is_all_locations_ticket_post( int $post_id ): bool {
        if ( $post_id <= 0 ) {
            return false;
        }

        $scope = (string) get_post_meta( $post_id, 'iw_ticket_scope', true );
        $is_all_locations = in_array( $scope, [ 'all_locations', 'all_museums' ], true );

        if ( ! $is_all_locations ) {
            $flag = get_post_meta( $post_id, '_com_all_museums_ticket', true );
            $is_all_locations = $flag === true || $flag === 1 || $flag === '1' || $flag === 'yes' || $flag === 'true';
        }

        return (bool) apply_filters( 'iw_cashier_ticket_entry_is_all_locations', $is_all_locations, $post_id );
    }

    protected static function cashier_ticket_entry_building_id( int $source_id, int $ticket_id ): int {
        if ( self::is_all_locations_ticket_post( $ticket_id ) || self::is_all_locations_ticket_post( $source_id ) ) {
            return 0;
        }

        if ( get_post_type( $ticket_id ) === 'museum' ) {
            return $ticket_id;
        }

        if ( get_post_type( $source_id ) === 'museum' ) {
            return $source_id;
        }

        if ( get_post_type( $ticket_id ) === 'building' ) {
            return $ticket_id;
        }

        foreach ( [ 'building_location', 'building' ] as $field ) {
            $building_id = self::normalize_building_reference_id(
                function_exists( 'get_field' ) ? get_field( $field, $source_id ) : get_post_meta( $source_id, $field, true )
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

    protected static function resolve_cashier_ticket_post_id( int $source_id ) {
        if ( $source_id <= 0 || ! get_post( $source_id ) || ! class_exists( 'IW_Ticketing' ) ) {
            return new WP_Error( 'iw_cashier_invalid_ticket', __( 'Δεν βρέθηκε εισιτήριο.', 'iw-theme' ), [ 'status' => 404 ] );
        }

        if ( ! IW_Ticketing::is_sellable( $source_id ) ) {
            return new WP_Error( 'iw_cashier_ticket_not_sellable', __( 'Το εισιτήριο δεν είναι διαθέσιμο για πώληση.', 'iw-theme' ), [ 'status' => 404 ] );
        }

        $current = $source_id;
        $visited = [];

        while ( $current > 0 && ! isset( $visited[ $current ] ) ) {
            $visited[ $current ] = true;

            if ( get_post_type( $current ) === 'building' ) {
                break;
            }

            $price    = function_exists( 'get_field' ) ? (string) get_field( '_price', $current ) : (string) get_post_meta( $current, '_price', true );
            $building = function_exists( 'get_field' ) ? get_field( 'building_location', $current ) : get_post_meta( $current, 'building_location', true );
            $building_id = 0;
            if ( is_object( $building ) && isset( $building->ID ) ) {
                $building_id = (int) $building->ID;
            } elseif ( is_numeric( $building ) ) {
                $building_id = absint( $building );
            }

            $should_use_building = ( self::is_permanent_exhibition( $current ) || $price === '0' || $price === '' ) && $building_id > 0;
            if ( ! $should_use_building ) {
                break;
            }

            if ( ! IW_Ticketing::is_sellable( $building_id ) ) {
                return new WP_Error( 'iw_cashier_building_ticket_not_sellable', __( 'Το εισιτήριο κτιρίου δεν είναι διαθέσιμο για πώληση.', 'iw-theme' ), [ 'status' => 404 ] );
            }

            $current = $building_id;
        }

        if ( $current <= 0 || ! IW_Ticketing::is_sellable( $current ) ) {
            return new WP_Error( 'iw_cashier_ticket_not_sellable', __( 'Το εισιτήριο δεν είναι διαθέσιμο για πώληση.', 'iw-theme' ), [ 'status' => 404 ] );
        }

        return $current;
    }

    protected static function cashier_ticket_entry_type( int $source_id, int $ticket_id ): string {
        if ( get_post_type( $ticket_id ) === 'museum' || get_post_type( $source_id ) === 'museum' ) {
            return 'museum';
        }

        if ( get_post_type( $ticket_id ) === 'building' || self::is_permanent_exhibition( $source_id ) ) {
            return 'permanent-exhibition';
        }

        if ( get_post_type( $source_id ) === 'exhibition' ) {
            return 'temporary-exhibition';
        }

        return (string) get_post_type( $source_id );
    }

    protected static function is_permanent_exhibition( int $post_id ): bool {
        return get_post_type( $post_id ) === 'exhibition' && (bool) ( function_exists( 'get_field' ) ? get_field( 'is_permanent', $post_id ) : get_post_meta( $post_id, 'is_permanent', true ) );
    }

    protected static function ticket_filter_label( string $type ): string {
        $filters = self::get_ticket_filters();
        return isset( $filters[ $type ]['label'] ) ? self::plain_text( $filters[ $type ]['label'] ) : $type;
    }

    protected static function get_payment_methods(): array {
        $methods = [
            'cash' => [
                'id'                    => 'cash',
                'label'                 => __( 'Μετρητά', 'iw-theme' ),
                'gateway_id'            => 'cashier_cash',
                'gateway_title'         => __( 'Ταμείο - Μετρητά', 'iw-theme' ),
                'type'                  => 'cash',
                'provider'              => null,
                'enabled'               => true,
                'requires_pos_reference' => false,
                'api_integration'       => false,
            ],
            'pos_manual' => [
                'id'                    => 'pos_manual',
                'label'                 => __( 'POS', 'iw-theme' ),
                'gateway_id'            => 'cashier_pos_manual',
                'gateway_title'         => __( 'Ταμείο - POS', 'iw-theme' ),
                'type'                  => 'pos',
                'provider'              => 'manual',
                'enabled'               => true,
                'requires_pos_reference' => false,
                'api_integration'       => false,
            ],
            'pos_nexi' => [
                'id'                    => 'pos_nexi',
                'label'                 => __( 'POS Nexi API', 'iw-theme' ),
                'gateway_id'            => 'cashier_pos_nexi',
                'gateway_title'         => __( 'Ταμείο - POS Nexi', 'iw-theme' ),
                'type'                  => 'pos',
                'provider'              => 'nexi',
                'enabled'               => self::nexi_pos_is_configured(),
                'requires_pos_reference' => false,
                'requires_terminal_id'  => ! self::nexi_pos_has_default_terminal(),
                'api_integration'       => true,
            ],
            'pos_alpha' => [
                'id'                    => 'pos_alpha',
                'label'                 => __( 'POS Alpha API', 'iw-theme' ),
                'gateway_id'            => 'cashier_pos_alpha',
                'gateway_title'         => __( 'Ταμείο - POS Alpha', 'iw-theme' ),
                'type'                  => 'pos',
                'provider'              => 'alpha',
                'enabled'               => false,
                'requires_pos_reference' => false,
                'api_integration'       => true,
            ],
        ];

        return (array) apply_filters( 'iw_cashier_payment_methods', $methods );
    }

    protected static function prepare_ticket_purchase( int $ticket_id, string $day_raw, string $time_raw, $visitors ) {
        if ( $ticket_id <= 0 || ! class_exists( 'IW_Ticketing' ) ) {
            return new WP_Error( 'iw_cashier_missing_ticket', __( 'Λείπει το εισιτήριο.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $tickets_data = IW_Ticketing::get_tickets_data( $ticket_id );
        if ( ! $tickets_data ) {
            return new WP_Error( 'iw_cashier_invalid_ticket', __( 'Το εισιτήριο δεν είναι διαθέσιμο.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        if ( $day_raw === '' || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $day_raw ) ) {
            return new WP_Error( 'iw_cashier_invalid_day', __( 'Μη έγκυρη ημερομηνία.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $time_norm = str_replace( '-', ':', $time_raw );
        if ( $time_norm === '' || ! preg_match( '/^\d{2}:\d{2}$/', $time_norm ) ) {
            return new WP_Error( 'iw_cashier_invalid_time', __( 'Μη έγκυρη ώρα.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $hh = (int) substr( $time_norm, 0, 2 );
        $mm = (int) substr( $time_norm, 3, 2 );
        if ( $hh > 23 || $mm > 59 ) {
            return new WP_Error( 'iw_cashier_invalid_time_value', __( 'Μη έγκυρη ώρα.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $visitors = self::normalize_visitors_payload( $visitors );
        if ( is_wp_error( $visitors ) ) {
            return $visitors;
        }

        $max_visitors = (int) apply_filters( 'iw_cashier_max_visitors_per_request', 50 );
        if ( count( $visitors ) > $max_visitors ) {
            return new WP_Error( 'iw_cashier_too_many_visitors', __( 'Πάρα πολλά εισιτήρια σε μία έκδοση.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $schedule = self::read_data_value( $tickets_data, 'schedule' );
        if ( empty( $schedule ) ) {
            return new WP_Error( 'iw_cashier_missing_schedule', __( 'Δεν βρέθηκε πρόγραμμα διαθεσιμότητας.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $selected_slot = self::find_available_slot( $schedule, $day_raw, $time_norm, count( $visitors ) );
        if ( is_wp_error( $selected_slot ) ) {
            return $selected_slot;
        }

        $min_max = self::validate_global_ticket_limits( $tickets_data, count( $visitors ) );
        if ( is_wp_error( $min_max ) ) {
            return $min_max;
        }

        $enriched = self::enrich_visitors_from_ticket_categories( $tickets_data, $visitors );
        if ( is_wp_error( $enriched ) ) {
            return $enriched;
        }

        $member_slot_limit = self::validate_member_ticket_slot_limits( $ticket_id, $day_raw, $time_norm, $enriched );
        if ( is_wp_error( $member_slot_limit ) ) {
            return $member_slot_limit;
        }

        $total_price = 0.0;
        foreach ( $enriched as $visitor ) {
            $total_price += isset( $visitor['price'] ) ? (float) $visitor['price'] : 0.0;
        }

        return [
            'ticket_id'         => $ticket_id,
            'title'             => self::cashier_title( get_the_title( $ticket_id ) ),
            'day'               => $day_raw,
            'time'              => $time_norm,
            'visitors'          => $enriched,
            'requested_tickets' => count( $enriched ),
            'total_price'       => $total_price,
            'selected_slot'     => $selected_slot,
        ];
    }

    protected static function normalize_visitors_payload( $visitors ) {
        if ( is_string( $visitors ) ) {
            $decoded = json_decode( wp_unslash( $visitors ), true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                return new WP_Error( 'iw_cashier_invalid_visitors_json', __( 'Μη έγκυρα στοιχεία επισκεπτών.', 'iw-theme' ), [ 'status' => 400 ] );
            }
            $visitors = $decoded;
        }

        if ( ! is_array( $visitors ) ) {
            return new WP_Error( 'iw_cashier_invalid_visitors', __( 'Μη έγκυρα στοιχεία επισκεπτών.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $normalized = [];
        foreach ( $visitors as $index => $visitor ) {
            if ( ! is_array( $visitor ) ) {
                return new WP_Error( 'iw_cashier_invalid_visitor', sprintf( __( 'Μη έγκυρο εισιτήριο στη θέση %d.', 'iw-theme' ), $index + 1 ), [ 'status' => 400 ] );
            }

            $first       = isset( $visitor['first'] ) ? sanitize_text_field( (string) $visitor['first'] ) : '';
            $last        = isset( $visitor['last'] ) ? sanitize_text_field( (string) $visitor['last'] ) : '';
            $category_id = isset( $visitor['category-id'] ) ? sanitize_key( (string) $visitor['category-id'] ) : '';
            $member_card_id = isset( $visitor['member_card_id'] )
                ? self::normalize_member_card_id( $visitor['member_card_id'] )
                : '';
            $member_role = isset( $visitor['member_role'] ) ? sanitize_key( (string) $visitor['member_role'] ) : '';
            $member_user_id = isset( $visitor['member_user_id'] ) ? absint( $visitor['member_user_id'] ) : 0;
            $member_subscription_id = isset( $visitor['member_subscription_id'] ) ? absint( $visitor['member_subscription_id'] ) : 0;

            if ( strlen( $first ) > 80 || strlen( $last ) > 80 ) {
                return new WP_Error( 'iw_cashier_visitor_name_too_long', sprintf( __( 'Το όνομα στο εισιτήριο %d είναι πολύ μεγάλο.', 'iw-theme' ), $index + 1 ), [ 'status' => 400 ] );
            }

            if ( $category_id === '' || ! preg_match( '/^[a-z0-9_-]+$/', $category_id ) ) {
                return new WP_Error( 'iw_cashier_missing_visitor_type', sprintf( __( 'Λείπει τύπος εισιτηρίου στη θέση %d.', 'iw-theme' ), $index + 1 ), [ 'status' => 400 ] );
            }

            $row = [
                'first'       => $first,
                'last'        => $last,
                'category-id' => $category_id,
            ];

            if ( $member_card_id !== '' ) {
                $row['member_card_id'] = $member_card_id;
            }

            if ( in_array( $member_role, [ 'member', 'companion' ], true ) ) {
                $row['member_role'] = $member_role;
            }

            if ( $member_user_id > 0 ) {
                $row['member_user_id'] = $member_user_id;
            }

            if ( $member_subscription_id > 0 ) {
                $row['member_subscription_id'] = $member_subscription_id;
            }

            $normalized[] = $row;
        }

        return $normalized;
    }

    protected static function find_available_slot( $schedule, string $day, string $time, int $requested_tickets ) {
        $allow_dates = (array) self::read_data_value( $schedule, 'allowDates', [] );
        if ( empty( $allow_dates ) || ! in_array( $day, $allow_dates, true ) ) {
            return new WP_Error( 'iw_cashier_day_unavailable', __( 'Η επιλεγμένη ημερομηνία δεν είναι διαθέσιμη.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $times_by_date = (array) self::read_data_value( $schedule, 'timesByDate', [] );
        $day_slots     = isset( $times_by_date[ $day ] ) ? (array) $times_by_date[ $day ] : [];
        if ( empty( $day_slots ) ) {
            return new WP_Error( 'iw_cashier_no_times', __( 'Δεν υπάρχουν διαθέσιμες ώρες για την επιλεγμένη ημερομηνία.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $selected_slot = null;
        foreach ( $day_slots as $slot ) {
            $slot = is_object( $slot ) ? (array) $slot : $slot;
            if ( ! is_array( $slot ) ) {
                continue;
            }

            if ( isset( $slot['time'] ) && (string) $slot['time'] === $time ) {
                $selected_slot = $slot;
                break;
            }
        }

        if ( ! $selected_slot ) {
            return new WP_Error( 'iw_cashier_time_unavailable', __( 'Η επιλεγμένη ώρα δεν είναι διαθέσιμη.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $slot_status = isset( $selected_slot['status'] ) ? (string) $selected_slot['status'] : 'available';
        if ( $slot_status === 'sold-out' ) {
            return new WP_Error( 'iw_cashier_slot_sold_out', __( 'Η επιλεγμένη ώρα έχει εξαντληθεί.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        if ( isset( $selected_slot['availability'] ) ) {
            $availability = (int) $selected_slot['availability'];
            if ( $availability >= 0 && $requested_tickets > $availability ) {
                return new WP_Error(
                    'iw_cashier_slot_capacity',
                    __( 'Δεν υπάρχει διαθέσιμη χωρητικότητα για τον αριθμό εισιτηρίων που επιλέξατε.', 'iw-theme' ),
                    [
                        'status'       => 400,
                        'availability' => $availability,
                        'requested'    => $requested_tickets,
                    ]
                );
            }
        }

        return $selected_slot;
    }

    protected static function ticket_count_label( int $count ): string {
        return sprintf(
            _n( '%d εισιτήριο', '%d εισιτήρια', $count, 'iw-theme' ),
            $count
        );
    }

    protected static function validate_global_ticket_limits( $tickets_data, int $requested_tickets ) {
        $global_min = (int) self::read_data_value( $tickets_data, 'min_tickets', 1 );
        $global_max = self::read_data_value( $tickets_data, 'max_tickets', apply_filters( 'iw_cashier_default_global_max_tickets', 50 ) );

        if ( $global_min < 1 ) {
            $global_min = 1;
        }

        if ( $requested_tickets < $global_min ) {
            return new WP_Error( 'iw_cashier_min_tickets', sprintf( __( 'Πρέπει να επιλέξετε τουλάχιστον %s.', 'iw-theme' ), self::ticket_count_label( $global_min ) ), [ 'status' => 400 ] );
        }

        if ( $global_max !== null && $global_max !== 'null' && (int) $global_max > 0 && $requested_tickets > (int) $global_max ) {
            return new WP_Error( 'iw_cashier_max_tickets', sprintf( __( 'Μπορείτε να επιλέξετε έως %s.', 'iw-theme' ), self::ticket_count_label( (int) $global_max ) ), [ 'status' => 400 ] );
        }

        return true;
    }

    protected static function enrich_visitors_from_ticket_categories( $tickets_data, array $visitors ) {
        $ticket_categories = self::read_data_value( $tickets_data, 'ticket_categories', [] );
        if ( empty( $ticket_categories ) || ! is_array( $ticket_categories ) ) {
            return new WP_Error( 'iw_cashier_missing_ticket_categories', __( 'Δεν βρέθηκαν κατηγορίες εισιτηρίων.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $allowed_types = [];
        foreach ( $ticket_categories as $category ) {
            $require_full_name = (bool) self::read_data_value( $category, 'require_full_name', false );
            $cat_min           = (int) self::read_data_value( $category, 'min_tickets', 0 );
            $cat_max           = (int) self::read_data_value( $category, 'max_tickets', 0 );
            $cat_price         = self::read_data_value( $category, 'price', null );
            $subcategories     = self::read_data_value( $category, 'subcategories', [] );

            if ( ! is_array( $subcategories ) ) {
                continue;
            }

            foreach ( $subcategories as $subcategory ) {
                $value = sanitize_key( (string) self::read_data_value( $subcategory, 'value', '' ) );
                if ( $value === '' ) {
                    continue;
                }

                $price = self::read_data_value( $subcategory, 'price', null );
                if ( ( $price === null || $price === '' ) && is_numeric( $cat_price ) ) {
                    $price = (float) $cat_price;
                }

                $allowed_types[ $value ] = [
                    'require_full_name' => $require_full_name,
                    'min_tickets'       => $cat_min,
                    'max_tickets'       => $cat_max,
                    'price'             => is_numeric( $price ) ? (float) $price : null,
                    'label'             => trim( (string) self::read_data_value( $subcategory, 'label', $value ) ),
                ];
            }
        }

        if ( empty( $allowed_types ) ) {
            return new WP_Error( 'iw_cashier_no_valid_ticket_types', __( 'Δεν βρέθηκαν έγκυροι τύποι εισιτηρίων.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        $counts_by_type = [];
        $member_counts_by_card = [];
        foreach ( $visitors as $index => $visitor ) {
            $type = isset( $visitor['category-id'] ) ? sanitize_key( (string) $visitor['category-id'] ) : '';
            if ( $type === '' || ! isset( $allowed_types[ $type ] ) ) {
                return new WP_Error( 'iw_cashier_invalid_ticket_type', sprintf( __( 'Μη έγκυρος τύπος εισιτηρίου στο εισιτήριο %d.', 'iw-theme' ), $index + 1 ), [ 'status' => 400 ] );
            }

            $counts_by_type[ $type ] = ( $counts_by_type[ $type ] ?? 0 ) + 1;

            if ( ! empty( $allowed_types[ $type ]['require_full_name'] ) ) {
                $first = isset( $visitor['first'] ) ? trim( (string) $visitor['first'] ) : '';
                $last  = isset( $visitor['last'] ) ? trim( (string) $visitor['last'] ) : '';
                if ( $first === '' || $last === '' ) {
                    return new WP_Error( 'iw_cashier_missing_full_name', sprintf( __( 'Απαιτείται ονοματεπώνυμο για το εισιτήριο %d.', 'iw-theme' ), $index + 1 ), [ 'status' => 400 ] );
                }
            }

            if ( $allowed_types[ $type ]['price'] === null ) {
                return new WP_Error( 'iw_cashier_missing_ticket_price', sprintf( __( 'Δεν βρέθηκε τιμή για το εισιτήριο %d.', 'iw-theme' ), $index + 1 ), [ 'status' => 400 ] );
            }

            if ( self::is_member_ticket_type( $type ) ) {
                $member_card_id = self::normalize_member_card_id( $visitor['member_card_id'] ?? '' );
                if ( $member_card_id === '' ) {
                    return new WP_Error( 'iw_cashier_member_card_required', __( 'Απαιτείται έλεγχος ενεργής συνδρομής για εισιτήριο μέλους.', 'iw-theme' ), [ 'status' => 400 ] );
                }

                $verification = self::verify_cashier_member_card( $member_card_id );
                if ( is_wp_error( $verification ) ) {
                    return $verification;
                }

                if ( empty( $verification['valid'] ) ) {
                    $reason = (string) ( $verification['subscription_reason'] ?? $verification['error'] ?? __( 'Δεν βρέθηκε ενεργή συνδρομή για αυτό το μέλος.', 'iw-theme' ) );
                    return new WP_Error( 'iw_cashier_member_subscription_invalid', $reason, [ 'status' => 400 ] );
                }

                $member_counts_by_card[ $member_card_id ] = ( $member_counts_by_card[ $member_card_id ] ?? 0 ) + 1;
                if ( $member_counts_by_card[ $member_card_id ] > self::MEMBER_TICKET_SLOT_LIMIT ) {
                    return new WP_Error(
                        'iw_cashier_member_companion_limit',
                        __( 'Το μέλος μπορεί να έχει μόνο έναν συνοδό για το ίδιο slot.', 'iw-theme' ),
                        [ 'status' => 400 ]
                    );
                }

                $user = is_array( $verification['user'] ?? null ) ? $verification['user'] : [];
                $subscription = is_array( $verification['subscription'] ?? null ) ? $verification['subscription'] : [];
                $member_name = trim( (string) ( $user['name'] ?? '' ) );

                if ( $member_name !== '' && trim( (string) ( $visitors[ $index ]['first'] ?? '' ) ) === '' && trim( (string) ( $visitors[ $index ]['last'] ?? '' ) ) === '' ) {
                    $parts = preg_split( '/\s+/', $member_name ) ?: [];
                    $visitors[ $index ]['first'] = sanitize_text_field( (string) array_shift( $parts ) );
                    $visitors[ $index ]['last']  = sanitize_text_field( trim( implode( ' ', $parts ) ) );
                }

                $visitors[ $index ]['member_card_id'] = (string) ( $user['card_id'] ?? $member_card_id );
                $visitors[ $index ]['member_user_id'] = absint( $user['id'] ?? 0 );
                $visitors[ $index ]['member_subscription_id'] = absint( $subscription['id'] ?? 0 );
                $visitors[ $index ]['member_name'] = $member_name;
                $visitors[ $index ]['member_role'] = $member_counts_by_card[ $member_card_id ] > 1 ? 'companion' : 'member';
            } else {
                unset(
                    $visitors[ $index ]['member_card_id'],
                    $visitors[ $index ]['member_user_id'],
                    $visitors[ $index ]['member_subscription_id'],
                    $visitors[ $index ]['member_name'],
                    $visitors[ $index ]['member_role']
                );
            }

            $visitors[ $index ]['category-name'] = $allowed_types[ $type ]['label'] !== '' ? $allowed_types[ $type ]['label'] : $type;
            $visitors[ $index ]['price']         = (float) $allowed_types[ $type ]['price'];
        }

        foreach ( $counts_by_type as $type => $count ) {
            $cat_min = (int) ( $allowed_types[ $type ]['min_tickets'] ?? 0 );
            $cat_max = (int) ( $allowed_types[ $type ]['max_tickets'] ?? 0 );

            if ( $cat_min > 0 && $count < $cat_min ) {
                return new WP_Error( 'iw_cashier_ticket_type_min', sprintf( __( 'Για τον τύπο "%s" πρέπει να επιλέξετε τουλάχιστον %s.', 'iw-theme' ), $type, self::ticket_count_label( $cat_min ) ), [ 'status' => 400 ] );
            }

            if ( $cat_max > 0 && $count > $cat_max ) {
                return new WP_Error( 'iw_cashier_ticket_type_max', sprintf( __( 'Για τον τύπο "%s" μπορείτε να επιλέξετε έως %s.', 'iw-theme' ), $type, self::ticket_count_label( $cat_max ) ), [ 'status' => 400 ] );
            }
        }

        return $visitors;
    }

    protected static function normalize_member_card_id( $value ): string {
        if ( class_exists( 'IW_Member_Card' ) && method_exists( 'IW_Member_Card', 'normalize_member_card_id' ) ) {
            return IW_Member_Card::normalize_member_card_id( $value );
        }

        return trim( sanitize_text_field( (string) $value ) );
    }

    protected static function member_ticket_type_codes(): array {
        $codes = (array) apply_filters( 'iw_cashier_member_ticket_type_codes', [ self::MEMBER_TICKET_TYPE ] );
        $codes = array_map(
            static function ( $code ): string {
                return sanitize_key( (string) $code );
            },
            $codes
        );

        return array_values( array_filter( array_unique( $codes ) ) );
    }

    protected static function is_member_ticket_type( string $type ): bool {
        return in_array( sanitize_key( $type ), self::member_ticket_type_codes(), true );
    }

    protected static function verify_cashier_member_card( string $member_card_id ) {
        if ( $member_card_id === '' ) {
            return new WP_Error( 'iw_cashier_member_card_missing', __( 'Λείπει ο αριθμός κάρτας μέλους.', 'iw-theme' ), [ 'status' => 400 ] );
        }

        if ( ! class_exists( 'IW_Member_Card' ) || ! method_exists( 'IW_Member_Card', 'iw_verify_member_card' ) ) {
            return new WP_Error( 'iw_cashier_member_card_unavailable', __( 'Ο έλεγχος κάρτας μέλους δεν είναι διαθέσιμος.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $request = new WP_REST_Request( 'GET', '/' . self::REST_NS . '/verify-member-card' );
        $request->set_param( 'member-card-id', $member_card_id );
        $response = IW_Member_Card::iw_verify_member_card( $request );

        if ( $response instanceof WP_Error ) {
            return $response;
        }

        if ( $response instanceof WP_REST_Response ) {
            $response = $response->get_data();
        }

        if ( ! is_array( $response ) ) {
            return new WP_Error( 'iw_cashier_member_card_invalid_response', __( 'Δεν ήταν δυνατός ο έλεγχος της κάρτας μέλους.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        return $response;
    }

    protected static function validate_member_ticket_slot_limits( int $ticket_id, string $day, string $time, array $visitors ) {
        $counts_by_card = [];

        foreach ( $visitors as $visitor ) {
            $type = isset( $visitor['category-id'] ) ? sanitize_key( (string) $visitor['category-id'] ) : '';
            if ( ! self::is_member_ticket_type( $type ) ) {
                continue;
            }

            $member_card_id = self::normalize_member_card_id( $visitor['member_card_id'] ?? '' );
            if ( $member_card_id === '' ) {
                return new WP_Error( 'iw_cashier_member_card_required', __( 'Απαιτείται έλεγχος ενεργής συνδρομής για εισιτήριο μέλους.', 'iw-theme' ), [ 'status' => 400 ] );
            }

            $counts_by_card[ $member_card_id ] = ( $counts_by_card[ $member_card_id ] ?? 0 ) + 1;
            if ( $counts_by_card[ $member_card_id ] > self::MEMBER_TICKET_SLOT_LIMIT ) {
                return new WP_Error(
                    'iw_cashier_member_companion_limit',
                    __( 'Το μέλος μπορεί να έχει μόνο έναν συνοδό για το ίδιο slot.', 'iw-theme' ),
                    [ 'status' => 400 ]
                );
            }
        }

        foreach ( $counts_by_card as $member_card_id => $current_count ) {
            $issued_count = self::count_issued_member_tickets_for_slot( $ticket_id, $day, $time, (string) $member_card_id );
            if ( $issued_count + (int) $current_count <= self::MEMBER_TICKET_SLOT_LIMIT ) {
                continue;
            }

            $message = $issued_count >= self::MEMBER_TICKET_SLOT_LIMIT
                ? __( 'Το μέλος έχει ήδη εκδώσει εισιτήριο μέλους και συνοδό για αυτό το slot.', 'iw-theme' )
                : __( 'Το μέλος μπορεί να έχει μόνο έναν συνοδό για το ίδιο slot.', 'iw-theme' );

            return new WP_Error(
                'iw_cashier_member_slot_limit_reached',
                $message,
                [
                    'status'            => 400,
                    'member_card_id'    => (string) $member_card_id,
                    'issued_count'      => $issued_count,
                    'requested_count'   => (int) $current_count,
                    'allowed_per_slot'  => self::MEMBER_TICKET_SLOT_LIMIT,
                ]
            );
        }

        return true;
    }

    protected static function member_ticket_slot_start( string $day, string $time ): string {
        $time_norm = str_replace( '-', ':', $time );
        if ( preg_match( '/^\d{2}:\d{2}:\d{2}$/', $time_norm ) ) {
            $time_norm = substr( $time_norm, 0, 5 );
        }

        return $day . ' ' . $time_norm . ':00';
    }

    protected static function count_issued_member_tickets_for_slot( int $ticket_id, string $day, string $time, string $member_card_id ): int {
        global $wpdb;

        $table = $wpdb->prefix . 'iw_tickets';
        $slot_start = self::member_ticket_slot_start( $day, $time );
        $like_string = '%' . $wpdb->esc_like( '"member_card_id":"' . $member_card_id . '"' ) . '%';
        $like_number = is_numeric( $member_card_id )
            ? '%' . $wpdb->esc_like( '"member_card_id":' . (int) $member_card_id ) . '%'
            : $like_string;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(1)
                 FROM {$table}
                 WHERE post_id = %d
                   AND slot_start = %s
                   AND (status = 'valid' OR status = 'used' OR status = '' OR status = '0' OR status IS NULL OR UPPER(status) = 'NULL')
                   AND (meta_json LIKE %s OR meta_json LIKE %s)",
                $ticket_id,
                $slot_start,
                $like_string,
                $like_number
            )
        );
    }

    protected static function cashier_delivery_methods_from_request( WP_REST_Request $request ): array {
        $raw = $request->get_param( 'delivery_methods' );

        if ( is_string( $raw ) ) {
            $decoded = json_decode( wp_unslash( $raw ), true );
            $raw     = is_array( $decoded ) ? $decoded : explode( ',', $raw );
        }

        $allowed = [ 'print', 'email', 'sms' ];
        $methods = [];

        foreach ( (array) $raw as $method ) {
            $method = sanitize_key( (string) $method );
            if ( in_array( $method, $allowed, true ) && ! in_array( $method, $methods, true ) ) {
                $methods[] = $method;
            }
        }

        return $methods ?: [ 'print' ];
    }

    protected static function cashier_delivery_emails_from_request( WP_REST_Request $request ): array {
        $raw = $request->get_param( 'customer_emails' );

        if ( $raw === null || $raw === '' ) {
            $raw = $request->get_param( 'customer_email' );
        }

        if ( is_string( $raw ) ) {
            $decoded = json_decode( wp_unslash( $raw ), true );
            $raw     = is_array( $decoded ) ? $decoded : [ $raw ];
        }

        $emails = [];
        foreach ( (array) $raw as $email_group ) {
            $parts = is_array( $email_group )
                ? $email_group
                : preg_split( '/[\s,;\x{037E}]+/u', (string) $email_group, -1, PREG_SPLIT_NO_EMPTY );

            foreach ( (array) $parts as $email ) {
                $email = sanitize_email( (string) $email );
                if ( $email !== '' && is_email( $email ) && ! in_array( $email, $emails, true ) ) {
                    $emails[] = $email;
                }
            }
        }

        return $emails;
    }

    protected static function cashier_emails_from_meta( $raw, string $fallback = '' ): array {
        if ( is_string( $raw ) ) {
            $decoded = json_decode( $raw, true );
            $raw     = is_array( $decoded ) ? $decoded : [ $raw ];
        }

        $emails = [];
        foreach ( (array) $raw as $email_group ) {
            $parts = is_array( $email_group )
                ? $email_group
                : preg_split( '/[\s,;\x{037E}]+/u', (string) $email_group, -1, PREG_SPLIT_NO_EMPTY );

            foreach ( (array) $parts as $email ) {
                $email = sanitize_email( (string) $email );
                if ( $email !== '' && is_email( $email ) && ! in_array( $email, $emails, true ) ) {
                    $emails[] = $email;
                }
            }
        }

        $fallback = sanitize_email( $fallback );
        if ( $fallback !== '' && is_email( $fallback ) && ! in_array( $fallback, $emails, true ) ) {
            $emails[] = $fallback;
        }

        return $emails;
    }

    protected static function cashier_order_delivery_context( $order ): array {
        if ( ! $order ) {
            return [];
        }

        $raw_methods = $order->get_meta( '_iw_cashier_delivery_methods', true );
        if ( is_string( $raw_methods ) ) {
            $decoded     = json_decode( $raw_methods, true );
            $raw_methods = is_array( $decoded ) ? $decoded : explode( ',', $raw_methods );
        }

        $allowed_methods = [ 'print', 'email', 'sms' ];
        $methods         = [];
        foreach ( (array) $raw_methods as $method ) {
            $method = sanitize_key( (string) $method );
            if ( in_array( $method, $allowed_methods, true ) && ! in_array( $method, $methods, true ) ) {
                $methods[] = $method;
            }
        }
        if ( empty( $methods ) ) {
            $methods[] = 'print';
        }

        $emails = self::cashier_emails_from_meta(
            $order->get_meta( '_iw_cashier_delivery_emails', true ),
            (string) ( $order->get_meta( '_iw_cashier_delivery_email', true ) ?: $order->get_billing_email() )
        );
        $phone = (string) ( $order->get_meta( '_iw_cashier_delivery_phone', true ) ?: $order->get_billing_phone() );
        $newsletter_opt_in = (bool) (
            $order->get_meta( '_iw_cashier_newsletter_opt_in', true )
            || $order->get_meta( '_subscribe_newsletter', true )
        );
        $membership_invite_opt_in = (bool) $order->get_meta( '_iw_cashier_membership_invite_opt_in', true );

        return [
            'delivery' => [
                'methods'                  => $methods,
                'emails'                   => $emails,
                'email'                    => (string) ( $emails[0] ?? '' ),
                'phone'                    => $phone,
                'newsletter_opt_in'        => $newsletter_opt_in,
                'membership_invite_opt_in' => $membership_invite_opt_in,
            ],
            'delivery_methods'         => $methods,
            'customer_emails'          => $emails,
            'customer_email'           => (string) ( $emails[0] ?? '' ),
            'customer_phone'           => $phone,
            'newsletter_opt_in'        => $newsletter_opt_in,
            'membership_invite_opt_in' => $membership_invite_opt_in,
        ];
    }

    protected static function cashier_subscribe_newsletter_for_order( $order ): void {
        if ( ! $order || ! $order->get_meta( '_iw_cashier_newsletter_opt_in', true ) ) {
            return;
        }

        $emails = self::cashier_emails_from_meta(
            $order->get_meta( '_iw_cashier_newsletter_emails', true ),
            (string) $order->get_billing_email()
        );
        if ( empty( $emails ) ) {
            return;
        }

        $api_key = trim( (string) get_option( 'iw_form_submissions_mailchimp_api_key' ) );
        $list_id = trim( (string) get_option( 'iw_form_submissions_mailchimp_registration_list' ) );
        $parts   = explode( '-', $api_key );
        $dc      = count( $parts ) === 2 ? $parts[1] : '';

        if ( $api_key === '' || $list_id === '' || $dc === '' ) {
            $order->update_meta_data( '_iw_cashier_newsletter_mailchimp_status', 'missing_config' );
            $order->save();
            return;
        }

        $results = [];
        $success_count = 0;
        foreach ( $emails as $email ) {
            $subscriber_hash = md5( strtolower( $email ) );
            $url             = sprintf(
                'https://%s.api.mailchimp.com/3.0/lists/%s/members/%s',
                rawurlencode( $dc ),
                rawurlencode( $list_id ),
                $subscriber_hash
            );
            $response        = wp_remote_request(
                $url,
                [
                    'method'  => 'PUT',
                    'timeout' => 8,
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'apikey ' . $api_key,
                    ],
                    'body'    => wp_json_encode(
                        [
                            'email_address' => $email,
                            'status'        => 'subscribed',
                            'status_if_new' => 'subscribed',
                        ]
                    ),
                ]
            );

            if ( is_wp_error( $response ) ) {
                $results[ $email ] = [
                    'success' => false,
                    'error'   => $response->get_error_message(),
                ];
                continue;
            }

            $code              = (int) wp_remote_retrieve_response_code( $response );
            $success           = $code >= 200 && $code < 300;
            if ( $success ) {
                $success_count++;
            }
            $results[ $email ] = [
                'success' => $success,
                'code'    => $code,
            ];
        }

        $status = $success_count === count( $emails )
            ? 'synced'
            : ( $success_count > 0 ? 'partial' : 'failed' );
        $order->update_meta_data( '_iw_cashier_newsletter_mailchimp_status', $status );
        $order->update_meta_data( '_iw_cashier_newsletter_mailchimp_synced_at', time() );
        $order->update_meta_data( '_iw_cashier_newsletter_mailchimp_result', wp_json_encode( $results ) );
        $order->save();
    }

    protected static function cashier_member_password_setup_url( int $user_id, string $reset_key ): string {
        return esc_url_raw(
            add_query_arg(
                [
                    'reset-password-key' => $reset_key,
                    'id'                 => $user_id,
                    'welcome'            => 1,
                ],
                home_url( '/' )
            )
        );
    }

    protected static function cashier_unique_member_username( string $email ): string {
        $email_parts = explode( '@', $email );
        $base        = sanitize_user( (string) ( $email_parts[0] ?? '' ), true );
        if ( $base === '' ) {
            $base = 'member';
        }

        $username = $base;
        $counter  = 1;
        while ( username_exists( $username ) ) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    protected static function cashier_find_or_create_member_user( string $email ) {
        $existing_id = email_exists( $email );
        if ( $existing_id ) {
            return [
                'user_id' => (int) $existing_id,
                'created' => false,
            ];
        }

        $user_id = wp_insert_user(
            [
                'user_login'   => self::cashier_unique_member_username( $email ),
                'user_email'   => $email,
                'user_pass'    => wp_generate_password( 24, true ),
                'role'         => 'subscriber',
                'display_name' => $email,
            ]
        );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        return [
            'user_id' => (int) $user_id,
            'created' => true,
        ];
    }

    protected static function cashier_register_members_for_order( $order ): void {
        if ( ! $order || ! $order->get_meta( '_iw_cashier_membership_invite_opt_in', true ) ) {
            return;
        }

        if ( (int) $order->get_meta( '_iw_cashier_membership_invite_email_sent', true ) > 0 ) {
            return;
        }

        $emails = self::cashier_emails_from_meta(
            $order->get_meta( '_iw_cashier_membership_invite_emails', true ),
            (string) $order->get_billing_email()
        );
        if ( empty( $emails ) ) {
            return;
        }

        $site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $subject   = sprintf( __( 'Ορίστε κωδικό για τον λογαριασμό σας στο %s', 'iw-theme' ), $site_name );
        $mailer    = function_exists( 'WC' ) ? WC()->mailer() : null;

        $results = [];
        $success_count = 0;
        foreach ( $emails as $email ) {
            $member = self::cashier_find_or_create_member_user( $email );
            if ( is_wp_error( $member ) ) {
                $results[ $email ] = [
                    'success' => false,
                    'error'   => $member->get_error_message(),
                ];
                continue;
            }

            $user = get_user_by( 'id', (int) $member['user_id'] );
            if ( ! $user ) {
                $results[ $email ] = [
                    'success' => false,
                    'error'   => 'missing_user',
                ];
                continue;
            }

            $reset_key = get_password_reset_key( $user );
            if ( is_wp_error( $reset_key ) ) {
                $results[ $email ] = [
                    'success' => false,
                    'user_id' => (int) $member['user_id'],
                    'created' => (bool) $member['created'],
                    'error'   => $reset_key->get_error_message(),
                ];
                continue;
            }

            $setup_url = self::cashier_member_password_setup_url( (int) $member['user_id'], $reset_key );
            $message   = '<p>' . esc_html__( 'Ευχαριστούμε για την επίσκεψή σας.', 'iw-theme' ) . '</p>';
            $message  .= '<p>' . ( $member['created']
                ? esc_html__( 'Δημιουργήσαμε έναν λογαριασμό για αυτό το email. Πατήστε το κουμπί παρακάτω για να ορίσετε τον κωδικό σας.', 'iw-theme' )
                : esc_html__( 'Υπάρχει ήδη λογαριασμός για αυτό το email. Πατήστε το κουμπί παρακάτω για να ορίσετε νέο κωδικό.', 'iw-theme' )
            ) . '</p>';
            $message  .= '<p><a href="' . esc_url( $setup_url ) . '" class="btn" style="display:inline-block;padding:14px 22px;background:#173276;color:#FFFFFF;text-decoration:none;border-radius:10px;">' . esc_html__( 'Ορισμός κωδικού', 'iw-theme' ) . '</a></p>';
            $message  .= '<p style="color:#173276;font-size:13px;">' . esc_html__( 'Αν δεν ζητήσατε αυτή την εγγραφή, μπορείτε να αγνοήσετε αυτό το email.', 'iw-theme' ) . '</p>';
            $wrapped_message = $mailer ? $mailer->wrap_message( $subject, $message ) : $message;

            $sent = $mailer
                ? $mailer->send( $email, $subject, $wrapped_message, [ 'Content-Type: text/html; charset=UTF-8' ] )
                : wp_mail( $email, $subject, $wrapped_message, [ 'Content-Type: text/html; charset=UTF-8' ] );

            if ( $sent ) {
                $success_count++;
            }
            $results[ $email ] = [
                'success' => (bool) $sent,
                'user_id' => (int) $member['user_id'],
                'created' => (bool) $member['created'],
            ];
        }

        $status = $success_count === count( $emails )
            ? 'sent'
            : ( $success_count > 0 ? 'partial' : 'failed' );

        $order->update_meta_data( '_iw_cashier_membership_invite_email_status', $status );
        $order->update_meta_data( '_iw_cashier_membership_invite_email_result', wp_json_encode( $results ) );
        if ( $success_count > 0 ) {
            $order->update_meta_data( '_iw_cashier_membership_invite_email_sent', time() );
        }
        $order->save();
    }

    protected static function cashier_normalize_sms_phone( string $phone ): string {
        if ( class_exists( 'IW_Custom_Auth_Activation' ) && method_exists( 'IW_Custom_Auth_Activation', 'normalize_phone' ) ) {
            return (string) IW_Custom_Auth_Activation::normalize_phone( $phone );
        }

        $phone = preg_replace( '/[\s\-\.\(\)]/', '', trim( $phone ) );
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

    protected static function cashier_is_valid_sms_phone( string $phone ): bool {
        if ( class_exists( 'IW_Custom_Auth_Activation' ) && method_exists( 'IW_Custom_Auth_Activation', 'is_valid_phone' ) ) {
            return (bool) IW_Custom_Auth_Activation::is_valid_phone( $phone );
        }

        return (bool) preg_match( '/^\+[1-9]\d{7,14}$/', $phone );
    }

    protected static function cashier_twilio_messaging_config(): array {
        $field = static function ( string $name ): string {
            return function_exists( 'get_field' ) ? trim( (string) get_field( $name, 'option' ) ) : '';
        };

        $config = [
            'account_sid'           => defined( 'IW_CASHIER_TWILIO_ACCOUNT_SID' ) ? (string) IW_CASHIER_TWILIO_ACCOUNT_SID : $field( 'iw_cashier_twilio_account_sid' ),
            'auth_token'            => defined( 'IW_CASHIER_TWILIO_AUTH_TOKEN' ) ? (string) IW_CASHIER_TWILIO_AUTH_TOKEN : $field( 'iw_cashier_twilio_auth_token' ),
            'messaging_service_sid' => defined( 'IW_CASHIER_TWILIO_MESSAGING_SERVICE_SID' ) ? (string) IW_CASHIER_TWILIO_MESSAGING_SERVICE_SID : $field( 'iw_cashier_twilio_messaging_service_sid' ),
            'from'                  => defined( 'IW_CASHIER_TWILIO_FROM' ) ? (string) IW_CASHIER_TWILIO_FROM : $field( 'iw_cashier_twilio_from' ),
        ];

        if ( class_exists( 'IW_Custom_Auth_Activation' ) && method_exists( 'IW_Custom_Auth_Activation', 'get_twilio_config' ) ) {
            $verify_config = IW_Custom_Auth_Activation::get_twilio_config();
            if ( $config['account_sid'] === '' ) {
                $config['account_sid'] = (string) ( $verify_config['account_sid'] ?? '' );
            }
            if ( $config['auth_token'] === '' ) {
                $config['auth_token'] = (string) ( $verify_config['auth_token'] ?? '' );
            }
        }

        return (array) apply_filters( 'iw_cashier_twilio_messaging_config', $config );
    }

    protected static function cashier_send_twilio_message( string $to, string $body ) {
        $config = self::cashier_twilio_messaging_config();

        $account_sid = trim( (string) ( $config['account_sid'] ?? '' ) );
        $auth_token  = trim( (string) ( $config['auth_token'] ?? '' ) );
        $service_sid = trim( (string) ( $config['messaging_service_sid'] ?? '' ) );
        $from        = trim( (string) ( $config['from'] ?? '' ) );

        if ( $account_sid === '' || $auth_token === '' || ( $service_sid === '' && $from === '' ) ) {
            return new WP_Error( 'iw_cashier_twilio_missing_config', __( 'Λείπουν οι ρυθμίσεις Twilio Messaging.', 'iw-theme' ) );
        }

        $payload = [
            'To'   => $to,
            'Body' => $body,
        ];
        if ( $service_sid !== '' ) {
            $payload['MessagingServiceSid'] = $service_sid;
        } else {
            $payload['From'] = $from;
        }

        $response = wp_remote_post(
            sprintf( 'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', rawurlencode( $account_sid ) ),
            [
                'timeout' => 15,
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode( $account_sid . ':' . $auth_token ),
                ],
                'body'    => $payload,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = (int) wp_remote_retrieve_response_code( $response );
        $data        = json_decode( (string) wp_remote_retrieve_body( $response ), true );
        $data        = is_array( $data ) ? $data : [];

        if ( $status_code < 200 || $status_code >= 300 ) {
            return new WP_Error(
                'iw_cashier_twilio_send_failed',
                (string) ( $data['message'] ?? __( 'Δεν ήταν δυνατή η αποστολή SMS.', 'iw-theme' ) ),
                [
                    'status_code' => $status_code,
                    'data'        => $data,
                ]
            );
        }

        return [
            'status_code' => $status_code,
            'data'        => $data,
        ];
    }

    protected static function cashier_send_ticket_sms_for_order( $order ): void {
        if ( ! $order || (int) $order->get_meta( '_iw_cashier_sms_sent', true ) > 0 ) {
            return;
        }

        $delivery = self::cashier_order_delivery_context( $order );
        $methods  = (array) ( $delivery['delivery']['methods'] ?? [] );
        if ( ! in_array( 'sms', $methods, true ) ) {
            return;
        }

        $phone = self::cashier_normalize_sms_phone( (string) ( $delivery['delivery']['phone'] ?? '' ) );
        if ( ! self::cashier_is_valid_sms_phone( $phone ) ) {
            $order->update_meta_data( '_iw_cashier_sms_status', 'invalid_phone' );
            $order->save();
            return;
        }

        $public_url = self::cashier_public_order_url( $order );
        if ( $public_url === '' ) {
            $order->update_meta_data( '_iw_cashier_sms_status', 'missing_public_url' );
            $order->save();
            return;
        }

        $tickets = self::get_order_tickets( (int) $order->get_id() );
        $context = self::cashier_order_ticket_item_context( $order, $tickets );
        $message = (string) apply_filters(
            'iw_cashier_ticket_sms_message',
            sprintf(
                '%s: %s',
                class_exists( 'IW_Ticketing' ) ? IW_Ticketing::get_option( 'cashier_sms_prefix', __( 'Tickets', 'iw-theme' ) ) : __( 'Tickets', 'iw-theme' ),
                $public_url
            ),
            $order,
            $public_url,
            (string) ( $context['title'] ?? '' )
        );

        $order->update_meta_data( '_iw_cashier_sms_phone', $phone );
        $order->update_meta_data( '_iw_cashier_sms_public_order_url', esc_url_raw( $public_url ) );
        $order->update_meta_data( '_iw_cashier_sms_message', $message );

        $result = self::cashier_send_twilio_message( $phone, $message );
        if ( is_wp_error( $result ) ) {
            $order->update_meta_data( '_iw_cashier_sms_status', 'failed' );
            $order->update_meta_data( '_iw_cashier_sms_error', $result->get_error_message() );
            $order->update_meta_data( '_iw_cashier_sms_result', wp_json_encode( $result->get_error_data() ) );
            $order->save();
            return;
        }

        $order->update_meta_data( '_iw_cashier_sms_status', 'sent' );
        $order->update_meta_data( '_iw_cashier_sms_sent', time() );
        $order->update_meta_data( '_iw_cashier_sms_result', wp_json_encode( $result ) );
        $order->save();
    }

    protected static function create_cashier_order( array $prepared, string $method, array $method_config, WP_REST_Request $request ) {
        if ( ! function_exists( 'wc_create_order' ) || ! class_exists( 'CPT_As_Product' ) ) {
            return new WP_Error( 'iw_cashier_woocommerce_missing', __( 'Το WooCommerce δεν είναι διαθέσιμο.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $product_id = CPT_As_Product::get_cpt_virtual_product_id();
        $product    = $product_id > 0 && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
        if ( ! $product ) {
            return new WP_Error( 'iw_cashier_virtual_product_missing', __( 'Δεν έχει ρυθμιστεί το virtual product εισιτηρίων.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        try {
            $order = wc_create_order( [ 'created_via' => 'iw_cashier' ] );
        } catch ( Throwable $e ) {
            return new WP_Error( 'iw_cashier_order_create_failed', __( 'Δεν ήταν δυνατή η δημιουργία παραγγελίας.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        if ( ! $order ) {
            return new WP_Error( 'iw_cashier_order_create_failed', __( 'Δεν ήταν δυνατή η δημιουργία παραγγελίας.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        if ( method_exists( $order, 'set_created_via' ) ) {
            $order->set_created_via( 'iw_cashier' );
        }

        $customer_id = absint( $request->get_param( 'customer_id' ) );
        if ( $customer_id > 0 ) {
            $order->set_customer_id( $customer_id );
        }

        $delivery_emails = self::cashier_delivery_emails_from_request( $request );
        $billing_email   = (string) ( $delivery_emails[0] ?? '' );
        if ( $billing_email !== '' && is_email( $billing_email ) ) {
            $order->set_billing_email( $billing_email );
        }

        $billing_phone = function_exists( 'wc_sanitize_phone_number' )
            ? wc_sanitize_phone_number( (string) $request->get_param( 'customer_phone' ) )
            : sanitize_text_field( (string) $request->get_param( 'customer_phone' ) );
        if ( $billing_phone !== '' && method_exists( $order, 'set_billing_phone' ) ) {
            $order->set_billing_phone( $billing_phone );
        }

        $delivery_methods         = self::cashier_delivery_methods_from_request( $request );
        $newsletter_opt_in        = filter_var( $request->get_param( 'subscribe_newsletter' ), FILTER_VALIDATE_BOOLEAN );
        $membership_invite_opt_in = filter_var( $request->get_param( 'send_membership_email' ), FILTER_VALIDATE_BOOLEAN );

        $first_name = sanitize_text_field( (string) $request->get_param( 'customer_first_name' ) );
        $last_name  = sanitize_text_field( (string) $request->get_param( 'customer_last_name' ) );
        if ( $first_name !== '' ) {
            $order->set_billing_first_name( $first_name );
        }
        if ( $last_name !== '' ) {
            $order->set_billing_last_name( $last_name );
        }

        $item = new WC_Order_Item_Product();
        $item->set_product( $product );
        $item->set_product_id( $product_id );
        $item->set_name( $prepared['title'] );
        $item->set_quantity( 1 );
        $item->set_subtotal( (float) $prepared['total_price'] );
        $item->set_total( (float) $prepared['total_price'] );
        $item->add_meta_data( 'iw_item_type', 'tickets', true );
        $item->add_meta_data( 'iw_title', (string) $prepared['title'], true );
        $item->add_meta_data( 'iw_price', (float) $prepared['total_price'], true );
        $item->add_meta_data( 'tickets_for_id', (int) $prepared['ticket_id'], true );
        $item->add_meta_data( 'tickets_day', (string) $prepared['day'], true );
        $item->add_meta_data( 'tickets_time', (string) $prepared['time'], true );
        $item->add_meta_data( 'tickets_channel', self::CHANNEL, true );
        $item->add_meta_data( 'tickets_total', (int) $prepared['requested_tickets'], true );
        $item->add_meta_data( 'tickets_visitors', wp_json_encode( $prepared['visitors'] ), true );

        $order->add_item( $item );
        $order->set_payment_method( (string) ( $method_config['gateway_id'] ?? $method ) );
        $order->set_payment_method_title( (string) ( $method_config['gateway_title'] ?? $method ) );
        $order->update_meta_data( '_iw_cashier_channel', self::CHANNEL );
        $order->update_meta_data( '_iw_cashier_user_id', get_current_user_id() );
        $order->update_meta_data( '_iw_cashier_payment_method', $method );
        $order->update_meta_data( '_iw_cashier_payment_provider', (string) ( $method_config['provider'] ?? '' ) );
        $order->update_meta_data( '_iw_cashier_pos_reference', sanitize_text_field( (string) $request->get_param( 'pos_reference' ) ) );
        $order->update_meta_data( '_iw_cashier_terminal_id', sanitize_text_field( (string) $request->get_param( 'terminal_id' ) ) );
        $order->update_meta_data( '_iw_cashier_building_id', absint( $request->get_param( 'building_id' ) ) );
        $order->update_meta_data( '_iw_cashier_building_name', sanitize_text_field( (string) $request->get_param( 'building_name' ) ) );
        $order->update_meta_data( '_iw_cashier_delivery_methods', wp_json_encode( $delivery_methods ) );
        $order->update_meta_data( '_iw_cashier_delivery_email', $billing_email );
        $order->update_meta_data( '_iw_cashier_delivery_emails', wp_json_encode( $delivery_emails ) );
        $order->update_meta_data( '_iw_cashier_delivery_phone', $billing_phone );

        if ( $newsletter_opt_in && $billing_email !== '' && is_email( $billing_email ) ) {
            $order->update_meta_data( '_subscribe_newsletter', '1' );
            $order->update_meta_data( '_iw_cashier_newsletter_opt_in', '1' );
            $order->update_meta_data( '_iw_cashier_newsletter_emails', wp_json_encode( $delivery_emails ) );
        }

        if ( $membership_invite_opt_in && $billing_email !== '' && is_email( $billing_email ) ) {
            $order->update_meta_data( '_iw_cashier_membership_invite_opt_in', '1' );
            $order->update_meta_data( '_iw_cashier_membership_invite_emails', wp_json_encode( $delivery_emails ) );
        }

        if ( defined( 'IW_SCANNER_META_LAST_BUILDING_ID' ) && absint( $request->get_param( 'building_id' ) ) > 0 ) {
            update_user_meta( get_current_user_id(), IW_SCANNER_META_LAST_BUILDING_ID, absint( $request->get_param( 'building_id' ) ) );
        }
        if ( defined( 'IW_SCANNER_META_LAST_BUILDING_NAME' ) && trim( (string) $request->get_param( 'building_name' ) ) !== '' ) {
            update_user_meta( get_current_user_id(), IW_SCANNER_META_LAST_BUILDING_NAME, sanitize_text_field( (string) $request->get_param( 'building_name' ) ) );
        }

        $order->calculate_totals( false );
        $order->set_status( 'pending', __( 'Cashier order created.', 'iw-theme' ) );
        $order->save();

        return $order;
    }

    protected static function process_payment( $order, array $prepared, string $method, array $method_config, WP_REST_Request $request ) {
        $provider = isset( $method_config['provider'] ) ? sanitize_key( (string) $method_config['provider'] ) : '';

        $context = [
            'order_id'      => (int) $order->get_id(),
            'amount'        => (float) $prepared['total_price'],
            'currency'      => $order->get_currency(),
            'method'        => $method,
            'provider'      => $provider,
            'ticket_id'     => (int) $prepared['ticket_id'],
            'terminal_id'   => sanitize_text_field( (string) $request->get_param( 'terminal_id' ) ),
            'pos_reference' => sanitize_text_field( (string) $request->get_param( 'pos_reference' ) ),
            'building_id'   => absint( $request->get_param( 'building_id' ) ),
            'building_name' => sanitize_text_field( (string) $request->get_param( 'building_name' ) ),
            'request'       => $request,
        ];

        $custom_result = apply_filters( 'iw_cashier_process_pos_payment', null, $context, $order, $prepared, $method_config );
        if ( $custom_result !== null ) {
            if ( is_wp_error( $custom_result ) ) {
                return $custom_result;
            }
            return is_array( $custom_result ) ? $custom_result : [ 'status' => 'approved' ];
        }

        if ( $method === 'cash' ) {
            return [
                'status'         => 'approved',
                'provider'       => 'cash',
                'transaction_id' => 'cashier-cash-' . (int) $order->get_id(),
            ];
        }

        if ( $method === 'pos_manual' ) {
            $reference = sanitize_text_field( (string) $request->get_param( 'pos_reference' ) );
            return [
                'status'         => 'approved',
                'provider'       => 'manual_pos',
                'transaction_id' => $reference !== '' ? $reference : 'cashier-pos-' . (int) $order->get_id(),
                'terminal_id'    => sanitize_text_field( (string) $request->get_param( 'terminal_id' ) ),
            ];
        }

        if ( ! empty( $method_config['api_integration'] ) ) {
            if ( $provider === 'nexi' ) {
                return self::process_nexi_pos_payment( $order, $prepared, $context );
            }

            return new WP_Error(
                'iw_cashier_pos_provider_not_configured',
                sprintf( __( 'Ο POS provider "%s" είναι έτοιμος σαν adapter, αλλά δεν έχει συνδεθεί ακόμα με API credentials/docs.', 'iw-theme' ), $provider ),
                [ 'status' => 501 ]
            );
        }

        return new WP_Error( 'iw_cashier_payment_not_supported', __( 'Ο τρόπος πληρωμής δεν υποστηρίζεται.', 'iw-theme' ), [ 'status' => 400 ] );
    }

    protected static function process_nexi_pos_payment( $order, array $prepared, array $context ) {
        if ( ! self::nexi_pos_is_configured() ) {
            return new WP_Error(
                'iw_cashier_nexi_not_configured',
                __( 'Το Nexi POS API δεν έχει ρυθμιστεί ακόμα.', 'iw-theme' ),
                [ 'status' => 501 ]
            );
        }

        $terminal_id = self::resolve_nexi_terminal_id( (int) ( $context['building_id'] ?? 0 ), (string) ( $context['terminal_id'] ?? '' ) );
        if ( $terminal_id === '' ) {
            return new WP_Error(
                'iw_cashier_nexi_terminal_missing',
                __( 'Λείπει το Nexi terminal ID για το επιλεγμένο μουσείο.', 'iw-theme' ),
                [ 'status' => 400 ]
            );
        }

        $amount = (int) round( (float) $prepared['total_price'] * 100 );
        if ( $amount <= 0 ) {
            return new WP_Error(
                'iw_cashier_nexi_amount_invalid',
                __( 'Η πληρωμή POS απαιτεί ποσό μεγαλύτερο από μηδέν.', 'iw-theme' ),
                [ 'status' => 400 ]
            );
        }

        $external_id = self::get_or_create_nexi_external_id( $order );
        $order->update_meta_data( '_iw_cashier_terminal_id', $terminal_id );
        $order->update_meta_data( '_iw_cashier_nexi_external_id', $external_id );
        $order->save();

        $purchase_payload = [
            'terminal_id'      => $terminal_id,
            'external_id'      => $external_id,
            'currency'         => $order->get_currency() ?: 'EUR',
            'requested_amount' => $amount,
            'metadata'         => [
                'order_id'    => (string) $order->get_id(),
                'building_id' => (string) ( (int) ( $context['building_id'] ?? 0 ) ),
                'channel'     => self::CHANNEL,
            ],
            'options'          => [
                'wait_seconds' => (int) apply_filters( 'iw_cashier_nexi_wait_seconds', 25, $order, $prepared ),
            ],
        ];

        $response = self::nexi_pos_request( 'transaction/purchase', $purchase_payload );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $transaction = isset( $response['transaction'] ) && is_array( $response['transaction'] )
            ? $response['transaction']
            : $response;

        $order->update_meta_data( '_iw_cashier_nexi_purchase_response', wp_json_encode( $transaction ) );
        $order->save();

        $state       = isset( $transaction['state'] ) ? (string) $transaction['state'] : '';
        $result_code = isset( $transaction['result_code'] ) ? (string) $transaction['result_code'] : '';

        if ( $state === 'PROCESSING' || $state === 'AWAITING_CONTINUE' ) {
            return [
                'status'         => 'pending',
                'provider'       => 'nexi',
                'transaction_id' => (string) ( $transaction['id'] ?? $external_id ),
                'external_id'    => $external_id,
                'terminal_id'    => $terminal_id,
                'nexi'           => $transaction,
            ];
        }

        if ( $state !== 'AWAITING_CONFIRM' || $result_code !== 'SUCCESS' ) {
            return new WP_Error(
                'iw_cashier_nexi_payment_failed',
                self::nexi_transaction_error_message( $transaction ),
                [
                    'status'      => 402,
                    'nexi_state'  => $state,
                    'result_code' => $result_code,
                ]
            );
        }

        return [
            'status'           => 'approved',
            'provider'         => 'nexi',
            'transaction_id'   => (string) ( $transaction['id'] ?? $external_id ),
            'external_id'      => $external_id,
            'terminal_id'      => $terminal_id,
            'amount'           => $amount,
            'requires_confirm' => true,
            'nexi'             => $transaction,
        ];
    }

    protected static function confirm_nexi_pos_payment( array $payment_result, string $result_code, string $result_description = '' ) {
        if ( empty( $payment_result['requires_confirm'] ) || ( $payment_result['provider'] ?? '' ) !== 'nexi' ) {
            return true;
        }

        $payload = [
            'terminal_id' => (string) ( $payment_result['terminal_id'] ?? '' ),
            'external_id' => (string) ( $payment_result['external_id'] ?? '' ),
            'result_code' => $result_code,
            'metadata'    => [
                'channel' => self::CHANNEL,
            ],
        ];

        if ( $result_code === 'SUCCESS' && ! empty( $payment_result['amount'] ) ) {
            $payload['captured_amount'] = (int) $payment_result['amount'];
        }

        if ( $result_description !== '' ) {
            $clean_description = preg_replace( '/[^\x20-\x7E]/', '', $result_description );
            $payload['result_description'] = substr( $clean_description !== null ? $clean_description : '', 0, 255 );
        }

        if ( $payload['terminal_id'] === '' || $payload['external_id'] === '' ) {
            return new WP_Error( 'iw_cashier_nexi_confirm_missing_ids', __( 'Λείπουν στοιχεία επιβεβαίωσης Nexi POS.', 'iw-theme' ) );
        }

        return self::nexi_pos_request( 'transaction/confirm', $payload );
    }

    protected static function nexi_pos_is_configured(): bool {
        $credentials = self::nexi_pos_credentials();
        $configured = $credentials['username'] !== '' && $credentials['password'] !== '';

        return (bool) apply_filters( 'iw_cashier_nexi_pos_is_configured', $configured );
    }

    protected static function nexi_pos_has_default_terminal(): bool {
        return self::resolve_nexi_terminal_id( 0, '' ) !== '';
    }

    protected static function nexi_pos_credentials(): array {
        $username = defined( 'IW_NEXI_POS_API_USERNAME' ) ? (string) IW_NEXI_POS_API_USERNAME : (string) getenv( 'IW_NEXI_POS_API_USERNAME' );
        $password = defined( 'IW_NEXI_POS_API_PASSWORD' ) ? (string) IW_NEXI_POS_API_PASSWORD : (string) getenv( 'IW_NEXI_POS_API_PASSWORD' );

        return [
            'username' => trim( $username ),
            'password' => trim( $password ),
        ];
    }

    protected static function nexi_pos_base_url(): string {
        $base = defined( 'IW_NEXI_POS_API_BASE_URL' ) ? (string) IW_NEXI_POS_API_BASE_URL : (string) getenv( 'IW_NEXI_POS_API_BASE_URL' );
        if ( trim( $base ) === '' ) {
            $base = 'https://api.npay.eu/pos/v1/';
        }

        return trailingslashit( (string) apply_filters( 'iw_cashier_nexi_pos_base_url', $base ) );
    }

    protected static function resolve_nexi_terminal_id( int $building_id, string $requested_terminal_id = '' ): string {
        $requested_terminal_id = sanitize_text_field( $requested_terminal_id );
        if ( $requested_terminal_id !== '' ) {
            return $requested_terminal_id;
        }

        $terminal_id = '';
        if ( defined( 'IW_NEXI_POS_TERMINAL_ID' ) ) {
            $terminal_id = (string) IW_NEXI_POS_TERMINAL_ID;
        } elseif ( getenv( 'IW_NEXI_POS_TERMINAL_ID' ) ) {
            $terminal_id = (string) getenv( 'IW_NEXI_POS_TERMINAL_ID' );
        }

        return sanitize_text_field(
            (string) apply_filters( 'iw_cashier_nexi_terminal_id', trim( $terminal_id ), $building_id )
        );
    }

    protected static function get_or_create_nexi_external_id( $order ): string {
        $external_id = sanitize_text_field( (string) $order->get_meta( '_iw_cashier_nexi_external_id', true ) );
        if ( $external_id !== '' ) {
            return $external_id;
        }

        $external_id = sprintf( 'iw-o%d', (int) $order->get_id() );
        return substr( $external_id, 0, 63 );
    }

    protected static function nexi_pos_request( string $path, array $payload ) {
        $credentials = self::nexi_pos_credentials();
        if ( $credentials['username'] === '' || $credentials['password'] === '' ) {
            return new WP_Error( 'iw_cashier_nexi_missing_credentials', __( 'Λείπουν τα Nexi POS API credentials.', 'iw-theme' ), [ 'status' => 501 ] );
        }

        $url = self::nexi_pos_base_url() . ltrim( $path, '/' );
        $response = wp_remote_post(
            $url,
            [
                'timeout' => (int) apply_filters( 'iw_cashier_nexi_request_timeout', 35, $path, $payload ),
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode( $credentials['username'] . ':' . $credentials['password'] ),
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'User-Agent'    => class_exists( 'IW_Ticketing' ) ? IW_Ticketing::get_option( 'cashier_nexi_user_agent' ) : 'IWTickets/1.0 (cms:wordpress)',
                ],
                'body'    => wp_json_encode( $payload ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'iw_cashier_nexi_network_error', $response->get_error_message(), [ 'status' => 502 ] );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = (string) wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! is_array( $data ) ) {
            return new WP_Error( 'iw_cashier_nexi_invalid_response', __( 'Μη έγκυρη απάντηση από το Nexi POS API.', 'iw-theme' ), [ 'status' => 502 ] );
        }

        if ( $code < 200 || $code >= 300 ) {
            $error = isset( $data['error'] ) && is_array( $data['error'] ) ? $data['error'] : [];
            $message = (string) ( $error['description'] ?? $error['code'] ?? __( 'Η πληρωμή Nexi POS απέτυχε.', 'iw-theme' ) );
            return new WP_Error(
                'iw_cashier_nexi_api_error',
                $message,
                [
                    'status' => $code,
                    'nexi'   => $data,
                ]
            );
        }

        return $data;
    }

    protected static function nexi_transaction_error_message( array $transaction ): string {
        $description = isset( $transaction['result_description'] ) ? trim( (string) $transaction['result_description'] ) : '';
        if ( $description !== '' ) {
            return $description;
        }

        $result_code = isset( $transaction['result_code'] ) ? trim( (string) $transaction['result_code'] ) : '';
        if ( $result_code !== '' ) {
            return sprintf( __( 'Η πληρωμή Nexi POS απέτυχε (%s).', 'iw-theme' ), $result_code );
        }

        return __( 'Η πληρωμή Nexi POS δεν εγκρίθηκε.', 'iw-theme' );
    }

    protected static function issue_order_tickets( $order, array $prepared, array $payment_result ) {
        $order_id = (int) $order->get_id();
        $items    = $order->get_items();
        $item_id  = 0;
        $item     = null;

        foreach ( $items as $maybe_item_id => $maybe_item ) {
            if ( (int) $maybe_item->get_meta( 'tickets_for_id', true ) === (int) $prepared['ticket_id'] ) {
                $item_id = (int) $maybe_item_id;
                $item    = $maybe_item;
                break;
            }
        }

        if ( $order_id <= 0 || $item_id <= 0 || ! $item ) {
            return new WP_Error( 'iw_cashier_order_item_missing', __( 'Δεν βρέθηκε γραμμή εισιτηρίων στην παραγγελία.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $inventory_incremented = false;
        try {
            $reserved_inventory = self::reserve_inventory_for_issue( $prepared );
            if ( is_wp_error( $reserved_inventory ) ) {
                return $reserved_inventory;
            }
            $inventory_incremented = true;

            if ( class_exists( 'IW_WC_Tickets_Cart_Manager' ) && method_exists( 'IW_WC_Tickets_Cart_Manager', 'issue_tickets_for_order_item' ) ) {
                IW_WC_Tickets_Cart_Manager::issue_tickets_for_order_item( $order, $order_id, $item_id, $item );
            }
        } catch ( Throwable $e ) {
            if ( $inventory_incremented && class_exists( 'IW_Tickets_DB' ) && method_exists( 'IW_Tickets_DB', 'decrement_booked' ) ) {
                IW_Tickets_DB::decrement_booked( (int) $prepared['ticket_id'], (string) $prepared['day'], (string) $prepared['time'], (int) $prepared['requested_tickets'] );
            }
            return new WP_Error( 'iw_cashier_ticket_issue_failed', __( 'Δεν ήταν δυνατή η έκδοση των εισιτηρίων.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $tickets = self::get_order_item_tickets( $item_id );
        if ( count( $tickets ) < (int) $prepared['requested_tickets'] ) {
            if ( $inventory_incremented && class_exists( 'IW_Tickets_DB' ) && method_exists( 'IW_Tickets_DB', 'decrement_booked' ) ) {
                IW_Tickets_DB::decrement_booked( (int) $prepared['ticket_id'], (string) $prepared['day'], (string) $prepared['time'], (int) $prepared['requested_tickets'] );
            }
            return new WP_Error( 'iw_cashier_ticket_issue_incomplete', __( 'Η έκδοση των εισιτηρίων δεν ολοκληρώθηκε.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        do_action( 'iw_cashier_order_tickets_issued', $order, $tickets, $prepared, $payment_result );

        return [
            'order_item_id' => $item_id,
            'tickets'       => $tickets,
        ];
    }

    protected static function reserve_inventory_for_issue( array $prepared ) {
        if ( ! class_exists( 'IW_Tickets_DB' ) || ! method_exists( 'IW_Tickets_DB', 'increment_booked' ) ) {
            return true;
        }

        global $wpdb;

        $post_id = (int) $prepared['ticket_id'];
        $date    = (string) $prepared['day'];
        $time    = (string) $prepared['time'];
        $qty     = (int) $prepared['requested_tickets'];
        $slot    = isset( $prepared['selected_slot'] ) && is_array( $prepared['selected_slot'] ) ? $prepared['selected_slot'] : [];

        if ( $post_id <= 0 || $date === '' || $time === '' || $qty <= 0 ) {
            return new WP_Error( 'iw_cashier_inventory_invalid', __( 'Μη έγκυρα στοιχεία διαθεσιμότητας.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $time_db = preg_match( '/^\d{2}:\d{2}$/', $time ) ? $time . ':00' : $time;
        $capacity = array_key_exists( 'capacity', $slot ) && is_numeric( $slot['capacity'] ) ? (int) $slot['capacity'] : null;
        $one_booking_per_slot = self::ticket_requires_one_booking_per_slot( $post_id );

        if ( $capacity === null && ! $one_booking_per_slot ) {
            IW_Tickets_DB::increment_booked( $post_id, $date, $time, $qty );
            return true;
        }

        $inventory_table = $wpdb->prefix . 'iw_ticket_slot_inventory';
        $holds_table     = $wpdb->prefix . 'iw_ticket_slot_holds';
        $now             = current_time( 'mysql' );

        $wpdb->query( 'START TRANSACTION' );

        $inserted = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$inventory_table} (post_id, slot_date, slot_time, booked, created_at, updated_at)
                 VALUES (%d, %s, %s, 0, %s, CURRENT_TIMESTAMP)
                 ON DUPLICATE KEY UPDATE booked = booked",
                $post_id,
                $date,
                $time_db,
                $now
            )
        );

        if ( $inserted === false ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'iw_cashier_inventory_lock_failed', __( 'Δεν ήταν δυνατή η δέσμευση διαθεσιμότητας.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $booked = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT booked FROM {$inventory_table} WHERE post_id = %d AND slot_date = %s AND slot_time = %s FOR UPDATE",
                $post_id,
                $date,
                $time_db
            )
        );

        if ( $booked === null ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'iw_cashier_inventory_lock_failed', __( 'Δεν ήταν δυνατή η δέσμευση διαθεσιμότητας.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $reserved = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(qty), 0) FROM {$holds_table} WHERE post_id = %d AND slot_date = %s AND slot_time = %s AND expires_at > %s",
                $post_id,
                $date,
                $time_db,
                $now
            )
        );

        $booked = (int) $booked;
        if ( $one_booking_per_slot && ( $booked + $reserved ) > 0 ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error(
                'iw_cashier_slot_capacity',
                __( 'Η επιλεγμένη ώρα έχει ήδη δεσμευτεί.', 'iw-theme' ),
                [ 'status' => 409 ]
            );
        }

        if ( $capacity !== null && ( $booked + $reserved + $qty ) > $capacity ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error(
                'iw_cashier_slot_capacity',
                __( 'Δεν υπάρχει διαθέσιμη χωρητικότητα για τον αριθμό εισιτηρίων που επιλέξατε.', 'iw-theme' ),
                [
                    'status'       => 409,
                    'availability' => max( 0, $capacity - $booked - $reserved ),
                    'requested'    => $qty,
                ]
            );
        }

        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$inventory_table} SET booked = booked + %d, updated_at = CURRENT_TIMESTAMP WHERE post_id = %d AND slot_date = %s AND slot_time = %s",
                $qty,
                $post_id,
                $date,
                $time_db
            )
        );

        if ( $updated === false ) {
            $wpdb->query( 'ROLLBACK' );
            return new WP_Error( 'iw_cashier_inventory_update_failed', __( 'Δεν ήταν δυνατή η ενημέρωση διαθεσιμότητας.', 'iw-theme' ), [ 'status' => 500 ] );
        }

        $wpdb->query( 'COMMIT' );
        return true;
    }

    protected static function ticket_requires_one_booking_per_slot( int $post_id ): bool {
        if ( $post_id <= 0 || ! function_exists( 'get_field' ) ) {
            return false;
        }

        $ticket_categories = get_field( 'field_iw_ticket_categories_group', $post_id );
        if ( ! is_array( $ticket_categories ) ) {
            return false;
        }

        return ! empty( $ticket_categories['one_booking_per_slot'] );
    }

    protected static function get_order_item_tickets( int $order_item_id ): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}iw_tickets WHERE order_item_id = %d ORDER BY id ASC",
                $order_item_id
            ),
            ARRAY_A
        );

        return self::map_ticket_rows_for_response( $rows );
    }

    protected static function get_order_tickets( int $order_id ): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}iw_tickets WHERE order_id = %d ORDER BY id ASC",
                $order_id
            ),
            ARRAY_A
        );

        return self::map_ticket_rows_for_response( $rows );
    }

    protected static function map_ticket_rows_for_response( $rows ): array {
        if ( ! is_array( $rows ) ) {
            return [];
        }

        $exp     = time() + ( 365 * DAY_IN_SECONDS );
        $tickets = [];
        foreach ( $rows as $row ) {
            $uuid    = (string) ( $row['ticket_uuid'] ?? '' );
            $pdf_url = $uuid !== '' && class_exists( 'IW_Ticket_PDF_Service' ) && method_exists( 'IW_Ticket_PDF_Service', 'build_public_pdf_url' )
                ? IW_Ticket_PDF_Service::build_public_pdf_url( $uuid, $exp )
                : '';

            $tickets[] = [
                'id'             => (int) ( $row['id'] ?? 0 ),
                'ticket_uuid'    => $uuid,
                'barcode_hash'   => (string) ( $row['barcode_hash'] ?? '' ),
                'post_id'        => (int) ( $row['post_id'] ?? 0 ),
                'order_id'       => (int) ( $row['order_id'] ?? 0 ),
                'order_item_id'  => (int) ( $row['order_item_id'] ?? 0 ),
                'slot_start'     => (string) ( $row['slot_start'] ?? '' ),
                'slot_end'       => (string) ( $row['slot_end'] ?? '' ),
                'price_category' => (string) ( $row['price_category'] ?? '' ),
                'unit_price'      => isset( $row['unit_price'] ) && is_numeric( $row['unit_price'] ) ? (float) $row['unit_price'] : null,
                'attendee_name'  => (string) ( $row['attendee_name'] ?? '' ),
                'pdf_url'        => $pdf_url,
                'html_url'       => $pdf_url !== '' ? add_query_arg( 'view', 'html', $pdf_url ) : '',
            ];
        }

        return $tickets;
    }

    protected static function normalize_for_response( $value ) {
        if ( is_object( $value ) ) {
            $value = get_object_vars( $value );
        }

        if ( is_array( $value ) ) {
            $out = [];
            foreach ( $value as $key => $child ) {
                $out[ $key ] = self::normalize_for_response( $child );
            }
            return $out;
        }

        return $value;
    }

    protected static function read_data_value( $source, string $key, $default = null ) {
        if ( is_array( $source ) && array_key_exists( $key, $source ) ) {
            return $source[ $key ];
        }

        if ( is_object( $source ) && isset( $source->{$key} ) ) {
            return $source->{$key};
        }

        return $default;
    }
}
