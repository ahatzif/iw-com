<?php


add_action( 'wp_ajax_iw-auth-edit-address', function () {

    //check_ajax_referer( 'update_shipping_address', 'security' );

    $customer = new WC_Customer( get_current_user_id() );
    $addressType = isset( $_POST['address_type'] ) ? sanitize_key( $_POST['address_type'] ) : 'billing';
    if ( ! in_array( $addressType, [ 'billing', 'shipping' ], true ) ) {
        wp_send_json_error( [ 'message' => __( 'Bad request', 'iw-theme' ) ] );
    }

    $user_fields = isset( $_POST['user_fields'] ) && is_array( $_POST['user_fields'] ) ? wp_unslash( $_POST['user_fields'] ) : [];
    $get_field = function( $name ) use ( $user_fields, $addressType ) {
        return sanitize_text_field( $user_fields[ $addressType . '_' . $name ] ?? '' );
    };
    $send_error = function( $message ) {
        wp_send_json_error( [ 'message' => $message ] );
    };

    $data = [
        'first_name' => $get_field( 'first_name' ),
        'last_name'  => $get_field( 'last_name' ),
        'country'    => $get_field( 'country' ),
        'address_1'  => $get_field( 'address_1' ),
        'address_2'  => $get_field( 'address_2' ),
        'city'       => $get_field( 'city' ),
        'state'      => $get_field( 'state' ),
        'postcode'   => $get_field( 'postcode' ),
        'phone'      => $get_field( 'phone' ),
        'email'      => $get_field( 'email' ),
    ] ;

    $addressArgs = [
        'first_name' => 'required',
        'last_name'  => 'required',
        'country'    => 'required',
        'address_1'  => 'required',
        'address_2'  => '',
        'city'       => 'required',
        'state'      => 'required',
        'postcode'   => 'required',
        'phone'      => '',
        'email'      => $addressType === 'billing' ? 'email|required' : 'email',
    ];

    $tax_validation_source = '';
    $aade_data = [];
    $vies_data = [];

    if ( $addressType === 'billing' ) {
        $data = array_merge( $data, [
            'receipt_type'         => $get_field( 'receipt_type' ) ?: 'receipt',
            'tax_number'           => $get_field( 'tax_number' ),
            'company_name'         => $get_field( 'company_name' ),
            'company_profession'   => $get_field( 'company_profession' ),
            'tax_office'           => $get_field( 'tax_office' ),
        ] );

        $addressArgs = array_merge( $addressArgs, [
            'receipt_type' => 'required',
        ] );

        if ( $data['receipt_type'] === 'invoice' ) {
            $country = strtoupper( $data['country'] ?: 'GR' );

            $addressArgs['tax_number'] = 'required';
            $addressArgs['company_name'] = 'required';
            $addressArgs['state'] = '';

            if ( $country === 'GR' ) {
                $tax_number = preg_replace( '/\D+/', '', $data['tax_number'] );
                if ( empty( $tax_number ) ) {
                    $send_error( __( 'Το ΑΦΜ είναι υποχρεωτικό για τιμολόγιο Ελλάδας.', 'iw-theme' ) );
                }

                if ( ! class_exists( 'IW_Taxisnet_Integration' ) || ! IW_Taxisnet_Integration::validate_afm( $tax_number ) ) {
                    $send_error( __( 'Το ΑΦΜ δεν είναι έγκυρο.', 'iw-theme' ) );
                }

                $aade_data = class_exists( 'IW_WC_Customizations' ) ? IW_WC_Customizations::get_verified_aade_data( $tax_number ) : [];
                if ( empty( $aade_data ) ) {
                    $send_error( __( 'Παρακαλούμε πατήστε το βελάκι για επαλήθευση του ΑΦΜ από την ΑΑΔΕ πριν αποθηκεύσετε.', 'iw-theme' ) );
                }

                foreach ( IW_Taxisnet_Integration::get_billing_fields_from_company_info( $aade_data ) as $field_key => $value ) {
                    $data[ str_replace( 'billing_', '', $field_key ) ] = sanitize_text_field( $value );
                }

                $addressArgs['company_profession'] = 'required';
                $addressArgs['tax_office'] = 'required';
                $tax_validation_source = 'aade';
            } elseif ( class_exists( 'IW_WC_Customizations' ) && IW_WC_Customizations::is_eu_vat_country( $country ) ) {
                $tax_number = class_exists( 'IW_Taxisnet_Integration' ) ? IW_Taxisnet_Integration::normalize_vat_number( $country, $data['tax_number'] ) : $data['tax_number'];
                if ( empty( $tax_number ) ) {
                    $send_error( __( 'Το VAT number είναι υποχρεωτικό για τιμολόγιο εντός Ευρωπαϊκής Ένωσης.', 'iw-theme' ) );
                }

                $vies_data = IW_WC_Customizations::get_verified_vies_data( $country, $data['tax_number'] );
                if ( empty( $vies_data ) ) {
                    $send_error( __( 'Παρακαλούμε πατήστε το βελάκι για επαλήθευση του VAT number από το VIES πριν αποθηκεύσετε.', 'iw-theme' ) );
                }

                foreach ( IW_Taxisnet_Integration::get_billing_fields_from_vies_info( $vies_data ) as $field_key => $value ) {
                    if ( $value !== '' ) {
                        $data[ str_replace( 'billing_', '', $field_key ) ] = sanitize_text_field( $value );
                    }
                }

                $tax_validation_source = 'vies';
            } else {
                $tax_validation_source = 'manual_non_eu';
            }
        }
    }

    $field_labels = [
        'first_name'         => __( 'First name', 'woocommerce' ),
        'last_name'          => __( 'Last name', 'woocommerce' ),
        'country'            => __( 'Country / Region', 'woocommerce' ),
        'address_1'          => __( 'Street address', 'woocommerce' ),
        'city'               => __( 'Town / City', 'woocommerce' ),
        'state'              => __( 'State / County', 'woocommerce' ),
        'postcode'           => __( 'Postcode / ZIP', 'woocommerce' ),
        'email'              => __( 'Email address', 'woocommerce' ),
        'receipt_type'       => __( 'Είδος Παραστατικού', 'iw-theme' ),
        'tax_number'         => __( 'Α.Φ.Μ.', 'iw-theme' ),
        'company_name'       => __( 'Επωνυμία Επιχείρησης', 'iw-theme' ),
        'company_profession' => __( 'Δραστηριότητα Επιχείρησης', 'iw-theme' ),
        'tax_office'         => __( 'Δ.Ο.Υ.', 'iw-theme' ),
    ];

    $address = [];
    foreach ( $addressArgs as $key => $rules ) {
        $value = $data[ $key ] ?? '';
        $rule_parts = array_filter( explode( '|', $rules ) );
        $label = $field_labels[ $key ] ?? $key;

        if ( in_array( 'required', $rule_parts, true ) && trim( (string) $value ) === '' ) {
            $send_error( sprintf( __( 'Το πεδίο "%s" είναι υποχρεωτικό.', 'iw-theme' ), $label ) );
        }

        if ( in_array( 'email', $rule_parts, true ) && trim( (string) $value ) !== '' && ! is_email( $value ) ) {
            $send_error( __( 'Το email δεν είναι έγκυρο.', 'iw-theme' ) );
        }

        $address[ $key ] = $value;
    }

    foreach ( $address as $key => $value ) {
        $method = "set_{$addressType}_{$key}";
        $meta_key = "{$addressType}_{$key}";
        if ( method_exists( $customer, $method ) ) {
            $customer->{$method}( $value );
        } else {
            $customer->update_meta_data( $meta_key, $value );
        }

        if ( function_exists( 'WC' ) && WC()->session ) {
            WC()->session->set( $meta_key, $value );
        }
        if ( is_user_logged_in() ) {
            update_user_meta( get_current_user_id(), $meta_key, $value );
        }
    }

    if ( $addressType === 'billing' ) {
        if ( ! empty( $tax_validation_source ) ) {
            $customer->update_meta_data( 'billing_tax_validation_source', $tax_validation_source );
            if ( function_exists( 'WC' ) && WC()->session ) {
                WC()->session->set( 'billing_tax_validation_source', $tax_validation_source );
            }
            if ( is_user_logged_in() ) {
                update_user_meta( get_current_user_id(), 'billing_tax_validation_source', $tax_validation_source );
            }
        }

        if ( $tax_validation_source === 'aade' ) {
            $verified_at = $aade_data['verified_at'] ?? current_time( 'mysql' );
            $customer->update_meta_data( 'billing_aade_verified_at', $verified_at );
            $customer->update_meta_data( 'billing_aade_snapshot', $aade_data );
            if ( function_exists( 'WC' ) && WC()->session ) {
                WC()->session->set( 'billing_aade_verified_at', $verified_at );
                WC()->session->set( 'billing_aade_snapshot', $aade_data );
            }
            if ( is_user_logged_in() ) {
                update_user_meta( get_current_user_id(), 'billing_aade_verified_at', $verified_at );
                update_user_meta( get_current_user_id(), 'billing_aade_snapshot', $aade_data );
            }
            $customer->delete_meta_data( 'billing_vies_verified_at' );
            $customer->delete_meta_data( 'billing_vies_request_identifier' );
            $customer->delete_meta_data( 'billing_vies_snapshot' );
            if ( is_user_logged_in() ) {
                delete_user_meta( get_current_user_id(), 'billing_vies_verified_at' );
                delete_user_meta( get_current_user_id(), 'billing_vies_request_identifier' );
                delete_user_meta( get_current_user_id(), 'billing_vies_snapshot' );
            }
        } elseif ( $tax_validation_source === 'vies' ) {
            $verified_at = $vies_data['verified_at'] ?? current_time( 'mysql' );
            $customer->update_meta_data( 'billing_vies_verified_at', $verified_at );
            $customer->update_meta_data( 'billing_vies_request_identifier', $vies_data['request_identifier'] ?? '' );
            $customer->update_meta_data( 'billing_vies_snapshot', $vies_data );
            if ( function_exists( 'WC' ) && WC()->session ) {
                WC()->session->set( 'billing_vies_verified_at', $verified_at );
                WC()->session->set( 'billing_vies_request_identifier', $vies_data['request_identifier'] ?? '' );
                WC()->session->set( 'billing_vies_snapshot', $vies_data );
            }
            if ( is_user_logged_in() ) {
                update_user_meta( get_current_user_id(), 'billing_vies_verified_at', $verified_at );
                update_user_meta( get_current_user_id(), 'billing_vies_request_identifier', $vies_data['request_identifier'] ?? '' );
                update_user_meta( get_current_user_id(), 'billing_vies_snapshot', $vies_data );
            }
            $customer->delete_meta_data( 'billing_aade_verified_at' );
            $customer->delete_meta_data( 'billing_aade_snapshot' );
            if ( is_user_logged_in() ) {
                delete_user_meta( get_current_user_id(), 'billing_aade_verified_at' );
                delete_user_meta( get_current_user_id(), 'billing_aade_snapshot' );
            }
        } elseif ( ! empty( $tax_validation_source ) ) {
            $customer->delete_meta_data( 'billing_aade_verified_at' );
            $customer->delete_meta_data( 'billing_aade_snapshot' );
            $customer->delete_meta_data( 'billing_vies_verified_at' );
            $customer->delete_meta_data( 'billing_vies_request_identifier' );
            $customer->delete_meta_data( 'billing_vies_snapshot' );
            if ( is_user_logged_in() ) {
                delete_user_meta( get_current_user_id(), 'billing_aade_verified_at' );
                delete_user_meta( get_current_user_id(), 'billing_aade_snapshot' );
                delete_user_meta( get_current_user_id(), 'billing_vies_verified_at' );
                delete_user_meta( get_current_user_id(), 'billing_vies_request_identifier' );
                delete_user_meta( get_current_user_id(), 'billing_vies_snapshot' );
            }
        }
    }

    $customer->save();

    // WC()->customer->set_shipping_location( $address['country'], $address['state'], $address['postcode'], $address['city']);
    // WC()->cart->calculate_shipping();
    // WC()->cart->calculate_totals();

    $success_message = $addressType === 'billing'
        ? __( 'Η διεύθυνση χρέωσης ενημερώθηκε', 'iw-theme' )
        : __( 'Η διεύθυνση αποστολής ενημερώθηκε', 'iw-theme' );

    wp_send_json_success( [ 'message' => $success_message, 'reload' => false, 'fields' => $address ] );

} );
