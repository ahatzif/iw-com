<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Apple Wallet web service endpoints for Tickets.
 *
 * This follows Apple's PassKit Web Service patterns, adapted to our ticketing data model.
 * Each ticket in `wp_iw_tickets` maps to one pass, with:
 *  - serialNumber = ticket_uuid
 *  - barcode value = barcode_hash (set during pass generation)
 *
 * Note: pass generation (pkpass) will be implemented in a separate class.
 */
class IW_Apple_Wallet_Tickets_Service {

    /**
     * REST namespace.
     */
    const REST_NS = 'iw/v1';

    /**
     * Route base.
     */
    const ROUTE_BASE = 'apple-wallet/tickets';

    /**
     * Register all REST routes.
     */
    public static function register_routes(): void {

        // Public download endpoint (signed) for emails / guests.
        // Example: /wp-json/iw/v1/apple-wallet/tickets/public-pass?ticket_uuid=...&exp=...&sig=...
        register_rest_route(
            self::REST_NS,
            '/' . self::ROUTE_BASE . '/public-pass',
            [
                [
                    'methods'             => WP_REST_Server::READABLE, // GET
                    'callback'            => [ __CLASS__, 'handle_public_pass_download' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'ticket_uuid' => [ 'required' => true ],
                        'exp'         => [ 'required' => true ],
                        'sig'         => [ 'required' => true ],
                    ],
                ],
            ]
        );

        // POST/DELETE registration for a specific pass.
        register_rest_route(
            self::REST_NS,
            '/' . self::ROUTE_BASE . '/devices/(?P<deviceLibraryIdentifier>[^/]+)/registrations/(?P<passTypeIdentifier>[^/]+)/(?P<serialNumber>[^/]+)',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE, // POST
                    'callback'            => [ __CLASS__, 'handle_register' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'deviceLibraryIdentifier' => [ 'required' => true ],
                        'passTypeIdentifier'      => [ 'required' => true ],
                        'serialNumber'            => [ 'required' => true ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE, // DELETE
                    'callback'            => [ __CLASS__, 'handle_unregister' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'deviceLibraryIdentifier' => [ 'required' => true ],
                        'passTypeIdentifier'      => [ 'required' => true ],
                        'serialNumber'            => [ 'required' => true ],
                    ],
                ],
            ]
        );

        // GET list of serial numbers updated since a timestamp for a device.
        register_rest_route(
            self::REST_NS,
            '/' . self::ROUTE_BASE . '/devices/(?P<deviceLibraryIdentifier>[^/]+)/registrations/(?P<passTypeIdentifier>[^/]+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE, // GET
                    'callback'            => [ __CLASS__, 'handle_list_updated_serials' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'deviceLibraryIdentifier' => [ 'required' => true ],
                        'passTypeIdentifier'      => [ 'required' => true ],
                        'passesUpdatedSince'      => [ 'required' => false ],
                    ],
                ],
            ]
        );

        // GET the pkpass for a specific serial.
        register_rest_route(
            self::REST_NS,
            '/' . self::ROUTE_BASE . '/passes/(?P<passTypeIdentifier>[^/]+)/(?P<serialNumber>[^/]+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE, // GET
                    'callback'            => [ __CLASS__, 'handle_get_pass' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'passTypeIdentifier' => [ 'required' => true ],
                        'serialNumber'       => [ 'required' => true ],
                    ],
                ],
            ]
        );
    }

    /**
     * Expected Authorization token for a ticket pass.
     *
     * We use a deterministic HMAC so we don't need to store auth tokens in DB.
     */
    public static function get_expected_auth_token_for_ticket( string $ticket_uuid ): string {
        $ticket_uuid = trim( $ticket_uuid );
        if ( $ticket_uuid === '' ) {
            return '';
        }
        return hash_hmac( 'sha256', $ticket_uuid, wp_salt( 'iw_ticketing_wallet' ) );
    }

    /**
     * Validate ApplePass authorization for a given serial (ticket_uuid).
     */
    protected static function validate_authorization_for_serial( WP_REST_Request $request, string $serialNumber ): bool {
        $auth_header = (string) $request->get_header( 'authorization' );
        $auth_header = trim( $auth_header );

        if ( $auth_header === '' ) {
            return false;
        }

        // Apple commonly sends: "ApplePass <token>".
        $token = $auth_header;
        if ( stripos( $auth_header, 'ApplePass ' ) === 0 ) {
            $token = trim( substr( $auth_header, strlen( 'ApplePass ' ) ) );
        }

        if ( $token === '' ) {
            return false;
        }

        $expected = self::get_expected_auth_token_for_ticket( $serialNumber );
        if ( $expected === '' ) {
            return false;
        }

        // Timing-safe compare.
        return hash_equals( $expected, $token );
    }

    /**
     * POST registration.
     * Body: { "pushToken": "..." }
     */
    public static function handle_register( WP_REST_Request $request ) {
        $deviceLibraryIdentifier = (string) $request->get_param( 'deviceLibraryIdentifier' );
        $passTypeIdentifier      = (string) $request->get_param( 'passTypeIdentifier' );
        $serialNumber            = (string) $request->get_param( 'serialNumber' );

        $deviceLibraryIdentifier = trim( $deviceLibraryIdentifier );
        $passTypeIdentifier = trim( $passTypeIdentifier );
        $serialNumber = trim( $serialNumber );

        if ( $deviceLibraryIdentifier === '' || $passTypeIdentifier === '' || $serialNumber === '' ) {
            return new WP_REST_Response( [ 'error' => 'missing_params' ], 400 );
        }

        if ( ! self::validate_authorization_for_serial( $request, $serialNumber ) ) {
            return new WP_REST_Response( [ 'error' => 'unauthorized' ], 401 );
        }

        // Read pushToken either from JSON body or form data.
        $pushToken = '';
        $json = $request->get_json_params();
        if ( is_array( $json ) && ! empty( $json['pushToken'] ) ) {
            $pushToken = (string) $json['pushToken'];
        }
        if ( $pushToken === '' ) {
            $pushToken = (string) $request->get_param( 'pushToken' );
        }
        $pushToken = trim( $pushToken );

        if ( $pushToken === '' ) {
            return new WP_REST_Response( [ 'error' => 'missing_push_token' ], 400 );
        }

        // Ensure ticket exists.
        if ( ! class_exists( 'IW_Tickets_DB' ) || ! method_exists( 'IW_Tickets_DB', 'get_ticket_by_uuid' ) ) {
            return new WP_REST_Response( [ 'error' => 'db_not_ready' ], 500 );
        }
        $ticket = IW_Tickets_DB::get_ticket_by_uuid( $serialNumber );
        if ( empty( $ticket ) ) {
            return new WP_REST_Response( [ 'error' => 'not_found' ], 404 );
        }

        $res = IW_Tickets_DB::upsert_wallet_device_registration( $serialNumber, $passTypeIdentifier, $deviceLibraryIdentifier, $pushToken );
        if ( $res <= 0 ) {
            return new WP_REST_Response( [ 'error' => 'db_write_failed' ], 500 );
        }

        // Apple expects: 201 Created if new registration, 200 OK if already registered.
        // MySQL can return 1 for insert, 2 for update; treat 1 as created.
        $status = ( $res === 1 ) ? 201 : 200;
        return new WP_REST_Response( null, $status );
    }

    /**
     * DELETE registration.
     */
    public static function handle_unregister( WP_REST_Request $request ) {
        $deviceLibraryIdentifier = (string) $request->get_param( 'deviceLibraryIdentifier' );
        $passTypeIdentifier      = (string) $request->get_param( 'passTypeIdentifier' );
        $serialNumber            = (string) $request->get_param( 'serialNumber' );

        $deviceLibraryIdentifier = trim( $deviceLibraryIdentifier );
        $passTypeIdentifier = trim( $passTypeIdentifier );
        $serialNumber = trim( $serialNumber );

        if ( $deviceLibraryIdentifier === '' || $passTypeIdentifier === '' || $serialNumber === '' ) {
            return new WP_REST_Response( [ 'error' => 'missing_params' ], 400 );
        }

        if ( ! self::validate_authorization_for_serial( $request, $serialNumber ) ) {
            return new WP_REST_Response( [ 'error' => 'unauthorized' ], 401 );
        }

        if ( ! class_exists( 'IW_Tickets_DB' ) || ! method_exists( 'IW_Tickets_DB', 'delete_wallet_device_registration' ) ) {
            return new WP_REST_Response( [ 'error' => 'db_not_ready' ], 500 );
        }

        IW_Tickets_DB::delete_wallet_device_registration( $serialNumber, $deviceLibraryIdentifier, $passTypeIdentifier );
        // Apple expects 200 OK even if it wasn't registered.
        return new WP_REST_Response( null, 200 );
    }

    /**
     * GET updated serial numbers for a device.
     * Query: passesUpdatedSince=<unix timestamp>
     */
    public static function handle_list_updated_serials( WP_REST_Request $request ) {
        $deviceLibraryIdentifier = (string) $request->get_param( 'deviceLibraryIdentifier' );
        $passTypeIdentifier      = (string) $request->get_param( 'passTypeIdentifier' );

        $deviceLibraryIdentifier = trim( $deviceLibraryIdentifier );
        $passTypeIdentifier = trim( $passTypeIdentifier );

        if ( $deviceLibraryIdentifier === '' || $passTypeIdentifier === '' ) {
            return new WP_REST_Response( [ 'error' => 'missing_params' ], 400 );
        }

        if ( ! class_exists( 'IW_Tickets_DB' ) || ! method_exists( 'IW_Tickets_DB', 'get_ticket_uuids_for_device_since' ) ) {
            return new WP_REST_Response( [ 'error' => 'db_not_ready' ], 500 );
        }

        $since = (int) $request->get_param( 'passesUpdatedSince' );
        $since = max( 0, $since );

        $serials = IW_Tickets_DB::get_ticket_uuids_for_device_since( $deviceLibraryIdentifier, $passTypeIdentifier, $since );

        if ( empty( $serials ) ) {
            // Apple expects 204 No Content if no updates.
            return new WP_REST_Response( null, 204 );
        }

        $resp = [
            'lastUpdated'   => time(),
            'serialNumbers' => array_values( $serials ),
        ];

        return new WP_REST_Response( $resp, 200 );
    }

    /**
     * GET pkpass for a serial.
     *
     * NOTE: The actual pkpass generation will be wired in via IW_Apple_Wallet_Ticket_Pass.
     */
    public static function handle_get_pass( WP_REST_Request $request ) {
        $passTypeIdentifier = (string) $request->get_param( 'passTypeIdentifier' );
        $serialNumber       = (string) $request->get_param( 'serialNumber' );

        $passTypeIdentifier = trim( $passTypeIdentifier );
        $serialNumber = trim( $serialNumber );

        if ( $passTypeIdentifier === '' || $serialNumber === '' ) {
            return new WP_REST_Response( [ 'error' => 'missing_params' ], 400 );
        }

        if ( ! self::validate_authorization_for_serial( $request, $serialNumber ) ) {
            return new WP_REST_Response( [ 'error' => 'unauthorized' ], 401 );
        }

        // Ensure ticket exists.
        if ( ! class_exists( 'IW_Tickets_DB' ) || ! method_exists( 'IW_Tickets_DB', 'get_ticket_by_uuid' ) ) {
            return new WP_REST_Response( [ 'error' => 'db_not_ready' ], 500 );
        }
        $ticket = IW_Tickets_DB::get_ticket_by_uuid( $serialNumber );
        if ( empty( $ticket ) ) {
            return new WP_REST_Response( [ 'error' => 'not_found' ], 404 );
        }

        // This will be implemented next.
        if ( ! class_exists( 'IW_Apple_Wallet_Ticket_Pass' ) || ! method_exists( 'IW_Apple_Wallet_Ticket_Pass', 'output_pkpass_for_ticket_uuid' ) ) {
            return new WP_REST_Response( [ 'error' => 'pkpass_not_implemented' ], 501 );
        }

        // The method is expected to output the pkpass and exit.
        IW_Apple_Wallet_Ticket_Pass::output_pkpass_for_ticket_uuid( $serialNumber, $passTypeIdentifier );

        // In case it doesn't exit for some reason.
        return new WP_REST_Response( [ 'error' => 'pkpass_output_failed' ], 500 );
    }
    /**
     * Create HMAC signature for public ticket pass links.
     * Payload: ticket_uuid|exp
     */
    public static function build_public_link_signature( string $ticket_uuid, int $exp ): string {
        $ticket_uuid = trim( $ticket_uuid );
        $exp = (int) $exp;
        $payload = $ticket_uuid . '|' . $exp;
        return hash_hmac( 'sha256', $payload, wp_salt( 'iw_ticketing_wallet_public' ) );
    }

    /**
     * Validate a public link signature + expiry.
     */
    protected static function validate_public_link( string $ticket_uuid, int $exp, string $sig ): bool {
        $ticket_uuid = trim( $ticket_uuid );
        $sig = trim( $sig );
        $exp = (int) $exp;

        if ( $ticket_uuid === '' || $sig === '' || $exp <= 0 ) {
            return false;
        }

        // Expired.
        if ( time() > $exp ) {
            return false;
        }

        $expected = self::build_public_link_signature( $ticket_uuid, $exp );
        return $expected !== '' && hash_equals( $expected, $sig );
    }

    /**
     * Public pkpass download for a ticket (signed guest link).
     */
    public static function handle_public_pass_download( WP_REST_Request $request ) {
        $ticket_uuid = (string) $request->get_param( 'ticket_uuid' );
        $exp = (int) $request->get_param( 'exp' );
        $sig = (string) $request->get_param( 'sig' );


        $ticket_uuid = trim( $ticket_uuid );
        $sig = trim( $sig );
        $exp = (int) $exp;

        if ( $ticket_uuid === '' || $exp <= 0 || $sig === '' ) {
            return new WP_REST_Response( [ 'error' => 'missing_params' ], 400 );
        }

        if ( ! self::validate_public_link( $ticket_uuid, $exp, $sig ) ) {
            return new WP_REST_Response( [ 'error' => 'invalid_or_expired_link' ], 403 );
        }

        // Ensure ticket exists before attempting to output pkpass.
        if ( ! class_exists( 'IW_Tickets_DB' ) || ! method_exists( 'IW_Tickets_DB', 'get_ticket_by_uuid' ) ) {
            return new WP_REST_Response( [ 'error' => 'db_not_ready' ], 500 );
        }
        $ticket = IW_Tickets_DB::get_ticket_by_uuid( $ticket_uuid );
        if ( empty( $ticket ) ) {
            return new WP_REST_Response( [ 'error' => 'not_found' ], 404 );
        }

        if ( ! class_exists( 'IW_Apple_Wallet_Ticket_Pass' ) || ! method_exists( 'IW_Apple_Wallet_Ticket_Pass', 'output_pkpass_for_ticket_uuid' ) ) {
            
            return new WP_REST_Response( [ 'error' => 'pkpass_not_implemented' ], 501 );
        }
        

        // Let generator decide passTypeIdentifier (tickets).
        $pti = method_exists( 'IW_Apple_Wallet_Ticket_Pass', 'get_pass_type_identifier' ) ? IW_Apple_Wallet_Ticket_Pass::get_pass_type_identifier() : '';


        IW_Apple_Wallet_Ticket_Pass::output_pkpass_for_ticket_uuid( $ticket_uuid, $pti );


        return new WP_REST_Response( [ 'error' => 'pkpass_output_failed' ], 500 );
    }
}
