<?php

namespace WCML\Compatibility\WoofWcProductFilter;

class MulticurrencyHooks implements \IWPML_Action {
	private $currentCurrency;
	private $defaultCurrency;
	private $rates;

	public function add_hooks() {
		add_action( 'init', [ $this, 'setupCurrencies' ] );
		add_filter( 'woof_get_meta_query', [ $this, 'priceInDefaultCurrency' ], 10, 1 );
		add_filter( 'wcml_exchange_rates', [ $this, 'storeExchangeRates' ], 10, 1 );
	}

	public function setupCurrencies() {
		$this->defaultCurrency = wcml_get_woocommerce_currency_option();
		$this->currentCurrency = apply_filters( 'wcml_price_currency', $this->defaultCurrency );
	}

	public function priceInDefaultCurrency( $metaQuery ) {
		if ( $this->priceIsInSwitchedCurrency() ) {
			foreach ( $metaQuery as $queryIndex => $queryMeta ) {
				if ( $this->isMetaWithPriceValues( $queryIndex, $queryMeta ) ) {
					foreach ( $queryMeta['value'] as $valueIndex => $valuePrice ) {
						$metaQuery[ $queryIndex ]['value'][ $valueIndex ] = $this->getPriceInDefaultCurrency( $valuePrice );
					}
				}
			}
		}
		return $metaQuery;
	}

	public function storeExchangeRates( $rates ) {
		$this->rates = $rates;
		return $rates;
	}

	private function priceIsInSwitchedCurrency() {
		return $this->currentCurrency !== $this->defaultCurrency;
	}

	private function isMetaWithPriceValues( $index, $meta ) {
		return is_numeric( $index )
			   && isset( $meta['key'], $meta['value'] )
			   && '_price' === $meta['key']
			   && is_array( $meta['value'] );
	}

	private function getPriceInDefaultCurrency( $valuePrice ) {
		if ( isset( $this->rates[ $this->currentCurrency ] ) ) {
			$valuePrice /= $this->rates[ $this->currentCurrency ];
		}
		return $valuePrice;
	}

}
