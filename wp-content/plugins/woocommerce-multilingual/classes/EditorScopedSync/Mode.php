<?php

namespace WCML\EditorScopedSync;

class Mode {

	const VALUE_COMPLETE      = 'complete';
	const VALUE_EDITOR_SCOPED = 'editor_scoped';

	const SETTING_KEY = 'editor_scoped_save_mode';

	private $woocommerce_wpml;

	public function __construct( \woocommerce_wpml $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function current() {
		$settings = $this->woocommerce_wpml->get_settings();
		$value    = isset( $settings[ self::SETTING_KEY ] ) ? $settings[ self::SETTING_KEY ] : self::VALUE_COMPLETE;
		return self::VALUE_EDITOR_SCOPED === $value ? self::VALUE_EDITOR_SCOPED : self::VALUE_COMPLETE;
	}

	public function isEditorScoped() {
		return self::VALUE_EDITOR_SCOPED === $this->current();
	}

	public function set( $value ) {
		$value    = self::VALUE_EDITOR_SCOPED === $value ? self::VALUE_EDITOR_SCOPED : self::VALUE_COMPLETE;
		$settings = $this->woocommerce_wpml->get_settings();
		$settings[ self::SETTING_KEY ] = $value;
		$this->woocommerce_wpml->update_settings( $settings );
	}
}
