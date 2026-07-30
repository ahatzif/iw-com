<?php

namespace WCML\Compatibility;

use WooCommerce;
use wpdb;
use WPML_Element_Translation_Package;

abstract class ComponentFactory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	abstract public function create();

	protected static function getWpdb() {
		global $wpdb;

		return $wpdb;
	}

	protected static function getWooCommerce() {
		global $woocommerce;

		return $woocommerce;
	}

	protected static function getElementTranslationPackage() {
		return class_exists( 'WPML_Element_Translation_Package' ) ? new WPML_Element_Translation_Package() : null;
	}

	protected static function getPostTranslations() {
		global $wpml_post_translations;

		return $wpml_post_translations;
	}
}
