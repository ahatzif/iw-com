<?php

namespace WCML\Rest\Store;

use WCML\Rest\Functions;
use WPML\FP\Obj;

use function WPML\FP\partialRight;

class PriceRangeHooks implements \IWPML_Action {

	private $woocommerce_wpml;

	public function __construct( $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function add_hooks() {
		add_filter( 'rest_request_after_callbacks', [ $this, 'convertPriceRange' ], 10, 3 );
	}

	public function convertPriceRange( $response, $handler, $request ) {
		if (
			\WP_REST_Server::READABLE === $request->get_method()
			&& 'products/collection-data' === Functions::getStoreStrippedEndpoint( $request )
			&& $request->get_param( 'calculate_price_range' )
		) {
			$mc = $this->woocommerce_wpml->multi_currency;

			$fromCurrency = $mc->get_default_currency();
			$toCurrency   = $mc->get_client_currency();
			if ( $fromCurrency !== $toCurrency ) {
				$data = $response->get_data();
				if ( ! empty( $data['price_range'] ) ) {
					$convert = partialRight( [ $mc->prices, 'convert_price_amount' ], $toCurrency );
					$data    = Obj::over( Obj::lensPath( [ 'price_range', 'min_price' ] ), $convert, $data );
					$data    = Obj::over( Obj::lensPath( [ 'price_range', 'max_price' ] ), $convert, $data );
					$response->set_data( $data );
				}

			}
		}

		return $response;
	}

}
