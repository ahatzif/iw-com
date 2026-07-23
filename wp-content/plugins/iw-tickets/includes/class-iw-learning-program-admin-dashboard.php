<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IW_Learning_Program_Admin_Dashboard {

    const PAGE_SLUG = 'iw-learning-program-dashboard';
    const REST_NS   = 'iw/v1';
    const REST_BASE = 'admin/learning-program-dashboard';

    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'register_admin_page' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'admin_head', [ __CLASS__, 'hide_admin_notices' ] );
        add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
    }

    public static function register_admin_page(): void {
        add_submenu_page(
            'edit.php?post_type=learning-program',
            __( 'Bookings & Schedule', 'iw-tickets' ),
            __( 'Bookings & Schedule', 'iw-tickets' ),
            self::capability(),
            self::PAGE_SLUG,
            [ __CLASS__, 'render_admin_page' ]
        );
    }

    public static function enqueue_assets( string $hook ): void {
        if ( $hook !== 'learning-program_page_' . self::PAGE_SLUG ) {
            return;
        }

        $shared_admin_css_file = WP_PLUGIN_DIR . '/iw-admin-ui/assets/admin-ui.css';
        $style_dependencies    = [];

        if ( file_exists( $shared_admin_css_file ) ) {
            wp_enqueue_style(
                'iw-admin-ui',
                content_url( 'plugins/iw-admin-ui/assets/admin-ui.css' ),
                [],
                filemtime( $shared_admin_css_file )
            );
            $style_dependencies[] = 'iw-admin-ui';
        }

        wp_enqueue_style(
            'iw-learning-program-dashboard',
            plugin_dir_url( __DIR__ ) . 'assets/admin-learning-program-dashboard.css',
            $style_dependencies,
            filemtime( plugin_dir_path( __DIR__ ) . 'assets/admin-learning-program-dashboard.css' )
        );

        wp_enqueue_script(
            'iw-learning-program-dashboard',
            plugin_dir_url( __DIR__ ) . 'assets/admin-learning-program-dashboard.js',
            [],
            filemtime( plugin_dir_path( __DIR__ ) . 'assets/admin-learning-program-dashboard.js' ),
            true
        );

        wp_script_add_data( 'iw-learning-program-dashboard', 'type', 'module' );

        wp_localize_script(
            'iw-learning-program-dashboard',
            'IWTicketsLearningProgramDashboard',
            [
                'restUrl' => esc_url_raw( rest_url( self::REST_NS . '/' . self::REST_BASE ) ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
                'programId' => isset( $_GET['program_id'] ) ? (int) $_GET['program_id'] : 0,
                'programLabel' => isset( $_GET['program_id'] ) ? self::get_program_filter_label( (int) $_GET['program_id'] ) : '',
                'schoolId' => isset( $_GET['school_id'] ) ? (int) $_GET['school_id'] : 0,
                'schoolLabel' => isset( $_GET['school_id'] ) ? self::get_school_filter_label( (int) $_GET['school_id'] ) : '',
                'userId' => isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0,
                'userLabel' => isset( $_GET['user_id'] ) ? self::get_user_filter_label( (int) $_GET['user_id'] ) : '',
                'strings' => [
                    'loading' => __( 'Loading data…', 'iw-tickets' ),
                    'empty'   => __( 'No bookings or purchases were found for the selected filters.', 'iw-tickets' ),
                    'error'   => __( 'Unable to load dashboard data.', 'iw-tickets' ),
                ],
            ]
        );
    }

    public static function register_rest_routes(): void {
        register_rest_route(
            self::REST_NS,
            '/' . self::REST_BASE,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ __CLASS__, 'get_dashboard_data' ],
                    'permission_callback' => [ __CLASS__, 'can_view_dashboard' ],
                ],
            ]
        );
    }

    public static function can_view_dashboard(): bool {
        return current_user_can( self::capability() );
    }

    public static function capability(): string {
        return (string) apply_filters( 'iw_learning_program_dashboard_capability', 'edit_posts' );
    }

    public static function render_admin_page(): void {
        echo '<div class="wrap iw-admin-ui iw-learning-program-dashboard-page">';
        echo '<h1>' . esc_html__( 'Bookings, purchases, and schedule', 'iw-tickets' ) . '</h1>';
        echo '<p class="iw-learning-program-dashboard-description">' . esc_html__( 'Operational view of learning programs with filters, summary stats, a detailed table, and calendar view so you can instantly see what is booked and when.', 'iw-tickets' ) . '</p>';
        echo '<div id="iw-learning-program-dashboard-root"></div>';
        echo '</div>';
    }

    public static function hide_admin_notices(): void {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        if ( ! $screen || $screen->id !== 'learning-program_page_' . self::PAGE_SLUG ) {
            return;
        }

        echo '<style>
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .wrap > h1.wp-heading-inline,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .wrap > .page-title-action,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .wrap > hr.wp-header-end,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' .notice,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' .update-nag,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' .inline.notice,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' .components-snackbar-list,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .wrap.iw-learning-program-dashboard-page > .notice,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .wrap.iw-learning-program-dashboard-page > .update-nag,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .wrap.iw-learning-program-dashboard-page > .updated,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .wrap.iw-learning-program-dashboard-page > .error,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .wrap.iw-learning-program-dashboard-page > .woocommerce-message,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .wrap.iw-learning-program-dashboard-page > .woocommerce-error,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .wrap.iw-learning-program-dashboard-page > .woocommerce-info,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .notice,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .updated,
            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content > .error {
                display: none !important;
            }

            .learning-program_page_' . esc_html( self::PAGE_SLUG ) . ' #wpbody-content {
                padding-bottom: 0;
            }

        </style>';
    }

    public static function get_dashboard_data( WP_REST_Request $request ): WP_REST_Response {
        $program_id = absint( $request->get_param( 'program_id' ) );
        $school_id  = absint( $request->get_param( 'school_id' ) );
        $user_id    = absint( $request->get_param( 'user_id' ) );
        $date_from  = self::sanitize_date( (string) $request->get_param( 'date_from' ) );
        $date_to    = self::sanitize_date( (string) $request->get_param( 'date_to' ) );
        $view       = sanitize_key( (string) $request->get_param( 'view' ) );

        $rows     = self::get_dashboard_rows( $program_id, $date_from, $date_to );
        $rows     = self::filter_dashboard_rows( $rows, $school_id, $user_id );
        $summary  = self::build_summary( $rows );

        return new WP_REST_Response(
            [
                'filters' => [
                    'program_id' => $program_id,
                    'school_id'  => $school_id,
                    'user_id'    => $user_id,
                    'date_from'  => $date_from,
                    'date_to'    => $date_to,
                    'view'       => $view !== '' ? $view : 'table',
                ],
                'programs' => [],
                'summary'  => $summary,
                'rows'     => $rows,
                'calendar' => self::build_calendar_payload( $rows ),
            ]
        );
    }

    private static function sanitize_date( string $date ): string {
        $date = trim( $date );
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return $date;
        }
        return '';
    }

    private static function get_program_filter_label( int $program_id ): string {
        if ( $program_id <= 0 ) {
            return '';
        }

        return self::to_plain_text( get_the_title( $program_id ) );
    }

    private static function get_school_filter_label( int $school_id ): string {
        if ( $school_id <= 0 ) {
            return '';
        }

        return self::to_plain_text( get_the_title( $school_id ) );
    }

    private static function get_user_filter_label( int $user_id ): string {
        if ( $user_id <= 0 ) {
            return '';
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return '';
        }

        return self::to_plain_text( $user->display_name ?: $user->user_email );
    }

    private static function get_learning_program_options(): array {
        $posts = get_posts(
            [
                'post_type'      => 'learning-program',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'fields'         => 'ids',
            ]
        );

        $items = [];
        foreach ( $posts as $post_id ) {
            $items[] = [
                'id'    => (int) $post_id,
                'title' => get_the_title( $post_id ),
            ];
        }

        return $items;
    }

    private static function filter_dashboard_rows( array $rows, int $school_id, int $user_id ): array {
        if ( $school_id <= 0 && $user_id <= 0 ) {
            return $rows;
        }

        return array_values(
            array_filter(
                $rows,
                static function ( array $row ) use ( $school_id, $user_id ): bool {
                    if ( $school_id > 0 && (int) ( $row['school_id'] ?? 0 ) !== $school_id ) {
                        return false;
                    }

                    if ( $user_id > 0 && (int) ( $row['teacher_user_id'] ?? 0 ) !== $user_id ) {
                        return false;
                    }

                    return true;
                }
            )
        );
    }

    private static function get_dashboard_rows( int $program_id, string $date_from, string $date_to ): array {
        global $wpdb;

        $tickets_table = $wpdb->prefix . 'iw_tickets';
        $holds_table   = $wpdb->prefix . 'iw_ticket_slot_holds';
        $items_table   = $wpdb->prefix . 'woocommerce_order_items';

        $where = [ "t.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'learning-program')" ];
        $args  = [];

        if ( $program_id > 0 ) {
            $where[] = 't.post_id = %d';
            $args[]  = $program_id;
        }

        if ( $date_from !== '' ) {
            $where[] = 't.slot_start >= %s';
            $args[]  = $date_from . ' 00:00:00';
        }

        if ( $date_to !== '' ) {
            $where[] = 't.slot_start < %s';
            $args[]  = self::get_next_date( $date_to ) . ' 00:00:00';
        }

        $ticket_sql = "SELECT
                t.order_id,
                t.order_item_id,
                t.post_id,
                t.slot_start,
                t.slot_end,
                SUM(t.unit_price) AS amount,
                COUNT(*) AS tickets_count,
                MAX(t.currency) AS currency,
                MAX(t.status) AS ticket_status
            FROM {$tickets_table} t
            WHERE " . implode( ' AND ', $where ) . '
            GROUP BY t.order_item_id, t.post_id, t.order_id, t.slot_start, t.slot_end
            ORDER BY t.slot_start ASC';

        $ticket_query = ! empty( $args ) ? $wpdb->prepare( $ticket_sql, $args ) : $ticket_sql;
        $ticket_rows = $wpdb->get_results( $ticket_query, ARRAY_A );

        $hold_where = [ "h.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'learning-program')" ];
        $hold_args  = [];

        if ( $program_id > 0 ) {
            $hold_where[] = 'h.post_id = %d';
            $hold_args[]  = $program_id;
        }

        if ( $date_from !== '' ) {
            $hold_where[] = 'h.slot_date >= %s';
            $hold_args[]  = $date_from;
        }

        if ( $date_to !== '' ) {
            $hold_where[] = 'h.slot_date <= %s';
            $hold_args[]  = $date_to;
        }

        $hold_sql = "SELECT
                oi.order_id,
                h.order_item_id,
                h.post_id,
                CONCAT(h.slot_date, ' ', h.slot_time) AS slot_start,
                NULL AS slot_end,
                0 AS amount,
                SUM(h.qty) AS tickets_count,
                '' AS currency,
                'reserved' AS ticket_status
            FROM {$holds_table} h
            INNER JOIN {$items_table} oi ON oi.order_item_id = h.order_item_id
            WHERE " . implode( ' AND ', $hold_where ) . '
            GROUP BY h.order_item_id, h.post_id, oi.order_id, h.slot_date, h.slot_time
            ORDER BY slot_start ASC';

        $hold_query = ! empty( $hold_args ) ? $wpdb->prepare( $hold_sql, $hold_args ) : $hold_sql;
        $hold_rows = $wpdb->get_results( $hold_query, ARRAY_A );

        $ticket_index = [];
        foreach ( $ticket_rows as $row ) {
            $ticket_index[ (int) $row['order_item_id'] ] = true;
        }

        $rows = [];

        foreach ( $ticket_rows as $row ) {
            $normalized = self::normalize_row( $row, 'purchase' );
            if ( $normalized ) {
                $rows[] = $normalized;
            }
        }

        foreach ( $hold_rows as $row ) {
            if ( isset( $ticket_index[ (int) $row['order_item_id'] ] ) ) {
                continue;
            }

            $normalized = self::normalize_row( $row, 'reservation' );
            if ( $normalized ) {
                $rows[] = $normalized;
            }
        }

        usort(
            $rows,
            static function ( array $a, array $b ): int {
                return strcmp( $a['slot_start'], $b['slot_start'] );
            }
        );

        return $rows;
    }

    private static function normalize_row( array $row, string $source ): ?array {
        $order_id      = isset( $row['order_id'] ) ? (int) $row['order_id'] : 0;
        $order_item_id = isset( $row['order_item_id'] ) ? (int) $row['order_item_id'] : 0;
        $post_id       = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
        $slot_start    = isset( $row['slot_start'] ) ? (string) $row['slot_start'] : '';

        if ( $order_id <= 0 || $order_item_id <= 0 || $post_id <= 0 || $slot_start === '' ) {
            return null;
        }

        $order = wc_get_order( $order_id );
        $post  = get_post( $post_id );
        $item  = $order ? $order->get_item( $order_item_id ) : null;

        if ( ! $order || ! $post || ! $item ) {
            return null;
        }

        $user_id = (int) $order->get_user_id();
        $school  = ( $user_id > 0 && function_exists( 'get_field' ) ) ? get_field( 'user_school_unit', 'user_' . $user_id ) : null;
        $school_title = '';
        $school_id = 0;
        if ( is_object( $school ) && ! empty( $school->post_title ) ) {
            $school_title = (string) $school->post_title;
            $school_id = ! empty( $school->ID ) ? (int) $school->ID : 0;
        } elseif ( is_array( $school ) && ! empty( $school['post_title'] ) ) {
            $school_title = (string) $school['post_title'];
            $school_id = ! empty( $school['ID'] ) ? (int) $school['ID'] : 0;
        } elseif ( is_numeric( $school ) ) {
            $school_id = (int) $school;
            $school_title = get_the_title( $school_id );
        }

        $building = function_exists( 'get_field' ) ? get_field( 'building_location', $post_id ) : null;
        $building_title = '';
        if ( is_object( $building ) && ! empty( $building->ID ) ) {
            $building_title = get_the_title( $building->ID );
        } elseif ( is_numeric( $building ) ) {
            $building_title = get_the_title( (int) $building );
        }

        $start_dt = self::safe_datetime( $slot_start );
        $end_dt   = self::safe_datetime( isset( $row['slot_end'] ) ? (string) $row['slot_end'] : '' );

        $tickets_visitors_raw = (string) $item->get_meta( 'tickets_visitors', true );
        $visitors = json_decode( $tickets_visitors_raw, true );
        $visitors = is_array( $visitors ) ? $visitors : [];

        $status = self::map_status( $order, $order_item_id, $source );
        $amount = isset( $row['amount'] ) ? (float) $row['amount'] : 0.0;
        if ( $amount <= 0 ) {
            $amount = (float) $item->get_total() + (float) $item->get_total_tax();
        }
        $currency = isset( $row['currency'] ) && $row['currency'] !== '' ? (string) $row['currency'] : (string) $order->get_currency();
        $program_title = get_the_title( $post_id );
        $amount_label_html = function_exists( 'wc_price' ) ? wc_price( $amount, [ 'currency' => $currency ] ) : (string) $amount;

        return [
            'id'            => $order_item_id,
            'source'        => $source,
            'status'        => $status,
            'status_label'  => self::get_status_label( $status ),
            'order_id'      => $order_id,
            'order_item_id' => $order_item_id,
            'program_id'    => $post_id,
            'program_title' => self::to_plain_text( $program_title ),
            'program_featured_image' => get_the_post_thumbnail_url( $post_id, 'medium' ) ?: '',
            'program_edit_url' => get_edit_post_link( $post_id, 'raw' ) ?: '',
            'program_url' => get_the_permalink( $post_id ) ?: '',
            'slot_start'    => $start_dt ? $start_dt->format( 'Y-m-d H:i:s' ) : $slot_start,
            'slot_end'      => $end_dt ? $end_dt->format( 'Y-m-d H:i:s' ) : null,
            'date'          => $start_dt ? $start_dt->format( 'Y-m-d' ) : '',
            'day_label'     => $start_dt ? wp_date( 'D', $start_dt->getTimestamp() ) : '',
            'time_range'    => self::format_time_range( $start_dt, $end_dt ),
            'tickets_count' => isset( $row['tickets_count'] ) ? (int) $row['tickets_count'] : 0,
            'amount'        => $amount,
            'amount_label'  => self::to_plain_text( $amount_label_html ),
            'amount_label_html' => self::sanitize_inline_html( $amount_label_html ),
            'currency'      => $currency,
            'school_id'     => $school_id,
            'school'        => self::to_plain_text( $school_title ),
            'school_html'   => self::sanitize_inline_html( $school_title ),
            'school_edit_url' => $school_id > 0 ? get_edit_post_link( $school_id, 'raw' ) ?: '' : '',
            'teacher_user_id' => $user_id,
            'teacher_edit_url' => $user_id > 0 ? get_edit_user_link( $user_id ) ?: '' : '',
            'teacher_name'  => self::to_plain_text( trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ),
            'teacher_phone' => (string) $order->get_billing_phone(),
            'teacher_email' => (string) $order->get_billing_email(),
            'billing_company' => (string) $order->get_billing_company(),
            'billing_vat'   => (string) $order->get_meta( '_billing_vat', true ),
            'building'      => self::to_plain_text( $building_title ),
            'building_html' => self::sanitize_inline_html( $building_title ),
            'order_status'  => (string) $order->get_status(),
            'order_status_label' => wc_get_order_status_name( 'wc-' . $order->get_status() ),
            'visitors'      => $visitors,
            'notes'         => (string) $order->get_customer_note(),
        ];
    }

    private static function get_next_date( string $date ): string {
        try {
            return ( new DateTimeImmutable( $date, wp_timezone() ) )->modify( '+1 day' )->format( 'Y-m-d' );
        } catch ( Throwable $e ) {
            return $date;
        }
    }

    private static function sanitize_inline_html( string $value ): string {
        $allowed_tags = [
            'span'   => [ 'class' => true ],
            'strong' => [],
            'b'      => [],
            'em'     => [],
            'i'      => [],
            'small'  => [],
            'sup'    => [],
            'sub'    => [],
            'br'     => [],
            'bdi'    => [],
        ];

        return wp_kses( (string) $value, $allowed_tags );
    }

    private static function to_plain_text( string $value ): string {
        return html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    }

    private static function safe_datetime( string $value ): ?DateTimeImmutable {
        $value = trim( $value );
        if ( $value === '' ) {
            return null;
        }

        try {
            return new DateTimeImmutable( $value, wp_timezone() );
        } catch ( Throwable $e ) {
            return null;
        }
    }

    private static function format_time_range( ?DateTimeImmutable $start, ?DateTimeImmutable $end ): string {
        if ( ! $start ) {
            return '';
        }

        if ( $end ) {
            return $start->format( 'H:i' ) . ' - ' . $end->format( 'H:i' );
        }

        return $start->format( 'H:i' );
    }

    private static function map_status( WC_Order $order, int $order_item_id, string $source ): string {
        if ( $source === 'reservation' ) {
            $confirmed = wc_get_order_item_meta( $order_item_id, '_reservation_confirmed', true );
            $cancelled = wc_get_order_item_meta( $order_item_id, '_reservation_cancelled', true );

            if ( $cancelled ) {
                return 'cancelled';
            }

            if ( $confirmed ) {
                return 'confirmed';
            }

            return 'reserved';
        }

        $status = $order->get_status();

        if ( in_array( $status, [ 'cancelled', 'failed', 'refunded' ], true ) ) {
            return 'cancelled';
        }

        if ( in_array( $status, [ 'completed', 'processing' ], true ) ) {
            return 'completed';
        }

        return 'pending';
    }

    private static function get_status_label( string $status ): string {
        $labels = [
            'reserved'  => __( 'Reserved', 'iw-tickets' ),
            'confirmed' => __( 'Confirmed', 'iw-tickets' ),
            'completed' => __( 'Completed', 'iw-tickets' ),
            'pending'   => __( 'Pending', 'iw-tickets' ),
            'cancelled' => __( 'Cancelled', 'iw-tickets' ),
        ];

        return $labels[ $status ] ?? $status;
    }

    private static function build_summary( array $rows ): array {
        $summary = [
            'total_rows'         => count( $rows ),
            'total_tickets'      => 0,
            'total_revenue'      => 0,
            'reservations_count' => 0,
            'purchases_count'    => 0,
        ];

        foreach ( $rows as $row ) {
            $summary['total_tickets'] += (int) ( $row['tickets_count'] ?? 0 );
            $summary['total_revenue'] += (float) ( $row['amount'] ?? 0 );

            if ( ( $row['source'] ?? '' ) === 'reservation' ) {
                $summary['reservations_count']++;
            } else {
                $summary['purchases_count']++;
            }
        }

        return $summary;
    }

    private static function build_calendar_payload( array $rows ): array {
        $days = [];

        foreach ( $rows as $row ) {
            $date = (string) ( $row['date'] ?? '' );
            if ( $date === '' ) {
                continue;
            }

            if ( ! isset( $days[ $date ] ) ) {
                $days[ $date ] = [
                    'date'          => $date,
                    'count'         => 0,
                    'tickets_count' => 0,
                    'revenue'       => 0,
                    'items'         => [],
                ];
            }

            $days[ $date ]['count']++;
            $days[ $date ]['tickets_count'] += (int) ( $row['tickets_count'] ?? 0 );
            $days[ $date ]['revenue'] += (float) ( $row['amount'] ?? 0 );
            $days[ $date ]['items'][] = $row;
        }

        ksort( $days );

        return array_values( $days );
    }
}

IW_Learning_Program_Admin_Dashboard::init();
