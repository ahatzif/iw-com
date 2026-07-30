<?php

class WPML_File {
	private $wp_api;

	private $filesystem;

	public function __construct( ?WPML_WP_API $wp_api = null, ?WP_Filesystem_Direct $filesystem = null ) {
		if ( ! $wp_api ) {
			$wp_api = new WPML_WP_API();
		}

		$this->wp_api = $wp_api;

		if ( ! $filesystem ) {
			$filesystem = new WP_Filesystem_Direct( null );
		}

		$this->filesystem = $filesystem;
	}

	public function fix_dir_separator( $path ) {
		$directory_separator = $this->wp_api->constant( 'DIRECTORY_SEPARATOR' );

		return ( '\\' === $directory_separator ) ? str_replace( '/', '\\', $path ) : str_replace( '\\', '/', $path );
	}

	public function get_uri_from_path( $path ) {
		$base = null;

		if ( $this->wp_api->defined( 'WP_CONTENT_DIR' ) && $this->wp_api->defined( 'WP_CONTENT_URL' ) ) {
			$base_path = $this->fix_dir_separator( $this->wp_api->constant( 'WP_CONTENT_DIR' ) );

			if ( 0 === strpos( $path, $base_path ) ) {
				$base = array(
					'path' => $base_path,
					'uri'  => $this->wp_api->constant( 'WP_CONTENT_URL' ),
				);
			}
		}

		if ( ! $base ) {
			$base = array(
				'path' => $this->wp_api->constant( 'ABSPATH' ),
				'uri'  => site_url(),
			);
		}

		$base['uri']   = preg_replace( '/(^https?:)/', '', $base['uri'] );
		$relative_path = substr( $path, strlen( $base['path'] ) );
		$relative_path = str_replace( array( '/', '\\' ), '/', $relative_path );
		$relative_path = ltrim( $relative_path, '/' );

		return trailingslashit( $base['uri'] ) . $relative_path;
	}

	public function get_relative_path( $path ) {
		return str_replace( $this->fix_dir_separator( ABSPATH ), '', $this->fix_dir_separator( $path ) );
	}

	public function get_full_path( $path ) {
		return ABSPATH . $this->get_relative_path( $path );
	}

	public function file_exists( $path ) {
		return $this->filesystem->is_readable( $this->get_full_path( $path ) );
	}

	public function get_file_modified_timestamp( $path ) {
		return $this->filesystem->mtime( $this->get_full_path( $path ) );
	}
}
