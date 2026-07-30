<?php

namespace WCML\User\Store;


class Noop {

	public function get( $key ) {
		return null;
	}

	public function set( $key, $value ) {
	}
}