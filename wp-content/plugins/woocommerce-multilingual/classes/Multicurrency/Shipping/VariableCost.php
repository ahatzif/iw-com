<?php

namespace WCML\Multicurrency\Shipping;

trait VariableCost {
	use DefaultConversion;

	public function getFieldTitle( $currencyCode ): ?string {
		/* translators: %s is a currency code */
		return sprintf( esc_html_x( 'Cost in %s',
			'The label for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	public function getFieldDescription( $currencyCode ): ?string {
		/* translators: %s is a currency code */
		return sprintf( esc_html_x( 'The shipping cost if customer choose %s as a purchase currency.',
			'The description for the field with shipping cost in additional currency. The currency symbol will be added in place of %s specifier.',
			'woocommerce-multilingual' ), $currencyCode );
	}

	private function getCostKey( $currencyCode ) {
		return sprintf( 'cost_%s', $currencyCode );
	}

	private function getShippingClassCostKey( $shippingClassKey, $currency ) {
		return $this->replaceShippingClassId( $shippingClassKey ) . '_' . $currency;
	}

	private function getNoShippingClassCostKey( $currency ): string {
		return 'no_class_cost_' . $currency;
	}

	public function getSettingsFormKey( $currencyCode ) {
		return $this->getCostKey( $currencyCode );
	}

	public function getMinimalOrderAmountValue( $amount, $shipping, $currency ) {
		return $amount;
	}

	public function getShippingCostValue( $rate, $currency ) {
		$costName = $this->getCostKey( $currency );
		return $this->getCostValueForName( $rate, $currency, $costName, 'cost' );
	}

	public function getShippingClassCostValue( $rate, $currency, $shippingClassKey ) {
		$costName = $this->getShippingClassCostKey( $shippingClassKey, $currency );
		return $this->getCostValueForName( $rate, $currency, $costName, $shippingClassKey );
	}

	public function getNoShippingClassCostValue( $rate, $currency ) {
		$costName = $this->getNoShippingClassCostKey( $currency );
		return $this->getCostValueForName( $rate, $currency, $costName, 'no_class_cost' );
	}

	private function getCostValueForName( $rate, $currency, $costName, $rateField ) {
		if ( ! isset( $rate->$rateField ) ) {
			@$rate->$rateField = 0;
		}
		if ( ! empty( $rate->instance_id ) ) {
			if ( $this->isManualPricingEnabled( $rate ) ) {
				$rateSettings = $this->getWpOption( $this->getMethodId(), $rate->instance_id );
				if ( ! empty( $rateSettings[ $costName ] ) ) {
					$rate->$rateField = $rateSettings[ $costName ];
				} else {
					$rate->$rateField = $this->getValueFromDefaultCurrency( $rate->$rateField, $rateSettings, $costName, $currency );
				}
			}
		}
		return $rate->$rateField;
	}

	public function isManualPricingEnabled( $instance ) {
		return self::isEnabled( $this->getWpOption( $this->getMethodId(), $instance->instance_id ) );
	}

	private function getWpOption( $methodId, $instanceId ) {
		return get_option( \WCML_Multi_Currency_Shipping::getShippingOptionName( $methodId, $instanceId ) );
	}

	private function getShippingClassTermId( $key ) {
		if ( preg_match( '/^class_cost_(\d*)(_[A-Z]*)*$/', $key, $matches ) && isset( $matches[1] ) ) {
			return $matches[1];
		}
		return false;
	}
	private function replaceShippingClassId( $shippingClassKey ) {
		$termId         = $this->getShippingClassTermId( $shippingClassKey );
		if ( $termId ) {
			$termTrid = apply_filters( 'wpml_element_trid', null, $termId, 'tax_product_shipping_class' );
			$termTranslations = apply_filters( 'wpml_get_element_translations', null, $termTrid, 'tax_product_shipping_class' );
			if ( is_array( $termTranslations ) ) {
				foreach ( $termTranslations as $translation ) {
					if ( $translation->source_language_code === null ) {
						$shippingClassKey = str_replace( $termId, $translation->element_id, $shippingClassKey );
						break;
					}
				}
			}
		}
		return $shippingClassKey;
	}


	public function _testGetShippingClassTermId( $key ) {
		if ( ! isset( $_SERVER['SCRIPT_NAME'] ) || stristr( $_SERVER['SCRIPT_NAME'], 'phpunit' ) === false ) {
			die( "don't run this method directly outside phpunit env" );
		}
		return $this->getShippingClassTermId( $key );
	}
}
