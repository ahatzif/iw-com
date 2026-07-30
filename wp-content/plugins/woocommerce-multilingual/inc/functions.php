<?php

namespace WCML\functions;

use function WPML\Container\make;
use WCML\StandAlone\NullSitePress;
use SitePress;
use woocommerce_wpml;

if ( ! function_exists( 'WCML\functions\getSitePress' ) ) {
	function getSitePress() {
		global $sitepress;

		if ( null === $sitepress ) {
			return new NullSitePress();
		}
		return $sitepress;
	}
}

if ( ! function_exists( 'WCML\functions\getWooCommerceWpml' ) ) {
	function getWooCommerceWpml() {
		global $woocommerce_wpml;

		return $woocommerce_wpml;
	}
}

if ( ! function_exists( 'WCML\functions\isStandAlone' ) ) {
	function isStandAlone() {
		return ! defined( 'ICL_SITEPRESS_VERSION' );
	}
}

if ( ! function_exists( 'WCML\functions\assetLink' ) ) {
	function assetLink( $asset ) {
		if ( isStandAlone() ) {
			return WCML_PLUGIN_URL . '/addons/wpml-dependencies/lib' . $asset;
		}
		return ICL_PLUGIN_URL . $asset;
	}
}

if ( ! function_exists( '\WCML\functions\getSetting' ) ) {
	function getSetting( $key, $default = null ) {
		return make( woocommerce_wpml::class )->get_setting( $key, $default );
	}
}

if ( ! function_exists( '\WCML\functions\updateSetting' ) ) {
	function updateSetting( $key, $value, $autoload = false ) {
		make( woocommerce_wpml::class )->update_setting( $key, $value, $autoload );
	}
}

if ( ! function_exists( '\WCML\functions\getClientCurrency' ) ) {
	function getClientCurrency() {
		return make( \WCML_Multi_Currency::class )->get_client_currency();
	}
}

if ( ! function_exists( 'WCML\functions\isCli' ) ) {
	function isCli() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}
}

if ( ! function_exists( 'WCML\functions\flushProductCachePrefixById' ) ) {
	function flushProductCachePrefixById( $product_id ) {
		$cacheKey   = "wc_product_{$product_id}_cache_prefix";
		$cacheGroup = "product_{$product_id}";

		wp_cache_delete( $cacheKey, $cacheGroup );
	}
}
