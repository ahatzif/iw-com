<?php


add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=iw-form',
        'Settings',
        'Settings',
        'manage_options',
        'iw_form_submissions_settings',
        function() {
            ?>
            <div class="wrap">
                <h2>Settings</h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('iw_form_submissions_mailchimp');
                    do_settings_sections('iw_form_submissions_mailchimp');
                    ?>
                </form>
                <?php
                $status = get_option('iw_form_submissions_mailchimp_api_connection_status');

                if( $status === 'CONNECTED' ){?>
                    <div>
                        <h2>Mailchimp Lists and Merge Fields</h2>
                        <style>


                            .widefat td, .widefat th{
                                padding: 8px 30px 8px 10px;
                                white-space: nowrap;
                            }
                            .wp-list-table th{
                                font-weight: bold;
                            }
                            .wp-list-table tbody td:first-child {
                                width: 1px;
                            }
                            .wp-list-table tbody td:nth-child(2) {
                                width: 1px;
                                white-space: nowrap;
                            }
                            .wp-list-table tbody td:last-child {
                                width: 100%;
                            }
                        </style>
                        <table class="wp-list-table widefat  striped">
                            <thead><tr><th>List ID</th><th>List Name</th><th>Fields</th><th>Latest Subscriber</th></tr></thead>
                            <tbody>
                            <?php
                            $lists = get_option('iw_form_submissions_mailchimp_lists');
                            if ($lists) {
                                foreach ($lists as $list) {

                                    $api_key = get_option('iw_form_submissions_mailchimp_api_key');
                                    $data_center = iw_get_mailchimp_data_center($api_key);
                                    $subscribers_url = "https://" . $data_center . ".api.mailchimp.com/3.0/lists/" . $list['id'] . "/members?count=1&sort_field=timestamp_opt&sort_dir=desc";
                                    $subscribers_response = wp_remote_get($subscribers_url, [ 'headers' => [ 'Authorization' => 'Basic ' . base64_encode('user:' . $api_key) ] ]);



                                    echo '<tr>';
                                    echo '<td>' . esc_html($list['id']) . '</td>';
                                    echo '<td>' . esc_html($list['name']) . '</td>';

                                    echo '<td>';
                                    foreach ($list['merge_fields'] as $key => $merge_field) {
                                        echo esc_html($merge_field['name']);
                                        if( $key < count( $list['merge_fields'])  - 1  ) echo ', ';
                                    }
                                    echo '</td>';
                                    echo '<td>';
                                    if (!is_wp_error($subscribers_response) && wp_remote_retrieve_response_code($subscribers_response) === 200) {
                                        $subscribers_data = json_decode(wp_remote_retrieve_body($subscribers_response), true);
                                        foreach ($subscribers_data['members'] as $subscriber) {
                                            echo esc_html($subscriber['email_address']) . '<br>';
                                        }
                                    } else {
                                        echo 'Error fetching subscribers';
                                    }
                                    echo '</td>';

                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="3">No Mailchimp lists available</td></tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
            <?php
        }
    );
});



// Mailchimp settings page
add_action('admin_init', function () {
    register_setting('iw_form_submissions_mailchimp', 'iw_form_submissions_mailchimp_api_key');
    register_setting('iw_form_submissions_mailchimp', 'iw_form_submissions_mailchimp_registration_list');
    register_setting('iw_form_submissions_mailchimp', 'iw_form_submissions_turnstile_site_key' );
    register_setting('iw_form_submissions_mailchimp', 'iw_form_submissions_turnstile_secret_key' );



    add_settings_section(
        'iw_form_submissions_turnstile_section',
        'Cloudflare Turnstile Settings',
        function () {
            echo 'Enter your Cloudflare Turnstile <a href="https://dash.cloudflare.com/sign-up?to=/:account/turnstile" target="_blank">API keys</a> below:';
        },
        'iw_form_submissions_mailchimp'
    );

    add_settings_field(
        'iw_form_submissions_turnstile_site_key',
        'Site Key',
        function () {
            $site_key = get_option('iw_form_submissions_turnstile_site_key');
            echo '<input type="text" name="iw_form_submissions_turnstile_site_key" value="' . esc_attr($site_key) . '" />';
        },
        'iw_form_submissions_mailchimp',
        'iw_form_submissions_turnstile_section'
    );

    add_settings_field(
        'iw_form_submissions_turnstile_secret_key',
        'Secret Key',
        function () {
            $secret_key = get_option('iw_form_submissions_turnstile_secret_key');
            echo '<input type="text" name="iw_form_submissions_turnstile_secret_key" value="' . esc_attr($secret_key) . '" />';
        },
        'iw_form_submissions_mailchimp',
        'iw_form_submissions_turnstile_section'
    );



    add_settings_section(
        'iw_form_submissions_mailchimp_section',
        'Mailchimp API Settings',
        function () {
            echo 'Enter your Mailchimp API key below:';
        },
        'iw_form_submissions_mailchimp'
    );

    add_settings_field(
        'iw_form_submissions_mailchimp_connection_status',
        'Status',
        function () {
            $status = get_option('iw_form_submissions_mailchimp_api_connection_status');

            if( $status === 'CONNECTED' ){ ?>
                <div style="font-size: 12px;color: #fff;padding: 5px 10px;font-weight: 700;display: inline-block;border-radius: 5px;background: #00DD00;">CONNECTED</div>
            <?php } else {?>
                <div style="font-size: 12px;color: #fff;padding: 5px 10px;font-weight: 700;display: inline-block;border-radius: 5px;background: #DD0000;">NOT CONNECTED</div>
            <?php }
        },
        'iw_form_submissions_mailchimp',
        'iw_form_submissions_mailchimp_section'
    );

    add_settings_field(
        'iw_form_submissions_mailchimp_api_key',
        'API Key',
        function () {
            $api_key = get_option('iw_form_submissions_mailchimp_api_key');
            echo '<input type="text" name="iw_form_submissions_mailchimp_api_key" value="' . esc_attr($api_key) . '" />';

        },
        'iw_form_submissions_mailchimp',
        'iw_form_submissions_mailchimp_section'
    );

    add_settings_field(
        'iw_form_submissions_mailchimp_registration_list',
        'Checkout List',
        function () {
            $lists = get_option('iw_form_submissions_mailchimp_lists');
            $registrationList = get_option('iw_form_submissions_mailchimp_registration_list');
            ?>
            <select name="iw_form_submissions_mailchimp_registration_list">
            <?php if ($lists) {
                foreach ($lists as $list) { ?>
                    <option value="<?php echo esc_html($list['id']);?>" <?php if( $registrationList === $list['id'] ) echo 'selected="selected"' ?>><?php echo esc_html($list['name']);  ?></option>
                <?php
                }
            }  ?>
            </select>
            <?php
            submit_button('Save API Key', 'primary', 'save_mailchimp_api_key');
        },
        'iw_form_submissions_mailchimp',
        'iw_form_submissions_mailchimp_section'
    );











});

add_action( 'update_option_iw_form_submissions_mailchimp_api_key', function(   ){
    $api_key = get_option('iw_form_submissions_mailchimp_api_key');
    iw_get_mailchimp_lists($api_key);
},10,2);



function iw_get_mailchimp_data_center($api_key) {
    $parts = explode('-', $api_key);
    if (count($parts) === 2) {
        return $parts[1];
    } else {
        return false;
    }
}



function iw_get_mailchimp_lists($api_key) {
    $data_center = iw_get_mailchimp_data_center($api_key);
    $url = "https://$data_center.api.mailchimp.com/3.0/lists?fields=lists.id,lists.name";
    $response = wp_remote_get( $url, array('headers' => [ 'Content-Type' => 'application/json', 'Authorization' => 'Basic ' . base64_encode("username:$api_key") ] ) );
    if (is_wp_error($response)) {
        update_option('iw_form_submissions_mailchimp_api_connection_status', 'NOT CONNECTED' );

    } else {
        $body = json_decode( wp_remote_retrieve_body($response), true);
        if( empty( $body[ 'lists' ] )){
            update_option('iw_form_submissions_mailchimp_api_connection_status', 'NOT CONNECTED' );
        } else {
            $lists = $body[ 'lists' ];
            $lists_data = array();
            foreach ($lists as $list) {
                $url = "https://$data_center.api.mailchimp.com/3.0/lists/" . $list['id']. "/merge-fields";
                $response = wp_remote_get( $url, array('headers' => [ 'Content-Type' => 'application/json', 'Authorization' => 'Basic ' . base64_encode("username:$api_key") ] ) );
                $body = json_decode( wp_remote_retrieve_body($response), true);
                $lists_data[] = array('id' => $list['id'], 'name' => $list['name'], 'merge_fields' => $body['merge_fields'] );
            }
            update_option('iw_form_submissions_mailchimp_api_connection_status', 'CONNECTED' );
            update_option('iw_form_submissions_mailchimp_lists', $lists_data );
        }
    }
}




add_filter('acf/load_field/name=mailchimp_list_id', function ( $field ) {
    if( ! empty( $lists = get_option('iw_form_submissions_mailchimp_lists') ) ){
        $field['choices'] = array();
        foreach ( $lists as $list ){
            $field['choices'][ $list['id'] ] = $list['name'];
        }
    }
    return $field;
});
