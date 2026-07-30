<?php

namespace WCML\Rest;

use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\API\Sanitize;

class Generic {

	public static function preventDefaultLangUrlRedirect() {
		$exp = explode( '?', $_SERVER['REQUEST_URI'] );
		if ( ! empty( $exp[1] ) ) {
			parse_str( $exp[1], $vars );
			if ( isset( $vars['lang'] ) && $vars['lang'] === wpml_get_default_language() ) {
				unset( $vars['lang'] );
				$_SERVER['REQUEST_URI'] = $exp[0] . '?' . http_build_query( $vars );
			}
		}
	}

	public static function autoAdjustIncludedIds( \WP_Query $wp_query ) {
		$lang    = Sanitize::string( (string) Obj::prop( 'lang', $_GET ) );
		$include = $wp_query->get( 'post__in' );
		if ( empty( $lang ) && ! empty( $include ) ) {
			$filtered_include = [];
			foreach ( $include as $id ) {
				$filtered_include[] = wpml_object_id_filter( $id, get_post_type( $id ), true );
			}
			$wp_query->set( 'post__in', $filtered_include );
		}
	}

	public static function removeHomeUrlFilterOnRestAuthentication() {
		$returnTrue = Fns::always( true );

		add_filter( 'determine_current_user', Fns::tap( function() use ( $returnTrue ) {
			add_filter( 'wpml_skip_convert_url_string', $returnTrue );
		} ), 10 );

		add_filter( 'determine_current_user', Fns::tap( function() use ( $returnTrue ) {
			remove_filter( 'wpml_skip_convert_url_string', $returnTrue );
		} ), 20 );
	}

}
