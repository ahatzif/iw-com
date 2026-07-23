<?php

get_header();

IW_Custom_Auth::get_part( 'form-section-start' );

IW_Custom_Auth::get_part('form-header', [
    'title' => get_the_title(),
    'description' => apply_filters( 'the_content', get_the_content() ),
]);

IW_Custom_Auth::get_part('success-message', [
    'html' => __( 'You have successfully registered on our site. Please check your email for instructions on how to activate your account.', 'iw-theme'),
]);

IW_Custom_Auth::get_part('form-start', [ 'fields' => [
    'action' => 'waction-register'
]]);

IW_Custom_Auth::get_part('error-message');

IW_Custom_Auth::get_part('input', [
    'label' => __('First name', 'iw-theme'),
    'name' => 'first_name',
    'validate' => 'required'
]);

IW_Custom_Auth::get_part('input', [
    'label' => __('Last name', 'iw-theme'),
    'name' => 'last_name',
    'validate' => 'required'
]);

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
]);

IW_Custom_Auth::get_part('checkbox', [
    'label' => __('I agree to the <a href="#" class="font-bold" target="_blank">terms of use</a>', 'iw-theme'),
    'name' => 'agree_to_terms',
    'validate' => 'required'
]);

IW_Custom_Auth::get_part('button', [
    'label' => __('SIGN UP', 'iw-theme'),
]);

ob_start(); ?>
<a href="<?php echo get_permalink( IW_Custom_Auth::get_page( 'login' ) ) ?>" class="font-bold"><?php _e( 'Already have an account?', 'iw-theme') ?></a>
<?php
$html = ob_get_contents();
ob_end_clean();

IW_Custom_Auth::get_part('form-end', [ 'html' => $html]);

IW_Custom_Auth::get_part('form-section-end' );

get_footer();
