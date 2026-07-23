<?php

get_header();

IW_Custom_Auth::get_part('form-section-start' );

IW_Custom_Auth::get_part('form-header', [
    'title' => get_the_title(),
    'description' => apply_filters( 'the_content', get_the_content() ),
]);

IW_Custom_Auth::get_part('success-message', [
    'html' => __( 'Check your email for the confirmation link, then visit the <a href="' . get_permalink( iw_get_user_page( "login" ) ) . '"> <strong>login page</strong></a>', 'iw-theme'),
]);

IW_Custom_Auth::get_part('form-start', [ 'fields' => [
'action' => 'waction-lost-password'
]]);

IW_Custom_Auth::get_part('error-message');

IW_Custom_Auth::get_part('input', [
    'label' => __('Email', 'iw-theme'),
    'type' => 'text',
    'name' => 'user_email',
    'validate' => 'required|email'
]);

IW_Custom_Auth::get_part('button', [
    'label' => __('RESET PASSWORD', 'iw-theme'),
]);

ob_start(); ?>
<a href="<?php echo get_permalink( iw_get_user_page( 'login' ) ) ?>" class="font-bold"><?php _e( 'Already have an account?', 'iw-theme') ?></a>
<?php
$html = ob_get_contents();
ob_end_clean();

IW_Custom_Auth::get_part('form-end', [ 'html' => $html]);

IW_Custom_Auth::get_part('form-section-end' );

get_footer();





