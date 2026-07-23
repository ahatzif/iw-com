<?php

include_once 'acf-form-settings.php';

add_action('init', function (){
    register_post_type('iw-form', [
        'labels'             => [
            'name'               => _x('Forms', 'post type general name', 'iw-theme'),
            'singular_name'      => _x('Form', 'post type singular name', 'iw-theme'),
            'menu_name'          => _x('Forms', 'admin menu', 'iw-theme'),
            'name_admin_bar'     => _x('Form', 'add new on admin bar', 'iw-theme'),
            'add_new'            => _x('Add New Form', 'form', 'iw-theme'),
            'add_new_item'       => __('Add New Form', 'iw-theme'),
            'new_item'           => __('New Form', 'iw-theme'),
            'edit_item'          => __('Edit Form', 'iw-theme'),
            'view_item'          => __('View Form', 'iw-theme'),
            'all_items'          => __('All Forms', 'iw-theme'),
            'search_items'       => __('Search Forms', 'iw-theme'),
            'parent_item_colon'  => __('Parent Forms:', 'iw-theme'),
            'not_found'          => __('No forms found.', 'iw-theme'),
            'not_found_in_trash' => __('No forms found in Trash.', 'iw-theme'),
        ],
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title',  'custom-fields', 'revisions'),
    ]);
    register_post_type('iw-form-submission', [
        'labels'             => [
            'name'               => _x('Submissions', 'post type general name', 'iw-theme'),
            'singular_name'      => _x('Submission', 'post type singular name', 'iw-theme'),
            'menu_name'          => _x('Submissions', 'admin menu', 'iw-theme'),
            'name_admin_bar'     => _x('Submission', 'add new on admin bar', 'iw-theme'),
            'add_new'            => _x('Add New', 'submission', 'iw-theme'),
            'add_new_item'       => __('Add New Submission', 'iw-theme'),
            'new_item'           => __('New Submission', 'iw-theme'),
            'edit_item'          => __('Edit Submission', 'iw-theme'),
            'view_item'          => __('View Submission', 'iw-theme'),
            'all_items'          => __('Submissions', 'iw-theme'),
            'search_items'       => __('Search Submissions', 'iw-theme'),
            'parent_item_colon'  => __('Parent Submissions:', 'iw-theme'),
            'not_found'          => __('No submissions yet.', 'iw-theme'),
            'not_found_in_trash' => __('No submissions found in Trash.', 'iw-theme'),
        ],
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=iw-form',
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title',  'custom-fields', 'revisions'),
    ]);
});



add_action('add_meta_boxes', function(){

    add_meta_box(
        'custom_form_meta_box',
        __('Form Builder', 'iw-theme'),
        function ($post) {
            $formConfig = get_post_meta($post->ID, '_form_config', true);
            ?>
            <textarea style="display: none;" type="hidden" id="form_config" name="form_config" style="width: 100%;"><?php echo $formConfig; ?></textarea>
            <?php

            $mailchimp_list_id = get_field( 'mailchimp_list_id', $post->ID);
            $mailchimp_integration = get_field( 'mailchimp_integration', $post->ID);

            $lists = get_option('iw_form_submissions_mailchimp_lists');
            if( $mailchimp_integration && $mailchimp_list_id ){
                foreach ( $lists as $list ){
                    if( $list['id'] === $mailchimp_list_id ){
                        ?>
                        <div class="postbox">
                            <div class="inside">
                                <div class="main">
                                    <p><strong>Mailchimp List Fields</strong></p>
                                    <p>Click any field below to insert it in your form. Fields with * are required</p>
                                    <p>

                                    <div class="button button-primary"
                                         data-label="Email"
                                         data-name="email_address"
                                         data-required="1"
                                         data-type="text"
                                         data-subtype="email"
                                         data-add-form-field
                                    >Email *</div>

                                        <?php
                                        foreach ($list['merge_fields'] as $key => $merge_field) {?>
                                            <div class="button button-primary"
                                                 data-type="text"
                                                 data-label="<?php echo esc_html($merge_field['name']); ?>"
                                                 data-name="<?php echo esc_html($merge_field['tag']); ?>"
                                                 data-required="<?php echo esc_html($merge_field['required']); ?>"
                                                 data-type="<?php echo 'text' ?>"
                                                 data-add-form-field><?php echo esc_html($merge_field['name']); if( $merge_field['required'] ) echo ' *'; ?></div>
                                        <?php
                                            //echo esc_html($merge_field['name']) . ' ' . $merge_field['tag'];
                                        }
                                        ?>

                                    </p>
                                </div>
                            </div>

                        </div>
                        <?php
                        break;
                    }
                }
            }
            ?>

            <div id="build-wrap"></div>

            <?php
        },
        'iw-form',
        'normal',
        'high'
    );

    add_meta_box(
        'custom_data_meta_box',
        'Submission Data',
        function ($post) {
            // Retrieve the custom data and metadata from post meta
            $submission_data = get_post_meta($post->ID, '_submission_data', true);
            $field_metadata = get_post_meta($post->ID, '_field_metadata', true);
            $formId = get_post_meta($post->ID, '_form_id', true);
            if( $formId ){
                $form = get_post( $formId );
            }
        ?>
            <table class="form-table">
                <?php if( ! empty( $form ) ) { ?>
                <tr>
                    <th>Form</th>
                    <td><?php edit_post_link( $form->post_title, "", "", $form) ?></td>
                </tr>
                <?php } ?>
                <?php
                foreach ($submission_data as $key => $value) {
                    if (isset($field_metadata[$key])) {
                        $label = $field_metadata[$key]['label'];
                        $type = $field_metadata[$key]['type'];
                        ?>
                        <tr>
                            <th scope="row"><label for="submission_data_<?php echo ($key); ?>"><?php echo $label; ?></label></th>
                            <td>
                                <?php
                                if ($type === 'checkbox-group') {
                                    $checkbox_values = $field_metadata[$key]['values'];
                                    foreach ($checkbox_values as $checkbox_key => $checkbox_label) {
                                        echo '<label style="margin-bottom: 8px;display: inline-block;;">';
                                        echo '<input  type="checkbox" id="submission_data_' . esc_attr($key) . '_' . esc_attr($checkbox_key) . '" name="submission_data[' . esc_attr($key) . '][]" value="' .$checkbox_key  . '" ' . ( in_array($checkbox_key, (array) $value) ? 'checked="checked"' : '' ) . '>';
                                        echo strip_tags( esc_html($checkbox_label) );
                                        echo '</label><br>';
                                    }
                                } else if ($type === 'radio-group') {
                                    $radio_values = $field_metadata[$key]['values'];
                                    foreach ($radio_values as $radio_key => $radio_label) {
                                        echo '<label style="margin-bottom: 8px;display: inline-block;margin-right: 10px;">';
                                        echo '<input  type="radio" id="submission_data_' . esc_attr($key) . '_' . esc_attr($radio_key) . '" name="submission_data[' . esc_attr($key) . ']" value="' .$radio_key  . '" ' . ( $radio_key === $value[0] ? 'checked="checked"' : '' ) . '>';
                                        echo strip_tags( $radio_label );
                                        echo '</label>';
                                    }
                                    echo '<br>';
                                }
                                else if ($type === 'textarea') {
                                    echo '<textarea style="width: 100%;" id="submission_data_' . esc_attr($key) . '" name="submission_data[' . esc_attr($key) . ']">' . esc_textarea($value) . '</textarea>';
                                }
                                elseif ($type === 'select') {
                                    $options = $field_metadata[$key]['values'];
                                    $is_multiselect = isset($field_metadata[$key]['multiselect']) && $field_metadata[$key]['multiselect'];
                                    echo '<select style="width: 100%;" id="submission_data_' . esc_attr($key) . '" name="submission_data[' . esc_attr($key) . ']';
                                    if ($is_multiselect) {echo '[]" multiple'; }  echo '"';
                                    echo '>';
                                    foreach ($options as $option_key => $option_label) {
                                        echo '<option value="' . esc_attr($option_key) . '" ' . ( in_array($option_key, (array) $value) ? 'selected="selected"' : '' )  . '>' . esc_html($option_label) . '</option>';
                                    }
                                    echo '</select>';
                                } else {
                                    echo '<input  style="width: 100%;" type="text" id="submission_data_' . esc_attr($key) . '" name="submission_data[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" />';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </table>
        <?php },
        'iw-form-submission', // Replace with your custom post type
        'normal',
        'high'
    );

    remove_meta_box('wpseo_meta', 'iw-form', 'normal');
    remove_meta_box('wpseo_meta', 'iw-form-submission', 'normal');
});



add_action('save_post_iw-form', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if( isset( $_POST['form_config'] ) ){
        update_post_meta($post_id, '_form_config', $_POST['form_config'] );
    }
});

add_action('save_post_iw-form-submission', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['submission_data'])) {

        $submission_data = iw_form_recursive_sanitize_text_field($_POST['submission_data']);
        update_post_meta($post_id, '_submission_data', $submission_data);
    }
} );





add_filter('manage_iw-form-submission_posts_columns', function ($columns) {
    $new_columns = array( 'form' => 'Form');
    $position = 2;
    return array_merge(array_slice($columns, 0, $position), $new_columns, array_slice($columns, $position));

});

add_action('manage_iw-form-submission_posts_custom_column', function ($column, $post_id) {
    if ($column == 'form') {
        $form_id = get_post_meta($post_id, '_form_id', true);
        $form = get_post( $form_id );
        if( ! empty( $form ) ){
            edit_post_link( $form->post_title, "", "", $form );
        }
    }
}, -10, 2);

add_action('pre_get_posts', function ($query) {
    global $pagenow;
    if (is_admin() && $pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'iw-form-submission' && isset($_GET['filter_submissions'])) {
        $query->set('meta_query', $meta_query = [ [ 'key' => '_form_id', 'value' => sanitize_text_field($_GET['filter_submissions']), 'compare' => '=' ] ]);
    }
});




add_action('restrict_manage_posts', function() {
    global $typenow;

    if ('iw-form-submission' === $typenow) {
        $selected_value = isset($_GET['submission_form_id']) ? $_GET['submission_form_id'] : '';
        $forms = get_posts([ 'post_type' => 'iw-form', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);

        echo '<select name="submission_form_id">';
        echo '<option value="">All Forms</option>';
        foreach ($forms as $option) {
            $option_value = $option->ID;
            $option_label = get_the_title($option->ID);
            $selected = ($selected_value == $option_value) ? 'selected="selected"' : '';
            echo '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . esc_html($option_label) . '</option>';
        }
        echo '</select>';
    }
});



add_filter('parse_query', function ($query) {
    global $pagenow, $typenow;

    if ('edit.php' === $pagenow && 'iw-form-submission' === $typenow && $query->query['post_type'] === 'iw-form-submission' && isset($_GET['submission_form_id'])) {
        $value = sanitize_text_field($_GET['submission_form_id']);
        if ($value) {
            $query->query_vars['meta_key'] = '_form_id';
            $query->query_vars['meta_value'] = $value;
        }
    }
});




function iw_form_recursive_sanitize_text_field($array) {
    foreach ( $array as $key => &$value ) {
        if ( is_array( $value ) ) {
            $value = iw_form_recursive_sanitize_text_field($value);
        }
        else {
            $value = sanitize_text_field( $value );
        }
    }

    return $array;
}




add_action('admin_enqueue_scripts', function ($hook) {
    global $post_type;
    $plugin_dir = plugin_dir_url(__FILE__ );
    if ($hook == 'post-new.php' || $hook == 'post.php') {
        if ($post_type == 'iw-form') {
            wp_enqueue_script('iw-forms-builder',  'https://cdnjs.cloudflare.com/ajax/libs/jQuery-formBuilder/3.17.3/form-builder.min.js', array('jquery'), '1.0', true);
            wp_enqueue_style('iw-forms', $plugin_dir . 'css/index.css?v=' .rand(),  );
            wp_enqueue_script('iw-forms', $plugin_dir . 'js/index.js', array('jquery', 'jquery-ui-draggable', 'iw-forms-builder','iw-forms-builder-textarea'), rand(), true);
            wp_enqueue_script('iw-forms-builder-textarea', $plugin_dir . 'js/textarea.trumbowyg.js', array('jquery','iw-forms-builder'), rand(), true);
        }
    }
});










add_action('admin_enqueue_scripts', function ($hook) {
    global $typenow;
    $plugin_dir = plugin_dir_url(__FILE__ );
    if( $typenow === 'iw-form-submission' ){
        wp_enqueue_script('iw-forms-export-submissions', $plugin_dir . 'js/export-submissions.js', array('jquery' ), rand(), true);
    }
});


add_action( 'manage_posts_extra_tablenav', function( $where ) {
    global $typenow;
    if ( $where === 'top' && 'iw-form-submission' === $typenow  ) {
        $selected_value = isset($_GET['submission_form_id']) ? $_GET['submission_form_id'] : '';
        ?>
        <style>
            .spinner_ajPY{transform-origin:center;animation:spinner_AtaB .75s infinite linear}@keyframes spinner_AtaB{100%{transform:rotate(360deg)}}
            #wp-admin-export-iw-form{ display: flex; align-items: center; gap: 5px;}
            #wp-admin-export-iw-form svg{ display: none; }
            #wp-admin-export-iw-form.loading svg{ display: block; }
        </style>
        <button id="wp-admin-export-iw-form" data-id="<?php echo $selected_value; ?>" class="button button-primary" data-form-id="<?php echo $selected_value; ?>" <?php if( $selected_value === '' ) echo 'disabled title="Select a form first to export submissions"'; ?>>
            <svg width="16" fill="white" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,19a8,8,0,1,1,8-8A8,8,0,0,1,12,20Z" opacity=".25"/><path d="M10.14,1.16a11,11,0,0,0-9,8.92A1.59,1.59,0,0,0,2.46,12,1.52,1.52,0,0,0,4.11,10.7a8,8,0,0,1,6.66-6.61A1.42,1.42,0,0,0,12,2.69h0A1.57,1.57,0,0,0,10.14,1.16Z" class="spinner_ajPY"/>
            </svg>
            <span class="txt">Export Submissions</span>
        </button>
        <?php
    }
});





add_action( 'wp_ajax_export-submissions', function(){

    require 'vendor/autoload.php';


    if ( ! current_user_can('administrator') ) {
        wp_send_json_error( [ 'message' => 'You need higher privileges'] );
    }

    if( ! isset( $_POST[ 'form-id' ] ) ){
        wp_send_json_error( [ 'message' => 'Invalid form id' ] );
    }


    $formId = (int) $_POST[ 'form-id' ] ;

    $form = get_post( $formId );

    if( empty( $form ) ){
        wp_send_json_error( [ 'message' => 'Form not found' ] );
    }



    $query = new WP_Query( [ 'post_type' => 'iw-form-submission', 'posts_per_page' => -1, 'post_status'=> 'any', 'meta_query' => [ [ 'key' => '_form_id', 'value' => $formId ] ] ]);

    if ( ! $query->have_posts()) {
        wp_send_json_error( [ 'message' => 'No entries found' ] );
    }





    set_time_limit(0);

    ob_start();
    echo json_encode( [ 'success' => true, 'message' => 'Starting Export' ] );
    update_option('iw-forms-export-status', 'Starting Export');
    $size = ob_get_length();
    header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
    header("Content-Encoding: none");
    header("Content-Length: {$size}");
    header("Connection: close");
    ob_end_flush();
    @ob_flush();
    flush();
    if(session_id()) session_write_close();



    $date = date('d-m-Y-His');
    $directory =  plugin_dir_path( __FILE__ ) . 'exports/';
    $fileName  = $directory;
    $fileName .= apply_filters( 'sanitize_title', $form->post_title ) ;
    $fileName .= '-' . $date . '-' . md5( $date ) . '.xlsx';
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();


    $stringValueBinder = new \PhpOffice\PhpSpreadsheet\Cell\StringValueBinder();
    $stringValueBinder->setNumericConversion(false)->setBooleanConversion(false)->setNullConversion(false)->setFormulaConversion(false);
    \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder( $stringValueBinder );

    $formConfig = get_post_meta( $formId, '_form_config', true);
    $formConfig = json_decode($formConfig);

    $rowCount = 1;
    $cellCount = 1;
    $spreadsheet->getActiveSheet()->setCellValue([ $cellCount, $rowCount], 'ID' );
    $cellCount++;
    $spreadsheet->getActiveSheet()->setCellValue([$cellCount, $rowCount], 'Trash' );

    foreach ($formConfig as $key => $field ) {//
        if( isset( $field->name )  &&  empty( $field->dontExport ) ){
            $cellCount++;
            if( isset( $field->label ) ) {
                $spreadsheet->getActiveSheet()->setCellValue([ $cellCount, $rowCount], $field->label );
            }
        }
    }



    // Do export here..

    while ( $query->have_posts() ) {
        $query->the_post();
        $rowCount++;
        update_option('iw-forms-export-status', 'Processing ' . ( $rowCount -1 )  . '/' . $query->found_posts );
        $submissionId = get_the_id();
        $submission_data = get_post_meta( $submissionId, '_submission_data', true);
        $cellCount = 1;
        $spreadsheet->getActiveSheet()->setCellValue([$cellCount, $rowCount], $submissionId );
        $spreadsheet->getActiveSheet()->getCell([ $cellCount, $rowCount] )->getHyperlink()->setUrl( get_edit_post_link( $submissionId, false ) );
        $cellCount++;
        $spreadsheet->getActiveSheet()->setCellValue([$cellCount, $rowCount], 'Trash' );
        $spreadsheet->getActiveSheet()->getCell([ $cellCount, $rowCount] )->getHyperlink()->setUrl( str_replace( '&amp;', '&', get_delete_post_link( $submissionId) ) );



        foreach ($formConfig as $key => $field ) {
            if( isset( $field->label ) && isset( $field->name ) && empty( $field->dontExport ) ){
                $cellCount++;
                if( isset( $submission_data[ $field->name ] ) ){
                    $value = $submission_data[ $field->name ];
                    $type = $field->type;
                    if ($type === 'checkbox-group') {
                        $value = (array) $value;
                        $values = [];
                        foreach ($field->values as $option) {
                            if( in_array( $option->value, $value )  ){
                                $values []= $option->label;
                            }
                        }
                        $value = implode( ',', $values );
                    } else if ($type === 'radio-group') {
                        foreach ($field->values as $radio_option) {
                            if( $radio_option->value === $value[ 0 ] ){
                                $value = $radio_option->label;
                            }
                        }
                    }
                    elseif ($type === 'select') {
                        $value = (array) $value;
                        $values = [];
                        // TODO: WHEN $field->values is array of objects
                        $options = apply_filters( 'fbs-form-field-values', $field->values, $formId, [ 'name' => $field->name ] );

                        $optionsValues = [];
                        foreach( $options as $i => $val ){
                            if( is_object( $val) ){
                                $optionsValues[ $val->value ] = $val->label;
                            } else {
                                $optionsValues[ $i ] = $val;
                            }
                        }
                        $options = $optionsValues;

                        foreach ( $value as $val ) {
                            $key = array_search( $val , $options);
                            if( $key !== false ){
                                $values[] = $options[ $key ];
                            }
                        }
                        $value = implode( ',', $values );
                    }


                    if( ! empty( $value ) ){
                        $value = str_replace( '=', '', $value );
                        $spreadsheet->getActiveSheet()->setCellValue([ $cellCount, $rowCount], (string) strip_tags( $value ) );
                    }
                }
            }
        }
    } wp_reset_postdata();

    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
    $writer->save( $fileName );
    delete_option('iw-forms-export-status' );

} );




add_action('admin_notices', function () {
    // Directory to scan for .xlsx files
    $directory = plugin_dir_path(__FILE__) . 'exports/';
    // URL path to the directory
    $directory_url = plugin_dir_url(__FILE__) . 'exports/';

    // Ensure we are on the correct admin screen
    if (get_current_screen()->id == 'edit-iw-form-submission') {
        // Check if the directory exists
        if (is_dir($directory)) {
            // Scan the directory for .xlsx files
            $files = glob($directory . '*.xlsx');

            // Start the output for the notice
            if (!empty($files)) {
                ?>
                    <style>
                        .delete-file-button{
                            display: inline-flex;;
                            align-items: center;
                            justify-content: center;
                            border-radius: 9999px;
                            width: 15px;
                            height: 15px;
                            background: #2271b1;
                            cursor: pointer;
                        }
                        .delete-file-button svg{
                            width: 10px;
                            height: 10px;
                            fill: white
                        }
                    </style>
                <div class="notice notice-info">
                    <h2>Exports</h2>
                    <?php  if( str_starts_with( $status = get_option('iw-forms-export-status' ), 'Processing ', ) ){?>
                        <div id="status-text" style="color: red;"><?php echo $status; ?></div>
                    <?php } ?>
                    <ul id="xlsx-files-list">
                        <?php
                        // Loop through the files and list them
                        foreach ($files as $file) {
                            // Get the file name
                            $filename = basename($file);
                            // Construct the download URL
                            $file_url = $directory_url . $filename;
                            ?>
                            <li data-file="<?php echo esc_attr($filename); ?>">
                                <span class="delete-file-button" data-file="<?php echo esc_attr($filename); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M19.77 18.36l-6.36-6.36 6.36-6.36-1.41-1.41-6.36 6.36-6.36-6.36-1.41 1.41 6.36 6.36-6.36 6.36 1.41 1.41 6.36-6.36 6.36 6.36 1.41-1.41z"/></svg>
                                </span>
                                <a href="<?php echo esc_url($file_url); ?>" download><?php echo esc_html($filename); ?></a>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </div>
                <script>
                    jQuery(document).ready(function($) {

                        function updateStatusText() {
                            $.ajax({
                                type: 'GET',
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                data: { action: 'get_export_status' },
                                success: function(response) {
                                    if( response !== $('#status-text').text() ){
                                        setTimeout(updateStatusText, 500);
                                    }
                                    $('#status-text').html(response);

                                },
                                error: function(xhr, status, error) {
                                    console.error(xhr.responseText);
                                    // Handle error if needed
                                }
                            });
                        }

                        if( $('#status-text') ){
                            setTimeout(updateStatusText, 500);
                        }


                        $('#xlsx-files-list').on('click', '.delete-file-button', function(e) {
                            e.preventDefault();
                            var filename = $(this).data('file');
                            var security = '<?php echo wp_create_nonce('delete_xlsx_file_nonce'); ?>';
                            if (confirm('Are you sure you want to delete this file?')) {
                                $.ajax({
                                    type: 'POST',
                                    url: ajaxurl,
                                    data: { action: 'delete_xlsx_file', security: security, filename: filename },
                                    success: function(response) {
                                        if (response.success) {
                                            $('li[data-file="' + filename + '"]').remove(); // Remove from UI
                                        } else {
                                            alert('Error: ' + response.data); // Error message
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        alert('Error deleting file. Please try again.'); // Generic error message
                                    }
                                });
                            }
                        });
                    });

                </script>
                <?php
            }
        }
    }
});

add_action('wp_ajax_delete_xlsx_file', function () {
    check_ajax_referer('delete_xlsx_file_nonce', 'security');
    $directory = plugin_dir_path(__FILE__) . 'exports/';
    $filename = sanitize_file_name($_POST['filename']);
    $file_path = $directory . $filename;
    if (file_exists($file_path)) {
        unlink($file_path);
        wp_send_json_success("File '$filename' deleted successfully.");
    } else {
        wp_send_json_error("Error: File '$filename' not found.");
    }
});

add_action('wp_ajax_get_export_status', function () {
    echo get_option('iw-forms-export-status', '');
    die();
});
