<?php

namespace WCML\Rest\Wrapper\Reports;

use WCML\Rest\Exceptions\InvalidLanguage;
use WCML\Rest\Wrapper\Handler;
use WPML\FP\Obj;

class ProductsSales extends Handler {

	public function prepare( $response, $object, $request ) {

		$currency = Obj::prop( 'currency', $request->get_params() );

		if ( $currency ) {

			$response->data['currency'] = $currency;
		}

		return $response;
	}

}