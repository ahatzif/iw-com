<?php
/**
 * Plugin Name:     Cookiebot Customizer
 * Plugin URI:      http://interweaveagency.com/
 * Description:     Customize Cookiebot appearance
 * Author:          INTERWEAVE AGENCY
 * Author URI:      https://interweaveagency.com/
 * Text Domain:     iw-cookiebot-customizer
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Iw_Cookiebot_Customizer
 */

namespace IW\CookiebotCustomizer;

require __DIR__ . '/vendor/autoload.php';

if ( defined('WP_INSTALLING') && WP_INSTALLING ) {
    return;
}

class IW_CookiebotCustomizer{

    public function __construct() {
        add_action( 'customize_register', [ $this, 'customize_register'] );
        add_action( 'wp_head', [ $this, 'wp_head'], -10000);
        add_action( 'wp_body_open', [ $this, 'wp_body_open'] );
        add_action('customize_save_after', [ $this, 'saveStyles'] );
        add_action('wp_head', [ $this, 'echoStyles'] );

    }

    public function compile(){
        $compiler = new \ScssPhp\ScssPhp\Compiler();
        $source_scss = plugin_dir_path( __FILE__ ) . 'src/scss/main.scss';
        $import_path = plugin_dir_path( __FILE__ ) . 'src/scss';
        $scssContents = file_get_contents($source_scss);
        $compiler->addImportPath($import_path);

        $compiler->setVariables([
            '$banner-bg-color' => get_option('iw_cookiebot_customizer_settings')["banner_bg_color"],
            '$banner-text-color' => get_option('iw_cookiebot_customizer_settings')["banner_text_color"],
            '$banner-btn-bgc-color' => get_option('iw_cookiebot_customizer_settings')["banner_btn_bgc_color"],
            '$banner-btn-text-color' => get_option('iw_cookiebot_customizer_settings')["banner_btn_text_color"],
            '$popup-bg-color' => get_option('iw_cookiebot_customizer_settings')["popup_bg_color"],
            '$popup-text-color' => get_option('iw_cookiebot_customizer_settings')["popup_text_color"],
            '$popup-btn-bgc-color' => get_option('iw_cookiebot_customizer_settings')["popup_btn_bgc_color"],
            '$popup-btn-text-color' => get_option('iw_cookiebot_customizer_settings')["popup_btn_text_color"],
        ]);
        return $compiler->compile($scssContents);
    }

    public function echoStyles(){
        if (is_customize_preview()) {
            $css = $this->compile();
            echo '<style type="text/css">' . $css . '</style>';
        }
    }


    public function saveStyles(){

        $fileName = wp_generate_password(12, false);

        if(get_option('iw_cookiebot_customizer_filename')){
            $prevFile = plugin_dir_path( __FILE__ ) . 'dist/' . get_option('iw_cookiebot_customizer_filename') . '.css';

            if (file_exists($prevFile)) {
                unlink($prevFile);
            }
            update_option('iw_cookiebot_customizer_filename', $fileName);
        }
        else {
            add_option('iw_cookiebot_customizer_filename', $fileName);
        }


        $target_css = plugin_dir_path( __FILE__ ) . 'dist/' . $fileName . '.css';
        $css = $this->compile();
        file_put_contents($target_css, $css);

    }





    public function customize_register( $wp_customize ){

        $wp_customize->add_section('iw_cookiebot_customizer_settings', ['title' => 'Cookiebot & GTM ', 'description' => '', 'priority' => -120]);

        $wp_customize->add_setting('iw_cookiebot_customizer_settings[gtm_id]', ['default' => '', 'type' => 'option']);
        $wp_customize->add_control('iw_theme_gtm_control', [
            'label' => __( 'Google Tag Manager ID', 'my_theme' ),
            'section' => 'iw_cookiebot_customizer_settings',
            'settings' => 'iw_cookiebot_customizer_settings[gtm_id]',
        ]);

        $wp_customize->add_setting('iw_cookiebot_customizer_settings[logo]', ['default' => '', 'type' => 'option']);
        $wp_customize->add_control( new \WP_Customize_Image_Control( $wp_customize, 'iw_cookiebot_customizer_settings_logo', array(
            'label' => __( 'Upload Logo', 'm1' ),
            'section' => 'iw_cookiebot_customizer_settings',
            'settings' => 'iw_cookiebot_customizer_settings[logo]',
        ) ) );

        // banner colors
        $wp_customize->add_setting( 'iw_cookiebot_customizer_settings[banner_bg_color]', array( 'type' => 'option', 'default'   => '#0B6F7A') );
        $wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'iw_cookiebot_customizer_settings_banner_bg_color', array(
            'section' => 'iw_cookiebot_customizer_settings',
            'label'   => esc_html__( 'Banner background color', 'theme' ),
            'settings' => 'iw_cookiebot_customizer_settings[banner_bg_color]',
        ) ) );


        $wp_customize->add_setting( 'iw_cookiebot_customizer_settings[banner_text_color]', array( 'type' => 'option', 'default'   => '#FFF') );
        $wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'iw_cookiebot_customizer_settings_banner_text_color', array(
            'section' => 'iw_cookiebot_customizer_settings',
            'label'   => esc_html__( 'Banner text color', 'theme' ),
            'settings' => 'iw_cookiebot_customizer_settings[banner_text_color]',
        ) ) );

        $wp_customize->add_setting( 'iw_cookiebot_customizer_settings[banner_btn_bgc_color]', array( 'type' => 'option', 'default'   => '#8addda') );
        $wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'iw_cookiebot_customizer_settings_banner_btn_bgc_color', array(
            'section' => 'iw_cookiebot_customizer_settings',
            'label'   => esc_html__( 'Banner button background color', 'theme' ),
            'settings' => 'iw_cookiebot_customizer_settings[banner_btn_bgc_color]',
        ) ) );

        $wp_customize->add_setting( 'iw_cookiebot_customizer_settings[banner_btn_text_color]', array( 'type' => 'option', 'default'   => '#0B6F7A') );
        $wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'iw_cookiebot_customizer_settings_banner_btn_text_color', array(
            'section' => 'iw_cookiebot_customizer_settings',
            'label'   => esc_html__( 'Banner button text color', 'theme' ),
            'settings' => 'iw_cookiebot_customizer_settings[banner_btn_text_color]',
        ) ) );

        // popup colors
        $wp_customize->add_setting( 'iw_cookiebot_customizer_settings[popup_bg_color]', array( 'type' => 'option', 'default'   => '#0B6F7A') );
        $wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'popup_bg_color', array(
            'section' => 'iw_cookiebot_customizer_settings',
            'label'   => esc_html__( 'Popup background color', 'theme' ),
            'settings' => 'iw_cookiebot_customizer_settings[popup_bg_color]',
        ) ) );


        $wp_customize->add_setting( 'iw_cookiebot_customizer_settings[popup_text_color]', array( 'type' => 'option', 'default'   => '#FFF') );
        $wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'popup_text_color', array(
            'section' => 'iw_cookiebot_customizer_settings',
            'label'   => esc_html__( 'Popup text color', 'theme' ),
            'settings' => 'iw_cookiebot_customizer_settings[popup_text_color]',
        ) ) );

        $wp_customize->add_setting( 'iw_cookiebot_customizer_settings[popup_btn_bgc_color]', array( 'type' => 'option', 'default'   => '#8addda') );
        $wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'popup_btn_bgc_color', array(
            'section' => 'iw_cookiebot_customizer_settings',
            'label'   => esc_html__( 'Popup button background color', 'theme' ),
            'settings' => 'iw_cookiebot_customizer_settings[popup_btn_bgc_color]',
        ) ) );

        $wp_customize->add_setting( 'iw_cookiebot_customizer_settings[popup_btn_text_color]', array( 'type' => 'option', 'default'   => '#0B6F7A') );
        $wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize, 'popup_btn_text_color', array(
            'section' => 'iw_cookiebot_customizer_settings',
            'label'   => esc_html__( 'Popup button text color', 'theme' ),
            'settings' => 'iw_cookiebot_customizer_settings[popup_btn_text_color]',
        ) ) );

    }

    public function wp_head(){

        $logo = get_option( 'iw_cookiebot_customizer_settings' )['logo'] ?? false;
        $scriptId = get_option( 'iw_cookiebot_customizer_settings' )['gtm_id'] ?? 'GTM-NW4KFS8';
        $pluginURL = plugin_dir_url( __FILE__ );
        include_once plugin_dir_path( __FILE__ ) . 'template/gtm-head.php';
    }

    public function wp_body_open(){
        $scriptId = get_option( 'iw_cookiebot_customizer_settings' )['gtm_id'] ?? 'GTM-NW4KFS8';
        include_once plugin_dir_path( __FILE__ ) . 'template/gtm-body.php';
    }
}

new IW_CookiebotCustomizer();


