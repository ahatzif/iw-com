<?php

use WPML\Core\Twig_SimpleFunction;

class WCML_Setup_Header_UI extends WCML_Templates_Factory {

	private $steps;
	private $step;

	public function __construct( $steps, $step ) {

		$functions = [
			new Twig_SimpleFunction( 'language_attributes', [ $this, 'language_attributes' ] ),
			new Twig_SimpleFunction( 'wp_print_scripts', [ $this, 'wp_print_scripts' ] ),
			new Twig_SimpleFunction( 'wp_do_action', [ $this, 'wp_do_action' ] ),
		];

		parent::__construct( $functions );

		$this->steps = $steps;
		$this->step  = $step;
	}

	public function get_model() {

		$model = [
			'title'           => __( 'WPML Multilingual & Multicurrency for WooCommerce › Setup Wizard', 'woocommerce-multilingual' ),
			'WCML_PLUGIN_URL' => WCML_PLUGIN_URL,
			'step'            => $this->step,
			'has_handler'     => ! empty( $this->steps[ $this->step ]['handler'] ),
			'nonce'           => wp_create_nonce( $this->step ),
		];

		return $model;

	}

	public function language_attributes() {
		language_attributes();
	}

	public function wp_print_scripts( $tag ) {
		wp_print_scripts( $tag );
	}

	public function wp_do_action( $hook ) {
		do_action( $hook );
	}

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return '/setup/header.twig';
	}


}
