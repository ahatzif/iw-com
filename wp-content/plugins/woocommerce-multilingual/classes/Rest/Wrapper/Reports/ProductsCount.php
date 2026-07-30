<?php

namespace WCML\Rest\Wrapper\Reports;

use WCML\Rest\Exceptions\InvalidLanguage;
use WCML\Rest\Wrapper\Handler;
use WPML\FP\Obj;

class ProductsCount extends Handler {

	private $sitepress;
	private $wpdb;

	public function __construct( \SitePress $sitepress, \wpdb $wpdb ) {
		$this->sitepress = $sitepress;
		$this->wpdb      = $wpdb;
	}

	public function prepare( $response, $object, $request ) {

		$language = Obj::prop( 'lang', $request->get_params() );

		if ( $language ) {

			if ( ! $this->sitepress->is_active_language( $language ) ) {
				throw new InvalidLanguage( $language );
			}

			$term = get_term_by( 'slug', $object->slug, 'product_type' );

			$count = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT count( p.ID ) FROM {$this->wpdb->posts} as p 
						LEFT JOIN {$this->wpdb->term_relationships} as tr ON p.ID = tr.object_id 
						LEFT JOIN {$this->wpdb->prefix}icl_translations as icl ON p.ID = icl.element_id 
						WHERE tr.term_taxonomy_id = %d AND icl.language_code = %s AND icl.element_type = 'post_product'",
					$term->term_taxonomy_id, $language
				)
			);

			$data = \WPML\FP\Obj::assoc( 'total', $count, $response->get_data() );
			$data = \WPML\FP\Obj::assoc( 'lang', $language, $data );

			$response->set_data( $data );
		}

		return $response;
	}

}