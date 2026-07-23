<?php
/**
 * Plugin Name: IW Opening Hours
 * Description: AJAX Opening Hours Check.
 * Version:     1.1
 * Author:      Andreas Hatzifotis
 * Text Domain: iw-opening-hours
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once 'fields.php';

class IW_Buildings_Calendar_Service {
    public function __construct() {
        add_action( 'wp_ajax_check_opening_hours', [ __CLASS__, 'check_opening_hours' ] );
        add_action( 'wp_ajax_nopriv_check_opening_hours', [ __CLASS__, 'check_opening_hours' ] );
    }

    private static function remove_accents( $value ) {
        if ( class_exists( 'IW_Theme' ) && method_exists( 'IW_Theme', 'remove_accents' ) ) {
            return IW_Theme::remove_accents( $value );
        }

        return function_exists( 'remove_accents' ) ? remove_accents( $value ) : $value;
    }
    public static function check_opening_hours() {
        $opening_ids = isset($_REQUEST['opening_ids']) ? (array)$_REQUEST['opening_ids'] : array();
        $opening_ids = array_map('intval', $opening_ids);
        $opening_ids = array_filter($opening_ids);
        if ( empty($opening_ids) ) {
            wp_send_json_error( array( 'error' => 'No valid opening IDs specified.') );
        }
        $results = array();
        $currentYear = (int) wp_date( 'Y' );

        foreach ($opening_ids as $o_id) {
            $profile = IW_Buildings_Calendar_Service::opening_hours_get_profile( (int) $o_id, (int) $currentYear );
            $status = self::check_museum_open_status( $profile['weekData'], $profile['fixedHolidayArray'], $profile['movableHolidays'], $profile['closedInAugust'] );
            $results[$o_id] = $status;
        }
        wp_send_json_success($results);
    }

    public static function check_museum_open_status($weekData, $fixedHolidayArray, $movableHolidays, $closedInAugust) {

        $currentDateTime = new DateTime( 'now', wp_timezone() );
        $todayDayOfWeek  = (int)$currentDateTime->format('N');  // 1=Δευ, 7=Κυρ
        $todayTime       = $currentDateTime->format('H:i');
        $todayMonth      = $currentDateTime->format('m');       // π.χ. "08"
        $todayMonthDay   = $currentDateTime->format('m-d');


        // 3.1) Αν κλειστός ο Αύγουστος & σήμερα είναι Αύγουστος => κλειστό
        if ($closedInAugust && $todayMonth === '08') {
            return array(
                'isOpen'   => false,
                'message'  => self::remove_accents(__('Κλειστό τον Αύγουστο', 'iw-theme')),
                'nextOpen' => self::get_next_open_datetime($currentDateTime, $weekData, $fixedHolidayArray, $movableHolidays, $closedInAugust),
            );
        }

        // 3.2) Έλεγχος αργίας (σταθερή/κινητή)
        if ( in_array($todayMonthDay, $fixedHolidayArray) || in_array($todayMonthDay, $movableHolidays) ) {
            return array(
                'isOpen'   => false,
                'message'  => self::remove_accents(__('Κλειστό σήμερα', 'iw-theme')),
                'nextOpen' => self::get_next_open_datetime($currentDateTime, $weekData, $fixedHolidayArray, $movableHolidays, $closedInAugust),
            );
        }

        // 3.3) Έχει οριστεί ωράριο για σήμερα;
        if ( empty($weekData[$todayDayOfWeek]) ) {
            return array(
                'isOpen'   => false,
                'message'  => self::remove_accents(__('-', 'iw-theme')),
                'nextOpen' => self::get_next_open_datetime($currentDateTime, $weekData, $fixedHolidayArray, $movableHolidays, $closedInAugust),
            );
        }

        $todayInfo = $weekData[$todayDayOfWeek];
        $isClosedToday = !empty($todayInfo['closed']) ? $todayInfo['closed'] : false;
        if ($isClosedToday) {
            // Δηλωμένο «Κλειστά» για τη σημερινή ημέρα
            return array(
                'isOpen'   => false,
                'message'  => self::remove_accents(__('Κλειστό σήμερα', 'iw-theme')),
                'nextOpen' => self::get_next_open_datetime($currentDateTime, $weekData, $fixedHolidayArray, $movableHolidays, $closedInAugust),
            );
        }

        // 3.4) Έλεγχος ώρας
        $openTime  = isset($todayInfo['open_time'])  ? $todayInfo['open_time']  : '00:00';
        $closeTime = isset($todayInfo['close_time']) ? $todayInfo['close_time'] : '00:00';

        // Είμαστε εντός ωραρίου;
        if ($todayTime >= $openTime && $todayTime < $closeTime) {
            $msg = sprintf(__('Ανοιχτό έως %s', 'iw-theme'), $closeTime);
            $msg = self::remove_accents($msg);

            return array( 'isOpen'   => true, 'message'  => $msg, 'nextOpen' => null,);
        }

        // Αλλιώς εκτός ωραρίου
        return array(
            'isOpen'   => false,
            'message'  => self::remove_accents(__('Κλειστό σήμερα-', 'iw-theme')),
            'nextOpen' => self::get_next_open_datetime($currentDateTime, $weekData, $fixedHolidayArray, $movableHolidays, $closedInAugust),
        );
    }

    public static function get_next_open_datetime($fromDateTime, $weekData, $fixedHolidayArray, $movableHolidays, $closedInAugust) {

        $checkDateTime = clone $fromDateTime;

        for ($dayOffset = 0; $dayOffset < 14; $dayOffset++) {

            // Την πρώτη μέρα μένουμε στην ίδια ώρα
            // Αν είναι επόμενες μέρες, πάμε στις 00:00 της επόμενης
            if ($dayOffset > 0) {
                $checkDateTime->setTime(0, 0, 0);
                $checkDateTime->modify('+1 day');
            }

            $dayOfWeek = (int)$checkDateTime->format('N');
            $month     = $checkDateTime->format('m');
            $monthDay  = $checkDateTime->format('m-d');
            $timeNow   = $checkDateTime->format('H:i');

            // (α) Κλειστός ο Αύγουστος;
            if ($closedInAugust && $month === '08') {
                // Αν είμαστε σε Αύγουστο, προχωράμε στην επόμενη μέρα
                continue;
            }

            // (β) Αργία;
            if (in_array($monthDay, $fixedHolidayArray) || in_array($monthDay, $movableHolidays)) {
                continue;
            }

            // (γ) Έχει ωράριο αυτή η μέρα;
            if (empty($weekData[$dayOfWeek])) {
                continue;
            }

            $info = $weekData[$dayOfWeek];
            if (!empty($info['closed'])) {
                continue;
            }

            $openTime  = !empty($info['open_time'])  ? $info['open_time']  : '00:00';
            $closeTime = !empty($info['close_time']) ? $info['close_time'] : '00:00';

            // Σύγκριση ώρας
            if ($timeNow < $openTime) {
                // Π.χ. ώρα τώρα 07:00, ανοίγει 10:00
                $nextOpen = clone $checkDateTime;
                list($h, $m) = explode(':', $openTime);
                $nextOpen->setTime((int)$h, (int)$m, 0);

                return self::remove_accents(
                    __('Ανοίγει ξανά ', 'iw-theme')
                    . wp_date('d/m/Y H:i', $nextOpen->getTimestamp())
                );

            } elseif ($timeNow >= $openTime && $timeNow < $closeTime) {
                // Είμαστε ήδη μέσα στο ωράριο
                return self::remove_accents(
                    __('Ανοίγει ξανά ', 'iw-theme')
                    . wp_date('d/m/Y H:i', $checkDateTime->getTimestamp())
                );
            }
            // αλλιώς πάμε στην επόμενη μέρα
        }

        // Δεν βρέθηκε μέχρι 14 ημέρες
        return null;
    }

    public static function get_week_data( $opening_id ){
        return [
            1 => get_field( 'mon_hours', $opening_id ),
            2 => get_field( 'tue_hours', $opening_id ),
            3 => get_field( 'wed_hours', $opening_id ),
            4 => get_field( 'thu_hours', $opening_id ),
            5 => get_field( 'fri_hours', $opening_id ),
            6 => get_field( 'sat_hours', $opening_id ),
            7 => get_field( 'sun_hours', $opening_id ),
        ];
    }

    public static function get_fixed_holidays( $opening_id ){
        $fixedHolidays = get_field( 'fixed_holidays', $opening_id );
        $fixedHolidayArray = [];

        if ( $fixedHolidays ) {
            foreach ( $fixedHolidays as $row ) {
                if ( ! empty( $row['holiday_date'] ) ) {
                    $fixedHolidayArray[] = $row['holiday_date']; // expects 'm-d'
                }
            }
        }

        return $fixedHolidayArray;
    }

    public static function get_movable_holidays($year) {
        $easterSunday = IW_Buildings_Calendar_Service::get_orthodox_easter_date($year);
        $holidays = array();

        // Καθαρά Δευτέρα = 48 ημέρες πριν
        $cleanMonday = clone $easterSunday;
        $cleanMonday->modify('-48 days');
        $holidays[] = $cleanMonday->format('m-d');

        // Κυριακή Πάσχα
        $holidays[] = $easterSunday->format('m-d');

        // Δευτέρα του Πάσχα
        $easterMonday = clone $easterSunday;
        $easterMonday->modify('+1 day');
        $holidays[] = $easterMonday->format('m-d');

        // Αγίου Πνεύματος = 50 ημέρες μετά
        $pentecostMonday = clone $easterSunday;
        $pentecostMonday->modify('+50 days');
        $holidays[] = $pentecostMonday->format('m-d');

        return $holidays;
    }

    public static function get_orthodox_easter_date($year) {
        // Κλασικός αλγόριθμος (1923–2099)
        $r1 = $year % 4;
        $r2 = $year % 7;
        $r3 = $year % 19;
        $r4 = (19 * $r3 + 16) % 30;
        $r5 = (2 * $r1 + 4 * $r2 + 6 * $r4) % 7;
        $r6 = ($r4 + $r5 + 3);

        if ($r6 > 30) {
            $r6 -= 30;
            $month = 5; // Μάιος
        } else {
            $month = 4; // Απρίλιος
        }

        return new DateTime( "$year-$month-$r6", wp_timezone() );
    }

    public static function is_closed_in_august( $opening_id ){
        return (bool) get_field( 'closed_in_august', $opening_id );
    }

    public static function opening_hours_get_profile( $opening_id, $year = null ){
        $year = $year ?: (int) wp_date( 'Y' );
        $movableHolidays = IW_Buildings_Calendar_Service::get_movable_holidays( $year );

        return [
            'weekData'         => IW_Buildings_Calendar_Service::get_week_data( $opening_id ),
            'fixedHolidayArray'=> IW_Buildings_Calendar_Service::get_fixed_holidays( $opening_id ),
            'movableHolidays'  => $movableHolidays,
            'closedInAugust'   => IW_Buildings_Calendar_Service::is_closed_in_august( $opening_id ),
            'year'             => $year,
        ];
    }

    public static function get_allow_dates( $opening_id, $from = 'now', $days_ahead = 120 ){
        $tz = wp_timezone();
        $days_ahead = absint( $days_ahead );

        // Resolve start date
        if ( $from === 'now' || empty( $from ) ) {
            $today_dt = current_datetime();
            $today    = new DateTimeImmutable( $today_dt->format( 'Y-m-d' ), $tz );
        } else {
            $from_str = (string) $from;

            $dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $from_str, $tz );
            if ( ! $dt ) {
                $dt = DateTimeImmutable::createFromFormat( 'd-m-Y', $from_str, $tz );
            }
            if ( ! $dt ) {
                $dt = new DateTimeImmutable( $from_str, $tz );
            }

            $today = $dt->setTime( 0, 0, 0 );
        }

        // Build holiday set across year boundary if needed
        $year_from = (int) $today->format( 'Y' );
        $year_to   = (int) $today->modify( "+{$days_ahead} days" )->format( 'Y' );

        $movable = [];
        for ( $y = $year_from; $y <= $year_to; $y++ ) {
            foreach ( IW_Buildings_Calendar_Service::get_movable_holidays( $y ) as $md ) {
                $movable[ "{$y}-{$md}" ] = true;
            }
        }

        $weekData        = IW_Buildings_Calendar_Service::get_week_data( $opening_id );
        $fixedHolidayMd  = IW_Buildings_Calendar_Service::get_fixed_holidays( $opening_id );
        $fixed = [];
        foreach ( $fixedHolidayMd as $md ) {
            // Add fixed holidays for each year in range
            for ( $y = $year_from; $y <= $year_to; $y++ ) {
                $fixed[ "{$y}-{$md}" ] = true;
            }
        }

        $closedInAugust = IW_Buildings_Calendar_Service::is_closed_in_august( $opening_id );

        $allow_dates = [];
        for ( $i = 0; $i <= $days_ahead; $i++ ) {
            $d = $today->modify( "+{$i} days" );
            $md = $d->format( 'm-d' );

            // Closed in August
            if ( $closedInAugust && $d->format( 'm' ) === '08' ) {
                continue;
            }

            // Holiday (fixed or movable)
            $ymd_key = $d->format( 'Y-m-d' );
            $ymd_key = str_replace( '-', '-', $ymd_key );
            $ymd_lookup = $d->format( 'Y' ) . '-' . $md;

            if ( isset( $fixed[ $ymd_lookup ] ) || isset( $movable[ $ymd_lookup ] ) ) {
                continue;
            }

            // Weekday open?
            $dow = (int) $d->format( 'N' ); // 1=Mon..7=Sun (matches weekData keys)
            if ( empty( $weekData[ $dow ] ) || ! empty( $weekData[ $dow ]['closed'] ) ) {
                continue;
            }

            $allow_dates[] = $d->format( 'd-m-Y' );
        }

        return $allow_dates;
    }


}


new IW_Buildings_Calendar_Service();





