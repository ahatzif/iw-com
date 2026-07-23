<?php
/**
 * Plugin Name: Interweave's Site Architecture
 * Description: Registers project post types, taxonomies, and architecture metadata.
 * Author: Interweave Agency
 * Author URI: https://interweaveagency.com
 * Text Domain: iw-architecture
 * Domain Path: /languages
 * Version: 0.1.0
 *
 * @package IW_Architecture
 */

if ( ! class_exists( 'IW_Architecture' ) ) {
	class IW_Architecture {
		private static $instance = null;

		public static $post_types = [];
		public static $taxonomies = [];
		public static $post_type_names = [];
		public static $taxonomy_names = [];

		public $currentDir;

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			$this->currentDir = plugin_dir_path( __FILE__ );
			$this->register_post_types();
			$this->register_taxonomies();

			add_action( 'init', [ $this, 'collect_post_type_objects' ], 20 );
			add_action( 'init', [ $this, 'collect_taxonomy_objects' ], 20 );
		}

		public function register_post_types() {
			foreach ( $this->get_php_files( 'post-types' ) as $file_path ) {
				include_once $file_path;
				self::$post_type_names[] = $this->get_filename_without_extension( $file_path );
			}

			self::$post_type_names = $this->unique_names( self::$post_type_names );
		}

		public function register_taxonomies() {
			foreach ( $this->get_php_files( 'taxonomies' ) as $file_path ) {
				include_once $file_path;
				self::$taxonomy_names[] = $this->get_filename_without_extension( $file_path );
			}

			foreach ( $this->get_php_files( 'taxonomies/taxonomy-sync' ) as $file_path ) {
				include_once $file_path;
				self::$taxonomy_names[] = $this->get_filename_without_extension( $file_path );
			}

			self::$taxonomy_names = $this->unique_names( self::$taxonomy_names );
		}

		public function collect_post_type_objects() {
			$post_type_names = array_merge( self::$post_type_names, [ 'post', 'page' ] );
			$post_type_names = apply_filters( 'iw_architecture_post_type_names', $post_type_names );

			self::$post_type_names = $this->unique_names( $post_type_names );
			self::$post_types = [];

			foreach ( self::$post_type_names as $post_type_name ) {
				$post_type_object = get_post_type_object( $post_type_name );
				if ( $post_type_object ) {
					self::$post_types[ $post_type_name ] = $post_type_object;
				}
			}
		}

		public function collect_taxonomy_objects() {
			$taxonomy_names = apply_filters( 'iw_architecture_taxonomy_names', self::$taxonomy_names );

			self::$taxonomy_names = $this->unique_names( $taxonomy_names );
			self::$taxonomies = [];

			foreach ( self::$taxonomy_names as $taxonomy_name ) {
				$taxonomy_object = get_taxonomy( $taxonomy_name );
				if ( $taxonomy_object ) {
					self::$taxonomies[ $taxonomy_name ] = $taxonomy_object;
				}
			}
		}

		private function get_php_files( $relative_dir ) {
			$files = glob( trailingslashit( $this->currentDir . trim( $relative_dir, '/' ) ) . '*.php' );

			return is_array( $files ) ? $files : [];
		}

		private function get_filename_without_extension( $path ) {
			return pathinfo( $path, PATHINFO_FILENAME );
		}

		private function unique_names( $names ) {
			$names = array_filter( array_map( 'strval', (array) $names ) );

			return array_values( array_unique( $names ) );
		}
	}
}

IW_Architecture::instance();

$iw_architecture_post_list_fields = plugin_dir_path( __FILE__ ) . 'post-list-fields.php';
if ( file_exists( $iw_architecture_post_list_fields ) ) {
	include_once $iw_architecture_post_list_fields;
}
