<?php
/*
 * UPLOAD AVATAR
 */
ini_set('memory_limit', '-1');

add_action( 'wp_ajax_iw-auth-upload-avatar', function(){

    $current_user = wp_get_current_user();

    if( ! isset( $_REQUEST[ 'user_avatar' ] ) ) wp_send_json( [ 'success' => true, 'message' => '' ]  );

    if( ! empty( $oldId = get_field('user_avatar', 'user_' . $current_user->ID ) ) ){
        wp_delete_attachment( $oldId['ID'], true);
    }

    if( $_REQUEST[ 'user_avatar' ] === 'remove' ){
        update_field('user_avatar', false, 'user_' . $current_user->ID );
    }



    $upload_dir         = wp_upload_dir();
    $upload_path        = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;


    $mime = substr($_REQUEST[ 'user_avatar' ], 11, 4);
    $mime = str_replace(';', '', $mime);
    if ($mime != 'jpeg' && $mime != 'png') wp_send_json( [ 'success' => false, 'message' => '' ] );
    $img             = str_replace( 'data:image/'.$mime.';base64,', '', $_REQUEST[ 'user_avatar' ] );
    $img             = str_replace( ' ', '+', $img );
    $decoded         = base64_decode( $img );
    $filename        = $current_user->user_login . '.' . $mime;
    $file_type       = 'image/' . $mime;


    $hashed_filename    = md5( $filename . microtime() ) . '_' . $filename;
    $upload_file = file_put_contents( $upload_path . $hashed_filename, $decoded );

    add_filter('jpeg_quality', function($arg){return 100;});

    $attach_id = wp_insert_attachment( [
        'post_mime_type' => $file_type,
        'post_title'     => $current_user->first_name . ' ' . $current_user->last_name,
        'post_content'   => '',
        'post_status'    => 'inherit',
        'guid'           => $upload_dir['url'] . '/' . basename( $hashed_filename )
    ], $upload_dir['path'] . '/' . $hashed_filename );

    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $upload_dir['path'] . '/' . $hashed_filename );
    wp_update_attachment_metadata( $attach_id, $attach_data );


    update_field('user_avatar', $attach_id, 'user_' . $current_user->ID );

    wp_send_json( [ 'success' => true, 'message' => '' ] );
});



