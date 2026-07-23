<?php

use Firebase\JWT\JWT;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Client as GoogleClient;
use Google\Service\Walletobjects;
use Google\Service\Walletobjects\GenericObject;
use Google\Service\Walletobjects\GenericClass;
use Google\Service\Walletobjects\Barcode;
use Google\Service\Walletobjects\ImageModuleData;
use Google\Service\Walletobjects\LinksModuleData;
use Google\Service\Walletobjects\TextModuleData;
use Google\Service\Walletobjects\TranslatedString;
use Google\Service\Walletobjects\LocalizedString;
use Google\Service\Walletobjects\ImageUri;
use Google\Service\Walletobjects\Image;
use Google\Service\Walletobjects\Uri;



class IW_Google_Wallet_Service {

    public static function push_update($user_id) {
        if ( ! IW_Google_Wallet_Card::is_configured() ) {
            return false;
        }

        try {
            IW_Google_Wallet_Card::generate_google_pass($user_id, false, false);
        } catch (Exception $e) {
            error_log("Google Wallet push_update failed for user {$user_id}: " . $e->getMessage());
            return false;
        }
        return true;
    }
}
