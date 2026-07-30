<?php

namespace WCML\Rest\Language;

use WPML\API\Sanitize;
use WPML\FP\Obj;

class Set {

	public static function fromUrlQueryVar() {
		$lang = self::sanitize( Obj::prop( 'lang', $_GET ) );

		if ( $lang ) {
			wpml_switch_language_action( $lang );
		}
	}

	public static function beforeCallbacks( $response, $handler, \WP_REST_Request $request ) {
		$lang = self::getFromRequestParams( $request )
			?: self::getFromProduct( $handler, $request );

		if ( $lang ) {
			wpml_switch_language_action( $lang );
		}

		return $response;
	}

	private static function getFromRequestParams( \WP_REST_Request $request ) {
		return self::sanitize( $request->get_param( 'lang' ) );
	}

	private static function getFromProduct( $handler, \WP_REST_Request $request ) {
		$callback = Obj::prop( 'callback', $handler );

		if (
			is_array( $callback )
			&& Obj::prop( 0, $callback ) instanceof \WC_REST_Products_Controller
			&& $request->get_param( 'id' )
		) {
			return (string) Obj::prop(
				'language_code',
				apply_filters( 'wpml_post_language_details', [], $request->get_param( 'id' ) )
			);
		}

		return '';
	}

	private static function sanitize( $lang ) {
		return Sanitize::string( $lang );
	}
}
