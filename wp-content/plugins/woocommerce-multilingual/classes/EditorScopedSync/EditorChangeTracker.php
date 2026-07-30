<?php

namespace WCML\EditorScopedSync;

class EditorChangeTracker implements \IWPML_Backend_Action {

	const PRIORITY_BEFORE_WC_SAVES = 0;

	private static $editedVariationIds = [];

	private static $deletedVariationIds = [];

	private static $productChanges = [];

	public function add_hooks() {
		add_action( 'woocommerce_save_product_variation', [ __CLASS__, 'recordVariationSave' ], self::PRIORITY_BEFORE_WC_SAVES, 2 );
		add_action( 'before_delete_post', [ __CLASS__, 'recordVariationDelete' ], self::PRIORITY_BEFORE_WC_SAVES, 2 );
		add_action( 'woocommerce_admin_process_product_object', [ __CLASS__, 'recordProductChanges' ], self::PRIORITY_BEFORE_WC_SAVES, 1 );
	}

	public static function recordVariationSave( $variationId, $i ) {
		$variationId = (int) $variationId;
		$parent       = (int) wp_get_post_parent_id( $variationId );
		if ( $parent <= 0 ) {
			return;
		}
		self::$editedVariationIds[ $parent ][ $variationId ] = true;
	}

	public static function recordVariationDelete( $postId, $post = null ) {
		if ( ! $post || 'product_variation' !== $post->post_type ) {
			return;
		}
		$parent = (int) $post->post_parent;
		if ( $parent <= 0 ) {
			return;
		}
		self::$deletedVariationIds[ $parent ][ (int) $postId ] = true;
	}

	public static function recordProductChanges( $product ) {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_changes' ) || ! method_exists( $product, 'get_id' ) ) {
			return;
		}
		self::$productChanges[ (int) $product->get_id() ] = $product->get_changes();
	}

	public static function editedVariationIdsFor( $productId ) {
		$productId = (int) $productId;
		$ids = isset( self::$editedVariationIds[ $productId ] ) ? self::$editedVariationIds[ $productId ] : [];
		return array_keys( $ids );
	}

	public static function deletedVariationIdsFor( $productId ) {
		$productId = (int) $productId;
		$ids = isset( self::$deletedVariationIds[ $productId ] ) ? self::$deletedVariationIds[ $productId ] : [];
		return array_keys( $ids );
	}

	public static function productChangesFor( $productId ) {
		$productId = (int) $productId;
		return isset( self::$productChanges[ $productId ] ) ? self::$productChanges[ $productId ] : [];
	}

	public static function hasAnyVariationActivity( $productId ) {
		$productId = (int) $productId;
		return ! empty( self::$editedVariationIds[ $productId ] )
			|| ! empty( self::$deletedVariationIds[ $productId ] );
	}

	public static function reset() {
		self::$editedVariationIds  = [];
		self::$deletedVariationIds = [];
		self::$productChanges      = [];
	}
}
