<?php

namespace WCML\Rest\Wrapper;

class Composite extends Handler {

	private $restHandlers;

	public function __construct( array $restHandlers ) {
		$this->restHandlers = $restHandlers;
	}

	public function query( $args, $request ) {
		foreach ( $this->restHandlers as $restHandler ) {
			$args = $restHandler->query( $args, $request );
		}

		return $args;
	}

	public function prepare( $response, $object, $request ) {
		foreach ( $this->restHandlers as $restHandler ) {
			$response = $restHandler->prepare( $response, $object, $request );
		}

		return $response;
	}

	public function insert( $object, $request, $creating ) {
		foreach ( $this->restHandlers as $restHandler ) {
			$restHandler->insert( $object, $request, $creating );
		}
	}

}