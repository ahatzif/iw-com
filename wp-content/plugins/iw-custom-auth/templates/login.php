<?php

get_header();

IW_Custom_Auth::get_part('form-section-start' );

IW_Custom_Auth::get_part('form-header', [
    'title' => get_the_title(),
    'description' => apply_filters( 'the_content', get_the_content() ),
]);

IW_Custom_Auth::get_part('success-message', [
    'html' => __( 'You have successfully logged in on our site. You will be redirected to your profile page.', 'iw-theme'),
]);

IW_Custom_Auth::get_part('form-start', [ 'fields' => [
    'action' => 'waction-login'
]]);

IW_Custom_Auth::get_part('error-message');

IW_Custom_Auth::get_part('input', [
    'label' => __('Email', 'iw-theme'),
    'type' => 'text',
    'name' => 'user_email',
    'validate' => 'required|email'
]);

IW_Custom_Auth::get_part('password', [
    'label' => __('Password', 'iw-theme'),
    'name' => 'user_password',
    'validate' => 'required|password',
    'instructions' => false,
]);

IW_Custom_Auth::get_part('checkbox', [
    'label' => __('Remember me', 'iw-theme'),
    'name' => 'rememberme',
    'validate' => ''
]);

IW_Custom_Auth::get_part('button', [
    'label' => __('LOG IN', 'iw-theme'),
]);

ob_start(); ?>
<a href="<?php echo get_permalink( iw_get_user_page( 'lost-password' ) ) ?>" class="font-bold"><?php _e( 'Lost your password?', 'iw-theme') ?></a> |
<a href="<?php echo get_permalink( iw_get_user_page( 'register' ) ) ?>" class="font-bold"><?php _e( "Don't have an account?", 'iw-theme') ?></a>

<?php
$html = ob_get_contents();
ob_end_clean();

IW_Custom_Auth::get_part('form-end', [ 'html' => $html]);

IW_Custom_Auth::get_part('form-section-end' );

get_footer();

