<?php
/*
Plugin Name: Gutenberg Block Extension
Description:
Version: 1.0
Author: Your Name
Text Domain: iw-theme-gutenberg-core-blocks
*/

function iw_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'iw-editor-script',
        plugin_dir_url( __FILE__ ) . 'iw-editor.js',
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-hooks' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'iw-editor.js' )
    );
}
add_action( 'enqueue_block_editor_assets', 'iw_enqueue_block_editor_assets' );
