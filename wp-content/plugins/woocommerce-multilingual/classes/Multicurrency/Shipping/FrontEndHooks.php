<?php

namespace WCML\Multicurrency\Shipping;

use IWPML_Action;
use WCML\StandAlone\IStandAloneAction;

class FrontEndHooks implements IWPML_Action, IStandAloneAction {

	private $multiCurrency;

	public function __construct( $multiCurrency ) {
		$this->multiCurrency = $multiCurrency;
	}

	public function add_hooks() {
		ShippingModeProvider::getAll()->each( function( ShippingMode $shippingMode ) {
			add_filter(
				'woocommerce_shipping_' . $shippingMode->getMethodId() . '_instance_option',
				$this->getShippingCost( $shippingMode ),
				10,
				3
			);
		}
		);
	}

	public function getShippingCost( ShippingMode $shippingMode ) {
		return function( $rate, $key, $wcShippingMethod ) use ( $shippingMode ) {
			if ( $shippingMode->isManualPricingEnabled( $wcShippingMethod ) ) {
				if ( 'cost' === $key ) {
					$rate = $shippingMode->getShippingCostValue( $wcShippingMethod, $this->getClientCurrency() );
					do_action( 'wcml_shipping_cost_in_mc', $wcShippingMethod );
				} elseif ( $shippingMode instanceof ShippingClassesMode ) {
					if ( $this->isShippingClass( $key ) ) {
						$rate = $shippingMode->getShippingClassCostValue( $wcShippingMethod, $this->getClientCurrency(), $key );
					} elseif ( $this->isNoShippingClass( $key ) ) {
						$rate = $shippingMode->getNoShippingClassCostValue( $wcShippingMethod, $this->getClientCurrency() );
					}
				}
			}
			return $rate;
		};
	}

	private function isShippingClass( $key ) {
		return 'class_cost_' === substr( $key, 0, 11 );
	}

	private function isNoShippingClass( $key ) {
		return 'no_class_cost' === substr( $key, 0, 13 );
	}

	private function getClientCurrency() {
		return $this->adjustCurrencyOnWidgetChange( $this->multiCurrency->get_client_currency() );
	}

	private function adjustCurrencyOnWidgetChange( $currencyCode ) {
		$postData = wpml_collect( $_POST );
		$currencyCodeInRequest = $postData->get( 'currency' );
		if ( 'wcml_switch_currency' === $postData->get( 'action' )
		     && $currencyCodeInRequest
		     && $this->validateCurrencyCode( $currencyCodeInRequest ) ) {
			return $currencyCodeInRequest;
		}
		return $currencyCode;
	}

	private function validateCurrencyCode( $currencyCode ) {
		return in_array( $currencyCode, $this->multiCurrency->get_currency_codes() );
	}

}
