<?php
/**
 * The 404 page template.
 */

function com_theme_404_hide_breadcrumb(): bool {
	return false;
}

function com_theme_404_remove_header_margin(): bool {
	return false;
}

add_filter( 'show_breadcrumb', 'com_theme_404_hide_breadcrumb' );
add_filter( 'header_margin', 'com_theme_404_remove_header_margin' );
get_header( null, [
	'header_theme'    => 'light',
	'page_background' => 'blue',
	'barba_namespace' => 'page-404',
	'active_nav'      => '',
] );
remove_filter( 'show_breadcrumb', 'com_theme_404_hide_breadcrumb' );
remove_filter( 'header_margin', 'com_theme_404_remove_header_margin' );

$background_id = com_theme_attachment_id( com_theme_option( '404_background', [] ) );
if ( ! $background_id && function_exists( 'get_field' ) ) {
	$background_id = com_theme_attachment_id( get_field( '404_background', 'option', false ) );
}
if ( ! $background_id ) {
	$background_id = com_theme_attachment_id( get_option( 'options_404_background', 0 ) );
	$background_id = (int) apply_filters( 'wpml_object_id', $background_id, 'attachment', true );
}
$overlay_value = com_theme_option( '404_overlay', 0 );
$overlay = min( 100, max( 0, is_numeric( $overlay_value ) ? (float) $overlay_value : 0 ) );
$custom_title = trim( (string) com_theme_option( '404_title', '' ) );
$custom_description = trim( (string) com_theme_option( '404_description', '' ) );
$custom_button_text = trim( (string) com_theme_option( '404_button_text', '' ) );
$custom_button_url = trim( (string) com_theme_option( '404_button_url', '' ) );
$custom_secondary_button_url = trim( (string) com_theme_option( '404_secondary_button_url', '' ) );

$defaults = [
	'title'       => __( 'Κάποιες διαδρομές χάνονται. Η μνήμη, ποτέ.', 'com-theme' ),
	'description' => __( 'Η σελίδα που αναζητάτε δεν υπάρχει — η ιστορία του Μεσολογγίου όμως είναι παντού γύρω μας.', 'com-theme' ),
	'button'      => __( 'Επιστροφή στην αρχική', 'com-theme' ),
];

$view_args = [
	'title'          => $custom_title !== '' ? $custom_title : $defaults['title'],
	'description'    => $custom_description !== '' ? $custom_description : $defaults['description'],
	'button_text'    => $custom_button_text !== '' ? $custom_button_text : $defaults['button'],
	'button_url'     => $custom_button_url !== '' ? $custom_button_url : home_url( '/' ),
	'secondary_button_text' => com_theme_option( '404_secondary_button_text', __( 'Ανακαλύψτε τα μουσεία', 'com-theme' ) ),
	'secondary_button_url'  => $custom_secondary_button_url !== '' ? $custom_secondary_button_url : home_url( '/#museums' ),
	'background_id'  => $background_id,
	'overlay_opacity' => $overlay / 100,
	'image_alt'      => com_theme_option( '404_image_alt', '' ),
	'edition_text'   => com_theme_option( '404_edition_text', '1826—2026' ),
	'anniversary_text' => com_theme_option( '404_anniversary_text', __( '200 ΧΡΟΝΙΑ ΑΠΟ ΤΗΝ ΕΞΟΔΟ', 'com-theme' ) ),
	'error_text'     => com_theme_option( '404_error_text', __( 'ΣΦΑΛΜΑ 404', 'com-theme' ) ),
	'image_badge'    => com_theme_option( '404_image_badge', __( 'Ι.Π. ΜΕΣΟΛΟΓΓΙΟΥ', 'com-theme' ) ),
	'image_caption'  => com_theme_option( '404_image_caption', __( 'Ένας τόπος όπου η ιστορία παραμένει ζωντανή.', 'com-theme' ) ),
	'image_mark'     => com_theme_option( '404_image_mark', '’26' ),
	'image_location' => com_theme_option( '404_image_location', __( 'ΛΙΜΝΟΘΑΛΑΣΣΑ ΜΕΣΟΛΟΓΓΙΟΥ', 'com-theme' ) ),
	'image_coordinates' => com_theme_option( '404_image_coordinates', '38.3689° N' ),
	'timeline_left_year' => com_theme_option( '404_timeline_left_year', '1826' ),
	'timeline_left_text' => com_theme_option( '404_timeline_left_text', __( 'Η Έξοδος', 'com-theme' ) ),
	'timeline_center_year' => com_theme_option( '404_timeline_center_year', '2026' ),
	'timeline_center_text' => com_theme_option( '404_timeline_center_text', __( '200 χρόνια', 'com-theme' ) ),
	'timeline_right_year' => com_theme_option( '404_timeline_right_year', __( 'ΣΗΜΕΡΑ', 'com-theme' ) ),
	'timeline_right_text' => com_theme_option( '404_timeline_right_text', __( 'Η ιστορία συνεχίζεται', 'com-theme' ) ),
];

get_template_part( 'templates/com/404/history', null, $view_args );
get_footer();
