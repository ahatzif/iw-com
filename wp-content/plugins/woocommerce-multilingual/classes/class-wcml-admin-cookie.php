<?php

class WCML_Admin_Cookie {

	private $name;

	public function __construct( $name ) {
		$this->name = $name;
	}

	public function set_value( $value, $expiration = null ) {
		if ( null === $expiration ) {
			$expiration = time() + DAY_IN_SECONDS;
		}
		wc_setcookie( $this->name, $value, $expiration );
	}

	public function get_value() {
		$value = null;
		if ( isset( $_COOKIE [ $this->name ] ) ) {
			$value = $_COOKIE[ $this->name ];
		}
		return $value;
	}
}
