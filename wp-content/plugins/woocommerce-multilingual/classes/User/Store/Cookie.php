<?php

namespace WCML\User\Store;


class Cookie implements Strategy {

	public $cookieHandler;


	public function __construct( \WPML_Cookie $cookieHandler ) {
		$this->cookieHandler = $cookieHandler;
	}

	public function get( $key ) {

		return $this->cookieHandler->get_cookie( $key );
	}

	public function set( $key, $value ) {

		if ( ! $this->cookieHandler->headers_sent() ) {

			$expiration = time() + (int) apply_filters( 'wcml_cookie_expiration', 48 * HOUR_IN_SECONDS, $key );

			$this->cookieHandler->set_cookie( $key, $value, $expiration, COOKIEPATH, COOKIE_DOMAIN );
		}
	}
}
