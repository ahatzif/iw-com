<?php
/**
 * Plugin Name:     IW Custom Auth
 * Description:     Creates frontend forms for login, registration, lost password, and account flows.
 * Author:          Andreas Hatzifotis
 * Text Domain:     iw-custom-auth
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Iw_Custom_Auth
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once 'inc/fields.php';
include_once 'helpers.php';
include_once "inc/IW_Form_Validator.php";
include_once "inc/IW_Custom_Auth_Activation.php";
include_once "check-activation.php";



include_once 'ajax/login.php';
include_once 'ajax/register.php';
include_once 'ajax/activation.php';
include_once 'ajax/lost-password.php';
include_once 'ajax/reset-password.php';

include_once 'ajax/edit-account.php';
include_once 'ajax/edit-card.php';
include_once 'ajax/delete-account.php';
include_once 'ajax/change-password.php';
include_once 'ajax/edit-address.php';
include_once 'ajax/edit-school-unit.php';

//include_once 'ajax/update-meta.php';
//include_once 'ajax/upload-avatar.php';


class IW_Custom_Auth{

    public static $pages = [];
    public static $guestPages = [
        'register'              => 'Registration',
        'login'                 => 'Login',
        'lost-password'         => 'Lost Password',
        'reset-password'        => 'Reset Password',
        'activation'            => 'Account Activated',
    ];

    public static $loggedInPages = [
        'my-collections'        => 'My Collections',
        'my-favorites'          => 'My Favorites',
        'edit-profile'          => 'Edit Profile',
        'my-account'            => 'My Account',
    ];

    public function __construct(){
        self::$pages = self::$guestPages + self::$loggedInPages;
        add_filter('display_post_states', [ $this, 'mark_pages_in_admin' ], 10, 2);
        //add_filter('template_include', [ $this, 'template_include' ], 10, 2);
        add_filter('template_include', [ $this, 'pages_checks' ], 10, 2);
        add_action( 'admin_head', [ $this, 'admin_head'], 10, 2 );
        add_action( 'template_redirect', [ $this, 'redirect_woocommerce_account_guests' ], 1 );
    }

    public function admin_head()
    { ?>
    <?php }

    public function redirect_woocommerce_account_guests() {
        if ( ! function_exists( 'is_account_page' ) || is_user_logged_in() ) {
            return;
        }
        if ( is_account_page() ) {
            $redirect_to = ( is_ssl() ? 'https://' : 'http://' ) . ( $_SERVER['HTTP_HOST'] ?? '' ) . ( $_SERVER['REQUEST_URI'] ?? '' );
            $redirect_to = esc_url_raw( $redirect_to );
            $cookie_opts = ['expires' => time() + 300, 'path' => '/', 'domain' => '', 'secure' => is_ssl(), 'httponly' => false, 'samesite' => 'Lax', ];
            setcookie( 'redirect-login-modal', rawurlencode( $redirect_to ), $cookie_opts );
            setcookie( 'open-modal', 'login-modal' , $cookie_opts );
            if ( ! is_front_page() ) {
                wp_safe_redirect( home_url( '/' ) );
                exit;
            }
        }
    }

    public function mark_pages_in_admin( $states, $post ) {
        if ( ( 'page' == get_post_type( $post->ID ) )  ) {
            $key = get_post_meta( $post->ID, 'iw-custom-auth-template', true) ;
            if( $key && isset( self::$pages[ $key ] )) {
                $states[] = '<span style="color: #d63638;">' . self::$pages[ $key ] . ' Page</span>';
            }
        }
        return $states;
    }

    public static function get_page( $key ){
        $pages = get_pages(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => 'iw-custom-auth-template', 'meta_value' => $key ]);
        return empty ( $pages ) ? false : $pages[0];
    }

    public static function plugin_activated(){
        foreach ( self::$pages as $key =>  $pageTitle ) {
            if( ! self::get_page( $key ) ) {
                wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $pageTitle, 'meta_input' => [ 'iw-custom-auth-template' => $key,]]);
            }
        }

    }

    public function template_include( $template ) {
        global $post;

        if ( ! $post ) return $template;
        if( $key = get_post_meta( $post->ID, 'iw-custom-auth-template', true)  ){
            add_filter('show_breadcrumb', function() { return false; } );
            if( $themeTemplate = locate_template( array( 'iw-custom-auth/' . $key . '.php' ) )  ){
                return $themeTemplate;
            }
            $pluginTemplate = dirname(__FILE__) . '/templates/' . $key . '.php';

            if( file_exists( $pluginTemplate ) ) {
                return $pluginTemplate;
            }
        }
        return $template;
    }

    public function pages_checks($template) {
        if(str_contains($template, 'iw-custom-auth')){
            nocache_headers();
            $file = basename($template, '.php');
            if( is_user_logged_in() && ! array_key_exists( $file, self::$loggedInPages ) ) {
                $redirectPage = get_permalink( iw_get_user_page( 'my-account' ) );
                wp_redirect( $redirectPage );
                die();
            }

            if( ! is_user_logged_in() && ! array_key_exists( $file, self::$guestPages ) ) {
                wp_redirect( get_permalink( $this->get_page( 'login' ) ));
                die();
            }


            if( $file === 'activation' && ( ( isset( $_GET['user'] ) && isset( $_GET['key'] ) ) || ( isset( $_GET['id'] ) && isset( $_GET['account-activation-key'] ) ) ) ){
                $user_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : (int) $_GET['user'];
                $submitted_code = isset( $_GET['account-activation-key'] ) ? sanitize_text_field( wp_unslash( $_GET['account-activation-key'] ) ) : sanitize_text_field( wp_unslash( $_GET['key'] ) );
                $code = get_user_meta( $user_id, 'has_to_be_activated', true );
                if ( $code === $submitted_code ) {
                    delete_user_meta( $user_id, 'has_to_be_activated' );
                }
            }

            if( $file === 'reset-password' ) {
                if( ! isset( $_GET[ "key" ] ) || ! isset( $_GET[ "login" ] ) ) {
                    wp_redirect( get_permalink( $this->get_page(  'lost-password' ) ));
                    die();
                } else {
                    $user = check_password_reset_key( $_GET[ "key" ], $_GET[ "login" ] );
                    if ( ! $user || is_wp_error( $user ) ) {
                        wp_redirect( get_permalink( $this->get_page( 'login' ) ));
                        die();
                    }
                }
            }

        }

        return $template;
    }

    public static function get_part( $slug, $args = [] ) {
        if( locate_template( 'iw-custom-auth/parts/' . $slug . '.php' ) ){
            get_template_part( 'iw-custom-auth/parts/' . $slug , false, $args );
        } else {
            $partPath = dirname(__FILE__) . "/templates/parts/" .  $slug . '.php';
            if( file_exists( $partPath ) ) {
                load_template( $partPath , false,  $args);
            }

        }
    }

}

new IW_Custom_Auth();

register_activation_hook( __FILE__, array( 'IW_Custom_Auth', 'plugin_activated' ) );
add_action('nsl_register_new_user', function ($user_id, $provider = null) {
    update_user_meta( $user_id, 'iw_custom_auth_social_registration', true );

    if ( is_object( $provider ) && method_exists( $provider, 'getId' ) ) {
        update_user_meta( $user_id, 'iw_custom_auth_social_provider', sanitize_key( $provider->getId() ) );
    }
}, 10, 2);

add_action( 'admin_init', function() {
    $user = wp_get_current_user();
    $is_shop_orders_manager = in_array( 'shop_orders_manager', (array) $user->roles );
    $can_access_admin = apply_filters( 'iw_custom_auth_can_access_admin', current_user_can( 'edit_pages' ) || $is_shop_orders_manager, $user );
    if ( ! $can_access_admin && ! str_contains( $_SERVER['PHP_SELF'], 'admin-ajax.php' ) ) {
        wp_redirect( home_url() );
        exit;
    }
} );
