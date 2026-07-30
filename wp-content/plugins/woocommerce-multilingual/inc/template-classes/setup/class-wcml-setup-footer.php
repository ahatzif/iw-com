<?php

class WCML_Setup_Footer_UI extends WCML_Templates_Factory {

	private $has_handler;

	public function __construct( $has_handler ) {
		parent::__construct();
		$this->has_handler = $has_handler;
	}

	public function get_model() {

		$model = [
			'has_handler' => $this->has_handler,
		];

		return $model;

	}

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return '/setup/footer.twig';
	}


}
