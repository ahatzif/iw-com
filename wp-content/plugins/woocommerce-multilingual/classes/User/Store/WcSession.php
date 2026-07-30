<?php

namespace WCML\User\Store;


class WcSession implements Strategy {

	private $session;

	public function __construct( \WC_Session $session ) {

		$this->session = $session;
	}

	public function get( $key ) {

		return $this->session->get( $key );
	}

	public function set( $key, $value ) {

		$this->session->set( $key, $value );
	}
}
