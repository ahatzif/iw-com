<?php

namespace WCML\MultiCurrency\ExchangeRateServices;

class ExchangeRatesApi extends ApiLayerService {

	public function getId() {
		return 'exchangeratesapi';
	}

	public function getName() {
		return 'Exchange rates API';
	}

	public function getUrl() {
		return 'https://exchangeratesapi.io/';
	}

	protected function getApiLayerUrl() {
		return 'https://api.apilayer.com/exchangerates_data/latest?base=%2$s&symbols=%3$s&amount=1';
	}

	protected function getApiLegacyUrl() {
		return 'http://api.exchangeratesapi.io/v1/latest?access_key=%1$s&base=%2$s&symbols=%3$s';
	}
}
