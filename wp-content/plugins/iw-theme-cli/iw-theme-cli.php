<?php
/**
 * Plugin Name: IW Theme CLI
 * Plugin URI: https://interweaveagency.com/
 * Description: WP-CLI helpers for project theme modules and Gutenberg blocks.
 * Author: ahtzif
 * Author URI: https://interweaveagency.com/
 * Version: 0.1.0
 *
 * @package Iw_Theme_Cli
 */

class IW_Theme_CLI_Create {
	protected static $modules_dir = '/assets/src/modules/';
	protected static $modules_file = '_all.js';
	protected static $modules_default_file = '_default-module.js';
	protected static $blocks_dir = '/gutenberg-blocks/';
	protected static $acf_json_dir = '/acf-json/';

	protected $theme_dir;

	public function __construct() {
		$this->theme_dir = get_stylesheet_directory();
	}

	/**
	 * Creates an ES6 modujs module.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The name of the module.
	 *
	 * ## EXAMPLES
	 *
	 *     wp theme create module fantastic-module
	 *
	 * @when after_wp_load
	 */
	public function module( $args, $assoc_args = [] ) {
		$slug = $this->normalize_slug( $args[0] ?? '' );
		$this->require_slug( $slug, 'module' );

		$module_name = $this->module_export_name( $slug );
		$readable_name = $this->readable_name( $slug );
		$modules_file = $this->theme_path( self::$modules_dir . self::$modules_file );
		$new_module_file = $this->theme_path( self::$modules_dir . $slug . '.js' );
		$modules_default_file = $this->theme_path( self::$modules_dir . self::$modules_default_file );

		$this->require_missing_file( $new_module_file, 'Module "' . $slug . '" already exists.' );
		$this->require_file( $modules_file, "Can't find modules file: " . $modules_file );
		$this->require_file( $modules_default_file, "Can't find default module file: " . $modules_default_file );

		if ( ! copy( $modules_default_file, $new_module_file ) ) {
			WP_CLI::error( 'Failed to create module file: ' . $new_module_file );
		}

		$modules_file_contents = (string) file_get_contents( $modules_file );
		$export_line = "export { default as {$module_name} } from './{$slug}';";
		if (
			false === strpos( $modules_file_contents, "from './{$slug}'" )
			&& false === strpos( $modules_file_contents, "from './{$slug}.js'" )
		) {
			$modules_file_contents = rtrim( $modules_file_contents ) . PHP_EOL . $export_line . PHP_EOL;
			$this->write_file( $modules_file, $modules_file_contents );
		}

		$this->normalize_file_mode( $new_module_file );

		WP_CLI::success( 'Module "' . $readable_name . '" created.' );
		WP_CLI::line( 'File Path: ' . $this->relative_to_abspath( $new_module_file ) );

		return $this->relative_to_abspath( $new_module_file );
	}

	/**
	 * Creates a new Gutenberg block.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The name of the Gutenberg block.
	 *
	 * [--with-module]
	 * : If set, a JS module is created and attached to the block section.
	 *
	 * [--nested]
	 * : If set, creates gutenberg-blocks/<name>/<name>.php instead of the flat default.
	 *
	 * ## EXAMPLES
	 *
	 *     wp theme create block fantastic-block
	 *     wp theme create block fantastic-block --with-module
	 *
	 * @when after_wp_load
	 */
	public function block( $args, $assoc_args = [] ) {
		$slug = $this->normalize_slug( $args[0] ?? '' );
		$this->require_slug( $slug, 'block' );

		$readable_name = $this->readable_name( $slug );
		$nested = isset( $assoc_args['nested'] );
		$new_file = $nested
			? $this->theme_path( self::$blocks_dir . $slug . '/' . $slug . '.php' )
			: $this->theme_path( self::$blocks_dir . $slug . '.php' );
		$acf_json_dir = $this->theme_path( self::$acf_json_dir );

		$this->require_missing_file( $new_file, 'Block "' . $slug . '" already exists, try another name.' );
		$this->ensure_directory( dirname( $new_file ) );
		$this->ensure_directory( $acf_json_dir );

		$module_filename = isset( $assoc_args['with-module'] ) ? $this->module( [ $slug ] ) : '';

		$acf_group = $this->render_boilerplate(
			__DIR__ . '/boilerplate/acf-group.json',
			[
				'{{block-key}}' => uniqid(),
				'{{block-name}}' => $readable_name,
				'{{field-key-message}}' => uniqid(),
				'{{field-key-content}}' => uniqid(),
				'{{field-key}}' => uniqid(),
				'{{block-slug}}' => $slug,
				'{{block-modified}}' => time(),
			]
		);

		$group_key = $this->extract_acf_group_key( $acf_group );
		$acf_group_file_name = $acf_json_dir . $slug . '_' . $group_key . '.json';
		$acf_group_file_name_relative = $this->relative_to_abspath( $acf_group_file_name );
		$this->write_file( $acf_group_file_name, $acf_group );

		$block_contents = $this->render_boilerplate(
			__DIR__ . '/boilerplate/gutenberg-block.php',
			[
				'{{block-name}}' => $readable_name,
				'{{acf-json-file-path}}' => '@see ' . $acf_group_file_name_relative . ' ACF Fields',
				'{{js-module-file-path}}' => $module_filename ? '@see ' . $module_filename . ' JS Module File' : '',
				'{{data-module-attribute}}' => $module_filename ? ' data-module-' . $slug : '',
			]
		);
		$this->write_file( $new_file, $block_contents );

		WP_CLI::success( 'Gutenberg block "' . $readable_name . '" created.' );
		WP_CLI::line( 'Block File: ' . $this->relative_to_abspath( $new_file ) );
		WP_CLI::line( 'ACF JSON: ' . $acf_group_file_name_relative );
	}

	protected function normalize_slug( $name ) {
		$name = trim( (string) $name );
		$slug = function_exists( 'sanitize_title' )
			? sanitize_title( $name )
			: strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $name ) );

		return trim( (string) $slug, '-' );
	}

	protected function require_slug( $slug, $type ) {
		if ( empty( $slug ) ) {
			WP_CLI::error( 'Please provide a valid ' . $type . ' name.' );
		}
	}

	protected function module_export_name( $slug ) {
		$name = str_replace( ' ', '', ucwords( str_replace( '-', ' ', $slug ) ) );
		$name = preg_replace( '/[^A-Za-z0-9_]/', '', $name );

		if ( ! preg_match( '/^[A-Za-z_]/', $name ) ) {
			$name = 'Module' . $name;
		}

		return $name;
	}

	protected function readable_name( $slug ) {
		return ucwords( str_replace( '-', ' ', $slug ) );
	}

	protected function theme_path( $path ) {
		return rtrim( $this->theme_dir, '/' ) . '/' . ltrim( $path, '/' );
	}

	protected function relative_to_abspath( $path ) {
		if ( defined( 'ABSPATH' ) && 0 === strpos( $path, ABSPATH ) ) {
			return str_replace( ABSPATH, '', $path );
		}

		return $path;
	}

	protected function require_file( $path, $message ) {
		if ( ! file_exists( $path ) ) {
			WP_CLI::error( $message );
		}
	}

	protected function require_missing_file( $path, $message ) {
		if ( file_exists( $path ) ) {
			WP_CLI::error( $message );
		}
	}

	protected function ensure_directory( $path ) {
		if ( is_dir( $path ) ) {
			return;
		}

		if ( ! mkdir( $path, 0755, true ) && ! is_dir( $path ) ) {
			WP_CLI::error( 'Failed to create directory: ' . $path );
		}
	}

	protected function render_boilerplate( $path, $replacements ) {
		$this->require_file( $path, "Can't find boilerplate file: " . $path );

		return str_replace( array_keys( $replacements ), array_values( $replacements ), (string) file_get_contents( $path ) );
	}

	protected function write_file( $path, $contents ) {
		if ( false === file_put_contents( $path, $contents ) ) {
			WP_CLI::error( 'Unable to write file: ' . $path );
		}

		$this->normalize_file_mode( $path );
	}

	protected function normalize_file_mode( $path ) {
		if ( file_exists( $path ) ) {
			chmod( $path, 0644 );
		}
	}

	protected function extract_acf_group_key( $acf_group ) {
		$data = json_decode( $acf_group, true );
		if ( ! empty( $data['key'] ) ) {
			return $data['key'];
		}

		return 'group_' . uniqid();
	}
}

if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'theme create', 'IW_Theme_CLI_Create' );
}
