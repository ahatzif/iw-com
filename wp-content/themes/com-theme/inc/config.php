<?php

namespace com;

class config {



    public static $menus = [
        'main' => 'Main Menu',
        'burger' => 'Burger Menu',
        'footer' => 'Footer Menu',
        'copyright' => 'Copyright Menu',
        'social' => 'Social Media',
    ];

    public static $imageSizes = [
        'com-theme-og-image'            => [ 1200, 630, true],
        'com-theme-square-thumb'        => [ 100, 100, true],
        'com-theme-desktop'             => [ 1920, 1080, true],
        'com-theme-featured-image'      => [ 1920, 660 * 1920 / 1440, true],

        'com-theme-photo-gallery'       => [ 9999999, 640 * 1920 / 1440, true],

        'com-theme-promo-half'          => [ 720  * 1920 / 1440, 660 * 1.2 * 1920 / 1440 , true],
        'com-theme-promo-full'          => [ 1920, 660 * 1.2 * 1920 / 1440 , true],

        'com-theme-post-list'           => [ 535, 800, true ],
        'com-theme-post-list-double'    => [ 1067, 800, true ],

        'com-theme-photo-wall'          => [ 289 * 1920 / 1440, 376 * 1.2 * 1920 / 1440, true ],
    ];


    public static function post_list_image_size( $postType ){
        $default = 'com-theme-post-list';
        $maybe = $default .'-' . $postType;
        return array_key_exists($maybe , self::$imageSizes ) ? $maybe : $default;
    }

}
