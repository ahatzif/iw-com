<?php

if (!defined('ABSPATH')) {
    exit;
}
add_action('plugins_loaded', function () {
    class WC_Gateway_Pay_At_Museum extends WC_Payment_Gateway {

        public function __construct() {

            $this->id = 'pay_at_museum';

            $this->method_title = __('Πληρωμή στο μουσείο', 'iw-theme');
            $this->method_description = __('Κράτηση εισιτηρίων και πληρωμή στο μουσείο.', 'iw-theme');

            $this->has_fields = false;
            $this->supports = ['products'];


            $this->init_form_fields();
            $this->init_settings();

            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title', __( 'Κράτηση εισιτηρίων και πληρωμή στο μουσείο', 'iw-theme' ));
            $this->description = $this->get_option('description', __( 'Εάν θελήσετε να αλλάξετε την αρχική σας κράτηση, μπορείτε από την ενότητα κρατήσεις να ακυρώστε την κράτηση σας και να προχωρήσετε στην πραγματοποίηση νέας.', 'iw-theme') );

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ]);
            add_filter( 'iw_pay_at_museum_ticket_post_types', [ $this, 'get_enabled_ticket_post_types' ] );
            add_filter( 'iw_learning_program_reservation_days_before', [ $this, 'get_reservation_days_before' ] );
            add_filter( 'iw_learning_program_confirmation_start_days_before', [ $this, 'get_confirmation_start_days_before' ] );
            add_filter( 'iw_learning_program_confirmation_days_before', [ $this, 'get_confirmation_days_before' ] );
            add_filter( 'iw_learning_program_reservations_require_schools_only', [ $this, 'requires_schools_only' ] );
        }

        public function is_available() {
            if ( ! parent::is_available() ) return false;

            return IW_WC_Tickets_Cart_Manager::cart_can_be_reserved();
        }



        public function process_payment($order_id) {

            $order = wc_get_order($order_id);

            if ( ! IW_WC_Tickets_Cart_Manager::cart_can_be_reserved() ) {
                throw new Exception(__('Reservation is no longer available.', 'iw-theme'));
            }

            // convert cart holds → reservation using stored hold token
            $token = $order->get_meta('_iw_ticket_hold_token');

            if ( $token && class_exists('IW_Tickets_DB') ) {
                IW_Tickets_DB::convert_holds_to_reservation($token);

                // clear session token so later cart hooks cannot release it
                if ( function_exists('WC') && WC()->session ) {
                    WC()->session->set('iw_ticket_hold_token', '');
                }
            }

            // set order status
            $order->update_status('wc-reserved', 'Reservation without online payment.', true);

            if ( class_exists( 'IW_Ticketing' ) && method_exists( 'IW_Ticketing', 'schedule_reservation_confirmation_emails' ) ) {
                IW_Ticketing::schedule_reservation_confirmation_emails( $order );
            }

            WC()->cart->empty_cart();

            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            ];
        }

        public function init_form_fields() {

            $this->form_fields = [

                'enabled' => [
                    'title'   => 'Enable / Disable',
                    'type'    => 'checkbox',
                    'label'   => 'Enable Pay at Museum',
                    'default' => 'yes'
                ],

                'title' => [
                    'title'   => 'Title',
                    'type'    => 'text',
                    'default' => __( 'Κράτηση εισιτηρίων και πληρωμή στο μουσείο', 'iw-theme' )
                ],

                'description' => [
                    'title'   => 'Description',
                    'type'    => 'textarea',
                    'default' => __( 'Εάν θελήσετε να αλλάξετε την αρχική σας κράτηση, μπορείτε από την ενότητα κρατήσεις να ακυρώστε την κράτηση σας και να προχωρήσετε στην πραγματοποίηση νέας.', 'iw-theme')
                ],

                'ticket_post_types' => [
                    'title'          => __( 'Eligible ticket types', 'iw-theme' ),
                    'type'           => 'multiselect',
                    'class'          => 'wc-enhanced-select',
                    'css'            => 'min-width: 350px;',
                    'description'    => __( 'Choose which ticketable content types can use this reservation payment method. The cart must contain only tickets from the selected types.', 'iw-theme' ),
                    'default'        => [ 'learning-program' ],
                    'options'        => $this->get_ticketable_post_type_options(),
                    'select_buttons' => true,
                ],

                'reservation_days_before' => [
                    'title'             => __( 'Reservation cutoff', 'iw-theme' ),
                    'type'              => 'number',
                    'description'       => __( 'Minimum number of days before the selected slot that the reservation payment method remains available.', 'iw-theme' ),
                    'default'           => 4,
                    'desc_tip'          => true,
                    'custom_attributes' => [
                        'min'  => 0,
                        'step' => 1,
                    ],
                ],

                'confirmation_start_days_before' => [
                    'title'             => __( 'Confirmation opens', 'iw-theme' ),
                    'type'              => 'number',
                    'description'       => __( 'How many days before the selected slot the teacher can start confirming the reservation from My Account.', 'iw-theme' ),
                    'default'           => 4,
                    'desc_tip'          => true,
                    'custom_attributes' => [
                        'min'  => 0,
                        'step' => 1,
                    ],
                ],

                'confirmation_days_before' => [
                    'title'             => __( 'Confirmation deadline', 'iw-theme' ),
                    'type'              => 'number',
                    'description'       => __( 'How many days before the selected slot the confirmation window closes.', 'iw-theme' ),
                    'default'           => 2,
                    'desc_tip'          => true,
                    'custom_attributes' => [
                        'min'  => 0,
                        'step' => 1,
                    ],
                ],

                'require_schools_only' => [
                    'title'       => __( 'Require school-only learning programs', 'iw-theme' ),
                    'type'        => 'checkbox',
                    'label'       => __( 'For learning programs, show this payment method only when they are marked as school-only.', 'iw-theme' ),
                    'default'     => 'yes',
                    'description' => __( 'This restriction applies only to learning-program tickets included in the selected ticket types.', 'iw-theme' ),
                ],

            ];

        }

        private function get_ticketable_post_type_options(): array {
            $post_types = class_exists( 'IW_Ticketing' ) ? IW_Ticketing::get_supported_post_types() : [];
            $options = [];

            foreach ( (array) $post_types as $post_type ) {
                $post_type = sanitize_key( (string) $post_type );
                if ( $post_type === '' ) {
                    continue;
                }

                $post_type_object = get_post_type_object( $post_type );
                $label = $post_type_object && ! empty( $post_type_object->labels->singular_name )
                    ? (string) $post_type_object->labels->singular_name
                    : $post_type;

                if ( $label === $post_type || sanitize_title( $label ) === $post_type ) {
                    $label = ucwords( str_replace( [ '-', '_' ], ' ', $post_type ) );
                }

                $options[ $post_type ] = $label;
            }

            if ( empty( $options ) ) {
                $options['learning-program'] = __( 'Learning program', 'iw-theme' );
            }

            return $options;
        }

        public function get_enabled_ticket_post_types( $default = [ 'learning-program' ] ) {
            $selected = (array) $this->get_option( 'ticket_post_types', $default );
            $allowed = array_keys( $this->get_ticketable_post_type_options() );
            $selected = array_values( array_intersect( array_map( 'sanitize_key', $selected ), $allowed ) );

            return ! empty( $selected ) ? $selected : [ 'learning-program' ];
        }

        public function get_reservation_days_before( $default = 4 ) {
            $raw = $this->get_option( 'reservation_days_before', $default );
            return is_numeric( $raw ) ? max( 0, (int) $raw ) : max( 0, (int) $default );
        }

        public function get_confirmation_start_days_before( $default = 4 ) {
            $raw = $this->get_option( 'confirmation_start_days_before', $default );
            return is_numeric( $raw ) ? max( 0, (int) $raw ) : max( 0, (int) $default );
        }

        public function get_confirmation_days_before( $default = 2 ) {
            $raw = $this->get_option( 'confirmation_days_before', $default );
            return is_numeric( $raw ) ? max( 0, (int) $raw ) : max( 0, (int) $default );
        }

        public function requires_schools_only( $default = true ) {
            $raw = $this->get_option( 'require_schools_only', $default ? 'yes' : 'no' );
            return $raw === 'yes';
        }

        public function validate_fields() {
            if ( ! IW_WC_Tickets_Cart_Manager::cart_can_be_reserved() ) {
                wc_add_notice( __('Η κράτηση δεν είναι πλέον διαθέσιμη για αυτό το πρόγραμμα.', 'iw-theme'), 'error' );
                return false;
            }

            return true;
        }
    }
    add_filter('woocommerce_payment_gateways', function ($methods) {
        $methods[] = 'WC_Gateway_Pay_At_Museum';
        return $methods;
    });
});
