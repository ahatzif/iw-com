<?php

use Firebase\JWT\JWT;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Client as GoogleClient;
use Google\Service\Walletobjects;
use Google\Service\Walletobjects\GenericObject;
use Google\Service\Walletobjects\GenericClass;
use Google\Service\Walletobjects\Barcode;
use Google\Service\Walletobjects\MerchantLocation;
use Google\Service\Walletobjects\TranslatedString;
use Google\Service\Walletobjects\LocalizedString;
use Google\Service\Walletobjects\ImageUri;
use Google\Service\Walletobjects\Image;
use Google\Service\Walletobjects\Uri;

class IWGooglePass
{
    public  $client;
    public $keyFilePath = '';
    public $applicationName = 'Wallet';
    public $credentials;
    public $service;
    public function __construct( string $keyFilePath = '', string $applicationName = 'Wallet' )
    {
        $this->keyFilePath = $keyFilePath;
        $this->applicationName = $applicationName;
        $this->auth();
    }

    public function auth() {
        if ( '' === $this->keyFilePath || ! file_exists( $this->keyFilePath ) ) {
            throw new RuntimeException( 'Google Wallet service account file is missing.' );
        }

        $this->credentials = new ServiceAccountCredentials(Walletobjects::WALLET_OBJECT_ISSUER, $this->keyFilePath);
        $this->client = new GoogleClient();
        $this->client->setApplicationName($this->applicationName);
        $this->client->setScopes(Walletobjects::WALLET_OBJECT_ISSUER);
        $this->client->setAuthConfig($this->keyFilePath);
        $this->service = new Walletobjects($this->client);
    }

    public function createClass($issuerId, $classSuffix)
    {
        $classId = "{$issuerId}.{$classSuffix}";

        try {
            $this->service->genericclass->get($classId);
            return $classId;
        } catch (Google\Service\Exception $ex) {
            if (empty($ex->getErrors()) || $ex->getErrors()[0]['reason'] != 'classNotFound') {
                return $classId;
            }
        }

        $newClass = new GenericClass([ 'id' => $classId, 'reviewStatus' => 'UNDER_REVIEW',  'classTemplateInfo' => [
            'cardTemplateOverride' => [ 'cardRowTemplateInfos' => [[ 'twoItems' => [
                'startItem' => [ 'firstValue' => ['fields' => [ ['fieldPath' => "object.textModulesData['member_since']"]]]],
                'endItem' => [ 'firstValue' => ['fields' => [['fieldPath' => "object.textModulesData['renews_on']"]]] ]
            ]]]],
            'detailsTemplateOverride' => [ 'detailsItemInfos' => [
                [ 'item' => ['firstValue' => ['fields' => [ ['fieldPath' => "object.textModulesData['card_number']"] ] ] ] ],
                [ 'item' => ['firstValue' => [ 'fields' => [ ['fieldPath' => "object.textModulesData['features']"] ]]]]
            ]]
        ]]);

        $response = $this->service->genericclass->insert($newClass);

        return $response->id;
    }

    public function create_or_update_object($issuerId, $classSuffix, $objectSuffix, $card_styles = [], $card_data = []) {
        $objectId = "{$issuerId}.{$objectSuffix}";
        $created = false;
        $fields = $this->build_object_fields($issuerId, $classSuffix, $objectSuffix, $card_styles, $card_data);
        $fields['version'] = time();
        try {
            $existing = $this->service->genericobject->get($objectId);
            $newObject = new GenericObject($fields);
            $response = $this->service->genericobject->update($objectId, $newObject);

        } catch (Google\Service\Exception $ex) {
            if (!empty($ex->getErrors()) && $ex->getErrors()[0]['reason'] == 'resourceNotFound') {
                $newObject = new GenericObject($fields);
                $response = $this->service->genericobject->insert($newObject);
                $created = true;
            } else {
                // Other error
                error_log(print_r($ex, true));
                return [ 'id' => $objectId, 'created' => false ];
            }
        }
        // Store Google Wallet object ID to user meta
        if (!empty($card_data['user_id'])) {
            update_user_meta($card_data['user_id'], 'google_wallet_object_id', $response->id);
        }

        return [ 'id' => $response->id, 'created' => $created ];
    }

    /**
     * Helper to build all fields for the Google Wallet object from card_styles and card_data.
     */
    private function build_object_fields($issuerId, $classSuffix, $objectSuffix, $card_styles, $card_data) {
        // $card_data should contain: name, card_number, type, features, next_renewal, subscription, locations, qr_value, etc.
        $fields = [
            'id' => "{$issuerId}.{$objectSuffix}",
            'classId' => "{$issuerId}.{$classSuffix}",
            'state' => 'ACTIVE',
        ];
        // Hero image
        if (!empty($card_styles['google_images']['hero'])) {
            $fields['heroImage'] = new Image([
                'sourceUri' => new ImageUri([ 'uri' => $card_styles['google_images']['hero'] ]),
                'contentDescription' => new LocalizedString([
                    'defaultValue' => new TranslatedString([ 'language' => 'en-US', 'value' => 'Membership Hero Image' ])
                ])
            ]);
        }
        // Logo
        if (!empty($card_styles['google_images']['logo'])) {

            $fields['logo'] = new Image([
                'sourceUri' => new ImageUri([ 'uri' => $card_styles['google_images']['logo'] ]),
                'contentDescription' => new LocalizedString([
                    'defaultValue' => new TranslatedString([ 'language' => 'en-US', 'value' => 'Membership Card Logo' ])
                ])
            ]);
        }

        /*if (!empty($card_styles['google_images']['image_modules']) && is_array($card_styles['google_images']['image_modules'])) {
            $imgMods = [];
            foreach ($card_styles['google_images']['image_modules'] as $imgMod) {
                if (!empty($imgMod['url'])) {
                    $desc = (!empty($imgMod['desc'])) ? $imgMod['desc'] : 'Card image';
                    $imgMods[] = new ImageModuleData([
                        'mainImage' => new Image([
                            'sourceUri' => new ImageUri([ 'uri' => $imgMod['url'] ]),
                            'contentDescription' => new LocalizedString([
                                'defaultValue' => new TranslatedString([
                                    'language' => 'en-US',
                                    'value' => $desc,
                                ]),
                            ]),
                        ]),
                        'id' => $imgMod['id'] ?? null
                    ]);
                }
            }
            if (!empty($imgMods)) {
                $fields['imageModulesData'] = $imgMods;
            }
        }*/


        // Background color
        if (!empty($card_styles['background_color'])) {
            $fields['hexBackgroundColor'] = $card_styles['background_color'];
        }
        // Header (TYPE as the header, el-GR)
        if (!empty($card_data['type'])) {
            $fields['header'] = new LocalizedString([
                'defaultValue' => new TranslatedString([ 'language' => 'el-GR', 'value' => $card_data['type'] ])
            ]);
        }
        // Card title
        if (!empty($card_data['name'])) {
            $fields['subheader'] = new LocalizedString([ 'defaultValue' => new TranslatedString([ 'language' => 'en-US', 'value' =>_x( "TYPE", 'wallet', 'iw-theme' )  ]) ]);
            $fields['cardTitle'] = new LocalizedString([ 'defaultValue' => new TranslatedString([ 'language' => 'en-US', 'value' => $card_data['name'] ]) ]);
        }

        $fields['textModulesData'] = [];
        if (!empty($card_data['subscription']) && !empty($card_data['subscription']->get_date_created())) {
            $fields['textModulesData'][] = new Google_Service_Walletobjects_TextModuleData([ 'id' => 'member_since', "header" => _x( 'MEMBER SINCE', 'wallet', 'iw-theme' ), 'body' => $card_data['subscription']->get_date_created()->date_i18n('d/m/Y'),]);
        }
        if (!empty($card_data['next_renewal'])) {
            $fields['textModulesData'][] = new Google_Service_Walletobjects_TextModuleData(['id' => 'renews_on', 'header' => _x( 'RENEWS ON', 'wallet', 'iw-theme' ), 'body' => $card_data['next_renewal']->format('d/m/Y'),]);
        }
        if (!empty($card_data['features'])) {
            $fields['textModulesData'][] = new Google_Service_Walletobjects_TextModuleData([ 'id' => 'features', 'header' => _x( 'Features', 'wallet', 'iw-theme' ), 'body' => $card_data['features'] ]);
        }
        if (!empty($card_data['card_number'])) {
            $fields['textModulesData'][] = new Google_Service_Walletobjects_TextModuleData([ 'id' => 'card_number', "header" => _x( 'Card Number', 'wallet', 'iw-theme' ), 'body' => $card_data['card_number'] ]);
        }

        // Barcode (QR code)
        if (!empty($card_data['qr_value'])) {
            $fields['barcode'] = new Barcode([ 'type' => 'QR_CODE',  'value' => $card_data['qr_value'],]);
        }
        // Locations

        if (!empty($card_data['locations']) && is_array($card_data['locations'])) {
            $fields['merchantLocations'] = [];
            foreach ($card_data['locations'] as $loc) {
                if (!empty($loc['latitude']) && !empty($loc['longitude'])) {
                    $fields['merchantLocations'][] = new MerchantLocation( [
                        'latitude'  => (float) $loc['latitude'],
                        'longitude' => (float) $loc['longitude'],
                        //'label'     => $loc['relevantText'] ?? null,
                    ]);
                }
            }
        }






        return $fields;
    }

    public function createJwtNewObjects($issuerId, $classSuffix, $objectSuffix){
        $serviceAccount = json_decode(file_get_contents($this->keyFilePath), true);

        // Fetch full class and object from Google Wallet API
        try {
            $fullObject = $this->service->genericobject->get("{$issuerId}.{$objectSuffix}")->toSimpleObject();
        } catch (\Exception $e) {
            error_log("GOOGLE WALLET ERROR: object not found or invalid for JWT save");
            return false;
        }

        try {
            $fullClass = $this->service->genericclass->get("{$issuerId}.{$classSuffix}")->toSimpleObject();
        } catch (\Exception $e) {
            error_log("GOOGLE WALLET ERROR: class not found or invalid for JWT save");
            return false;
        }

        $claims = [
            'iss' => $serviceAccount['client_email'],
            'aud' => 'google',
            'typ' => 'savetowallet',
            'origins' => IW_Google_Wallet_Card::jwt_origins(),
            'payload' => [ 'genericObjects' => [ $fullObject ],  'genericClasses' => [ $fullClass ] ]
        ];

        $token = JWT::encode($claims, $serviceAccount['private_key'], 'RS256');
        return "https://pay.google.com/gp/v/save/{$token}";
    }

}
