<?php
class IW_Ticketing_ICS{
    public static function get_calendar_ics_url_for_order( $order_id ): string {
        $order_id = is_numeric( $order_id ) ? (int) $order_id : 0;
        if ( $order_id <= 0 ) return '';
        $nonce = wp_create_nonce( 'iw_ticket_ics_' . $order_id );
        return add_query_arg( [ 'iw_ticket_ics' => 1, 'mode' => 'order', 'order_id' => $order_id, '_wpnonce' => $nonce ], home_url( '/' ) );
    }

    /**
     * Guest-safe: signed, expiring URL that returns an .ics for a specific slot.
     * Intended for emails / confirmation screens even for non-logged-in users.
     */
    public static function get_calendar_ics_url_for_slot( int $post_id, $slot_start, $slot_end = null, int $expires_in_seconds = 1209600 ): string {
        $post_id = (int) $post_id;
        if ( $post_id <= 0 ) return '';

        $tz = wp_timezone();

        try {
            $start = $slot_start instanceof DateTimeInterface
                ? new DateTimeImmutable( $slot_start->format( 'Y-m-d H:i:s' ), $slot_start->getTimezone() )
                : new DateTimeImmutable( (string) $slot_start, $tz );
        } catch ( Throwable $e ) {
            return '';
        }

        try {
            $end = null;
            if ( $slot_end instanceof DateTimeInterface ) {
                $end = new DateTimeImmutable( $slot_end->format( 'Y-m-d H:i:s' ), $slot_end->getTimezone() );
            } elseif ( ! empty( $slot_end ) ) {
                $end = new DateTimeImmutable( (string) $slot_end, $tz );
            }
        } catch ( Throwable $e ) {
            $end = null;
        }

        if ( ! $end ) {
            $end = $start->modify( '+60 minutes' );
        }

        $exp = time() + max( 300, (int) $expires_in_seconds ); // min 5 minutes

        $payload = implode( '|', [
            $post_id,
            $start->format( 'Y-m-d H:i:s' ),
            $end->format( 'Y-m-d H:i:s' ),
            $exp,
        ] );

        $sig = hash_hmac( 'sha256', $payload, wp_salt( 'iw_ticketing_ics' ) );

        return add_query_arg(
            [
                'iw_ticket_ics' => 1,
                'mode'          => 'slot',
                'post_id'       => $post_id,
                'slot_start'    => $start->format( 'Y-m-d H:i:s' ),
                'slot_end'      => $end->format( 'Y-m-d H:i:s' ),
                'exp'           => $exp,
                'sig'           => $sig,
            ],
            home_url( '/' )
        );
    }

    /**
     * Generate an ICS (iCalendar) string for an order/slot summary.
     */
    public static function build_order_ics( array $order_summary, array $tickets_rows ): string {
        // Use the order-level slot (first ticket slot) as the event.


        $post_id   = (int) $order_summary['post_id'];
        $title     = $post_id ? get_the_title( $post_id ) : __( 'Tickets', 'iw' );
        $count     = (int) ( $order_summary['tickets_count'] ?? 0 );
        $order_id  = (int) ( $order_summary['order_id'] ?? 0 );

        $tz = wp_timezone();

        $start = $order_summary['slot_start'] ?? null;
        $end   = $order_summary['slot_end'] ?? null;

        if (is_string($start) && trim($start) !== '') {
            $start = new DateTimeImmutable(trim($start), $tz);
        }
        if (is_string($end) && trim($end) !== '') {
            $end = new DateTimeImmutable(trim($end), $tz);
        }

        if ($start instanceof DateTimeInterface && !($end instanceof DateTimeInterface)) {
            $end = (new DateTimeImmutable($start->format('Y-m-d H:i:s'), $start->getTimezone()))->modify('+60 minutes');
        }

        if (!($start instanceof DateTimeInterface)) {
            return '';
        }




        $tz_utc = new DateTimeZone( 'UTC' );
        $dtstart = ( new DateTimeImmutable( $start->format( 'Y-m-d H:i:s' ), $start->getTimezone() ) )->setTimezone( $tz_utc );
        $dtend   = $end instanceof DateTimeInterface
            ? ( new DateTimeImmutable( $end->format( 'Y-m-d H:i:s' ), $end->getTimezone() ) )->setTimezone( $tz_utc )
            : $dtstart->modify( '+60 minutes' );



        $permalink = $post_id ? get_permalink( $post_id ) : '';
        $summary = sprintf( '%s (%d)', $title, $count );

        $description_lines = [];

        $building_location = get_field( 'building_location', $post_id );
        $location = '';

        if ( $building_location ) {
            $building_title = strip_tags( get_the_title( $building_location ) );
            if ( $building_title !== '' ) {
                $description_lines[] = $building_title;
                $location = $building_title;
            }

            if ( ! empty( $building_address = get_field( 'address', $building_location ) ) ) {
                $addr_title = (string) ( $building_address['title'] ?? '' );
                $addr_url   = (string) ( $building_address['url'] ?? '' );

                // Keep the maps link in description (nice for all clients).
                if ( $addr_title !== '' && $addr_url !== '' ) {
                    $description_lines[] = $addr_title . ': ' . $addr_url;
                } elseif ( $addr_title !== '' ) {
                    $description_lines[] = $addr_title;
                } elseif ( $addr_url !== '' ) {
                    $description_lines[] = $addr_url;
                }

                // LOCATION field: include address title, and append the maps URL (Mac Calendar shows it as text).
                if ( $addr_title !== '' ) {
                    $location = $location ? ( $location . ', ' . $addr_title ) : $addr_title;
                }
                if ( $addr_url !== '' ) {
                    $location = $location ? ( $location . ' - ' . $addr_url ) : $addr_url;
                }
            }
        }

        $description_lines[] = __( 'Πληροφορίες' ) . ': ' . $permalink;

        $description = implode( "\n\n", $description_lines );


        $uid = 'iw-tickets-' . ( $order_id > 0 ? $order_id : md5( $summary . $dtstart->format('c') ) ) . '@' . parse_url( home_url(), PHP_URL_HOST );


        // ICS must use CRLF.
        $lines = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//IW Ticketing//EN';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:' . self::ics_escape( $uid );
        $lines[] = 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' );
        $lines[] = 'DTSTART:' . $dtstart->format( 'Ymd\THis\Z' );
        $lines[] = 'DTEND:' . $dtend->format( 'Ymd\THis\Z' );
        $lines[] = 'SUMMARY:' . self::ics_escape( $summary );

        if ( ! empty( $location ) ) {
            $lines[] = 'LOCATION:' . self::ics_escape( $location );
        }

        $lines[] = 'DESCRIPTION:' . self::ics_escape( $description );
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode( "\r\n", $lines ) . "\r\n";
    }

    /**
     * Return all calendar URLs (ics, google, outlook_web, yahoo) for a given slot.
     * Safe for email usage. Dates are converted to UTC where required.
     */
    public static function get_calendar_links( int $post_id, $slot_start, $slot_end = null ): array {
        $post_id = (int) $post_id;
        if ( $post_id <= 0 ) {
            return [];
        }

        $tz = wp_timezone();

        try {
            $start = $slot_start instanceof DateTimeInterface
                ? new DateTimeImmutable( $slot_start->format( 'Y-m-d H:i:s' ), $slot_start->getTimezone() )
                : new DateTimeImmutable( (string) $slot_start, $tz );
        } catch ( Throwable $e ) {
            return [];
        }

        try {
            if ( $slot_end instanceof DateTimeInterface ) {
                $end = new DateTimeImmutable( $slot_end->format( 'Y-m-d H:i:s' ), $slot_end->getTimezone() );
            } elseif ( ! empty( $slot_end ) ) {
                $end = new DateTimeImmutable( (string) $slot_end, $tz );
            } else {
                $end = null;
            }
        } catch ( Throwable $e ) {
            $end = null;
        }

        if ( ! $end ) {
            $end = $start->modify( '+60 minutes' );
        }

        $tz_utc = new DateTimeZone( 'UTC' );
        $dtstart_utc = $start->setTimezone( $tz_utc );
        $dtend_utc   = $end->setTimezone( $tz_utc );

        $title = strip_tags(get_the_title( $post_id ));
        $permalink = get_permalink( $post_id );

        $building_location = get_field( 'building_location', $post_id );
        $location = '';
        if ( $building_location ) {
            $building_title = strip_tags( get_the_title( $building_location ) );
            if ( $building_title !== '' ) {
                $location = $building_title;
            }

            if ( ! empty( $building_address = get_field( 'address', $building_location ) ) ) {
                $addr_title = (string) ( $building_address['title'] ?? '' );
                $addr_url   = (string) ( $building_address['url'] ?? '' );

                if ( $addr_title !== '' ) {
                    $location = $location ? ( $location . ', ' . $addr_title ) : $addr_title;
                }

                // Optional: include maps URL in location string so it survives in clients that don't render DESCRIPTION links.
                if ( $addr_url !== '' ) {
                    $location = $location . ' - ' . $addr_url;
                }
            }
        }

        $description = __( 'Πληροφορίες', 'iw' ) . ': ' . $permalink;

        // ICS (guest-safe signed URL)
        $ics_url = self::get_calendar_ics_url_for_slot( $post_id, $start, $end );

        // Google Calendar
        $google_args = [
            'action'  => 'TEMPLATE',
            'text'    => $title,
            'dates'   => $dtstart_utc->format( 'Ymd\THis\Z' ) . '/' . $dtend_utc->format( 'Ymd\THis\Z' ),
            'details' => $description,
            'location'=> $location,
            'ctz'     => 'Europe/Athens',
        ];
        $google_url = 'https://calendar.google.com/calendar/render?' . http_build_query( $google_args );

        // Outlook Web (Office 365 / Outlook.com)
        $outlook_web_args = [
            'path'     => '/calendar/action/compose',
            'rru'      => 'addevent',
            'subject'  => $title,
            'startdt'  => $start->format( 'Y-m-d\TH:i:s' ),
            'enddt'    => $end->format( 'Y-m-d\TH:i:s' ),
            'body'     => $description,
            'location' => $location,
            'allday'   => 'false',
        ];
        $outlook_web_url = 'https://outlook.office.com/calendar/0/deeplink/compose?' . http_build_query( $outlook_web_args );

        // Yahoo Calendar
        $duration_minutes = max( 1, (int) round( ( $end->getTimestamp() - $start->getTimestamp() ) / 60 ) );
        $hours   = floor( $duration_minutes / 60 );
        $minutes = $duration_minutes % 60;
        $yahoo_dur = sprintf( '%02d%02d', $hours, $minutes );

        $yahoo_args = [
            'v'     => '60',
            'title' => $title,
            'st'    => $dtstart_utc->format( 'Ymd\THis\Z' ),
            'dur'   => $yahoo_dur,
            'desc'  => $description,
            'in_loc'=> $location,
        ];
        $yahoo_url = 'https://calendar.yahoo.com/?' . http_build_query( $yahoo_args );

        return [
            'ics'          => $ics_url,
            'google'       => $google_url,
            'outlook_web'  => $outlook_web_url,
            'yahoo'        => $yahoo_url,
        ];
    }

    /**
     * Escape text for ICS fields.
     */
    private static function ics_escape( string $value ): string {
        // Normalize line breaks.
        $value = str_replace( "\r", '', $value );

        // Escape according to RFC5545: backslash, semicolon, comma.
        // IMPORTANT: Do this before newline conversion, otherwise the backslash
        // we introduce for "\\n" will get double-escaped and render as literal "\\n".
        $value = str_replace( [ '\\', ';', ',' ], [ '\\\\', '\\;', '\\,' ], $value );

        // Convert actual newlines to the literal sequence \n expected by iCalendar.
        $value = str_replace( "\n", "\\n", $value );

        return $value;
    }

    /**
     * Validate slot-based ICS request signature + expiry, and verify that the slot exists in schedule.
     */
    public static function validate_slot_ics_request( int $post_id, string $slot_start, string $slot_end, int $exp, string $sig ): bool {
        if ( $post_id <= 0 || $slot_start === '' || $slot_end === '' || $exp <= 0 || $sig === '' ) {
            return false;
        }

        if ( time() > $exp ) {
            return false;
        }

        $payload = implode( '|', [ $post_id, $slot_start, $slot_end, $exp ] );
        $expected = hash_hmac( 'sha256', $payload, wp_salt( 'iw_ticketing_ics' ) );
        if ( ! hash_equals( $expected, $sig ) ) {
            return false;
        }

        // Hardening: ensure slot_start is actually offered for this post in schedule.
        try {
            $tz = wp_timezone();
            $start = new DateTimeImmutable( $slot_start, $tz );
            $date = $start->format( 'Y-m-d' );
            $time = $start->format( 'H:i' );

            $schedule = IW_Tickets_Calendar_Service::get_schedule( $post_id );
            if ( empty( $schedule ) || empty( $schedule['allowDates'] ) || empty( $schedule['timesByDate'] ) ) {
                return false;
            }

            if ( ! in_array( $date, (array) $schedule['allowDates'], true ) ) {
                return false;
            }

            $times = [];
            if ( is_object( $schedule['timesByDate'] ) && isset( $schedule['timesByDate']->$date ) ) {
                $times = (array) $schedule['timesByDate']->$date;
            } elseif ( is_array( $schedule['timesByDate'] ) && isset( $schedule['timesByDate'][ $date ] ) ) {
                $times = (array) $schedule['timesByDate'][ $date ];
            }

            $found = false;
            foreach ( $times as $slot ) {
                $slot_time = is_array( $slot ) ? (string) ( $slot['time'] ?? '' ) : '';
                if ( $slot_time === $time ) {
                    $found = true;
                    break;
                }
            }

            if ( ! $found ) {
                return false;
            }
        } catch ( Throwable $e ) {
            return false;
        }

        return true;
    }
}


// Serve calendar.ics (order mode for logged-in users, slot mode for guests via signed URL).
add_action( 'template_redirect', function () {
    if ( empty( $_GET['iw_ticket_ics'] ) ) return;
    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    $mode = isset( $_GET['mode'] ) ? (string) $_GET['mode'] : 'order';

    // ------------------------------
    // MODE: ORDER (logged-in)
    // ------------------------------
    if ( $mode === 'order' ) {
        $order_id = isset( $_GET['order_id'] ) && is_numeric( $_GET['order_id'] ) ? (int) $_GET['order_id'] : 0;
        if ( $order_id <= 0 ) { status_header( 400 ); exit; }

        if ( ! is_user_logged_in() ) { status_header( 401 ); exit; }

        $nonce = (string) ( $_GET['_wpnonce'] ?? '' );
        if ( ! wp_verify_nonce( $nonce, 'iw_ticket_ics_' . $order_id ) ) { status_header( 403 ); exit; }

        $data = IW_Ticketing::get_order_tickets( $order_id );
        if ( ! empty( $data['forbidden'] ) ) { status_header( 403 ); exit; }
        if ( empty( $data ) || empty( $data['order'] ) ) { status_header( 404 ); exit; }

        // get_order_tickets() returns 'items' (ticket entries). Keep empty array if none.
        $tickets = ! empty( $data['tickets'] ) ? (array) $data['tickets'] : [];


        // Build an order_summary from the first item (slot/post) if available.
        $first = $tickets[0] ?? null;

        $order_summary = [
            'order_id'      => $order_id,
            'post_id'          => $first->post_id,
            'slot_start'    => $first->slot_start ?? null,
            'slot_end'      => $first->slot_end ?? null,
            'tickets_count' => count( $tickets ),
        ];

        

        $ics = IW_Ticketing_ICS::build_order_ics( $order_summary, $tickets );
        if ( $ics === '' ) { status_header( 500 ); exit; }

        nocache_headers();
        header( 'Content-Type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="calendar.ics"' );
        echo $ics;
        exit;
    }

    // ------------------------------
    // MODE: SLOT (guest-safe, signed)
    // ------------------------------
    if ( $mode === 'slot' ) {
        $post_id    = isset( $_GET['post_id'] ) && is_numeric( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
        $slot_start = isset( $_GET['slot_start'] ) ? (string) $_GET['slot_start'] : '';
        $slot_end   = isset( $_GET['slot_end'] ) ? (string) $_GET['slot_end'] : '';
        $exp        = isset( $_GET['exp'] ) && is_numeric( $_GET['exp'] ) ? (int) $_GET['exp'] : 0;
        $sig        = isset( $_GET['sig'] ) ? (string) $_GET['sig'] : '';

        if ( ! IW_Ticketing_ICS::validate_slot_ics_request( $post_id, $slot_start, $slot_end, $exp, $sig ) ) {
            status_header( 403 );
            exit;
        }

        $post = $post_id ? get_post( $post_id ) : null;
        if ( ! $post ) { status_header( 404 ); exit; }

        // Build a minimal order-like summary for ICS builder.
        $tz = wp_timezone();
        try {
            $start = new DateTimeImmutable( $slot_start, $tz );
            $end   = new DateTimeImmutable( $slot_end, $tz );
        } catch ( Throwable $e ) {
            status_header( 400 );
            exit;
        }

        $summary = [
            'order_id'      => 0,
            'post'          => $post,
            'slot_start'    => $start,
            'slot_end'      => $end,
            'tickets_count' => 1,
        ];

        $ics = IW_Ticketing_ICS::build_order_ics( $summary, [] );
        if ( $ics === '' ) { status_header( 500 ); exit; }

        nocache_headers();
        header( 'Content-Type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="calendar.ics"' );
        echo $ics;
        exit;
    }

    status_header( 400 );
    exit;
} );
