<?php

namespace WCML\Multicurrency\Shipping;

trait ShippingModeBase {
	public static function isEnabled( $rate_settings ) {
		return isset( $rate_settings[ AdminHooks::WCML_SHIPPING_COSTS ] ) && 'manual' === $rate_settings[ AdminHooks::WCML_SHIPPING_COSTS ];
	}
}
