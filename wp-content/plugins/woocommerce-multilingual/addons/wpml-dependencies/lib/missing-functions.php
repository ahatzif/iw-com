<?php

if ( ! function_exists( 'wcml_wpml_get_admin_notices' ) ) {
	function wcml_wpml_get_admin_notices() {
		global $wpml_admin_notices;

		if ( ! $wpml_admin_notices ) {
			$wpml_admin_notices = new WPML_Notices( new WPML_Notice_Render() );
			$wpml_admin_notices->init_hooks();
		}

		return $wpml_admin_notices;
	}
}

if ( ! function_exists( 'wpml_is_ajax' ) ) {
	function wpml_is_ajax() {
		if ( defined( 'DOING_AJAX' ) ) {
			return true;
		}

		return ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && wpml_mb_strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) ? true : false;
	}
}