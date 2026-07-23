<?php

if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_options_page(['page_title' 	=> 'IW Custom Auth', 'parent_slug' => 'options-general.php', 'menu_title' => 'IW Custom Auth', 'menu_slug' => 'iw-custom-auth', 'capability' => 'edit_posts', 'redirect' => false, 'autoload' => true, 'post_id' => 'options' ]);
}

if( function_exists('acf_add_local_field_group') ):

    if( function_exists('acf_add_local_field_group') ):

        acf_add_local_field_group(array(
            'key' => 'group_63e69ecc044e7_12',
            'title' => 'Settings',
            'fields' => array(

                array(
                    'key' => 'field_iw_custom_auth_activation_tab',
                    'label' => 'Account activation',
                    'name' => '',
                    'aria-label' => '',
                    'type' => 'tab',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'placement' => 'left',
                    'endpoint' => 0,
                ),
                array(
                    'key' => 'field_iw_custom_auth_activation_method',
                    'label' => 'Activation method',
                    'name' => 'iw_custom_auth_activation_method',
                    'type' => 'select',
                    'instructions' => 'Choose how new front-end accounts receive their activation code.',
                    'required' => 1,
                    'choices' => array(
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'email_sms' => 'Email + SMS',
                    ),
                    'default_value' => 'email',
                    'allow_null' => 0,
                    'multiple' => 0,
                    'ui' => 1,
                    'ajax' => 0,
                    'return_format' => 'value',
                    'placeholder' => '',
                ),
                array(
                    'key' => 'field_iw_custom_auth_twilio_message',
                    'label' => 'Twilio Verify',
                    'name' => '',
                    'type' => 'message',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_iw_custom_auth_activation_method',
                                'operator' => '!=',
                                'value' => 'email',
                            ),
                        ),
                    ),
                    'message' => 'Use a Twilio Verify Service. Trial accounts can only send verification SMS to verified destination numbers.',
                    'new_lines' => 'wpautop',
                    'esc_html' => 0,
                ),
                array(
                    'key' => 'field_iw_custom_auth_twilio_account_sid',
                    'label' => 'Twilio Account SID',
                    'name' => 'iw_custom_auth_twilio_account_sid',
                    'type' => 'text',
                    'instructions' => 'Required for SMS activation.',
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_iw_custom_auth_activation_method',
                                'operator' => '!=',
                                'value' => 'email',
                            ),
                        ),
                    ),
                    'wrapper' => array(
                        'width' => '50%',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'placeholder' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                ),
                array(
                    'key' => 'field_iw_custom_auth_twilio_auth_token',
                    'label' => 'Twilio Auth Token',
                    'name' => 'iw_custom_auth_twilio_auth_token',
                    'type' => 'password',
                    'instructions' => 'For production, prefer defining this in wp-config.php or an environment variable.',
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_iw_custom_auth_activation_method',
                                'operator' => '!=',
                                'value' => 'email',
                            ),
                        ),
                    ),
                    'wrapper' => array(
                        'width' => '50%',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'placeholder' => '',
                ),
                array(
                    'key' => 'field_iw_custom_auth_twilio_verify_service_sid',
                    'label' => 'Twilio Verify Service SID',
                    'name' => 'iw_custom_auth_twilio_verify_service_sid',
                    'type' => 'text',
                    'instructions' => 'Create this in Twilio Console under Verify > Services.',
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_iw_custom_auth_activation_method',
                                'operator' => '!=',
                                'value' => 'email',
                            ),
                        ),
                    ),
                    'wrapper' => array(
                        'width' => '50%',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'placeholder' => 'VAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                ),
                array(
                    'key' => 'field_iw_custom_auth_twilio_resend_cooldown',
                    'label' => 'SMS resend cooldown',
                    'name' => 'iw_custom_auth_twilio_resend_cooldown',
                    'type' => 'number',
                    'instructions' => 'Seconds before the user can request another SMS. Twilio Verify tokens are valid for 10 minutes by default.',
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_iw_custom_auth_activation_method',
                                'operator' => '!=',
                                'value' => 'email',
                            ),
                        ),
                    ),
                    'wrapper' => array(
                        'width' => '50%',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => 60,
                    'min' => 30,
                    'step' => 1,
                ),
                array(
                    'key' => 'field_iw_cashier_sms_tab',
                    'label' => 'Cashier SMS',
                    'name' => '',
                    'type' => 'tab',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'placement' => 'left',
                    'endpoint' => 0,
                ),
                array(
                    'key' => 'field_iw_cashier_twilio_message',
                    'label' => 'Twilio Messaging',
                    'name' => '',
                    'type' => 'message',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'message' => 'Used by cashier ticket delivery SMS. Fill either a Messaging Service SID or a From sender. Account SID/Auth Token can be set here or inherited from the Twilio Verify fields above.',
                    'new_lines' => 'wpautop',
                    'esc_html' => 0,
                ),
                array(
                    'key' => 'field_iw_cashier_twilio_account_sid',
                    'label' => 'Cashier Twilio Account SID',
                    'name' => 'iw_cashier_twilio_account_sid',
                    'type' => 'text',
                    'instructions' => 'Optional. Leave empty to reuse Twilio Account SID from activation settings.',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '50%',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'placeholder' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                ),
                array(
                    'key' => 'field_iw_cashier_twilio_auth_token',
                    'label' => 'Cashier Twilio Auth Token',
                    'name' => 'iw_cashier_twilio_auth_token',
                    'type' => 'password',
                    'instructions' => 'Optional. Leave empty to reuse Twilio Auth Token from activation settings.',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '50%',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'placeholder' => '',
                ),
                array(
                    'key' => 'field_iw_cashier_twilio_messaging_service_sid',
                    'label' => 'Messaging Service SID',
                    'name' => 'iw_cashier_twilio_messaging_service_sid',
                    'type' => 'text',
                    'instructions' => 'Preferred for outbound cashier SMS if available.',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '50%',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'placeholder' => 'MGxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                ),
                array(
                    'key' => 'field_iw_cashier_twilio_from',
                    'label' => 'From sender',
                    'name' => 'iw_cashier_twilio_from',
                    'type' => 'text',
                    'instructions' => 'Phone number or alphanumeric sender ID used when Messaging Service SID is empty.',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '50%',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'placeholder' => '+30...',
                ),

                array(
                    'key' => 'field_5ec53e6417b0cbd_13',
                    'label' => 'Account activation email',
                    'name' => '',
                    'aria-label' => '',
                    'type' => 'tab',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'wpml_cf_preferences' => 0,
                    'placement' => 'left',
                    'endpoint' => 0,
                ),
                array(
                    'key' => 'field_63e69ecc13091d_13',
                    'label' => 'Account activation email Cover',
                    'name' => 'iw_custom_auth_email_user_activation_cover',
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
                    'key' => 'field_63ea78fedd31da_13',
                    'label' => 'Account activation email Subject',
                    'name' => 'iw_custom_auth_email_user_activation_subject',
                    'type' => 'text',
                    'required' => 1,
                    'default_value' => 'Your [Company Name here] Account Activvation'
                ),
                array(
                    'key' => 'field_63ea78fedd31fb_13',
                    'label' => 'Account activation email Body',
                    'name' => 'iw_custom_auth_email_user_activation_text',
                    'type' => 'wysiwyg',
                    'instructions' => '[First Name] will be replaced with user\'s name and [Account Activation Link] with the actual link for the user to activate account',
                    'default_value' => '
                        Dear [First Name],
                        
                        We are thrilled to welcome you to [Company Name], and we are excited to have you on board. As a new member of our community, we wanted to extend our warmest welcome and introduce you to our platform.
                        
                        At [Company Name], we believe in [core value or mission statement], and we are committed to providing you with [benefit or value proposition]. We believe that by doing so, we can help you achieve [desired outcome or goal].
                        
                        To get started, we recommend that you [action step, such as complete your profile or explore our features]. 
                        
                        <a href="[Account Activation Link]" class="btn">ACTIVATE ACCOUNT</a>.
                        [Account Activation Code]
                        
                        You can also find helpful resources on our [support center or knowledge base], or you can reach out to our [customer support or community forum] if you have any questions or concerns.
                        
                        Thank you for choosing [Company Name]. We look forward to supporting you on your journey.
                        
                        Best regards,
                        
                        [Company Name] Team
                    ',

                ),

                array(
                    'key' => 'field_5ec53e6417b0cb_12',
                    'label' => 'Password reset email',
                    'name' => '',
                    'aria-label' => '',
                    'type' => 'tab',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'wpml_cf_preferences' => 0,
                    'placement' => 'left',
                    'endpoint' => 0,
                ),
                array(
                    'key' => 'field_63e69ecc13091b_12',
                    'label' => 'Password reset email Cover',
                    'name' => 'iw_custom_auth_email_password_reset_cover',
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
                    'key' => 'field_63ea78fedd31da12',
                    'label' => 'Password reset email Subject',
                    'name' => 'iw_custom_auth_email_password_reset_subject',
                    'type' => 'text',
                    'required' => 1,
                    'default_value' => 'Password Reset Request for Your [Company Name here] Account'
                ),
                array(
                    'key' => 'field_63ea78fedd31fa_12',
                    'label' => 'Password reset email Body',
                    'name' => 'iw_custom_auth_email_password_reset_text',
                    'type' => 'wysiwyg',
                    'instructions' => '[First Name] will be replaced with user\'s name and [Password Reset Link] with the actual link for the user to reset password',
                    'default_value' => '
                        Dear [First Name],

                        We have received a request to reset your password for your [Company Name here] account. If you did not request this, please disregard this message.
                        
                        To reset your password, please click on the following link: 
                        
                        <a href="[Password Reset Link]" class="btn">RESET PASSWORD</a>. 
                        
                        You will be prompted to create a new password. If you have any issues resetting your password or if you did not request this change, please contact our customer support team immediately.
                        
                        Thank you for choosing [Company Name here]. We take the security of your account very seriously and we appreciate your cooperation in keeping your information safe.
                        
                        Best regards,
                        
                        [Company Name here] Team
                    ',

                )
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'iw-custom-auth',
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
