<?php

/*
 *  GET USER PAGE BY SLUG
 */


function iw_get_user_page( $page ){
    $pages = get_posts(['post_type' => 'page', 'fields' => 'ids', 'nopaging' => true, 'meta_key' => 'iw-custom-auth-template', 'meta_value' =>  $page ]);
    return empty ( $pages ) ? false : $pages[0];
}

/*
 *  LOAD TEMPLATE PART INTO VAR
 */
function iw_load_template_part($template, $data = null ) {
    ob_start();
    get_template_part($template, false, $data );
    $var = ob_get_contents();
    ob_end_clean();
    return $var;
}
