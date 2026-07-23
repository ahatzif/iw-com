<?php


//add_filter('wp_pagenavi_class_first', function(){ return 'hidden'; } );
//add_filter('wp_pagenavi_class_previouspostslink', function(){ return 'mr-30'; } );
//add_filter('wp_pagenavi_class_nextpostslink', function(){ return 'ml-10'; } );
//add_filter('wp_pagenavi_class_last', function(){ return 'hidden'; } );


//add_filter('wp_pagenavi_class_extend', function(){ return 'hidden'; } );


add_filter('wp_pagenavi_class_pages', function(){ return 'hidden'; } );
add_filter('wp_pagenavi_class_smaller', function () {return '';});
add_filter('wp_pagenavi_class_page', function () {return '';});
add_filter('wp_pagenavi_class_current', function () {return 'underline';});
add_filter('wp_pagenavi_class_larger', function () {return '';});



wp_pagenavi( wp_parse_args( $args, [
    'wrapper_class' => 'pt-120 md:px-inner-padding text-blue font-black text-27 space-x-20',
    'pages_text' => false,
    'first_text' => '&laquo;',
    'prev_text' => '&lsaquo;',
    'next_text' => '&rsaquo;',
    'last_text' => '&raquo;'
]) );

?>
