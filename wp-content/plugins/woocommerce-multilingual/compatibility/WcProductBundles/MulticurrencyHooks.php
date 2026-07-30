<?php

namespace WCML\Compatibility\WcProductBundles;

class MulticurrencyHooks implements \IWPML_Action {

	public function add_hooks() {
		add_filter( 'wcml_price_custom_fields_filtered', [ $this, 'getPriceCustomFields' ], 10 );
		add_filter( 'wcml_update_custom_prices_values', [ $this, 'updateBundlesCustomPricesValues' ], 10, 2 );
		add_action( 'wcml_after_save_custom_prices', [ $this, 'updateBundlesBasePrice' ], 10, 4 );
	}

	public function getPriceCustomFields( $customFields ) {
		return array_merge(
			$customFields,
			[
				'_wc_pb_base_regular_price',
				'_wc_pb_base_sale_price',
				'_wc_pb_base_price',
				'_wc_sw_max_price',
				'_wc_sw_max_regular_price',
			]
		);
	}

	public function updateBundlesCustomPricesValues( $prices, $code ) {
		if ( isset( $_POST['_custom_regular_price'][ $code ] ) ) {
			$prices['_wc_pb_base_regular_price'] = wc_format_decimal( $_POST['_custom_regular_price'][ $code ] );
		}

		if ( isset( $_POST['_custom_sale_price'][ $code ] ) ) {
			$prices['_wc_pb_base_sale_price'] = wc_format_decimal( $_POST['_custom_sale_price'][ $code ] );
		}

		return $prices;
	}

	public function updateBundlesBasePrice( $postId, $productPrice, $customPrices, $code ) {
		if ( isset( $customPrices['_wc_pb_base_regular_price'] ) ) {
			update_post_meta( $postId, '_wc_pb_base_price_' . $code, $productPrice );
		}
	}
}
