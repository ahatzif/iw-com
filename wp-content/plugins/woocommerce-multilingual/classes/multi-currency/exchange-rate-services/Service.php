<?php

namespace WCML\MultiCurrency\ExchangeRateServices;

use WPML\FP\Obj;

abstract class Service {

	private $settings;

	abstract public function getId();

	abstract public function getName();

	abstract public function getUrl();

	abstract public function getApiUrl();

	abstract public function isKeyRequired();

	public function resetConnectionCache() {

	}

	public function getRates( $from, $tos ) {
		$this->clearLastError();

		$response = $this->makeRequest( $from, $tos );

		if ( is_wp_error( $response ) ) {
			$http_error = implode( "\n", $response->get_error_messages() );
			$this->saveLastError( $http_error );
			throw new \Exception( $http_error );
		}

		$data = json_decode( $response['body'] );

		if ( $this->isInvalidResponse( $data ) ) {
			$error = self::get_formatted_error( $data );
			$this->saveLastError( $error );
			throw new \Exception( $error );
		}

		return $this->extractRates( $data, $from, $tos );
	}

	protected function makeRequest( $from, $tos ) {
		if ( $this->isKeyRequired() ) {
			$url = sprintf( $this->getApiUrl(), $this->getApiKey(), $from, implode( ',', $tos ) );
		} else {
			$url = sprintf( $this->getApiUrl(), $from, implode( ',', $tos ) );
		}

		return wp_safe_remote_get( $url, [ 'headers' => $this->getRequestHeaders() ] );
	}

	protected function getRequestHeaders() {
		return [];
	}

	protected function isInvalidResponse( $decodedData ) {
		return empty( $decodedData->rates );
	}

	protected function extractRates( $validData, $from, $tos ) {
		$rates = [];

		foreach ( $validData->rates as $to => $rate ) {
			$rates[ $to ] = round( $rate, \WCML_Exchange_Rates::DIGITS_AFTER_DECIMAL_POINT );
		}

		return $rates;
	}

	public static function get_formatted_error( $response ) {
		$getFromPath = function( $path ) use ( $response ) {
			try {
				$value = Obj::path( $path, $response );
				return is_string( $value ) || is_int( $value ) ? $value : null;
			} catch ( \Exception $e ) {
				return null;
			}
		};

		$formattedError = wpml_collect( [
			'error'         => $getFromPath( [ 'error' ] ),
			'error_code'    => $getFromPath( [ 'error', 'code' ] ),
			'error_type'    => $getFromPath( [ 'error', 'type' ] ),
			'error_info'    => $getFromPath( [ 'error', 'info' ] ),
			'error_message' => $getFromPath( [ 'error', 'message' ] ),
			'message'       => $getFromPath( [ 'message' ] ),
			'description'   => $getFromPath( [ 'description' ] ),
		] )->filter()
		   ->map( function( $value, $key ) {
			   return "$key: $value";
		   } )
		   ->implode( ' - ' );

		return $formattedError
			? strip_tags( $formattedError )
			: esc_html__( 'Cannot get exchange rates. Connection failed.', 'woocommerce-multilingual' );
	}

	public function getSettings() {
		if ( null === $this->settings ) {
			$this->settings = get_option( 'wcml_exchange_rate_service_' . $this->getId(), [] );
		}

		return $this->settings;
	}

	private function saveSettings() {
		update_option( 'wcml_exchange_rate_service_' . $this->getId(), $this->getSettings() );
	}

	public function getSetting( $key ) {
		return Obj::prop( $key, $this->getSettings() );
	}

	public function saveSetting( $key, $value ) {
		$this->getSettings();
		$this->settings[ $key ] = $value;
		$this->saveSettings();
	}

	public function saveLastError( $error_message ) {
		$this->saveSetting(
			'last_error',
			[
				'text' => $error_message,
				'time' => date_i18n( 'F j, Y g:i a', false, true ),
			]
		);
	}

	public function clearLastError() {
		$this->saveSetting( 'last_error', false );
	}

	public function getLastError() {
		return $this->getSetting( 'last_error' );
	}

	protected function getApiKey() {
		return $this->getSetting( 'api-key' );
	}
}
