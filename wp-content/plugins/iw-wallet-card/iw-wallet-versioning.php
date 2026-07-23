<?php

class IW_Wallet_Versioning {

    public static function init() {
        add_action( 'wp_update_user', function ( $user_id, $userdata, $userdata_raw ){
            $card_id = get_field( 'member_card' ,'user_' . $user_id );
            IW_Wallet_Versioning::check_member_card_change( $card_id ,'user_' . $user_id, [ 'name' => 'member_card' ]);

        }, 10 ,3  );
    }

    public static function check_member_card_change($value, $post_id, $field) {
        if( $field['name'] == 'member_card' && str_starts_with($post_id, 'user_') ) {
            if ( empty( $value ) ) {
                return $value;
            }

            $user_id = intval(str_replace('user_', '', $post_id));
            $old_hash = get_user_meta($user_id, '_member_card_snapshot', true);
            $new_hash = md5(json_encode($value));
            if ( $old_hash !== $new_hash ) {
                update_user_meta($user_id, '_member_card_snapshot', $new_hash);
                update_user_meta($user_id, 'apple_wallet_pass_version', time());
                update_user_meta($user_id, 'google_wallet_pass_version', time());
                IW_Apple_Wallet_Service::push_update($user_id);
                IW_Google_Wallet_Service::push_update($user_id);
            }
        }
        return $value;
    }
}
IW_Wallet_Versioning::init();
