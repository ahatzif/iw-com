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

if ( defined('WP_INSTALLING') && WP_INSTALLING ) {
    return;
}

class IW_CookiebotCustomizer{

    public function __construct() {
        add_action( 'customize_register', [ $this, 'customize_register'] );
        add_action( 'wp_head', [ $this, 'wp_head'], -10000);
        add_action( 'wp_body_open', [ $this, 'wp_body_open'] );
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
