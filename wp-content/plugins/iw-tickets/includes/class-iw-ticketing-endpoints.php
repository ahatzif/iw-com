<?php
class IW_Ticketing_Endpoints {
    private static $instance = null;
    public static $endpoint = 'tickets';
    public static $reservation_endpoint = 'reservations';

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_action( 'init', [ $this, 'register_my_account_endpoint' ], 20 );
        add_action( 'wp_loaded', [ $this, 'maybe_flush_rewrite_rules' ] );
        add_action( 'woocommerce_account_' . $this::$endpoint . '_endpoint', [ $this, 'register_my_account_template' ], 20 );
        //add_action( 'init', [ $this, 'register_ticketing_post_type_endpoints' ], 20 );
    }



    public function register_query_vars( $vars ) {
        $vars[] = $this::$endpoint;
        $vars[] = $this::$reservation_endpoint;
        $vars[] = 'tickets-date';
        $vars[] = 'tickets-time';
        return $vars;
    }

    /**
     * Register REST API routes used by the ticketing plugin (wallet, calendar, etc.).
     * We keep this here so routing is centralized.
     */
    public function register_rest_routes() {
        // Apple Wallet (Tickets) – routes will be implemented in a dedicated service class.
        if ( class_exists( 'IW_Apple_Wallet_Tickets_Service' ) && method_exists( 'IW_Apple_Wallet_Tickets_Service', 'register_routes' ) ) {
            IW_Apple_Wallet_Tickets_Service::register_routes();
        }

        // Google Wallet (Tickets) – routes will be implemented in a dedicated service class.
        if ( class_exists( 'IW_Google_Wallet_Tickets_Service' ) && method_exists( 'IW_Google_Wallet_Tickets_Service', 'register_routes' ) ) {
            IW_Google_Wallet_Tickets_Service::register_routes();
        }

    }

    public function register_my_account_endpoint() {
        // Base endpoint: /my-account/tickets/
        add_rewrite_endpoint( $this::$endpoint, EP_ROOT | EP_PAGES );
        add_rewrite_endpoint( $this::$reservation_endpoint, EP_ROOT | EP_PAGES );
    }

    public function maybe_flush_rewrite_rules() {
        if ( ! defined( 'IW_TICKETS_REWRITE_VERSION' ) ) {
            return;
        }

        if ( get_option( 'iw_tickets_rewrite_version' ) === IW_TICKETS_REWRITE_VERSION ) {
            return;
        }

        flush_rewrite_rules( false );
        update_option( 'iw_tickets_rewrite_version', IW_TICKETS_REWRITE_VERSION );
    }

    public function register_my_account_template() {

        $order_item_id = self::get_tickets_order_id_from_request();

        // Default view: list
        if ( empty( $order_item_id ) ) {
            wc_get_template( 'myaccount/' . $this::$endpoint . '.php' );
            return;
        }

        $order_item_id = (int) $order_item_id;

        if ( $order_item_id <= 0 ) {
            wc_get_template( 'myaccount/' . $this::$endpoint . '.php' );
            return;
        }

        global $wpdb;

        // Resolve order_id from order_item_id
        $order_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d",
                $order_item_id
            )
        );

        if ( $order_id <= 0 ) {
            wc_get_template( 'myaccount/' . $this::$endpoint . '.php' );
            return;
        }

        $order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;

        if ( ! $order ) {
            wc_get_template( 'myaccount/' . $this::$endpoint . '.php' );
            return;
        }

        // Must belong to the current customer
        $current_user_id = get_current_user_id();
        $order_user_id   = (int) $order->get_user_id();

        if ( $current_user_id <= 0 || $order_user_id !== $current_user_id ) {
            wc_get_template( 'myaccount/' . $this::$endpoint . '.php' );
            return;
        }

        wc_get_template( 'myaccount/' . $this::$endpoint . '-order.php', [
            'order_id' => $order_id,
            'order_item_id' => $order_item_id
        ] );
    }

    public static function get_tickets_endpoint_url( $order_id = null ) {
        return wc_get_account_endpoint_url( self::$endpoint, (string) $order_id );
    }

    public static function get_tickets_order_id_from_request() {
        return self::get_order_item_id_from_request( self::$endpoint );
    }

    public static function get_order_item_id_from_request( string $endpoint = '' ) {
        $endpoint = $endpoint !== '' ? sanitize_key( $endpoint ) : self::$endpoint;
        $tickets_url = get_query_var( $endpoint, '' );
        if ( ! is_string( $tickets_url ) || $tickets_url === '' ) {
            return '';
        }

        $tickets_url = trim( $tickets_url, '/' );

        // New format: order-item/{digits}
        if ( preg_match( '#^order-item/(\d+)$#', $tickets_url, $m ) ) {
            return (int) $m[1];
        }

        return '';
    }

    public static function get_order_item_url( $order_item_id, string $endpoint = '' ) {
        $order_item_id = (int) $order_item_id;
        $endpoint = $endpoint !== '' ? sanitize_key( $endpoint ) : self::$endpoint;

        if ( $order_item_id <= 0 ) {
            return wc_get_account_endpoint_url( $endpoint );
        }

        return trailingslashit( wc_get_account_endpoint_url( $endpoint ) ) . 'order-item/' . $order_item_id . '/';
    }

    // Backwards compatibility (old code may still call this)
    public static function get_order_url( $order_id ) {
        return self::get_order_item_url( $order_id );
    }


    public function register_ticketing_post_type_endpoints() {
        foreach ( IW_Ticketing::get_supported_post_types() as $pt ) {
            $pt = sanitize_key( $pt );
            if ( $pt === '' ) continue;
            $pto = get_post_type_object( $pt );
            $base = $pt;
            if ( $pto && isset( $pto->rewrite ) && is_array( $pto->rewrite ) && ! empty( $pto->rewrite['slug'] ) ) {
                $base = (string) $pto->rewrite['slug'];
            }
            $base = trim( $base, '/' );
            // With date + time
            add_rewrite_rule('^' . $base . '/([^/]+)/tickets/([0-9]{4}-[0-9]{2}-[0-9]{2})/([0-9]{2}-[0-9]{2})/?$', 'index.php?post_type=' . $pt . '&name=$matches[1]&tickets-date=$matches[2]&tickets-time=$matches[3]', 'top');
            // With date only
            add_rewrite_rule('^' . $base . '/([^/]+)/tickets/([0-9]{4}-[0-9]{2}-[0-9]{2})/?$', 'index.php?post_type=' . $pt . '&name=$matches[1]&tickets-date=$matches[2]', 'top');
            // Base
            add_rewrite_rule('^' . $base . '/([^/]+)/tickets/?$', 'index.php?post_type=' . $pt . '&name=$matches[1]', 'top') ;
        }
    }

}
