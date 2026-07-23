<?php
/**
 * Plugin Name:     IW Under Construction
 * Plugin URI:      https://interweaveagency.com/
 * Description:     Displays under construction page for not logged in users
 *
 * @package         IW_Under_Construction
 */


if ( ! defined( 'ABSPATH' ) ) exit;

class IW_Under_Construction {
    private static $instance = null;
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __construct() {
        add_action( 'acf/init', [ $this, 'register_acf_options' ] );
        add_action( 'template_redirect', [ $this, 'maybe_show_under_construction' ], 30 );

    }

    public function register_acf_options() {

        if ( ! function_exists( 'acf_add_options_page' ) ) {
            return; // ACF δεν είναι ενεργό
        }

        // 1. Options Page
        acf_add_options_page( [
            'page_title' => __( 'Under Construction', 'under-construction' ),
            'menu_title' => __( 'Under Construction', 'under-construction' ),
            'menu_slug'  => 'uc-settings',
            'capability' => 'manage_options',
            'redirect'   => false,
            'icon_url'   => 'dashicons-hammer',
        ] );

        // 2. Πεδίο-group (δηλώνεται δυναμικά ώστε να μη χρειάζεσαι export JSON)
        if ( function_exists( 'acf_add_local_field_group' ) ) {

            acf_add_local_field_group( [
                'key'                   => 'group_uc_settings',
                'title'                 => 'Under Construction Settings',
                'fields'                => [
                    [
                        'key'   => 'field_uc_toggle',
                        'label' => __( 'Enable Under Construction', 'under-construction' ),
                        'name'  => 'under_construction',
                        'type'  => 'true_false',
                        'ui'    => 1,
                    ],
                    [
                        'key'   => 'field_uc_logo',
                        'label' => __( 'Logo', 'under-construction' ),
                        'name'  => 'uc_logo',
                        'type'  => 'image',
                        'return_format' => 'array',
                        'preview_size'  => 'medium',
                        'library'       => 'all',
                    ],
                    [
                        'key'   => 'field_uc_text',
                        'label' => __( 'Message', 'under-construction' ),
                        'name'  => 'uc_text',
                        'type'  => 'textarea',
                        'rows'  => 4,
                    ],
                ],
                'location'              => [
                    [
                        [
                            'param'    => 'options_page',
                            'operator' => '==',
                            'value'    => 'uc-settings',
                        ],
                    ],
                ],
                'position'              => 'acf_after_title',
                'style'                 => 'seamless',
                'active'                => true,
            ] );
        }
    }
    public function maybe_show_under_construction() {
        if ( is_user_logged_in() || is_admin() || defined( 'DOING_AJAX' ) || defined( 'REST_REQUEST' ) || preg_match( '/\/wp-login\.php$/', wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) {
            return;
        }
        $is_enabled = function_exists( 'get_field' ) ? get_field( 'under_construction', 'option' ) : false;
        if ( ! $is_enabled ) { return; }
        status_header( 503 );
        nocache_headers();
        if ( locate_template( 'templates/under-construction.php', true, true ) ) {
            exit;
        }

        $logo_id = get_field( 'uc_logo', 'option' );
        $uc_text = get_field( 'uc_text', 'option' );
        get_header();
        echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;padding:20px;text-align:center;">';


        if ( $logo_id ) {
            echo wp_get_attachment_image( $logo_id, 'medium', false, [ 'style' => 'max-width:220px;height:auto;margin-bottom:32px;',  'alt' => esc_attr__( 'Logo', 'under-construction' ) ] );
        }

        if ( $uc_text ) {
            echo '<p style="font-size:18px;line-height:1.5;max-width:600px;">' . wp_kses_post( nl2br( $uc_text ) ) . '</p>';
        } else {
            echo '<h1>' . esc_html__( 'Το site είναι προσωρινά υπό κατασκευή.', 'under-construction' ) . '</h1>';
        }
        echo '</div>';
        get_footer();
        exit;
    }
}
IW_Under_Construction::instance();
