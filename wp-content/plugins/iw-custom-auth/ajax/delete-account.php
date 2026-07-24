<?php
/*
 * DELETE CURRENT USER
 */


add_action( 'wp_ajax_iw-auth-delete-account', function(){
    check_ajax_referer( 'iw-auth-delete-account_delete_account', 'nonce' );

    if( current_user_can( 'manage_options' ) ){
        wp_send_json_error( ['message' => __('Ο λογαριασμός σας δεν μπορεί να διαγραφεί επειδή διαθέτει δικαιώματα διαχειριστή.', 'iw-custom-auth') ] );

    } else {
        $current_user = wp_get_current_user();

        $subscriptions = function_exists( 'wcs_get_users_subscriptions' )
            ? wcs_get_users_subscriptions( get_current_user_id() )
            : [];

        foreach ( $subscriptions as $subscription ) {
            if ( $subscription->has_status( ['active', 'on-hold', 'pending'] ) ) {
                wp_send_json_error([ 'message' => __('Έχετε ενεργή συνδρομή. Παρακαλούμε ακυρώστε τη συνδρομή πριν διαγράψετε τον λογαριασμό σας.', 'iw-theme')]);
            }
        }




        if ( ! function_exists( 'wp_delete_user' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        $deleted = wp_delete_user( $current_user->ID, 1 );

        if ( ! $deleted ) {
            wp_send_json_error( [ 'message' => __( 'Δεν ήταν δυνατή η διαγραφή του λογαριασμού σας.', 'iw-custom-auth' ) ] );
        }

        wp_clear_auth_cookie();
        wp_send_json_success( [
            'message'  => __( 'Ο λογαριασμός σας διαγράφηκε.', 'iw-custom-auth' ),
            'redirect' => home_url( '/' ),
        ] );
    }
});
