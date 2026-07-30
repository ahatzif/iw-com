<?php

namespace WCML\Multicurrency\CurrencySwitcher;

interface CurrencySwitcherTemplateInterface {
	public function is_path_valid(): bool;

	public function set_model( $model );

	public function has_styles(): bool;

	public function get_styles( bool $with_version = false ): array;

	public function get_scripts( bool $with_version = false ): array;

	public function get_resource_handler( string $index ): string;

	public function get_inline_style_handler();

	public function get_template_data(): array;

	public function get_template();

	public function get_view( $template = null, $model = null );
}