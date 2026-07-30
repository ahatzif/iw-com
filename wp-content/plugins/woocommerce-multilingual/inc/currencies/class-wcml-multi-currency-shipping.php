<?php

use WCML\Multicurrency\Shipping\ShippingModeProvider as ManualCost;

class WCML_Multi_Currency_Shipping {

	const CACHE_PERSISTENT_GROUP = 'converted_shipping_cost';

	private $multi_currency;
	private $wpdb;

	const PRIORITY_SHIPPING = 10;

	private $wcShippingCostInMcList = [];

	public function __construct( WCML_Multi_Currency $multi_currency, wpdb $wpdb ) {

		$this->multi_currency = $multi_currency;
		$this->wpdb           = $wpdb;
		wp_cache_add_non_persistent_groups( self::CACHE_PERSISTENT_GROUP );
	}

	public function add_hooks() {

		$rates = $this->wpdb->get_results( "SELECT * FROM {$this->wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id IN ('flat_rate', 'local_pickup', 'free_shipping')" );
		foreach ( $rates as $method ) {
			$option_name = self::getShippingOptionName( $method->method_id, $method->instance_id );
			add_filter( 'option_' . $option_name, [ $this, 'convert_shipping_method_cost_settings' ] );
		}

		add_action( 'wcml_shipping_cost_in_mc', [ $this, 'action_shipping_cost_in_mc' ] );
		add_filter( 'wcml_shipping_price_amount', [ $this, 'shipping_price_filter' ] );
		add_filter( 'wcml_shipping_free_min_amount', [ $this, 'shipping_free_min_amount' ], 10, 2 );

		add_filter( 'woocommerce_evaluate_shipping_cost_args', [ $this, 'woocommerce_evaluate_shipping_cost_args' ], 10, 3 );

		add_filter( 'woocommerce_shipping_packages', [ $this, 'convert_shipping_taxes' ], self::PRIORITY_SHIPPING );

		add_filter( 'woocommerce_shipping_packages', [
			$this,
			'applyShippingRoundingRules'
		], \WCML_Multi_Currency_Shipping::PRIORITY_SHIPPING + 1 );

		add_filter( 'woocommerce_package_rates', [ $this, 'convert_shipping_costs_in_package_rates' ] );
	}

	public function action_shipping_cost_in_mc( $wcShippingMethod ) {
		$this->wcShippingCostInMcList[] = $wcShippingMethod;
	}

	private function isManualPricingEnabledForThisRate( $rate ): bool {
		return ManualCost::get( $rate->method_id )->isManualPricingEnabled( $rate );
	}

	public function convert_shipping_costs_in_package_rates( $rates ) {

		$client_currency = $this->multi_currency->get_client_currency();

		foreach ( $rates as $rate_id => $rate ) {

			$cache_key                      = $rate_id;
			$cached_converted_shipping_cost = wp_cache_get( $cache_key, self::CACHE_PERSISTENT_GROUP );

			if ( $cached_converted_shipping_cost ) {
				$rate->cost = $cached_converted_shipping_cost;
			} elseif ( isset( $rate->cost ) && $rate->cost ) {
				if ( ! $this->isManualPricingEnabledForThisRate( $rate ) ) {
					$rate->cost = $this->multi_currency->prices->raw_price_filter( $rate->cost, $client_currency );
				}
				wp_cache_set( $cache_key, $rate->cost, self::CACHE_PERSISTENT_GROUP );
			}
		}

		return $rates;
	}

	public function convert_shipping_method_cost_settings( $settings ) {

		$has_free_shipping_coupon = false;
		if ( null !== WC()->cart && $coupons = WC()->cart->get_coupons() ) {
			foreach ( $coupons as $coupon ) {

				if (
					$coupon->is_valid() &&
					(
						method_exists( $coupon, 'get_free_shipping' ) ?
							$coupon->get_free_shipping() :
							$coupon->enable_free_shipping()
					)
				) {
					$has_free_shipping_coupon = true;
				}
			}
		}

		if ( ! empty( $settings['requires'] ) ) {

			if (
				$settings['requires'] === 'min_amount' ||
				$settings['requires'] === 'either' ||
				( $settings['requires'] === 'both' && $has_free_shipping_coupon )
			) {
				$settings['min_amount'] = apply_filters( 'wcml_shipping_free_min_amount', $settings['min_amount'], $settings );
			}
		}

		return $settings;
	}

	public function woocommerce_evaluate_shipping_cost_args( $args, $sum, $WC_Shipping_Method ) {
		if ( in_array( $WC_Shipping_Method, $this->wcShippingCostInMcList, true ) ) {
			return $args;
		}

		$args['cost'] = $this->multi_currency->prices->unconvert_price_amount( $args['cost'] );

		return $args;
	}

	public function convert_shipping_taxes( $packages ) {
		if ( wc_tax_enabled() ) {
			foreach ( $packages as $package_id => $package ) {
				if ( isset( $package['rates'] ) ) {
					foreach ( $package['rates'] as $rate_id => $rate ) {
						if ( $rate->get_shipping_tax() > 0 ) {
							$packages[ $package_id ]['rates'][ $rate_id ]->taxes =
								WC_Tax::calc_shipping_tax( $packages[ $package_id ]['rates'][ $rate_id ]->cost, WC_Tax::get_shipping_tax_rates() );
						}
					}
				}
			}
		}

		return $packages;
	}

	public function applyShippingRoundingRules( $packages ) {
		foreach ( $packages as $packageKey => $package ) {
			foreach ( $package['rates'] as $rateKey => $rate ) {
				$packages[ $packageKey ]['rates'][ $rateKey ] = $this->roundingShippingCostIncludingTax( $rate );
			}
		}

		return $packages;
	}

	public function shipping_price_filter( $price ) {

		$price = $this->multi_currency->prices->raw_price_filter( $price, $this->multi_currency->get_client_currency() );

		return $price;

	}

	public function shipping_free_min_amount( $price, $settings ) {
		if ( ManualCost::get( 'free_shipping' )->isManualPricingEnabled( $settings ) ) {
			$price = ManualCost::get( 'free_shipping' )->getMinimalOrderAmountValue( $price, $settings, $this->multi_currency->get_client_currency() );
		} else {
			$price = $this->multi_currency->prices->raw_price_filter( $price, $this->multi_currency->get_client_currency() );
		}
		return $price;

	}

	public static function getShippingOptionName( $methodId, $instanceId ) {
		return sprintf( 'woocommerce_%s_%d_settings', $methodId, $instanceId );
	}

	private function is_cart_prices_exclude_tax(): bool {
		return ! wc_tax_enabled() || 'excl' === get_option( 'woocommerce_tax_display_cart' );
	}

	private function is_cart_prices_include_tax(): bool {
		return wc_tax_enabled() || 'incl' === get_option( 'woocommerce_tax_display_cart' );
	}

	private function roundingShippingCostIncludingTax( $shippingRate ) {
		$shippingCosts = floatval( $shippingRate->get_cost() );
		$shippingTaxes = floatval( $shippingRate->get_shipping_tax() );

		if ( 0.0 === $shippingCosts ) {
			return $shippingRate;
		}

		if ( $this->is_cart_prices_exclude_tax() ) {
			$priceWithRounding = $this->applyRoundingRules( $shippingCosts );

			$shippingRate->set_cost( $priceWithRounding );
			if ( $shippingTaxes > 0 ) {
				$shippingRate->set_taxes( $this->calculateTaxForCost( $priceWithRounding ) );
			}

			return $shippingRate;
		}

		if ( $this->is_cart_prices_include_tax() ) {
			$priceStandard     = $shippingCosts + $shippingTaxes;
			$priceWithRounding = $this->applyRoundingRules( $priceStandard );

			if ( $priceWithRounding !== $priceStandard ) {
				$tax     = $shippingTaxes / $shippingCosts;
				$newCost = $priceWithRounding / ( 1 + $tax );

				$shippingRate->set_cost( $newCost );
				if ( $shippingTaxes > 0 ) {
					$shippingRate->set_taxes( $this->calculateTaxForCost( $newCost ) );
				}

				return $shippingRate;
			}
		}

		return $shippingRate;
	}

	private function applyRoundingRules( $price ) {
		return $this->multi_currency->prices->apply_rounding_rules( $price );
	}

	private function calculateTaxForCost( $price ): array {
		if ( ! wc_tax_enabled() ) {
			return [];
		}

		return \WC_Tax::calc_shipping_tax( $price, \WC_Tax::get_shipping_tax_rates() );
	}
}
