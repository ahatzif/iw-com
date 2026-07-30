<?php

namespace WCML\Multicurrency\CurrencySwitcher;

class CurrencySwitcherComponent implements CurrencySwitcherTemplateInterface {
	const TEMPLATE_FILENAME = 'template.php';

	private $templateSetup;

	private $templatePaths = [];

	private $prefix = 'wcml-cs-';

	private $model;

	private ?\WPML_WP_API $wp_api = null;

	public function __construct( $templateSetup ) {
		$this->templateSetup = $this->formatTemplateSetupData( $templateSetup );
		$this->initTemplateBaseDir();
	}

	protected function initTemplateBaseDir() {
		$this->templatePaths = (array) $this->templateSetup['path'];
	}

	private function formatTemplateSetupData( array $templateSetup ): array {
		foreach ( [ 'path', 'js', 'css' ] as $k ) {
			$templateSetup[ $k ] = $templateSetup[ $k ] ?? [];
			$templateSetup[ $k ] = is_array( $templateSetup[ $k ] ) ? $templateSetup[ $k ] : [ $templateSetup[ $k ] ];
		}

		return $templateSetup;
	}

	public function get_template() {
		return self::TEMPLATE_FILENAME;
	}

	public function set_model( $model ) {
		$this->model = is_array( $model ) ? $model : [ $model ];
	}

	public function render() {
		echo $this->get_view();
	}

	public function get_view( $template = null, $model = null ) {
		if ( null === $template ) {
			$template = $this->get_template();
		}

		$output = '';

		if ( null === $model ) {
			$model = $this->model;
		}

		try {
			$output = $this->renderUsingPHPTemplate( $template, $model );
		} catch ( \WPML\Core\Twig\Error\Error $e ) {
			$message = 'Invalid template string: ' . $e->getRawMessage() . "\n" . $template;
			$this->getWPML_WP_APIInstance()->error_log( $message );
		}

		return $output;
	}

	public function has_styles(): bool {
		return ! empty( $this->templateSetup['css'] );
	}

	public function get_inline_style_handler() {
		$count = count( $this->templateSetup['css'] );

		return $count > 0 ? $this->get_resource_handler( $count - 1 ) : null;
	}

	public function get_scripts( bool $withVersion = false ): array {
		return $withVersion
			? array_map( [ self::class, 'addResourceVersion' ], $this->templateSetup['js'] )
			: $this->templateSetup['js'];
	}

	public function get_styles( bool $withVersion = false ): array {
		return $withVersion
			? array_map( [ self::class, 'addResourceVersion' ], $this->templateSetup['css'] )
			: $this->templateSetup['css'];
	}

	public static function addResourceVersion( $url ): string {
		return $url . '?ver=' . WCML_VERSION;
	}

	public function is_path_valid(): bool {
		foreach ( $this->templatePaths as $path ) {
			if ( ! file_exists( $path ) ) {
				return false;
			}
		}

		return true;
	}

	public function get_resource_handler( string $index ): string {
		$slug   = $this->templateSetup['slug'] ?? '';
		$prefix = $this->is_core() ? '' : $this->prefix;

		return $prefix . $slug . '-' . $index;
	}

	public function is_core(): bool {
		return isset( $this->templateSetup['is_core'] ) ? (bool) $this->templateSetup['is_core'] : false;
	}

	public function get_template_data(): array {
		return $this->templateSetup;
	}

	private function renderUsingPHPTemplate( $template, $model ): string {
		if ( ! is_array( $model ) ) {
			$model = [];
		}

		foreach ( $this->templatePaths as $template_path ) {
			$template_file = $template_path . DIRECTORY_SEPARATOR . $template;
			if ( file_exists( $template_file ) ) {
				extract( $model );

				ob_start();
				require $template_file;
				$output = ob_get_contents();
				ob_end_clean();

				if ( false === $output ) {
					return '';
				}

				return $output;
			}
		}

		return '';
	}

	protected function getWPML_WP_APIInstance(): \WPML_WP_API {
		if ( ! ( $this->wp_api instanceof \WPML_WP_API ) ) {
			$this->wp_api = new \WPML_WP_API();
		}

		return $this->wp_api;
	}
}
