<?php

namespace WPML\Templates\PHP;

class Model {
	private $attributes = [];

	public function __construct( $data = [] ) {
		foreach ( $data as $key => $value ) {
			$this->__set( $key, $value );
		}
	}

	public function __get( $name ) {
		if ( ! array_key_exists( $name, $this->attributes ) ) {
			$this->attributes[ $name ] = new Model();
		}

		return $this->attributes[ $name ];
	}

	public function __set( $name, $value ) {
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}
		if ( is_array( $value ) ) {
			$is_assoc = count( array_filter( array_keys( $value ), 'is_string' ) ) > 0;
			if($is_assoc) {
				$value = new Model( $value );
			}
		}
		$this->attributes[ $name ] = $value;
	}

	public function hasValue( $name ) {
		return ! $this->isNull( $name ) && ! $this->isEmpty( $name );
	}

	public function isNull( $name ) {
		return $this->__get( $name ) === null;
	}

	public function isEmpty( $name ) {
		return $this->__get( $name ) === '' || ( ( $this->__get( $name ) instanceof Model ) && ! $this->__get( $name )->getAttributes() );
	}

	public function getAttributes() {
		return $this->attributes;
	}

	public function __toString() {
		if ( count( $this->attributes ) === 0 ) {
			return '';
		}
		if ( count( $this->attributes ) === 1 ) {
			return array_values( $this->attributes )[0];
		}

		return wp_json_encode( $this->attributes );
	}
}
