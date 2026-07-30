<?php

abstract class WCML_Payment_Gateway {

	const OPTION_KEY = 'wcml_payment_gateway_';

	protected $current_currency;

	protected $default_currency;

	protected $active_currencies;

	protected $gateway;

	private $settings = [];

	protected $woocommerce_wpml;

	public function __construct( WC_Payment_Gateway $gateway, woocommerce_wpml $woocommerce_wpml ) {
		$this->gateway          = $gateway;
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->settings         = get_option( self::OPTION_KEY . $this->get_id(), [] );
	}

	abstract public function get_output_model();

	public function get_gateway() {
		return $this->gateway;
	}

	public function get_id() {
		return $this->gateway->id;
	}

	public function get_title() {
		return $this->gateway->title;
	}

	public function get_settings() {
		return $this->settings;
	}

	private function save_settings() {
		update_option( self::OPTION_KEY . $this->get_id(), $this->settings );
	}

	public function get_setting( $currency ) {
		$setting = $this->settings[ $currency ] ?? null;

		return $this->set_currency( $setting, $currency );
	}

	private function set_currency( $setting, $currency ) {
		if ( is_array( $setting ) && empty( $setting['currency'] ) ) {
			$setting['currency'] = $currency;
		}

		return $setting;
	}

	public function save_setting( $key, $value ) {
		$this->settings[ $key ] = $value;
		$this->save_settings();
	}

	public function get_active_currencies() {

		$active_currencies = $this->active_currencies;

		if ( ! in_array( $this->current_currency, array_keys( $active_currencies ), true ) ) {
			$active_currencies[ $this->current_currency ] = [];
		}

		return $active_currencies;
	}

}
