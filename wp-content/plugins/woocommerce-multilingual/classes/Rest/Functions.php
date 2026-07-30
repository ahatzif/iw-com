<?php

namespace WCML\Rest;

use WPML\FP\Obj;
use WPML\FP\Str;

class Functions {

	const STORE_NAMESPACE = 'wc/store/v1';

	public static function isAnalyticsPage() {
		return is_admin()
			&& 'wc-admin' === Obj::prop( 'page', $_GET )
			&& 0 === strpos( sanitize_text_field( wp_unslash( Obj::prop( 'path', $_GET ) ) ), '/analytics/' );
	}

	public static function isRestApiRequest() {
		return apply_filters( 'woocommerce_rest_is_request_to_rest_api', self::checkEndpoint( 'wc/v' . self::getApiRequestVersion() . '/' ) );
	}

	public static function isAnalyticsRestRequest() {
		return self::checkEndpoint( 'wc-analytics/' );
	}

	public static function isStoreAPIRequest() {
		return self::checkEndpoint( 'wc/store' );
	}

	public static function getApiRequestVersion() {

		$version = 0;

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return $version;
		}

		$restPrefix = trailingslashit( rest_get_url_prefix() );
		if ( preg_match( '@' . $restPrefix . 'wc/v([0-9]+)/@i', $_SERVER['REQUEST_URI'], $matches ) ) {
			$version = intval( $matches[1] );
		}

		return $version;
	}

	private static function checkEndpoint( $endpoint = 'wc/' ) {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		return ( false !== stripos( $_SERVER['REQUEST_URI'], $rest_prefix . $endpoint ) );
	}

	public static function getStoreStrippedEndpoint( $request ) {
		$route = trim( $request->get_route(), '/' );

		if ( Str::startsWith( self::STORE_NAMESPACE, $route ) ) {
			return Str::sub( Str::len( trailingslashit( self::STORE_NAMESPACE ) ), $route );
		}

		return null;
	}

}
