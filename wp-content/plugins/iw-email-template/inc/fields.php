<?php

if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page(['page_title' 	=> 'IW Email Template', 'parent_slug' => 'options-general.php', 'menu_title' => 'IW Email Template', 'menu_slug' => 'iw-email-template', 'capability' => 'edit_posts', 'redirect' => false, 'autoload' => true, 'post_id' => 'options' ]);
}

if( function_exists('acf_add_local_field_group') ):

    if( function_exists('acf_add_local_field_group') ):

        acf_add_local_field_group(array(
            'key' => 'group_63e69ecc044e7',
            'title' => 'Email template default options',
            'fields' => array(

                array(
                    'key' => 'field_63e69ecc13069',
                    'label' => 'Logo',
                    'name' => 'iw_email_template_logo',
                    'aria-label' => '',
                    'type' => 'image',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '50%',
                        'class' => '',
                        'id' => '',
                    ),
                    'return_format' => 'url',
                    'preview_size' => 'medium',
                    'library' => 'all',
                    'min_width' => '',
                    'min_height' => '',
                    'min_size' => '',
                    'max_width' => '',
                    'max_height' => '',
                    'max_size' => '',
                    'mime_types' => 'jpg,png',
                ),
                array(
                    'key' => 'field_63e69ecc13091',
                    'label' => 'Cover',
                    'name' => 'iw_email_template_cover',
                    'aria-label' => '',
                    'type' => 'image_aspect_ratio_crop',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '50%',
                        'class' => '',
                        'id' => '',
                    ),
                    'crop_type' => 'pixel_size',
                    'aspect_ratio_width' => 600,
                    'aspect_ratio_height' => 338,
                    'return_format' => 'url',
                    'preview_size' => 'medium',
                    'library' => 'all',
                    'min_width' => 600,
                    'min_height' => 338,
                    'min_size' => '',
                    'max_width' => '',
                    'max_height' => '',
                    'max_size' => '',
                    'mime_types' => '',
                ),
                array(
                    'key' => 'field_63ea75acfecbc',
                    'label' => 'Legal Menu',
                    'name' => 'iw_email_template_legal_menu',
                    'aria-label' => '',
                    'type' => 'nav_menu',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'save_format' => 'object',
                    'container' => 'div',
                    'allow_null' => 1,
                ),
                array(
                    'key' => 'field_63ea78fedd31f',
                    'label' => 'Copyright Text',
                    'name' => 'iw_email_template_copyright_text',
                    'aria-label' => '',
                    'type' => 'wysiwyg',
                    'instructions' => '%s will be replaced with current year',
                    'required' => 1,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => 'Copyright %s - All rights reserved',
                    'maxlength' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ),
                array(
                    'key' => 'field_iw_email_footer_disclaimer',
                    'label' => 'Footer Disclaimer',
                    'name' => 'iw_email_template_disclaimer',
                    'aria-label' => '',
                    'type' => 'textarea',
                    'instructions' => 'Briefly explain why the recipient received the email.',
                    'required' => 0,
                    'wrapper' => array(
                        'width' => '100%',
                    ),
                    'default_value' => 'Λάβατε αυτό το μήνυμα επειδή έχετε λογαριασμό, πραγματοποιήσατε συναλλαγή ή ζητήσατε ενημέρωση από τον Δήμο Ιεράς Πόλης Μεσολογγίου. Πρόκειται για αυτοματοποιημένο μήνυμα· παρακαλούμε μην απαντήσετε.',
                    'rows' => 3,
                    'new_lines' => 'br',
                ),
                array(
                    'key' => 'field_iw_email_test_recipient',
                    'label' => 'Test Email Recipient',
                    'name' => 'iw_email_test_recipient',
                    'type' => 'email',
                    'instructions' => 'Enter an email address for test sends.',
                    'required' => 0,
                    'wrapper' => array(
                        'width' => '50%',
                    ),
                ),
                array(
                    'key' => 'field_iw_email_test_actions',
                    'label' => 'Test Email',
                    'name' => 'iw_email_test_actions',
                    'type' => 'message',
                    'message' => '<button type="button" class="button" id="iw-preview-email">Preview Email</button> <button type="button" class="button button-primary" id="iw-send-test-email">Send Test Email</button>',
                    'wrapper' => array(
                        'width' => '50%',
                    ),
                ),
                array(
                    'key' => 'field_iw_email_test_body',
                    'label' => 'Test Email Body',
                    'name' => 'iw_email_test_body',
                    'type' => 'textarea',
                    'instructions' => 'Optional content used by the preview and test email tools.',
                    'required' => 0,
                    'wrapper' => array(
                        'width' => '100%',
                    ),
                ),

            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'iw-email-template',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
            'show_in_rest' => 0,
        ));

    endif;

endif;
