<?php

namespace WCML\Synchronization\Component;

use WCML\Terms\SuspendWpmlFiltersFactory;
use WCML\Utilities\DB;
use WCML\Utilities\SyncHash;
use WPML_Non_Persistent_Cache;

class VariationTaxonomies extends Synchronizer {

	public function run( $variation, $translationsIds, $translationsLanguages ) {
		$filtersSuspend = SuspendWpmlFiltersFactory::create();
		foreach ( $translationsLanguages as $translationId => $language ) {
			$this->runForTranslation( $variation->ID, $translationId, $language );
		}
		$filtersSuspend->resume();
	}

	private function runForTranslation( $variationId, $translationId, $language ) {
		$taxonomies       = get_object_taxonomies( 'product_variation' );
		$taxonomiesToSync = array_values( array_diff( $taxonomies, [ 'translation_priority' ] ) );
		$taxonomiesToSync = apply_filters( 'wcml_product_variations_taxonomies_to_sync', $taxonomiesToSync, $variationId, $translationId, $language );
		$found            = false;
		$allTerms         = WPML_Non_Persistent_Cache::get( $variationId, __CLASS__, $found );
		if ( ! $found ) {
			$allTerms  = wp_get_object_terms( $variationId, $taxonomiesToSync );
			if ( is_wp_error( $allTerms ) ) {
				$allTerms = [];
			}
			WPML_Non_Persistent_Cache::set( $variationId, $allTerms, __CLASS__ );
		}

		$termIds     = wp_list_pluck( $allTerms, 'term_id' );
		$currentHash = md5( join( ',', $termIds ) );

		if ( $this->syncHashManager->isNewGroupValue( $translationId, SyncHash::GROUP_TAXONOMIES, $currentHash ) ) {
			foreach ( $taxonomiesToSync as $taxonomy ) {
				$terms = array_filter(
					$allTerms,
					function ( $term ) use ( $taxonomy ) {
						return $term->taxonomy === $taxonomy;
					}
				);

				if ( empty ( $terms ) ) {
					if ( ! $this->woocommerceWpml->terms->is_translatable_wc_taxonomy( $taxonomy ) ) {
						wp_set_object_terms( $translationId, [], $taxonomy );
					}
					continue;
				}

				$ttIds        = [];
				$ttIdsTrans   = [];
				$termIds      = [];
				$termIdsTrans = [];

				foreach ( $terms as $term ) {
					if ( $this->sitepress->is_translated_taxonomy( $taxonomy ) ) {
						$ttIds[] = $term->term_taxonomy_id;
					} else {
						$termIds[] = $term->term_id;
					}
				}

				foreach ( $ttIds as $ttId ) {
					$ttIdTrans = $this->elementTranslations->element_id_in( $ttId, $language );
					if ( $ttIdTrans ) {
						$ttIdsTrans[] = $ttIdTrans;
					}
				}

				$ttIdsTrans   = array_values( array_unique( array_map( 'intval', $ttIdsTrans ) ) );
				if ( ! empty( $ttIdsTrans ) ) {
					$termIdsTrans = $this->wpdb->get_col(
						$this->wpdb->prepare(
							"SELECT term_id FROM {$this->wpdb->term_taxonomy} WHERE term_taxonomy_id IN (" . DB::prepareIn( $ttIdsTrans, '%d' ) . ") LIMIT %d",
							count( $ttIdsTrans )
						)
					);
				}

				$termsToSync = array_merge( $termIds, $termIdsTrans );
				$termsToSync = array_unique( array_map( 'intval', $termsToSync ) );

				if ( empty( $termsToSync ) ) {
					continue;
				}
				wp_set_object_terms( $translationId, $termsToSync, $taxonomy, true );
			}

			$this->syncHashManager->updateGroupValue( $translationId, SyncHash::GROUP_TAXONOMIES, $currentHash );
		}
	}

}
