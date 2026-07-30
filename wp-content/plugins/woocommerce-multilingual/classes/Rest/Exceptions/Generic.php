<?php

namespace WCML\Rest\Exceptions;

use WC_REST_Exception;

class Generic extends WC_REST_Exception {

	public function __construct( $message ) {
		parent::__construct( "422", $message, 422 );
	}

}