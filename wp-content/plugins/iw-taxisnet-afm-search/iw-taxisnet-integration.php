<?php
/**
 * Plugin Name: IW Taxisnet AFM Search
 * Description: Verifies Greek tax numbers through AADE and EU VAT numbers through VIES.
 * Version: 1.1.0
 * Author: Interweave
 * Text Domain: iw-taxisnet-afm-search
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class IW_Taxisnet_Integration {
    private const AADE_SESSION_KEY = 'iw_aade_invoice_data';
    private const VIES_SESSION_KEY = 'iw_vies_invoice_data';

    private static $instance = null;

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_ajax_search_tax_number', [ $this, 'ajax_search_tax_number' ] );
        add_action( 'wp_ajax_nopriv_search_tax_number', [ $this, 'ajax_search_tax_number' ] );
    }

    public function add_settings_page() {
        add_options_page(
            __( 'AADE / Taxisnet verification', 'iw-taxisnet-afm-search' ),
            __( 'AADE / Taxisnet', 'iw-taxisnet-afm-search' ),
            'manage_options',
            'iw-taxisnet-integration',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting(
            'iw_taxisnet_settings',
            'iw_taxisnet_username',
            [ 'sanitize_callback' => 'sanitize_text_field' ]
        );
        register_setting(
            'iw_taxisnet_settings',
            'iw_taxisnet_password',
            [ 'sanitize_callback' => 'sanitize_text_field' ]
        );
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?= esc_html__( 'AADE / Taxisnet verification', 'iw-taxisnet-afm-search' ) ?></h1>
            <p>
                <?= esc_html__( 'Enter the dedicated RgWsPublic2 web-service credentials supplied by AADE. They are not the credentials used to sign in to Taxisnet.', 'iw-taxisnet-afm-search' ) ?>
            </p>
            <form method="post" action="options.php">
                <?php settings_fields( 'iw_taxisnet_settings' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="iw_taxisnet_username"><?= esc_html__( 'Username', 'iw-taxisnet-afm-search' ) ?></label>
                        </th>
                        <td>
                            <input
                                id="iw_taxisnet_username"
                                class="regular-text"
                                type="text"
                                name="iw_taxisnet_username"
                                value="<?= esc_attr( get_option( 'iw_taxisnet_username' ) ) ?>"
                                autocomplete="off"
                            >
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="iw_taxisnet_password"><?= esc_html__( 'Password', 'iw-taxisnet-afm-search' ) ?></label>
                        </th>
                        <td>
                            <input
                                id="iw_taxisnet_password"
                                class="regular-text"
                                type="password"
                                name="iw_taxisnet_password"
                                value="<?= esc_attr( get_option( 'iw_taxisnet_password' ) ) ?>"
                                autocomplete="new-password"
                            >
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function validate_afm( $afm ) {
        $afm = preg_replace( '/\D+/', '', (string) $afm );

        if ( strlen( $afm ) !== 9 || $afm === str_repeat( '0', 9 ) ) {
            return false;
        }

        $sum = 0;
        for ( $index = 0; $index < 8; $index++ ) {
            $sum += (int) $afm[ $index ] * ( 1 << ( 8 - $index ) );
        }

        return ( $sum % 11 ) % 10 === (int) $afm[8];
    }

    public static function get_vies_country_code( $country ) {
        $country = strtoupper( trim( (string) $country ) );

        return $country === 'GR' ? 'EL' : $country;
    }

    public static function normalize_vat_number( $country, $vat_number ) {
        $country_code = self::get_vies_country_code( $country );
        $vat_number = strtoupper( trim( (string) $vat_number ) );
        $vat_number = preg_replace( '/[^A-Z0-9]/', '', $vat_number );

        if ( $country_code !== '' && str_starts_with( $vat_number, $country_code ) ) {
            $vat_number = substr( $vat_number, strlen( $country_code ) );
        }

        if ( $country_code === 'EL' && str_starts_with( $vat_number, 'GR' ) ) {
            $vat_number = substr( $vat_number, 2 );
        }

        return $vat_number;
    }

    public static function get_billing_fields_from_company_info( $data ) {
        $postcode = $data['postcode'] ?? ( $data['post_code'] ?? '' );

        return [
            'billing_receipt_type'       => 'invoice',
            'billing_tax_number'         => $data['tax_number'] ?? '',
            'billing_company_name'       => $data['company_name'] ?? '',
            'billing_company_profession' => $data['company_profession'] ?? '',
            'billing_tax_office'         => $data['tax_office'] ?? '',
            'billing_address_1'          => $data['address_1'] ?? '',
            'billing_postcode'           => $postcode,
            'billing_city'               => $data['city'] ?? '',
            'billing_country'            => $data['country'] ?? 'GR',
        ];
    }

    public static function get_billing_fields_from_vies_info( $data ) {
        return array_filter(
            [
                'billing_receipt_type' => 'invoice',
                'billing_tax_number'   => $data['tax_number'] ?? '',
                'billing_company_name' => $data['company_name'] ?? '',
                'billing_address_1'    => $data['address_1'] ?? '',
                'billing_country'      => $data['country'] ?? '',
            ],
            static fn( $value ) => $value !== null && $value !== ''
        );
    }

    private static function get_wc_session_value( $key ) {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return null;
        }

        return WC()->session->get( $key );
    }

    private static function persist_verified_data( $source, $data, $billing_fields ) {
        if ( empty( $data['tax_number'] ) ) {
            return;
        }

        $verified_at = $data['verified_at'] ?? current_time( 'mysql' );
        $data['verified_source'] = $source;
        $data['verified_at'] = $verified_at;
        $session_key = $source === 'aade' ? self::AADE_SESSION_KEY : self::VIES_SESSION_KEY;

        $verification_fields = $source === 'aade'
            ? [
                'billing_tax_validation_source' => 'aade',
                'billing_aade_verified_at'       => $verified_at,
                'billing_aade_snapshot'          => $data,
            ]
            : [
                'billing_tax_validation_source'   => 'vies',
                'billing_vies_verified_at'         => $verified_at,
                'billing_vies_request_identifier' => $data['request_identifier'] ?? '',
                'billing_vies_snapshot'            => $data,
            ];

        if ( function_exists( 'WC' ) && WC()->session ) {
            WC()->session->set( $session_key, $data );
            foreach ( array_merge( $billing_fields, $verification_fields ) as $field_key => $value ) {
                WC()->session->set( $field_key, $value );
            }
        }

        if ( function_exists( 'WC' ) && WC()->customer ) {
            $customer = WC()->customer;
            $core_setters = [
                'billing_country'   => 'set_billing_country',
                'billing_postcode'  => 'set_billing_postcode',
                'billing_city'      => 'set_billing_city',
                'billing_address_1' => 'set_billing_address_1',
            ];

            foreach ( $billing_fields as $field_key => $value ) {
                if ( isset( $core_setters[ $field_key ] ) ) {
                    $customer->{$core_setters[ $field_key ]}( $value );
                } else {
                    $customer->update_meta_data( $field_key, $value );
                }
            }

            foreach ( $verification_fields as $field_key => $value ) {
                $customer->update_meta_data( $field_key, $value );
            }
            $customer->save();
        }

        if ( is_user_logged_in() ) {
            foreach ( array_merge( $billing_fields, $verification_fields ) as $field_key => $value ) {
                update_user_meta( get_current_user_id(), $field_key, $value );
            }
        }
    }

    public static function save_company_info_to_session( $data ) {
        self::persist_verified_data( 'aade', $data, self::get_billing_fields_from_company_info( $data ) );
    }

    public static function save_vies_company_info_to_session( $data ) {
        self::persist_verified_data( 'vies', $data, self::get_billing_fields_from_vies_info( $data ) );
    }

    public static function get_saved_company_info() {
        $data = self::get_wc_session_value( self::AADE_SESSION_KEY );
        if ( is_array( $data ) && ! empty( $data['tax_number'] ) ) {
            return $data;
        }

        if ( ! is_user_logged_in() || get_user_meta( get_current_user_id(), 'billing_tax_validation_source', true ) !== 'aade' ) {
            return [];
        }

        $snapshot = get_user_meta( get_current_user_id(), 'billing_aade_snapshot', true );
        if ( is_array( $snapshot ) && ! empty( $snapshot['tax_number'] ) ) {
            $snapshot['verified_source'] = 'aade';
            $snapshot['verified_at'] = $snapshot['verified_at'] ?? get_user_meta( get_current_user_id(), 'billing_aade_verified_at', true );

            return $snapshot;
        }

        return [
            'tax_number'         => get_user_meta( get_current_user_id(), 'billing_tax_number', true ),
            'company_name'       => get_user_meta( get_current_user_id(), 'billing_company_name', true ),
            'company_profession' => get_user_meta( get_current_user_id(), 'billing_company_profession', true ),
            'tax_office'         => get_user_meta( get_current_user_id(), 'billing_tax_office', true ),
            'address_1'          => get_user_meta( get_current_user_id(), 'billing_address_1', true ),
            'postcode'           => get_user_meta( get_current_user_id(), 'billing_postcode', true ),
            'city'               => get_user_meta( get_current_user_id(), 'billing_city', true ),
            'country'            => get_user_meta( get_current_user_id(), 'billing_country', true ) ?: 'GR',
            'verified_source'    => 'aade',
            'verified_at'        => get_user_meta( get_current_user_id(), 'billing_aade_verified_at', true ),
        ];
    }

    public static function get_saved_vies_company_info() {
        $data = self::get_wc_session_value( self::VIES_SESSION_KEY );
        if ( is_array( $data ) && ! empty( $data['tax_number'] ) ) {
            return $data;
        }

        if ( ! is_user_logged_in() || get_user_meta( get_current_user_id(), 'billing_tax_validation_source', true ) !== 'vies' ) {
            return [];
        }

        $snapshot = get_user_meta( get_current_user_id(), 'billing_vies_snapshot', true );
        if ( is_array( $snapshot ) && ! empty( $snapshot['tax_number'] ) ) {
            $snapshot['verified_source'] = 'vies';
            $snapshot['verified_at'] = $snapshot['verified_at'] ?? get_user_meta( get_current_user_id(), 'billing_vies_verified_at', true );

            return $snapshot;
        }

        $country = get_user_meta( get_current_user_id(), 'billing_country', true );

        return [
            'tax_number'         => get_user_meta( get_current_user_id(), 'billing_tax_number', true ),
            'vat_number'         => self::normalize_vat_number( $country, get_user_meta( get_current_user_id(), 'billing_tax_number', true ) ),
            'vat_country'        => self::get_vies_country_code( $country ),
            'company_name'       => get_user_meta( get_current_user_id(), 'billing_company_name', true ),
            'address_1'          => get_user_meta( get_current_user_id(), 'billing_address_1', true ),
            'country'            => $country,
            'request_identifier' => get_user_meta( get_current_user_id(), 'billing_vies_request_identifier', true ),
            'verified_source'    => 'vies',
            'verified_at'        => get_user_meta( get_current_user_id(), 'billing_vies_verified_at', true ),
        ];
    }

    public static function get_vies_company_info( $country, $vat_number ) {
        $country = strtoupper( trim( (string) $country ) );
        $vies_country = self::get_vies_country_code( $country );
        $normalized_vat = self::normalize_vat_number( $country, $vat_number );

        if ( $vies_country === '' || $normalized_vat === '' ) {
            return [ 'error' => true, 'message' => __( 'Συμπληρώστε χώρα και VAT number.', 'iw-taxisnet-afm-search' ) ];
        }

        $url = sprintf(
            'https://ec.europa.eu/taxation_customs/vies/rest-api/ms/%s/vat/%s',
            rawurlencode( $vies_country ),
            rawurlencode( $normalized_vat )
        );
        $response = wp_remote_get(
            $url,
            [
                'timeout' => 15,
                'headers' => [ 'Accept' => 'application/json' ],
            ]
        );

        $body = '';
        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            if ( ! function_exists( 'curl_init' ) ) {
                return [ 'error' => true, 'message' => __( 'Το VIES δεν είναι διαθέσιμο αυτή τη στιγμή. Δοκιμάστε ξανά σε λίγο.', 'iw-taxisnet-afm-search' ) ];
            }

            $curl = curl_init( $url );
            curl_setopt_array(
                $curl,
                [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_HTTPHEADER     => [ 'Accept: application/json' ],
                ]
            );
            $body = curl_exec( $curl );
            $curl_error = curl_error( $curl );
            $response_code = curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );
            curl_close( $curl );

            if ( $body === false || $curl_error !== '' || (int) $response_code !== 200 ) {
                return [ 'error' => true, 'message' => __( 'Το VIES δεν είναι διαθέσιμο αυτή τη στιγμή. Δοκιμάστε ξανά σε λίγο.', 'iw-taxisnet-afm-search' ) ];
            }
        } else {
            $body = wp_remote_retrieve_body( $response );
        }

        $json = json_decode( $body, true );
        if ( ! is_array( $json ) ) {
            return [ 'error' => true, 'message' => __( 'Δεν ήταν δυνατή η ανάγνωση της απάντησης από το VIES.', 'iw-taxisnet-afm-search' ) ];
        }

        if ( empty( $json['isValid'] ) ) {
            $unavailable_errors = [ 'MS_UNAVAILABLE', 'SERVICE_UNAVAILABLE', 'SERVER_BUSY', 'TIMEOUT', 'GLOBAL_MAX_CONCURRENT_REQ', 'MS_MAX_CONCURRENT_REQ' ];
            $message = in_array( $json['userError'] ?? '', $unavailable_errors, true )
                ? __( 'Το VIES δεν είναι διαθέσιμο αυτή τη στιγμή. Δοκιμάστε ξανά σε λίγο.', 'iw-taxisnet-afm-search' )
                : __( 'Το VAT number δεν είναι έγκυρο στο VIES.', 'iw-taxisnet-afm-search' );

            return [
                'error'        => true,
                'message'      => $message,
                'vies_error'   => $json['userError'] ?? '',
                'raw_response' => $json,
            ];
        }

        $name = isset( $json['name'] ) && $json['name'] !== '---' ? $json['name'] : '';
        $address = isset( $json['address'] ) && $json['address'] !== '---' ? $json['address'] : '';
        $tax_number = $vies_country . ( $json['vatNumber'] ?? $normalized_vat );

        return [
            'tax_number'         => $tax_number,
            'vat_number'         => $json['vatNumber'] ?? $normalized_vat,
            'vat_country'        => $vies_country,
            'company_name'       => $name,
            'company_info'       => implode( '<br>', array_filter( [ trim( $tax_number ), nl2br( esc_html( $address ) ) ] ) ),
            'address_1'          => preg_replace( '/\s+/', ' ', $address ),
            'country'            => $country,
            'request_date'       => $json['requestDate'] ?? '',
            'request_identifier' => $json['requestIdentifier'] ?? '',
            'is_active'          => true,
            'is_company'         => true,
            'verified_source'    => 'vies',
            'verified_at'        => current_time( 'mysql' ),
            'raw_response'       => $json,
        ];
    }

    private static function get_aade_credentials() {
        return [
            'username' => defined( 'IW_TAXISNET_USERNAME' ) ? IW_TAXISNET_USERNAME : get_option( 'iw_taxisnet_username' ),
            'password' => defined( 'IW_TAXISNET_PASSWORD' ) ? IW_TAXISNET_PASSWORD : get_option( 'iw_taxisnet_password' ),
        ];
    }

    public static function get_company_info( $afm ) {
        $afm = preg_replace( '/\D+/', '', (string) $afm );
        if ( ! self::validate_afm( $afm ) ) {
            return [ 'error' => true, 'message' => __( 'Το ΑΦΜ δεν είναι έγκυρο.', 'iw-taxisnet-afm-search' ) ];
        }

        $credentials = self::get_aade_credentials();
        if ( empty( $credentials['username'] ) || empty( $credentials['password'] ) ) {
            return [ 'error' => true, 'message' => __( 'Δεν έχουν ρυθμιστεί τα στοιχεία σύνδεσης της υπηρεσίας ΑΑΔΕ.', 'iw-taxisnet-afm-search' ) ];
        }

        if ( ! class_exists( 'SoapClient' ) ) {
            return [ 'error' => true, 'message' => __( 'Η υπηρεσία SOAP δεν είναι διαθέσιμη στον διακομιστή.', 'iw-taxisnet-afm-search' ) ];
        }

        try {
            $client = new SoapClient(
                'https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2?WSDL',
                [
                    'trace'        => false,
                    'soap_version' => SOAP_1_2,
                    'exceptions'   => true,
                    'connection_timeout' => 15,
                ]
            );
            $auth_header = (object) [
                'UsernameToken' => (object) [
                    'Username' => $credentials['username'],
                    'Password' => $credentials['password'],
                ],
            ];
            $client->__setSoapHeaders(
                [
                    new SoapHeader(
                        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
                        'Security',
                        $auth_header,
                        true
                    ),
                ]
            );
            $response = $client->rgWsPublic2AfmMethod(
                [
                    'INPUT_REC' => [
                        'afm_called_by'  => '',
                        'afm_called_for' => $afm,
                    ],
                ]
            );
            $result = $response->result->rg_ws_public2_result_rtType ?? null;

            if ( ! $result ) {
                return [ 'error' => true, 'message' => __( 'Δεν ελήφθη έγκυρη απάντηση από την ΑΑΔΕ.', 'iw-taxisnet-afm-search' ) ];
            }

            $error_code = $result->error_rec->error_code ?? '';
            if ( $error_code !== '' ) {
                $authentication_errors = [
                    'RG_WS_PUBLIC_TOKEN_USERNAME_NOT_DEFINED',
                    'RG_WS_PUBLIC_TOKEN_USERNAME_NOT_AUTHENTICATED',
                ];
                $message = in_array( $error_code, $authentication_errors, true )
                    ? __( 'Πρόβλημα σύνδεσης στην υπηρεσία ΑΑΔΕ.', 'iw-taxisnet-afm-search' )
                    : __( 'Το ΑΦΜ δεν βρέθηκε στο μητρώο της ΑΑΔΕ.', 'iw-taxisnet-afm-search' );

                return [ 'error' => true, 'message' => $message ];
            }

            if ( empty( $result->firm_act_tab ) ) {
                return [ 'error' => true, 'message' => __( 'Το ΑΦΜ δεν διαθέτει ενεργό ΚΑΔ.', 'iw-taxisnet-afm-search' ) ];
            }

            $info = $result->basic_rec ?? null;
            if ( ! $info || ! empty( $info->stop_date ) ) {
                return [ 'error' => true, 'message' => __( 'Το ΑΦΜ δεν είναι ενεργό.', 'iw-taxisnet-afm-search' ) ];
            }

            $activities = $result->firm_act_tab->item ?? [];
            if ( is_object( $activities ) ) {
                $activities = [ $activities ];
            }
            $main_activity = null;
            foreach ( (array) $activities as $activity ) {
                if ( isset( $activity->firm_act_kind ) && (string) $activity->firm_act_kind === '1' ) {
                    $main_activity = [
                        'code'        => $activity->firm_act_code ?? '',
                        'description' => $activity->firm_act_descr ?? '',
                    ];
                    break;
                }
            }

            $address_1 = trim( implode( ' ', array_filter( [ $info->postal_address ?? '', $info->postal_address_no ?? '' ] ) ) );
            $postcode = $info->postal_zip_code ?? '';
            $city = $info->postal_area_description ?? '';
            $company_name = ! empty( $info->commer_title ) ? $info->commer_title : ( $info->onomasia ?? '' );
            $company_info = implode(
                '<br>',
                array_filter(
                    [
                        $main_activity['description'] ?? '',
                        trim( $afm . ( ! empty( $info->doy_descr ) ? ' | ' . __( 'ΔΟΥ', 'iw-taxisnet-afm-search' ) . ' ' . $info->doy_descr : '' ) ),
                        implode( ', ', array_filter( [ $address_1, $postcode, $city ] ) ),
                    ]
                )
            );

            return [
                'tax_number'         => $afm,
                'company_name'       => $company_name,
                'company_profession' => $main_activity['description'] ?? '',
                'company_info'       => $company_info,
                'tax_office'         => $info->doy_descr ?? '',
                'postcode'           => $postcode,
                'post_code'          => $postcode,
                'city'               => $city,
                'registration_date'  => $info->regist_date ?? '',
                'stop_date'          => $info->stop_date ?? '',
                'is_active'          => true,
                'is_company'         => true,
                'main_kad_code'      => $main_activity['code'] ?? '',
                'street'             => $info->postal_address ?? '',
                'street_number'      => $info->postal_address_no ?? '',
                'address_1'          => $address_1,
                'country'            => 'GR',
                'verified_source'    => 'aade',
                'verified_at'        => current_time( 'mysql' ),
            ];
        } catch ( Throwable $exception ) {
            return [ 'error' => true, 'message' => __( 'Η ΑΑΔΕ δεν είναι διαθέσιμη αυτή τη στιγμή. Δοκιμάστε ξανά σε λίγο.', 'iw-taxisnet-afm-search' ) ];
        }
    }

    public function ajax_search_tax_number() {
        check_ajax_referer( 'search_tax_number_nonce', 'security' );

        $tax_number = isset( $_POST['tax_number'] ) ? sanitize_text_field( wp_unslash( $_POST['tax_number'] ) ) : '';
        $country = isset( $_POST['country'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['country'] ) ) ) : 'GR';

        if ( $country !== 'GR' && class_exists( 'IW_WC_Customizations' ) && IW_WC_Customizations::is_eu_vat_country( $country ) ) {
            $data = self::get_vies_company_info( $country, $tax_number );
            if ( ! empty( $data['error'] ) ) {
                wp_send_json_error( $data );
            }

            self::save_vies_company_info_to_session( $data );
            wp_send_json_success( $data );
        }

        if ( $country !== 'GR' ) {
            wp_send_json_error(
                [
                    'error'   => true,
                    'message' => __( 'Δεν υπάρχει αυτόματη επαλήθευση για χώρες εκτός Ευρωπαϊκής Ένωσης.', 'iw-taxisnet-afm-search' ),
                ]
            );
        }

        $data = self::get_company_info( $tax_number );
        if ( ! empty( $data['error'] ) ) {
            wp_send_json_error( $data );
        }

        self::save_company_info_to_session( $data );
        wp_send_json_success( $data );
    }
}

IW_Taxisnet_Integration::get_instance();
