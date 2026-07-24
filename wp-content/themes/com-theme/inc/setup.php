<?php

//add_filter( 'locale', function() { return 'en'; });

define( 'ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS', true );
define( 'ICL_DONT_LOAD_LANGUAGES_JS', true );

remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'index_rel_link' );
remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wp_shortlink_wp_head', 10);
remove_action( 'template_redirect', 'wp_shortlink_header', 11);
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );

add_action( 'wp_print_styles', function() { wp_deregister_style( 'wp-pagenavi' );}, 100 );
add_filter('protected_title_format', function ($title) { return '%s'; });
add_filter( 'block_categories', function( $categories, $post ) { return array_merge( $categories, [[ 'slug' => 'com-theme', 'title' => __( 'Theme Blocks', 'com-theme' )]]); }, 10, 2 );
add_action( 'wp_footer', function(){ wp_deregister_script( 'wp-embed' );} );
add_action( 'admin_head', function() {
    echo '<style>.icl-st-original img { display: none;} </style>';
    echo '<style>td.media-icon img[src$=".svg"], img[src$=".svg"].attachment-post-thumbnail { width: 100% !important; height: auto !important; }</style>';
    echo '<style type="text/css">.attachment-266x266, .thumbnail img {width: 100% !important;height: auto !important;}</style>';
} );


add_filter( 'upload_mimes', function ( $mimes ){
    $mimes['svg'] = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    $mimes['dxf'] = 'image/x-dwg';
    $mimes['vcf'] = 'text/vcard';
    $mimes['vcard'] = 'text/vcard';
    return $mimes;
} );

// Add admin theme settings page
if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page(['page_title' 	=> 'Theme Settings', 'menu_title' => 'Theme Settings', 'menu_slug' => 'theme-general-settings', 'capability' => 'edit_posts', 'redirect' => false, 'autoload' => true ]);
}


add_action( 'init', function() {
    add_post_type_support( 'page', 'excerpt' );
    foreach ( get_taxonomies() as $tax ) {
        add_filter( 'manage_edit-' . $tax . '_columns', function ( $columns ) {
            if( isset( $columns['description'] ) ) unset( $columns['description'] );
            return $columns;
        });
    }
});

// Register scripts and styles
add_action( 'wp_enqueue_scripts', function() {
    wp_add_inline_script( 'jquery-migrate', 'jQuery.migrateMute = true;' );
    wp_dequeue_style('wp-block-library');
    wp_enqueue_style( 'google-fonts-css', get_theme_file_uri( '/assets/css/google-fonts.css' ), [], com_theme_asset_version( 'assets/css/google-fonts.css' ) );
    wp_enqueue_style( 'theme-fonts-css', get_theme_file_uri( '/assets/css/theme-fonts.css' ), [], com_theme_asset_version( 'assets/css/theme-fonts.css' ) );
    wp_enqueue_style( 'tailwind-css', get_theme_file_uri( '/assets/css/tailwind.css' ), [], com_theme_asset_version( 'assets/css/tailwind.css' ) );
    wp_enqueue_style( 'swiper-css', get_theme_file_uri( '/assets/css/swiper.css' ), [], com_theme_asset_version( 'assets/css/swiper.css' ) );
    wp_enqueue_style( 'fancybox-css', get_theme_file_uri( '/assets/css/fancybox.css' ), [], com_theme_asset_version( 'assets/css/fancybox.css' ) );
    wp_enqueue_script( 'index-js', get_theme_file_uri( '/assets/js/index.js' ), [], com_theme_asset_version( 'assets/js/index.js' ), true );
    wp_localize_script( 'index-js', 'THEME_OBJ', ['homeURL' => home_url(), 'ajaxURL' => admin_url( 'admin-ajax.php' )]);
    wp_localize_script( 'index-js', 'FORM_MESSAGES', [
        'fieldRequired' => __( 'Το πεδίο είναι υποχρεωτικό', 'com-theme' ),
        'emailRequired' => __( 'Συμπληρώστε ένα έγκυρο email', 'com-theme' ),
        'acceptTerms' => __( 'Παρακαλούμε αποδεχτείτε τους όρους χρήσης', 'com-theme' ),
        'acceptPolicy' => __( 'Παρακαλούμε αποδεχτείτε την πολιτική απορρήτου', 'com-theme' ),
        'maxFiles' => __( 'Max files is', 'com-theme' ),
        'minFiles' => __( 'Min files is', 'com-theme' ),
        'min'       => __( 'Το πεδίο πρέπει να περιέχει τουλάχιστον {x} χαρακτήρες' ),
        'url'     => __( "Η διεύθυνση δεν είναι έγκυρη", "com-theme"),
        'password'  => __( 'The password is invalid' ),
        'uppercase' => __( 'Το πεδίο δεν περιέχει κεφαλαίο χαρακτήρα' ),
        'lowercase' => __( 'Το πεδίο δεν περιέχει πεζό χαρακτήρα' ),
        'number'    => __( 'Το πεδίο δεν περιέχει αριθμό' ),
        'special'   => __( 'Το πεδίο δεν περιέχει ειδικό χαρακτήρο' ),
    ]);
}, 100);


add_action( 'after_setup_theme', function() {
    load_theme_textdomain( 'com-theme' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'script', 'style' ] );
    add_theme_support( 'title-tag' );
    add_theme_support( 'woocommerce' );
});


// remove comments
add_action( 'wp_before_admin_bar_render', function () { global $wp_admin_bar; $wp_admin_bar->remove_menu('comments');} );
add_action('init', function() {
    remove_post_type_support( 'post', 'comments' );
    remove_post_type_support( 'page', 'comments' );
}, 100);

// Allow SVG
add_filter( 'wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {
    global $wp_version; if( $wp_version == '4.7' || ( (float) $wp_version < 4.7 ) ) { return $data; }
    $filetype = wp_check_filetype( $filename, $mimes );
    return [ 'ext' => $filetype['ext'], 'type' => $filetype['type'], 'proper_filename' => $data['proper_filename'] ];
}, 10, 4 );


add_filter('mod_rewrite_rules', function ( $rules ) {
    $my_content = <<<EOD
\n # BEGIN My Added Content
# Protect wpconfig.php
<IfModule mod_expires.c>
  FileETag MTime Size
  AddOutputFilterByType DEFLATE text/plain text/html text/xml text/css application/xml application/xhtml+xml application/rss+xml application/javascript application/x-javascript
  ExpiresActive On
  ExpiresByType text/html "access 600 seconds"
  ExpiresByType application/xhtml+xml "access 600 seconds"
  ExpiresByType text/css "access 1 month"
  ExpiresByType text/javascript "access 1 month"
  ExpiresByType text/x-javascript "access 1 month"
  ExpiresByType application/javascript "access 1 month"
  ExpiresByType application/x-javascript "access 1 month"
  ExpiresByType application/x-shockwave-flash "access 1 month"
  ExpiresByType application/pdf "access 1 month"
  ExpiresByType image/x-icon "access 1 year"
  ExpiresByType image/jpg "access 1 year"
  ExpiresByType image/jpeg "access 1 year"
  ExpiresByType image/png "access 1 year"
  ExpiresByType image/gif "access 1 year"
  ExpiresByType image/webp "access 1 year"
  ExpiresByType video/mp4 "access 1 year"
  ExpiresDefault "access 1 month"
</IfModule>
# END My Added Content\n
EOD;
    return $my_content . $rules;
});


add_action('admin_menu', function() {
    remove_menu_page( 'edit-comments.php' );
    add_menu_page('Patterns', 'Block Patterns', 'manage_options', 'edit.php?post_type=wp_block', '', 'dashicons-admin-generic', 30);
});

add_filter( 'acf/fields/wysiwyg/toolbars' , function ( $toolbars ){
    $toolbars['Simple' ] = [];
    $toolbars['Simple' ][1] = [ 'bold' , 'italic' , 'underline', 'link' ] ;
    $toolbars['Headings'] = [];
    $toolbars['Headings'][1] = ['formatselect', 'bold', 'italic', 'underline', 'link', 'bullist'];
    $toolbars['Post Content'] = [];
    $toolbars['Post Content'][1] = [ 'formatselect', 'bold', 'italic', 'bullist', 'numlist', 'alignleft', 'aligncenter', 'alignright', 'underline', 'link', 'list', 'strikethrough', 'pastetext', 'removeformat', 'undo', 'redo' ];
    return $toolbars;
}  );

add_filter('acf/get_post_types', function ($post_types) {
    if (!in_array('wp_block', $post_types)) {
        $post_types[] = 'wp_block';
    }
    return $post_types;
}, 10, 1);

add_filter('post_date_column_time', function ($t_time, $post) {
    return get_the_modified_date(get_option('date_format'), $post) . ' ' . _e( 'at ' ) . ' ' . get_the_modified_time(get_option('time_format'), $post);
}, 10, 2);

add_filter('body_class', function ($classes) {

    if (isset($_COOKIE['layout-grid']) && $_COOKIE['layout-grid'] === 'true') { $classes[] = 'show-layout-grid'; }
    if (isset($_COOKIE['hide-admin-bar']) && $_COOKIE['hide-admin-bar'] === 'true') { $classes[] = 'hide-admin-bar'; }

    $id = is_tax() ? get_queried_object() : get_the_ID();

    if ( function_exists( 'com_theme_page_background_class' ) ) {
        $page_background_class = com_theme_page_background_class( $id );

        if ( $page_background_class !== '' ) {
            $classes[] = $page_background_class;
        }
    }

    if ( function_exists( 'com_theme_field_value' ) && com_theme_field_value( 'white_header', $id, false ) ) {
        $classes[] = 'white-header';
        $classes[] = 'text-white';
    }

    if ( function_exists( 'com_theme_hide_breadcrumb' ) && com_theme_hide_breadcrumb( $id ) ) {
        $classes[] = 'hide-breadcrumb';
    }

    if ( function_exists( 'com_theme_is_purchase_flow' ) && com_theme_is_purchase_flow() ) {
        $classes[] = 'is-woocommerce-page';
        $classes[] = 'com-purchase-flow';
    }

    return $classes;
});



function custom_admin_sidebar_width() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'wp_block' ) {
        echo '<style>
            .edit-post-sidebar {
                width: 100% !important;
            }
            .is-mode-visual  .interface-interface-skeleton__content{
                display: none !important;
            }
            .interface-interface-skeleton__sidebar{
                flex-grow: 100;
            }
            .interface-complementary-area__fill, .interface-complementary-area {
                width: 100% !important;
            }
        </style>';
    }
}
add_action('admin_head', 'custom_admin_sidebar_width');
