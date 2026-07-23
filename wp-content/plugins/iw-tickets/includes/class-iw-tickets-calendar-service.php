<?php


class IW_Tickets_Calendar_Service {
    
    public static function get_orthodox_easter( $year ){
        // Meeus Julian algorithm -> convert to Gregorian
        $a     = $year % 4;
        $b     = $year % 7;
        $c     = $year % 19;
        $d     = ( 19 * $c + 15 ) % 30;
        $e     = ( 2 * $a + 4 * $b - $d + 34 ) % 7;
        $month = intdiv( $d + $e + 114, 31 ); // 3=March, 4=April (Julian)
        $day   = ( ( $d + $e + 114 ) % 31 ) + 1;

        // Julian calendar date
        $julian = new DateTimeImmutable( sprintf( '%04d-%02d-%02d', $year, $month, $day ), wp_timezone() );

        // Convert Julian -> Gregorian by adding the calendar difference (13 days for 1900-2099)
        // This is sufficient for our current operating years.
        $gregorian = $julian->modify( '+13 days' );

        // Orthodox Easter is the Sunday after the Paschal full moon; the Julian computation above yields that Sunday.
        return $gregorian;
    }

    public static function get_gr_holidays( $year ){

        /**
         * Greek holidays / excluded dates for a given year as Y-m-d strings.
         * - Fixed recurring dates come from options repeater (kind=recurring, month_day=MM-DD).
         * - One-off excluded dates come from options repeater (kind=single, date=Y-m-d).
         * - Movable (Orthodox, Easter-based) exclusions are included only when the option toggle is enabled.
         */

        $tz = wp_timezone();
        $year = (int) $year;

        $fixed  = [];
        $single = [];

        // Read options repeater for excluded dates.
        if ( function_exists( 'get_field' ) ) {
            $rows = get_field( 'iw_ticketing_excluded_dates', 'option' );
            $rows = is_array( $rows ) ? $rows : [];

            foreach ( $rows as $row ) {

                $kind = isset( $row['kind'] ) ? (string) $row['kind'] : 'recurring';

                if ( $kind === 'recurring' ) {
                    $md = isset( $row['month_day'] ) ? trim( (string) $row['month_day'] ) : '';
                    if ( $md === '' ) {
                        continue;
                    }

                    // Expect MM-DD.
                    if ( ! preg_match( '/^(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/', $md ) ) {
                        continue;
                    }

                    $fixed[] = sprintf( '%04d-%s', $year, $md );
                    continue;
                }

                if ( $kind === 'single' ) {
                    $d = isset( $row['date'] ) ? (string) $row['date'] : '';
                    if ( $d === '' ) {
                        continue;
                    }

                    // date_picker return_format is configured as Y-m-d, but normalize defensively.
                    $ymd = self::normalize_date_to_ymd( $d, $tz );
                    if ( preg_match( '/^' . preg_quote( (string) $year, '/' ) . '-\d{2}-\d{2}$/', $ymd ) ) {
                        $single[] = $ymd;
                    }
                }
            }
        }

        // Fallback fixed list if options are unavailable/empty.
        if ( empty( $fixed ) ) {
            $fixed = [
                sprintf( '%04d-01-01', $year ),
                sprintf( '%04d-01-06', $year ),
                sprintf( '%04d-03-25', $year ),
                sprintf( '%04d-05-01', $year ),
                sprintf( '%04d-08-15', $year ),
                sprintf( '%04d-10-28', $year ),
                sprintf( '%04d-12-25', $year ),
                sprintf( '%04d-12-26', $year ),
            ];
        }

        // Movable (Orthodox, Easter-based) exclusions.
        $include_movable = true;
        if ( function_exists( 'get_field' ) ) {
            $v = get_field( 'iw_ticketing_exclude_orthodox_movable_holidays', 'option' );
            if ( $v !== null && $v !== '' ) {
                $include_movable = (bool) $v;
            }
        }

        $movable = [];
        if ( $include_movable ) {
            $easter = self::get_orthodox_easter( $year )->setTimezone( $tz );

            $clean_monday       = $easter->modify( '-48 days' );
            $easter_sunday      = $easter;
            $easter_monday      = $easter->modify( '+1 day' );
            $holy_spirit_monday = $easter->modify( '+50 days' );

            $movable = [
                $clean_monday->format( 'Y-m-d' ),
                $easter_sunday->format( 'Y-m-d' ),
                $easter_monday->format( 'Y-m-d' ),
                $holy_spirit_monday->format( 'Y-m-d' ),
            ];
        }

        return array_values( array_unique( array_merge( $fixed, $single, $movable ) ) );
    }
    public static function get_schedule( int $tickets_for_post_id, $include_staff = false,  $from = 'now', $days_ahead = 60, $holidays_only = false )
    {

        // Generate allowDates for the next N days (inclusive), excluding closed weekdays and holidays.


        // Mode-aware overrides
        $mode = function_exists('get_field') ? (string)get_field('opening_hours_mode', $tickets_for_post_id) : '';

        // Notes-only => no selectable dates
        if ($mode === 'from_notes') {
            return [
                'allowDates' => [],
                'soldOutDates' => [],
                'limitedDates' => [],
                'timesByDate' => (object)[],
            ];
        }
        // Specific days => use explicit list (skip weekday iteration & holiday exclusion by default)
        else if ($mode === 'specific_days' ) {
            $rows = get_field('choose_specifics_days', $tickets_for_post_id);
            $rows = is_array($rows) ? $rows : [];

            $allow_dates = [];
            $timesByDate = [];
            foreach ($rows as $row) {
                $date = isset($row['choose_date']) ? (string)$row['choose_date'] : '';
                if ($date === '') continue;

                // Convert date to Y-m-d
                $tz = wp_timezone();
                $date_ymd = self::normalize_date_to_ymd($date, $tz);

                // Collect non-cancelled times for this specific date
                $times_rows = isset($row['choose_time']) && is_array($row['choose_time']) ? $row['choose_time'] : [];
                $slots = [];
                $times = [];
                foreach ($times_rows as $trow) {
                    if (!empty($trow['cancel'])) {
                        continue;
                    }
                    $start = isset($trow['time']) ? (string)$trow['time'] : '';
                    $end = isset($trow['time_to']) ? (string)$trow['time_to'] : null;
                    $capacity = isset($trow['capacity']) && $trow['capacity'] !== '' ? (int)$trow['capacity'] : null;
                    if ($start !== '') {
                        $status = ($capacity !== null && $capacity <= 0) ? 'sold-out' : 'available';
                        $slot = ['time' => $start];
                        if ($end !== null && $end !== '') $slot['end'] = $end;
                        if ($capacity !== null) $slot['capacity'] = $capacity;
                        $slot['status'] = $status;
                        if( $include_staff ){
                            $slot['staff'] = $trow['staff'];
                        }
                        $slots[] = $slot;
                        $times[] = $start; // backward compat
                    }
                }
                $times = array_values(array_unique($times));
                sort($times);
                if (!empty($slots)) {
                    $allow_dates[] = $date_ymd;
                    $timesByDate[$date_ymd] = self::slots_to_objects($slots);
                }
            }

            $allow_dates = array_values(array_unique($allow_dates));

            // Active window for exceptions application.
            $tz = wp_timezone();
            if ($from === 'now' || empty($from)) {
                $today_dt = current_datetime();
                $today = new DateTimeImmutable($today_dt->format('Y-m-d'), $tz);
            } else {
                $from_str = (string)$from;
                $dt = DateTimeImmutable::createFromFormat('Y-m-d', $from_str, $tz);
                if (!$dt) {
                    $dt = DateTimeImmutable::createFromFormat('d-m-Y', $from_str, $tz);
                }
                if (!$dt) {
                    $dt = new DateTimeImmutable($from_str, $tz);
                }
                $today = $dt->setTime(0, 0, 0);
            }
            $window_from = $today->format('Y-m-d');
            $window_to   = $today->modify("+{$days_ahead} days")->format('Y-m-d');

            // Apply exceptions (cancel ranges / override days) within the active window.
            $closed_hints = [];
            self::apply_exceptions_to_allow_dates( $tickets_for_post_id, $allow_dates, $timesByDate, $window_from, $window_to, $closed_hints );

            // Booking window filtering (absolute/relative/inherit)
            self::apply_booking_rules_filter( $tickets_for_post_id, $allow_dates, $timesByDate );

            // Apply inventory (booked/reserved/availability/status)
            self::apply_inventory_to_times_by_date( $tickets_for_post_id, $timesByDate );
            self::apply_past_time_filter( $allow_dates, $timesByDate );

            // Compute soldOutDates and limitedDates from slot data
            $sold_out_dates = [];
            $limited_dates = [];
            foreach ($allow_dates as $date) {
                $day_slots = $timesByDate[$date] ?? [];
                if (empty($day_slots)) continue;
                $all_sold_out = true;
                $any_limited = false;
                foreach ($day_slots as $slot) {
                    if (isset($slot['status']) && $slot['status'] !== 'sold-out') {
                        $all_sold_out = false;
                    }
                    if (isset($slot['status']) && $slot['status'] === 'limited') {
                        $any_limited = true;
                    }
                }
                if ($all_sold_out) {
                    $sold_out_dates[] = $date;
                } elseif ($any_limited) {
                    $limited_dates[] = $date;
                }
            }

            return [
                'allowDates' => $allow_dates,
                'soldOutDates' => $sold_out_dates,
                'limitedDates' => $limited_dates,
                'holidays' => array_values( array_map( static function( $d ) use ( $closed_hints ) { return [ 'date' => $d, 'label' => (string) $closed_hints[ $d ] ]; }, array_keys( $closed_hints ) ) ),
                'closedExceptions' => (object) $closed_hints,
                'timesByDate' => (object)$timesByDate,
            ];
        }


        // IMPORTANT: opening hours come from the building, but booking rules / inventory / min-max dates must come from the tickets-for post.
        else if ($mode === 'museum_hours' ) {
            $building = get_field('building_location', $tickets_for_post_id);
            $building_id = 0;

            if (is_object($building) && isset($building->ID)) {
                $building_id = (int)$building->ID;
            } elseif (is_numeric($building)) {
                $building_id = (int)$building;
            }

            // If we can't resolve a building, fall back to the post itself.
            if ( $building_id > 0 ) {
                // We will use the building as the *schedule source* (opening hours fields),
                // but continue the pipeline (exceptions, booking rules, inventory) using $tickets_for_post_id.
                $schedule_source_post_id = $building_id;
            } else {
                $schedule_source_post_id = $tickets_for_post_id;
            }
        } else {
            $schedule_source_post_id = $tickets_for_post_id;
        }

        $tz = wp_timezone();

        // Normalize $days_ahead
        $days_ahead = absint($days_ahead);

        // If we're generating from "now", make sure we generate enough days to cover the earliest booking window.
        // Otherwise the calendar may stop early (e.g. 60 days) even if earliest booking allows 90+ days.
        if ( $from === 'now' || empty( $from ) ) {
            $rules = self::get_booking_rules( $tickets_for_post_id );
            // Only meaningful for relative/inherit modes. Absolute mode is independent of slot time.
            if ( isset( $rules['mode'] ) && $rules['mode'] !== 'absolute' ) {
                $earliest_minutes = isset( $rules['earliest_minutes'] ) ? (int) $rules['earliest_minutes'] : 0;
                if ( $earliest_minutes > 0 ) {
                    $earliest_days = (int) ceil( $earliest_minutes / 1440 );
                    if ( $earliest_days > $days_ahead ) {
                        // Cap to 365 days to avoid huge calendars.
                        $days_ahead = min( 365, $earliest_days );
                    }
                }
            }
        }

        // Resolve the starting date
        if ($from === 'now' || empty($from)) {
            $today_dt = current_datetime(); // WP_DateTime (site timezone)
            $today = new DateTimeImmutable($today_dt->format('Y-m-d'), $tz);
        } else {
            // Accept 'Y-m-d' or 'd-m-Y'
            $from_str = (string)$from;

            $dt = DateTimeImmutable::createFromFormat('Y-m-d', $from_str, $tz);
            if (!$dt) {
                $dt = DateTimeImmutable::createFromFormat('d-m-Y', $from_str, $tz);
            }

            // Fallback: let PHP try to parse it
            if (!$dt) {
                $dt = new DateTimeImmutable($from_str, $tz);
            }

            $today = $dt->setTime(0, 0, 0);
        }

        // Active window for exceptions application.
        $window_from = $today->format('Y-m-d');
        $window_to   = $today->modify("+{$days_ahead} days")->format('Y-m-d');

        $holiday_set = [];
        for ($y = (int)$today->format('Y'); $y <= (int)$today->modify("+{$days_ahead} days")->format('Y'); $y++) {
            foreach (self::get_gr_holidays($y) as $h) {
                $holiday_set[$h] = true;
            }
        }

        $holiday_dates = array_keys( $holiday_set );
        $holiday_dates = array_values( array_filter( $holiday_dates, static function( $d ) use ( $window_from, $window_to ) {
            return $d >= $window_from && $d <= $window_to;
        } ) );
        sort( $holiday_dates );

        $holidays = [];

        // Build labels from options repeater (if available)
        $label_map = [];
        if ( function_exists( 'get_field' ) ) {
            $rows = get_field( 'iw_ticketing_excluded_dates', 'option' );
            $rows = is_array( $rows ) ? $rows : [];

            foreach ( $rows as $row ) {
                $kind = isset( $row['kind'] ) ? (string) $row['kind'] : '';
                $label = isset( $row['label'] ) ? (string) $row['label'] : '';

                if ( $kind === 'recurring' && ! empty( $row['month_day'] ) ) {
                    $label_map[ $row['month_day'] ] = $label;
                }

                if ( $kind === 'single' && ! empty( $row['date'] ) ) {
                    $label_map[ $row['date'] ] = $label;
                }
            }
        }

        // Add labels for Orthodox movable holidays (if enabled).
        $include_movable = true;
        if ( function_exists( 'get_field' ) ) {
            $v = get_field( 'iw_ticketing_exclude_orthodox_movable_holidays', 'option' );
            if ( $v !== null && $v !== '' ) {
                $include_movable = (bool) $v;
            }
        }

        if ( $include_movable ) {
            $y_from = (int) $today->format( 'Y' );
            $y_to   = (int) $today->modify( "+{$days_ahead} days" )->format( 'Y' );

            for ( $y = $y_from; $y <= $y_to; $y++ ) {
                $easter = self::get_orthodox_easter( $y )->setTimezone( $tz );

                $clean_monday       = $easter->modify( '-48 days' )->format( 'Y-m-d' );
                $easter_sunday      = $easter->format( 'Y-m-d' );
                $easter_monday      = $easter->modify( '+1 day' )->format( 'Y-m-d' );
                $holy_spirit_monday = $easter->modify( '+50 days' )->format( 'Y-m-d' );

                // Only set if not already labelled by options.
                if ( ! isset( $label_map[ $clean_monday ] ) || $label_map[ $clean_monday ] === '' ) {
                    $label_map[ $clean_monday ] = __( 'Καθαρά Δευτέρα', 'iw-theme' );
                }
                if ( ! isset( $label_map[ $easter_sunday ] ) || $label_map[ $easter_sunday ] === '' ) {
                    $label_map[ $easter_sunday ] = __( 'Κυριακή του Πάσχα', 'iw-theme' );
                }
                if ( ! isset( $label_map[ $easter_monday ] ) || $label_map[ $easter_monday ] === '' ) {
                    $label_map[ $easter_monday ] = __( 'Δευτέρα του Πάσχα', 'iw-theme' );
                }
                if ( ! isset( $label_map[ $holy_spirit_monday ] ) || $label_map[ $holy_spirit_monday ] === '' ) {
                    $label_map[ $holy_spirit_monday ] = __( 'Αγίου Πνεύματος', 'iw-theme' );
                }
            }
        }

        foreach ( $holiday_dates as $d ) {
            $md = substr( $d, 5 ); // MM-DD
            $label = '';

            if ( isset( $label_map[ $d ] ) && $label_map[ $d ] !== '' ) {
                $label = $label_map[ $d ];
            } elseif ( isset( $label_map[ $md ] ) && $label_map[ $md ] !== '' ) {
                $label = $label_map[ $md ];
            }

            if ( $label === '' ) {
                $label = __( 'Public holiday', 'iw-theme' );
            }

            $holidays[] = [
                'date'  => $d,
                'label' => $label,
            ];
        }

        if( $holidays_only ){
            return $holidays;
        }

        $allow_dates = [];
        $timesByDate = [];
        for ($i = 0; $i <= $days_ahead; $i++) {
            $d = $today->modify("+{$i} days");
            // Holiday
            $ymd = $d->format('Y-m-d');
            if (isset($holiday_set[$ymd])) {
                continue;
            }
            // Build time slots for this date (source can be building when mode=museum_hours)
            $slot_data = self::get_time_slots_for_date($schedule_source_post_id, $ymd, $include_staff, 60);

            // Only allow dates that have at least one selectable slot/time.
            if (!empty($slot_data['slots'])) {
                $allow_dates[] = $ymd;
                $timesByDate[$ymd] = self::slots_to_objects($slot_data['slots']);
            } elseif (!empty($slot_data['times'])) {
                $allow_dates[] = $ymd;
                $timesByDate[$ymd] = self::times_to_objects($slot_data['times']);
            } else {
                // No opening hours for that day => do not include as selectable.
                continue;
            }
        }

        // Apply exceptions (cancel ranges / override days) within the active window.
        $closed_hints = [];
        self::apply_exceptions_to_allow_dates( $tickets_for_post_id, $allow_dates, $timesByDate, $window_from, $window_to, $closed_hints );


        // Booking window filtering (absolute/relative/inherit)
        self::apply_booking_rules_filter( $tickets_for_post_id, $allow_dates, $timesByDate );

        // Apply inventory (booked/reserved/availability/status)
        self::apply_inventory_to_times_by_date( $tickets_for_post_id, $timesByDate );
        self::apply_past_time_filter( $allow_dates, $timesByDate );
        // Apply post-level min/max date constraints (must come from the tickets-for post, not the building).
        self::apply_min_max_date_filter( $tickets_for_post_id, $allow_dates, $timesByDate );

        // Compute soldOutDates and limitedDates from slot data
        $sold_out_dates = [];
        $limited_dates = [];
        foreach ($allow_dates as $date) {
            $day_slots = $timesByDate[$date] ?? [];
            if (empty($day_slots)) continue;
            $all_sold_out = true;
            $any_limited = false;
            foreach ($day_slots as $slot) {
                if (isset($slot['status']) && $slot['status'] !== 'sold-out') {
                    $all_sold_out = false;
                }
                if (isset($slot['status']) && $slot['status'] === 'limited') {
                    $any_limited = true;
                }
            }
            if ($all_sold_out) {
                $sold_out_dates[] = $date;
            } elseif ($any_limited) {
                $limited_dates[] = $date;
            }
        }
        // Add exception-closed dates as additional disabled dates with their note.
        if ( ! empty( $closed_hints ) ) {
            $existing_dates = [];
            foreach ( $holidays as $h ) {
                if ( isset( $h['date'] ) ) {
                    $existing_dates[ (string) $h['date'] ] = true;
                }
            }
            foreach ( $closed_hints as $d => $label ) {
                // If the date is already a holiday, keep the holiday label (do not override).
                if ( isset( $existing_dates[ $d ] ) ) {
                    continue;
                }
                $holidays[] = [
                    'date'  => (string) $d,
                    'label' => (string) $label,
                ];
            }
        }

        $datepicker_options = [
            'allowDates' => $allow_dates,
            'soldOutDates' => $sold_out_dates,
            'limitedDates' => $limited_dates,
            'holidays' => $holidays,
            'closedExceptions' => (object) $closed_hints,
            'timesByDate' => (object)$timesByDate,
        ];

        return $datepicker_options;
    }
    /**
     * Normalize an ACF group value (days/hours/minutes) into total minutes.
     */
    private static function normalize_dhm_to_minutes( $value, int $default_minutes ): int {
        if ( is_array( $value ) ) {
            $days    = isset( $value['days'] ) ? (int) $value['days'] : 0;
            $hours   = isset( $value['hours'] ) ? (int) $value['hours'] : 0;
            $minutes = isset( $value['minutes'] ) ? (int) $value['minutes'] : 0;

            $total = ( $days * 1440 ) + ( $hours * 60 ) + $minutes;
        } else {
            $total = is_numeric( $value ) ? (int) $value : $default_minutes;
        }

        if ( $total < 0 ) {
            $total = 0;
        }
        // clamp to 365 days
        if ( $total > 525600 ) {
            $total = 525600;
        }

        return $total;
    }

    /**
     * Resolve booking rules for a post.
     * Returns an array:
     *  - mode: inherit|relative|absolute
     *  - earliest_minutes, latest_minutes (for relative/inherit)
     *  - abs_start_ts, abs_end_ts (for absolute)
     */
    private static function get_booking_rules( int $tickets_for_post_id ): array {
        $tz = wp_timezone();


        // Defaults (match options defaults)
        $default_earliest = 90 * 1440; // 90 days
        $default_latest   = 2 * 60;    // 2 hours

        // Global options
        $opt_window = function_exists( 'get_field' ) ? get_field( 'iw_ticketing_booking_window', 'option' ) : null;
        $opt_earliest = is_array( $opt_window ) && isset( $opt_window['earliest'] ) ? $opt_window['earliest'] : null;
        $opt_latest   = is_array( $opt_window ) && isset( $opt_window['latest'] ) ? $opt_window['latest'] : null;

        $opt_earliest_min = self::normalize_dhm_to_minutes( $opt_earliest, $default_earliest );
        $opt_latest_min   = self::normalize_dhm_to_minutes( $opt_latest, $default_latest );

        // Per-post mode
        $mode = 'inherit';
        if ( function_exists( 'get_field' ) ) {
            $m = (string) get_field( 'iw_ticketing_booking_rules_mode', $tickets_for_post_id );
            if ( in_array( $m, [ 'inherit', 'relative', 'absolute' ], true ) ) {
                $mode = $m;
            }
        }

        // Inherit -> use global
        if ( $mode === 'inherit' ) {
            return [
                'mode' => 'inherit',
                'earliest_minutes' => $opt_earliest_min,
                'latest_minutes'   => $opt_latest_min,
                'abs_start_ts'     => null,
                'abs_end_ts'       => null,
            ];
        }


        // Relative override
        if ( $mode === 'relative' ) {
            $post_window = function_exists( 'get_field' ) ? get_field( 'iw_ticketing_booking_window_override', $tickets_for_post_id ) : null;
            $post_earliest = is_array( $post_window ) && isset( $post_window['earliest'] ) ? $post_window['earliest'] : null;
            $post_latest   = is_array( $post_window ) && isset( $post_window['latest'] ) ? $post_window['latest'] : null;

            return [
                'mode' => 'relative',
                'earliest_minutes' => self::normalize_dhm_to_minutes( $post_earliest, $opt_earliest_min ),
                'latest_minutes'   => self::normalize_dhm_to_minutes( $post_latest, $opt_latest_min ),
                'abs_start_ts'     => null,
                'abs_end_ts'       => null,
            ];
        }

        // Absolute override
        $abs = function_exists( 'get_field' ) ? get_field( 'iw_ticketing_booking_absolute_override', $tickets_for_post_id ) : null;
        $start_raw = is_array( $abs ) && ! empty( $abs['start'] ) ? (string) $abs['start'] : '';
        $end_raw   = is_array( $abs ) && ! empty( $abs['end'] ) ? (string) $abs['end'] : '';

        $start_ts = null;
        $end_ts   = null;

        if ( $start_raw !== '' ) {
            $dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $start_raw, $tz );
            if ( ! $dt ) {
                $dt = new DateTimeImmutable( $start_raw, $tz );
            }
            $start_ts = $dt ? $dt->getTimestamp() : null;
        }
        if ( $end_raw !== '' ) {
            $dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $end_raw, $tz );
            if ( ! $dt ) {
                $dt = new DateTimeImmutable( $end_raw, $tz );
            }
            $end_ts = $dt ? $dt->getTimestamp() : null;
        }




        return [
            'mode' => 'absolute',
            'earliest_minutes' => $opt_earliest_min,
            'latest_minutes'   => $opt_latest_min,
            'abs_start_ts'     => $start_ts,
            'abs_end_ts'       => $end_ts,
        ];
    }

    /**
     * Apply booking rules to allowDates/timesByDate.
     * - Absolute mode: if now is outside [start,end], no selectable dates.
     * - Relative/inherit: filters out slots whose start time is outside the booking window.
     */
    private static function apply_booking_rules_filter( int $tickets_for_post_id, array &$allow_dates, array &$timesByDate ): void {
        if ( empty( $allow_dates ) || empty( $timesByDate ) ) {
            return;
        }

        $tz = wp_timezone();
        // IMPORTANT: use a real Unix timestamp (UTC-based) so comparisons with DateTimeImmutable::getTimestamp() are correct.
        // `current_time('timestamp')` returns a blog-time "timestamp" (offset-applied) which can skew comparisons by the timezone offset.
        $now_wp = current_datetime(); // WP_DateTime in site timezone
        $now_ts = (int) $now_wp->getTimestamp();

        $rules = self::get_booking_rules( $tickets_for_post_id );

        // Absolute window: independent of slot time.
        if ( $rules['mode'] === 'absolute' ) {
            $start_ts = $rules['abs_start_ts'];
            $end_ts   = $rules['abs_end_ts'];

            if ( $start_ts !== null && $now_ts < $start_ts ) {
                $allow_dates = [];
                $timesByDate = [];
                return;
            }
            if ( $end_ts !== null && $now_ts > $end_ts ) {
                $allow_dates = [];
                $timesByDate = [];
                return;
            }
            // within absolute window => keep existing allowDates; no per-slot filtering here.
            return;
        }

        $earliest = isset( $rules['earliest_minutes'] ) ? (int) $rules['earliest_minutes'] : 0;
        $latest   = isset( $rules['latest_minutes'] ) ? (int) $rules['latest_minutes'] : 0;


        $filtered_allow = [];
        $filtered_times = [];

        foreach ( $allow_dates as $ymd ) {
            $day_slots = $timesByDate[ $ymd ] ?? [];
            if ( empty( $day_slots ) || ! is_array( $day_slots ) ) {
                continue;
            }

            $kept = [];
            foreach ( $day_slots as $slot ) {
                if ( ! is_array( $slot ) ) {
                    continue;
                }
                $time = isset( $slot['time'] ) ? (string) $slot['time'] : '';
                if ( $time === '' ) {
                    continue;
                }

                $slot_dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $ymd . ' ' . $time, $tz );
                if ( ! $slot_dt ) {
                    // If we can't parse, keep it (avoid hiding valid inventory).
                    $kept[] = $slot;
                    continue;
                }

                $slot_cutoff_dt = ! empty( $slot['use_end_for_cutoff'] )
                    ? self::get_slot_boundary_datetime( $ymd, $slot, $tz, 'end' )
                    : $slot_dt;

                if ( ! $slot_cutoff_dt ) {
                    $slot_cutoff_dt = $slot_dt;
                }

                $slot_ts = $slot_dt->getTimestamp();
                $slot_cutoff_ts = $slot_cutoff_dt->getTimestamp();

                // Earliest: booking opens at slot_start - earliest.
                if ( $earliest > 0 && $now_ts < ( $slot_ts - ( $earliest * 60 ) ) ) {
                    continue;
                }

                // Latest: booking allowed until the effective slot cutoff - latest.
                // Timed slots use their start time; range/day-ticket slots can use their end time.
                if ( $latest > 0 && $now_ts > ( $slot_cutoff_ts - ( $latest * 60 ) ) ) {
                    continue;
                }

                // If latest is 0, allow until the effective cutoff itself.
                if ( $latest === 0 && $now_ts > $slot_cutoff_ts ) {
                    continue;
                }

                $kept[] = $slot;
            }

            if ( ! empty( $kept ) ) {
                $filtered_allow[] = $ymd;
                $filtered_times[ $ymd ] = $kept;
            }
        }

        $allow_dates = array_values( array_unique( $filtered_allow ) );
        sort( $allow_dates );
        $timesByDate = $filtered_times;
    }

    /**
     * Remove past slots for "today" so users cannot pick times that have already passed.
     * If no slots remain for today, the date is removed from allowDates.
     *
     * This is a UI/UX filter for calendar rendering; checkout must still validate slot existence.
     */
    private static function apply_past_time_filter( array &$allow_dates, array &$timesByDate, int $buffer_minutes = 0 ): void {
        if ( empty( $allow_dates ) || empty( $timesByDate ) ) {
            return;
        }

        $tz = wp_timezone();

        // Current time in site timezone.
        $now_wp = current_datetime();
        $now_dt = new DateTimeImmutable( $now_wp->format( 'Y-m-d H:i:s' ), $tz );
        $today  = $now_dt->format( 'Y-m-d' );

        // Only filter today's slots.
        if ( empty( $timesByDate[ $today ] ) || ! is_array( $timesByDate[ $today ] ) ) {
            return;
        }

        $cutoff = $buffer_minutes > 0 ? $now_dt->modify( '+' . (int) $buffer_minutes . ' minutes' ) : $now_dt;

        $filtered = [];
        foreach ( $timesByDate[ $today ] as $slot ) {
            if ( ! is_array( $slot ) ) {
                continue;
            }

            $time = isset( $slot['time'] ) ? (string) $slot['time'] : '';
            if ( $time === '' ) {
                continue;
            }

            // Parse the effective cutoff for today.
            // Range/day-ticket slots can remain bookable after their start time, until their end time.
            $slot_dt = ! empty( $slot['use_end_for_cutoff'] )
                ? self::get_slot_boundary_datetime( $today, $slot, $tz, 'end' )
                : DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $today . ' ' . $time, $tz );
            if ( ! $slot_dt ) {
                // If we can't parse it, keep it rather than hiding potentially valid slots.
                $filtered[] = $slot;
                continue;
            }

            // Keep slots whose effective cutoff is at/after now.
            if ( $slot_dt >= $cutoff ) {
                $filtered[] = $slot;
            }
        }

        if ( empty( $filtered ) ) {
            // No remaining slots today => remove today from allowDates and timesByDate.
            $allow_dates = array_values( array_filter( $allow_dates, static function( $d ) use ( $today ) {
                return $d !== $today;
            } ) );
            unset( $timesByDate[ $today ] );
            return;
        }

        $timesByDate[ $today ] = $filtered;
    }

    /**
     * Normalize an incoming date string to Y-m-d.
     * Accepts Y-m-d, d-m-Y, d/m/Y, and fallback parse.
     */
    private static function normalize_date_to_ymd( string $date, DateTimeZone $tz ): string {
        $date = trim( $date );
        // already Y-m-d
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return $date;
        }
        // d-m-Y
        if ( preg_match( '/^\d{2}-\d{2}-\d{4}$/', $date ) ) {
            $dt = DateTimeImmutable::createFromFormat( 'd-m-Y', $date, $tz );
            if ( $dt ) {
                return $dt->format( 'Y-m-d' );
            }
        }
        // d/m/Y
        if ( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $date ) ) {
            $dt = DateTimeImmutable::createFromFormat( 'd/m/Y', $date, $tz );
            if ( $dt ) {
                return $dt->format( 'Y-m-d' );
            }
        }
        // fallback parse
        try {
            $dt = new DateTimeImmutable( $date, $tz );
            return $dt->format( 'Y-m-d' );
        } catch ( Exception $e ) {
            return $date;
        }
    }

    /**
     * Return a slot's start or end datetime in the site timezone.
     * End times that are earlier than the start are treated as crossing midnight.
     */
    public static function get_slot_boundary_datetime( string $ymd, array $slot, DateTimeZone $tz, string $boundary = 'start' ): ?DateTimeImmutable {
        $time = isset( $slot['time'] ) ? (string) $slot['time'] : '';
        if ( $time === '' ) {
            return null;
        }

        $start_dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $ymd . ' ' . $time, $tz );
        if ( ! $start_dt ) {
            return null;
        }

        if ( $boundary !== 'end' ) {
            return $start_dt;
        }

        $end = isset( $slot['end'] ) ? (string) $slot['end'] : '';
        if ( $end === '' ) {
            return $start_dt;
        }

        $end_dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $ymd . ' ' . $end, $tz );
        if ( ! $end_dt ) {
            return $start_dt;
        }

        if ( $end_dt <= $start_dt ) {
            $end_dt = $end_dt->modify( '+1 day' );
        }

        return $end_dt;
    }

    /**
     * Convert slot arrays to FE-friendly objects.
     * Each slot: [ 'time', optional 'end', optional 'capacity', optional 'availability', 'status' ]
     */
    private static function slots_to_objects( array $slots ): array {
        $out = [];
        foreach ( $slots as $slot ) {
            if ( empty( $slot['time'] ) ) continue;
            $row = [
                'time' => (string) $slot['time'],
                'status' => isset( $slot['status'] ) ? $slot['status'] : 'available',
            ];
            if ( isset( $slot['end'] ) && $slot['end'] !== '' ) {
                $row['end'] = (string) $slot['end'];
            }
            if ( isset( $slot['capacity'] ) && $slot['capacity'] !== '' ) {
                $row['capacity'] = (int) $slot['capacity'];
            }
            if ( isset( $slot['availability'] ) ) {
                $row['availability'] = (int) $slot['availability'];
            }
            if ( isset( $slot['staff'] ) ) {
                $row['staff'] = $slot['staff'];
            }
            if ( ! empty( $slot['use_end_for_cutoff'] ) ) {
                $row['use_end_for_cutoff'] = true;
            }
            $out[] = $row;
        }
        return $out;
    }


    /**
     * Convert time strings to FE-friendly objects.
     * Output example: [ ['time'=>'10:00','status'=>'available'], ... ]
     */
    private static function times_to_objects( array $times, string $status = 'available', ?int $availability = null ): array {
        $out = [];

        foreach ( $times as $t ) {
            $row = [
                'time'   => (string) $t,
                'status' => $status,
            ];

            if ( $availability !== null ) {
                $row['availability'] = (int) $availability;
            }

            $out[] = $row;
        }

        return $out;
    }

    /**
     * Return selectable time slots for a given tickets-for post and a selected date.
     *
     * @param int    $tickets_for_post_id
     * @param string $date                Accepts 'd-m-Y' or 'd/m/Y' or 'Y-m-d'
     * @param int    $interval_minutes    Used to expand ranges from custom_hours into discrete times (default 60).
     *
     * @return array {
     *   mode: string,
     *   date: string (Y-m-d),
     *   times: string[] (H:i),
     *   slots: array[],
     *   ranges: array[] (optional; for debugging)
     * }
     */
    public static function get_time_slots_for_date( int $tickets_for_post_id, string $date, $include_staff = false, int $interval_minutes = 60 ): array {

        $tz = wp_timezone();

        $mode = function_exists( 'get_field' ) ? (string) get_field( 'opening_hours_mode', $tickets_for_post_id ) : '';


        // Notes-only => no slots
        if ( $mode === 'from_notes' ) {
            return [
                'mode'   => $mode,
                'date'   => self::normalize_date_to_ymd( $date, $tz ),
                'times'  => [],
                'slots'  => [],
                'ranges' => [],
            ];
        }

        $normalized = self::normalize_date_to_ymd( $date, $tz );
        $dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $normalized, $tz );
        if ( ! $dt ) {
            return [
                'mode'   => $mode,
                'date'   => $normalized,
                'times'  => [],
                'slots'  => [],
                'ranges' => [],
            ];
        }

        // SPECIFIC DAYS => exact times per date
        if ( $mode === 'specific_days' && function_exists( 'get_field' ) ) {
            $rows = get_field( 'choose_specifics_days', $tickets_for_post_id );
            $rows = is_array( $rows ) ? $rows : [];

            $slots = [];
            $times = [];
            foreach ( $rows as $row ) {
                $row_date = isset( $row['choose_date'] ) ? (string) $row['choose_date'] : '';
                if ( $row_date === '' ) continue;
                $row_ymd = self::normalize_date_to_ymd( $row_date, $tz );
                if ( $row_ymd !== $normalized ) {
                    continue;
                }
                $time_rows = isset( $row['choose_time'] ) && is_array( $row['choose_time'] ) ? $row['choose_time'] : [];
                foreach ( $time_rows as $trow ) {
                    if ( ! empty( $trow['cancel'] ) ) {
                        continue;
                    }
                    $start = isset( $trow['time'] ) ? (string) $trow['time'] : '';
                    $end = isset( $trow['time_to'] ) ? (string) $trow['time_to'] : null;
                    $capacity = isset( $trow['capacity'] ) && $trow['capacity'] !== '' ? (int) $trow['capacity'] : null;
                    if ( $start !== '' ) {
                        $status = ($capacity !== null && $capacity <= 0) ? 'sold-out' : 'available';
                        $slot = [ 'time' => $start ];
                        if ( $end !== null && $end !== '' ) $slot['end'] = $end;
                        if ( $capacity !== null ) $slot['capacity'] = $capacity;
                        $slot['status'] = $status;
                        if( $include_staff ){
                            $slot['staff'] = $trow['staff'];
                        }
                        $slots[] = $slot;
                        $times[] = $start;
                    }
                }
            }
            $times = array_values( array_unique( $times ) );
            sort( $times );

            return [
                'mode'   => $mode,
                'date'   => $normalized,
                'times'  => $times,
                'slots'  => $slots,
                'ranges' => [],
            ];
        }

        // CUSTOM HOURS => do not expand ranges, just return ranges as slots
        if ( $mode === 'custom_hours' && function_exists( 'get_field' ) ) {
            $per_day = get_field( 'opening_hours_per_day', $tickets_for_post_id );
            $per_day = is_array( $per_day ) ? $per_day : [];


            // DateTimeImmutable::format('N'): 1=Mon..7=Sun
            $dow = (int) $dt->format( 'N' );

            $map = [
                1 => 'monday',
                2 => 'tuesday',
                3 => 'wednesday',
                4 => 'thursday',
                5 => 'friday',
                6 => 'saturday',
                7 => 'sunday',
            ];

            $acf_key = isset( $map[ $dow ] ) ? $map[ $dow ] : null;
            $ranges = ( $acf_key && isset( $per_day[ $acf_key ] ) && is_array( $per_day[ $acf_key ] ) ) ? $per_day[ $acf_key ] : [];

            $normalized_ranges = [];
            $slots = [];
            $times = [];
            foreach ( $ranges as $r ) {

                $from = isset( $r['from'] ) ? (string) $r['from'] : '';
                $to   = isset( $r['to'] ) ? (string) $r['to'] : '';
                $capacity = isset( $r['capacity'] ) && $r['capacity'] !== '' ? (int) $r['capacity'] : null;

                if ( $from === '' ) {
                    continue;
                }

                // Do not derive a default end time if missing; keep $to as-is (may be empty).

                $normalized_ranges[] = [ 'from' => $from, 'to' => $to ];

                $status = ( $capacity !== null && $capacity <= 0 ) ? 'sold-out' : 'available';
                $slot = [
                    'time' => $from,
                    'status' => $status,
                    'use_end_for_cutoff' => true,
                ];
                if ( $to !== '' ) {
                    $slot['end'] = $to;
                }
                if ( $capacity !== null ) {
                    $slot['capacity'] = $capacity;
                }


                if( $include_staff ){
                    $slot['staff'] = $r['staff'];
                }

                $slots[] = $slot;
                $times[] = $from;
            }
            $times = array_values( array_unique( $times ) );
            sort( $times );
            return [
                'mode'   => $mode,
                'date'   => $normalized,
                'times'  => $times,
                'slots'  => $slots,
                'ranges' => $normalized_ranges,
            ];
        }

        // MUSEUM HOURS (fallback):
        // For now we don't generate specific time slots here; return empty list.
        // You can later decide to use building-based opening hours to create time slots.
        return [
            'mode'   => $mode ?: 'museum_hours',
            'date'   => $normalized,
            'times'  => [],
            'slots'  => [],
            'ranges' => [],
        ];
    }


    /**
     * Apply slot inventory (booked/availability/status) to the FE slot objects in-place.
     * Expects $timesByDate to be a map: [ 'Y-m-d' => [ [time,end?,capacity?,status?,availability?], ... ] ]
     */
    private static function apply_inventory_to_times_by_date( int $tickets_for_post_id, array &$timesByDate ): void {

        if ( empty( $timesByDate ) ) {
            return;
        }

        // If enabled, once a slot has any booking/reservation, it becomes unavailable (regardless of capacity).
        $one_booking_per_slot = false;
        if ( function_exists( 'get_field' ) ) {
            $ticket_categories =  get_field( 'field_iw_ticket_categories_group', $tickets_for_post_id );
            if( $ticket_categories ){
                $one_booking_per_slot = $ticket_categories[ 'one_booking_per_slot' ];
            }
        }

        // Inventory layer must exist.
        if (
            ! class_exists( 'IW_Tickets_DB' )
            || ! method_exists( 'IW_Tickets_DB', 'get_booked_map' )
            || ! method_exists( 'IW_Tickets_DB', 'get_reserved_map' )
        ) {
            return;
        }

        // Determine range from keys.
        $dates = array_keys( $timesByDate );
        sort( $dates );
        $date_from = $dates[0];
        $date_to   = $dates[ count( $dates ) - 1 ];

        $booked_map = IW_Tickets_DB::get_booked_map( $tickets_for_post_id, $date_from, $date_to );
        $reserved_map = IW_Tickets_DB::get_reserved_map( $tickets_for_post_id, $date_from, $date_to );

        // Threshold rule for "limited" (can be adjusted later).
        $limited_threshold_abs = 2; // <=2 remaining => limited
        $limited_threshold_pct = 0.10; // <=10% remaining => limited

        foreach ( $timesByDate as $date => &$slots ) {
            if ( empty( $slots ) || ! is_array( $slots ) ) {
                continue;
            }

            $day_booked = $booked_map[ $date ] ?? [];
            $day_reserved = $reserved_map[ $date ] ?? [];

            foreach ( $slots as &$slot ) {
                if ( ! is_array( $slot ) ) {
                    continue;
                }

                $time = isset( $slot['time'] ) ? (string) $slot['time'] : '';
                if ( $time === '' ) {
                    continue;
                }

                $time_key = $time;
                if ( preg_match( '/^\d{2}:\d{2}$/', $time_key ) ) {
                    $time_key .= ':00';
                }

                $capacity = isset( $slot['capacity'] ) ? (int) $slot['capacity'] : null;
                $booked   = isset( $day_booked[ $time_key ] ) ? (int) $day_booked[ $time_key ] : 0;
                $reserved = isset( $day_reserved[ $time_key ] ) ? (int) $day_reserved[ $time_key ] : 0;

                if ( $one_booking_per_slot ) {
                    // Rule: only ONE booking is allowed per slot.
                    // Practically, as soon as there is any booked/reserved presence for the slot, it becomes unavailable.
                    // When the slot is still free, we expose the *real* availability (capacity) so a single booking can
                    // still include multiple tickets (2,3,5,10...) up to capacity.

                    $has_any = ( $booked + $reserved ) > 0;

                    $slot['reserved'] = $reserved;

                    if ( $has_any ) {
                        $slot['availability'] = 0;
                        $slot['status'] = 'sold-out';
                        continue;
                    }

                    // Slot is free => allow one booking with up to capacity tickets (if capacity is defined).
                    if ( $capacity !== null ) {
                        $slot['availability'] = max( 0, $capacity );
                    } else {
                        // No capacity info: do not override availability (leave it unset) to avoid lying.
                        unset( $slot['availability'] );
                    }

                    $slot['status'] = 'available';
                    continue;
                }

                if ( $capacity === null ) {
                    // No capacity defined => cannot compute availability; keep status as-is.
                    continue;
                }

                $availability = max( 0, $capacity - $booked - $reserved );
                $slot['availability'] = $availability;
                $slot['reserved'] = $reserved;

                if ( $availability <= 0 ) {
                    $slot['status'] = 'sold-out';
                    continue;
                }

                $pct_threshold = (int) ceil( $capacity * $limited_threshold_pct );
                $threshold = max( $limited_threshold_abs, $pct_threshold );

                if ( $availability <= $threshold ) {
                    $slot['status'] = 'limited';
                } else {
                    $slot['status'] = 'available';
                }
            }
            unset( $slot );
        }
        unset( $slots );

        self::apply_learning_program_staff_conflicts_to_times_by_date( $tickets_for_post_id, $timesByDate, $date_from, $date_to );
    }




    /**
     * Expand and apply ACF exceptions within a date window.
     * - cancel_range removes dates (single_year or every_year).
     * - override_day replaces the full slots schedule for a specific date and can add dates that were not selectable.
     * Precedence: override_day > cancel_range > defaults.
     *
     * @param int   $tickets_for_post_id
     * @param array $allow_dates   Array of Y-m-d strings (in/out)
     * @param array $timesByDate   Map [Y-m-d => slot objects[]] (in/out)
     * @param string $window_from  Y-m-d
     * @param string $window_to    Y-m-d
     */
    private static function apply_exceptions_to_allow_dates( int $tickets_for_post_id, array &$allow_dates, array &$timesByDate, string $window_from, string $window_to, array &$closed_hints = [] ): void {
        if ( ! function_exists( 'get_field' ) ) {
            return;
        }

        $rows = get_field( 'exceptions', $tickets_for_post_id );
        $rows = is_array( $rows ) ? $rows : [];
        if ( empty( $rows ) ) {
            return;
        }

        $tz = wp_timezone();

        $window_from_dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $window_from, $tz );
        $window_to_dt   = DateTimeImmutable::createFromFormat( 'Y-m-d', $window_to, $tz );
        if ( ! $window_from_dt || ! $window_to_dt ) {
            return;
        }

        $cancel_set = [];
        $override_map = []; // [Y-m-d => slot[]]
        $closed_hints = is_array( $closed_hints ) ? $closed_hints : [];

        foreach ( $rows as $row ) {
            $type = isset( $row['type'] ) ? (string) $row['type'] : 'cancel_range';

            $date_raw = isset( $row['date'] ) ? (string) $row['date'] : '';
            if ( $date_raw === '' ) {
                continue;
            }

            $start_ymd = self::normalize_date_to_ymd( $date_raw, $tz );
            $start_dt  = DateTimeImmutable::createFromFormat( 'Y-m-d', $start_ymd, $tz );
            if ( ! $start_dt ) {
                continue;
            }

            if ( $type === 'override_day' ) {
                $slots_rows = isset( $row['slots'] ) && is_array( $row['slots'] ) ? $row['slots'] : [];

                $slots = [];
                foreach ( $slots_rows as $srow ) {
                    if ( ! empty( $srow['cancel'] ) ) {
                        continue;
                    }
                    $from = isset( $srow['time_from'] ) ? (string) $srow['time_from'] : '';
                    if ( $from === '' ) {
                        continue;
                    }
                    $to = isset( $srow['time_to'] ) ? (string) $srow['time_to'] : '';
                    $capacity = isset( $srow['capacity'] ) && $srow['capacity'] !== '' ? (int) $srow['capacity'] : null;

                    $status = ( $capacity !== null && $capacity <= 0 ) ? 'sold-out' : 'available';

                    $slot = [
                        'time'   => $from,
                        'status' => $status,
                    ];
                    if ( $to !== '' ) {
                        $slot['end'] = $to;
                    }
                    if ( $capacity !== null ) {
                        $slot['capacity'] = $capacity;
                    }

                    $slots[] = $slot;
                }

                // Read exception note for override_day
                $note = isset( $row['note'] ) ? trim( (string) $row['note'] ) : '';
                if ( $note === '' ) {
                    $note = __( 'Closed', 'iw-theme' );
                }

                // Only apply override within the active window.
                if ( $start_dt >= $window_from_dt && $start_dt <= $window_to_dt ) {
                    // If override defines no slots, treat as closed (do not add to allowDates).
                    $override_map[ $start_ymd ] = $slots;
                    // If override has no slots, treat as closed and expose the note for FE.
                    if ( empty( $slots ) ) {
                        $closed_hints[ $start_ymd ] = $note;
                    }
                }

                continue;
            }

            // cancel_range (date or range)
            $recurrence = isset( $row['range_recurrence'] ) ? (string) $row['range_recurrence'] : 'single_year';
            $date_to_raw = isset( $row['date_to'] ) ? (string) $row['date_to'] : '';

            $note = isset( $row['note'] ) ? trim( (string) $row['note'] ) : '';
            if ( $note === '' ) {
                $note = __( 'Closed', 'iw-theme' );
            }

            $end_ymd = $start_ymd;
            if ( $date_to_raw !== '' ) {
                $end_ymd = self::normalize_date_to_ymd( $date_to_raw, $tz );
            }

            // Expand cancellations into concrete dates within window.
            self::expand_cancel_range_into_set(
                $cancel_set,
                $start_ymd,
                $end_ymd,
                $recurrence,
                $window_from_dt,
                $window_to_dt,
                $tz,
                $closed_hints,
                $note
            );
        }

        // 1) Apply cancellations first.
        if ( ! empty( $cancel_set ) ) {
            $allow_dates = array_values( array_filter( $allow_dates, static function( $d ) use ( $cancel_set ) {
                return ! isset( $cancel_set[ $d ] );
            } ) );

            foreach ( $cancel_set as $d => $_ ) {
                unset( $timesByDate[ $d ] );
            }
        }

        // 2) Apply overrides (wins over cancellations/default closures).
        foreach ( $override_map as $d => $slots ) {
            // If override has no slots, we consider it closed: remove from allowDates and timesByDate.
            if ( empty( $slots ) ) {
                $allow_dates = array_values( array_filter( $allow_dates, static function( $x ) use ( $d ) {
                    return $x !== $d;
                } ) );
                unset( $timesByDate[ $d ] );
                // Store closed message for FE (disabled date hint).
                if ( ! isset( $closed_hints[ $d ] ) ) {
                    $closed_hints[ $d ] = __( 'Closed', 'iw-theme' );
                }
                continue;
            }

            // Ensure date exists in allowDates.
            if ( ! in_array( $d, $allow_dates, true ) ) {
                $allow_dates[] = $d;
            }

            // Replace day schedule.
            $timesByDate[ $d ] = self::slots_to_objects( $slots );
        }

        // Keep allowDates sorted.
        $allow_dates = array_values( array_unique( $allow_dates ) );
        sort( $allow_dates );

        // Remove closed_hints outside the active window
        if ( ! empty( $closed_hints ) ) {
            foreach ( array_keys( $closed_hints ) as $d ) {
                if ( $d < $window_from || $d > $window_to ) {
                    unset( $closed_hints[ $d ] );
                }
            }
        }
    }

    /**
     * Expand a cancel range into the cancel set for the active window.
     * - single_year: uses the actual year in the selected dates.
     * - every_year: repeats annually, projecting month/day onto each year in the window.
     */
    private static function expand_cancel_range_into_set(
        array &$cancel_set,
        string $start_ymd,
        string $end_ymd,
        string $recurrence,
        DateTimeImmutable $window_from_dt,
        DateTimeImmutable $window_to_dt,
        DateTimeZone $tz,
        array &$closed_hints = [],
        string $note = ''
    ): void {
        $start_dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $start_ymd, $tz );
        $end_dt   = DateTimeImmutable::createFromFormat( 'Y-m-d', $end_ymd, $tz );
        if ( ! $start_dt || ! $end_dt ) {
            return;
        }

        // Normalize end before start => assume it spans to the next day/year.
        if ( $end_dt < $start_dt ) {
            $end_dt = $end_dt->modify( '+1 day' );
        }

        if ( $recurrence !== 'every_year' ) {
            // Single year: iterate concrete dates.
            $cursor = $start_dt;
            while ( $cursor <= $end_dt ) {
                if ( $cursor >= $window_from_dt && $cursor <= $window_to_dt ) {
                    $cancel_set[ $cursor->format( 'Y-m-d' ) ] = true;
                    if ( $note !== '' && ! isset( $closed_hints[ $cursor->format( 'Y-m-d' ) ] ) ) {
                        $closed_hints[ $cursor->format( 'Y-m-d' ) ] = $note;
                    }
                }
                $cursor = $cursor->modify( '+1 day' );
            }
            return;
        }

        // Every year: project month/day onto each year intersecting the window.
        $start_md = $start_dt->format( 'm-d' );
        $end_md   = $end_dt->format( 'm-d' );

        $y_from = (int) $window_from_dt->format( 'Y' );
        $y_to   = (int) $window_to_dt->format( 'Y' );

        for ( $y = $y_from; $y <= $y_to; $y++ ) {
            $p_start = DateTimeImmutable::createFromFormat( 'Y-m-d', sprintf( '%04d-%s', $y, $start_md ), $tz );
            if ( ! $p_start ) {
                continue;
            }

            // If end_md is "before" start_md in calendar order, treat it as spanning into next year.
            $p_end_year = $y;
            if ( $end_md < $start_md ) {
                $p_end_year = $y + 1;
            }
            $p_end = DateTimeImmutable::createFromFormat( 'Y-m-d', sprintf( '%04d-%s', $p_end_year, $end_md ), $tz );
            if ( ! $p_end ) {
                continue;
            }

            $cursor = $p_start;
            while ( $cursor <= $p_end ) {
                if ( $cursor >= $window_from_dt && $cursor <= $window_to_dt ) {
                    $cancel_set[ $cursor->format( 'Y-m-d' ) ] = true;
                    if ( $note !== '' && ! isset( $closed_hints[ $cursor->format( 'Y-m-d' ) ] ) ) {
                        $closed_hints[ $cursor->format( 'Y-m-d' ) ] = $note;
                    }
                }
                $cursor = $cursor->modify( '+1 day' );
            }
        }
    }


    /**
     * Apply post-level min/max date constraints to allowDates/timesByDate.
     * Expected ACF fields on the tickets-for post:
     *  - min_date: date (Y-m-d or common variants)
     *  - max_date: date (Y-m-d or common variants)
     * If fields are missing/empty, no filtering occurs.
     */
    private static function apply_min_max_date_filter( int $tickets_for_post_id, array &$allow_dates, array &$timesByDate ): void {
        if ( empty( $allow_dates ) ) {
            return;
        }

        if ( ! function_exists( 'get_field' ) ) {
            return;
        }

        $tz = wp_timezone();

        $min_raw = (string) get_field( 'min_date', $tickets_for_post_id );
        $max_raw = (string) get_field( 'max_date', $tickets_for_post_id );

        $min = $min_raw !== '' ? self::normalize_date_to_ymd( $min_raw, $tz ) : '';
        $max = $max_raw !== '' ? self::normalize_date_to_ymd( $max_raw, $tz ) : '';

        // If both are empty, nothing to do.
        if ( $min === '' && $max === '' ) {
            return;
        }

        $filtered = [];
        foreach ( $allow_dates as $d ) {
            $d = (string) $d;
            if ( $min !== '' && $d < $min ) {
                unset( $timesByDate[ $d ] );
                continue;
            }
            if ( $max !== '' && $d > $max ) {
                unset( $timesByDate[ $d ] );
                continue;
            }
            $filtered[] = $d;
        }

        $allow_dates = array_values( array_unique( $filtered ) );
        sort( $allow_dates );

        // Also remove any orphaned timesByDate keys not in allow_dates.
        if ( ! empty( $timesByDate ) ) {
            $allowed_map = array_fill_keys( $allow_dates, true );
            foreach ( array_keys( $timesByDate ) as $k ) {
                if ( ! isset( $allowed_map[ $k ] ) ) {
                    unset( $timesByDate[ $k ] );
                }
            }
        }
    }

    /**
     * For learning programs, a booked/held slot reserves its assigned staff members.
     * Any other learning-program slot that overlaps and shares at least one staff member
     * must be unavailable even when it belongs to a different post.
     */
    private static function apply_learning_program_staff_conflicts_to_times_by_date( int $tickets_for_post_id, array &$timesByDate, string $date_from, string $date_to ): void {
        if ( get_post_type( $tickets_for_post_id ) !== 'learning-program' || empty( $timesByDate ) ) {
            return;
        }

        $current_index = self::get_learning_program_slot_staff_index( $tickets_for_post_id, $date_from, $date_to );
        if ( empty( $current_index ) ) {
            return;
        }

        $busy_intervals = self::get_learning_program_staff_busy_intervals( $tickets_for_post_id, $date_from, $date_to );
        if ( empty( $busy_intervals ) ) {
            return;
        }

        foreach ( $timesByDate as $date => &$slots ) {
            if ( empty( $slots ) || ! is_array( $slots ) || empty( $busy_intervals[ $date ] ) ) {
                continue;
            }

            foreach ( $slots as &$slot ) {
                if ( ! is_array( $slot ) || empty( $slot['time'] ) ) {
                    continue;
                }

                $time_key = self::normalize_time_to_slot_key( (string) $slot['time'] );
                if ( $time_key === '' || empty( $current_index[ $date ][ $time_key ] ) ) {
                    continue;
                }

                foreach ( $current_index[ $date ][ $time_key ] as $current_slot ) {
                    foreach ( $busy_intervals[ $date ] as $busy_slot ) {
                        if (
                            self::time_ranges_overlap( $current_slot['start_minutes'], $current_slot['end_minutes'], $busy_slot['start_minutes'], $busy_slot['end_minutes'] )
                            && self::staff_ids_intersect( $current_slot['staff_ids'], $busy_slot['staff_ids'] )
                        ) {
                            $slot['availability'] = 0;
                            $slot['status'] = 'sold-out';
                            continue 3;
                        }
                    }
                }
            }
            unset( $slot );
        }
        unset( $slots );
    }

    public static function learning_program_slot_has_staff_conflict( int $tickets_for_post_id, string $date, string $time ): bool {
        if ( get_post_type( $tickets_for_post_id ) !== 'learning-program' ) {
            return false;
        }

        $tz = wp_timezone();
        $date = self::normalize_date_to_ymd( $date, $tz );
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return false;
        }

        $time_key = self::normalize_time_to_slot_key( $time );
        if ( $time_key === '' ) {
            return false;
        }

        $current_index = self::get_learning_program_slot_staff_index( $tickets_for_post_id, $date, $date );
        if ( empty( $current_index[ $date ][ $time_key ] ) ) {
            return false;
        }

        $busy_intervals = self::get_learning_program_staff_busy_intervals( $tickets_for_post_id, $date, $date );
        if ( empty( $busy_intervals[ $date ] ) ) {
            return false;
        }

        foreach ( $current_index[ $date ][ $time_key ] as $current_slot ) {
            foreach ( $busy_intervals[ $date ] as $busy_slot ) {
                if (
                    self::time_ranges_overlap( $current_slot['start_minutes'], $current_slot['end_minutes'], $busy_slot['start_minutes'], $busy_slot['end_minutes'] )
                    && self::staff_ids_intersect( $current_slot['staff_ids'], $busy_slot['staff_ids'] )
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function get_learning_program_staff_busy_intervals( int $exclude_post_id, string $date_from, string $date_to ): array {
        static $cache = [];

        $cache_key = $exclude_post_id . '|' . $date_from . '|' . $date_to;
        if ( array_key_exists( $cache_key, $cache ) ) {
            return $cache[ $cache_key ];
        }

        $cache[ $cache_key ] = [];

        if (
            ! class_exists( 'IW_Tickets_DB' )
            || ! method_exists( 'IW_Tickets_DB', 'get_booked_map' )
            || ! method_exists( 'IW_Tickets_DB', 'get_reserved_map' )
        ) {
            return $cache[ $cache_key ];
        }

        foreach ( self::get_learning_program_busy_candidate_ids( $exclude_post_id, $date_from, $date_to ) as $candidate_id ) {
            $candidate_index = self::get_learning_program_slot_staff_index( $candidate_id, $date_from, $date_to );
            if ( empty( $candidate_index ) ) {
                continue;
            }

            $booked_map   = IW_Tickets_DB::get_booked_map( $candidate_id, $date_from, $date_to );
            $reserved_map = IW_Tickets_DB::get_reserved_map( $candidate_id, $date_from, $date_to );

            foreach ( $candidate_index as $date => $times ) {
                foreach ( $times as $time_key => $slot_rows ) {
                    $booked   = isset( $booked_map[ $date ][ $time_key ] ) ? (int) $booked_map[ $date ][ $time_key ] : 0;
                    $reserved = isset( $reserved_map[ $date ][ $time_key ] ) ? (int) $reserved_map[ $date ][ $time_key ] : 0;

                    if ( ( $booked + $reserved ) <= 0 ) {
                        continue;
                    }

                    foreach ( $slot_rows as $slot_row ) {
                        $cache[ $cache_key ][ $date ][] = $slot_row;
                    }
                }
            }
        }

        return $cache[ $cache_key ];
    }

    private static function get_learning_program_busy_candidate_ids( int $exclude_post_id, string $date_from, string $date_to ): array {
        static $cache = [];

        $cache_key = $exclude_post_id . '|' . $date_from . '|' . $date_to;
        if ( array_key_exists( $cache_key, $cache ) ) {
            return $cache[ $cache_key ];
        }

        $candidate_ids = self::get_learning_program_conflict_candidate_ids( $exclude_post_id );
        if ( empty( $candidate_ids ) ) {
            $cache[ $cache_key ] = [];
            return $cache[ $cache_key ];
        }

        global $wpdb;

        $inventory_table = $wpdb->prefix . 'iw_ticket_slot_inventory';
        $holds_table     = $wpdb->prefix . 'iw_ticket_slot_holds';
        $busy_ids        = [];
        $now             = current_time( 'mysql' );

        foreach ( array_chunk( $candidate_ids, 200 ) as $chunk ) {
            $chunk = array_values( array_filter( array_map( 'absint', $chunk ) ) );
            if ( empty( $chunk ) ) {
                continue;
            }

            $placeholders = implode( ',', array_fill( 0, count( $chunk ), '%d' ) );

            $inventory_sql = "SELECT DISTINCT post_id FROM {$inventory_table} WHERE post_id IN ({$placeholders}) AND slot_date BETWEEN %s AND %s AND booked > 0";
            $inventory_args = array_merge( $chunk, [ $date_from, $date_to ] );
            foreach ( (array) $wpdb->get_col( $wpdb->prepare( $inventory_sql, $inventory_args ) ) as $post_id ) {
                $busy_ids[] = (int) $post_id;
            }

            $holds_sql = "SELECT DISTINCT post_id FROM {$holds_table} WHERE post_id IN ({$placeholders}) AND slot_date BETWEEN %s AND %s AND expires_at > %s AND qty > 0";
            $holds_args = array_merge( $chunk, [ $date_from, $date_to, $now ] );
            foreach ( (array) $wpdb->get_col( $wpdb->prepare( $holds_sql, $holds_args ) ) as $post_id ) {
                $busy_ids[] = (int) $post_id;
            }
        }

        $cache[ $cache_key ] = array_values( array_unique( array_filter( $busy_ids, static function( $post_id ) {
            return (int) $post_id > 0;
        } ) ) );

        return $cache[ $cache_key ];
    }

    private static function get_learning_program_conflict_candidate_ids( int $exclude_post_id ): array {
        static $all_ids = null;

        if ( $all_ids === null ) {
            $ids = get_posts( [
                'post_type'      => 'learning-program',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_query'     => [
                    [
                        'key'     => '_sellable',
                        'value'   => '1',
                        'compare' => '=',
                    ],
                ],
            ] );

            $all_ids = array_values( array_unique( array_map( 'absint', (array) $ids ) ) );

            if ( class_exists( 'IW_Ticketing' ) && method_exists( 'IW_Ticketing', 'is_sellable' ) ) {
                $all_ids = array_values( array_filter( $all_ids, static function( $post_id ) {
                    try {
                        return IW_Ticketing::is_sellable( (int) $post_id );
                    } catch ( Throwable $e ) {
                        return false;
                    }
                } ) );
            }
        }

        return array_values( array_filter( $all_ids, static function( $post_id ) use ( $exclude_post_id ) {
            return (int) $post_id > 0 && (int) $post_id !== (int) $exclude_post_id;
        } ) );
    }

    private static function get_learning_program_slot_staff_index( int $tickets_for_post_id, string $date_from, string $date_to ): array {
        static $cache = [];

        $cache_key = $tickets_for_post_id . '|' . $date_from . '|' . $date_to;
        if ( array_key_exists( $cache_key, $cache ) ) {
            return $cache[ $cache_key ];
        }

        $cache[ $cache_key ] = [];

        if ( get_post_type( $tickets_for_post_id ) !== 'learning-program' || ! function_exists( 'get_field' ) ) {
            return $cache[ $cache_key ];
        }

        $tz = wp_timezone();
        $from_dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $date_from, $tz );
        $to_dt   = DateTimeImmutable::createFromFormat( 'Y-m-d', $date_to, $tz );
        if ( ! $from_dt || ! $to_dt ) {
            return $cache[ $cache_key ];
        }

        $slots_by_date = self::get_raw_learning_program_staff_slots( $tickets_for_post_id, $from_dt, $to_dt, $tz );
        if ( empty( $slots_by_date ) ) {
            return $cache[ $cache_key ];
        }

        foreach ( $slots_by_date as $date => $slots ) {
            foreach ( $slots as $slot ) {
                $staff_ids = self::resolve_slot_conflict_staff_ids( $slot );
                if ( empty( $staff_ids ) || empty( $slot['time'] ) ) {
                    continue;
                }

                $time_key = self::normalize_time_to_slot_key( (string) $slot['time'] );
                $range = self::slot_range_to_minutes( $slot );
                if ( $time_key === '' || $range === null ) {
                    continue;
                }

                if ( empty( $cache[ $cache_key ][ $date ] ) ) {
                    $cache[ $cache_key ][ $date ] = [];
                }
                if ( empty( $cache[ $cache_key ][ $date ][ $time_key ] ) ) {
                    $cache[ $cache_key ][ $date ][ $time_key ] = [];
                }

                $cache[ $cache_key ][ $date ][ $time_key ][] = [
                    'staff_ids'     => $staff_ids,
                    'start_minutes' => $range[0],
                    'end_minutes'   => $range[1],
                ];
            }
        }

        return $cache[ $cache_key ];
    }

    private static function get_raw_learning_program_staff_slots( int $tickets_for_post_id, DateTimeImmutable $from_dt, DateTimeImmutable $to_dt, DateTimeZone $tz ): array {
        $mode = (string) get_field( 'opening_hours_mode', $tickets_for_post_id );
        $slots_by_date = [];

        if ( $mode === 'specific_days' ) {
            $rows = get_field( 'choose_specifics_days', $tickets_for_post_id );
            $rows = is_array( $rows ) ? $rows : [];

            foreach ( $rows as $row ) {
                $date_raw = isset( $row['choose_date'] ) ? (string) $row['choose_date'] : '';
                if ( $date_raw === '' ) {
                    continue;
                }

                $date = self::normalize_date_to_ymd( $date_raw, $tz );
                if ( $date < $from_dt->format( 'Y-m-d' ) || $date > $to_dt->format( 'Y-m-d' ) ) {
                    continue;
                }

                $time_rows = isset( $row['choose_time'] ) && is_array( $row['choose_time'] ) ? $row['choose_time'] : [];
                foreach ( $time_rows as $time_row ) {
                    if ( ! empty( $time_row['cancel'] ) || empty( $time_row['time'] ) ) {
                        continue;
                    }

                    $slots_by_date[ $date ][] = [
                        'time'  => (string) $time_row['time'],
                        'end'   => isset( $time_row['time_to'] ) ? (string) $time_row['time_to'] : '',
                        'staff' => $time_row['staff'] ?? [],
                        'organizers' => $time_row['organizers'] ?? [],
                    ];
                }
            }
        } elseif ( $mode === 'custom_hours' ) {
            $per_day = get_field( 'opening_hours_per_day', $tickets_for_post_id );
            $per_day = is_array( $per_day ) ? $per_day : [];

            $day_map = [
                1 => 'monday',
                2 => 'tuesday',
                3 => 'wednesday',
                4 => 'thursday',
                5 => 'friday',
                6 => 'saturday',
                7 => 'sunday',
            ];

            $cursor = $from_dt;
            while ( $cursor <= $to_dt ) {
                $date = $cursor->format( 'Y-m-d' );
                $acf_key = $day_map[ (int) $cursor->format( 'N' ) ] ?? '';
                $ranges = $acf_key !== '' && isset( $per_day[ $acf_key ] ) && is_array( $per_day[ $acf_key ] ) ? $per_day[ $acf_key ] : [];

                foreach ( $ranges as $range ) {
                    if ( empty( $range['from'] ) ) {
                        continue;
                    }

                    $slots_by_date[ $date ][] = [
                        'time'  => (string) $range['from'],
                        'end'   => isset( $range['to'] ) ? (string) $range['to'] : '',
                        'staff' => $range['staff'] ?? [],
                        'organizers' => $range['organizers'] ?? [],
                    ];
                }

                $cursor = $cursor->modify( '+1 day' );
            }
        }

        self::apply_raw_learning_program_staff_exceptions( $tickets_for_post_id, $slots_by_date, $from_dt, $to_dt, $tz );

        return $slots_by_date;
    }

    private static function apply_raw_learning_program_staff_exceptions( int $tickets_for_post_id, array &$slots_by_date, DateTimeImmutable $from_dt, DateTimeImmutable $to_dt, DateTimeZone $tz ): void {
        $rows = get_field( 'exceptions', $tickets_for_post_id );
        $rows = is_array( $rows ) ? $rows : [];
        if ( empty( $rows ) ) {
            return;
        }

        $cancel_set = [];
        $override_map = [];
        $closed_hints = [];

        foreach ( $rows as $row ) {
            $type = isset( $row['type'] ) ? (string) $row['type'] : 'cancel_range';
            $date_raw = isset( $row['date'] ) ? (string) $row['date'] : '';
            if ( $date_raw === '' ) {
                continue;
            }

            $start_ymd = self::normalize_date_to_ymd( $date_raw, $tz );
            $start_dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $start_ymd, $tz );
            if ( ! $start_dt ) {
                continue;
            }

            if ( $type === 'override_day' ) {
                if ( $start_dt < $from_dt || $start_dt > $to_dt ) {
                    continue;
                }

                $slot_rows = isset( $row['slots'] ) && is_array( $row['slots'] ) ? $row['slots'] : [];
                $override_slots = [];

                foreach ( $slot_rows as $slot_row ) {
                    if ( ! empty( $slot_row['cancel'] ) || empty( $slot_row['time_from'] ) ) {
                        continue;
                    }

                    $override_slots[] = [
                        'time'  => (string) $slot_row['time_from'],
                        'end'   => isset( $slot_row['time_to'] ) ? (string) $slot_row['time_to'] : '',
                        'staff' => $slot_row['staff'] ?? [],
                        'organizers' => $slot_row['organizers'] ?? [],
                    ];
                }

                $override_map[ $start_ymd ] = $override_slots;
                continue;
            }

            $date_to_raw = isset( $row['date_to'] ) ? (string) $row['date_to'] : '';
            $end_ymd = $date_to_raw !== '' ? self::normalize_date_to_ymd( $date_to_raw, $tz ) : $start_ymd;
            $recurrence = isset( $row['range_recurrence'] ) ? (string) $row['range_recurrence'] : 'single_year';

            self::expand_cancel_range_into_set(
                $cancel_set,
                $start_ymd,
                $end_ymd,
                $recurrence,
                $from_dt,
                $to_dt,
                $tz,
                $closed_hints,
                ''
            );
        }

        foreach ( array_keys( $cancel_set ) as $date ) {
            unset( $slots_by_date[ $date ] );
        }

        foreach ( $override_map as $date => $slots ) {
            if ( empty( $slots ) ) {
                unset( $slots_by_date[ $date ] );
            } else {
                $slots_by_date[ $date ] = $slots;
            }
        }
    }

    private static function resolve_slot_conflict_staff_ids( array $slot ): array {
        $organizer_ids = self::normalize_slot_staff_ids( $slot['organizers'] ?? [] );
        if ( ! empty( $organizer_ids ) ) {
            return $organizer_ids;
        }

        return self::normalize_slot_staff_ids( $slot['staff'] ?? [] );
    }

    private static function normalize_slot_staff_ids( $staff ): array {
        $ids = [];

        if ( is_object( $staff ) && isset( $staff->ID ) ) {
            $ids[] = (int) $staff->ID;
        } elseif ( is_numeric( $staff ) ) {
            $ids[] = (int) $staff;
        } elseif ( is_array( $staff ) ) {
            foreach ( $staff as $item ) {
                if ( is_object( $item ) && isset( $item->ID ) ) {
                    $ids[] = (int) $item->ID;
                } elseif ( is_array( $item ) && isset( $item['ID'] ) ) {
                    $ids[] = (int) $item['ID'];
                } elseif ( is_numeric( $item ) ) {
                    $ids[] = (int) $item;
                }
            }
        }

        return array_values( array_unique( array_filter( $ids, static function( $id ) {
            return (int) $id > 0;
        } ) ) );
    }

    private static function normalize_time_to_slot_key( string $time ): string {
        $time = str_replace( '-', ':', trim( $time ) );
        if ( preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
            return $time . ':00';
        }
        if ( preg_match( '/^\d{2}:\d{2}:\d{2}$/', $time ) ) {
            return $time;
        }

        return '';
    }

    private static function slot_range_to_minutes( array $slot ): ?array {
        $start = self::time_to_minutes( isset( $slot['time'] ) ? (string) $slot['time'] : '' );
        if ( $start === null ) {
            return null;
        }

        $end = null;
        if ( isset( $slot['end'] ) && (string) $slot['end'] !== '' ) {
            $end = self::time_to_minutes( (string) $slot['end'] );
        }

        if ( $end === null ) {
            $end = $start + 1;
        } elseif ( $end <= $start ) {
            $end += 1440;
        }

        return [ $start, $end ];
    }

    private static function time_to_minutes( string $time ): ?int {
        $time = str_replace( '-', ':', trim( $time ) );
        if ( preg_match( '/^(\d{2}):(\d{2})(?::\d{2})?$/', $time, $m ) ) {
            $h = (int) $m[1];
            $i = (int) $m[2];
            if ( $h >= 0 && $h <= 23 && $i >= 0 && $i <= 59 ) {
                return ( $h * 60 ) + $i;
            }
        }

        return null;
    }

    private static function time_ranges_overlap( int $a_start, int $a_end, int $b_start, int $b_end ): bool {
        return $a_start < $b_end && $b_start < $a_end;
    }

    private static function staff_ids_intersect( array $a, array $b ): bool {
        if ( empty( $a ) || empty( $b ) ) {
            return false;
        }

        $lookup = array_fill_keys( array_map( 'intval', $a ), true );
        foreach ( $b as $id ) {
            if ( isset( $lookup[ (int) $id ] ) ) {
                return true;
            }
        }

        return false;
    }

}
