<?php

namespace WCML\MultiCurrency\ExchangeRateServices;

class CurrencyLayer extends ApiLayerService {

	public function getId() {
		return 'currencylayer';
	}

	public function getName() {
		return 'currencylayer';
	}

	public function getUrl() {
		return 'https://currencylayer.com/';
	}

	protected function getApiLayerUrl() {
		return 'https://api.apilayer.com/currency_data/live?source=%2$s&currencies=%3$s';
	}

	protected function getApiLegacyUrl() {
		return 'http://apilayer.net/api/live?access_key=%s&source=%s&currencies=%s&amount=1';
	}

	protected function isInvalidResponse( $decodedData ) {
		return empty( $decodedData->quotes );
	}

	protected function extractRates( $validData, $from, $tos ) {
		$rates = [];

		foreach ( $tos as $to ) {
			if ( isset( $validData->quotes->{$from . $to} ) ) {
				$rates[ $to ] = round( $validData->quotes->{$from . $to}, \WCML_Exchange_Rates::DIGITS_AFTER_DECIMAL_POINT );
			}
		}

		return $rates;
	}
}
