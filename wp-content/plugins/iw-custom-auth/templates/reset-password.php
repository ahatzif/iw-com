<?php

get_header();

IW_Custom_Auth::get_part('templates/form-parts/form-section-start');

IW_Custom_Auth::get_part('form-header', [
    'title' => get_the_title(),
    'description' => apply_filters( 'the_content', get_the_content() ),
]);

IW_Custom_Auth::get_part('success-message', [
    'html' => __( 'Your password has been changed, please visit the <a href="' . get_permalink( iw_get_user_page( 'login' ) ) . '"> login page </a>', 'iw-theme'),
]);

IW_Custom_Auth::get_part('form-start', [ 'fields' => [
    'action' => 'waction-reset-password',
    'key' => esc_attr( $_GET['key'] ),
    'login' => esc_attr( $_GET['login'] )

]]);

IW_Custom_Auth::get_part('error-message');

IW_Custom_Auth::get_part('password', [
    'label' => __('Password', 'iw-theme'),
    'name' => 'user_password',
    'validate' => 'required|password',
]);

IW_Custom_Auth::get_part('button', [
    'label' => __('RESET PASSWORD', 'iw-theme'),
]);

ob_start(); ?>
<a href="<?php echo get_permalink( iw_get_user_page( 'login' ) ) ?>" class="font-bold"><?php _e( 'Back to login', 'iw-theme') ?></a>
<?php
$html = ob_get_contents();
ob_end_clean();

IW_Custom_Auth::get_part('form-end', [ 'html' => $html]);

IW_Custom_Auth::get_part('form-section-end' );

get_footer();










