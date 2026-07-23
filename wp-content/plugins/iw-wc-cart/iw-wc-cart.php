<?php
/**
 * Plugin Name:     IW WC Cart
 * Description:     AJAX cart, checkout, reservation, and shipping helpers for WooCommerce projects.
 * Author:          Andreas Hatzifotis
 * Text Domain:     iw-wc-cart
 * Version:         0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IW_WC_Cart {

    private  static $totalsCalculated;
    const DHL_EXPRESS_SETTINGS_KEY = 'woocommerce_dhlexpress_settings';
    const DHL_EXPORT_CUSTOMS_FEE_NAME = 'Έξοδα Διαδικασίας Εκτελωνισμού Εξαγωγής (Non-EU)';
    const DHL_EXPORT_CUSTOMS_FEE_DESCRIPTION = 'Δεν περιλαμβάνει φόρους ή δασμούς της χώρας παραλήπτη.';
    const DHL_PACKAGE_INSURANCE_FEE_NAME = 'Package Insurance (Outside Greece)';

    public static function init() {
        $actions = [ "add_coupon", "remove_coupon", "add_donation", "update_product_quantity", "update_shipping_method", "refresh_totals", "add_to_cart", 'clear', 'checkout', 'add_gift_subscription', 'add_gift_subscriptions', 'cover_stripe_fee', 'refresh' ];
        foreach ($actions as $action) {
            add_action('wp_ajax_iw_cart_' . $action, [__CLASS__, $action]);
            add_action('wp_ajax_nopriv_iw_cart_' . $action, [__CLASS__, $action]);
        }
        add_action('woocommerce_cart_calculate_fees', [__CLASS__, 'calculation_fees']);
        add_filter('woocommerce_settings_api_form_fields_dhlexpress', [__CLASS__, 'add_dhlexpress_fee_settings']);
        add_action('woocommerce_before_calculate_totals', [__CLASS__, 'calculation_totals'] );
        add_action( 'woocommerce_checkout_fields', [__CLASS__, 'checkout_fields'] );

        add_action('init', function () {
            register_post_status('wc-reserved', [ 'label' => __( 'Κράτηση', 'iw-theme'), 'public' => true, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop('Κράτηση (%s)', 'Κράτηση (%s)') ]);
        });

        add_filter('wc_order_statuses', function ($statuses) {
            $new = [];
            foreach ($statuses as $key => $label) {
                $new[$key] = $label;
                if ($key === 'wc-pending') {
                    $new['wc-reserved'] = __( 'Κράτηση', 'iw-theme');
                }
            }
            return $new;
        });
    }

    private static function render_template_part( $template, $args = [] ) {
        if ( class_exists( 'IW_Theme' ) && method_exists( 'IW_Theme', 'load_template_part' ) ) {
            return IW_Theme::load_template_part( $template, $args );
        }

        $html = '';
        if ( function_exists( 'locate_template' ) ) {
            $template_file = locate_template( $template . '.php' );
            if ( $template_file ) {
                ob_start();
                load_template( $template_file, false, $args );
                $html = ob_get_clean();
            }
        }

        return apply_filters( 'iw_wc_cart_template_part_html', $html, $template, $args );
    }

    private static function form_messages() {
        $messages = [
            'fieldRequired' => __( 'Το πεδίο είναι υποχρεωτικό.', 'iw-theme' ),
            'emailRequired' => __( 'Παρακαλούμε συμπληρώστε ένα έγκυρο email.', 'iw-theme' ),
        ];

        if ( class_exists( 'IW_Theme' ) && method_exists( 'IW_Theme', 'form_messages' ) ) {
            $messages = array_merge( $messages, (array) IW_Theme::form_messages() );
        }

        return apply_filters( 'iw_wc_cart_form_messages', $messages );
    }

    public static function refresh()
    {
        wp_send_json_success([ 'cart' => self::get_cart() ]);
    }


    public static function checkout() {
        try {
            ob_start();
            WC()->checkout()->process_checkout();
            ob_end_clean();
        } catch ( WC_Data_Exception $e ) {
            wp_send_json_error([ 'message' => $e->getMessage(), 'code' => $e->getErrorCode() ]);
        } catch ( Exception $e ) {
            $messages = wc_get_notices();
            wp_send_json_error([ 'message'  => $e->getMessage(), 'notices'  => $messages,]);
        }
        wp_send_json_error();
    }

    public static function add_coupon() {
        if ( empty( $coupon_code = sanitize_text_field($_POST['coupon'] ?? '') )) wp_send_json_error(['message' => __('Μη έγκυρος ή ανενεργός κωδικός.', 'iw-theme')]);
        if( WC()->cart->has_discount($coupon_code) ) wp_send_json_error([ 'cart' => self::get_cart(), 'errors' =>  [ 'coupon' => __( 'Το κουπόνι έχει προστεθεί ήδη', 'iw-theme') ] ], 400);
        $applied = WC()->cart->apply_coupon($coupon_code);
        if( $applied ){
            wp_send_json_success([ 'cart' => self::get_cart(), 'message' =>  __( 'Το κουπόνι προστέθηκε επιτυχώς', 'iw-theme') ]);
        } else {
            wp_send_json_error([ 'cart' => self::get_cart(), 'errors' =>  [ 'coupon' => __( 'Μη έγκυρος ή ανενεργός κωδικός κουπονιού', 'iw-theme') ] ], 400);
        }

    }

    public static function remove_coupon() {
        if ( empty( $coupon_code = sanitize_text_field($_POST['coupon'] ?? '') ) ) wp_send_json_error(['message' => __('Μη έγκυρος ή ανενεργός κωδικός.', 'iw-theme')]);
        if( ! WC()->cart->has_discount($coupon_code) ) wp_send_json_error(['message' => __('Το κουπόνι δεν υπάρχει στο καλάθι.', 'iw-theme')]);
        WC()->cart->remove_coupon($coupon_code);
        wp_send_json_success(['message' => __('Το κουπόνι αφαιρέθηκε επιτυχώς.', 'iw-theme'), 'cart' => self::get_cart()]);
    }

    public static function add_donation() {
        //check_ajax_referer('cart_nonce', 'security');
        if (isset($_POST['donation-amount'])) {
            $amount = floatval($_POST['donation-amount']);
            WC()->session->set('donation_amount', $amount);
            wp_send_json_success([ 'cart' => self::get_cart() ]);
        }
        wp_send_json_error();
    }

    public static function remove_donation() {
        WC()->session->set('donation_amount', 0 );
    }



    public static function update_product_quantity() {
        $product_id    = intval($_POST['product_id'] ?? 0);
        $variation_id  = intval($_POST['variation_id'] ?? 0);
        $quantity      = intval($_POST['quantity'] ?? 0);
        $cart_item_key = self::iw_get_cart_item_id( $product_id, $variation_id );
        if ( ! $cart_item_key || ! WC()->cart->get_cart_item($cart_item_key)) wp_send_json_error(['message' => 'Item not found in cart']);
        if ($quantity < 1) {
            WC()->cart->remove_cart_item($cart_item_key);
            $item_new_total = 0;
        } else {
            WC()->cart->set_quantity($cart_item_key, $quantity, true);
            $cart_item_key = self::iw_get_cart_item_id( $product_id, $variation_id );
            $item = WC()->cart->get_cart_item($cart_item_key);
            $line_total = $item['line_total'] + $item['line_tax'];
            $item_new_total = wc_price($line_total);
        }
        wp_send_json_success( [ 'cart' => self::get_cart(),  'item_new_total' => $item_new_total ]);
    }

    public static function update_shipping_method() {

        if (empty($method_id = sanitize_text_field($_POST['method_id'] ?? ''))) {
            wp_send_json_error(['message' => 'Δεν δόθηκε μέθοδος αποστολής.']);
        }

        WC()->cart->calculate_shipping();


        $packages = WC()->shipping()->get_packages();
        $available = [];


        if (!empty($packages)) {
            foreach ($packages[0]['rates'] as $rate_id => $rate) {
                $available[] = $rate_id;
            }
        }


        if (!in_array($method_id, $available, true)) {
            wp_send_json_error(['message' => 'Μη έγκυρη μέθοδος αποστολής.']);
        }

        WC()->session->set('chosen_shipping_methods', [$method_id]);

        wp_send_json_success([ 'message' => 'Η μέθοδος αποστολής ενημερώθηκε.', 'cart' => self::get_cart(), 'selected' => self::get_selected_shipping_method() ]);
    }

    public static function refresh_totals() {
        $customer = WC()->customer;
        $data = class_exists( 'IW_WC_Customizations' ) ? IW_WC_Customizations::get_normalized_invoice_data( $_POST ) : $_POST;



        if ( class_exists( 'IW_WC_Customizations' ) ) {
            IW_WC_Customizations::update_fields( $data );
        }

        $p = function($key, $default = '') use ( $data ) { return isset($data[$key]) ? wc_clean( wp_unslash( $data[$key] ) ) : $default; };
        $customer->set_billing_country( $p('billing_country' ) );
        $customer->set_billing_postcode( $p('billing_postcode' ) );
        $customer->set_billing_city( $p('billing_city' ) );
        $customer->set_billing_address_1( $p('billing_address_1' ) );
        $customer->set_billing_address_2( $p('billing_address_2' ) );
        $customer->set_billing_state( $p('billing_state' ) );


        $ship_diff = $p('ship_to_different_address', '') ? true : false;

        $customer->set_shipping_country( $ship_diff ? $p('shipping_country' ) : $customer->get_billing_country() );
        $customer->set_shipping_postcode( $ship_diff ? $p('shipping_postcode' ) : $customer->get_billing_postcode() );
        $customer->set_shipping_city( $ship_diff ? $p('shipping_city' ) : $customer->get_billing_city() );
        $customer->set_shipping_address_1( $ship_diff ? $p('shipping_address_1' ) : $customer->get_billing_address_1() );
        $customer->set_shipping_address_2( $ship_diff ? $p('shipping_address_2' ) : $customer->get_billing_address_2() );
        $customer->set_shipping_state( $ship_diff ? $p('shipping_state' ) : $customer->get_billing_state() );

        $customer->save();
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();

        wp_send_json_success( [ 'cart' => self::get_cart(), 'shipping_methods_html' => self::render_template_part( 'woocommerce/checkout/shipping-methods' ) ]);
    }

    public static function add_to_cart() {
        $product_id   = absint($_POST['product_id'] ?? 0);
        $variation_id = absint($_POST['variation_id'] ?? 0);
        $quantity     = max(1, intval($_POST['quantity'] ?? 1));
        $attributes = json_decode(stripslashes($_POST['attributes'] ?? ''), true) ?? [];
        if (!$product_id) {
            wp_send_json_error(['message' => 'Missing product_id']);
        }



        do_action( 'iw_add_to_cart_validation',  $product_id);


        if ( class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $product_id ) ) {
            if ( is_user_logged_in() ) {
                $user_id = get_current_user_id();
                if ( function_exists('wcs_user_has_subscription') && wcs_user_has_subscription( $user_id, '', array('active','trial') ) ) {
                    wp_send_json_error([ 'message' => __('Έχετε ήδη ενεργή συνδρομή. Μπορείτε να αγοράσετε μόνο συνδρομή δώρου.', 'iw-theme')], 400);
                }
            }

            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                $cart_product_id = $cart_item['product_id'];
                if ( ! empty( $cart_item['wcsg_gift_recipients_email'] ) ) {
                    continue;
                }
                if ( class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $cart_product_id ) ) {
                    WC()->cart->remove_cart_item( $cart_item_key );
                }
            }
        }



        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $attributes);

        $notices = wc_get_notices('error');
        wc_clear_notices();

        if ( ! $cart_item_key) {
            $message = ! empty( $notices ) ? end($notices)['notice'] : __('Δεν ήταν δυνατή η προσθήκη στο καλάθι.', 'iw-theme');
            wp_send_json_error(['message' => $message]);
        }
        $cart = self::get_cart();
        $cart[ 'new_item' ] = self::render_template_part('woocommerce/cart/cart-item', array('cart_item_key' => $cart_item_key, 'cart_item' => WC()->cart->get_cart_item($cart_item_key) ) );

        wp_send_json_success( [ 'message' => __( 'Το προϊόν προστέθηκε στο καλάθι', 'iw-theme' ), 'cart' => $cart  ]);
    }

    public static function add_gift_subscriptions()
    {
        if( empty( $_POST[ 'subscriptions' ] ) ){
            wp_send_json_error(['message' => __('Kάτι πήγε στραβά, παρακαλούμε κάντε refresh τη σελίδα', 'iw-theme')], 422);
        }

        $subscriptions = json_decode( wp_unslash( $_POST['subscriptions'] ?? '[]' ), true );

        $errors = false;
        $newItems = [];
        $newItemKeys = [];
        $errorCode = null;
        foreach ($subscriptions as $index => $subscription) {
            $return = self::add_gift_subscription_item( $subscription);
            if( isset( $return[ 'cart' ] ) ){
                $newItems [] = $return[ 'cart' ][ 'new_item' ];
                $newItemKeys [] = $return[ 'cart' ][ 'new_item_key' ];
            } else {

                if( $return[ 'errors' ] ){
                    $errorCode = 422;
                    $errors[ $index ] = $return[ 'errors' ];
                } else {
                    $errors[ $index ] = $return['message'];
                }

            }
        }
        if( $errors ){
            foreach ( $newItemKeys as $newItemKey ) {
                if ( WC()->cart->get_cart_item( $newItemKey ) ) {
                    WC()->cart->remove_cart_item( $newItemKey );
                }
            }
            // Return errors as a JSON array (not an object). JSON turns sparse numeric keys into objects,
            // so we fill missing indices with null to keep a stable array shape.
            $errors_out = array_fill( 0, count( $subscriptions ), null );
            foreach ( (array) $errors as $i => $err ) {
                $errors_out[ (int) $i ] = $err;
            }

            wp_send_json_error( [ 'errors' => $errors_out ], $errorCode );
        } else {
            $cart = self::get_cart();
            $cart[ 'new_items' ] = $newItems;
            wp_send_json_success( [ 'message' => __( 'Οι συνδρομές προστέθηκαν στο καλάθι', 'iw-theme' ) , 'cart' => $cart  ]);
        }


    }

    public static function add_gift_subscription(){
        $return = self::add_gift_subscription_item();
        if( isset( $return[ 'cart' ] ) ){
            wp_send_json_success( $return );
        } else {
            wp_send_json_error( $return, $return[ 'errors' ] ? 422 : null );
        }
    }
    public static function add_gift_subscription_item( $data = [] ){
        $errors = [];
        $form_messages = self::form_messages();
        $_data = empty( $data ) ? $_POST : (array) $data;


        $requiredFields = ["variation_id", "first_name", "last_name", "email"];
        foreach ($requiredFields as $key) {
            if (empty($_data[$key])) {
                $errors[$key] = $form_messages['fieldRequired'];
            }
        }


        if ( ! is_email($_data['email'])) {
            $errors['email'] = $form_messages['emailRequired'];
        } else if ($user_id = email_exists($_data['email'])) {
            $current_user_email = is_user_logged_in() ? wp_get_current_user()->user_email : '';
            if (strtolower(trim($_data['email'])) === strtolower(trim($current_user_email))) {
                $errors['email'] = __('Δεν μπορείτε να στείλετε δώρο στον εαυτό σας.', 'iw-theme');
            } else if ( function_exists('wcs_user_has_subscription') && wcs_user_has_subscription($user_id, '', array('active', 'trial'))) {
                $errors['email'] = __('Ο παραλήπτης έχει ήδη ενεργή ή δοκιμαστική συνδρομή.', 'iw-theme');
            }
        }


        if( empty( $errors['email'] ) ){
            // Check if cart already contains a gift subscription for this email
            $entered_email = strtolower(trim($_data['email']));
            foreach (WC()->cart->get_cart() as $cart_item) {
                if (!empty($cart_item['wcsg_gift_recipients_email'])) {
                    $cart_email = strtolower(trim($cart_item['wcsg_gift_recipients_email']));
                    if ($cart_email === $entered_email) {
                        $errors['email'] = __('Υπάρχει ήδη συνδρομή δώρου στο καλάθι για το email ', 'iw-theme');
                        break;
                    }
                }
            }
        }

        if ( ! empty($errors) ) {
            return ['errors' => $errors];
        }

        $variation_id = intval( $_data['variation_id'] ?? 0 );
        $variation    = wc_get_product( $variation_id );

        if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
            return ['message' => __('Δεν ήταν δυνατή η προσθήκη στο καλάθι.', 'iw-theme')];
        }

        $product_id = (int) $variation->get_parent_id();
        $product    = $product_id ? wc_get_product( $product_id ) : null;

        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            return ['message' => __('Δεν ήταν δυνατή η προσθήκη στο καλάθι.', 'iw-theme')];
        }

        if ( class_exists( 'WC_Subscriptions_Product' ) && ! WC_Subscriptions_Product::is_subscription( $product ) ) {
            return ['message' => __('Δεν ήταν δυνατή η προσθήκη στο καλάθι.', 'iw-theme')];
        }

        if ( ! $variation->is_purchasable() || ! $variation->is_in_stock() ) {
            return ['message' => __('Δεν ήταν δυνατή η προσθήκη στο καλάθι.', 'iw-theme')];
        }

        $cart_item_data = [
            'wcsg_gift_recipients_email'        => sanitize_email($_data['email'] ?? ''),
            'wcsg_gift_recipients_first_name'   => sanitize_text_field($_data['first_name'] ?? ''),
            'wcsg_gift_recipients_last_name'    => sanitize_text_field($_data['last_name'] ?? ''),
            'wcsg_gift_recipients_message'      => sanitize_textarea_field($_data['message'] ?? ''),
        ];
        do_action( 'iw_add_to_cart_validation',  $product_id);
        $cart_item_key = WC()->cart->add_to_cart( $product_id, 1, $variation_id, [ 'attribute_pa_period' => $variation->get_attribute( 'pa_period' ) ], $cart_item_data );



        $notices = wc_get_notices('error');
        wc_clear_notices();

        if ( ! $cart_item_key) {
            $message = ! empty( $notices ) ? end($notices)['notice'] : __('Δεν ήταν δυνατή η προσθήκη στο καλάθι.', 'iw-theme');
            return ['message' => $message];
        } else {
            $cart = self::get_cart();
            $cart[ 'new_item' ] = self::render_template_part('woocommerce/cart/cart-item', array('cart_item_key' => $cart_item_key, 'cart_item' => WC()->cart->get_cart_item($cart_item_key) ) );
            $cart[ 'new_item_key' ] = $cart_item_key;

            return [ 'message' => __( 'Το προϊόν προστέθηκε στο καλάθι', 'iw-theme' ), 'cart' => $cart  ];
        }

    }

    public static function can_be_reserved( ){
        return apply_filters( 'iw_cart_can_be_reserved', false );
    }


    public static function clear(){
        WC()->cart->empty_cart();
        wp_send_json_success( [ 'cart' => self::get_cart() ]);
    }

    public static function iw_get_cart_item_id( $product_id, $variation_id){
        $cart_item_key = false;
        foreach (WC()->cart->get_cart() as $key => $item) {
            if ((int) $item['product_id'] === $product_id && (int) $item['variation_id'] === $variation_id) {
                $cart_item_key = $key;
                break;
            }
        }
        return $cart_item_key;
    }




    public static function get_cart( $args = array() ) {
        do_action( 'woocommerce_before_cart_display' );
        add_action('woocommerce_before_calculate_totals', [__CLASS__, 'calculation_totals'] );
        WC()->cart->calculate_totals();
        

        $cart = WC()->cart;
        $data = array();
        $totalItems = WC()->cart->get_cart_contents_count();

        $data['itemsCount'] = $totalItems;
        $data['itemsCountText'] = $totalItems .  " " . ( $totalItems > 1 ? __('Είδη', 'iw-theme') : __('Είδος', 'iw-theme') );
        $data['subtotal'] = $cart->get_cart_subtotal();
        $data['subtotalWithFees'] = wc_price( WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() + WC()->cart->get_fee_total() );
        // Fees
        $fees = [];
        foreach ( $cart->get_fees() as $fee ) {
            $fees[] = [
                'name'  => $fee->name,
                'value' => wc_price( $fee->amount + $fee->tax ),
                'description' => self::get_fee_description( $fee->name ),
            ];
        }
        $data['fees'] = $fees;


        // Coupons
        $coupons = [];
        if ( wc_coupons_enabled() ) {
            foreach ( $cart->get_coupons() as $code => $coupon ) {
                $coupons[] = [ 'key' => $coupon->get_code(), 'value' => wc_price( $cart->get_coupon_discount_amount( $coupon->get_code(), $cart->display_cart_ex_tax ) )];
            }
        }
        $data['coupons'] = $coupons;

        $data[ 'shipping' ] = $cart->needs_shipping() ? IW_WC_Cart::get_selected_shipping_method() : false;
        $data[ 'needs_shipping' ] = $cart->needs_shipping();


        $items = [];
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product        = $cart_item['data'];
            $qty            = is_numeric( $cart_item['quantity'] ) ? (float) $cart_item['quantity'] : 0;
            $raw_regular_price  = $product->get_regular_price();
            $raw_discount_price = $product->get_price();
            $discount_price = is_numeric( $raw_discount_price ) ? (float) $raw_discount_price : 0;
            $regular_price  = is_numeric( $raw_regular_price ) ? (float) $raw_regular_price : $discount_price;
            $regular_total  = $regular_price * $qty;
            $discount_total = $discount_price * $qty;
            if ( $regular_price > $discount_price ) {
                $priceHTML =  '<span style="text-decoration: line-through;" class="mr-5">' . wc_price( $regular_total ) . '</span>';
                $priceHTML .= '<span class="text-shop2">' . wc_price( $discount_total ) . '</span>';
            } else {
                $priceHTML  =  wc_price( $discount_total );
            }
            $items[ $cart_item_key ] = [
                'priceHTML' => $priceHTML,
                'regular_total' => $regular_total,
                'discount_total' => $discount_total,
            ];
        }
        $data['items'] = $items;

        // Total
        $data['total'] = $cart->get_total();

        $new_item_key = $args['new_item'] ?? '';
        if( $new_item_key ){
            $new_cart_item = WC()->cart->get_cart_item( $new_item_key );
            if ( $new_cart_item ) {
                $data[ 'new_item' ] = self::render_template_part('woocommerce/cart/cart-item', array('cart_item_key' => $new_item_key, 'cart_item' => $new_cart_item ) );
            }
        }
        return  $data;
    }


    public static function get_selected_shipping_method(){
        $selectedMethodId = WC()->session->chosen_shipping_methods[0] ?? '';
        $selectedMethod = false;
        if( ! empty(  $packages = WC()->shipping()->get_packages() ) ) {
            $package = $packages[0];
            foreach ( (array) $package['rates'] as $method) {
                if ($method->id === $selectedMethodId) {
                    $selectedMethod = $method;
                }
            }
        }
        return $selectedMethod ? (object) [ 'cost' => wc_price($selectedMethod->get_cost() ), 'name' => $selectedMethod->label ] : false;
    }

    public static function add_dhlexpress_fee_settings( $form_fields ) {
        $form_fields['iw_export_customs_fee_title'] = [
            'title' => __('Export customs clearance fee', 'iw-theme'),
            'type' => 'title',
            'description' => __('Applies when DHL Express ships outside the EU.', 'iw-theme'),
        ];
        $form_fields['iw_export_customs_fee_enabled'] = [
            'type' => 'checkbox',
            'label' => __('Enable export customs fee', 'iw-theme'),
            'default' => 'yes',
        ];
        $form_fields['iw_export_customs_fee_label'] = [
            'title' => __('Fee label', 'iw-theme'),
            'type' => 'text',
            'default' => self::DHL_EXPORT_CUSTOMS_FEE_NAME,
        ];
        $form_fields['iw_export_customs_fee_amount'] = [
            'title' => __('Fee amount', 'iw-theme'),
            'type' => 'price',
            'description' => __('Amount in shop currency.', 'iw-theme'),
            'default' => '20',
            'desc_tip' => true,
        ];
        $form_fields['iw_export_customs_fee_description'] = [
            'title' => __('Fee note', 'iw-theme'),
            'type' => 'textarea',
            'default' => self::DHL_EXPORT_CUSTOMS_FEE_DESCRIPTION,
        ];
        $form_fields['iw_package_insurance_title'] = [
            'title' => __('Package insurance fee', 'iw-theme'),
            'type' => 'title',
            'description' => __('Applies when DHL Express ships outside Greece and cart subtotal is above the configured threshold.', 'iw-theme'),
        ];
        $form_fields['iw_package_insurance_enabled'] = [
            'type' => 'checkbox',
            'label' => __('Enable package insurance fee', 'iw-theme'),
            'default' => 'yes',
        ];
        $form_fields['iw_package_insurance_label'] = [
            'title' => __('Fee label', 'iw-theme'),
            'type' => 'text',
            'default' => self::DHL_PACKAGE_INSURANCE_FEE_NAME,
        ];
        $form_fields['iw_package_insurance_amount'] = [
            'title' => __('Fee amount', 'iw-theme'),
            'type' => 'price',
            'description' => __('Amount in shop currency.', 'iw-theme'),
            'default' => '10',
            'desc_tip' => true,
        ];
        $form_fields['iw_package_insurance_threshold'] = [
            'title' => __('Minimum cart subtotal', 'iw-theme'),
            'type' => 'price',
            'description' => __('The fee is added only when the displayed cart subtotal is greater than this amount.', 'iw-theme'),
            'default' => '100',
            'desc_tip' => true,
        ];
        return $form_fields;
    }

    public static function get_fee_description( $fee_name ) {
        $settings = self::get_dhlexpress_fee_settings();
        $customs_label = trim( (string) $settings['iw_export_customs_fee_label'] );

        if ( $customs_label && trim( (string) $fee_name ) === $customs_label ) {
            return trim( (string) $settings['iw_export_customs_fee_description'] );
        }

        return '';
    }

    private static function get_dhlexpress_fee_settings() {
        return wp_parse_args( get_option( self::DHL_EXPRESS_SETTINGS_KEY, [] ), [
            'iw_export_customs_fee_enabled' => 'yes',
            'iw_export_customs_fee_label' => self::DHL_EXPORT_CUSTOMS_FEE_NAME,
            'iw_export_customs_fee_amount' => '20',
            'iw_export_customs_fee_description' => self::DHL_EXPORT_CUSTOMS_FEE_DESCRIPTION,
            'iw_package_insurance_enabled' => 'yes',
            'iw_package_insurance_label' => self::DHL_PACKAGE_INSURANCE_FEE_NAME,
            'iw_package_insurance_amount' => '10',
            'iw_package_insurance_threshold' => '100',
        ] );
    }

    private static function get_dhlexpress_fee_amount( $settings, $key ) {
        $amount = isset( $settings[ $key ] ) ? wc_format_decimal( $settings[ $key ] ) : 0;
        return max( 0, (float) $amount );
    }

    private static function is_dhlexpress_selected() {
        if ( ! WC()->session ) return false;

        $chosen_methods = (array) WC()->session->get( 'chosen_shipping_methods', [] );
        foreach ( $chosen_methods as $method_id ) {
            if ( strpos( (string) $method_id, 'dhlexpress' ) === 0 ) {
                return true;
            }
        }

        return false;
    }

    private static function get_shipping_country() {
        if ( ! WC()->customer ) return '';

        $country = WC()->customer->get_shipping_country();
        if ( ! $country ) {
            $country = WC()->customer->get_billing_country();
        }

        return strtoupper( (string) $country );
    }

    private static function is_eu_country( $country ) {
        if ( ! WC()->countries || ! method_exists( WC()->countries, 'get_european_union_countries' ) ) {
            return in_array( $country, [ 'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK' ], true );
        }

        return in_array( $country, WC()->countries->get_european_union_countries(), true );
    }

    private static function add_dhlexpress_fees( $cart ) {
        if ( ! $cart || ! self::is_dhlexpress_selected() ) return;

        $country = self::get_shipping_country();
        if ( ! $country ) return;

        $settings = self::get_dhlexpress_fee_settings();
        $outside_greece = $country !== 'GR';

        if ( $outside_greece && $settings['iw_package_insurance_enabled'] === 'yes' ) {
            $threshold = self::get_dhlexpress_fee_amount( $settings, 'iw_package_insurance_threshold' );
            $amount = self::get_dhlexpress_fee_amount( $settings, 'iw_package_insurance_amount' );

            if ( $amount > 0 && (float) $cart->get_displayed_subtotal() > $threshold ) {
                $cart->add_fee( $settings['iw_package_insurance_label'], $amount, false );
            }
        }

        if ( $outside_greece && ! self::is_eu_country( $country ) && $settings['iw_export_customs_fee_enabled'] === 'yes' ) {
            $amount = self::get_dhlexpress_fee_amount( $settings, 'iw_export_customs_fee_amount' );
            if ( $amount > 0 ) {
                $cart->add_fee( $settings['iw_export_customs_fee_label'], $amount, false );
            }
        }
    }


    public static function calculation_fees($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;
        $amount = WC()->session->get('donation_amount');
        if ($amount && $amount > 0) {
            $cart->add_fee(__('Χορηγία', 'iw-theme'), $amount, false);
        }

        if (WC()->session->get('cover_stripe_fee')) {
            $cart->add_fee(__('Κάλυψη εξόδων Συναλλαγής', 'iw-theme'), round(self::get_stripe_fee(), 2), false);
        }

        self::add_dhlexpress_fees( $cart );

    }

    public static function get_stripe_fee_text( ){
        return '3% + ' . wc_price( 0.3 );
    }

    public static function get_stripe_fee( $get_total = true )
    {
        $cart = WC()->cart;
        $percent = 0.03;
        $fixed   = 0.3;

        // Total including taxes (cart items + shipping + fees)
        $total = (float) $cart->get_cart_contents_total()
            + (float) $cart->get_cart_contents_tax()
            + (float) $cart->get_shipping_total()
            + (float) $cart->get_shipping_tax()
            + (float) $cart->get_fee_total()
            + (float) $cart->get_fee_tax();
        if ($total <= 0) {
            return 0;
        }


        return (($total + $fixed) / (1 - $percent)) - $total;
    }

    public static function cover_stripe_fee()
    {
        $val = isset($_POST['cover_stripe_fee']) && $_POST['cover_stripe_fee'] === '1';
        WC()->session->set('cover_stripe_fee', $val);
        wp_send_json_success([ 'cart' => self::get_cart() ]);
    }

    public static function calculation_totals($cart){



        if (is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if( self::$totalsCalculated ) return;
        self::$totalsCalculated = true;



        $discount_percent = self::get_active_membership_discount_percent($cart);

        if ($discount_percent > 0 ) {
            $multiplier = max(0, (100 - $discount_percent) / 100);
            foreach ($cart->get_cart() as $cart_item) {
                if ( class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $cart_item['data'] ) ) {
                    continue;
                }
                if ( ! empty( $cart_item['iw_item_type'] ) && $cart_item['iw_item_type'] === 'donation' ) {
                    continue;
                }
                $price = $cart_item['data']->get_price();
                if ( ! is_numeric( $price ) ) {
                    continue;
                }
                $cart_item['data']->set_price( (float) $price * $multiplier );
            }
        }


    }

    public static function format_discount_percent($percent) {
        $percent = (float) $percent;
        return floor($percent) === $percent ? (string) (int) $percent : rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.');
    }

    public static function get_active_membership_discount_percent($cart = null) {
        $discount_percent = self::user_has_subscription() ? 10.0 : 0.0;

        if (!$discount_percent && $cart) {
            foreach ($cart->get_cart() as $cart_item) {
                $product_id = $cart_item['product_id'];
                if (empty($cart_item['wcsg_gift_recipients_email']) && function_exists('is_product_subscription') && is_product_subscription($product_id)) {
                    $discount_percent = 10.0;
                    break;
                }
            }
        }

        return max(0, min(100, (float) apply_filters('iw_active_membership_discount_percent', $discount_percent, get_current_user_id(), $cart)));
    }

    public static function user_has_subscription() {
        $user_id = get_current_user_id();
        return $user_id && function_exists('wcs_user_has_subscription') && wcs_user_has_subscription($user_id, '', array('active', 'trial'));
    }

    public static function checkout_fields($fields) {
        $fields['billing']['billing_phone']['required'] = true;
        return $fields;
    }
}

IW_WC_Cart::init();



add_filter( 'woocommerce_add_cart_item_data', function ( $cart_item_data, $product_id ) {
    $discount_percent = IW_WC_Cart::get_active_membership_discount_percent();
    if ( $discount_percent > 0 ) {
        if ( function_exists('is_product_subscription') && is_product_subscription( $product_id ) ) {
            return $cart_item_data;
        }
        if ( class_exists( 'IW_Donations' ) && IW_Donations::is_donation_product( $product_id ) ) {
            return $cart_item_data;
        }
        $cart_item_data['active_membership_discount'] = [
            'label' => 'active_membership_discount',
            'value' => sprintf(__('Έκπτωση %s%% ενεργής συνδρομής', 'iw-theme'), IW_WC_Cart::format_discount_percent($discount_percent)),
            'percent' => $discount_percent,
        ];
    }
    return $cart_item_data;
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $cart_item_key, $values, $order ) {
    if ( class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $item->get_product() ) ) {
        return;
    }
    if ( ! empty( $values['iw_item_type'] ) && $values['iw_item_type'] === 'tickets' ) {
        return;
    }
    if ( ! empty( $values['iw_item_type'] ) && $values['iw_item_type'] === 'donation' ) {
        return;
    }
    if ( ! empty( $values['active_membership_discount'] ) ) {
        $item->add_meta_data( 'membership_discount_label', $values['active_membership_discount']['value'], true);
        $discount_percent = isset($values['active_membership_discount']['percent']) ? (float) $values['active_membership_discount']['percent'] : 10.0;
        $discount_multiplier = max(0.01, (100 - $discount_percent) / 100);

        // Snapshot τιμών τη στιγμή της αγοράς.
        $qty = (int) $item->get_quantity();
        $paid_total = $item->get_total() + $item->get_total_tax();
        $paid_per_item = $qty > 0 ? $paid_total / $qty : 0;

        // Τιμή ΠΡΙΝ την έκπτωση (reverse-calculated).
        $original_price = round( $paid_per_item / $discount_multiplier, wc_get_price_decimals() );

        $item->add_meta_data( '_original_unit_price', $original_price, true );
        $item->add_meta_data( '_original_line_total', $original_price * $qty, true );
    }

}, 10, 4 );
