<?php

namespace WCML\Utilities;

use WPML\FP\Fns;

class Post {

	public static function insert( array $args, $lang = null, $trid = null ) {
		$saveInLang = $saveWithTrid = null;

		if ( $lang ) {
			$saveInLang = Fns::always( $lang );
			add_filter( 'wpml_save_post_lang', $saveInLang );
		}

		if ( $trid ) {
			$saveWithTrid = Fns::always( $trid );
			add_filter( 'wpml_save_post_trid_value', $saveWithTrid );
		}

		$newPostId = wp_insert_post( $args );

		remove_filter( 'wpml_save_post_lang', $saveInLang );
		remove_filter( 'wpml_save_post_trid_value', $saveWithTrid );

		return $newPostId;
	}
}
