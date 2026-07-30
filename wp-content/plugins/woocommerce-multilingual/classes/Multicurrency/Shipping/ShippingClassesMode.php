<?php

namespace WCML\Multicurrency\Shipping;

interface ShippingClassesMode extends ShippingMode {
	public function getShippingClassCostValue( $rate, $currency, $shippingClassKey );

	public function getNoShippingClassCostValue( $rate, $currency );
}
