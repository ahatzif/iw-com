<?php
/**
 * Plugin Name: Tickets
 * Description: Ticketing system for Woocommerce
 */

if (!defined('ABSPATH')) exit;

define('IW_TICKETS_DB_VERSION', '1.4');
define('IW_TICKETS_REWRITE_VERSION', '2026-06-02-reservations');

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/includes/class-iw-tickets-db.php';
require_once __DIR__ . '/includes/class-cpt-as-product.php';
require_once __DIR__ . '/includes/class-iw-ticketing-options-page.php';
require_once __DIR__ . '/includes/class-iw-ticketing-acf.php';
require_once __DIR__ . '/includes/class-iw-ticketing-endpoints.php';
require_once __DIR__ . '/includes/class-iw-tickets-calendar-service.php';
require_once __DIR__ . '/includes/class-iw-wc-tickets-cart-manager.php';
require_once __DIR__ . '/includes/class-iw-ticketing-ics.php';

require_once __DIR__ . '/includes/class-iw-wallet-ticket-card-styles.php';

require_once __DIR__ . '/includes/class-iw-apple-wallet-tickets-service.php';
require_once __DIR__ . '/includes/class-iw-apple-wallet-ticket-pass.php';

require_once __DIR__ . '/includes/class-iw-google-wallet-tickets-service.php';

require_once __DIR__ . '/includes/class-iw-ticket-scanner.php';
require_once __DIR__ . '/includes/class-iw-ticket-pdf-service.php';
require_once __DIR__ . '/includes/class-iw-zebra-ticket-printer.php';
require_once __DIR__ . '/includes/class-iw-zebra-print-queue.php';
require_once __DIR__ . '/includes/class-iw-cashier-ticketing.php';

require_once __DIR__ . '/includes/class-iw-gateway-pay-at-museum.php';
require_once __DIR__ . '/includes/class-iw-learning-program-admin-dashboard.php';




class IW_Ticketing
{
    private static $instance = null;

    public static function option_defaults(): array {
        $site_name = get_bloginfo( 'name' ) ?: __( 'Ticketing', 'iw-theme' );

        return [
            'brand_name'                         => $site_name,
            'pdf_logo_path'                      => '',
            'pdf_qr_logo_path'                   => '',
            'pdf_footer_left'                    => '',
            'pdf_footer_right'                   => '',
            'pdf_visit_info_left'                => '',
            'pdf_visit_info_right'               => '',
            'google_wallet_service_account_path' => '',
            'google_wallet_issuer_id'            => '',
            'google_wallet_class_suffix'         => 'tickets',
            'google_wallet_object_prefix'        => 'ticket_',
            'google_wallet_application_name'     => sprintf( __( '%s Tickets', 'iw-theme' ), $site_name ),
            'google_wallet_jwt_origins'          => home_url(),
            'cashier_sms_prefix'                 => sprintf( __( '%s tickets', 'iw-theme' ), $site_name ),
            'cashier_nexi_user_agent'            => sprintf( 'IWTickets/1.0 (%s; cms:wordpress)', sanitize_title( $site_name ) ),
            'zebra_test_qr_value'                => home_url(),
        ];
    }

    public static function get_option( string $key, $default = null ) {
        $defaults = self::option_defaults();
        $use_registered_default = null === $default;
        if ( null === $default ) {
            $default = $defaults[ $key ] ?? '';
        }

        $constant = 'IW_TICKETING_' . strtoupper( $key );
        if ( defined( $constant ) ) {
            $value = constant( $constant );
        } else {
            $value = get_option( 'iw_ticketing_' . $key, $default );
        }

        if ( '' === $value && $use_registered_default && array_key_exists( $key, $defaults ) ) {
            $value = $defaults[ $key ];
        }

        return apply_filters( 'iw_ticketing_' . $key, $value );
    }

    public static function get_list_option( string $key, array $default = [] ): array {
        $value = self::get_option( $key, $default );
        if ( is_string( $value ) ) {
            $value = preg_split( '/[\r\n]+/', $value, -1, PREG_SPLIT_NO_EMPTY );
        }

        return array_values( array_filter( array_map( 'trim', (array) $value ) ) );
    }

    public static function resolve_path( $path, string $base_dir = '' ): string {
        $path = trim( (string) $path );
        if ( '' === $path ) {
            return '';
        }

        if ( '/' === $path[0] || preg_match( '/^[A-Za-z]:[\/\\\\]/', $path ) ) {
            return $path;
        }

        $base_dir = $base_dir ?: __DIR__;
        return trailingslashit( $base_dir ) . ltrim( $path, '/\\' );
    }

    public static function remove_accents( string $text ): string {
        if ( class_exists( 'IW_Theme' ) && method_exists( 'IW_Theme', 'remove_accents' ) ) {
            return (string) IW_Theme::remove_accents( $text );
        }

        return remove_accents( $text );
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', ['IW_Ticketing', 'register_order_statuses']);
        add_filter('wc_order_statuses', ['IW_Ticketing', 'add_order_statuses']);
        add_action('wp_ajax_iw_confirm_reservation', ['IW_Ticketing', 'ajax_confirm_reservation']);
        add_action('wp_ajax_iw_cancel_reservation', ['IW_Ticketing', 'ajax_cancel_reservation']);
        add_action('woocommerce_order_status_reserved', ['IW_Ticketing', 'schedule_reservation_confirmation_emails']);
        add_action('iw_send_reservation_confirmation_email', ['IW_Ticketing', 'send_reservation_confirmation_email_for_item'], 10, 2);
    }

    public static function is_sellable($p)
    {
        $post = get_post($p);
        if (!$post) return false;
        // Only published posts can be sellable
        if ($post->post_status !== 'publish') {
            return false;
        }
        if (!in_array($post->post_type, self::get_supported_post_types(), true)) {
            return false;
        }
        $is_abroad = get_field('is_abroad', $post->ID);
        if ($is_abroad) {
            return false;
        }

        $is_sellable = get_field('_sellable', $post->ID);


        $max_date = trim(get_field('max_date', $post->ID));
        if ($max_date === '') {
            return $is_sellable;
        }
        $tz = wp_timezone();
        $dt = DateTime::createFromFormat('d/m/Y', $max_date, $tz);
        $dt->setTime(0, 0, 0);
        $today = new DateTime('now', $tz);
        $today->setTime(0, 0, 0);

        return $is_sellable && ($dt >= $today);
    }

    public static function get_supported_post_types()
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $val = function_exists('get_field')
            ? get_field('iw_ticketing_supported_post_types', 'option')
            : [];
        $val = self::normalize_post_type_list($val);

        if (empty($val)) {
            $val = self::normalize_post_type_list(get_option('options_iw_ticketing_supported_post_types', []));
        }

        if (empty($val)) {
            $val = class_exists('IW_Ticketing_ACF')
                ? IW_Ticketing_ACF::$post_types
                : ['event', 'guided-tour', 'exhibition', 'learning-program', 'experience', 'building'];
        }

        $cache = self::normalize_post_type_list($val);
        return $cache;
    }

    private static function normalize_post_type_list($post_types): array
    {
        if (!is_array($post_types)) {
            $post_types = [$post_types];
        }

        return array_values(array_unique(array_filter(array_map('sanitize_key', $post_types))));
    }


    /**
     * Prepares all data needed to render the Buy Tickets form for a sellable entity (event/exhibition/etc).
     *
     * This method:
     * - Validates the requested "tickets-for" post id is sellable (via IW_Ticketing::is_sellable()).
     * - Loads ticket category definitions (option-based) and merges any post-level custom categories.
     * - Builds a normalized ticket categories array used by the front-end template/JS (min/max tickets, prices, subcategories).
     * - Loads schedule data from IW_Tickets_Calendar_Service and applies optional preselected date/time from rewrite vars.
     *
     * Side effects:
     * - Mutates global $post and calls setup_postdata() for the requested post (needed for get_the_ID(), template tags, etc).
     * - If the post is not sellable, it renders a "not found" template part and returns null.
     *
     * Data contract (returned array keys):
     * - ticket_categories (array<object>)   Normalized categories for the form UI.
     * - total_min_tickets (int)            Global minimum tickets required (post-level).
     * - total_max_tickets (int)            Global maximum tickets allowed (post-level; fallback 10).
     * - ajax_action (string)               Action used for AJAX/cart submit (used for nonce + data-action).
     * - schedule (array)                   Schedule structure from Calendar Service (allowDates, timesByDate, etc).
     * - tickets_date (string)              Preselected date in 'YYYY-MM-DD' or '' if none/invalid.
     * - tickets_time (string)              Preselected time in 'HH:MM' or '' if none/invalid.
     * - tickets_time_human (string)        Same as tickets_time but kept for template parity.
     *
     * Notes:
     * - Rewrite vars used: tickets-date (YYYY-MM-DD), tickets-time (HH-mm).
     * - Custom category keys and subcategory keys are sanitized via sanitize_key().
     * - Prices support comma decimal separator (e.g. "10,50") which is normalized to float.
     *
     * @param int $post_id The post ID for which we are buying tickets.
     * @return object<string, mixed>|null Returns prepared data object or null if not sellable.
     */

    public static function get_tickets_data($post_id = false)
    {

        if (empty($post_id))  {
            $post_id = absint(get_query_var('tickets-for'));
        }

        if (!self::is_sellable($post_id)) {
            return null;
        }



        if( get_field( 'is_permanent', $post_id ) && ! empty( $building = get_field( 'building_location', $post_id ) ) ) {
            wp_redirect( get_tickets_permalink( $building->ID ) );
        }


        $ticket_categories = [];
        $ticket_categories_info = (array)get_field('iw_ticket_categories', 'option');
        $post_ticket_categories = (array)get_field('ticket_categories', $post_id);
        $custom_ticket_categories = (array)get_field('custom_ticket_categories', $post_id);


        // Join custom categories into the same structures used by the default (option-based) categories.

        if (!empty($custom_ticket_categories)) {

            foreach ($custom_ticket_categories as $custom_cat) {
                if( ! $custom_cat ) continue;

                $custom_cat_key = sanitize_key((string)($custom_cat['key'] ?? ''));
                $custom_cat_label = (string)($custom_cat['label'] ?? '');
                $custom_cat_description = (string)($custom_cat['description'] ?? '');

                if ($custom_cat_key === '' || $custom_cat_label === '') {
                    continue;
                }

                $custom_subs = $custom_cat['subcategories'] ?? [];
                if (empty($custom_subs) || !is_array($custom_subs)) {
                    continue;
                }

                // Extend info array (labels/description/structure).
                $custom_cat_info = [
                    'key' => $custom_cat_key,
                    'label' => $custom_cat_label,
                    'description' => $custom_cat_description,
                    'subcategories' => [],
                ];

                // Ensure values array exists for this category.
                if (!isset($post_ticket_categories[$custom_cat_key]) || !is_array($post_ticket_categories[$custom_cat_key])) {
                    $post_ticket_categories[$custom_cat_key] = [];
                }

                // Category min/max tickets (same location as default categories).
                if (isset($custom_cat['min_tickets']) && is_numeric($custom_cat['min_tickets'])) {
                    $post_ticket_categories[$custom_cat_key]['min_tickets'] = (int)$custom_cat['min_tickets'];
                }

                if (isset($custom_cat['max_tickets']) && is_numeric($custom_cat['max_tickets'])) {
                    $post_ticket_categories[$custom_cat_key]['max_tickets'] = (int)$custom_cat['max_tickets'];
                }

                $post_ticket_categories[$custom_cat_key]['require_full_name'] = $custom_cat['require_full_name'] ?? false;

                foreach ($custom_subs as $custom_sub) {
                    $custom_sub_key = sanitize_key((string)($custom_sub['key'] ?? ''));
                    $custom_sub_label = (string)($custom_sub['label'] ?? '');
                    $custom_sub_description = (string)($custom_sub['description'] ?? '');

                    $price_raw = $custom_sub['price'] ?? null;
                    if (is_string($price_raw)) {
                        $price_raw = str_replace(',', '.', $price_raw);
                    }

                    if ($custom_sub_key === '' || $custom_sub_label === '' || !is_numeric($price_raw)) {
                        continue;
                    }

                    $custom_cat_info['subcategories'][] = [
                        'key' => $custom_sub_key,
                        'label' => $custom_sub_label,
                        'description' => $custom_sub_description,
                    ];

                    $post_ticket_categories[$custom_cat_key][$custom_sub_key] = (float)$price_raw;
                }

                if (!empty($custom_cat_info['subcategories'])) {
                    $ticket_categories_info[] = $custom_cat_info;
                }
            }
        }

        $min_tickets = empty($post_ticket_categories['min_tickets']) ? 0 : (int)$post_ticket_categories['min_tickets'];
        $max_tickets = empty($post_ticket_categories['max_tickets']) ? 10 : (int)$post_ticket_categories['max_tickets'];


        foreach ($ticket_categories_info as $cat_info) {
            if( empty( $cat_info ) ) continue;
            $cat_key = sanitize_key((string)$cat_info['key']);

            $sub_categories_info = $cat_info['subcategories'] ?: [];
            $sub_values = $post_ticket_categories[$cat_key] ?? [];

            $subcategories = [];
            $min_price = null;
            $max_price = null;

            foreach ($sub_categories_info as $sub_info) {
                $sub_key = sanitize_key((string)$sub_info['key']);
                $val = $sub_values[$sub_key] ?? null;

                if (!is_numeric($val)) {
                    continue;
                }

                $val_num = (float)$val;
                $min_price = ($min_price === null) ? $val_num : min($min_price, $val_num);
                $max_price = ($max_price === null) ? $val_num : max($max_price, $val_num);

                $subcategories[] = (object)[
                    'value' => $sub_key,
                    'label' => $sub_info['label'],
                    'selected' => false,
                    'price' => $val_num,
                    'attrs' => 'data-price="' . $val_num . '"',
                    'description' => $sub_info['description'],
                ];
            }

            if (empty($subcategories)) {
                continue;
            }

            array_unshift($subcategories, (object)[
                'value' => '',
                'selected' => false,
                'label' => __('Παρακαλούμε Επιλέξτε', 'iw-theme'),
            ]);

            $ticket_categories[] = (object)[
                'key' => $cat_key,
                'require_full_name' => $sub_values['require_full_name'] ?? false,
                'price' => ($min_price !== null) ? $min_price : 0,
                'min_price' => ($min_price !== null) ? $min_price : 0,
                'max_price' => ($max_price !== null) ? $max_price : 0,
                'min_tickets' => empty($sub_values['min_tickets']) ? 0 : $sub_values['min_tickets'],
                'max_tickets' => empty($sub_values['max_tickets']) ? $max_tickets : $sub_values['max_tickets'],
                'label' => $cat_info['label'],
                'description' => $cat_info['description'],
                'subcategories' => $subcategories,
            ];
        }

        $ajax_action = 'iw_cart_add_tickets_to_cart';

        $schedule = IW_Tickets_Calendar_Service::get_schedule( $post_id );

        // Optional preselected date/time from URL (rewrite vars).
        $tickets_date_raw = (string)get_query_var('tickets-date');
        $tickets_time_raw = (string)get_query_var('tickets-time');

        $tickets_date = '';
        $tickets_time = '';
        $tickets_time_human = '';

        if ($tickets_date_raw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tickets_date_raw)) {
            if (!empty($schedule['allowDates']) && in_array($tickets_date_raw, (array)$schedule['allowDates'], true)) {
                $tickets_date = $tickets_date_raw;
            }

            // Slots for selected date (if any)
            $slots = [];
            if (isset($schedule['timesByDate']) && isset($schedule['timesByDate']->$tickets_date)) {
                $slots = (array)$schedule['timesByDate']->$tickets_date;

                if (!empty($slots) && $tickets_time_raw && preg_match('/^\d{2}-\d{2}$/', $tickets_time_raw)) {
                    $tickets_time_human = str_replace('-', ':', $tickets_time_raw);

                    $has_time = false;
                    foreach ($slots as $slot) {
                        if (is_array($slot) && isset($slot['time']) && (string)$slot['time'] === $tickets_time_human) {
                            $has_time = true;
                            break;
                        }
                    }

                    $tickets_time = $has_time ? $tickets_time_human : '';
                }
            }
        }


        return (object) [

            'ajax_action' => $ajax_action,

            'post_id' => $post_id,

            'schedule' => $schedule,

            'tickets_date' => $tickets_date,
            'tickets_time' => $tickets_time,
            'tickets_time_human' => $tickets_time_human,

            'ticket_categories' => $ticket_categories,
            'min_tickets' => $min_tickets,
            'max_tickets' => $max_tickets,


        ];

    }

    public static function get_slot_hold_minutes(): int
    {
        $raw = get_field('iw_ticketing_slot_hold_minutes', 'option');
        $minutes = (is_numeric($raw) && (int)$raw > 0) ? (int)$raw : 15;

        if ($minutes < 1) $minutes = 1;
        elseif ($minutes > 1440) $minutes = 1440;

        return $minutes;
    }

    /**
     * Register custom WooCommerce order statuses for reservations
     */
    public static function register_order_statuses() {
        register_post_status('wc-res-confirmed', [
            'label'                     => __( 'Επιβεβαιωμένη κράτηση', 'iw-theme' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Επιβεβαιωμένη κράτηση (%s)',
                'Επιβεβαιωμένη κράτηση (%s)',
                'iw-theme'
            ),
        ]);

        register_post_status('wc-res-cancelled', [
            'label'                     => __( 'Ακυρωμένη κράτηση', 'iw-theme' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Ακυρωμένη κράτηση (%s)',
                'Ακυρωμένη κράτηση (%s)',
                'iw-theme'
            ),
        ]);

        register_post_status('wc-res-expired', [
            'label'                     => __( 'Ληγμένη κράτηση', 'iw-theme' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Ληγμένη κράτηση (%s)',
                'Ληγμένη κράτηση (%s)',
                'iw-theme'
            ),
        ]);
    }

    public static function add_order_statuses( $statuses ) {
        $reservation_statuses = [
            'wc-res-confirmed' => __( 'Επιβεβαιωμένη κράτηση', 'iw-theme' ),
            'wc-res-cancelled' => __( 'Ακυρωμένη κράτηση', 'iw-theme' ),
            'wc-res-expired'   => __( 'Ληγμένη κράτηση', 'iw-theme' ),
        ];

        $new = [];
        $inserted = false;

        foreach ( $statuses as $key => $label ) {
            $new[ $key ] = $label;

            if ( $key === 'wc-reserved' ) {
                $new += $reservation_statuses;
                $inserted = true;
            }
        }

        if ( ! $inserted ) {
            $new += $reservation_statuses;
        }

        return $new;
    }

    private static function get_reservation_statuses(): array {
        return [
            'reserved',
            'res-confirmed',
            'res-cancelled',
            'res-expired',
            'reservation-confirmed',
            'reservation-cancelled',
            'reservation-expired',
            'reservation-confi',
            'reservation-cance',
            'reservation-expir',
        ];
    }

    private static function is_ticket_order_item( $item ): bool {
        return $item && is_callable( [ $item, 'get_meta' ] ) && (int) $item->get_meta( 'tickets_for_id', true ) > 0;
    }

    public static function get_reservation_confirmation_window( int $order_item_id, $item = null, $order = null ): array {
        $order_item_id = (int) $order_item_id;

        if ( $order_item_id <= 0 ) {
            return [];
        }

        if ( ! $item ) {
            global $wpdb;
            $order_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d",
                    $order_item_id
                )
            );
            $order = $order_id ? wc_get_order( $order_id ) : null;
            $item  = $order ? $order->get_item( $order_item_id ) : null;
        }

        if ( ! self::is_ticket_order_item( $item ) ) {
            return [];
        }

        $tickets_day  = (string) $item->get_meta( 'tickets_day', true );
        $tickets_time = (string) $item->get_meta( 'tickets_time', true );

        if ( $tickets_day === '' || $tickets_time === '' ) {
            return [];
        }

        $tickets_time = str_replace( '-', ':', $tickets_time );
        if ( preg_match( '/^\d{2}:\d{2}$/', $tickets_time ) ) {
            $tickets_time .= ':00';
        }

        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $tickets_day ) || ! preg_match( '/^\d{2}:\d{2}:\d{2}$/', $tickets_time ) ) {
            return [];
        }

        try {
            $tz      = wp_timezone();
            $slot_dt = new DateTimeImmutable( trim( $tickets_day . ' ' . $tickets_time ), $tz );
        } catch ( Exception $e ) {
            return [];
        }

        $default_start_days = (int) apply_filters( 'iw_learning_program_reservation_days_before', 4 );
        $start_days = (int) apply_filters(
            'iw_learning_program_confirmation_start_days_before',
            $default_start_days,
            $order_item_id,
            $item,
            $order
        );
        $deadline_days = (int) apply_filters(
            'iw_learning_program_confirmation_days_before',
            2,
            $order_item_id,
            $item,
            $order
        );

        $start_days    = max( 0, $start_days );
        $deadline_days = max( 0, $deadline_days );

        $start_dt = $slot_dt->modify( '-' . $start_days . ' days' );
        $end_dt   = $slot_dt->modify( '-' . $deadline_days . ' days' );

        if ( $start_dt->getTimestamp() > $end_dt->getTimestamp() ) {
            [ $start_dt, $end_dt ] = [ $end_dt, $start_dt ];
        }

        $now_ts   = time();
        $start_ts = $start_dt->getTimestamp();
        $end_ts   = $end_dt->getTimestamp();

        return [
            'slot'            => $slot_dt,
            'start'           => $start_dt,
            'end'             => $end_dt,
            'start_timestamp' => $start_ts,
            'end_timestamp'   => $end_ts,
            'formatted_start' => wp_date( 'd/m/Y H:i', $start_ts ),
            'formatted_end'   => wp_date( 'd/m/Y H:i', $end_ts ),
            'has_started'     => $now_ts >= $start_ts,
            'has_ended'       => $now_ts > $end_ts,
            'is_open'         => $now_ts >= $start_ts && $now_ts <= $end_ts,
            'start_days'      => $start_days,
            'deadline_days'   => $deadline_days,
        ];
    }

    public static function get_reservation_confirmation_window_message( array $window ): string {
        if ( empty( $window['formatted_start'] ) || empty( $window['formatted_end'] ) ) {
            return '';
        }

        if ( ! empty( $window['is_open'] ) ) {
            return sprintf(
                __( 'Μπορείτε να επιβεβαιώσετε την κράτηση από %1$s έως %2$s.', 'iw-theme' ),
                '<strong>' . esc_html( $window['formatted_start'] ) . '</strong>',
                '<strong>' . esc_html( $window['formatted_end'] ) . '</strong>'
            );
        }

        if ( ! empty( $window['has_ended'] ) ) {
            return sprintf(
                __( 'Η κράτησή σας έχει ακυρωθεί αυτόματα, καθώς η επιβεβαίωση ήταν διαθέσιμη από %1$s έως %2$s και η προθεσμία έχει λήξει.', 'iw-theme' ),
                '<strong>' . esc_html( $window['formatted_start'] ) . '</strong>',
                '<strong>' . esc_html( $window['formatted_end'] ) . '</strong>'
            );
        }

        return sprintf(
            __( 'Η επιβεβαίωση της κράτησης θα είναι διαθέσιμη από %1$s έως %2$s.', 'iw-theme' ),
            '<strong>' . esc_html( $window['formatted_start'] ) . '</strong>',
            '<strong>' . esc_html( $window['formatted_end'] ) . '</strong>'
        );
    }

    public static function schedule_reservation_confirmation_emails( $order ): void {
        $order = is_numeric( $order ) ? wc_get_order( (int) $order ) : $order;

        if ( ! $order || ! is_callable( [ $order, 'get_items' ] ) ) {
            return;
        }

        foreach ( $order->get_items() as $item_id => $item ) {
            if ( ! self::is_ticket_order_item( $item ) ) {
                continue;
            }

            if ( wc_get_order_item_meta( $item_id, '_reservation_confirmation_email_sent_at', true ) ) {
                continue;
            }

            $window = self::get_reservation_confirmation_window( (int) $item_id, $item, $order );
            if ( empty( $window['start_timestamp'] ) ) {
                continue;
            }

            wc_update_order_item_meta(
                (int) $item_id,
                '_reservation_confirmation_email_scheduled_at',
                wp_date( 'Y-m-d H:i:s', (int) $window['start_timestamp'] )
            );

            if ( ! empty( $window['is_open'] ) ) {
                self::send_reservation_confirmation_email_for_item( (int) $order->get_id(), (int) $item_id );
                continue;
            }

            if ( ! empty( $window['has_ended'] ) ) {
                continue;
            }

            $args = [ (int) $order->get_id(), (int) $item_id ];
            if ( ! wp_next_scheduled( 'iw_send_reservation_confirmation_email', $args ) ) {
                wp_schedule_single_event( (int) $window['start_timestamp'], 'iw_send_reservation_confirmation_email', $args );
            }
        }
    }

    public static function send_reservation_confirmation_email_for_item( int $order_id, int $order_item_id ): bool {
        $order = wc_get_order( $order_id );
        $item  = $order ? $order->get_item( $order_item_id ) : null;

        if ( ! $order || ! self::is_ticket_order_item( $item ) ) {
            return false;
        }

        if ( wc_get_order_item_meta( $order_item_id, '_reservation_confirmed', true )
            || wc_get_order_item_meta( $order_item_id, '_reservation_cancelled', true )
            || wc_get_order_item_meta( $order_item_id, '_reservation_confirmation_email_sent_at', true )
        ) {
            return false;
        }

        $window = self::get_reservation_confirmation_window( $order_item_id, $item, $order );
        if ( empty( $window ) || ! empty( $window['has_ended'] ) ) {
            return false;
        }

        if ( empty( $window['is_open'] ) ) {
            self::schedule_reservation_confirmation_emails( $order );
            return false;
        }

        $to = (string) $order->get_billing_email();
        if ( $to === '' ) {
            $user = $order->get_user_id() ? get_user_by( 'id', (int) $order->get_user_id() ) : null;
            $to = $user && ! empty( $user->user_email ) ? (string) $user->user_email : '';
        }

        if ( $to === '' ) {
            return false;
        }

        $post_id = (int) $item->get_meta( 'tickets_for_id', true );
        $title   = $post_id ? get_the_title( $post_id ) : $item->get_name();
        $link    = class_exists( 'IW_Ticketing_Endpoints' )
            ? IW_Ticketing_Endpoints::get_order_item_url( $order_item_id, 'reservations' )
            : wc_get_account_endpoint_url( 'reservations' );

        $subject = sprintf( __( 'Επιβεβαίωση κράτησης: %s', 'iw-theme' ), wp_strip_all_tags( (string) $title ) );
        $message = '<p>' . esc_html__( 'Το παράθυρο επιβεβαίωσης της κράτησής σας είναι πλέον ανοιχτό.', 'iw-theme' ) . '</p>';
        $message .= '<p><strong>' . esc_html( $title ) . '</strong><br>';
        $message .= wp_kses_post( self::get_reservation_confirmation_window_message( $window ) ) . '</p>';
        $message .= '<p><a href="' . esc_url( $link ) . '" style="display:inline-block;padding:14px 22px;background:#31312F;color:#FFFFFF;text-decoration:none;border-radius:999px;">' . esc_html__( 'Επιβεβαίωση κράτησης', 'iw-theme' ) . '</a></p>';

        $mailer = function_exists( 'WC' ) ? WC()->mailer() : null;
        if ( $mailer ) {
            $message = $mailer->wrap_message( $subject, $message );
            $sent    = $mailer->send( $to, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
        } else {
            $sent = wp_mail( $to, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
        }

        if ( $sent ) {
            wc_update_order_item_meta( $order_item_id, '_reservation_confirmation_email_sent_at', current_time( 'mysql' ) );
        }

        return (bool) $sent;
    }

    public static function reconcile_reservation_order_status( $order ): void {
        if ( ! $order || ! in_array( $order->get_status(), self::get_reservation_statuses(), true ) ) {
            return;
        }

        $total = 0;
        $confirmed = 0;
        $cancelled = 0;
        $expired = 0;
        $pending = 0;

        foreach ( $order->get_items() as $item_id => $item ) {
            if ( ! self::is_ticket_order_item( $item ) ) {
                continue;
            }

            $total++;

            if ( wc_get_order_item_meta( $item_id, '_reservation_confirmed', true ) ) {
                $confirmed++;
                continue;
            }

            if ( wc_get_order_item_meta( $item_id, '_reservation_cancelled', true ) ) {
                $cancelled++;
                continue;
            }

            if ( wc_get_order_item_meta( $item_id, '_reservation_expired', true ) ) {
                $expired++;
                continue;
            }

            $pending++;
        }

        if ( $total === 0 || $pending > 0 ) {
            return;
        }

        if ( $confirmed > 0 ) {
            $order->update_status( 'res-confirmed', __( 'Reservation resolved with confirmed tickets.', 'iw-theme' ) );
            return;
        }

        if ( $cancelled === $total ) {
            $order->update_status( 'res-cancelled', __( 'All reservation items cancelled.', 'iw-theme' ) );
            return;
        }

        if ( $cancelled + $expired === $total ) {
            $order->update_status( 'res-expired', __( 'Reservation expired before confirmation.', 'iw-theme' ) );
        }
    }

    /**
     * AJAX: Confirm reservation for a specific order item
     */
    public static function ajax_confirm_reservation() {

        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'iw_confirm_reservation')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $order_item_id = isset($_POST['order_item_id']) ? (int)$_POST['order_item_id'] : 0;

        if (!$order_item_id) {
            wp_send_json_error(['message' => 'Invalid order item']);
        }

        global $wpdb;

        $order_id = (int) $wpdb->get_var( $wpdb->prepare("SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d", $order_item_id ) );

        $order = $order_id ? wc_get_order($order_id) : null;

        if (!$order || $order->get_user_id() !== get_current_user_id()) {
            wp_send_json_error(['message' => 'Order not found']);
        }

        $item = $order->get_item($order_item_id);

        if ( ! self::is_ticket_order_item( $item ) ) {
            wp_send_json_error(['message' => __( 'Invalid reservation item.', 'iw-theme' )]);
        }

        // Avoid double confirmation
        $already_confirmed = wc_get_order_item_meta($order_item_id, '_reservation_confirmed', true);
        if ($already_confirmed) {
            wp_send_json_success([
                'message' => __( 'Η κράτηση έχει ήδη επιβεβαιωθεί.', 'iw-theme' ),
                'redirect' => IW_Ticketing_Endpoints::get_order_item_url( $order_item_id, 'tickets' ),
            ]);
        }

        // Do not allow confirmation if the reservation item was cancelled
        $already_cancelled = wc_get_order_item_meta($order_item_id, '_reservation_cancelled', true);
        if ($already_cancelled) {
            wp_send_json_error([
                'message' => __( 'Η κράτηση έχει ήδη ακυρωθεί.', 'iw-theme' ),
                'redirect' => wc_get_account_endpoint_url( 'reservations' ),
            ]);
        }

        if ($order->get_status() !== 'reserved') {
            wp_send_json_error(['message' => __( 'Η κράτηση δεν είναι πλέον διαθέσιμη για επιβεβαίωση.', 'iw-theme' )]);
        }

        $confirmation_window = self::get_reservation_confirmation_window( $order_item_id, $item, $order );
        if ( empty( $confirmation_window ) || empty( $confirmation_window['is_open'] ) ) {
            if ( ! empty( $confirmation_window['has_ended'] ) ) {
                wc_update_order_item_meta($order_item_id, '_reservation_expired', 1);
                wc_update_order_item_meta($order_item_id, '_reservation_expired_at', current_time('mysql'));

                if ( class_exists( 'IW_Tickets_DB' ) && method_exists( 'IW_Tickets_DB', 'release_order_item_holds' ) ) {
                    IW_Tickets_DB::release_order_item_holds( $order_item_id );
                }

                self::reconcile_reservation_order_status( $order );
            }

            $message = ! empty( $confirmation_window )
                ? self::get_reservation_confirmation_window_message( $confirmation_window )
                : __( 'Η κράτηση δεν είναι διαθέσιμη για επιβεβαίωση αυτή τη στιγμή.', 'iw-theme' );

            wp_send_json_error([ 'message' => $message ]);
        }

        if ( class_exists( 'IW_Tickets_DB' ) && method_exists( 'IW_Tickets_DB', 'order_item_has_active_holds' ) && ! IW_Tickets_DB::order_item_has_active_holds( $order_item_id ) ) {
            wc_update_order_item_meta($order_item_id, '_reservation_expired', 1);
            wc_update_order_item_meta($order_item_id, '_reservation_expired_at', current_time('mysql'));
            if ( class_exists( 'IW_Tickets_DB' ) && method_exists( 'IW_Tickets_DB', 'release_order_item_holds' ) ) {
                IW_Tickets_DB::release_order_item_holds( $order_item_id );
            }
            self::reconcile_reservation_order_status( $order );
            wp_send_json_error(['message' => __( 'Η προθεσμία επιβεβαίωσης της κράτησης έχει λήξει.', 'iw-theme' )]);
        }

        // Issue tickets if they do not already exist
        $existing_tickets = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}iw_tickets WHERE order_item_id = %d", $order_item_id ));

        if ($existing_tickets === 0 ) {
            if ($item) {
                IW_WC_Tickets_Cart_Manager::issue_tickets_for_order_item(
                    $order,
                    $order_id,
                    $order_item_id,
                    $item
                );
            }
        }

        $issued_tickets = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}iw_tickets WHERE order_item_id = %d", $order_item_id ));
        if ( $issued_tickets <= 0 ) {
            wp_send_json_error(['message' => __( 'Δεν ήταν δυνατή η έκδοση των εισιτηρίων της κράτησης.', 'iw-theme' )]);
        }

        if ( class_exists( 'IW_Tickets_DB' ) && method_exists( 'IW_Tickets_DB', 'consume_order_item_holds_to_booked' ) ) {
            IW_Tickets_DB::consume_order_item_holds_to_booked( $order_item_id );
        }

        // Mark order item as confirmed
        wc_update_order_item_meta($order_item_id, '_reservation_confirmed', 1);
        wc_update_order_item_meta($order_item_id, '_reservation_confirmed_at', current_time('mysql'));

        self::reconcile_reservation_order_status( $order );

        wp_send_json_success([
            'message' => __( 'Η κράτηση επιβεβαιώθηκε.', 'iw-theme' ),
            'redirect' => IW_Ticketing_Endpoints::get_order_item_url( $order_item_id, 'tickets' ),
        ]);
    }

    /**
     * AJAX: Cancel reservation for a specific order item
     */
    public static function ajax_cancel_reservation() {

        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'iw_cancel_reservation')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $order_item_id = isset($_POST['order_item_id']) ? (int)$_POST['order_item_id'] : 0;

        if (!$order_item_id) {
            wp_send_json_error(['message' => 'Invalid order item']);
        }

        global $wpdb;

        $order_id = (int)$wpdb->get_var( $wpdb->prepare("SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d", $order_item_id ));

        $order = $order_id ? wc_get_order($order_id) : null;

        if (!$order || $order->get_user_id() !== get_current_user_id()) {
            wp_send_json_error(['message' => 'Order not found']);
        }

        $item = $order->get_item($order_item_id);

        if ( ! self::is_ticket_order_item( $item ) ) {
            wp_send_json_error(['message' => __( 'Invalid reservation item.', 'iw-theme' )]);
        }

        // Prevent cancelling if already confirmed
        $already_confirmed = wc_get_order_item_meta($order_item_id, '_reservation_confirmed', true);
        if ($already_confirmed) {
            wp_send_json_error(['message' => __( 'Οι επιβεβαιωμένες κρατήσεις δεν μπορούν να ακυρωθούν.', 'iw-theme' )]);
        }

        // Prevent double cancel
        $already_cancelled = wc_get_order_item_meta($order_item_id, '_reservation_cancelled', true);
        if ($already_cancelled) {
            wp_send_json_success([
                'message' => __( 'Η κράτηση έχει ήδη ακυρωθεί.', 'iw-theme' ),
                'redirect' => wc_get_account_endpoint_url( 'reservations' ),
            ]);
        }

        if ($order->get_status() !== 'reserved') {
            wp_send_json_error(['message' => __( 'Η κράτηση δεν είναι πλέον διαθέσιμη για ακύρωση.', 'iw-theme' )]);
        }

        $confirmation_window = self::get_reservation_confirmation_window( $order_item_id, $item, $order );
        if ( ! empty( $confirmation_window['has_ended'] ) ) {
            wc_update_order_item_meta($order_item_id, '_reservation_expired', 1);
            wc_update_order_item_meta($order_item_id, '_reservation_expired_at', current_time('mysql'));

            if ( class_exists( 'IW_Tickets_DB' ) && method_exists( 'IW_Tickets_DB', 'release_order_item_holds' ) ) {
                IW_Tickets_DB::release_order_item_holds( $order_item_id );
            }

            self::reconcile_reservation_order_status( $order );

            wp_send_json_error([
                'message' => self::get_reservation_confirmation_window_message( $confirmation_window ),
                'redirect' => wc_get_account_endpoint_url( 'reservations' ),
            ]);
        }

        IW_Tickets_DB::release_order_item_holds($order_item_id);

        wc_update_order_item_meta($order_item_id, '_reservation_cancelled', 1);
        wc_update_order_item_meta($order_item_id, '_reservation_cancelled_at', current_time('mysql'));

        self::reconcile_reservation_order_status( $order );

        wp_send_json_success([
            'message' => __( 'Η κράτηση ακυρώθηκε.', 'iw-theme' ),
            'redirect' => wc_get_account_endpoint_url( 'reservations' ),
        ]);
    }
    /**
     * Prints hidden fields required for confirm reservation AJAX
     */
    public static function print_confirm_reservation_ajax_fields()
    {
        echo '<input type="hidden" name="action" value="iw_confirm_reservation">';
        echo '<input type="hidden" name="security" value="' . esc_attr(wp_create_nonce('iw_confirm_reservation')) . '">';
    }

    /**
     * Prints hidden fields required for cancel reservation AJAX
     */
    public static function print_cancel_reservation_ajax_fields()
    {
        echo '<input type="hidden" name="action" value="iw_cancel_reservation">';
        echo '<input type="hidden" name="security" value="' . esc_attr(wp_create_nonce('iw_cancel_reservation')) . '">';
    }

    public static function get_ticket_orders( $type = 'active', $per_page = 1000, $paged = 1 ) {
        global $wpdb;
        if ( ! in_array( $type, [ 'active', 'past', 'all' ], true ) ) $type = 'active';
        $per_page = ( is_numeric( $per_page ) && (int) $per_page > 0 ) ? (int) $per_page : 1000;
        $paged    = ( is_numeric( $paged ) && (int) $paged > 0 ) ? (int) $paged : 1;
        $offset   = ( $paged - 1 ) * $per_page;
        if (  empty( $customer_id = get_current_user_id() ) ) return [];

        $cache_key = 'iw_ticket_orders_' . md5( $customer_id . '|' . $type . '|' . $per_page . '|' . $paged );
        $cached = wp_cache_get( $cache_key, 'iw_ticketing' );
        if ( $cached !== false ) {
            return $cached;
        }

        $order_ids = wc_get_orders( [ 'customer_id' => $customer_id, 'limit' => -1, 'orderby' => 'date', 'order' => 'DESC', 'status' => [ 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-reserved', 'wc-res-confirmed', 'wc-reservation-confirmed', 'wc-reservation-confi' ], 'return' => 'ids'] );
        if ( empty( $order_ids ) || ! is_array( $order_ids ) ) {
            return [];
        }

        $items  = [];
        $groups = [];




        $tz = new DateTimeZone( wp_timezone_string() );
        $placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );

        $type_where = $type === 'all' ? '' : ( $type === 'active' ? ' AND t.slot_start >= NOW() ' : ' AND t.slot_start < NOW() ' );
        $type_params = [];

        $sql = "SELECT t.order_id,
                       t.order_item_id,
                       MIN(t.slot_start) AS slot_start,
                       MIN(t.slot_end) AS slot_end,
                       MIN(t.post_id) AS post_id,
                       COUNT(*) AS tickets_count
                FROM {$wpdb->prefix}iw_tickets t
                WHERE t.order_id IN ($placeholders) AND t.slot_start IS NOT NULL {$type_where}
                GROUP BY t.order_item_id
                ORDER BY slot_start DESC
                LIMIT %d OFFSET %d";


        $params = array_merge( array_map( 'intval', $order_ids ), [ $per_page, $offset ]);
        $rows   = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

        $count_sql = "SELECT COUNT(DISTINCT t.order_item_id)
                      FROM {$wpdb->prefix}iw_tickets t
                      WHERE t.order_id IN ($placeholders) AND t.slot_start IS NOT NULL {$type_where}";




        $total_items = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, array_map('intval',$order_ids) ) );

        $total_pages = $per_page > 0 ? (int) ceil( $total_items / $per_page ) : 1;



        foreach ( (array) $rows as $row ) {
            $post_id = isset( $row->post_id ) ? (int) $row->post_id : 0;
            $post = $post_id ? get_post( $post_id ) : null;
            if ( ! $post ) { continue; }

            $end = ! empty( $row->slot_end ) ? new DateTimeImmutable( (string) $row->slot_end, $tz ) : null;
            $start = new DateTimeImmutable( (string) $row->slot_start, $tz );
            $entry = [
                'order_id'      => (int) $row->order_id,
                'order_item_id' => isset($row->order_item_id) ? (int)$row->order_item_id : 0,
                'post_id'       => $post_id,
                'post'          => $post,
                'slot_start'    => $start,
                'slot_end'      => $end,
                'tickets_count' => (int) $row->tickets_count ?? 0,
            ];
            $items[] = $entry;
            if ( ! isset( $groups[ $post->post_type ] ) ) {
                $groups[ $post->post_type ] = [];
            }
            $groups[ $post->post_type ][] = $entry;
        }

        $render_order = self::get_supported_post_types();


        $sorted_groups = [];
        foreach ( $render_order as $pt ) {
            if ( isset( $groups[ $pt ] ) ) {
                $sorted_groups[ $pt ] = $groups[ $pt ];
            }
        }

        if ( empty( $items ) ) {
            wp_cache_set( $cache_key, [], 'iw_ticketing', 60 );
            return [];
        }

        $result = [
            'items'       => $items,
            'byPostType'  => $sorted_groups,
            'pagination'  => (object) [
                'per_page'     => $per_page,
                'paged'        => $paged,
                'total_items'  => $total_items,
                'max_num_pages'  => $total_pages,
            ],
        ];

        wp_cache_set( $cache_key, $result, 'iw_ticketing', 60 ); // cache for 60 seconds

        return $result;
    }
    public static function get_order_tickets( $order_item_id ){
        global $wpdb;
        $order_item_id = (int) $order_item_id;
        $current_user_id = get_current_user_id();
        $tz = new DateTimeZone( wp_timezone_string() );

        if ( $current_user_id <= 0 || $order_item_id <= 0 ) {
            return [];
        }

        $order_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d", $order_item_id ) );

        $order = $order_id ? wc_get_order( $order_id ) : null;

        if ( ! $order || $order->get_user_id() !== $current_user_id ) {
            return [];
        }

        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT t.* FROM {$wpdb->prefix}iw_tickets t WHERE t.order_item_id = %d ORDER BY t.slot_start ASC", $order_item_id ) );

        if( ! $rows ) { return []; }
        $row = $rows[0];
        $post_id = isset( $row->post_id ) ? (int) $row->post_id : 0;
        $post = $post_id ? get_post( $post_id ) : null;

        $end = ! empty( $row->slot_end ) ? new DateTimeImmutable( (string) $row->slot_end, $tz ) : null;
        $start = new DateTimeImmutable( (string) $row->slot_start, $tz );

        return [
            'order' => [
                'order_id' => $row->order_id,
                'order_item_id' => $order_item_id,
                'post' => $post,
                'slot_start' => $start,
                'slot_end' => $end,
                'tickets_count' => count( $rows )
            ],
            'tickets' => $rows
        ];

    }

    public static function get_reservation_orders( $per_page = 1000, $paged = 1 ) {
        global $wpdb;

        $per_page = ( is_numeric( $per_page ) && (int) $per_page > 0 ) ? (int) $per_page : 1000;
        $paged    = ( is_numeric( $paged ) && (int) $paged > 0 ) ? (int) $paged : 1;
        $offset   = ( $paged - 1 ) * $per_page;

        if ( empty( $customer_id = get_current_user_id() ) ) return [];
        $orders = wc_get_orders( [ 'customer_id' => $customer_id, 'limit' => -1, 'status' => [ 'wc-reserved' ] ]);

        if ( empty( $orders ) || ! is_array( $orders ) ) return [];

        $items  = [];
        $groups = [];

        $tz = new DateTimeZone( wp_timezone_string() );

        // Build order IDs list
        $order_ids = array_map(function($o){ return (int)$o->get_id(); }, $orders);
        if ( empty( $order_ids ) ) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($order_ids), '%d'));

        $sql = "SELECT oi.order_id,
                       h.order_item_id,
                       MIN(CONCAT(h.slot_date,' ',h.slot_time)) AS slot_start,
                       h.post_id AS post_id,
                       SUM(h.qty) AS tickets_count
                FROM {$wpdb->prefix}iw_ticket_slot_holds h
                INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = h.order_item_id
                WHERE oi.order_id IN ($placeholders) AND h.expires_at > %s
                GROUP BY h.order_item_id, h.post_id
                ORDER BY slot_start DESC
                LIMIT %d OFFSET %d";

        $params = array_merge( $order_ids, [ current_time( 'mysql' ), $per_page, $offset ] );

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

        $count_sql = "SELECT COUNT(DISTINCT h.order_item_id)
                      FROM {$wpdb->prefix}iw_ticket_slot_holds h
                      INNER JOIN {$wpdb->prefix}woocommerce_order_items oi
                      ON oi.order_item_id = h.order_item_id
                      WHERE oi.order_id IN ($placeholders) AND h.expires_at > %s";
        $total_items = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, array_merge( $order_ids, [ current_time( 'mysql' ) ] ) ) );
        $total_pages = $per_page > 0 ? (int) ceil( $total_items / $per_page ) : 1;

        foreach ( (array) $rows as $row ) {

            $post_id = isset( $row->post_id ) ? (int) $row->post_id : 0;
            $post = $post_id ? get_post( $post_id ) : null;

            if ( ! $post ) {
                continue;
            }

            $start = new DateTimeImmutable( (string) $row->slot_start, $tz );

            $order_id = isset($row->order_id) ? (int)$row->order_id : 0;

            $entry = [
                'order_id'      => $order_id,
                'order_item_id' => isset($row->order_item_id) ? (int)$row->order_item_id : 0,
                'post'          => $post,
                'slot_start'    => $start,
                'tickets_count' => (int) $row->tickets_count ?? 0,
            ];

            $items[] = $entry;

            if ( ! isset( $groups[ $post->post_type ] ) ) {
                $groups[ $post->post_type ] = [];
            }

            $groups[ $post->post_type ][] = $entry;
        }

        if ( empty( $items ) ) {
            return [];
        }

        return [
            'items'      => $items,
            'byPostType' => $groups,
            'pagination' => (object) [
                'per_page'      => $per_page,
                'paged'         => $paged,
                'total_items'   => $total_items,
                'max_num_pages' => $total_pages,
            ],
        ];
    }

    public static function get_reservation_order_tickets( $order_item_id ){
        global $wpdb;

        $order_item_id = (int) $order_item_id;
        $current_user_id = get_current_user_id();
        $tz = new DateTimeZone( wp_timezone_string() );

        if ( $current_user_id <= 0 || $order_item_id <= 0 ) {
            return [];
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT h.*, oi.order_id
                 FROM {$wpdb->prefix}iw_ticket_slot_holds h
                 LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi
                 ON oi.order_item_id = h.order_item_id
                 WHERE h.order_item_id = %d AND h.expires_at > %s
                 ORDER BY CONCAT(h.slot_date,' ',h.slot_time) ASC",
                $order_item_id,
                current_time( 'mysql' )
            )
        );

        if ( ! $rows ) {
            return [];
        }

        $row = $rows[0];
        $order_id = isset($row->order_id) ? (int)$row->order_id : 0;
        $order = $order_id ? wc_get_order( $order_id ) : null;

        if ( ! $order || $order->get_user_id() !== $current_user_id ) {
            return [];
        }

        $post_id = isset( $row->post_id ) ? (int) $row->post_id : 0;
        $post = $post_id ? get_post( $post_id ) : null;

        $start = new DateTimeImmutable( (string) ($row->slot_date . ' ' . $row->slot_time), $tz );

        $qty_total = array_sum(array_map(function($r){ return (int)($r->qty ?? 0); }, $rows));

        return [
            'order' => [
                'order_id' => $order_id,
                'order_item_id' => (int)$order_item_id,
                'post' => $post,
                'slot_start' => $start,
                'tickets_count' => $qty_total
            ],
            'tickets' => $rows
        ];
    }


}


register_activation_hook( __FILE__, [ 'IW_Tickets_DB', 'activate' ] );
register_activation_hook( __FILE__, [ 'CPT_As_Product', 'activate' ] );
register_activation_hook( __FILE__, function(){
    IW_Ticketing_Endpoints::get_instance()->register_my_account_endpoint();
    flush_rewrite_rules( false );
    update_option( 'iw_tickets_rewrite_version', IW_TICKETS_REWRITE_VERSION );
});

add_action( 'plugins_loaded', function(){
    IW_Ticketing::get_instance();
    IW_Ticketing_Endpoints::get_instance();
    IW_Ticket_PDF_Service::init();
    IW_Zebra_Print_Queue::init();
    IW_Cashier_Ticketing::init();
});
