<?php

namespace WCML\Synchronization\Component;

use WCML\Utilities\SyncHash;

abstract class Synchronizer {

	protected $woocommerceWpml;

	protected $sitepress;

	protected $elementTranslations;

	protected $wpdb;

	protected $syncHashManager;

	public function __construct(
		\woocommerce_wpml         $woocommerceWpml,
		\SitePress                $sitepress,
		\WPML_Element_Translation $elementTranslations,
		\wpdb                     $wpdb,
		SyncHash                  $syncHashManager
	) {
		$this->woocommerceWpml     = $woocommerceWpml;
		$this->sitepress           = $sitepress;
		$this->elementTranslations = $elementTranslations;
		$this->wpdb                = $wpdb;
		$this->syncHashManager     = $syncHashManager;
	}

	abstract public function run( $product, $translationsIds, $translationsLanguages );

}

