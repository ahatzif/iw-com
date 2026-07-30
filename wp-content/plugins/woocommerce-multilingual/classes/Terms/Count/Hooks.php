<?php

namespace WCML\Terms\Count;

use WCML\Terms\SuspendWpmlFiltersFactory;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks as WpHooks;
use WCML\Utilities\WCTaxonomies;
use function WPML\FP\spreadArgs;

class Hooks implements \IWPML_Backend_Action, \IWPML_REST_Action {

	public function add_hooks() {
		WpHooks::onFilter( 'woocommerce_product_recount_terms', PHP_INT_MAX )
			->then( spreadArgs( [ self::class, 'disableTermFilters' ] ) );

		WpHooks::onAction( 'icl_save_term_translation', 10, 2 )
			->then( spreadArgs( [ self::class, 'recountOnSaveTermTranslation' ] ) );

		WpHooks::onAction( 'wpml_sync_term_hierarchy_done' )
			->then( [ self::class, 'recountAllTermsInShutdown' ] );
	}

	public static function disableTermFilters( $shouldRecountTerms ) {
		if ( $shouldRecountTerms ) {
			$filtersSuspend = SuspendWpmlFiltersFactory::create();

			add_action( 'delete_transient_wc_term_counts', function() use ( $filtersSuspend ) {
				$filtersSuspend->resume();
			} );

		}

		return $shouldRecountTerms;
	}

	public static function recountOnSaveTermTranslation( $originalTax, $translatedTerm ) {
		$taxonomyName = Obj::prop( 'taxonomy', $originalTax );

		if ( in_array( $taxonomyName, [ WCTaxonomies::TAXONOMY_PRODUCT_CATEGORY, WCTaxonomies::TAXONOMY_PRODUCT_TAG ], true ) ) {
			SuspendWpmlFiltersFactory::create()->runAndResume( function () use ( $translatedTerm, $taxonomyName ) {
				_wc_term_recount( [ (int) Obj::prop( 'term_taxonomy_id', $translatedTerm ) ], get_taxonomy( $taxonomyName ) );
			} );
		}
	}

	public static function recountAllTermsInShutdown() {
		WpHooks::onAction( 'shutdown' )->then( Fns::once( [ self::class, 'recountAllTerms' ] ) );
	}

	public static function recountAllTerms() {
		SuspendWpmlFiltersFactory::create()->runAndResume( function() {
			wc_recount_all_terms();
		} );
	}
}
