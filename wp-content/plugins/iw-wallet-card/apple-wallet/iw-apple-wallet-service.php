<?php

class IW_Apple_Wallet_Service {

    public static function init() {
        // Apple required endpoints
        add_action( 'rest_api_init', function() {
            register_rest_route('iw-apple-wallet/v1', '/devices/(?P<deviceLibraryIdentifier>[A-Za-z0-9]+)/registrations/(?P<passTypeIdentifier>[^/]+)/(?P<serialNumber>[^/]+)', [ 'methods'  => 'POST', 'callback' => [ __CLASS__, 'register_device' ], 'permission_callback' => '__return_true']);
            register_rest_route('iw-apple-wallet/v1', '/devices/(?P<deviceLibraryIdentifier>[A-Za-z0-9]+)/registrations/(?P<passTypeIdentifier>[^/]+)/(?P<serialNumber>[^/]+)', [ 'methods'  => 'DELETE', 'callback' => [ __CLASS__, 'unregister_device' ], 'permission_callback' => '__return_true']);
            register_rest_route('iw-apple-wallet/v1', '/devices/(?P<deviceLibraryIdentifier>[A-Za-z0-9]+)/registrations/(?P<passTypeIdentifier>[^/]+)', [ 'methods'  => 'GET', 'callback' => [ __CLASS__, 'serials_for_device' ], 'permission_callback' => '__return_true']);
            register_rest_route('iw-apple-wallet/v1', '/passes/(?P<passTypeIdentifier>[^/]+)/(?P<serialNumber>[^/]+)', [ 'methods'  => 'GET', 'callback' => [ __CLASS__, 'get_pass' ], 'permission_callback' => '__return_true' ]);
        });
    }

    public static function register_device( WP_REST_Request $req ) {
        $deviceId    = sanitize_text_field($req['deviceLibraryIdentifier']);
        $serial      = sanitize_text_field($req['serialNumber']);
        $passType    = sanitize_text_field($req['passTypeIdentifier']);
        $pushToken   = sanitize_text_field($req->get_param('pushToken'));

        if ($passType !== IW_Apple_Wallet_Card::pass_type_identifier()) return new WP_REST_Response(null, 404);
        $users = get_users( [ 'meta_key'   => 'member_card_id', 'meta_value' => $serial, 'number' => 1, 'fields' => 'ID']);
        if ( ! $users )  return new WP_REST_Response(null, 404);
        $user_id = $users[0];
        $existing = get_user_meta($user_id, 'apple_wallet_device_tokens', true);
        if (!is_array($existing)) $existing = [];
        $existing[$deviceId] = [ 'deviceLibraryIdentifier' => $deviceId, 'passTypeIdentifier' => $passType, 'serialNumber' => $serial, 'pushToken' => $pushToken ];
        update_user_meta($user_id, 'apple_wallet_device_tokens', $existing);
        return new WP_REST_Response([ "status" => "ok" ], 201);
    }


    /**
     * UNREGISTER DEVICE
     */
    public static function unregister_device( WP_REST_Request $req ) {
        $deviceId = sanitize_text_field($req['deviceLibraryIdentifier']);
        $serial   = sanitize_text_field($req['serialNumber']);
        $users = get_users([ 'meta_key' => 'member_card_id', 'meta_value' => $serial, 'number' => 1, 'fields' => 'ID' ]);
        if ( ! $users ) return new WP_REST_Response(null, 404);
        $user_id = $users[0];
        $existing = get_user_meta($user_id, 'apple_wallet_device_tokens', true);
        if (!is_array($existing)) $existing = [];

        if (isset($existing[$deviceId])) {
            unset($existing[$deviceId]);
            update_user_meta($user_id, 'apple_wallet_device_tokens', $existing);
        }
        return new WP_REST_Response(null, 200);
    }


    public static function serials_for_device( WP_REST_Request $req ) {
        $deviceId = sanitize_text_field($req['deviceLibraryIdentifier']);
        $passType = sanitize_text_field($req['passTypeIdentifier']);
        if ( $passType !== IW_Apple_Wallet_Card::pass_type_identifier() ) return new WP_REST_Response(null, 404);
        $users = get_users([ 'meta_key' => 'apple_wallet_device_tokens', 'meta_compare' => 'LIKE', 'meta_value' => $deviceId, 'number' => 1, 'fields'=> 'ID']);
        if (empty($users))  return [ 'lastUpdated'  => time(), 'serialNumbers' => []];
        $user_id = $users[0];
        $serial  = get_user_meta($user_id, 'member_card_id', true);
        $version =  get_user_meta($user_id, 'apple_wallet_pass_version', true);
        if (!$serial) return [ 'lastUpdated'  => time(), 'serialNumbers' => [] ];
        return [ 'lastUpdated'  => $version, 'serialNumbers' => [ $serial ]];
    }


    /**
     * GET PASS (Apple calls this when device receives push)
     */
    public static function get_pass( WP_REST_Request $req ) {
        $serial   = sanitize_text_field($req['serialNumber']);
        $passType = sanitize_text_field($req['passTypeIdentifier']);
        if ($passType !== IW_Apple_Wallet_Card::pass_type_identifier()) return new WP_REST_Response(null, 404);
        $users = get_users( [ 'meta_key' => 'member_card_id',  'meta_value' => $serial, 'number' => 1, 'fields' => 'ID']);
        if (!$users) return new WP_REST_Response(null, 404);
        $user_id = $users[0];
        IW_Apple_Wallet_Card::generate_apple_pass($user_id, $serial);
        exit;
    }

    public static function push_update($user_id) {
        $devices = get_user_meta($user_id, 'apple_wallet_device_tokens', true);
        if (!is_array($devices)) return;
        foreach ($devices as $device) {
            self::send_push_to_device($device['pushToken'], $device['passTypeIdentifier']);
        }
    }

    private static function send_push_to_device($pushToken, $passTypeIdentifier) {
        $jwt = self::generate_jwt();
        if ( '' === $jwt ) {
            return false;
        }

        $payload = json_encode( [ 'aps' => [ 'content-available' => 1 ] ]);
        $ch = curl_init("https://api.push.apple.com/3/device/{$pushToken}");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [ "authorization: bearer {$jwt}", "apns-topic: {$passTypeIdentifier}"],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    private static function generate_jwt() {
        $key_id = IW_Apple_Wallet_Card::key_id();
        $team_id = IW_Apple_Wallet_Card::team_identifier();
        $private_key_path = IW_Apple_Wallet_Card::get_private_key_path();
        if ( '' === $key_id || '' === $team_id || '' === $private_key_path || ! file_exists( $private_key_path ) ) {
            error_log( 'Apple Wallet push is not configured. Set iw_wallet_card_apple_key_id, iw_wallet_card_apple_team_identifier, and iw_wallet_card_apple_private_key_path.' );
            return '';
        }

        $header = [ 'alg' => 'ES256', 'kid' => $key_id ];
        $claims = [ 'iss' => $team_id, 'iat' => time() ];
        $header_encoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $claims_encoded = rtrim(strtr(base64_encode(json_encode($claims)), '+/', '-_'), '=');
        $data = $header_encoded . '.' . $claims_encoded;
        openssl_sign($data, $signature, file_get_contents($private_key_path), 'sha256');
        if (empty($signature)) {
            error_log("JWT SIGN ERROR: Could not sign with private key at {$private_key_path}");
            return '';
        }
        $signature_encoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        return $data . '.' . $signature_encoded;
    }

}

IW_Apple_Wallet_Service::init();
