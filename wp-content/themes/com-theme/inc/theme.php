<?php

namespace com;

require_once __DIR__ . '/setup.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/video-url-parser.php';

class theme extends config {

    public function __construct() {
        add_action( 'init', [ $this, 'init'] );
        add_action( 'after_setup_theme', [ $this, 'after_setup_theme']);
        add_filter('nav_menu_css_class' , [ $this, 'is_active_class'], 10 , 2 );
        add_action( 'customize_register', [ $this, 'customize_register'] );
    }

    public static $blank_image = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\'/%3E';

    public static function blank_image( $width = 1, $height = 1 ){
        return "data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20$width%20$height'%3E%3C/svg%3E";
    }

    public function init(){

    }

    public function after_setup_theme(){
        register_nav_menus( self::$menus );
        foreach ( self::$imageSizes as $key => $sizes ){
            add_image_size( $key, $sizes[0], $sizes[1], $sizes[2] );
        }
    }

    public function customize_register( $wp_customize ){
        // TODO: Add Social Media Section
    }

    public function is_active_class($classes, $item) {
        if (in_array('current-menu-item', $classes) ){
            $classes[] = 'is-active ';
        }
        return $classes;
    }

    public static function get_last_category( $post_id ){
        $terms                = get_the_terms( $post_id, 'category' );
        $parents              = array_filter( wp_list_pluck( $terms,'parent' ) );
        $term_ids_not_parents = array_diff( wp_list_pluck( $terms,'term_id' ),  $parents );
        $terms_not_parents    = array_intersect_key( $terms, $term_ids_not_parents );
        return array_shift( $terms_not_parents );
    }

    public static function get_top_category( $cat ) {
        $topCategory = get_category( $cat );
        while ( $topCategory->parent !== 0 ) $topCategory = get_category( $par = $topCategory->parent );
        return $topCategory->term_id;
    }

    public static function remove_accents( $str ) {
        return str_replace( ['Ά', 'Έ', 'Ή', 'Ί', 'Ό', 'Ύ', 'Ώ'], ['Α', 'Ε', 'Η', 'Ι', 'Ο', 'Υ', 'Ω'], mb_strtoupper($str));
    }

    public static function get_image_sizes( $key = false ){
        $imageSizes = self::$imageSizes;
        return $key && array_key_exists( $key, $imageSizes) ? $imageSizes[ $key ] : $imageSizes;
    }

    public static function get_srcset_string( $id = false ,$args = [], $showWidth = true, $result = '' ) {
        if ( ! $id || empty( $args ) ) return '';
        foreach ( $args as $ind => $item ) {
            $widthString = $showWidth ? ' ' . self::get_image_sizes($item)[0] . 'w' . ( ( count($args) - 1 === $ind ) ? '' : ', ' ) : '';
            $result .= wp_get_attachment_image_url( $id, $item ) . $widthString;
        }
        return $result;
    }

    public static function responsive_img_tag( $id = '', $srcSize = '', $dataSizes = '', $srcsetSizes = [], $classes = '', $attributes = '', $lazy = true, $alt_attr = false ) {
        $dataSrc = self::get_srcset_string( $id, $srcSize, false );
        echo
        '<img class="' . $classes . '"' . ' ' . $attributes . ' ' .
        ' alt="' . ( ! $alt_attr ? get_post_meta( $id, '_wp_attachment_image_alt', TRUE ) : $alt_attr ) . '"' .
        ' src="'. $lazy ? PLACEHOLDER : $dataSrc . '"' .
            ' data-src="' . $dataSrc . '"' .
            ' data-sizes="' . $dataSizes . '"' .
            ' data-srcset="' . self::get_srcset_string( $id,$srcsetSizes ) . '">';
    }

    public static function menu_item_id( $id  ){
        return "";
    }

    public static function menu_item_class( $classes  ){
        if( self::$menuItemClass ) {
            $newClasses = [self::$menuItemClass];
            if( in_array( 'current-menu-item', $classes) ){
                $newClasses[] = 'is-active';
            }
            return $newClasses;
        };
        return $classes;
    }

    public static $menuItemClass = false;

    public static function menu_caps( $args ){
        if( ! empty( $args['li_class'] ) ) self::$menuItemClass = $args['li_class'];
        add_filter( 'nav_menu_css_class', [ self::class, 'menu_item_class' ] );
        add_filter( 'nav_menu_item_id', [ self::class, 'menu_item_id' ] );
        add_filter( 'nav_menu_item_title', [ self::class, 'remove_accents' ] );
        wp_nav_menu( $args );
        self::$menuItemClass = false;
        remove_filter( 'nav_menu_css_class', [ self::class, 'menu_item_class' ] );
        remove_filter( 'nav_menu_item_id', [ self::class, 'menu_item_id' ] );
        remove_filter( 'nav_menu_item_title', [ self::class, 'remove_accents' ] );

    }

    public static function menu_class( $args ){
        if( ! empty( $args['li_class'] ) ) self::$menuItemClass = $args['li_class'];
        add_filter( 'nav_menu_css_class', [ self::class, 'menu_item_class' ] );
        wp_nav_menu( $args );
        self::$menuItemClass = false;
        remove_filter( 'nav_menu_css_class', [ self::class, 'menu_item_class' ] );

    }

    public static function get_file( $fileOrId ){
        $path = is_int( $fileOrId ) ?
            wp_get_attachment_image_src( $fileOrId )[0] :
            get_stylesheet_directory() . '/' . ltrim( $fileOrId , '/' );
        return str_replace( '<!--?xml version="1.0" encoding="utf-8"?-->', "", file_get_contents( $path ) );
    }

    public static function get_page_by_template( $slug ){
        $pages = get_posts( ['post_type' => 'page', 'meta_key' => '_wp_page_template', 'meta_value' => $slug  ] );
        if( ! empty( $pages ) ) return $pages[ 0 ];
    }

    public static function send_mail( $to, $data = [] , $template = "thank-you", $subject = false ){
        if( ! $subject ) $subject = get_field( 'thank_you_subject', 'options' );
        if( ! $data[ 'subject' ] ) $data[ 'subject' ] = $subject;
        $emailHTML = self::load_template_part( "templates/email/" . $template, $data );
        return wp_mail( $to, $subject,  $emailHTML, [ 'Content-Type: text/html; charset=UTF-8' ] );
    }

    public static function load_template_part( $template_name,  $args = [] ) {

        ob_start();
        get_template_part($template_name, null , $args );
        $templateContents = ob_get_contents();
        ob_end_clean();
        return $templateContents;
    }


    public static function get_countries(){
        $lang = apply_filters( 'wpml_current_language', null );
        if( ! $lang ) $lang = 'el';
        return include __DIR__ . '/countries-' . $lang . '.php';
    }


    public static function getSVGContents( $attachmentId, $class = '' ){
        $contents = '';
        $attachment_path = get_attached_file( $attachmentId );
        if( ! empty( $attachment_path ) ){
            $contents = file_get_contents( $attachment_path );
            if( ! empty( $class ) ){
                $contents = str_replace( '<svg ', '<svg class="' . $class . '" ', $contents );
            }
        }
        return $contents;
    }

    public static function svg($fileName, $dir = '/assets/images/svg/', $echo = true)
    {
        $svg = file_get_contents(get_stylesheet_directory() . $dir . $fileName . '.svg');
        if ($echo) echo $svg;
        else return $svg;
    }



}


\class_alias( theme::class, 'IW_Theme' );
\class_alias( config::class, 'IW_Theme_Config' );

new theme();
