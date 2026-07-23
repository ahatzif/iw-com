<?php
/*
 * EDIT ACCOUNT
 */


add_action( 'wp_ajax_iw-auth-edit-school-unit', function(){


    if( empty( $school_unit_id = $_REQUEST['user_school_autocomplete'] ) ){
        wp_send_json_error( [ 'message' => __( 'Παρακαλούμε επιλέξτε σχολείο', 'iw-theme' )] );
    } else {
        $school_unit = get_post( (int) $school_unit_id );
        if ( ! $school_unit || 'school-unit' !== $school_unit->post_type ) {
            wp_send_json_error( [ 'message' => __( 'Παρακαλούμε επιλέξτε σχολείο', 'iw-theme' )] );
        }
        if( ! empty( $school_unit_id ) ) {
            $userId = wp_get_current_user()->ID;
            update_field('is_teacher', true, 'user_' . $userId);
            update_field('user_school_unit', $school_unit_id, 'user_' . $userId);
        }
    }

    wp_send_json_success( [ 'message' => __( 'Profile updated', 'iw-theme' ), 'reload' => true ] );




});
