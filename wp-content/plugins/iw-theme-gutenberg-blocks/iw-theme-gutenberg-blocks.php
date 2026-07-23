<?php

/**
 * Plugin Name:     Iw Theme Gutenberg Blocks
 * Plugin URI:      https://www.interweaveagency.com
 * Description:     Registers Theme blocks from the gutenberg-blocks directory in the theme
 * Author:          Interweave
 * Author URI:      https://www.interweaveagency.com
 * Text Domain:     iw-theme-gutenberg-blocks
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Iw_Theme_Gutenberg_Blocks
 */

class IW_Theme_Gutenberg_Blocks {

    private $iw_theme_blocks = [];
    private $directory = '';

    private $block_paths = [];

    public function __construct() {
        require_once 'iw-theme-block-styles.php';
        include_once 'acf-theme-block-spacing-field.php';

        $this->get_blocks_paths();

        add_action('acf/include_field_types', [$this, 'include_acf_field_types']);
        add_action('admin_head', [$this, 'add_admin_styles']);
        add_action('init', [$this, 'register_blocks']);
        add_filter('allowed_block_types_all', [$this, 'filter_allowed_blocks'], 25, 2);
        add_filter('render_block', [$this, 'filter_render_block'], 10, 3);
        add_filter('the_content', [$this, 'filter_content']);
        add_filter('acf/settings/load_json', [ $this, 'acf_load_json' ]);


        add_action('acf/render_field', function($field) {
            if ($field['type'] === 'message' && $field['menu_order'] === 0) {
                echo '<div class="acf-field-extra"><div class="expand">Expand</div><div class="collapse">Collapse</div></div>';
                $block_name = strtolower(str_replace(' ', '-', $field['label']));
                if (!empty($block_name)) {
                    $preview_path = get_template_directory() . '/gutenberg-blocks/_previews/' . $block_name . '.png';
                    $preview_url  = get_template_directory_uri() . '/gutenberg-blocks/_previews/' . $block_name . '.png';
                    if (file_exists($preview_path)) {
                        echo '<div class="acf-preview"><img src="' . esc_url($preview_url) . '" alt="Preview" style="max-width:100%;width: 100%;margin-top:10px;" /></div>';
                    }
                }
            }
        });
    }

    public function get_blocks_paths(){
        $this->directory = new RecursiveDirectoryIterator(get_template_directory() . '/gutenberg-blocks');
        $iterator = new RecursiveIteratorIterator($this->directory);
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $path_parts = pathinfo($file);
            $path_name = $file->getPathname();
            $blockData = get_file_data($path_name, [ 'title' => 'Title', 'description' => 'Description', 'icon' => 'Icon']);

            if( ! in_array( $path_parts['dirname'], $this->block_paths ) ){
                $this->block_paths[] = $path_parts['dirname'];
            }

            if (!empty($blockData['title'])) {
                $block = array_merge( $blockData, [
                    'name'            => $path_parts['filename'],
                    'path'            => str_replace(get_template_directory() . '/', '', $path_parts['dirname'] ) . '/' . $path_parts['filename'],
                    'render_template' => plugin_dir_path(__FILE__) . 'block-loader.php',
                    'category'        => 'iw-theme',
                    'mode'            => 'edit',
                    'supports'        => ['mode' => false],
                ]);

                $this->iw_theme_blocks[] = $block;
            }

        }
    }
    public function include_acf_field_types($version) {
        new ACF_Theme_Block_Spacing_Field([ 'version' => $version, 'url' => plugin_dir_url(__FILE__), 'path' => plugin_dir_path(__FILE__)]);
    }

    public function add_admin_styles() { //max-width: 100% !important; ?>
        <style>
            :root, :root :where(.editor-styles-wrapper) {
                --wp-admin-theme-color: #0b6f7a !important;
                --wp-admin-theme-color-darker-10: #0a646e !important;
                --wp-admin-theme-color-darker-20: #095962 !important;
                --wp-admin-theme-color-light: #7fd8d5 !important;
                --wp-block-synced-color: #a8396c !important;
            }
            html :where(.wp-block) {  margin: 10px auto ! important; max-width: 1024px; }

            .acf-block-fields { border: 1px solid #f8f8f8 !important; border-radius: 20px; box-shadow: 0 0 40px 5px rgb(0 0 0 / 3%); overflow: hidden; }
            .acf-switch.-on { background: var(--wp-admin-theme-color) !important; border-color: var(--wp-admin-theme-color-light) !important; }

            .acf-field-message:first-child .acf-label { margin: 0; }

            body:not(.post-type-wp_block) .acf-block-fields:not(.is-expanded) .acf-field:not(.acf-field-message:first-child), body:not(.post-type-wp_block) .acf-block-fields:not(.is-expanded) .acf-tab-wrap { display: none !important; }
            .block-editor-block-list__layout .block-editor-block-list__block.is-highlighted:after, .block-editor-block-list__layout .block-editor-block-list__block.is-highlighted~.is-multi-selected:after, .block-editor-block-list__layout .block-editor-block-list__block:not([contenteditable=true]):focus:after{ border-radius: 20px; display: none;}
            .acf-field-extra { display: none;width: 100%; }
            .acf-field-extra .collapse { display: none; }
            .is-expanded .acf-field-extra .expand { display: none; }
            .is-expanded .acf-field-extra .collapse { display: block; }
            .block-editor-block-list__block .acf-field-message:first-child .acf-field-extra { display: block; cursor: pointer; }
            .block-editor-block-list__block.is-selected .acf-field-message:first-child { background: var(--wp-admin-theme-color-light); color: #000; }

            .acf-preview { width: 100%; }

            ul.acf-radio-list li, ul.acf-checkbox-list li, .acf-block-component .acf-block-fields {font-size: 12px;}

            .acf-fields>.acf-tab-wrap .acf-block-fields { background: transparent; margin-bottom: 20px; }
            .acf-fields>.acf-tab-wrap .acf-tab-group { border: none; }
            .acf-fields>.acf-tab-wrap .acf-tab-group li.active a { background: var(--wp-admin-theme-color-light); }
            .acf-fields>.acf-tab-wrap .acf-tab-group li a { font-size: 11px; border: none; border-radius: 99999px; padding: 5px 15px; }
            .acf-switch, .acf-switch .acf-switch-slider { border-radius: 9999px; border: none; }
            .acf-switch span { font-size: 11px; }

            input[type=checkbox], input[type=radio] { border-color: #f5f5f5; }
            input[type=radio]:checked::before { background-color: var(--wp-admin-theme-color); }

            #adminmenu .wp-submenu-head, #adminmenu a.menu-top { font-size: 12px; }
            #adminmenu .wp-submenu a { font-size: 11px; }
            .acf-hl:after{
                height: 3px;
            }
        </style>
        <script>
        document.addEventListener('click', function(e) {
            if (e.target.closest('.acf-field-extra')) {
                const block = e.target.closest('.acf-block-fields');
                if (block) {
                    block.classList.toggle('is-expanded');
                }
            }
        });
        </script>
        <?php
    }

    public function register_blocks() {
        if (function_exists('acf_register_block')) {
            foreach ( $this->iw_theme_blocks as $block) {
                acf_register_block_type($block);
            }
        }
    }

    public function acf_load_json( $paths ) {
        return array_merge($paths, $this->block_paths);
    }

    public function filter_allowed_blocks($allowed_blocks, $editor_context) {
        $blocks = ['core/paragraph', 'core/block', 'core/shortcode', 'core/group'];

        foreach ($this->iw_theme_blocks as $block) {
            $blocks[] = 'acf/' . $block['name'];
        }

        return $blocks;
    }

    public function filter_render_block($block_content, $parsed, $block) {
        $block = $parsed['blockName'];

        if (!$block && empty(trim($parsed['innerHTML']))) {
            return '';
        }

        if (str_starts_with($block, "acf/")) {
            return $block_content;
        } elseif ($block === 'core/group') {
            $layout = $parsed["attrs"]["layout"];
            $blocks = [];

            foreach ($parsed['innerBlocks'] as $block) {
                $blocks[] = (new WP_Block($block))->render();
            }

            ob_start();
            get_template_part('gutenberg-blocks/_group', false, [
                'blocks'      => $blocks,
                'type'        => $layout['type'],
                'columnCount' => $layout['columnCount'],
                'attrs'       => $parsed["attrs"],
            ]);
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        } elseif ($block !== 'core/block' && $block !== 'core/list-item') {
            return $block_content;
        }

        return $block_content;
    }

    public function filter_content($content) {
        return str_replace('src="https://www.youtube.com"', 'src="https://www.youtube-nocookie.com"', $content);
    }
}

// Initialize the plugin
new IW_Theme_Gutenberg_Blocks();
?>
