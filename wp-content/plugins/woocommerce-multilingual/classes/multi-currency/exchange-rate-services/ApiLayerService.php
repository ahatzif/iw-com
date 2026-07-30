<?php

namespace WCML\MultiCurrency\ExchangeRateServices;

use WPML\FP\Obj;

abstract class ApiLayerService extends Service {

	abstract protected function getApiLayerUrl();

	abstract protected function getApiLegacyUrl();

	public function getApiUrl() {
		return $this->getSelectedApiEndpoint();
	}

	public function resetConnectionCache() {
		$this->setSelectedApiEndpoint( null );
	}

	private function setSelectedApiEndpoint( $endpoint ) {
		$this->saveSetting( 'selected-endpoint', $endpoint );
	}

	private function getSelectedApiEndpoint() {
		return $this->getSetting( 'selected-endpoint' );
	}

	protected function makeRequest( $from, $tos ) {
		if ( $this->getSelectedApiEndpoint() ) {
			$response = parent::makeRequest( $from, $tos );
		} else {
			$this->setSelectedApiEndpoint( $this->getApiLayerUrl() );
			$response = parent::makeRequest( $from, $tos );

			if ( $this->isWrongAuthenticationWithApiLayer( $response ) ) {
				$this->setSelectedApiEndpoint( $this->getApiLegacyUrl() );
				$response = parent::makeRequest( $from, $tos );
			}
		}

		return $response;
	}

	private function isWrongAuthenticationWithApiLayer( $data ) {
		return Obj::path( [ 'response', 'code' ], $data ) === 401;
	}

	protected function getRequestHeaders() {
		return [ 'apikey' => $this->getApiKey() ];
	}

	public function isKeyRequired() {
		return true;
	}
}
