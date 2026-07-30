<?php

namespace WCML\Synchronization\Component;

class Linked extends SynchronizerForMeta {

	const LINKED_META_KEYS = [
		'_upsell_ids',
		'_cross_sell_ids',
		'_children',
	];

	const TRANSIENTS_PREFIXES = [
		'wc_product_children_%s',
		'_transient_wc_product_children_ids_%s',
	];

	public function run( $product, $translationsIds, $translationsLanguages ) {
		foreach ( self::LINKED_META_KEYS as $metaKey ) {
			$this->syncLinkType( $product->ID, $translationsIds, $translationsLanguages, $metaKey );
		}
		foreach ( $translationsIds as $translationId ) {
			$translationParentId = wp_get_post_parent_id( $translationId );
			if ( $translationParentId ) {
				foreach ( self::TRANSIENTS_PREFIXES as $transientPrefix ) {
					delete_transient( sprintf( $transientPrefix, $translationParentId ) );
				}
			}
		}
	}

	private function syncLinkType( $productId, $translationsIds, $translationsLanguages, $metaKey ) {
		$productsIds  = array_merge( [ $productId ], $translationsIds );
		$storedLinks  = $this->getMeta( $metaKey, $productsIds );
		$productLinks = $storedLinks[ $productId ] ?? null;

		if ( null === $productLinks ) {
			$translationsIdsToClear = array_intersect( $translationsIds, array_keys( $storedLinks ) );
			$this->clearTranslationsValue( $translationsIdsToClear, $metaKey );
			return;
		}

		if ( empty( $productLinks ) ) {
			$this->spreadEmptyValue( $translationsIds, $storedLinks, $metaKey );
			return;
		}

		$translatedLinks = [];
		foreach ( $translationsLanguages as $translationId => $language ) {
			$translatedLinks[ $translationId ] = $this->translateLinks( $productLinks, $language );
		}

		$metaToInsert = [];
		$metaToUpdate = [];
		foreach ( $translatedLinks as $translationId => $translationLinks ) {
			if ( ! array_key_exists( $translationId, $storedLinks ) ) {
				$metaToInsert[ $translationId ] = $translationLinks;
				continue;
			}
			if ( maybe_serialize( $translationLinks ) === maybe_serialize( $storedLinks[ $translationId ] ) ) {
				continue;
			}
			$metaToUpdate[ $translationId ] = $translationLinks;
		}

		$this->insertMeta( $metaKey, $metaToInsert );
		$this->updateMeta( $metaKey, $metaToUpdate );
	}

	private function translateLinks( $productLinks, $language ) {
		$translatedLinkedProducts = [];
		foreach ( $productLinks as $linkedProduct ) {
			$translatedLinkedProducts[] = apply_filters( 'wpml_object_id', $linkedProduct, get_post_type( $linkedProduct ), false, $language );
		}
		return $translatedLinkedProducts;
	}

}
