<?php

namespace WCML\Synchronization;

class Manager {

	const CONTEXT_PRODUCT_EDIT_SCREEN_UPDATE = 'product_edit_screen_update';
	const CONTEXT_PRODUCT_BULK_OR_QUICK_EDIT = 'product_bulk_or_quick_edit';

	private $postTranslations;

	private $syncStore;

	private $context;

	public function __construct( Store $syncStore ) {
		$this->syncStore        = $syncStore;

		global $wpml_post_translations;
		$this->postTranslations = $wpml_post_translations;
	}

	public function setContext( $context ) {
		$this->context = $context;
	}

	private function isInContext( $context ) {
		$context = (array) $context;

		return in_array( $this->context, $context, true );
	}

	public function getOriginalProduct( $product ) {
		$originalProduct   = $product;
		$originalProductId = $this->postTranslations->get_original_element( $product->ID ) ?: $product->ID;
		if ( $originalProductId !== $product->ID ) {
			$originalProduct = get_post( $originalProductId );
		}
		return $originalProduct;
	}

	public function isOriginalProduct( $product ) {
		return null === $this->postTranslations->get_source_lang_code( $product->ID );
	}

	public function getElementLanguage( $elementId ) {
		return $this->postTranslations->get_element_lang_code( $elementId );
	}

	public function getElementTranslations( $elementId, $trid = false, $actualtranslationsOnly = true ) {
		return $this->postTranslations->get_element_translations( $elementId, $trid, $actualtranslationsOnly );
	}

	public function run( $product, $translationsIds = [], $translationsLanguages = [] ) {
		$originalProduct = $this->getOriginalProduct( $product );

		if ( empty( $translationsIds ) ) {
			$translationsIds = $this->postTranslations->get_element_translations( $originalProduct->ID, false, true );
		}

		if ( empty( $translationsIds ) ) {
			return;
		}

		foreach ( $translationsIds as $translationId ) {
			if ( isset( $translationsLanguages[ $translationId ] ) ) {
				continue;
			}
			$translationsLanguages[ $translationId ] = $this->getElementLanguage( $translationId );
		}

		do_action( 'wcml_before_sync_product', $originalProduct->ID, $product->ID );
		$this->runProductComponents( $originalProduct, $translationsIds, $translationsLanguages );
		do_action( 'wcml_after_sync_product', $originalProduct->ID, $product->ID );
	}

	public function runProductComponents( $product, $translationsIds, $translationsLanguages ) {
		$components = $this->getComponentsByPostType( $product->post_type );
		if ( empty( $components ) ) {
			return;
		}

		foreach ( $translationsIds as $translationId ) {
			do_action( 'wcml_before_sync_product_data', $product->ID, $translationId, $translationsLanguages[ $translationId ] );
		}

		
		foreach ( $components as $componentName ) {
			$this->runComponent( $product, $translationsIds, $translationsLanguages, $componentName );
		}

		$wcmlDataStore = wcml_product_data_store_cpt();

		foreach ( $translationsIds as $translationId ) {
			$wcmlDataStore->update_lookup_table_data( $translationId );
			wc_delete_product_transients( $translationId );
			do_action( 'wcml_after_sync_product_data', $product->ID, $translationId, $translationsLanguages[ $translationId ] );
		}

	}

	public function runProductVariationComponents( $variation, $translationsIds, $translationsLanguages ) {
		$components = $this->getComponentsByPostType( $variation->post_type );
		if ( empty( $components ) ) {
			return;
		}

		foreach ( $components as $componentName ) {
			$this->runComponent( $variation, $translationsIds, $translationsLanguages, $componentName );
		}

		$wcmlDataStore = wcml_product_data_store_cpt();

		foreach ( $translationsIds as $translationId ) {
			$wcmlDataStore->update_lookup_table_data( $translationId );
		}
	}

	public function runComponent( $product, $translationsIds, $translationsLanguages, $componentName ) {
		$component = $this->syncStore->getComponent( $componentName );
		$component->run( $product, $translationsIds, $translationsLanguages );
	}

	private function getComponentsByPostType( $postType ) {
		$components = [];

		if ( 'product' === $postType ) {
			$components = [
				Store::COMPONENT_ATTACHMENTS,
				Store::COMPONENT_ATTRIBUTES,
				Store::COMPONENT_DOWNLOADABLE_FILES,
				Store::COMPONENT_LINKED,
				Store::COMPONENT_POST,
				Store::COMPONENT_STOCK,
				Store::COMPONENT_TAXONOMIES,
				Store::COMPONENT_META,
			];

			if ( ! $this->isInContext( [ self::CONTEXT_PRODUCT_EDIT_SCREEN_UPDATE, self::CONTEXT_PRODUCT_BULK_OR_QUICK_EDIT ] ) ) {
				$components[] = Store::COMPONENT_VARIATIONS;
			}

		} elseif ( 'product_variation' === $postType ) {
			$components = [
				Store::COMPONENT_VARIATION_ATTACHMENTS,
				Store::COMPONENT_VARIATION_META,
				Store::COMPONENT_DOWNLOADABLE_FILES,
				Store::COMPONENT_VARIATION_TAXONOMIES,
				Store::COMPONENT_STOCK,
			];
		}

		return $components;
	}

}
