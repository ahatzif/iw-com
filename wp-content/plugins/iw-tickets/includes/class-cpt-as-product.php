
<?php

class CPT_As_Product {

    public static function init() {
        $instance = new self();
        $instance->hooks();
    }

    public static function activate()
    {
        if ( (int) get_option( 'iw_cpt_virtual_product_id', 0 ) <= 0 && function_exists( 'wc_get_product' ) ) {
            $product = new WC_Product_Simple();
            $product->set_name( 'Tickets Virtual Placeholder' );
            $product->set_status( 'publish' );
            $product->set_catalog_visibility( 'hidden' );
            $product->set_virtual( true );
            $product->set_downloadable( false );
            $product->set_regular_price( '0' );
            $product_id = $product->save();
            if ( $product_id > 0 ) {
                update_option( 'iw_cpt_virtual_product_id', (int) $product_id );
            }
        }
    }
    public function hooks() {
        add_filter('woocommerce_product_class', [$this, 'set_product_class'], 10, 4);
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 2);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'restore_cart_item'], 10, 3);
        add_filter('woocommerce_cart_item_price', [$this, 'override_cart_item_price'], 10, 2);
        add_filter('woocommerce_cart_item_name', [$this, 'override_cart_item_name'], 10, 2);
        add_action('woocommerce_before_calculate_totals', [$this, 'set_cart_item_price_on_totals'], 10, 1);
        add_filter('woocommerce_get_item_data', [$this, 'render_cart_item_meta'], 10, 2);
        add_filter('woocommerce_cart_item_permalink', [$this, 'override_cart_item_permalink'], 10, 3);

        add_action( 'woocommerce_add_to_cart', [$this, 'maybe_create_ticket_hold'], 10, 6 );
        add_action( 'woocommerce_cart_item_removed', [$this, 'maybe_release_ticket_hold'], 10, 2 );
        add_action( 'woocommerce_cart_emptied', [$this, 'release_all_ticket_holds'] );
        add_action( 'woocommerce_before_cart_display', [$this, 'remove_expired_ticket_items_from_cart'] );
        add_action( 'woocommerce_checkout_create_order', [$this, 'attach_hold_token_to_order'], 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'attach_virtual_item_meta_to_order_item' ], 10, 4 );
        add_action( 'woocommerce_payment_complete', [$this, 'consume_holds_on_payment_complete'] );
        add_action( 'woocommerce_order_status_cancelled', [$this, 'release_holds_on_order_cancelled'] );
        add_action( 'woocommerce_order_status_failed', [$this, 'release_holds_on_order_cancelled'] );
    }




    private function get_virtual_product_id(){
        $id = (int) get_option( 'iw_cpt_virtual_product_id', 0 );
        return $id > 0 ? $id : 0;
    }


    public static function get_cpt_virtual_product_id(){
        $id = (int) get_option( 'iw_cpt_virtual_product_id', 0 );
        return $id > 0 ? $id : 0;
    }

    /**
     * Add a virtual (placeholder) product to cart with custom title/price and arbitrary meta.
     * Used for CPT priced items and for Tickets.
     */
    public static function add_to_cart( array $args ){
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return new WP_Error( 'no_cart', 'Cart not available' );
        }

        $virtual_product_id = self::get_cpt_virtual_product_id();
        if ( $virtual_product_id <= 0 ) {
            return new WP_Error( 'no_virtual_product', 'Virtual product not configured' );
        }

        $type  = isset( $args['type'] ) ? sanitize_key( (string) $args['type'] ) : 'cpt';
        $title = isset( $args['title'] ) ? (string) $args['title'] : '';
        $price = isset( $args['price'] ) ? (float) $args['price'] : 0.0;
        $meta  = isset( $args['meta'] ) && is_array( $args['meta'] ) ? $args['meta'] : [];

        $title = apply_filters( 'iw_virtual_cart_item_title', $title, $type, $meta, $args );
        $price = apply_filters( 'iw_virtual_cart_item_price', $price, $type, $meta, $args );
        $meta  = apply_filters( 'iw_virtual_cart_item_meta',  $meta,  $type, $title, $price, $args );

        $cart_item_data = array_merge(
            [
                'iw_item_type' => $type,
                'title'        => $title,
                'price'        => $price,
            ],
            $meta
        );

        $cart_item_data = apply_filters( 'iw_virtual_cart_item_data', $cart_item_data, $type, $args );

        // Force uniqueness to prevent Woo from merging items with same product_id.
        $cart_item_data['unique_key'] = md5( wp_json_encode( $cart_item_data ) . '|' . microtime( true ) . '|' . wp_rand() );

        $cart_item_key = WC()->cart->add_to_cart( $virtual_product_id, 1, 0, [], $cart_item_data );
        if ( ! $cart_item_key ) {
            return new WP_Error( 'add_to_cart_failed', 'Failed to add to cart' );
        }
        return $cart_item_key;
    }

    public static function is_cpt_product( $product ){
        $virtual_id = self::get_cpt_virtual_product_id();
        if( $virtual_id <= 0 ){
            return false;
        }

        $product_id = 0;
        if( is_numeric( $product ) ){
            $product_id = (int) $product;
        }else if( is_object( $product ) ){
            if( method_exists( $product, 'get_id' ) ){
                $product_id = (int) $product->get_id();
            }else if( isset( $product->ID ) ){
                $product_id = (int) $product->ID;
            }
        }

        return $product_id > 0 && $product_id === $virtual_id;
    }

    public static function is_cpt_cart_item( $cart_item ){
        if( ! is_array( $cart_item ) ) return false;
        if ( ! empty( $cart_item['iw_item_type'] ) ) return true;
        if( ! empty( $cart_item['cpt_id'] ) ) return true;
        $virtual_id = self::get_cpt_virtual_product_id();
        if( $virtual_id <= 0 ) return false;
        $product_id = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
        return $product_id > 0 && $product_id === $virtual_id;
    }

    /**
     * Helper to get CPT post id from cart item (0 if not a CPT item).
     */
    public static function get_cpt_id_from_cart_item( $cart_item ){
        if ( ! self::is_cpt_cart_item( $cart_item ) ) {
            return 0;
        }

        // Tickets are also virtual items; their "content" post id is stored under tickets_for_id.
        if ( ! empty( $cart_item['iw_item_type'] ) && $cart_item['iw_item_type'] === 'tickets' ) {
            return ! empty( $cart_item['tickets_for_id'] ) ? (int) $cart_item['tickets_for_id'] : 0;
        }

        // Legacy CPT-as-product items.
        return ! empty( $cart_item['cpt_id'] ) ? (int) $cart_item['cpt_id'] : 0;
    }

    /**
     * Returns the underlying content post id for any virtual item (CPT or Tickets).
     * Alias of get_cpt_id_from_cart_item() for backward compatibility.
     */
    public static function get_content_post_id_from_cart_item( $cart_item ){
        return self::get_cpt_id_from_cart_item( $cart_item );
    }


    public function set_product_class( $classname, $product_type, $post_type, $product_id ) {
        $virtual_id = $this->get_virtual_product_id();
        if ( $virtual_id > 0 && (int) $product_id === (int) $virtual_id ) {
            return 'WC_Product_CPT_Virtual';
        }
        return $classname;
    }

    public function add_cart_item_data( $cart_item_data, $product_id ) {
        if ( isset( $cart_item_data['cpt_id'] ) || isset( $cart_item_data['iw_item_type'] ) ) {
            $cart_item_data['unique_key'] = md5( wp_json_encode( $cart_item_data ) . '|' . microtime( true ) . '|' . wp_rand() );
        }
        return $cart_item_data;
    }

    public function restore_cart_item( $item, $values, $cart_item_key ) {
        // Restore CPT item fields.
        if ( isset( $values['cpt_id'] ) ) {
            $item['cpt_id'] = $values['cpt_id'];
        }

        // Restore generic virtual-item fields (used by tickets too).
        if ( isset( $values['iw_item_type'] ) ) {
            $item['iw_item_type'] = $values['iw_item_type'];
        }
        if ( isset( $values['title'] ) ) {
            $item['title'] = $values['title'];
        }
        if ( isset( $values['price'] ) ) {
            $item['price'] = $values['price'];
        }

        // Restore known ticket meta if present.
        foreach ( [ 'tickets_for_id', 'tickets_day', 'tickets_time', 'tickets_visitors', 'tickets_total', 'tickets_channel' ] as $k ) {
            if ( isset( $values[ $k ] ) ) {
                $item[ $k ] = $values[ $k ];
            }
        }

        return $item;
    }


    public function set_cart_item_price_on_totals( $cart ){
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if ( ! is_object( $cart ) ) return;

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( CPT_As_Product::is_cpt_cart_item( $cart_item )
                && isset( $cart_item['price'], $cart_item['data'] )
                && is_object( $cart_item['data'] )
            ) {

                $final_price = (float) $cart_item['price'];
                $final_price = apply_filters( 'iw_virtual_cart_item_final_price', $final_price, $cart_item, $cart_item_key, $cart );

                $cart_item['data']->set_price( $final_price );
            }
        }
    }

    public function override_cart_item_name($name, $item) {
        return $item['title'] ?? $name;
    }

    public function override_cart_item_price($price_html, $item) {
        return isset($item['price']) ? wc_price($item['price']) : $price_html;
    }

    /**
     * Render additional meta in cart/checkout for ticket items.
     */
    public function render_cart_item_meta( $item_data, $cart_item ) {
        if ( empty( $cart_item['iw_item_type'] ) || $cart_item['iw_item_type'] !== 'tickets' ) {
            return $item_data;
        }

        if ( ! empty( $cart_item['tickets_day'] ) ) {
            $item_data[] = [
                'name'  => __( 'Ημερομηνία', 'iw-theme' ),
                'value' => esc_html( (string) $cart_item['tickets_day'] ),
            ];
        }

        if ( ! empty( $cart_item['tickets_time'] ) ) {
            $item_data[] = [
                'name'  => __( 'Ώρα', 'iw-theme' ),
                'value' => esc_html( (string) $cart_item['tickets_time'] ),
            ];
        }

        if ( isset( $cart_item['tickets_total'] ) ) {
            $item_data[] = [
                'name'  => __( 'Εισιτήρια', 'iw-theme' ),
                'value' => (int) $cart_item['tickets_total'],
            ];
        }

        return $item_data;
    }

    /**
     * Prevent Woo from linking the title to the virtual product page for our virtual items.
     */
    public function override_cart_item_permalink( $permalink, $cart_item, $cart_item_key ) {
        if ( ! empty( $cart_item['iw_item_type'] ) ) {
            return '';
        }
        return $permalink;
    }

    public function attach_virtual_item_meta_to_order_item( $item, $cart_item_key, $values, $order ): void
    {
        if ( ! is_array( $values ) ) {
            return;
        }

        // Only act on our virtual items.
        if ( empty( $values['iw_item_type'] ) && empty( $values['cpt_id'] ) ) {
            return;
        }

        // Generic virtual-item fields.
        if ( isset( $values['iw_item_type'] ) ) {
            $item->add_meta_data( 'iw_item_type', sanitize_key( (string) $values['iw_item_type'] ), true );
        }
        if ( isset( $values['title'] ) ) {
            $item->add_meta_data( 'iw_title', (string) $values['title'], true );
        }
        if ( isset( $values['price'] ) ) {
            $item->add_meta_data( 'iw_price', (float) $values['price'], true );
        }
        // Legacy CPT-as-product.
        if ( isset( $values['cpt_id'] ) ) {
            $item->add_meta_data( 'cpt_id', (int) $values['cpt_id'], true );
        }

        // Tickets fields (stored at root level in cart item data).
        if ( ! empty( $values['iw_item_type'] ) && $values['iw_item_type'] === 'tickets' ) {

            foreach ( [ 'tickets_for_id', 'tickets_day', 'tickets_time', 'tickets_visitors', 'tickets_total', 'tickets_channel' ] as $k ) {
                if ( array_key_exists( $k, $values ) ) {
                    $item->add_meta_data( $k, $values[ $k ], true );
                }
            }

            // Attach the order_item_id to existing slot holds so reservations/tickets
            // can be reliably linked to the specific order item.
            if ( class_exists( 'IW_Tickets_DB' ) ) {

                $token = self::get_existing_hold_token();

                if ( $token !== '' ) {

                    $post_id = ! empty( $values['tickets_for_id'] ) ? (int) $values['tickets_for_id'] : 0;
                    $date    = ! empty( $values['tickets_day'] ) ? (string) $values['tickets_day'] : '';
                    $time    = ! empty( $values['tickets_time'] ) ? (string) $values['tickets_time'] : '';

                    if ( $post_id > 0 && $date !== '' && $time !== '' ) {


                        $order_item_id = (int) $item->get_id();


                        if ($order_item_id === 0) {
                            $item->save();
                            $order_item_id = (int) $item->get_id();
                        }

                        IW_Tickets_DB::attach_order_item_to_holds(
                            $token,
                            $order_item_id,
                            (int) $post_id,
                            (string) $date,
                            (string) $time
                        );

                    }
                }
            }
        }
    }


    private static function get_hold_token(): string {
        if ( ! function_exists('WC') || ! WC()->session ) {
            return '';
        }

        $token = (string) WC()->session->get( 'iw_ticket_hold_token', '' );
        if ( $token === '' ) {
            $token = wp_generate_uuid4(); // ή bin2hex(random_bytes(16)) σε PHP 7+
            WC()->session->set( 'iw_ticket_hold_token', $token );
        }

        return $token;
    }

    private static function get_existing_hold_token(): string {
        if ( ! function_exists('WC') || ! WC()->session ) {
            return '';
        }
        return (string) WC()->session->get( 'iw_ticket_hold_token', '' );
    }

    private function get_ticket_slot_cutoff_timestamp( int $post_id, string $date, string $time, DateTimeZone $tz ): ?int {
        $time = str_replace( '-', ':', trim( $time ) );
        if ( preg_match( '/^\d{2}:\d{2}:\d{2}$/', $time ) ) {
            $time = substr( $time, 0, 5 );
        }

        try {
            $fallback_dt = new DateTimeImmutable( trim( $date . ' ' . $time ), $tz );
        } catch ( Exception $e ) {
            return null;
        }

        $cutoff_dt = $fallback_dt;

        if ( class_exists( 'IW_Tickets_Calendar_Service' ) && method_exists( 'IW_Tickets_Calendar_Service', 'get_slot_boundary_datetime' ) ) {
            try {
                $schedule = IW_Tickets_Calendar_Service::get_schedule( $post_id, false, $date, 1 );
                $timesByDate = $schedule['timesByDate'] ?? null;
                $timesByDate_arr = (array) $timesByDate;
                $slots = $timesByDate_arr[ $date ] ?? [];

                if ( is_array( $slots ) ) {
                    foreach ( $slots as $slot ) {
                        if ( is_object( $slot ) ) {
                            $slot = (array) $slot;
                        }
                        if ( ! is_array( $slot ) ) {
                            continue;
                        }

                        $slot_time = isset( $slot['time'] ) ? str_replace( '-', ':', (string) $slot['time'] ) : '';
                        if ( preg_match( '/^\d{2}:\d{2}:\d{2}$/', $slot_time ) ) {
                            $slot_time = substr( $slot_time, 0, 5 );
                        }

                        if ( $slot_time !== $time ) {
                            continue;
                        }

                        if ( ! empty( $slot['use_end_for_cutoff'] ) ) {
                            $slot_end_dt = IW_Tickets_Calendar_Service::get_slot_boundary_datetime( $date, $slot, $tz, 'end' );
                            if ( $slot_end_dt ) {
                                $cutoff_dt = $slot_end_dt;
                            }
                        }

                        break;
                    }
                }
            } catch ( Exception $e ) {
                // Fall back to the selected slot start.
            }
        }

        return (int) $cutoff_dt->getTimestamp();
    }

    public function maybe_create_ticket_hold( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        if ( empty($cart_item_data['iw_item_type']) || $cart_item_data['iw_item_type'] !== 'tickets' ) {
            return;
        }

        $channel = isset( $cart_item_data['tickets_channel'] ) ? (string) $cart_item_data['tickets_channel'] : 'online';
        if ( $channel === 'onsite' || $channel === 'cashier' ) {
            return;
        }

        if ( ! class_exists('IW_Tickets_DB') ) return;

        $token   = self::get_hold_token();
        $post_id = ! empty($cart_item_data['tickets_for_id']) ? (int) $cart_item_data['tickets_for_id'] : 0;
        $date    = ! empty($cart_item_data['tickets_day']) ? (string) $cart_item_data['tickets_day'] : '';
        $time    = ! empty($cart_item_data['tickets_time']) ? (string) $cart_item_data['tickets_time'] : '';
        $qty     = ! empty($cart_item_data['tickets_total']) ? (int) $cart_item_data['tickets_total'] : 0;

        if ( $token === '' || $post_id <= 0 || $date === '' || $time === '' ) return;

        // TTL comes from plugin option (defaults to 15) but can be overridden by filter.
        $default_ttl = ( class_exists( 'IW_Ticketing' ) && method_exists( 'IW_Ticketing', 'get_slot_hold_minutes' ) )
            ? IW_Ticketing::get_slot_hold_minutes()
            : 15;
        $ttl = (int) apply_filters( 'iw_tickets_hold_ttl_minutes', $default_ttl, $post_id );

        // Cap hold TTL so it never extends beyond the latest-booking cutoff for this slot.
        $latest_offset_minutes = $this->get_latest_booking_offset_minutes( (int) $post_id );
        if ( $latest_offset_minutes > 0 ) {
            try {
                $tz = wp_timezone();
                $slot_cutoff_ts = $this->get_ticket_slot_cutoff_timestamp( (int) $post_id, (string) $date, (string) $time, $tz );
                if ( ! $slot_cutoff_ts ) {
                    throw new Exception( 'Invalid ticket slot cutoff.' );
                }
                $now_ts = (int) current_datetime()->getTimestamp();

                $cutoff_ts = $slot_cutoff_ts - ( $latest_offset_minutes * 60 );
                $remaining_minutes = (int) floor( ( $cutoff_ts - $now_ts ) / 60 );

                // If already past cutoff, remove item immediately (should not be bookable).
                if ( $remaining_minutes <= 0 ) {
                    if ( function_exists( 'WC' ) && WC()->cart ) {
                        WC()->cart->remove_cart_item( $cart_item_key );
                    }
                    wc_add_notice( __( 'Έληξε το χρονικό όριο κράτησης για αυτό το slot. Παρακαλούμε επιλέξτε άλλη ημερομηνία/ώρα.', 'iw-theme' ), 'error' );
                    return;
                }

                if ( $ttl > 0 ) {
                    $ttl = min( $ttl, $remaining_minutes );
                } else {
                    $ttl = $remaining_minutes;
                }
            } catch ( Exception $e ) {
                // If parsing fails, keep original TTL.
            }
        }

        // Let DB layer normalize/clamp; pass null to use option directly.
        // order_item_id is not yet known at cart stage; it will be attached later during checkout
        IW_Tickets_DB::upsert_hold(
            $token,
            $post_id,
            $date,
            $time,
            $qty,
            $ttl > 0 ? $ttl : null,
            null
        );
    }

    public function maybe_release_ticket_hold( $cart_item_key, $cart ) {
        if ( ! $cart || empty($cart->removed_cart_contents[$cart_item_key]) ) return;
        $item = $cart->removed_cart_contents[$cart_item_key];

        if ( empty($item['iw_item_type']) || $item['iw_item_type'] !== 'tickets' ) return;
        if ( ! class_exists('IW_Tickets_DB') ) return;

        $channel = ! empty( $item['tickets_channel'] ) ? (string) $item['tickets_channel'] : 'online';
        if ( $channel === 'onsite' || $channel === 'cashier' ) return;

        $token   = self::get_existing_hold_token();
        $post_id = ! empty($item['tickets_for_id']) ? (int) $item['tickets_for_id'] : null;
        $date    = ! empty($item['tickets_day']) ? (string) $item['tickets_day'] : null;
        $time    = ! empty($item['tickets_time']) ? (string) $item['tickets_time'] : null;

        if ( $token === '' ) return;

        IW_Tickets_DB::release_unattached_holds( $token, $post_id, $date, $time );
    }

    public function release_all_ticket_holds() {

        if ( ! class_exists('IW_Tickets_DB') ) return;

        $token = self::get_existing_hold_token();
        if ( $token === '' ) return;

        IW_Tickets_DB::release_unattached_holds( $token );
    }

    public function remove_expired_ticket_items_from_cart(): void {
        if ( ! function_exists('WC') || ! WC()->cart || WC()->cart->is_empty() ) {
            return;
        }

        $now_ts = (int) current_time( 'timestamp' );
        $removed_any = false;

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( empty( $cart_item['iw_item_type'] ) || $cart_item['iw_item_type'] !== 'tickets' ) {
                continue;
            }
            $channel = ! empty( $cart_item['tickets_channel'] ) ? (string) $cart_item['tickets_channel'] : 'online';
            if ( $channel === 'onsite' || $channel === 'cashier' ) {
                continue;
            }

            // Use session token if it exists; do not create a new token during cleanup.
            $token = self::get_existing_hold_token();
            $hold  = $token !== '' ? IW_Tickets_DB::get_hold_info( $cart_item ) : null;
            if ( ! $hold || empty( $hold->expires_at ) ) {
                WC()->cart->remove_cart_item( $cart_item_key );
                $removed_any = true;
                continue;
            }

            $expires_ts = strtotime( (string) $hold->expires_at );
            if ( ! $expires_ts || $expires_ts <= $now_ts ) {
                WC()->cart->remove_cart_item( $cart_item_key );
                $removed_any = true;
                continue;
            }

            // Extra safety: if cart qty does not match hold qty, drop it to avoid inconsistent reservations.
            $cart_qty = ! empty( $cart_item['tickets_total'] ) ? (int) $cart_item['tickets_total'] : 0;
            if ( $cart_qty <= 0 || (int) $hold->qty !== $cart_qty ) {
                WC()->cart->remove_cart_item( $cart_item_key );
                $removed_any = true;
                continue;
            }
        }

        if ( $removed_any ) {
            wc_add_notice(
                __( 'Κάποια επιλεγμένα slots έληξαν και αφαιρέθηκαν από το καλάθι. Παρακαλώ επιλέξτε ξανά.', 'iw-theme' ),
                'error'
            );
        }
    }

    public function attach_hold_token_to_order( $order, $data ) {
        $token = self::get_hold_token();
        if ( $token !== '' ) {
            $order->update_meta_data( '_iw_ticket_hold_token', $token );
        }
    }

    public function consume_holds_on_payment_complete( $order_id ) {


        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $token = (string) $order->get_meta('_iw_ticket_hold_token');
        if ( $token === '' ) return;

        IW_Tickets_DB::consume_holds_to_booked( $token );
    }

    public function release_holds_on_order_cancelled( $order_id ) {
        if ( ! class_exists('IW_Tickets_DB') ) return;

        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $token = (string) $order->get_meta('_iw_ticket_hold_token');
        if ( $token === '' ) return;

        IW_Tickets_DB::release_holds( $token );
    }

    /**
     * Returns latest booking cutoff offset (in minutes) before slot start.
     * Best-effort reading from options; supports multiple option structures.
     * Can be overridden by filter.
     */
    private function get_latest_booking_offset_minutes( int $post_id ): int {
        $minutes = 0;

        // A) If you store booking window in a grouped option (e.g. iw_ticketing_booking_window[latest][days|hours|minutes]).
        if ( function_exists( 'get_field' ) ) {
            $window = get_field( 'iw_ticketing_booking_window', 'option' );
            if ( is_array( $window ) ) {
                $latest = $window['latest'] ?? null;
                if ( is_array( $latest ) ) {
                    $d = isset( $latest['days'] ) ? (int) $latest['days'] : 0;
                    $h = isset( $latest['hours'] ) ? (int) $latest['hours'] : 0;
                    $m = isset( $latest['minutes'] ) ? (int) $latest['minutes'] : 0;
                    $minutes = max( 0, ( $d * 24 * 60 ) + ( $h * 60 ) + $m );
                }
            }

            // B) Alternative: a single option group field (e.g. iw_ticketing_latest_booking[days|hours|minutes]).
            if ( $minutes <= 0 ) {
                $latest = get_field( 'iw_ticketing_latest_booking', 'option' );
                if ( is_array( $latest ) ) {
                    $d = isset( $latest['days'] ) ? (int) $latest['days'] : 0;
                    $h = isset( $latest['hours'] ) ? (int) $latest['hours'] : 0;
                    $m = isset( $latest['minutes'] ) ? (int) $latest['minutes'] : 0;
                    $minutes = max( 0, ( $d * 24 * 60 ) + ( $h * 60 ) + $m );
                }
            }
        }

        /**
         * Allow per-post overrides.
         * Return minutes before slot start.
         */
        $minutes = (int) apply_filters( 'iw_tickets_latest_booking_offset_minutes', $minutes, $post_id );

        return max( 0, $minutes );
    }

}


add_action( 'plugins_loaded', function(){
    // Only boot when WooCommerce is available
    if ( ! class_exists( 'WC_Product' ) ) { return;}

    class WC_Product_CPT_Virtual extends WC_Product {
        public function __construct( $product = 0 ) {
            parent::__construct( $product );
            $this->set_virtual( true );
            $this->set_downloadable( false );
        }
    }
    CPT_As_Product::init();
});
