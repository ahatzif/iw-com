<?php

namespace WCML\User\Store;


class Store implements Strategy {

	public function get( $key ) {

		$key = $this->adjustKey( $key );

		return $this->getStrategy( $key )->get( $key );
	}

	public function set( $key, $value ) {

		$key = $this->adjustKey( $key );

		$this->getStrategy( $key )->set( $key, $value );
	}

	private function getStrategy( $key ) {
		global $woocommerce;

		switch ( apply_filters( 'wcml_user_store_strategy', 'wc-session', $key ) ) {
			case 'cookie':
				$store = \WPML\Container\make( Cookie::class );
				break;

			case 'wc-session':
			default:
				$store = isset( $woocommerce->session ) ? new WcSession( $woocommerce->session ) : new Noop();
		}

		return $store;
	}

	private function adjustKey( $key ) {

		$prefix = 'wcml_';

		return strpos( $key, $prefix ) === 0 ? $key : $prefix . $key;
	}

}
