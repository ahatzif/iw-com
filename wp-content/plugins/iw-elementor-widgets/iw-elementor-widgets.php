<?php
/**
 * Plugin Name:     IW Elementor Widgets
 *
 * @package         IW_Elementor_Widgets
 */

namespace IW\ElementorWidgets;
use Elementor\Widget_Base;



if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! in_array( 'elementor/elementor.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

require_once ABSPATH . 'wp-content/plugins/elementor/elementor.php';


Class ElementorWidgets{
    public static $folder_path;
    public function __construct() {
        self::$folder_path = get_template_directory() . '/elementor-widgets';
        if ( class_exists( 'Elementor\Plugin' ) ) {
            add_action('init', [$this, 'init']);
            require_once __DIR__ . '/cli.php';
            require_once __DIR__ . '/iw-elementor-widget.php';
            register_activation_hook( __FILE__, [ $this, 'on_activation' ] );

        }
    }
    public static function on_activation() {
        if ( ! file_exists( self::$folder_path ) ) {
            if ( self::copy_folder( __DIR__ . '/elementor-widgets', self::$folder_path ) ) {
                error_log( 'Elementor Widgets folder copied successfully.' );
            } else {
                error_log( 'Failed to copy the Elementor Widgets folder.' );
            }
        }
    }
    public static function init(){
        spl_autoload_register(function ($class) {
            if( str_starts_with($class, 'IW\ElementorWidgets') ){
                $file = str_replace('IW\ElementorWidgets\\', '' , $class);
                $file = self::$folder_path  . '/' . $file . DIRECTORY_SEPARATOR . str_replace('\\', '/', $file) . '.php';
                if (file_exists($file)) require $file;
            }
        });

        add_action('elementor/elements/categories_registered', function ($elements_manager) {
            $elements_manager->add_category('iw-category', ['title' => 'Theme Widgets', 'icon'  => 'fa fa-plug']);
        });

        add_action( 'elementor/widgets/register', function( $widgets_manager ) {
            $widgetDirectories = array_diff(scandir($file = self::$folder_path  . '/' ), ['..', '.']);
            foreach ($widgetDirectories as $widgetName ) {
                $widgetName = "IW\ElementorWidgets\\" . $widgetName;
                $widgets_manager->register( new $widgetName() );
            }
        } );


    }

    private static function copy_folder( $source, $destination ) {
        if ( ! is_dir( $source ) ) {
            return false;
        }
        if ( ! file_exists( $destination ) ) {
            mkdir( $destination, 0755, true );
        }
        $dir = opendir( $source );
        while ( false !== ( $file = readdir( $dir ) ) ) {
            if ( ( $file != '.' ) && ( $file != '..' ) ) {
                $srcFile = $source . '/' . $file;
                $dstFile = $destination . '/' . $file;
                if ( is_dir( $srcFile ) ) {
                    self::copy_folder( $srcFile, $dstFile );
                } else {
                    copy( $srcFile, $dstFile );
                }
            }
        }
        closedir( $dir );
        return true;
    }
}

new ElementorWidgets();
