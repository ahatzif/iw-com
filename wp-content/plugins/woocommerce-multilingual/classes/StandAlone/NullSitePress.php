<?php

namespace WCML\StandAlone;

use WPML\Core\ISitePress;
use WPML_WP_API;

class NullSitePress implements ISitePress {

	private ?WPML_WP_API $wp_api = null;

	public function get_active_languages( $refresh = false, $major_first = false, $order_by = 'english_name' ) {
		$code = $this->get_current_language();
		return [
			$code => [
				'id'             => 1,
				'code'           => $code,
				'major'          => 1,
				'active'         => 1,
				'default_locale' => get_locale(),
				'encode_url'     => 0,
				'tag'            => $code,
				'english_name'   => $code,
				'native_name'    => $code,
				'display_name'   => $code,
			],
		];
	}

	public function get_admin_language() {
		return $this->get_current_language();
	}

	public function get_current_language() {
		return preg_replace( '/_.+/', '', get_locale() );
	}

	public function switch_lang( $code = null, $cookie_lang = false ) {

	}

	public function get_default_language() {
		return $this->get_current_language();
	}

	public function get_element_translations(
		$trid,
		$el_type = 'post_post',
		$skip_empty = false,
		$all_statuses = false,
		$skip_cache = false,
		$skip_recursions = false,
		$skipPrivilegeChecking = false
	 ) {
		 return [];
	}

	public function get_flag_url( $code ) {
		return '';
	}

	public function get_language_from_url( $url ) {
		return $this->get_current_language();
	}

	public function get_search_form_filter( $form ) {
		return $form;
	}

	public function get_setting( $key, $default = false ) {
		return $default;
	}

	public function get_settings() {
		return [];
	}

	public function get_wp_api() {
		if ( ! ( $this->wp_api instanceof \WPML_WP_API ) ) {
			$this->wp_api = new WPML_WP_API();
		}

		return $this->wp_api;
	}

	public function is_rtl( $lang = false ) {
		return is_rtl();
	}

	public function get_language_for_element( $element_id, $element_type ) {
		return $this->get_current_language();
	}
}
