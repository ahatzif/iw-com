<?php

class WCML_Switch_Lang_Request implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_DIC_Action {

	const COOKIE_NAME = 'wp-wpml_current_language';

	protected $default_language;
	protected $wp_api;
	private $cookie;
	private $sitepress;

	public function __construct( WPML_Cookie $cookie, WPML_WP_API $wp_api, SitePress $sitepress ) {

		if ( ! is_admin() ) {
			$this->cookie           = $cookie;
			$this->wp_api           = $wp_api;
			$this->sitepress        = $sitepress;
			$this->default_language = $this->sitepress->get_default_language();
		}
	}

	public function add_hooks() {

		if ( ! is_admin() && $this->sitepress->get_setting( $this->wp_api->constant( 'WPML_Cookie_Setting::COOKIE_SETTING_FIELD' ) ) ) {
			add_action( 'wpml_before_init', [ $this, 'detect_user_switch_language' ] );
		}
	}

	public function detect_user_switch_language() {

		if ( ! $this->wp_api->constant( 'DOING_AJAX' ) ) {

			$lang_from = $this->get_cookie_lang();
			$lang_to   = $this->get_requested_lang();

			if ( $lang_from && $lang_from !== $lang_to ) {
				do_action( 'wcml_user_switch_language', $lang_from, $lang_to );
			}
		}

	}

	public function get_cookie_lang() {
		global $wpml_language_resolution;

		$cookie_name  = $this->get_cookie_name();
		$cookie_value = $this->cookie->get_cookie( $cookie_name );
		$lang         = $cookie_value ? substr( $cookie_value, 0, 10 ) : null;
		$lang         = $wpml_language_resolution->is_language_active( $lang ) ? $lang : $this->default_language;

		return $lang;
	}

	public function get_cookie_name() {
		return self::COOKIE_NAME;
	}

	public function get_cookie_domain() {

		return defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : $this->get_server_host_name();
	}

	public function get_server_host_name() {
		$host = $_SERVER['HTTP_HOST'] ?? null;
		if ( ! $host ) {
			$host        = $_SERVER['SERVER_NAME'] ?? null;
			$server_port = $_SERVER['SERVER_PORT'] ?? null;
			if ( $host && $server_port && ! in_array( $server_port, [ 80, 443 ], false ) ) {
				$host = $host . ':' . $server_port;
			}
		}
		return preg_replace( '@:[443]+([/]?)@', '$1', $host );
	}

	public function get_requested_lang() {
		return $this->is_comments_post_page() ? $this->get_cookie_lang() : $this->get_request_uri_lang();
	}

	public function is_comments_post_page() {
		global $pagenow;

		return 'wp-comments-post.php' === $pagenow;
	}

	public function get_request_uri_lang() {
		global $wpml_url_converter;

		$req_url = isset( $_SERVER['HTTP_HOST'] ) ? untrailingslashit( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) : '';

		return $wpml_url_converter->get_language_from_url( $req_url );
	}

}
