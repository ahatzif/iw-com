<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IW_Zebra_Ticket_Printer {
    const OPTION_KEY = 'iw_zebra_ticket_printer_settings';

    protected $settings = [];

    public function __construct( array $settings = [] ) {
        $this->settings = array_merge( self::get_settings(), $settings );
    }

    public static function get_settings(): array {
        $stored = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $stored ) ) {
            $stored = [];
        }

        $acf_settings = self::get_acf_settings();
        if ( ! empty( $acf_settings ) ) {
            $stored = array_merge( $stored, $acf_settings );
        }

        $defaults = [
            'host'             => '192.168.2.9',
            'port'             => 9100,
            'timeout'          => 5,
            'print_width'      => 640,
            'label_length'     => 1194,
            'print_speed'      => 3,
            'print_darkness'   => -5,
            'text_orientation' => 'B',
            'text_x'           => 260,
            'text_y'           => 250,
            'text_block_width' => 330,
            'line_gap'         => 30,
            'qr_x'             => 485,
            'qr_y'             => 50,
            'qr_model'         => 2,
            'qr_size'          => 3,
        ];

        $settings = array_merge( $defaults, $stored );

        $settings['host']             = sanitize_text_field( (string) $settings['host'] );
        $settings['port']             = max( 1, min( 65535, absint( $settings['port'] ) ) );
        $settings['timeout']          = max( 1, min( 30, absint( $settings['timeout'] ) ) );
        $settings['print_width']      = max( 1, absint( $settings['print_width'] ) );
        $settings['label_length']     = max( 1, absint( $settings['label_length'] ) );
        $settings['print_speed']      = max( 1, min( 14, absint( $settings['print_speed'] ) ) );
        $settings['print_darkness']   = max( -30, min( 30, (int) $settings['print_darkness'] ) );
        $settings['text_orientation'] = in_array( strtoupper( (string) $settings['text_orientation'] ), [ 'N', 'R', 'I', 'B' ], true )
            ? strtoupper( (string) $settings['text_orientation'] )
            : 'B';

        foreach ( [ 'text_x', 'text_y', 'text_block_width', 'line_gap', 'qr_x', 'qr_y', 'qr_model', 'qr_size' ] as $key ) {
            $settings[ $key ] = absint( $settings[ $key ] );
        }

        return (array) apply_filters( 'iw_zebra_ticket_printer_settings', $settings );
    }

    protected static function get_acf_settings(): array {
        if ( ! function_exists( 'get_field' ) ) {
            return [];
        }

        $settings = get_field( self::OPTION_KEY, 'option' );
        return is_array( $settings ) ? $settings : [];
    }

    public static function default_test_ticket(): array {
        $qr_value = class_exists( 'IW_Ticketing' )
            ? (string) IW_Ticketing::get_option( 'zebra_test_qr_value', home_url() )
            : home_url();
        $qr_host  = strtolower( (string) wp_parse_url( $qr_value, PHP_URL_HOST ) );

        if ( in_array( $qr_host, [ 'benaki.org', 'www.benaki.org', 'benaki.com', 'www.benaki.com' ], true ) ) {
            $qr_value = home_url( '/' );
        }

        return [
            'ticket_type' => 'ΕΙΣΙΤΗΡΙΟ',
            'category'    => 'ΓΕΝΙΚΗ ΕΙΣΟΔΟΣ',
            'exhibition'  => 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΚΤΥΠΩΣΗ',
            'attendee'    => 'ANDREAS CHATZIFOTIS',
            'price'       => '0,00 €',
            'visit'       => wp_date( 'd/m/Y · H:i', time(), wp_timezone() ),
            'code'        => 'TEST-ZEBRA-0001',
            'qr_value'    => $qr_value,
        ];
    }

    public static function get_order_tickets( int $order_id, string $ticket_uuid = '' ): array {
        global $wpdb;

        $order_id = absint( $order_id );
        if ( $order_id <= 0 ) {
            return [];
        }

        $table       = $wpdb->prefix . 'iw_tickets';
        $ticket_uuid = trim( $ticket_uuid );

        if ( $ticket_uuid !== '' ) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE order_id = %d AND ticket_uuid = %s ORDER BY id ASC",
                    $order_id,
                    $ticket_uuid
                ),
                ARRAY_A
            );
        } else {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE order_id = %d ORDER BY id ASC",
                    $order_id
                ),
                ARRAY_A
            );
        }

        return array_values(
            array_filter(
                array_map( [ __CLASS__, 'ticket_payload_from_row' ], is_array( $rows ) ? $rows : [] )
            )
        );
    }

    public static function ticket_payload_from_row( array $row ): array {
        $post_id = absint( $row['post_id'] ?? 0 );

        $price_category = (string) ( $row['price_category'] ?? '' );
        $price_parts    = explode( ':', $price_category );
        $category       = isset( $price_parts[1] ) && $price_parts[1] !== '' ? $price_parts[1] : $price_category;
        if ( trim( $category ) === '' ) {
            $category = __( 'Εισιτήριο', 'iw-theme' );
        }

        $unit_price = isset( $row['unit_price'] ) ? (float) $row['unit_price'] : 0.0;
        $price      = function_exists( 'wc_price' )
            ? trim( wp_strip_all_tags( html_entity_decode( wc_price( $unit_price ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) )
            : sprintf( '%s €', number_format_i18n( $unit_price, 2 ) );

        $ticket_uuid = trim( (string) ( $row['ticket_uuid'] ?? '' ) );
        $barcode     = trim( (string) ( $row['barcode_hash'] ?? '' ) );
        $code        = $ticket_uuid !== '' ? $ticket_uuid : $barcode;

        $ticket_type = sanitize_text_field( (string) ( $row['ticket_type'] ?? '' ) );
        if ( $ticket_type === '' || strtolower( $ticket_type ) === 'tickets' ) {
            $ticket_type = __( 'ΕΙΣΙΤΗΡΙΟ', 'iw-theme' );
        }

        return [
            'ticket_id'   => absint( $row['id'] ?? 0 ),
            'post_id'     => $post_id,
            'ticket_uuid' => $ticket_uuid,
            'ticket_type' => $ticket_type,
            'category'    => sanitize_text_field( $category ),
            'exhibition'  => $post_id > 0 ? sanitize_text_field( (string) get_the_title( $post_id ) ) : __( 'Εισιτήριο', 'iw-theme' ),
            'attendee'    => sanitize_text_field( (string) ( $row['attendee_name'] ?? '' ) ),
            'price'       => sanitize_text_field( $price ),
            'visit'       => self::format_slot_label(
                (string) ( $row['slot_start'] ?? '' ),
                (string) ( $row['slot_end'] ?? '' )
            ),
            'issued_at'   => self::format_date_label( (string) ( $row['issued_at'] ?? '' ) ),
            'code'        => sanitize_text_field( $code ),
            'qr_value'    => sanitize_text_field( $barcode !== '' ? $barcode : $code ),
        ];
    }

    public function printTicket( array $ticket ): array {
        $zpl = $this->renderZpl( $ticket );
        $this->sendZpl( $zpl );

        return [
            'success' => true,
            'host'    => $this->settings['host'],
            'port'    => $this->settings['port'],
        ];
    }

    public function renderZpl( array $ticket ): string {
        $ticket = $this->sanitize_ticket_payload( $ticket );

        $text_x = (int) $this->settings['text_x'];
        $text_y = (int) $this->settings['text_y'];
        $gap    = (int) $this->settings['line_gap'];
        $orient = (string) $this->settings['text_orientation'];
        $block_width = max( 1, (int) $this->settings['text_block_width'] );

        $category_price = trim( implode( ' • ', array_filter( [ $ticket['price'] , $ticket['category'] ] ) ) );
        $display_code   = $this->short_code_label( $ticket['code'] );

        $lines = [
            [
                'text'         => $ticket['exhibition'],
                'height'       => 30,
                'width'        => 20,
                'limit'        => 76,
                'max_lines'    => 3,
                'line_spacing' => 4,
                'advance'      => 94,
            ],
            [
                'text'         => $ticket['visit'] !== '' ?  $ticket['visit'] : '',
                'height'       => 23,
                'width'        => 20,
                'limit'        => 36,
                'max_lines'    => 1,
                'line_spacing' => 0,
                'advance'      => 26,
            ],
            [
                'text'         => $category_price,
                'height'       => 23,
                'width'        => 20,
                'limit'        => 36,
                'max_lines'    => 1,
                'line_spacing' => 0,
                'advance'      => 26,
            ],
            [
                'text'         => $ticket['attendee'],
                'height'       => 23,
                'width'        => 20,
                'limit'        => 36,
                'max_lines'    => 1,
                'line_spacing' => 0,
                'advance'      => 26,
            ],
        ];

        $zpl = [];
        $zpl[] = '^XA';
        $zpl[] = '^CI28';
        $zpl[] = '^PW' . (int) $this->settings['print_width'];
        $zpl[] = '^LL' . (int) $this->settings['label_length'];
        $zpl[] = '^LH0,0';
        $zpl[] = '^PR' . (int) $this->settings['print_speed'];
        $zpl[] = '^MD' . (int) $this->settings['print_darkness'];
        $zpl[] = '^FX Dynamic ticket fields.';

        $line_x = $text_x;
        foreach ( $lines as $line ) {
            $text = $line['text'] ?? '';
            $text = trim( (string) $text );
            if ( $text === '' ) {
                continue;
            }

            $zpl[] = sprintf(
                '^FO%d,%d^A0%s,%d,%d^FB%d,%d,%d,L,0^FH\\^FD%s^FS',
                $line_x,
                $text_y,
                $orient,
                (int) $line['height'],
                (int) $line['width'],
                $block_width,
                max( 1, (int) $line['max_lines'] ),
                max( 0, (int) $line['line_spacing'] ),
                $this->escape_zpl_field( $this->limit_text( $text, (int) $line['limit'] ) )
            );
            $line_x += max( 1, (int) ( $line['advance'] ?? $gap ) );
        }

        if ( $ticket['qr_value'] !== '' ) {
            $zpl[] = sprintf(
                '^FO%d,%d^BQN,%d,%d^FH\\^FDQA,%s^FS',
                (int) $this->settings['qr_x'],
                (int) $this->settings['qr_y'],
                (int) $this->settings['qr_model'],
                (int) $this->settings['qr_size'],
                $this->escape_zpl_field( $ticket['qr_value'] )
            );
        }

        $zpl[] = '^XZ';

        return implode( "\n", $zpl ) . "\n";
    }

    public function sendZpl( string $zpl ): bool {
        $host = trim( (string) $this->settings['host'] );
        $port = (int) $this->settings['port'];

        if ( $host === '' || $port <= 0 ) {
            throw new RuntimeException( 'Zebra printer host/port is not configured.' );
        }

        $errno  = 0;
        $errstr = '';
        $socket = @fsockopen( $host, $port, $errno, $errstr, (int) $this->settings['timeout'] );
        if ( ! $socket ) {
            throw new RuntimeException( sprintf( 'Printer connection failed: %s (%d)', $errstr, $errno ) );
        }

        $written = fwrite( $socket, $zpl );
        fclose( $socket );

        if ( $written === false || $written <= 0 ) {
            throw new RuntimeException( 'Printer write failed.' );
        }

        return true;
    }

    protected function sanitize_ticket_payload( array $ticket ): array {
        $defaults = self::default_test_ticket();
        $ticket   = array_merge( $defaults, $ticket );

        foreach ( $ticket as $key => $value ) {
            $ticket[ $key ] = sanitize_text_field( wp_strip_all_tags( self::decode_text_entities( (string) $value ) ) );
        }

        return $ticket;
    }

    protected static function decode_text_entities( string $value ): string {
        $charset = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'charset' ) : 'UTF-8';
        $decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, $charset ?: 'UTF-8' );

        $decoded = preg_replace_callback(
            '/&#(\d+);?/',
            static function( array $match ): string {
                $codepoint = (int) $match[1];
                return $codepoint > 0 && function_exists( 'mb_chr' ) ? mb_chr( $codepoint, 'UTF-8' ) : $match[0];
            },
            $decoded
        );

        $decoded = preg_replace_callback(
            '/&#x([0-9a-f]+);?/i',
            static function( array $match ): string {
                $codepoint = hexdec( $match[1] );
                return $codepoint > 0 && function_exists( 'mb_chr' ) ? mb_chr( $codepoint, 'UTF-8' ) : $match[0];
            },
            $decoded
        );

        return is_string( $decoded ) ? $decoded : $value;
    }

    protected function escape_zpl_field( string $value ): string {
        $value = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value );
        $value = str_replace(
            [ '\\', '^', '~' ],
            [ '\\5C', '\\5E', '\\7E' ],
            (string) $value
        );

        return $value;
    }

    protected function limit_text( string $value, int $limit ): string {
        $value = trim( $value );
        if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
            return mb_strlen( $value, 'UTF-8' ) > $limit
                ? mb_substr( $value, 0, $limit - 1, 'UTF-8' ) . '…'
                : $value;
        }

        return strlen( $value ) > $limit ? substr( $value, 0, $limit - 1 ) . '…' : $value;
    }

    protected function short_code_label( string $code ): string {
        $code = trim( $code );
        if ( $code === '' ) {
            return '';
        }

        if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) && mb_strlen( $code, 'UTF-8' ) > 18 ) {
            return '...' . mb_substr( $code, -15, null, 'UTF-8' );
        }

        return strlen( $code ) > 18 ? '...' . substr( $code, -15 ) : $code;
    }

    protected static function format_slot_label( string $slot_start, string $slot_end = '' ): string {
        $slot_start = trim( $slot_start );
        if ( $slot_start === '' ) {
            return '';
        }

        try {
            $start = new DateTimeImmutable( $slot_start, wp_timezone() );
            $label = wp_date( 'd/m/Y', $start->getTimestamp(), wp_timezone() );
            $time  = wp_date( 'H:i', $start->getTimestamp(), wp_timezone() );

            $slot_end = trim( $slot_end );
            if ( $slot_end !== '' ) {
                $end = new DateTimeImmutable( $slot_end, wp_timezone() );
                $time .= ' - ' . wp_date( 'H:i', $end->getTimestamp(), wp_timezone() );
            }

            return trim( $label . ' · ' . $time );
        } catch ( Throwable $e ) {
            return $slot_start;
        }
    }

    protected static function format_date_label( string $date ): string {
        $date = trim( $date );
        if ( $date === '' ) {
            return '';
        }

        try {
            $value = new DateTimeImmutable( $date, wp_timezone() );
            return wp_date( 'd/m/Y', $value->getTimestamp(), wp_timezone() );
        } catch ( Throwable $e ) {
            return $date;
        }
    }
}
