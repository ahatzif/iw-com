<?php

namespace WCML\Rest\Wrapper;


class Handler {

	public function query( $args, $request ) {
		return $args;
	}

	public function prepare( $response, $object, $request ) {
		return $response;
	}

	public function insert( $object, $request, $creating ) {

	}

}