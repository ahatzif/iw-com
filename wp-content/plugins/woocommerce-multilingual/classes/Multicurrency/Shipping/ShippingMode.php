<?php

namespace WCML\Multicurrency\Shipping;

interface ShippingMode {
	public function getMethodId();

	public function getFieldTitle( $currencyCode ): ?string;

	public function getFieldDescription( $currencyCode ): ?string;

	public function getSettingsFormKey( $currencyCode );

	public function getMinimalOrderAmountValue( $amount, $shipping, $currency );

	public function getShippingCostValue( $rate, $currency );

	public function isManualPricingEnabled( $instance );

}