<?php
/**
 * Plugin Name:     IW Form
 * Plugin URI:      https://interweaveagency.com/
 * Author:          Interweave Agency
 * Text Domain:     iw-form
 *
 * @package         Iw_Form
 */


if( is_admin() ) {
    include_once 'admin/admin.php';
    include_once 'admin/settings.php';
}



class IW_Form
{

    private static $actionName = 'fbs-form';
    private $formConfig = [];
    private $formErrors = [];
    private $sanitizedFieldValues = [];
    private $formPost;

    private $hasTurnstile = false;

    public function __construct(  )
    {
        add_action('wp_ajax_' . self::$actionName , [ $this, 'getFormConfig'] );
        add_action('wp_ajax_nopriv_' . self::$actionName , [ $this, 'getFormConfig'] );
        register_activation_hook( __FILE__, array( $this, 'activate' ) );

    }

    public function activate() {
        do_action( 'iw_form_activate' );
    }

    public function getFormConfig( ){
        if( ! isset( $_POST[ 'form-id' ] ) ){
            wp_send_json_error(['message' => "Invalid form"], 200);
        }
        $formId = (int) $_POST[ 'form-id' ];
        check_ajax_referer(self::$actionName . '-nonce-' . $formId, self::$actionName . '-nonce-' . $formId );
        $query = new WP_Query( [ 'p' => $formId, 'post_type' => 'iw-form', 'posts_per_page' => 1 ] );
        if( $query->have_posts() ){
            $this->formPost = $query->posts[0];
            $formConfig = get_post_meta( $formId, '_form_config', true);
            $formConfig = json_decode( $formConfig );
            $fieldsConfig = [];
            $filesConfig = [];
            foreach ( $formConfig as $field ){
                if( isset( $field->name ) ){
                    $rules = [];
                    if( ! empty( $field->required ) ) $rules[] = 'required';
                    if( ! empty( $field->subtype ) && $field->subtype === 'email' ) $rules[] = 'email';
                    if( ! empty( $field->validate ) ) $rules[] = $field->validate;
                    $field->rules = implode( '|', $rules );
                    if( isset( $field->values ) ){
                        $field->values = apply_filters( self::$actionName . '-field-values', $field->values, $this->formPost->ID,  (array) $field  );
                        $values = [];
                        foreach( $field->values as $key => $val ){
                            if( is_object( $val) ){
                                $values[ $val->value ] = $val->label;
                            } else {
                                $values[ $key ] = $val;
                            }
                        }
                        $field->values = $values;
                    }
                    if( $field->type === 'file' ){
                        $filesConfig[ $field->name ] = (array) $field;
                    } else {
                        $fieldsConfig[ $field->name ] = (array) $field;
                    }
                } else {
                    if( $field->type === 'turnstile' ){
                        $this->hasTurnstile = true;
                    }
                }
            }
            $formConfig = [];
            $formConfig[ 'fields' ] = $fieldsConfig;
            $formConfig[ 'files' ] = $filesConfig;
            $this->formConfig = $formConfig;
            $this->processForm();
        } else {
            wp_send_json_error(['message' => "Invalid form"], 200);
        }
    }

    public function processForm()
    {



        $fields = $this->formConfig['fields'];

        if( $this->hasTurnstile ) {
            $secretKey = get_option('iw_form_submissions_turnstile_secret_key');
            $turnstileResponse = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
            if( empty( $secretKey ) || empty( $turnstileResponse ) ){
                wp_send_json_error( [ 'message' => __('Cloudflare Turnstile is not configured.', 'iw-form') ], 400 );
            }
            $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [ 'body' => [ 'secret' => $secretKey, 'response' => $turnstileResponse ] ]);
            if (is_wp_error($response)) {
                wp_send_json_error([ 'message' => __('Failed to verify Turnstile response.', 'iw-form')], 400);
            }
            $body = wp_remote_retrieve_body($response);
            $responseKeys = json_decode($body, true);
            if ( empty( $responseKeys['success'] ) ) {
                wp_send_json_error( [ 'message' => __('Oops. Something went wrong. Cloudflare Turnstile validation failed, please try again later.', 'iw-form') ], 400);
            }
        }

        $uploadedFiles = [];
        foreach ($fields as $fieldConfig) {
            $value = isset($_POST[ $fieldConfig[ 'name' ] ]) ? $_POST[ $fieldConfig[ 'name' ] ] : '';
            $this->processFieldValue( $fieldConfig[ 'name' ], $value, $fieldConfig );
        }
        //$uploadedFiles = $this->processFileUploads(isset($formConfig['files']) ? $formConfig['files'] : [] );



        if( count( apply_filters( 'form_validation/' . $this->formPost->ID , $this->formErrors, $this->sanitizedFieldValues,  $this->formPost ) ) ){
            wp_send_json_error([ 'errors' => $this->formErrors ], 400);
        } else {
            do_action( 'iw_form_success', $this->formPost, $this->sanitizedFieldValues );
            $submissionId = $this->createEntries();
            $this->sendMail( $uploadedFiles, $submissionId );
            $this->mailchimpIntegration();
            wp_send_json_success( apply_filters( 'iw_form_success_attrs', [], $this->formPost, $this->sanitizedFieldValues ) , 200);
        }

    }

    private function getMailchimpDataCenter($api_key) {
        $parts = explode('-', $api_key);
        if (count($parts) === 2) {
            return $parts[1];
        } else {
            return false;
        }
    }

    private function mailchimpIntegration() {
        $formData = $this->sanitizedFieldValues;
        $hasMailchimpIntegration = get_field('mailchimp_integration', $this->formPost);
        $mailchimpListId = get_field('mailchimp_list_id', $this->formPost);
        $dieOnError = true;
        if( isset( $this->formConfig['fields']['mailchimp_subscription'] ) && ! isset( $formData['mailchimp_subscription'] )  ){
            return;
        } else {
            $dieOnError = false;
        }
        if ($hasMailchimpIntegration && !empty($mailchimpListId)) {
            $api_key = get_option('iw_form_submissions_mailchimp_api_key');
            $lists = get_option('iw_form_submissions_mailchimp_lists');

            if ($api_key && ! empty( $formData[ 'email_address' ] ) ) {

                $data_center = $this->getMailchimpDataCenter($api_key);
                $headers = [ 'Authorization' => 'Basic ' . base64_encode('apikey:' . $api_key), 'Content-Type'  => 'application/json' ];
                $api_url = 'https://' . $data_center . '.api.mailchimp.com/3.0/lists/' . $mailchimpListId . '/members/';

                $mergeFields = [];
                if( is_array( $lists ) ){
                    foreach ( $lists as $list ){
                        if( $list[ 'id' ] === $mailchimpListId ) {
                            $mergeFields = $list['merge_fields'];
                        }
                    }
                }

                $data = [ 'email_address' => $formData[ 'email_address' ], 'status' => 'pending', 'merge_fields' => [] ];
                foreach ( $mergeFields  as $mergeField){
                    $tag = $mergeField[ 'tag' ];
                    if( isset( $formData[ $tag ] ) ){
                        $value = $formData[ $tag ];
                        if( is_array( $value ) ) $value = implode( ', ', $value );
                        $data[ 'merge_fields' ][ $tag ] = $value;
                    }
                }
                if( empty( $data[ 'merge_fields' ] ) ) {
                    unset( $data[ 'merge_fields' ] );
                }



                $response = wp_remote_post($api_url, [ 'headers' => $headers, 'body' => json_encode( $data ) ]);

                if (is_wp_error($response)) {
                    wp_send_json_error(array('message' => __( 'Oops. Something went wrong. Please try again later.', 'iw-theme' ) ) );
                } else { // Subscription successful
                    $responseCode = wp_remote_retrieve_response_code($response);
                    $responseBody = json_decode(wp_remote_retrieve_body($response));
                    if( $responseCode === 200 ){

                    } else if( $dieOnError ) {
                        if( isset( $responseBody->title ) && $responseBody->title === 'Member Exists' ){
                            wp_send_json_error([ 'errors' => ['email_address' => __( 'Already subscribed', 'iw-theme' ) ] ],400);
                        } else {
                            wp_send_json_error([ 'errors' => ['email_address' => __( 'Oops. Something went wrong. Please try again later.', 'iw-theme' ) ] ],400);

                            // DEBUG RESPONSE
                            //wp_send_json_error([ 'errors' => ['email_address' => __( 'Oops. Something went wrong. Please try again later.', 'iw-theme' ) ], 'sent' => $data, 'r' => $responseBody, 'd' => $data, 'l' => $mailchimpListId],400);
                        }
                    } else if( $responseCode === 400 ){
                        wp_send_json_error([ 'errors' => ['email_address' => __( 'Oops. Something went wrong', 'iw-theme' ) ] ],400);
                    }
                }
            }
        }
    }

    private function processFieldValue( $fieldName, $value, $fieldConfig )
    {
        $allowMultiple = isset( $fieldConfig['multiple'] ) && $fieldConfig['multiple'] === true ;
        $allowMultiple = $allowMultiple || $fieldConfig['type'] === 'checkbox-group';




        if( is_array( $value ) ){
            if( ! isset( $fieldConfig['values'] ) || ! $allowMultiple ){
                $this->formErrors[$fieldName] = __('Invalid value', 'iw-theme');
                return;
            }
        }


        if( isset( $fieldConfig['values'] ) ) {

            if( empty( $value ) && $fieldConfig['required'] ){
                if( ! apply_filters( self::$actionName . '-field-validation', false, $fieldName, $this->formPost->ID,  (array) $fieldConfig, $this->sanitizedFieldValues  ) ){
                    $this->formErrors[$fieldName] = __('Field is required', 'iw-theme');
                    return;
                }
            }


            if( ! empty( $value ) ){
                $values = (array) $value;
                $this->sanitizedFieldValues[$fieldName] = [];
                foreach ( $values as $singleValue ){
                    $singleValue = str_replace( "\\" ,'', $singleValue );
                    if( ! array_key_exists( $singleValue, $fieldConfig['values'] ) ){
                        $this->formErrors[$fieldName] = __('Invalid value', 'iw-theme');
                        return;
                    }
                    $this->sanitizedFieldValues[$fieldName][] = strip_tags( $singleValue );
                }
            }


        } else {

            $this->sanitizedFieldValues[$fieldName] = strip_tags( $value );

            if ( isset($fieldConfig['rules']) ) {
                $rules = explode('|', $fieldConfig['rules']);
                foreach ($rules as $rule) {
                    $this->applyValidationRule($rule, $fieldName, $value );
                }
            }

        }

    }






    public function processFileUploads($filesConfig )
    {
        $uploadedFiles = [];

        foreach ($filesConfig as $fieldName => $fileConfig) {
            $allowMultiple = isset($fileConfig['multiple']) ? $fileConfig['multiple'] : false;

            if ( ! empty($_FILES[$fieldName]['name'])) {
                $files = $_FILES[$fieldName];
                if ( is_array($files['name'])  ) {
                    if( ! $allowMultiple ) {
                        $this->formErrors[ $fieldName ] =  __( 'Only one file please', 'iw-theme' );
                        continue;
                    }
                    if( empty($files['name'][0]) ){
                        if( isset($fileConfig['required']) && $fileConfig['required'] ){
                            $this->formErrors[ $fieldName ] =  __( 'File is required', 'iw-theme' );
                            continue;
                        }
                    } else {
                        if ( isset($fileConfig['max_files']) && count( $files['name'] )  > (int) $fileConfig['max_files'] ) {
                            $this->formErrors[ $fieldName ] =  sprintf( __( 'Up to %s files', 'iw-theme' ), $fileConfig['max_files'] );
                            continue;
                        }
                        for ($i = 0; $i < count($files['name']); $i++) {
                            $file = [
                                'name'     => $files['name'][$i],
                                'type'     => $files['type'][$i],
                                'tmp_name' => $files['tmp_name'][$i],
                                'error'    => $files['error'][$i],
                                'size'     => $files['size'][$i],
                            ];

                            $this->processSingleFileUpload($fieldName, $file, $fileConfig, $uploadedFiles );
                        }
                    }

                } else {
                    $this->processSingleFileUpload($fieldName, $files, $fileConfig, $uploadedFiles );
                }
            } else {
                if (isset($fileConfig['required']) && $fileConfig['required']) {
                    $this->formErrors[ $fieldName ] =  __( 'File is required', 'iw-theme' );
                }
            }
        }

        return $uploadedFiles;
    }

    private function processSingleFileUpload($fieldName, $file, $fileConfig, &$uploadedFiles )
    {
        // Validate file type
        $allowedFileTypes = isset($fileConfig['allowed_types']) ? $fileConfig['allowed_types'] : [];
        $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);

        if ( ! empty( $allowedFileTypes ) && ! in_array(strtolower($fileType), $allowedFileTypes)) {
            $this->formErrors[ $fieldName ] =  __( 'Invalid file type', 'iw-theme');
            return;
        }

        // Validate file size
        $maxFileSize = isset($fileConfig['max_size']) ? $fileConfig['max_size'] : 0; // In bytes
        if ($maxFileSize > 0 && $file['size'] > $maxFileSize) {
            $this->formErrors[ $fieldName ] =  __( 'File size exceeds the limit', 'iw-theme');
            return;
        }

        // Add file information to uploadedFiles
        $uploadedFiles[$fieldName][] = $file;
    }





    public function createEntries(){
        if( get_field( 'create_entries', $this->formPost ) ){
            $submissionId = wp_insert_post( [ 'post_type' => 'iw-form-submission', ] );
            wp_update_post(['ID' => $submissionId, 'post_title' => '#' . $submissionId]);
            update_post_meta( $submissionId, '_submission_data', $this->sanitizedFieldValues);
            update_post_meta($submissionId, '_field_metadata', $this->formConfig[ 'fields' ] );
            update_post_meta($submissionId, '_form_id', $this->formPost->ID );
            return $submissionId;
        }

        return 0;
    }


    public function sendMail(  $attachments = [], $submissionId = 0 )
    {
        if( empty( get_field( 'email_notification', $this->formPost) ) &&  empty( get_field( 'send_thank_you_email' , $this->formPost )  ) ) {
            return true;
        }

        $emailMessage = '';

        // Construct the email message


        $formDataTable = '';
        $replacements = $this->getEmailReplacements();

        foreach ( $this->sanitizedFieldValues as $fieldName => $value) {
            if( ! empty( $this->formConfig[ 'fields' ][ $fieldName ][ 'values' ] ) ){
                $configValues = $this->formConfig[ 'fields' ][ $fieldName ][ 'values' ];
                $labelValues = [];
                foreach ( (array) $value as $val ){
                    $labelValues[] = $configValues[$val];
                }
                $value = implode( ',', $labelValues);
            }


            if( ! empty( $replacements ) ) {
                foreach ($replacements as $replacement) {
                    $value = str_replace($replacement['replacement'], $replacement['text'], $value);
                }
            }

            $value = strip_tags( $value, '<a>' );

            $fieldLabel = empty( $this->formConfig[ 'fields' ][ $fieldName ][ 'label' ] ) ? '' : $this->formConfig[ 'fields' ][ $fieldName ][ 'label' ] ;
            $fieldType = $this->formConfig[ 'fields' ][ $fieldName ][ 'type' ];
            if( empty( $fieldLabel ) && ( $fieldType === 'checkbox-group' || $fieldType === 'radio-group' ) ) {
                $row = '<tr><td colspan="2" style="padding:3px 20px 3px 0; text-align:left">✓ ' . $value . '</td></tr>';
            } else {
                $row = '<tr><th style="vertical-align: top;padding: 3px 20px 3px 0; text-align: left;min-width: 120px;">' . $fieldLabel . '</th><td style="padding:3px 20px 3px 0; text-align:left">' . $value . '</td></tr>';;
            }
            $formDataTable .= $row;
        }

        if( ! empty( $formDataTable ) ){
            $formDataTable = '<table cellpadding="0" cellspacing="0">' .$formDataTable . '</table>';
        }

        $emailMessage = '<h1>' . $this->formPost->post_title .'</h1>' . $formDataTable;

        // Attach files to email

        $mailAttachments = [];
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/' . uniqid() . '/';
        wp_mkdir_p($target_dir);

        foreach ($attachments as $fieldAttachments) {
            foreach ( $fieldAttachments as $attachment ){
                $target_file = $target_dir . $attachment['name'];
                move_uploaded_file($attachment['tmp_name'], $target_file);
                $mailAttachments[]= $target_file;
            }
        }


        $headers = array('Content-Type: text/html; charset=UTF-8');


        if( ! get_field( 'variant_email_notification' , $this->formPost ) ) {
            $recipients = get_field( 'email_recipients', $this->formPost );
        } else {
            $fieldName = get_field( 'email_recipients_form_field', $this->formPost );
            $fieldValueEmails = get_field('email_recipients_form_field_value', $this->formPost);
            if( isset( $this->sanitizedFieldValues[ $fieldName ] ) ){
                $recipients = $this->getRecipientsByFieldValue( $fieldValueEmails, $this->sanitizedFieldValues[ $fieldName ] );
            }

        }

        if( empty( $recipients ) ) $recipients = get_option('admin_email');


        $subject =  get_field( 'email_subject', $this->formPost );
        if( empty( $subject ) ) $subject = $this->formPost->post_title . ' ' . __( 'Submission', 'iw-theme');

        $mailSent = true;
        if( ! empty( get_field( 'email_notification', $this->formPost) ) ) {
            $adminEmailMessage = $emailMessage;
            if ( ! empty( $submissionId ) ) {
                $submissionUrl = apply_filters( 'iw_form_submission_admin_url', get_edit_post_link( (int) $submissionId, 'raw' ), (int) $submissionId, $this->formPost );
                if ( ! empty( $submissionUrl ) ) {
                    $adminEmailMessage .= '<p><a class="btn" href="' . esc_url( $submissionUrl ) . '">' . esc_html__( 'View submission', 'iw-form' ) . '</a></p>';
                }
            }

            $mailSent = wp_mail( $recipients, $subject, $adminEmailMessage, $headers, $mailAttachments);
        }


        rmdir( $target_dir );

        if ( ! $mailSent ) {
            //wp_send_json_error(['message' => "We couldn't process the request right now. Please try again later."], 200);
        }



        if( ! empty( get_field( 'send_thank_you_email' , $this->formPost )  ) ) {
            $field = get_field( 'thank_you_email_field', $this->formPost );
            if( ! empty( $this->sanitizedFieldValues[ $field ] ) ){
                $subject =  get_field( 'thank_you_email_subject', $this->formPost );
                $to = $this->sanitizedFieldValues[ $field ];
                $emailMessage = get_field( 'thank_you_email_text', $this->formPost );
                $emailMessage = str_replace( '[data_table]', $formDataTable, $emailMessage );
                $mailSent = wp_mail( $to, $subject, $emailMessage, $headers, $mailAttachments);
                if( $mailSent ) {

                }
            }
        }

        return true;
    }

    private function getEmailReplacements()
    {
        $replacements = [
            [
                'replacement' => '{{terms-of-use}}',
                'text'        => apply_filters( 'iw_form_terms_of_use_replacement', '', $this->formPost, $this->sanitizedFieldValues ),
            ],
        ];

        return apply_filters( 'iw_form_email_replacements', $replacements, $this->formPost, $this->sanitizedFieldValues );
    }

    function getRecipientsByFieldValue($fieldValueEmails, $needle ) {
        $needle = implode( '', $needle );
        $lines = explode(PHP_EOL, $fieldValueEmails);
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $fieldValue = trim($parts[0]);
                $emails = trim($parts[1]);

                if( $needle === $fieldValue ){
                    return $emails;
                }
            }
        }
        return false;
    }



    private function applyValidationRule($rule, $fieldName, $value )
    {

        $parts = explode(':', $rule);
        $ruleName = $parts[0];

        switch ($ruleName) {

            case 'required':
                if (empty($value)) {
                    $this->formErrors[$fieldName] = __('Field is required', 'iw-theme');
                }
                break;

            case 'email':
                if ( ! empty( $value ) && ! is_email($value) ) {
                    $this->formErrors[$fieldName] = __('Not a valid email', 'iw-theme');
                }
                break;

            case 'min':
                $minLength = $parts[1];
                if (strlen($value) < $minLength) {
                    $this->formErrors[$fieldName] = __('Field length is less than required minimum', 'iw-theme');
                }
                break;

            case 'max':
                $maxLength = $parts[1];
                if (strlen($value) > $maxLength) {
                    $this->formErrors[$fieldName] = __('Field length is more than required max', 'iw-theme');
                }
                break;

            default:
                break;
        }
    }



}



new IW_Form();


/*add_action( 'wp_mail_failed', function ( $wp_error ) {
    error_log( print_r( $wp_error, true ) );
}, 10, 1 );*/

/*// Example usage
new IW_Form('careers-form', [
    'files' => [
        'attachments' => [
            'required' => false,
            'allowed_types' => ['pdf', 'doc', 'docx'],
            'max_size' => 1024000, // 1 MB in bytes
            'multiple' => true,
            'max_files' => 3
        ],
    ],
]);*/

