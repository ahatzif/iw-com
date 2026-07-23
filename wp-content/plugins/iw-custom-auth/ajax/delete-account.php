<?php
/*
 * DELETE CURRENT USER
 */


add_action( 'wp_ajax_iw-auth-delete-account', function(){
    check_ajax_referer( 'iw-auth-delete-account_delete_account', 'nonce' );

    if( current_user_can( 'manage_options' ) ){
        wp_send_json_error( ['message' => __('Ο λογαριασμός σας δεν μπορεί να διαγραφεί καθώς έχετε δικαιώματα διαχειρσ.', 'iw-theme') ] );

    } else {
        $current_user = wp_get_current_user();

        $subscriptions = wcs_get_users_subscriptions( get_current_user_id() );

        foreach ( $subscriptions as $subscription ) {
            if ( $subscription->has_status( ['active', 'on-hold', 'pending'] ) ) {
                wp_send_json_error([ 'message' => __('Έχετε ενεργή συνδρομή. Παρακαλούμε ακυρώστε τη συνδρομή πριν διαγράψετε τον λογαριασμό σας.', 'iw-theme')]);
            }
        }




        wp_delete_user( $current_user->ID, 1 );
        wp_send_json_success( [ 'message' => __( 'Ο λογαριασμός σας διαγράφηκε.', 'iw-theme' ), 'reload' => true ] );
    }
});
