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

/**
 * Return a validated WPML language code from the current auth request.
 *
 * Front-end auth forms post to the same admin-ajax endpoint for every language,
 * so the page language must travel with the form instead of relying on the
 * admin request URL or a browser cookie.
 */
function iw_custom_auth_request_language() {
    $requested = isset( $_POST['lang'] )
        ? sanitize_key( wp_unslash( $_POST['lang'] ) )
        : '';

    $languages = apply_filters( 'wpml_active_languages', null, [
        'skip_missing' => 0,
    ] );

    if ( $requested && ( ! is_array( $languages ) || isset( $languages[ $requested ] ) ) ) {
        return $requested;
    }

    $current = apply_filters( 'wpml_current_language', null );

    return is_string( $current ) && $current !== '' ? sanitize_key( $current ) : '';
}

/**
 * Switch WPML to the language carried by the auth form.
 */
function iw_custom_auth_switch_request_language() {
    $language = iw_custom_auth_request_language();

    if ( $language ) {
        do_action( 'wpml_switch_language', $language );
    }

    return $language;
}

/**
 * Register an editable email string with WPML String Translation and return
 * the translation for the active request language.
 */
function iw_custom_auth_email_string( $name, $value ) {
    $value = (string) $value;

    do_action(
        'wpml_register_single_string',
        'IW Custom Auth Emails',
        sanitize_key( $name ),
        $value,
        false,
        'el'
    );

    return (string) apply_filters(
        'wpml_translate_single_string',
        $value,
        'IW Custom Auth Emails',
        sanitize_key( $name )
    );
}
