<?php

namespace WCML\Multicurrency\Shipping;

trait DefaultConversion {
	public function getValueFromDefaultCurrency( $cost, $rateSettings, $costName, $currencyCode ) {
		if ( preg_match( '/(.*)_' . $currencyCode . '$/', $costName, $matches ) ) {
			$defaultCostName = $matches[1];
			if ( ! empty( $rateSettings[ $defaultCostName ] ) ) {
				$cost = wcml_convert_price( $rateSettings[ $defaultCostName ], $currencyCode );
			}
		}
		return $cost;
	}
}
