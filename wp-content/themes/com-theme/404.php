<?php
/**
 * The 404 page has two art-directed versions for review:
 * - ?404-version=history (default)
 * - ?404-version=playful
 */

function com_theme_404_hide_breadcrumb(): bool {
	return false;
}

function com_theme_404_remove_header_margin(): bool {
	return false;
}

$requested_version = isset( $_GET['404-version'] )
	? sanitize_key( wp_unslash( $_GET['404-version'] ) )
	: 'history';
$version = in_array( $requested_version, [ 'history', 'playful' ], true )
	? $requested_version
	: 'history';
$is_history = $version === 'history';

add_filter( 'show_breadcrumb', 'com_theme_404_hide_breadcrumb' );
add_filter( 'header_margin', 'com_theme_404_remove_header_margin' );
get_header( null, [
	'header_theme'    => $is_history ? 'light' : 'blue',
	'page_background' => $is_history ? 'blue' : 'ochre',
	'barba_namespace' => 'page-404',
	'active_nav'      => '',
] );
remove_filter( 'show_breadcrumb', 'com_theme_404_hide_breadcrumb' );
remove_filter( 'header_margin', 'com_theme_404_remove_header_margin' );

$background = (array) com_theme_option( '404_background', [] );
$background_id = absint( $background['ID'] ?? 0 );
$overlay_value = com_theme_option( '404_overlay', 0 );
$overlay = min( 100, max( 0, is_numeric( $overlay_value ) ? (float) $overlay_value : 0 ) );
$custom_title = trim( (string) com_theme_option( '404_title', '' ) );
$custom_button_text = trim( (string) com_theme_option( '404_button_text', '' ) );

$defaults = [
	'history' => [
		'title'       => __( 'Κάποιες διαδρομές χάνονται. Η μνήμη, ποτέ.', 'com-theme' ),
		'description' => __( 'Η σελίδα που αναζητάτε δεν υπάρχει — η ιστορία του Μεσολογγίου όμως είναι παντού γύρω μας.', 'com-theme' ),
		'button'      => __( 'Επιστροφή στην αρχική', 'com-theme' ),
	],
	'playful' => [
		'title'       => __( 'Χμ… μάλλον πήρατε λάθος δρόμο.', 'com-theme' ),
		'description' => __( 'Η σελίδα έφυγε βόλτα προς τη λιμνοθάλασσα. Εσείς μπορείτε να γυρίσετε στην αρχική και να συνεχίσετε την εξερεύνηση.', 'com-theme' ),
		'button'      => __( 'Πίσω στον σωστό δρόμο', 'com-theme' ),
	],
];

$view_args = [
	'title'          => $custom_title !== '' ? $custom_title : $defaults[ $version ]['title'],
	'description'    => $defaults[ $version ]['description'],
	'button_text'    => $custom_button_text !== '' ? $custom_button_text : $defaults[ $version ]['button'],
	'background_id'  => $background_id,
	'overlay_opacity' => $overlay / 100,
];

get_template_part( 'templates/com/404/' . $version, null, $view_args );
get_footer();
