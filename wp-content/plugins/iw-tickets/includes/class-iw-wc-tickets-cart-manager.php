<?php




class IW_WC_Tickets_Cart_Manager{

    public static function init(){

        $actions = [ 'add_tickets_to_cart'  ];
        foreach ($actions as $action) {
            add_action('wp_ajax_iw_cart_' . $action, [__CLASS__, $action]);
            add_action('wp_ajax_nopriv_iw_cart_' . $action, [__CLASS__, $action]);
        }

        // Validate ticket slots server-side during cart/checkout.
        // This prevents stale/invalid slots from being checked out (e.g. booking window changed, sold out, holiday closed).
        add_action( 'woocommerce_check_cart_items', [ __CLASS__, 'validate_cart_items' ] );
        add_action( 'woocommerce_after_checkout_validation', [ __CLASS__, 'validate_checkout_items' ], 10, 2 );
        add_action( 'woocommerce_payment_complete', [ __CLASS__, 'issue_tickets_for_order' ], 20 );
        add_action( 'iw_add_to_cart_validation', [ __CLASS__, 'add_to_cart_validation' ], 10, 1 );
        add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'validate_product_add_to_cart_against_learning_program_cart' ], 20, 6 );
        add_filter( 'iw_cart_can_be_reserved', [ __CLASS__, 'cart_can_be_reserved' ], 10, 0 );
    }


    private static function get_cart_item_meta_value( array $cart_item, string $key, $default = null ) {
        if ( array_key_exists( $key, $cart_item ) ) {
            return $cart_item[ $key ];
        }

        if ( isset( $cart_item['meta'] ) && is_array( $cart_item['meta'] ) && array_key_exists( $key, $cart_item['meta'] ) ) {
            return $cart_item['meta'][ $key ];
        }

        if ( isset( $cart_item['meta_data'] ) && is_array( $cart_item['meta_data'] ) ) {
            // Some cart implementations store meta_data as an array of arrays.
            foreach ( $cart_item['meta_data'] as $row ) {
                if ( is_array( $row ) && isset( $row['key'] ) && (string) $row['key'] === $key ) {
                    return $row['value'] ?? $default;
                }
            }
        }

        return $default;
    }

    private static function extract_schedule_from_tickets_data( $tickets_data ) {
        if ( is_object( $tickets_data ) && isset( $tickets_data->schedule ) ) {
            return $tickets_data->schedule;
        }
        if ( is_array( $tickets_data ) && isset( $tickets_data['schedule'] ) ) {
            return $tickets_data['schedule'];
        }
        return null;
    }

    private static function validate_one_ticket_cart_item( string $cart_item_key, array $cart_item, bool $remove_on_fail, $errors = null ): bool {
        // Identify tickets items.
        $type = self::get_cart_item_meta_value( $cart_item, 'type', '' );
        if ( (string) $type !== 'tickets' ) {
            return true;
        }

        $ticket_id = absint( self::get_cart_item_meta_value( $cart_item, 'tickets_for_id', 0 ) );
        $day_raw   = (string) self::get_cart_item_meta_value( $cart_item, 'tickets_day', '' );
        $time_raw  = (string) self::get_cart_item_meta_value( $cart_item, 'tickets_time', '' );
        $qty       = (int) self::get_cart_item_meta_value( $cart_item, 'tickets_total', 0 );

        if ( ! $ticket_id || $day_raw === '' || $time_raw === '' || $qty < 1 ) {
            $msg = __( 'Υπάρχει μη έγκυρη κράτηση εισιτηρίων στο καλάθι σας.', 'iw-tickets' );
            if ( $errors && is_object( $errors ) && method_exists( $errors, 'add' ) ) {
                $errors->add( 'iw_tickets_invalid_item', $msg );
            } else {
                wc_add_notice( $msg, 'error' );
            }
            if ( $remove_on_fail && function_exists( 'WC' ) && WC()->cart ) {
                WC()->cart->remove_cart_item( $cart_item_key );
            }
            return false;
        }

        $tickets_data = IW_Ticketing::get_tickets_data( $ticket_id );
        if ( ! $tickets_data ) {
            $msg = __( 'Η κράτηση εισιτηρίων δεν είναι πλέον διαθέσιμη και αφαιρέθηκε από το καλάθι.', 'iw-tickets' );
            if ( $errors && is_object( $errors ) && method_exists( $errors, 'add' ) ) {
                $errors->add( 'iw_tickets_invalid_ticket', $msg );
            } else {
                wc_add_notice( $msg, 'error' );
            }
            if ( $remove_on_fail && function_exists( 'WC' ) && WC()->cart ) {
                WC()->cart->remove_cart_item( $cart_item_key );
            }
            return false;
        }

        $schedule = self::extract_schedule_from_tickets_data( $tickets_data );
        if ( empty( $schedule ) ) {
            $msg = __( 'Δεν βρέθηκε πρόγραμμα διαθεσιμότητας για την κράτησή σας.', 'iw-tickets' );
            if ( $errors && is_object( $errors ) && method_exists( $errors, 'add' ) ) {
                $errors->add( 'iw_tickets_missing_schedule', $msg );
            } else {
                wc_add_notice( $msg, 'error' );
            }
            if ( $remove_on_fail && function_exists( 'WC' ) && WC()->cart ) {
                WC()->cart->remove_cart_item( $cart_item_key );
            }
            return false;
        }

        // allowDates
        $allow_dates = [];
        if ( is_array( $schedule ) && isset( $schedule['allowDates'] ) && is_array( $schedule['allowDates'] ) ) {
            $allow_dates = $schedule['allowDates'];
        } elseif ( is_object( $schedule ) && isset( $schedule->allowDates ) && is_array( $schedule->allowDates ) ) {
            $allow_dates = $schedule->allowDates;
        }

        if ( empty( $allow_dates ) || ! in_array( $day_raw, $allow_dates, true ) ) {
            $msg = __( 'Η επιλεγμένη ημερομηνία δεν είναι πλέον διαθέσιμη. Αφαιρέσαμε τα εισιτήρια από το καλάθι.', 'iw-tickets' );
            if ( $errors && is_object( $errors ) && method_exists( $errors, 'add' ) ) {
                $errors->add( 'iw_tickets_day_unavailable', $msg );
            } else {
                wc_add_notice( $msg, 'error' );
            }
            if ( $remove_on_fail && function_exists( 'WC' ) && WC()->cart ) {
                WC()->cart->remove_cart_item( $cart_item_key );
            }
            return false;
        }

        // timesByDate
        $timesByDate = null;
        if ( is_array( $schedule ) && isset( $schedule['timesByDate'] ) ) {
            $timesByDate = $schedule['timesByDate'];
        } elseif ( is_object( $schedule ) && isset( $schedule->timesByDate ) ) {
            $timesByDate = $schedule->timesByDate;
        }

        $timesByDate_arr = (array) $timesByDate;
        $day_slots = $timesByDate_arr[ $day_raw ] ?? [];

        if ( empty( $day_slots ) || ! is_array( $day_slots ) ) {
            $msg = __( 'Δεν υπάρχουν πλέον διαθέσιμες ώρες για την επιλεγμένη ημερομηνία. Αφαιρέσαμε τα εισιτήρια από το καλάθι.', 'iw-tickets' );
            if ( $errors && is_object( $errors ) && method_exists( $errors, 'add' ) ) {
                $errors->add( 'iw_tickets_no_times', $msg );
            } else {
                wc_add_notice( $msg, 'error' );
            }
            if ( $remove_on_fail && function_exists( 'WC' ) && WC()->cart ) {
                WC()->cart->remove_cart_item( $cart_item_key );
            }
            return false;
        }

        $selected_slot = null;
        foreach ( $day_slots as $slot ) {
            if ( ! is_array( $slot ) ) {
                continue;
            }
            $slot_time = isset( $slot['time'] ) ? (string) $slot['time'] : '';
            if ( $slot_time === $time_raw ) {
                $selected_slot = $slot;
                break;
            }
        }

        if ( ! $selected_slot ) {
            $msg = __( 'Η επιλεγμένη ώρα δεν είναι πλέον διαθέσιμη. Αφαιρέσαμε τα εισιτήρια από το καλάθι.', 'iw-tickets' );
            if ( $errors && is_object( $errors ) && method_exists( $errors, 'add' ) ) {
                $errors->add( 'iw_tickets_time_unavailable', $msg );
            } else {
                wc_add_notice( $msg, 'error' );
            }
            if ( $remove_on_fail && function_exists( 'WC' ) && WC()->cart ) {
                WC()->cart->remove_cart_item( $cart_item_key );
            }
            return false;
        }

        $slot_status = isset( $selected_slot['status'] ) ? (string) $selected_slot['status'] : 'available';
        if ( $slot_status === 'sold-out' ) {
            $msg = __( 'Η επιλεγμένη ώρα έχει πλέον εξαντληθεί. Αφαιρέσαμε τα εισιτήρια από το καλάθι.', 'iw-tickets' );
            if ( $errors && is_object( $errors ) && method_exists( $errors, 'add' ) ) {
                $errors->add( 'iw_tickets_sold_out', $msg );
            } else {
                wc_add_notice( $msg, 'error' );
            }
            if ( $remove_on_fail && function_exists( 'WC' ) && WC()->cart ) {
                WC()->cart->remove_cart_item( $cart_item_key );
            }
            return false;
        }

        if ( isset( $selected_slot['availability'] ) ) {
            $availability = (int) $selected_slot['availability'];
            if ( $availability >= 0 && $qty > $availability ) {
                $msg = __( 'Δεν υπάρχει πλέον διαθέσιμη χωρητικότητα για τον αριθμό εισιτηρίων που επιλέξατε. Αφαιρέσαμε τα εισιτήρια από το καλάθι.', 'iw-tickets' );
                if ( $errors && is_object( $errors ) && method_exists( $errors, 'add' ) ) {
                    $errors->add( 'iw_tickets_capacity', $msg );
                } else {
                    wc_add_notice( $msg, 'error' );
                }
                if ( $remove_on_fail && function_exists( 'WC' ) && WC()->cart ) {
                    WC()->cart->remove_cart_item( $cart_item_key );
                }
                return false;
            }
        }

        // Skip hold validation for onsite/cashier flow.
        // Onsite tickets are typically issued immediately and should not depend on temporary holds.
        $channel = (string) self::get_cart_item_meta_value( $cart_item, 'tickets_channel', 'online' );
        $skip_hold = ( $channel === 'onsite' || $channel === 'cashier' );
        $skip_hold = (bool) apply_filters( 'iw_tickets_skip_hold_validation', $skip_hold, $cart_item, $cart_item_key );

        if ( $skip_hold ) {
            return true;
        }

        // --- Hold validation (DB) ---
        // Schedule checks ensure the slot is still theoretically bookable.
        // Hold validation ensures the user still has an active reservation for this slot.
        if ( class_exists( 'IW_Tickets_DB' ) ) {
            $hold_cart_item = [
                'tickets_for_id' => $ticket_id,
                'tickets_day'    => $day_raw,
                'tickets_time'   => $time_raw,
            ];

            $hold_info = IW_Tickets_DB::get_hold_info( $hold_cart_item );

            if ( ! $hold_info || empty( $hold_info->expires_at ) ) {
                $msg = __( 'Η κράτηση του slot έχει λήξει ή δεν είναι πλέον ενεργή. Παρακαλούμε επιλέξτε ξανά ημερομηνία/ώρα.', 'iw-tickets' );
                if ( $errors && is_object( $errors ) && method_exists( $errors, 'add' ) ) {
                    $errors->add( 'iw_tickets_hold_missing', $msg );
                } else {
                    wc_add_notice( $msg, 'error' );
                }
                if ( $remove_on_fail && function_exists( 'WC' ) && WC()->cart ) {
                    WC()->cart->remove_cart_item( $cart_item_key );
                }
                return false;
            }

            // Expiry check.
            $expires_ts = strtotime( (string) $hold_info->expires_at );
            $now_ts     = (int) current_time( 'timestamp' );
            if ( $expires_ts !== false && $expires_ts <= $now_ts ) {
                $msg = __( 'Η κράτηση του slot έχει λήξει. Παρακαλούμε επιλέξτε ξανά ημερομηνία/ώρα.', 'iw-tickets' );
                if ( $errors && is_object( $errors ) && method_exists( $errors, 'add' ) ) {
                    $errors->add( 'iw_tickets_hold_expired', $msg );
                } else {
                    wc_add_notice( $msg, 'error' );
                }
                if ( $remove_on_fail && function_exists( 'WC' ) && WC()->cart ) {
                    WC()->cart->remove_cart_item( $cart_item_key );
                }
                return false;
            }

            // Quantity check: ensure hold qty covers requested qty.
            if ( isset( $hold_info->qty ) ) {
                $hold_qty = (int) $hold_info->qty;
                if ( $hold_qty > 0 && $qty > $hold_qty ) {
                    $msg = __( 'Η κράτησή σας δεν καλύπτει πλέον τον αριθμό εισιτηρίων που έχετε στο καλάθι. Παρακαλούμε επιλέξτε ξανά.', 'iw-tickets' );
                    if ( $errors && is_object( $errors ) && method_exists( $errors, 'add' ) ) {
                        $errors->add( 'iw_tickets_hold_qty_mismatch', $msg );
                    } else {
                        wc_add_notice( $msg, 'error' );
                    }
                    if ( $remove_on_fail && function_exists( 'WC' ) && WC()->cart ) {
                        WC()->cart->remove_cart_item( $cart_item_key );
                    }
                    return false;
                }
            }
        }

        return true;
    }

    public static function validate_cart_items(): void {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return;
        }

        $cart = WC()->cart->get_cart();
        if ( empty( $cart ) || ! is_array( $cart ) ) {
            return;
        }

        foreach ( $cart as $cart_item_key => $cart_item ) {
            if ( ! is_array( $cart_item ) ) {
                continue;
            }
            // Remove invalid items at cart stage.
            self::validate_one_ticket_cart_item( (string) $cart_item_key, $cart_item, true, null );
        }
    }

    public static function validate_checkout_items( $data, $errors ): void {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return;
        }

        $cart = WC()->cart->get_cart();
        if ( empty( $cart ) || ! is_array( $cart ) ) {
            return;
        }

        foreach ( $cart as $cart_item_key => $cart_item ) {
            if ( ! is_array( $cart_item ) ) {
                continue;
            }
            self::validate_one_ticket_cart_item( (string) $cart_item_key, $cart_item, false, $errors );
        }
    }

    public static function fail( $message, $extra = [] ) {
        wp_send_json_error( array_merge( [ 'message' => $message ], $extra ) );
    }

    private static function learning_program_product_mix_message(): string {
        return sprintf(
            __( 'Έχετε εκπαιδευτικά προγράμματα στο καλάθι σας που δεν μπορούν να συνδυαστούν με προϊόντα. Τα εκπαιδευτικά προγράμματα δεν μπορούν να συνδυαστούν με προσωπικές αγορές. Παρακαλούμε <a href="%s">ολοκληρώστε</a> πρώτα την παραγγελία σας ή αδειάστε το <a href="%s">καλάθι</a> σας για να συνεχίσετε.', 'iw-theme' ),
            esc_url( wc_get_checkout_url() ),
            esc_url( wc_get_cart_url() )
        );
    }

    private static function is_learning_program_cart_add( int $product_id, array $cart_item_data = [] ): bool {
        $tickets_for_id = ! empty( $cart_item_data['tickets_for_id'] ) ? absint( $cart_item_data['tickets_for_id'] ) : 0;
        if ( $tickets_for_id > 0 ) {
            return get_post_type( $tickets_for_id ) === 'learning-program';
        }

        $cpt_id = ! empty( $cart_item_data['cpt_id'] ) ? absint( $cart_item_data['cpt_id'] ) : 0;
        if ( $cpt_id > 0 ) {
            return get_post_type( $cpt_id ) === 'learning-program';
        }

        return get_post_type( $product_id ) === 'learning-program';
    }

    private static function cart_has_learning_program_ticket(): bool {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return false;
        }

        $cart = WC()->cart->get_cart();
        if ( empty( $cart ) || ! is_array( $cart ) ) {
            return false;
        }

        foreach ( $cart as $cart_item ) {
            if ( ! is_array( $cart_item ) ) {
                continue;
            }

            $ticket_id = ! empty( $cart_item['tickets_for_id'] ) ? absint( $cart_item['tickets_for_id'] ) : 0;
            if ( $ticket_id > 0 && get_post_type( $ticket_id ) === 'learning-program' ) {
                return true;
            }

            $cpt_id = ! empty( $cart_item['cpt_id'] ) ? absint( $cart_item['cpt_id'] ) : 0;
            if ( $cpt_id > 0 && get_post_type( $cpt_id ) === 'learning-program' ) {
                return true;
            }
        }

        return false;
    }

    public static function validate_product_add_to_cart_against_learning_program_cart( $passed, $product_id, $quantity = 1, $variation_id = 0, $variations = [], $cart_item_data = [] ) {
        if ( ! $passed || ! self::cart_has_learning_program_ticket() ) {
            return $passed;
        }

        $cart_item_data = is_array( $cart_item_data ) ? $cart_item_data : [];
        if ( self::is_learning_program_cart_add( absint( $product_id ), $cart_item_data ) ) {
            return $passed;
        }

        wc_add_notice( self::learning_program_product_mix_message(), 'error' );
        return false;
    }


    public static function get_slot_end_for_post_and_time( int $post_id, string $day_ymd, string $time_hm ): ?string
    {
        if ( $post_id <= 0 || $day_ymd === '' || $time_hm === '' ) {
            return null;
        }


        try {
            // Ask calendar for specific day only.
            $schedule = IW_Tickets_Calendar_Service::get_schedule( $post_id, false, $day_ymd, 1 );
            $timesByDate = $schedule['timesByDate'] ?? null;
            if ( ! $timesByDate || ! is_object( $timesByDate ) ) {
                return null;
            }
            $slots = $timesByDate->{$day_ymd} ?? null;
            if ( ! $slots || ! is_array( $slots ) ) {
                return null;
            }

            foreach ( $slots as $slot ) {
                if ( ! is_array( $slot ) ) continue;
                $t = isset( $slot['time'] ) ? (string) $slot['time'] : '';
                if ( $t === $time_hm ) {
                    $end = isset( $slot['end'] ) ? (string) $slot['end'] : '';
                    if ( $end !== '' ) {
                        return $day_ymd . ' ' . $end . ':00';
                    }
                    break;
                }
            }
        } catch ( Exception $e ) {
            return null;
        }

        return null;
    }

    public static function user_can_buy_learning_program()
    {
        if( ! is_user_logged_in() ) {
            self::fail( sprintf( __( 'Για να έχετε τη δυνατότητα κράτησης / αγοράς εκπαιδευτικού προγράμματος θα πρέπει να έχετε <span class="underline cursor-pointer" %s>συνδεθεί</span>. Αν δεν έχετε λογαριασμό μπορείτε να εγγραφείτε <span class="underline cursor-pointer" %s>εδώ</span>', 'iw-theme' ),
                'data-module-open-modal data-modal-name="login-modal"', 'data-module-open-modal data-modal-name="registration-modal"') );
        } else {
            $userId = wp_get_current_user()->ID;
            if( ! get_field( 'is_teacher', 'user_' . $userId ) ) {
                self::fail( __( 'Πρέπει να είστε εκπαιδευτικός για να προσθέσετε προγράμματα', 'iw-theme') );
            } else if( ! get_field( 'user_school_unit', 'user_' . $userId ) ) { // unlikely to happen
                self::fail( __( 'Πρέπει να επιλέξετε το εκπαιδευτικό ίδρυμα', 'iw-theme') );
            }

        }
    }

    public static function cart_has_learning_programs_only(): bool {
        if ( ! function_exists('WC') || ! WC()->cart ) {
            return false;
        }

        $cart = WC()->cart->get_cart();
        if ( empty( $cart ) || ! is_array( $cart ) ) {
            return false;
        }

        $found_learning_program = false;
        
        foreach ( $cart as $cart_item ) {
            $post_id = ! empty( $cart_item['tickets_for_id'] ) ? absint( $cart_item['tickets_for_id'] ) : 0;
            if ( ! $post_id && ! empty( $cart_item['cpt_id'] ) ) {
                $post_id = absint( $cart_item['cpt_id'] );
            }

            if ( ! $post_id ) {
                // Any regular product/subscription/donation means it's not "learning programs only".
                return false;
            }

            if ( get_post_type( $post_id ) !== 'learning-program' ) {
                // If any ticket belongs to another post type (event, exhibition, etc).
                return false;
            }

            $found_learning_program = true;
        }

        return $found_learning_program;
    }

    public static function get_reservable_ticket_post_types(): array {
        $post_types = (array) apply_filters( 'iw_pay_at_museum_ticket_post_types', [ 'learning-program' ] );
        $post_types = array_values( array_unique( array_filter( array_map( 'sanitize_key', $post_types ) ) ) );

        return ! empty( $post_types ) ? $post_types : [ 'learning-program' ];
    }

    public static function cart_has_reservable_ticket_types_only(): bool {
        if ( ! function_exists('WC') || ! WC()->cart ) {
            return false;
        }

        $cart = WC()->cart->get_cart();
        if ( empty( $cart ) || ! is_array( $cart ) ) {
            return false;
        }

        $allowed_post_types = self::get_reservable_ticket_post_types();
        $require_schools_only = (bool) apply_filters( 'iw_learning_program_reservations_require_schools_only', true );
        $found_reservable_ticket = false;

        foreach ( $cart as $cart_item ) {
            if ( empty( $cart_item['tickets_for_id'] ) ) {
                return false;
            }

            $post_type = get_post_type( $cart_item['tickets_for_id'] );
            if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
                return false;
            }

            if ( $post_type === 'learning-program' && $require_schools_only && ! get_field( 'schools_only', $cart_item['tickets_for_id'] ) ) {
                return false;
            }

            $found_reservable_ticket = true;
        }

        return $found_reservable_ticket;
    }

    public static function cart_can_be_reserved() {
        // Reservations only allowed when cart contains selected reservable ticket types.
        
        if ( ! self::cart_has_reservable_ticket_types_only() ) {
            return false;
        }
        


        if ( ! function_exists('WC') || ! WC()->cart ) {
            return false;
        }

        $cart = WC()->cart->get_cart();
        $now = current_time('timestamp');
        


        foreach ( $cart as $cart_item ) {
            if ( empty($cart_item['tickets_for_id']) ) {
                continue;
            }

            $post_type = get_post_type( $cart_item['tickets_for_id'] );
            if ( ! in_array( $post_type, self::get_reservable_ticket_post_types(), true ) ) {
                continue;
            }

            $day  = isset($cart_item['tickets_day'])  ? (string) $cart_item['tickets_day']  : '';
            $time = isset($cart_item['tickets_time']) ? (string) $cart_item['tickets_time'] : '00:00';

            if ( $day === '' ) {
                continue;
            }

            // Normalize time
            $time = str_replace('-', ':', $time);
            if ( ! preg_match('/^\d{2}:\d{2}$/', $time) ) {
                $time = '00:00';
            }

            // Slot datetime
            $slot_ts = strtotime($day . ' ' . $time . ':00');
            if ( ! $slot_ts ) {
                return false;
            }

            // configurable days before
            $days_before = (int) apply_filters( 'iw_learning_program_reservation_days_before', 4 );

            if ( $days_before < 0 ) {
                $days_before = 0;
            }

            $reservation_deadline = strtotime("-{$days_before} days", $slot_ts);

            if ( $now > $reservation_deadline ) {
                return false;
            }
        }

        return true;
    }

    public static function add_to_cart_validation( $ticket_id, string $day = '', string $time = '' ){
        $post = get_post( $ticket_id );

        if ( ! $post || $post->post_status !== 'publish' ) self::fail( 'Not found' );

        $is_adding_learning_program = get_post_type( $post ) === 'learning-program';

        if( $is_adding_learning_program ){
            self::user_can_buy_learning_program();
        }

        if ( count( WC()->cart->get_cart() ) === 0 ) return true; // cart is empty

        $cart_has_learning_programs_only = self::cart_has_learning_programs_only();
        $checkout_url = esc_url( wc_get_checkout_url() );
        $cart_url = esc_url( wc_get_cart_url() );

        if ( $is_adding_learning_program ) {
            if (
                $day !== ''
                && $time !== ''
                && class_exists( 'IW_Tickets_Calendar_Service' )
                && method_exists( 'IW_Tickets_Calendar_Service', 'learning_program_slot_has_staff_conflict' )
                && IW_Tickets_Calendar_Service::learning_program_slot_has_staff_conflict( (int) $ticket_id, $day, $time )
            ) {
                self::fail( __( 'Η επιλεγμένη ώρα δεν είναι διαθέσιμη, γιατί ένας από τους διοργανωτές είναι ήδη δεσμευμένος σε άλλο εκπαιδευτικό πρόγραμμα την ίδια ώρα. Παρακαλούμε επιλέξτε άλλη ώρα.', 'iw-theme' ) );
            }

            if( ! $cart_has_learning_programs_only ){
                self::fail( sprintf( __( 'Έχετε προϊόντα στο καλάθι σας που δεν μπορούν να συνδυαστούν με εκπαιδευτικά προγράμματα. Τα εκπαιδευτικά προγράμματα δεν μπορούν να συνδυαστούν με προσωπικές αγορές. Παρακαλούμε <a href="%s">ολοκληρώστε</a> πρώτα την παραγγελία σας ή αδειάστε το <a href="%s">καλάθι</a> σας για να συνεχίσετε.', 'iw-theme' ),  $checkout_url, $cart_url ) );
            }
        } else if ( self::cart_has_learning_program_ticket() ) {
            self::fail( self::learning_program_product_mix_message() );
        }
    }

    public static function add_tickets_to_cart(){
        check_ajax_referer( 'iw_cart_add_tickets_to_cart', 'nonce' );


        $visitors_raw = $_POST['visitors'] ?? '[]';
        $ticket_id = absint( $_POST['tickets-for-id'] ?? 0 );
        $day_raw  = isset( $_POST['day'] )  ? sanitize_text_field( wp_unslash( $_POST['day'] ) )  : '';
        $time_raw = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : '';


        $visitors_json = is_string( $visitors_raw ) ? wp_unslash( $visitors_raw ) : wp_json_encode( $visitors_raw );
        $visitors = json_decode( $visitors_json, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error([ 'message' => 'Invalid visitors payload', 'error' => json_last_error_msg() ]);
        }

        if ( ! $ticket_id ) {
            self::fail( 'Missing ticket-id' );
        }

        $tickets_data = IW_Ticketing::get_tickets_data( $ticket_id );
        if ( ! $tickets_data ) {
            self::fail( 'Invalid ticket-id' );
        }

        // Expect day like YYYY-MM-DD
        if ( empty( $day_raw ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $day_raw ) ) {
            self::fail( 'Invalid or missing day' );
        }

        // Accept time like HH:MM or HH-MM (frontend may use either). Normalize to HH:MM.
        $time_norm = str_replace( '-', ':', $time_raw );
        if ( empty( $time_norm ) || ! preg_match( '/^\d{2}:\d{2}$/', $time_norm ) ) {
            self::fail( 'Invalid or missing time' );
        }
        $hh = (int) substr( $time_norm, 0, 2 );
        $mm = (int) substr( $time_norm, 3, 2 );
        if ( $hh < 0 || $hh > 23 || $mm < 0 || $mm > 59 ) {
            self::fail( 'Invalid time value' );
        }

        self::add_to_cart_validation( $ticket_id, $day_raw, $time_norm );

        if ( ! is_array( $visitors ) ) {
            self::fail( 'Invalid visitors payload (not an array)' );
        }

        // Hard cap to avoid abuse
        $max_visitors = apply_filters( 'iw_cart_max_visitors_per_request', 50 );
        if ( count( $visitors ) > $max_visitors ) {
            self::fail( 'Too many visitors in one request' );
        }

        $normalized_visitors = [];
        foreach ( $visitors as $i => $v ) {
            if ( ! is_array( $v ) ) {
                self::fail( sprintf( 'Invalid visitor at index %d', $i ) );
            }

            // Only accept known fields. Never trust price/locked coming from the client.
            $first = isset( $v['first'] ) ? sanitize_text_field( (string) $v['first'] ) : '';
            $last  = isset( $v['last'] )  ? sanitize_text_field( (string) $v['last'] )  : '';
            $categoryId = isset( $v['category-id'] ) ? sanitize_key( (string) $v['category-id'] ) : '';

            // Basic length limits
            if ( strlen( $first ) > 80 || strlen( $last ) > 80 ) {
                self::fail( sprintf( 'Visitor name too long at index %d', $i ) );
            }

            if ( empty( $categoryId ) ) {
                self::fail( sprintf( 'Missing visitor type at index %d', $i ) );
            }

            // Defensive: allow only [a-z0-9_-] in category-id (sanitize_key already does it, but keep explicit intent)
            if ( ! preg_match( '/^[a-z0-9_-]+$/', $categoryId ) ) {
                self::fail( sprintf( 'Invalid visitor type at index %d', $i ) );
            }

            $normalized_visitors[] = [ 'first' => $first, 'last'  => $last, 'category-id' => $categoryId ];
        }

        // Replace original visitors with normalized version for downstream logic.
        $visitors = $normalized_visitors;

        // --- Validate request against server-side tickets data (single source of truth) ---

        // Normalize schedule shape.
        $schedule = null;
        if ( is_object( $tickets_data ) && isset( $tickets_data->schedule ) ) {
            $schedule = $tickets_data->schedule;
        }

        if ( empty( $schedule ) ) {
            self::fail( 'Δεν βρέθηκε πρόγραμμα διαθεσιμότητας.' );
        }

        $allow_dates = [];
        if ( is_array( $schedule ) && isset( $schedule['allowDates'] ) && is_array( $schedule['allowDates'] ) ) {
            $allow_dates = $schedule['allowDates'];
        } elseif ( is_object( $schedule ) && isset( $schedule->allowDates ) && is_array( $schedule->allowDates ) ) {
            $allow_dates = $schedule->allowDates;
        }

        if ( empty( $allow_dates ) || ! in_array( $day_raw, $allow_dates, true ) ) {
            self::fail( 'Η επιλεγμένη ημερομηνία δεν είναι διαθέσιμη.' );
        }

        $timesByDate = null;
        if ( is_array( $schedule ) && isset( $schedule['timesByDate'] ) ) {
            $timesByDate = $schedule['timesByDate'];
        } elseif ( is_object( $schedule ) && isset( $schedule->timesByDate ) ) {
            $timesByDate = $schedule->timesByDate;
        }

        $timesByDate_arr = (array) $timesByDate;
        $day_slots = $timesByDate_arr[ $day_raw ] ?? [];

        if ( empty( $day_slots ) || ! is_array( $day_slots ) ) {
            self::fail( 'Δεν υπάρχουν διαθέσιμες ώρες για την επιλεγμένη ημερομηνία.' );
        }

        $selected_slot = null;
        foreach ( $day_slots as $slot ) {
            if ( ! is_array( $slot ) ) {
                continue;
            }
            $slot_time = isset( $slot['time'] ) ? (string) $slot['time'] : '';
            if ( $slot_time === $time_norm ) {
                $selected_slot = $slot;
                break;
            }
        }

        if ( ! $selected_slot ) {
            self::fail( 'Η επιλεγμένη ώρα δεν είναι διαθέσιμη.' );
        }

        $slot_status = isset( $selected_slot['status'] ) ? (string) $selected_slot['status'] : 'available';
        ////////
        if (
            class_exists( 'IW_Tickets_Calendar_Service' )
            && method_exists( 'IW_Tickets_Calendar_Service', 'learning_program_slot_has_staff_conflict' )
            && IW_Tickets_Calendar_Service::learning_program_slot_has_staff_conflict( $ticket_id, $day_raw, $time_norm )
        ) {
            self::fail( __( 'Η επιλεγμένη ώρα δεν είναι διαθέσιμη, γιατί ένας από τους διοργανωτές είναι ήδη δεσμευμένος σε άλλο εκπαιδευτικό πρόγραμμα την ίδια ώρα. Παρακαλούμε επιλέξτε άλλη ώρα.', 'iw-theme' ) );
        }

        if ( $slot_status === 'sold-out' ) {
            self::fail( 'Η επιλεγμένη ώρα έχει εξαντληθεί.' );
        }

        $requested_tickets = count( $visitors );
        if ( isset( $selected_slot['availability'] ) ) {
            $availability = (int) $selected_slot['availability'];
            if ( $availability >= 0 && $requested_tickets > $availability ) {
                self::fail( 'Δεν υπάρχει διαθέσιμη χωρητικότητα για τον αριθμό εισιτηρίων που επιλέξατε.', [
                    'availability' => $availability,
                    'requested' => $requested_tickets,
                ] );
            }
        }

        // Global min/max from tickets_data (fallback to 1/50-ish if missing).
        $global_min = 1;
        // Default safety cap (used when tickets_data does not provide a numeric max).
        $global_max = (int) apply_filters( 'iw_cart_default_global_max_tickets', 50 );
        if ( is_array( $tickets_data ) ) {
            if ( isset( $tickets_data['min_tickets'] ) ) $global_min = (int) $tickets_data['min_tickets'];
            if ( array_key_exists( 'max_tickets', $tickets_data ) ) {
                $mx = $tickets_data['max_tickets'];
                // If max_tickets is null/'null', keep the default safety cap.
                $global_max = ( $mx === null || $mx === 'null' ) ? $global_max : (int) $mx;
            }
        } elseif ( is_object( $tickets_data ) ) {
            if ( isset( $tickets_data->min_tickets ) ) $global_min = (int) $tickets_data->min_tickets;
            if ( property_exists( $tickets_data, 'max_tickets' ) ) {
                $mx = $tickets_data->max_tickets;
                // If max_tickets is null/'null', keep the default safety cap.
                $global_max = ( $mx === null || $mx === 'null' ) ? $global_max : (int) $mx;
            }
        }
        if ( $global_min < 1 ) $global_min = 1;

        if ( $requested_tickets < $global_min ) {
            self::fail( sprintf( 'Πρέπει να επιλέξετε τουλάχιστον %d εισιτήρια.', $global_min ) );
        }
        if ( $global_max !== null && $global_max > 0 && $requested_tickets > $global_max ) {
            self::fail( sprintf( 'Μπορείτε να επιλέξετε έως %d εισιτήρια.', $global_max ) );
        }

        // Build allowed visitor type map from ticket_categories.
        $ticket_categories = [];
        if ( is_array( $tickets_data ) && isset( $tickets_data['ticket_categories'] ) && is_array( $tickets_data['ticket_categories'] ) ) {
            $ticket_categories = $tickets_data['ticket_categories'];
        } elseif ( is_object( $tickets_data ) && isset( $tickets_data->ticket_categories ) && is_array( $tickets_data->ticket_categories ) ) {
            $ticket_categories = $tickets_data->ticket_categories;
        }

        if ( empty( $ticket_categories ) ) {
            self::fail( 'Δεν βρέθηκαν κατηγορίες εισιτηρίων.' );
        }

        // Map: visitor_type (subcategory value) -> category object (min/max/require_full_name/price)
        $allowed_types = [];
        foreach ( $ticket_categories as $cat ) {
            if ( ! is_object( $cat ) ) {
                continue;
            }
            $require_full_name = ! empty( $cat->require_full_name );
            $cat_min = isset( $cat->min_tickets ) ? (int) $cat->min_tickets : 0;
            $cat_max = isset( $cat->max_tickets ) ? (int) $cat->max_tickets : 0;

            $subs = isset( $cat->subcategories ) && is_array( $cat->subcategories ) ? $cat->subcategories : [];
            foreach ( $subs as $sub ) {
                if ( ! is_object( $sub ) ) {
                    continue;
                }
                $val = isset( $sub->value ) ? (string) $sub->value : '';
                if ( $val === '' ) {
                    continue; // skip placeholder
                }

                // Prefer explicit subcategory price (server-side source of truth).
                $price = null;
                if ( isset( $sub->price ) && $sub->price !== '' && $sub->price !== null && is_numeric( $sub->price ) ) {
                    $price = (float) $sub->price;
                }

                // Final fallback to category price.
                if ( $price === null && isset( $cat->price ) && is_numeric( $cat->price ) ) {
                    $price = (float) $cat->price;
                }

                $label = null;
                if ( isset( $sub->label ) && is_string( $sub->label ) && trim( (string) $sub->label ) !== '' ) {
                    $label = trim( (string) $sub->label );
                }

                $allowed_types[ sanitize_key( $val ) ] = [
                    'require_full_name' => $require_full_name,
                    'min_tickets' => $cat_min,
                    'max_tickets' => $cat_max,
                    'price' => $price,
                    'label' => $label,
                ];
            }
        }

        if ( empty( $allowed_types ) ) {
            self::fail( 'Δεν βρέθηκαν έγκυροι τύποι εισιτηρίων.' );
        }

        // Validate each visitor category-id exists in allowed types and enforce full name when required.
        // Also enrich each visitor with server-side category label and per-ticket price.
        $counts_by_type = [];
        foreach ( $visitors as $i => $v ) {
            $type = isset( $v['category-id'] ) ? sanitize_key( (string) $v['category-id'] ) : '';
            if ( $type === '' || ! isset( $allowed_types[ $type ] ) ) {
                self::fail( sprintf( 'Μη έγκυρος τύπος εισιτηρίου στο εισιτήριο %d.', $i + 1 ) );
            }

            $counts_by_type[ $type ] = ( $counts_by_type[ $type ] ?? 0 ) + 1;

            if ( ! empty( $allowed_types[ $type ]['require_full_name'] ) ) {
                $first = isset( $v['first'] ) ? trim( (string) $v['first'] ) : '';
                $last  = isset( $v['last'] ) ? trim( (string) $v['last'] ) : '';
                if ( $first === '' || $last === '' ) {
                    self::fail( sprintf( 'Απαιτείται ονοματεπώνυμο για το εισιτήριο %d.', $i + 1 ) );
                }
            }

            $price = $allowed_types[ $type ]['price'] ?? null;
            if ( $price === null || ! is_numeric( $price ) ) {
                self::fail( sprintf( 'Δεν βρέθηκε τιμή για το εισιτήριο %d.', $i + 1 ) );
            }

            $label = $allowed_types[ $type ]['label'] ?? null;
            if ( ! is_string( $label ) || $label === '' ) {
                // Fallback to type key if label is not available.
                $label = (string) $type;
            }

            // Write back enriched fields.
            $visitors[ $i ]['category-name'] = $label;
            $visitors[ $i ]['price'] = (float) $price;
        }

        // Enforce per-type (per category) min/max where defined.
        foreach ( $counts_by_type as $type => $cnt ) {
            $cat_min = (int) ( $allowed_types[ $type ]['min_tickets'] ?? 0 );
            $cat_max = (int) ( $allowed_types[ $type ]['max_tickets'] ?? 0 );
            if ( $cat_min > 0 && $cnt < $cat_min ) {
                self::fail( sprintf( 'Για τον τύπο "%s" πρέπει να επιλέξετε τουλάχιστον %d εισιτήρια.', $type, $cat_min ) );
            }
            if ( $cat_max > 0 && $cnt > $cat_max ) {
                self::fail( sprintf( 'Για τον τύπο "%s" μπορείτε να επιλέξετε έως %d εισιτήρια.', $type, $cat_max ) );
            }
        }

        // Compute server-side total price from enriched visitors (per-ticket price).
        $total_price = 0.0;
        foreach ( $visitors as $i => $v ) {
            $price = $v['price'] ?? null;
            if ( $price === null || ! is_numeric( $price ) ) {
                self::fail( sprintf( 'Δεν βρέθηκε τιμή για το εισιτήριο %d.', $i + 1 ) );
            }
            $total_price += (float) $price;
        }

        // Add to Woo cart as virtual product item.

        $cart_key = CPT_As_Product::add_to_cart([
            'type'  => 'tickets',
            'title' => get_the_title( $ticket_id ),
            'price' => $total_price,
            'meta'  => [
                'tickets_for_id'   => $ticket_id,
                'tickets_day'      => $day_raw,
                'tickets_time'     => $time_norm,
                'tickets_channel'   => 'online',
                'tickets_total'    => count( $visitors ),
                'tickets_visitors' => wp_json_encode( $visitors ),
            ],
        ]);

        if ( is_wp_error( $cart_key ) ) {
            self::fail( 'Αποτυχία προσθήκης στο καλάθι.'  );
        }

        wp_send_json_success( [
            'redirect_url'   => wc_get_cart_url(),
            'cart_item_key'  => $cart_key,
            'visitors_count' => count( $visitors ),
            'ticket_id'      => $ticket_id,
            'day'            => $day_raw,
            'time'           => $time_norm,
            'slot_status'    => is_array( $selected_slot ) && isset( $selected_slot['status'] ) ? (string) $selected_slot['status'] : null,
            'slot_availability' => is_array( $selected_slot ) && isset( $selected_slot['availability'] ) ? (int) $selected_slot['availability'] : null,
            'total_price'    => $total_price,
            'cart_url'       => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
        ] );
    }

    /**
     * Helper: Build normalized ticket DB rows for a given order item.
     */
    public static function build_ticket_rows_from_order_item( $order, $item_id, $item ) {

        $tickets_for_id = absint( $item->get_meta( 'tickets_for_id', true ) );
        $tickets_day    = (string) $item->get_meta( 'tickets_day', true );
        $tickets_time   = (string) $item->get_meta( 'tickets_time', true );
        $tickets_total  = (int) $item->get_meta( 'tickets_total', true );
        $visitors_json  = (string) $item->get_meta( 'tickets_visitors', true );

        if ( ! $tickets_for_id || $tickets_day === '' || $tickets_time === '' ) {
            return [];
        }

        $visitors = [];
        if ( $visitors_json !== '' ) {
            $decoded = json_decode( $visitors_json, true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                $visitors = $decoded;
            }
        }

        if ( empty( $visitors ) ) {
            $fallback_n = $tickets_total > 0 ? $tickets_total : (int) $item->get_quantity();
            $fallback_n = max( 1, $fallback_n );
            for ( $i = 0; $i < $fallback_n; $i++ ) {
                $visitors[] = [ 'first' => null, 'last' => null, 'category-id' => null ];
            }
        }

        $time_norm = str_replace( '-', ':', $tickets_time );
        if ( ! preg_match( '/^\d{2}:\d{2}$/', $time_norm ) ) {
            if ( preg_match( '/^\d{2}:\d{2}:\d{2}$/', $time_norm ) ) {
                $time_norm = substr( $time_norm, 0, 5 );
            }
        }

        $slot_start = $tickets_day . ' ' . $time_norm . ':00';
        $slot_end   = self::get_slot_end_for_post_and_time( (int) $tickets_for_id, (string) $tickets_day, (string) $time_norm );

        $line_total_excl = is_callable( [ $item, 'get_total' ] ) ? (float) $item->get_total() : 0.0;
        $line_tax        = is_callable( [ $item, 'get_total_tax' ] ) ? (float) $item->get_total_tax() : 0.0;
        $line_total_incl = $line_total_excl + $line_tax;

        $currency   = is_callable( [ $order, 'get_currency' ] ) ? (string) $order->get_currency() : '';
        $n_tickets  = ! empty( $visitors ) ? count( $visitors ) : 1;

        $effective_tax_rate = ( $line_total_excl > 0 ) ? ( $line_tax / $line_total_excl ) : null;

        $avg_unit_price_excl = $n_tickets > 0 ? round( $line_total_excl / $n_tickets, 6 ) : null;
        $avg_unit_price_incl = $n_tickets > 0 ? round( $line_total_incl / $n_tickets, 6 ) : null;

        $rows = [];

        foreach ( $visitors as $v ) {

            $first = is_array( $v ) ? ( $v['first'] ?? null ) : null;
            $last  = is_array( $v ) ? ( $v['last'] ?? null ) : null;
            $cat   = sanitize_key( is_array( $v ) ? ( $v['category-id'] ?? '' ) : '' );
            $cat_name = is_array( $v ) ? ( $v['category-name'] ?? '' ) : '';

            $visitor_price_incl = is_array( $v ) ? ( $v['price'] ?? null ) : null;

            if ( $visitor_price_incl !== null && is_numeric( $visitor_price_incl ) ) {
                $unit_price_incl = (float) $visitor_price_incl;
            } else {
                $unit_price_incl = $avg_unit_price_incl;
            }

            $unit_price_excl = null;
            if ( $unit_price_incl !== null && $effective_tax_rate !== null ) {
                $unit_price_excl = round( $unit_price_incl / ( 1 + $effective_tax_rate ), 6 );
            } else {
                $unit_price_excl = $avg_unit_price_excl;
            }

            $unit_tax = null;
            if ( $unit_price_incl !== null && $unit_price_excl !== null ) {
                $unit_tax = round( $unit_price_incl - $unit_price_excl, 6 );
            }

            $attendee_name = null;
            if ( is_string( $first ) || is_string( $last ) ) {
                $attendee_name = trim( trim( (string) $first ) . ' ' . trim( (string) $last ) );
                if ( $attendee_name === '' ) {
                    $attendee_name = null;
                }
            }

            $rows[] = [
                'post_id'        => (int) $tickets_for_id,
                'slot_start'     => (string) $slot_start,
                'slot_end'       => $slot_end,
                'unit_price'     => $unit_price_incl,
                'currency'       => $currency !== '' ? $currency : null,
                'price_category' => $cat . ':' . $cat_name,
                'attendee_name'  => $attendee_name,
                'meta_json'      => wp_json_encode( [
                    'visitor' => $v,
                    'price_incl_tax' => $unit_price_incl,
                    'price_excl_tax' => $unit_price_excl,
                    'unit_tax' => $unit_tax,
                    'effective_tax_rate' => $effective_tax_rate,
                    'line_total_excl' => $line_total_excl,
                    'line_tax' => $line_tax,
                    'line_total_incl' => $line_total_incl,
                    'currency' => $currency,
                ] ),
            ];
        }

        return $rows;
    }

    public static function issue_tickets_for_order_item( $order, $order_id, $item_id, $item ): void
    {

        $all_meta = [];
        foreach ( $item->get_meta_data() as $md ) {
            $d = $md->get_data();
            $all_meta[ (string) $d['key'] ] = $d['value'] ?? null;
        }

        $tickets_for_id = absint( $item->get_meta( 'tickets_for_id', true ) );
        $tickets_day    = (string) $item->get_meta( 'tickets_day', true );
        $tickets_time   = (string) $item->get_meta( 'tickets_time', true );
        $tickets_total  = (int) $item->get_meta( 'tickets_total', true );
        $visitors_json  = (string) $item->get_meta( 'tickets_visitors', true );

        // Not a tickets item.
        if ( ! $tickets_for_id || $tickets_day === '' || $tickets_time === '' ) {
            return;
        }

        // Idempotency
        if ( method_exists( 'IW_Tickets_DB', 'count_tickets_for_order_item' ) ) {
            $existing = (int) IW_Tickets_DB::count_tickets_for_order_item( (int) $item_id );
            if ( $existing > 0 ) {
                return;
            }
        }

        $rows = self::build_ticket_rows_from_order_item( $order, $item_id, $item );

        foreach ( $rows as $row ) {

            IW_Tickets_DB::insert_ticket( [
                'order_id'       => (int) $order_id,
                'order_item_id'  => (int) $item_id,
                'post_id'        => $row['post_id'],
                'slot_start'     => $row['slot_start'],
                'slot_end'       => $row['slot_end'],
                'unit_price'     => $row['unit_price'],
                'currency'       => $row['currency'],
                'ticket_type'    => 'tickets',
                'channel'        => (string) $item->get_meta( 'tickets_channel', true ),
                'price_category' => $row['price_category'],
                'attendee_name'  => $row['attendee_name'],
                'attendee_email' => $order->get_billing_email() ? (string) $order->get_billing_email() : null,
                'meta_json'      => $row['meta_json'],
            ] );
        }
    }

    public static function issue_tickets_for_order( $order_id, $transaction_id = null ): void
    {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        // Avoid issuing on failed/cancelled/refunded orders.
        $status = $order->get_status();
        if ( in_array( $status, [ 'failed', 'cancelled', 'refunded' ], true ) ) {
            return;
        }

        foreach ( $order->get_items() as $item_id => $item ) {
            self::issue_tickets_for_order_item( $order, $order_id, $item_id, $item );
        }
    }

}

IW_WC_Tickets_Cart_Manager::init();
