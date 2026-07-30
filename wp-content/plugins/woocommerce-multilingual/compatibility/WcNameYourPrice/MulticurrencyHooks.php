<?php

namespace WCML\Compatibility\WcNameYourPrice;

use WC_Name_Your_Price_Compatibility;

use function WPML\Container\make;
use function WCML\functions\getClientCurrency;

class MulticurrencyHooks implements \IWPML_Action {

	public function add_hooks() {
		if ( ! is_admin() ) {
			if ( is_callable( [ 'WC_Name_Your_Price_Compatibility', 'is_nyp_gte' ] ) && WC_Name_Your_Price_Compatibility::is_nyp_gte( '3.0' ) ) {
				add_filter( 'wc_nyp_raw_suggested_price', [ $this, 'product_price_filter' ] );
				add_filter( 'wc_nyp_raw_minimum_price', [ $this, 'product_price_filter' ] );
				add_filter( 'wc_nyp_raw_maximum_price', [ $this, 'product_price_filter' ] );
			} else {
				add_filter( 'woocommerce_raw_suggested_price', [ $this, 'product_price_filter' ] );
				add_filter( 'woocommerce_raw_minimum_price', [ $this, 'product_price_filter' ] );
				add_filter( 'woocommerce_raw_maximum_price', [ $this, 'product_price_filter' ] );
			}
		}

		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_initial_currency' ] );
		add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'filter_woocommerce_get_cart_item_from_session' ], 20, 2 );

		add_filter( 'wc_nyp_edit_in_cart_args', [ $this, 'edit_in_cart_args' ], 10 );
		add_filter( 'wc_nyp_get_initial_price', [ $this, 'get_initial_price' ], 10, 3 );
	}

	public function product_price_filter( $price, $currency = false ) {
		return apply_filters( 'wcml_raw_price_amount', $price, $currency );
	}

	public function add_initial_currency( $cart_item_data ) {

		if ( isset( $cart_item_data['nyp'] ) ) {
			$cart_item_data['nyp_currency'] = get_woocommerce_currency();
			$cart_item_data['nyp_original'] = $cart_item_data['nyp'];
		}

		return $cart_item_data;
	}

	public function filter_woocommerce_get_cart_item_from_session( $session_data, $values ) {

		if ( isset( $values['nyp_currency'] ) ) {
			$session_data['nyp_currency'] = $values['nyp_currency'];
		}

		if ( isset( $values['nyp_original'] ) ) {
			$session_data['nyp_original'] = $values['nyp_original'];
		}

		$current_currency = getClientCurrency();

		if ( isset( $session_data['nyp_currency'] ) && $session_data['nyp_currency'] !== $current_currency ) {

			$product = $session_data['data'];

			$price_in_current_currency = $this->product_price_filter( $session_data['nyp'], $current_currency );

			$product->set_price( $price_in_current_currency );
			$product->set_regular_price( $price_in_current_currency );
			$product->set_sale_price( $price_in_current_currency );

			if ( $product->is_type( [ 'subscription', 'subscription_variation' ] ) ) {
				$product->update_meta_data( '_subscription_price', $price_in_current_currency );
			}
		}

		return $session_data;
	}

	public function edit_in_cart_args( $args ) {
		$args['nyp_currency'] = get_woocommerce_currency();
		return $args;
	}

	public function get_initial_price( $initial_price, $product, $suffix ) {

		if ( isset( $_REQUEST[ 'nyp_raw' . $suffix ] ) && isset( $_REQUEST[ 'nyp_currency' ] ) ) {
			$from_currency = wc_clean( $_REQUEST[ 'nyp_currency' ] );
			$current_currency = get_woocommerce_currency();
			if ( $from_currency !== $current_currency ) {
				$raw_price = wc_clean( $_REQUEST[ 'nyp_raw' . $suffix ] );

				$multi_currency = make( \WCML_Multi_Currency::class );
				$initial_price = $multi_currency->prices->convert_price_amount_by_currencies( $raw_price, $from_currency, $current_currency );
			}
		}
		
		return $initial_price;
	}  

}
