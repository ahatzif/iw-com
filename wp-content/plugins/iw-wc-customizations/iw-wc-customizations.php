<?php
/**
 * Plugin Name:     IW WC Customizations
 * Description:     Checkout, invoice, VAT, and account customizations for WooCommerce projects.
 * Author:          Andreas Hatzifotis
 * Text Domain:     iw-wc-customizations
 * Version:         0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IW_WC_Customizations {
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

        return apply_filters( 'iw_wc_customizations_template_part_html', $html, $template, $args );
    }

    public static function get_custom_fields( ) {
        return [
            'billing_receipt_type' => [
                'type'     => 'radio',
                'label'    => __( 'Είδος Παραστατικού', 'iw-theme' ),
                'required' => true,
                'priority' => 1,
                'options'  => [ 'receipt' => __( 'Απόδειξη', 'iw-theme' ), 'invoice' => __( 'Τιμολόγιο', 'iw-theme' ) ],
                'clear'    => true,
            ],
            'billing_tax_number' => [
                'type'        => 'text',
                'label'       => __('Α.Φ.Μ.', 'iw-theme'),
                'required'    => false,
                'priority'    => 2,
            ],
            'billing_company_name' => [
                'type'        => 'text',
                'label'       => __('Επωνυμία Επιχείρησης', 'iw-theme'),
                'required'    => false,
                'priority'    => 2.1,
            ],
            'billing_company_profession' => [
                'type'        => 'text',
                'label'       => __('Δραστηριότητα Επιχείρησης', 'iw-theme'),
                'required'    => false,
                'priority'    => 2.2,
            ],
            'billing_tax_office' => [
                'type'        => 'text',
                'label'       => __('Δ.Ο.Υ.', 'iw-theme'),
                'required'    => false,
                'priority'    => 2.3,
            ],
        ];
    }

    public static function get_posted_value( $data, $key, $default = '' ) {
        if ( isset( $data[ $key ] ) ) {
            return wc_clean( wp_unslash( $data[ $key ] ) );
        }

        if ( isset( $data['user_fields'] ) && is_array( $data['user_fields'] ) && isset( $data['user_fields'][ $key ] ) ) {
            return wc_clean( wp_unslash( $data['user_fields'][ $key ] ) );
        }

        return $default;
    }

    public static function get_receipt_type_from_data( $data ) {
        $receipt_type = self::get_posted_value( $data, 'billing_receipt_type' );

        if ( ! $receipt_type && function_exists( 'WC' ) && WC()->session ) {
            $receipt_type = WC()->session->get( 'billing_receipt_type' );
        }

        return $receipt_type ?: 'receipt';
    }

    public static function get_billing_country_from_data( $data ) {
        $country = self::get_posted_value( $data, 'billing_country' );

        if ( ! $country && function_exists( 'WC' ) && WC()->session ) {
            $country = WC()->session->get( 'billing_country' );
        }

        if ( ! $country && function_exists( 'WC' ) && WC()->customer ) {
            $country = WC()->customer->get_billing_country();
        }

        if ( ! $country && function_exists( 'WC' ) && WC()->countries ) {
            $country = WC()->countries->get_base_country();
        }

        return strtoupper( $country ?: 'GR' );
    }

    public static function is_greek_invoice( $data ) {
        return self::get_receipt_type_from_data( $data ) === 'invoice' && self::get_billing_country_from_data( $data ) === 'GR';
    }

    public static function is_eu_vat_country( $country ) {
        $eu_vat_countries = [
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU',
            'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'XI',
        ];

        return in_array( strtoupper( $country ), $eu_vat_countries, true );
    }

    public static function get_verified_aade_data( $tax_number = '' ) {
        if ( ! class_exists( 'IW_Taxisnet_Integration' ) ) {
            return [];
        }

        $data = IW_Taxisnet_Integration::get_saved_company_info();
        if ( empty( $data ) || ( $data['verified_source'] ?? '' ) !== 'aade' ) {
            return [];
        }

        $posted_tax_number = preg_replace( '/\D+/', '', $tax_number );
        $saved_tax_number  = preg_replace( '/\D+/', '', $data['tax_number'] ?? '' );

        if ( empty( $posted_tax_number ) || $posted_tax_number !== $saved_tax_number ) {
            return [];
        }

        return $data;
    }

    public static function get_saved_aade_company_info( $tax_number = '' ) {
        if ( ! class_exists( 'IW_Taxisnet_Integration' ) ) {
            return [];
        }

        $data = IW_Taxisnet_Integration::get_saved_company_info();
        if ( empty( $data ) || ( $data['verified_source'] ?? '' ) !== 'aade' ) {
            return [];
        }

        if ( ! empty( $tax_number ) ) {
            $posted_tax_number = preg_replace( '/\D+/', '', $tax_number );
            $saved_tax_number  = preg_replace( '/\D+/', '', $data['tax_number'] ?? '' );

            if ( $posted_tax_number !== $saved_tax_number ) {
                return [];
            }
        }

        return $data;
    }

    public static function get_aade_company_preview_args( $data ) {
        if ( empty( $data ) || ! is_array( $data ) ) {
            return [];
        }

        $postcode = $data['postcode'] ?? ( $data['post_code'] ?? '' );
        $lines = array_filter( [
            $data['company_profession'] ?? '',
            trim( ( $data['tax_number'] ?? '' ) . ( ! empty( $data['tax_office'] ) ? ' | ' . __( 'ΔΟΥ', 'iw-theme' ) . ' ' . $data['tax_office'] : '' ) ),
            implode( ', ', array_filter( [ $data['address_1'] ?? '', $postcode, $data['city'] ?? '' ] ) ),
        ] );

        return [
            'company_name' => $data['company_name'] ?? '',
            'company_info' => ! empty( $data['company_info'] ) ? $data['company_info'] : implode( '<br/>', $lines ),
        ];
    }

    public static function get_verified_vies_data( $country = '', $tax_number = '' ) {
        if ( ! class_exists( 'IW_Taxisnet_Integration' ) ) {
            return [];
        }

        $data = IW_Taxisnet_Integration::get_saved_vies_company_info();
        if ( empty( $data ) || ( $data['verified_source'] ?? '' ) !== 'vies' ) {
            return [];
        }

        $posted_country = strtoupper( $country ?: '' );
        $saved_country = strtoupper( $data['country'] ?? '' );
        if ( empty( $posted_country ) || empty( $saved_country ) || $posted_country !== $saved_country ) {
            return [];
        }

        $posted_tax_number = IW_Taxisnet_Integration::normalize_vat_number( $posted_country, $tax_number );
        $saved_tax_number = IW_Taxisnet_Integration::normalize_vat_number( $saved_country, $data['tax_number'] ?? ( $data['vat_number'] ?? '' ) );

        if ( empty( $posted_tax_number ) || $posted_tax_number !== $saved_tax_number ) {
            return [];
        }

        return $data;
    }

    public static function get_saved_vies_company_info( $country = '', $tax_number = '' ) {
        if ( ! class_exists( 'IW_Taxisnet_Integration' ) ) {
            return [];
        }

        $data = IW_Taxisnet_Integration::get_saved_vies_company_info();
        if ( empty( $data ) || ( $data['verified_source'] ?? '' ) !== 'vies' ) {
            return [];
        }

        if ( ! empty( $country ) && strtoupper( $data['country'] ?? '' ) !== strtoupper( $country ) ) {
            return [];
        }

        if ( ! empty( $tax_number ) ) {
            $posted_tax_number = IW_Taxisnet_Integration::normalize_vat_number( $country ?: ( $data['country'] ?? '' ), $tax_number );
            $saved_tax_number = IW_Taxisnet_Integration::normalize_vat_number( $data['country'] ?? '', $data['tax_number'] ?? ( $data['vat_number'] ?? '' ) );

            if ( $posted_tax_number !== $saved_tax_number ) {
                return [];
            }
        }

        return $data;
    }

    public static function get_company_preview_args( $data ) {
        return self::get_aade_company_preview_args( $data );
    }

    public static function get_normalized_invoice_data( $data ) {
        if ( self::get_receipt_type_from_data( $data ) !== 'invoice' || ! class_exists( 'IW_Taxisnet_Integration' ) ) {
            return $data;
        }

        $country = self::get_billing_country_from_data( $data );
        $tax_number = self::get_posted_value( $data, 'billing_tax_number' );

        if ( $country === 'GR' ) {
            $aade_data = self::get_verified_aade_data( $tax_number );
            if ( empty( $aade_data ) ) {
                return $data;
            }

            foreach ( IW_Taxisnet_Integration::get_billing_fields_from_company_info( $aade_data ) as $field_key => $value ) {
                $data[ $field_key ] = $value;
            }

            return $data;
        }

        if ( self::is_eu_vat_country( $country ) ) {
            $vies_data = self::get_verified_vies_data( $country, $tax_number );
            if ( empty( $vies_data ) ) {
                return $data;
            }

            foreach ( IW_Taxisnet_Integration::get_billing_fields_from_vies_info( $vies_data ) as $field_key => $value ) {
                if ( $value !== '' ) {
                    $data[ $field_key ] = $value;
                }
            }
        }

        return $data;
    }

    public static function sync_post_with_normalized_invoice_data() {
        $_POST = self::get_normalized_invoice_data( $_POST );
    }

    public static function get_required_invoice_fields( $country ) {
        if ( strtoupper( $country ) === 'GR' ) {
            return [
                'billing_tax_number',
                'billing_company_name',
                'billing_company_profession',
                'billing_tax_office',
                'billing_address_1',
                'billing_postcode',
                'billing_city',
                'billing_country',
            ];
        }

        return [
            'billing_tax_number',
            'billing_company_name',
            'billing_address_1',
            'billing_postcode',
            'billing_city',
            'billing_country',
        ];
    }

    public static function update_fields( $data ) {
        $customer = WC()->customer;
        $changed = false;
        $data = self::get_normalized_invoice_data( $data );

        foreach ( self::get_custom_fields() as $field_key => $field ) { if( isset( $data[ $field_key ] ) ) {
            $value = wc_clean( wp_unslash( $data[ $field_key ]) );
            WC()->session->set( $field_key, $value );
            $customer->update_meta_data( $field_key, $value );
            $changed = true;
            if ( is_user_logged_in() ) {
                update_user_meta( get_current_user_id(), $field_key, $value );
            }
        }}

        if ( $changed ) {
            $customer->save();
        }
    }


    public function __construct() {


        add_filter( 'woocommerce_checkout_get_value', function( $value, $field_name ){
            return ($field_name === 'billing_receipt_type' && empty( $value ) ) ? 'receipt' : $value;
        }, 10, 2 );


        add_filter('woocommerce_billing_fields', function ($fields) {
            foreach ( self::get_custom_fields() as $key => $custom_field) {
                $fields[ $key ] = $custom_field;
            }
            foreach ( ['billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone', 'billing_address_1', 'billing_postcode', 'billing_city', 'billing_state', 'billing_country' ] as $index => $field_key) {
                if (isset($fields[$field_key])) {
                    $fields[$field_key]['priority'] =  5 * ( $index + 1 );
                }
            }
            return $fields;
        });


        add_filter('woocommerce_shipping_fields', function ($fields) {
            foreach ( ['shipping_first_name', 'shipping_last_name', 'shipping_address_1', 'shipping_postcode', 'shipping_city', 'shipping_state', 'shipping_country'  ] as $index => $field_key) {
                if (isset($fields[$field_key])) {
                    $fields[$field_key]['priority'] =  5 * ( $index + 1 );
                }
            }
            return $fields;
        });

        add_action( 'woocommerce_checkout_process', function () {
            IW_WC_Customizations::sync_post_with_normalized_invoice_data();

            $receipt_type = IW_WC_Customizations::get_receipt_type_from_data( $_POST );
            if ($receipt_type !== 'invoice') {
                return;
            }

            $country = IW_WC_Customizations::get_billing_country_from_data( $_POST );
            if ( $country === 'GR' ) {
                $tax_number = IW_WC_Customizations::get_posted_value( $_POST, 'billing_tax_number' );

                if ( empty( $tax_number ) ) {
                    wc_add_notice( __( 'Το ΑΦΜ είναι υποχρεωτικό για τιμολόγιο Ελλάδας.', 'iw-theme' ), 'error' );
                    return;
                }

                if ( class_exists( 'IW_Taxisnet_Integration' ) && ! IW_Taxisnet_Integration::validate_afm( $tax_number ) ) {
                    wc_add_notice( __( 'Το ΑΦΜ δεν είναι έγκυρο.', 'iw-theme' ), 'error' );
                    return;
                }

                if ( empty( IW_WC_Customizations::get_verified_aade_data( $tax_number ) ) ) {
                    wc_add_notice( __( 'Παρακαλούμε πατήστε το βελάκι για επαλήθευση του ΑΦΜ από την ΑΑΔΕ πριν ολοκληρώσετε την παραγγελία.', 'iw-theme' ), 'error' );
                    return;
                }
            }

            if ( $country !== 'GR' && IW_WC_Customizations::is_eu_vat_country( $country ) ) {
                $tax_number = IW_WC_Customizations::get_posted_value( $_POST, 'billing_tax_number' );

                if ( empty( $tax_number ) ) {
                    wc_add_notice( __( 'Το VAT number είναι υποχρεωτικό για τιμολόγιο εντός Ευρωπαϊκής Ένωσης.', 'iw-theme' ), 'error' );
                    return;
                }

                if ( empty( IW_WC_Customizations::get_verified_vies_data( $country, $tax_number ) ) ) {
                    wc_add_notice( __( 'Παρακαλούμε πατήστε το βελάκι για επαλήθευση του VAT number από το VIES πριν ολοκληρώσετε την παραγγελία.', 'iw-theme' ), 'error' );
                    return;
                }
            }

            $checkout_fields = WC()->checkout()->get_checkout_fields( 'billing' );
            foreach ( IW_WC_Customizations::get_required_invoice_fields( $country ) as $field_key) {
                if ( empty( IW_WC_Customizations::get_posted_value( $_POST, $field_key ) ) ) {
                    $label = $checkout_fields[ $field_key ]['label'] ?? ( self::get_custom_fields()[ $field_key ]['label'] ?? $field_key );
                    wc_add_notice( sprintf( __( 'Το πεδίο "%s" είναι υποχρεωτικό.', 'iw-theme' ), $label ), 'error' );
                }
            }
        } );

        add_action( 'woocommerce_checkout_create_order', function ( $order, $data ) {
            $normalized_data = IW_WC_Customizations::get_normalized_invoice_data( $_POST );
            $receipt_type = IW_WC_Customizations::get_receipt_type_from_data( $normalized_data );

            foreach ( self::get_custom_fields() as $field_key => $field ) {
                $value = IW_WC_Customizations::get_posted_value( $normalized_data, $field_key, null );
                if ( $value !== null ) {
                    $order->update_meta_data( '_' . $field_key, $value );
                }
            }

            IW_WC_Customizations::update_fields( $normalized_data );

            if ( $receipt_type !== 'invoice' ) {
                $order->delete_meta_data( '_billing_tax_validation_source' );
                return;
            }

            $country = IW_WC_Customizations::get_billing_country_from_data( $normalized_data );
            if ( $country === 'GR' ) {
                $aade_data = IW_WC_Customizations::get_verified_aade_data( IW_WC_Customizations::get_posted_value( $normalized_data, 'billing_tax_number' ) );
                $order->update_meta_data( '_billing_tax_validation_source', 'aade' );
                if ( ! empty( $aade_data ) ) {
                    $order->update_meta_data( '_billing_aade_verified_at', $aade_data['verified_at'] ?? current_time( 'mysql' ) );
                    $order->update_meta_data( '_billing_aade_snapshot', $aade_data );
                }
                return;
            }

            if ( IW_WC_Customizations::is_eu_vat_country( $country ) ) {
                $vies_data = IW_WC_Customizations::get_verified_vies_data( $country, IW_WC_Customizations::get_posted_value( $normalized_data, 'billing_tax_number' ) );
                $order->update_meta_data( '_billing_tax_validation_source', 'vies' );
                if ( ! empty( $vies_data ) ) {
                    $order->update_meta_data( '_billing_vies_verified_at', $vies_data['verified_at'] ?? current_time( 'mysql' ) );
                    $order->update_meta_data( '_billing_vies_request_identifier', $vies_data['request_identifier'] ?? '' );
                    $order->update_meta_data( '_billing_vies_snapshot', $vies_data );
                }
                return;
            }

            $order->update_meta_data( '_billing_tax_validation_source', 'manual_non_eu' );
        }, 20, 2 );


        add_filter('woocommerce_checkout_get_value', function ($value, $input) {
            if ( array_key_exists($input, IW_WC_Customizations::get_custom_fields() ) && is_user_logged_in()) {
                $user_id = get_current_user_id();
                $saved_value = get_user_meta($user_id, $input, true);
                if ($saved_value) {
                    return $saved_value;
                }
            }
            return $value;
        }, 10, 2);




        // for admin

        add_action('show_user_profile', 'iw_show_billing_company_fields');
        add_action('edit_user_profile', 'iw_show_billing_company_fields');

        function iw_show_billing_company_fields($user) { ?>
            <h3><?php _e('Στοιχεία Τιμολόγησης', 'iw-theme'); ?></h3>
            <table class="form-table">
                <?php
                $is_receipt = false;
                foreach ( IW_WC_Customizations::get_custom_fields() as $field_key => $custom_field) { ?>
                    <?php
                    $is_receipt_type = ( $field_key === 'billing_receipt_type' );
                    $saved_value = get_user_meta( $user->ID, $field_key, true );
                    if( $is_receipt_type ){
                        $is_receipt = $saved_value === 'receipt';
                    }
                    ?>
                    <tr class="<?php echo $is_receipt_type ? '' : 'iw-invoice-field-row'; ?>" <?php if( $is_receipt ) echo 'style="display: none;"' ?>>
                        <th><label for="<?php echo $field_key ?>"><?php echo $custom_field[ 'label' ]; ?></label></th>
                        <td>
                            <?php
                            

                            
                             

                            if ( isset( $custom_field['type'] ) && $custom_field['type'] === 'radio' && ! empty( $custom_field['options'] ) ) :
                                foreach ( $custom_field['options'] as $option_value => $option_label ) :
                                    ?>
                                    <label style="margin-right:15px;">
                                        <input
                                            type="radio"
                                            name="<?php echo esc_attr( $field_key ); ?>"
                                            value="<?php echo esc_attr( $option_value ); ?>"
                                            <?php checked( $saved_value ?: $option_value, $option_value ); ?>
                                        />
                                        <?php echo esc_html( $option_label ); ?>
                                    </label>
                                    <?php
                                endforeach;
                            else :
                                ?>
                                <input
                                    type="text"
                                    name="<?php echo esc_attr( $field_key ); ?>"
                                    value="<?php echo esc_attr( $saved_value ); ?>"
                                    class="regular-text"
                                />
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
            <style>
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="billing_receipt_type"]');
    const invoiceRows = document.querySelectorAll('.iw-invoice-field-row');


    function toggleInvoiceFields() {
        let selected = document.querySelector('input[name="billing_receipt_type"]:checked');
        let show = selected && selected.value === 'invoice';
        console.info(invoiceRows)

        invoiceRows.forEach(row => {
            row.style.display = show ? '' : 'none';
        });
    }

    radios.forEach(radio => {
        radio.addEventListener('change', toggleInvoiceFields);
    });

    toggleInvoiceFields();
});
</script>
            <?php
        }

        add_action('personal_options_update', 'iw_save_billing_company_fields');
        add_action('edit_user_profile_update', 'iw_save_billing_company_fields');

        function iw_save_billing_company_fields($user_id) {
            foreach ( IW_WC_Customizations::get_custom_fields() as $field_key => $custom_field) {
                if (isset($_POST[$field_key])) {
                    update_user_meta($user_id, $field_key, wc_clean(wp_unslash($_POST[$field_key])));
                }
            }
        }


        add_filter( 'manage_woocommerce_page_wc-orders_columns',function ( $columns ) {
            $new_columns = array();
            foreach ( $columns as $column_name => $column_info ) {
                if( 'origin' === $column_name || 'subscription_relationship' === $column_name ) continue;
                $new_columns[ $column_name ] = $column_info;
                if ( 'order_status' === $column_name ) {
                    $new_columns['order_shipping_method'] = __( 'Shipping Method', 'iw-theme'  );
                    $new_columns['order_payment_method']  = __( 'Payment Gateway', 'iw-theme' );
                }
            }
            return $new_columns;
        } , 20 );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', function ( $column, $order ) {
            if ( $column === 'order_shipping_method' ) {
                $items = $order->get_items( 'shipping' );
                if ( ! empty( $items ) ) {
                    foreach ( $items as $item ) {
                        echo esc_html( $item->get_name()  );
                    }
                } else {
                    echo '—';
                }
            }

            if ( $column === 'order_payment_method' ) {
                echo esc_html( $order->get_payment_method_title() );
            }
        } , 20, 2 );



        // Remove existing subscription from cart if user adds a new one


        add_action( 'admin_head', function () { ?>
            <style>.column-order_shipping_method, .column-order_payment_method{ overflow: hidden;text-overflow: ellipsis;white-space: nowrap;}</style>
        <?php } );
        // Auto-complete orders that contain no physical/shop products after payment.
        add_action( 'woocommerce_payment_complete', [ $this, 'auto_complete_subscription_only_orders' ] , 20 );



        add_filter( 'block-page-header-title', function ( $title ) {

            IW_WC_Customizations::get_order_status() === 'reserved' ? $title = __( 'Ολοκλήρωση Κράτησης' ) : $title;
            return IW_WC_Customizations::is_failed_order() ? __( 'Αποτυχία Συναλλαγής') : $title;
        }, 10, 2 );
        add_filter( 'block-page-header-text', function ( $text ) { return IW_WC_Customizations::is_failed_order() ? __( 'Δυστυχώς, η παραγγελία σας δεν μπορεί να ολοκληρωθεί.') : $text; }, 10, 2 );

        add_filter( 'iw_email_use_wc_header_footer', function ( $use, $email ) {
            return false;
        }, 10, 2 );

        // Remove Woo's default header callback globally (for all emails) before it prints.
        add_action( 'woocommerce_email_header', function ( $email_heading, $email ) {

            if ( ! $mailer = function_exists( 'WC' ) ? WC()->mailer() : null ) return;
            $header_cb = [ $mailer, 'email_header' ];
            if ( has_action( 'woocommerce_email_header', $header_cb ) ) {
                remove_action( 'woocommerce_email_header', $header_cb, 10 );
            }
        }, 0, 2 );

        add_action( 'woocommerce_email_footer', function ( $email ) {
            if ( ! $mailer = function_exists( 'WC' ) ? WC()->mailer() : null ) return;
            $footer_cb = [ $mailer, 'email_footer' ];
            if ( has_action( 'woocommerce_email_footer', $footer_cb ) ) {
                remove_action( 'woocommerce_email_footer', $footer_cb, 10 );
            }
        }, 0, 1 );

        // dont require shipping address for virtual_products

        add_filter( 'wcsg_require_shipping_address_for_virtual_products', '__return_false' );


        add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $cart_item_key, $values, $order ) {
            
            $required_fields = [ 'wcsg_gift_recipients_email', 'wcsg_gift_recipients_first_name', 'wcsg_gift_recipients_last_name', ];
            $optional_fields = [ 'wcsg_gift_recipients_message'  ];
            foreach ( $required_fields as $field ) {
                if ( empty( $values[ $field ] ) ) {
                    return;
                }
            }
            foreach ( $required_fields as $field ) {
                $item->add_meta_data( $field, $values[$field] , true );
            }
            foreach ( $optional_fields as $field ) {
                if( ! empty( $values[ $field ] ) ) {
                    $item->add_meta_data( $field, $values[$field] , true );
                }
            }

        }, 10, 4 );


        $iw_wcsg_apply_recipient_names = function ( $order_id ) {
            $recipient_map = [];

            if ( ! function_exists( 'wcs_get_subscriptions_for_order' ) || ! class_exists( 'WCS_Gifting' ) ) {
                return;
            }

            if ( empty( $order = wc_get_order( $order_id ) ) ) {
                return;
            }

            if ( empty( $subscriptions = wcs_get_subscriptions_for_order( $order_id ) ) ) {
                return;
            }

            foreach ( $order->get_items( 'line_item' ) as $item ) {
                if ( empty( $email = $item->get_meta( 'wcsg_gift_recipients_email', true ) ) ) {
                    continue;
                };
                $email = sanitize_email( $email );
                $first = $item->get_meta( 'wcsg_gift_recipients_first_name', true );
                $last  = $item->get_meta( 'wcsg_gift_recipients_last_name', true );
                $message  = $item->get_meta( 'wcsg_gift_recipients_message', true );
                if ( ! isset( $recipient_map[ $email ] ) ) {
                    $recipient_map[ $email ] = [ 'first' => $first ? sanitize_text_field( $first ) : '', 'last'  => $last ? sanitize_text_field( $last ) : '', 'message' => $message ?? ''  ];
                }
            }

            if ( empty( $recipient_map ) ) {
                return;
            }

            foreach ( $subscriptions as $subscription ) {
                if ( ! $subscription || ! is_a( $subscription, 'WC_Subscription' ) ) continue;
                $recipient_email = $subscription->get_meta( '_recipient_user_email_address' );

                if ( empty( $recipient_email ) ) continue;
                $recipient_email = sanitize_email( $recipient_email );
                if ( empty( $recipient_map[ $recipient_email ] ) ) continue;
                $first = $recipient_map[ $recipient_email ]['first'] ?? '';
                $last  = $recipient_map[ $recipient_email ]['last'] ?? '';
                $message  = $recipient_map[ $recipient_email ]['message'] ?? '';
                if ( ! $first || ! $last ) continue;
                $recipient_user_id = WCS_Gifting::get_recipient_user( $subscription );
                if ( ! $recipient_user_id ) continue;
                update_user_meta( $recipient_user_id, 'first_name', $first );
                update_user_meta( $recipient_user_id, 'last_name', $last );

                $subscription->update_meta_data( '_recipient_user_message', $message  );



                delete_user_meta( $recipient_user_id, 'wcsg_update_account', 'true' );
                $subscription->save();
            }
        };

        add_action( 'woocommerce_order_status_processing', $iw_wcsg_apply_recipient_names, 1000, 1 );
        add_action('init', function () {
            remove_post_type_support('product', 'comments');
            add_post_type_support('product', 'revisions');
        });

        add_filter( 'manage_edit-product_columns', [ __CLASS__, 'filter_product_admin_columns' ], 20 );
        add_filter( 'manage_product_posts_columns', [ __CLASS__, 'filter_product_posts_columns' ], 999 );
        add_action( 'manage_product_posts_custom_column', [ __CLASS__, 'render_product_admin_column' ], 10, 2 );
        add_filter( 'manage_edit-product_sortable_columns', [ __CLASS__, 'register_sortable_product_columns' ], 20 );
        add_filter( 'posts_clauses', [ __CLASS__, 'filter_product_admin_orderby' ], 10, 2 );
        add_action( 'admin_head', [ __CLASS__, 'print_product_admin_column_styles' ] );
        add_filter( 'woocommerce_product_get_sku', [ __CLASS__, 'filter_admin_product_sku' ], 10, 2 );
        add_filter( 'wp_editor_settings', [ __CLASS__, 'filter_product_editor_settings' ], 10, 2 );
        add_filter( 'mce_buttons', [ __CLASS__, 'filter_product_editor_buttons' ], 10, 2 );
        add_filter( 'mce_buttons_2', [ __CLASS__, 'filter_product_editor_buttons_row_2' ], 10, 2 );
        add_filter( 'tiny_mce_before_init', [ __CLASS__, 'filter_product_editor_init' ], 10, 2 );

        add_filter( 'woocommerce_quantity_input_args', function ( $args, $product ) {
            $args[ 'is_subscription'] = class_exists('WC_Subscriptions_Product') && WC_Subscriptions_Product::is_subscription( $product );
            return $args;
        }, 10, 2 );



        add_filter( 'woocommerce_admin_features', function ( $features ) { return array_diff( $features, [ 'email_improvements' ] );} );

        add_filter( 'woocommerce_feature_email_improvements_enabled', '__return_false');

        add_filter( 'woocommerce_email_styles', function ( $css ) { return ''; });




        add_action( 'woocommerce_init', function () {
            remove_action( 'woocommerce_created_customer',  'WCSG_Email::send_new_recipient_user_email', 10 );
            remove_action( 'subscriptions_activated_for_order', 'WCSG_Email::maybe_send_recipient_order_emails', 11 );
        }, 200 );


        add_filter( 'woocommerce_email_subject_WCSG_Email_Customer_New_Account', function ( $subject ) {
            $site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

            return apply_filters(
                'iw_wc_customizations_gifting_new_account_subject',
                sprintf( __( 'Welcome to %s', 'iw-theme' ), $site_name ),
                $subject,
                $site_name
            );
        }, 10, 2 );
        add_action( 'subscriptions_activated_for_order', function ( $order) {
            if (
                ! function_exists( 'wcs_get_subscriptions' )
                || ! function_exists( 'wcs_get_subscription' )
                || ! class_exists( 'WCS_Gifting' )
                || ! class_exists( 'WCSG_Recipient_Management' )
            ) {
                return;
            }

            $order_id             = $order instanceof WC_Order ? $order->get_id() : $order;
            $subscriptions        = wcs_get_subscriptions( array( 'order_id' => $order_id ) );
            $processed_recipients = array();

            if ( empty( $subscriptions ) ) {
                return;
            }

            WC()->mailer();

            foreach ( $subscriptions as $subscription ) {
                if ( ! WCS_Gifting::is_gifted_subscription( $subscription ) ) {
                    continue;
                }

                $recipient_user_id = WCS_Gifting::get_recipient_user( $subscription );

                if ( in_array( $recipient_user_id, $processed_recipients ) ) {
                    continue;
                }

                $reset_key = get_password_reset_key( get_user_by( 'id', $recipient_user_id ) );

                $recipient_subscriptions = WCSG_Recipient_Management::get_recipient_subscriptions( $recipient_user_id, $order_id );
                if ( empty( $recipient_subscriptions ) ) {
                    continue;
                }

                $subscription             = wcs_get_subscription( $recipient_subscriptions[0] );
                $subscription_purchaser = WCS_Gifting::get_user_display_name( $subscription->get_user_id() );

                do_action( 'wcsg_created_customer_notification', $recipient_user_id, $reset_key, $subscription_purchaser );
                $processed_recipients[] = $recipient_user_id;
            }
        }
                , 100, 1 );

        
    }

    public static function filter_product_admin_columns( $columns ) {
        if ( isset( $columns['wpseo-score'] ) ) {
            unset( $columns['wpseo-score'] );
        }
        if ( isset( $columns['wpseo-score-readability'] ) ) {
            unset( $columns['wpseo-score-readability'] );
        }
        if ( isset( $columns['wpseo-title'] ) ) {
            unset( $columns['wpseo-title'] );
        }
        if ( isset( $columns['wpseo-metadesc'] ) ) {
            unset( $columns['wpseo-metadesc'] );
        }
        if ( isset( $columns['wpseo-focuskw'] ) ) {
            unset( $columns['wpseo-focuskw'] );
        }
        if ( isset( $columns['rank_math_seo_score'] ) ) {
            unset( $columns['rank_math_seo_score'] );
        }

        return self::inject_product_admin_columns( $columns );
    }

    public static function filter_product_posts_columns( $columns ) {
        if ( isset( $columns['iw_restock_interest'] ) && isset( $columns['iw_custom_labels'] ) ) {
            return $columns;
        }

        return self::inject_product_admin_columns( $columns );
    }

    public static function inject_product_admin_columns( $columns ) {
        $updated_columns = [];

        foreach ( $columns as $key => $label ) {
            $updated_columns[ $key ] = $label;

            if ( $key === 'sku' ) {
                $updated_columns['iw_custom_labels'] = __( 'Custom Labels', 'iw-theme' );
                $updated_columns['iw_restock_interest'] = __( 'Ενδιαφέρον', 'iw-theme' );
            }
        }

        if ( ! isset( $updated_columns['iw_custom_labels'] ) ) {
            $updated_columns['iw_custom_labels'] = __( 'Custom Labels', 'iw-theme' );
        }

        if ( ! isset( $updated_columns['iw_restock_interest'] ) ) {
            $updated_columns['iw_restock_interest'] = __( 'Ενδιαφέρον', 'iw-theme' );
        }

        return $updated_columns;
    }

    public static function render_product_admin_column( $column, $post_id ) {
        if ( $column === 'iw_custom_labels' ) {
            $custom_labels = trim( (string) get_field( 'custom_labels', $post_id ) );

            echo $custom_labels !== '' ? esc_html( $custom_labels ) : '—';
            return;
        }

        if ( $column !== 'iw_restock_interest' || ! class_exists( 'IW_WC_Check_Availability' ) ) {
            return;
        }

        $counts  = IW_WC_Check_Availability::get_notification_counts( $post_id );
        $pending = (int) ( $counts['pending'] ?? 0 );
        $total   = (int) ( $counts['total'] ?? 0 );

        if ( $total <= 0 ) {
            echo '0';
            return;
        }

        echo esc_html( $pending );

        if ( $total !== $pending ) {
            echo ' / ' . esc_html( $total );
        }
    }

    public static function register_sortable_product_columns( $columns ) {
        $columns['iw_restock_interest'] = 'iw_restock_interest';

        return $columns;
    }

    public static function filter_product_admin_orderby( $clauses, $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return $clauses;
        }

        if ( $query->get( 'post_type' ) !== 'product' || $query->get( 'orderby' ) !== 'iw_restock_interest' ) {
            return $clauses;
        }

        global $wpdb;

        $meta_key = class_exists( 'IW_WC_Check_Availability' )
            ? IW_WC_Check_Availability::PENDING_COUNT_META_KEY
            : '_iw_restock_notifications_pending_count';

        $order = strtoupper( (string) $query->get( 'order' ) ) === 'ASC' ? 'ASC' : 'DESC';

        if ( strpos( $clauses['join'], 'iw_restock_interest_pm' ) === false ) {
            $clauses['join'] .= $wpdb->prepare(
                " LEFT JOIN {$wpdb->postmeta} AS iw_restock_interest_pm
                  ON {$wpdb->posts}.ID = iw_restock_interest_pm.post_id
                  AND iw_restock_interest_pm.meta_key = %s",
                $meta_key
            );
        }

        $clauses['orderby'] = "CAST(COALESCE(iw_restock_interest_pm.meta_value, '0') AS UNSIGNED) {$order}, {$wpdb->posts}.ID DESC";

        return $clauses;
    }

    public static function print_product_admin_column_styles() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }

        $screen = get_current_screen();

        if ( ! $screen || $screen->id !== 'edit-product' ) {
            return;
        }
        ?>
        <style>
            .wp-list-table .column-sku {
                white-space: pre-line;
                line-height: 1.4;
            }
            .wp-list-table .column-iw_custom_labels {
                min-width: 160px;
            }
            .wp-list-table .column-iw_restock_interest {
                width: 110px;
                min-width: 110px;
                text-align: center;
                white-space: nowrap;
            }
        </style>
        <?php
    }

    public static function filter_admin_product_sku( $sku, $product ) {
        if ( ! is_admin() || wp_doing_ajax() || ! $product || ! $product->is_type( 'variable' ) ) {
            return $sku;
        }

        if ( ! function_exists( 'get_current_screen' ) ) {
            return $sku;
        }

        $screen = get_current_screen();

        if ( ! $screen || $screen->id !== 'edit-product' ) {
            return $sku;
        }

        $variation_skus = array_filter(
            array_unique(
                array_map(
                    static function( $variation_id ) {
                        $variation = wc_get_product( $variation_id );

                        return $variation ? $variation->get_sku() : '';
                    },
                    $product->get_children()
                )
            )
        );

        if ( empty( $variation_skus ) ) {
            return $sku;
        }

        return implode( "\n", $variation_skus );
    }

    public static function is_product_description_editor( $editor_id = '' ) {
        if ( ! is_admin() || $editor_id !== 'content' ) {
            return false;
        }

        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();

            if ( $screen && $screen->base === 'post' && $screen->post_type === 'product' ) {
                return true;
            }
        }

        global $typenow;

        return $typenow === 'product';
    }

    public static function filter_product_editor_settings( $settings, $editor_id ) {
        if ( ! self::is_product_description_editor( $editor_id ) ) {
            return $settings;
        }

        $settings['media_buttons'] = false;

        return $settings;
    }

    public static function filter_product_editor_buttons( $buttons, $editor_id ) {
        if ( ! self::is_product_description_editor( $editor_id ) ) {
            return $buttons;
        }

        return [ 'bold', 'italic', 'link', 'unlink' ];
    }

    public static function filter_product_editor_buttons_row_2( $buttons, $editor_id ) {
        if ( ! self::is_product_description_editor( $editor_id ) ) {
            return $buttons;
        }

        return [];
    }

    public static function filter_product_editor_init( $init, $editor_id ) {
        if ( ! self::is_product_description_editor( $editor_id ) ) {
            return $init;
        }

        $init['toolbar1']                = 'bold italic link unlink';
        $init['toolbar2']                = '';
        $init['block_formats']           = '';
        $init['menubar']                 = false;
        $init['forced_root_block']       = false;
        $init['force_br_newlines']       = true;
        $init['force_p_newlines']        = false;
        $init['convert_newlines_to_brs'] = true;

        return $init;
    }

    public static function auto_complete_subscription_only_orders( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;
        if ( $order->has_status( [ 'completed', 'reserved' ] ) || ! $order->is_paid() ) return;
        $has_shop_product = false;
        foreach ( $order->get_items( 'line_item' ) as $item ) {
            $product = $item->get_product();

            $tickets_for_id = absint( $item->get_meta( 'tickets_for_id', true ) );
            if ( $tickets_for_id ) continue;

            $iw_item_type = $item->get_meta( 'iw_item_type', true ) ?: $item->get_meta( '_iw_item_type', true );
            if ( in_array( $iw_item_type, [ 'tickets', 'donation' ], true ) ) continue;

            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            if (
                class_exists( 'IW_Donations' )
                && (
                    IW_Donations::is_donation_product( $product_id )
                    || ( $variation_id && IW_Donations::is_donation_product( $variation_id ) )
                )
            ) {
                continue;
            }

            if ( $product && class_exists( 'WC_Subscriptions_Product' ) ) {
                $is_subscription = WC_Subscriptions_Product::is_subscription( $product );
            } elseif ( $product ) {
                $is_subscription = $product->is_type( [ 'subscription', 'variable-subscription', 'subscription_variation' ] );
            } else {
                $is_subscription = false;
            }

            if ( ! $is_subscription ) {
                $has_shop_product = true;
                break;
            }
        }

        if ( $has_shop_product ) return;

        foreach ( $order->get_items( 'shipping' ) as $shipping_item_id => $shipping_item ) {
            $order->remove_item( $shipping_item_id );
        }
        $order->set_shipping_total( 0 );
        $order->set_shipping_tax( 0 );
        $order->calculate_totals( false );
        $order->update_status( 'completed', __( 'Auto-completed: order contains no physical/shop products.', 'iw-theme' ), true );
    }

    public static function is_failed_order() {
        $failed = is_wc_endpoint_url( 'order-received' )  && ! empty( $order_id = absint( get_query_var( 'order-received' ) ) ) && ! empty( $order = wc_get_order( $order_id ) );
        if( ! $failed ) return false;
        // If the order is paid it's definitely not failed
        if ( $order->is_paid() ) {
            return false;
        }

        $status = $order->get_status();
        // Only treat real WooCommerce failure states as failed
        if ( in_array( $status, [ 'failed', 'cancelled' ], true ) ) {
            return $order;
        }

        return false;
    }

    public static function get_order_status() {
        $failed = is_wc_endpoint_url( 'order-received' )  && ! empty( $order_id = absint( get_query_var( 'order-received' ) ) ) && ! empty( $order = wc_get_order( $order_id ) );
        if( ! $failed ) return false;
        return $order->get_status();
    }



}

new IW_WC_Customizations();
