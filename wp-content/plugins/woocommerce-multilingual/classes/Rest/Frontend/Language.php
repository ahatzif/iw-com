<?php

namespace WCML\Rest\Frontend;

use WCML_Switch_Lang_Request;
use WPML_Cookie;
use WPML_URL_Converter;

class Language {

	private $cookie;

	private $urlConverter;

	public function __construct(
		WPML_Cookie $cookie,
		WPML_URL_Converter $urlConverter
	) {
		$this->cookie       = $cookie;
		$this->urlConverter = $urlConverter;
	}

	public function get() {
		$lang = $this->cookie->get_cookie( WCML_Switch_Lang_Request::COOKIE_NAME );

		if ( ! $lang && isset( $_SERVER['HTTP_REFERER'] ) ) {
			$lang = $this->urlConverter->get_language_from_url( $_SERVER['HTTP_REFERER'] );
		}

		return $lang;
	}
}
