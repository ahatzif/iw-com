<?php

if ( ! function_exists( 'wcml_is_multi_currency_on' ) ) {
	function wcml_is_multi_currency_on() {
		global $woocommerce_wpml;

		if ( is_null( $woocommerce_wpml ) ) {
			return false;
		}

		return WCML_MULTI_CURRENCIES_INDEPENDENT === (int) $woocommerce_wpml->settings['enable_multi_currency'];
	}
}

if ( ! function_exists( 'wcml_price_custom_fields' ) ) {
	function wcml_price_custom_fields( $object_id ) {
		$default_keys = [
			'_max_variation_price',
			'_max_variation_regular_price',
			'_max_variation_sale_price',
			'_min_variation_price',
			'_min_variation_regular_price',
			'_min_variation_sale_price',
			'_price',
			'_regular_price',
			'_sale_price',
		];

		$filtered_keys = apply_filters( 'wcml_price_custom_fields_filtered', $default_keys, $object_id );

		$filtered_keys = apply_filters( 'wcml_price_custom_fields', $filtered_keys, $object_id );

		if ( ! is_array( $filtered_keys ) ) {
			$filtered_keys = $default_keys;
		}

		return $filtered_keys;
	}
}


if ( ! function_exists( 'wcml_get_woocommerce_currency_option' ) ) {
	function wcml_get_woocommerce_currency_option() {
		return get_option( 'woocommerce_currency' );
	}
}

if ( ! function_exists( 'wcml_product_data_store_cpt' ) ) {
	function wcml_product_data_store_cpt() {
		return new WCML_Product_Data_Store_CPT();
	}
}

if ( ! function_exists( 'wcml_convert_price' ) ) {

	function wcml_convert_price( $price, $currency_code = false ) {
		global $woocommerce_wpml;

		return $woocommerce_wpml->multi_currency->prices->raw_price_filter( $price, $currency_code );
	}
}

if ( ! function_exists( 'wcml_safe_redirect' ) ) {

	function wcml_safe_redirect( $location, $status = 302 ) {
		return wp_safe_redirect( $location, $status, 'WCML' ) && exit;
	}
}

function wcml_user_store_get( $key ) {
	return \WPML\Container\make( WCML\User\Store\Store::class )->get( $key );
}

function wcml_user_store_set( $key, $value ) {
	\WPML\Container\make( WCML\User\Store\Store::class )->set( $key, $value );
}

function wcml_register_script( $handle, $src, $deps = [], $args = [] ) {
	global $wp_version;

	if ( version_compare( $wp_version,'6.3', '<' ) ) {
		$args = $args['in_footer'] ?? false;
	}

	return wp_register_script( $handle, WCML_PLUGIN_URL . '/' . $src, $deps, WCML_VERSION, $args );
}
