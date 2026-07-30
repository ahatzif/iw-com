<?php

namespace WCML\User\Store;


interface Strategy {

	public function get( $key );

	public function set( $key, $value );
}
