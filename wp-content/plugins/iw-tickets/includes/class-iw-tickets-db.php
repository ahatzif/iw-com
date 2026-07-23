<?php


class IW_Tickets_DB
{
    public static function activate()
    {
        global $wpdb;

        // 1) Create required tables for ticketing
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;
        $table_slot_inventory = $prefix . 'iw_ticket_slot_inventory';
        // Slot inventory = booked counters per post/date/time (fast availability lookups)
        $sql_slot_inventory = "CREATE TABLE {$table_slot_inventory} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            slot_date DATE NOT NULL,
            slot_time TIME NOT NULL,
            booked INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slot_unique (post_id, slot_date, slot_time),
            KEY post_id (post_id),
            KEY slot_date (slot_date)
        ) {$charset_collate};";
        dbDelta($sql_slot_inventory);

        $table_slot_holds = $prefix . 'iw_ticket_slot_holds';
        // Slot holds = temporary reservations (cart holds) with TTL
        $sql_slot_holds = "CREATE TABLE {$table_slot_holds} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            token VARCHAR(64) NOT NULL,
            order_item_id BIGINT(20) UNSIGNED NULL,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            slot_date DATE NOT NULL,
            slot_time TIME NOT NULL,
            qty INT(11) NOT NULL DEFAULT 0,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY hold_unique (token, post_id, slot_date, slot_time),
            KEY slot_lookup (post_id, slot_date, slot_time),
            KEY token (token),
            KEY order_item_id (order_item_id),
            KEY expires_at (expires_at)
        ) {$charset_collate};";
        dbDelta($sql_slot_holds);

        // 2) Issued tickets table (one row per ticket)
        $table_tickets = $prefix . 'iw_tickets';
        $sql_tickets = "CREATE TABLE {$table_tickets} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_uuid CHAR(36) NOT NULL,
            barcode_hash CHAR(64) NOT NULL,
            order_id BIGINT(20) UNSIGNED NULL,
            order_item_id BIGINT(20) UNSIGNED NULL,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            slot_start DATETIME NOT NULL,
            slot_end DATETIME NULL,
            ticket_type VARCHAR(64) NULL,
            price_category VARCHAR(64) NULL,
            unit_price DECIMAL(10,2) NULL,
            currency CHAR(3) NULL,
            channel VARCHAR(64) NULL,
            attendee_name VARCHAR(190) NULL,
            attendee_email VARCHAR(190) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'valid',
            issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            used_at DATETIME NULL,
            used_by_user_id BIGINT(20) UNSIGNED NULL,
            used_device_id VARCHAR(64) NULL,
            void_reason VARCHAR(255) NULL,
            meta_json LONGTEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY ticket_uuid (ticket_uuid),
            UNIQUE KEY barcode_hash (barcode_hash),
            KEY order_item_id (order_item_id),
            KEY order_slot_item (order_id, slot_start, order_item_id),
            KEY post_slot (post_id, slot_start),
            KEY status (status)
        ) {$charset_collate};";
        dbDelta( $sql_tickets );

        // 2b) Ticket wallet device registrations (Apple Wallet devices per ticket).
        // Tickets can be used by guests, so we cannot store device registrations in usermeta.
        $table_ticket_wallet_devices = $prefix . 'iw_ticket_wallet_devices';
        $sql_ticket_wallet_devices = "CREATE TABLE {$table_ticket_wallet_devices} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_uuid CHAR(36) NOT NULL,
            pass_type_identifier VARCHAR(255) NOT NULL,
            device_library_identifier VARCHAR(64) NOT NULL,
            push_token VARCHAR(255) NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_ticket_device (ticket_uuid, device_library_identifier),
            KEY ticket_uuid (ticket_uuid),
            KEY device_library_identifier (device_library_identifier),
            KEY pass_type_identifier (pass_type_identifier)
        ) {$charset_collate};";
        dbDelta( $sql_ticket_wallet_devices );

        // 3) Ticket scan logs (audit trail)
        $table_scans = $prefix . 'iw_ticket_scans';
        $sql_scans = "CREATE TABLE {$table_scans} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_id BIGINT(20) UNSIGNED NOT NULL,
            scan_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            result VARCHAR(32) NOT NULL,
            gate VARCHAR(64) NULL,
            user_id BIGINT(20) UNSIGNED NULL,
            device_id VARCHAR(64) NULL,
            note VARCHAR(255) NULL,
            meta_json LONGTEXT NULL,
            PRIMARY KEY (id),
            KEY ticket_id (ticket_id),
            KEY scan_time (scan_time),
            KEY result (result)
        ) {$charset_collate};";
        dbDelta( $sql_scans );

        // Schedule periodic cleanup for expired holds.
        self::ensure_cron_scheduled();
        if ( defined('IW_TICKETS_DB_VERSION') ) {
            update_option('iw_tickets_db_version', IW_TICKETS_DB_VERSION);
        }
    }
    public static function maybe_upgrade_db(): void
    {
        if ( ! defined('IW_TICKETS_DB_VERSION') ) {
            return;
        }

        $installed = get_option('iw_tickets_db_version');

        if ( $installed === IW_TICKETS_DB_VERSION ) {
            return;
        }

        // Run schema creation / upgrades via dbDelta
        self::activate();

        update_option('iw_tickets_db_version', IW_TICKETS_DB_VERSION);
    }


    /**
     * Ensure cron schedule + event exist.
     * Safe to call multiple times.
     */
    public static function ensure_cron_scheduled(): void
    {
        // Add custom schedule interval.
        add_filter( 'cron_schedules', [ __CLASS__, 'cron_schedules' ] );

        // Schedule event if missing.
        if ( ! wp_next_scheduled( 'iw_tickets_cleanup_expired_holds' ) ) {
            wp_schedule_event( time() + 60, 'iw_tickets_5min', 'iw_tickets_cleanup_expired_holds' );
        }
    }

    /**
     * Add custom intervals for WP-Cron.
     */
    public static function cron_schedules( array $schedules ): array
    {
        if ( ! isset( $schedules['iw_tickets_5min'] ) ) {
            $schedules['iw_tickets_5min'] = [
                'interval' => 5 * 60,
                'display'  => __( 'Every 5 minutes (IW Tickets)', 'iw-theme' ),
            ];
        }

        return $schedules;
    }

    /**
     * Unschedule cron event.
     */
    public static function unschedule_cron(): void
    {
        $timestamp = wp_next_scheduled( 'iw_tickets_cleanup_expired_holds' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'iw_tickets_cleanup_expired_holds' );
        }
    }

    /**
     * Get booked counts indexed by date+time for a post within a date range.
     * Returns: [ 'Y-m-d' => [ 'HH:MM:SS' => booked_int ] ]
     */
    public static function get_booked_map( int $post_id, string $date_from, string $date_to ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_inventory';

        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT slot_date, slot_time, booked FROM {$table} WHERE post_id = %d AND slot_date BETWEEN %s AND %s",  $post_id, $date_from, $date_to), ARRAY_A );

        $out = [];
        foreach ( $rows as $r ) {
            $d = $r['slot_date'];
            $t = $r['slot_time'];
            if ( ! isset( $out[$d] ) ) {
                $out[$d] = [];
            }
            $out[$d][$t] = (int) $r['booked'];
        }

        return $out;
    }

    /**
     * Atomically increment booked count for a slot.
     */
    public static function increment_booked( int $post_id, string $date, string $time, int $qty ): void {
        if ( $qty <= 0 ) return;
        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_inventory';

        // Ensure HH:MM:SS
        if ( preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
            $time .= ':00';
        }
        $wpdb->query( $wpdb->prepare( "INSERT INTO {$table} (post_id, slot_date, slot_time, booked, created_at, updated_at) VALUES (%d, %s, %s, %d, %s, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE booked = booked + VALUES(booked), updated_at = CURRENT_TIMESTAMP",  $post_id, $date, $time, $qty, current_time( 'mysql' ) ) );
    }

    /**
     * Atomically decrement booked count for a slot (never below zero).
     */
    public static function decrement_booked( int $post_id, string $date, string $time, int $qty ): void {
        if ( $qty <= 0 ) return;
        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_inventory';

        // Ensure HH:MM:SS
        if ( preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
            $time .= ':00';
        }

        $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET booked = GREATEST(booked - %d, 0), updated_at = CURRENT_TIMESTAMP WHERE post_id = %d AND slot_date = %s AND slot_time = %s", $qty, $post_id,  $date,  $time ) );
    }
    /**
     * Get reserved (held) quantities indexed by date+time for a post within a date range.
     * Only counts non-expired holds.
     * Returns: [ 'Y-m-d' => [ 'HH:MM:SS' => reserved_int ] ]
     */
    public static function get_reserved_map( int $post_id, string $date_from, string $date_to ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_holds';

        $now = current_time( 'mysql' );

        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT slot_date, slot_time, SUM(qty) AS reserved FROM {$table} WHERE post_id = %d AND slot_date BETWEEN %s AND %s AND expires_at > %s GROUP BY slot_date, slot_time",  $post_id, $date_from, $date_to, $now ),  ARRAY_A );

        $out = [];
        foreach ( $rows as $r ) {
            $d = $r['slot_date'];
            $t = $r['slot_time'];
            if ( ! isset( $out[ $d ] ) ) {
                $out[ $d ] = [];
            }
            $out[ $d ][ $t ] = (int) $r['reserved'];
        }

        return $out;
    }

    /**
     * Create or update a hold for a token (cart/session).
     * If qty <= 0, the hold row is deleted.
     * Supports optional order_item_id.
     */
    public static function upsert_hold( string $token, int $post_id, string $date, string $time, int $qty, ?int $ttl_minutes = null, ?int $order_item_id = null ): void {
        $token = trim( $token );
        if ( $token === '' || $post_id <= 0 ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_holds';

        // Ensure HH:MM:SS
        if ( preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
            $time .= ':00';
        }

        if ( $qty <= 0 ) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table} WHERE token = %s AND post_id = %d AND slot_date = %s AND slot_time = %s",
                    $token,
                    $post_id,
                    $date,
                    $time
                )
            );
            return;
        }
        // If TTL not provided, use the configured option (defaults to 15 minutes).
        if ( $ttl_minutes === null ) {
            $ttl_minutes = ( class_exists( 'IW_Ticketing' ) && method_exists( 'IW_Ticketing', 'get_slot_hold_minutes' ) )
                ? IW_Ticketing::get_slot_hold_minutes()
                : 15;
        }
        $ttl_minutes = (int) $ttl_minutes;
        if ( $ttl_minutes < 1 ) {
            $ttl_minutes = 1;
        } elseif ( $ttl_minutes > 1440 ) {
            $ttl_minutes = 1440;
        }
        $now_ts = (int) current_time( 'timestamp' );
        $expires_at = date( 'Y-m-d H:i:s', $now_ts + ( $ttl_minutes * 60 ) );

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table} (token, order_item_id, post_id, slot_date, slot_time, qty, expires_at, created_at, updated_at)
                 VALUES (%s, %d, %d, %s, %s, %d, %s, %s, CURRENT_TIMESTAMP)
                 ON DUPLICATE KEY UPDATE order_item_id = VALUES(order_item_id), qty = VALUES(qty), expires_at = VALUES(expires_at), updated_at = CURRENT_TIMESTAMP",
                $token,
                $order_item_id,
                $post_id,
                $date,
                $time,
                $qty,
                $expires_at,
                current_time( 'mysql' )
            )
        );
    }

    /**
     * Extend TTL for all active holds of a token.
     */
    public static function refresh_holds_ttl( string $token, ?int $ttl_minutes = null ): void {
        $token = trim( $token );
        if ( $token === '' ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_holds';
        // If TTL not provided, use the configured option (defaults to 15 minutes).
        if ( $ttl_minutes === null ) {
            $ttl_minutes = ( class_exists( 'IW_Ticketing' ) && method_exists( 'IW_Ticketing', 'get_slot_hold_minutes' ) )
                ? IW_Ticketing::get_slot_hold_minutes()
                : 15;
        }
        $ttl_minutes = (int) $ttl_minutes;
        if ( $ttl_minutes < 1 ) {
            $ttl_minutes = 1;
        } elseif ( $ttl_minutes > 1440 ) {
            $ttl_minutes = 1440;
        }
        $now_ts = (int) current_time( 'timestamp' );
        $expires_at = date( 'Y-m-d H:i:s', $now_ts + ( $ttl_minutes * 60 ) );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET expires_at = %s, updated_at = CURRENT_TIMESTAMP WHERE token = %s AND expires_at > %s",
                $expires_at,
                $token,
                current_time( 'mysql' )
            )
        );
    }

    /**
    * Convert normal cart holds (15min) into reservation holds.
    * Expiration becomes: slot_datetime - confirmation_days_before.
    * Used when a user chooses "reserve" instead of immediate payment.
    */
    public static function convert_holds_to_reservation( string $token ): void {
        $token = trim( $token );
        if ( $token === '' ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_holds';

        $days_before = (int) apply_filters( 'iw_learning_program_confirmation_days_before',  2);

        if ( $days_before < 0 ) {
            $days_before = 0;
        }

        // Update expires_at based on slot_date + slot_time
        // expires_at = slot_datetime - confirmation_days_before
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table}
                 SET expires_at = DATE_SUB(CONCAT(slot_date,' ',slot_time), INTERVAL %d DAY),
                     updated_at = CURRENT_TIMESTAMP
                 WHERE token = %s",
                $days_before,
                $token
            )
        );
    }

    /**
     * Attach a WooCommerce order_item_id to existing slot holds.
     * This is used during checkout when the order item is created
     * so we can later relate reservations/tickets to the order item.
     */
    public static function attach_order_item_to_holds( string $token, int $order_item_id, int $post_id, string $date, string $time ): void {
        $token = trim( $token );
        if ( $token === '' || $order_item_id <= 0 || $post_id <= 0 ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_holds';

        // Normalize time to HH:MM:SS
        if ( preg_match('/^\\d{2}:\\d{2}$/', $time) ) {
            $time .= ':00';
        }

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table}
                 SET order_item_id = %d, updated_at = CURRENT_TIMESTAMP
                 WHERE token = %s
                 AND post_id = %d
                 AND slot_date = %s
                 AND slot_time = %s",
                $order_item_id,
                $token,
                $post_id,
                $date,
                $time
            )
        );
    }

    /**

    /**
     * Release holds for a token. Optionally filter by post/date/time.
     */
    public static function release_holds( string $token, ?int $post_id = null, ?string $date = null, ?string $time = null ): void {
        $token = trim( $token );
        if ( $token === '' ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_holds';

        $where = [ 'token = %s' ];
        $args  = [ $token ];

        if ( $post_id !== null ) {
            $where[] = 'post_id = %d';
            $args[]  = (int) $post_id;
        }
        if ( $date !== null ) {
            $where[] = 'slot_date = %s';
            $args[]  = $date;
        }
        if ( $time !== null ) {
            if ( preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
                $time .= ':00';
            }
            $where[] = 'slot_time = %s';
            $args[]  = $time;
        }

        $sql = "DELETE FROM {$table} WHERE " . implode( ' AND ', $where );
        $wpdb->query( $wpdb->prepare( $sql, $args ) );
    }

    /**
     * Release only cart-stage holds for a token.
     *
     * Once checkout has attached an order_item_id, the hold represents a real
     * reservation and must survive WooCommerce cart cleanup.
     */
    public static function release_unattached_holds( string $token, ?int $post_id = null, ?string $date = null, ?string $time = null ): void {
        $token = trim( $token );
        if ( $token === '' ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_holds';

        $where = [ 'token = %s', '(order_item_id IS NULL OR order_item_id = 0)' ];
        $args  = [ $token ];

        if ( $post_id !== null ) {
            $where[] = 'post_id = %d';
            $args[]  = (int) $post_id;
        }
        if ( $date !== null ) {
            $where[] = 'slot_date = %s';
            $args[]  = $date;
        }
        if ( $time !== null ) {
            if ( preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
                $time .= ':00';
            }
            $where[] = 'slot_time = %s';
            $args[]  = $time;
        }

        $sql = "DELETE FROM {$table} WHERE " . implode( ' AND ', $where );
        $wpdb->query( $wpdb->prepare( $sql, $args ) );
    }

    /**
     * Release holds associated with a specific WooCommerce order_item_id.
     * This is used when a reservation item is cancelled.
     * Pending reservations are stored only as holds, so cancelling them only
     * removes the reserved hold rows. Booked inventory is touched on confirmation.
     */
    public static function release_order_item_holds( int $order_item_id ): void {
        $order_item_id = (int) $order_item_id;

        if ( $order_item_id <= 0 ) {
            return;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'iw_ticket_slot_holds';

        $wpdb->query( $wpdb->prepare("DELETE FROM {$table} WHERE order_item_id = %d", $order_item_id ));
    }

    public static function order_item_has_active_holds( int $order_item_id ): bool {
        $order_item_id = (int) $order_item_id;

        if ( $order_item_id <= 0 ) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_holds';

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(1) FROM {$table} WHERE order_item_id = %d AND expires_at > %s",
                $order_item_id,
                current_time( 'mysql' )
            )
        );

        return (int) $count > 0;
    }

    /**
     * Convert the active reservation holds of one Woo order item into booked inventory.
     * Used when a teacher confirms one reserved learning-program item from My Account.
     */
    public static function consume_order_item_holds_to_booked( int $order_item_id ): void {
        $order_item_id = (int) $order_item_id;

        if ( $order_item_id <= 0 ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_holds';
        $inventory_table = $wpdb->prefix . 'iw_ticket_slot_inventory';
        $now = current_time( 'mysql' );

        $wpdb->query('START TRANSACTION');

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, slot_date, slot_time, SUM(qty) AS qty
                 FROM {$table}
                 WHERE order_item_id = %d AND expires_at > %s
                 GROUP BY post_id, slot_date, slot_time",
                $order_item_id,
                $now
            ),
            ARRAY_A
        );

        foreach ( $rows as $r ) {
            $post_id = (int) $r['post_id'];
            $date    = (string) $r['slot_date'];
            $time    = (string) $r['slot_time'];
            $qty     = (int) $r['qty'];

            if ( $post_id <= 0 || $qty <= 0 ) {
                continue;
            }

            $normalized_time = preg_match('/^\d{2}:\d{2}$/', $time) ? $time . ':00' : $time;

            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$inventory_table} (post_id, slot_date, slot_time, booked, created_at)
                     VALUES (%d, %s, %s, 0, %s)
                     ON DUPLICATE KEY UPDATE post_id = post_id",
                    $post_id,
                    $date,
                    $normalized_time,
                    current_time('mysql')
                )
            );

            $wpdb->query(
                $wpdb->prepare(
                    "SELECT id FROM {$inventory_table}
                     WHERE post_id = %d AND slot_date = %s AND slot_time = %s
                     FOR UPDATE",
                    $post_id,
                    $date,
                    $normalized_time
                )
            );

            self::increment_booked( $post_id, $date, $time, $qty );
        }

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE order_item_id = %d",
                $order_item_id
            )
        );

        $wpdb->query('COMMIT');
    }


    /**
     * Delete expired holds.
     * Returns number of rows deleted.
     */
    public static function cleanup_expired_holds(): int {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_holds';
        $now = current_time( 'mysql' );

        $expired_order_items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT h.order_item_id, oi.order_id
                 FROM {$table} h
                 LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = h.order_item_id
                 WHERE h.expires_at <= %s AND h.order_item_id IS NOT NULL AND h.order_item_id > 0",
                $now
            ),
            ARRAY_A
        );

        $orders_to_reconcile = [];

        foreach ( (array) $expired_order_items as $row ) {
            $order_item_id = isset( $row['order_item_id'] ) ? (int) $row['order_item_id'] : 0;
            $order_id = isset( $row['order_id'] ) ? (int) $row['order_id'] : 0;

            if ( $order_item_id <= 0 ) {
                continue;
            }

            if (
                function_exists( 'wc_get_order_item_meta' )
                && ! wc_get_order_item_meta( $order_item_id, '_reservation_confirmed', true )
                && ! wc_get_order_item_meta( $order_item_id, '_reservation_cancelled', true )
            ) {
                wc_update_order_item_meta( $order_item_id, '_reservation_expired', 1 );
                wc_update_order_item_meta( $order_item_id, '_reservation_expired_at', $now );
            }

            if ( $order_id > 0 ) {
                $orders_to_reconcile[ $order_id ] = true;
            }
        }

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE expires_at <= %s", $now ) );
        $deleted = (int) $wpdb->rows_affected;

        if ( class_exists( 'IW_Ticketing' ) && method_exists( 'IW_Ticketing', 'reconcile_reservation_order_status' ) && function_exists( 'wc_get_order' ) ) {
            foreach ( array_keys( $orders_to_reconcile ) as $order_id ) {
                IW_Ticketing::reconcile_reservation_order_status( wc_get_order( (int) $order_id ) );
            }
        }

        return $deleted;
    }

    /**
     * Consume (convert) all non-expired holds of a token into booked counts, then delete them.
     * This should be called on successful payment.
     */
    public static function consume_holds_to_booked( string $token ): void {
        $token = trim( $token );
        if ( $token === '' ) {
            return;
        }

        global $wpdb;
        // Start transaction to avoid race conditions when multiple checkouts happen simultaneously
        $wpdb->query('START TRANSACTION');
        $table = $wpdb->prefix . 'iw_ticket_slot_holds';
        $now = current_time( 'mysql' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, slot_date, slot_time, SUM(qty) AS qty
                 FROM {$table}
                 WHERE token = %s AND expires_at > %s
                 GROUP BY post_id, slot_date, slot_time",
                $token,
                $now
            ),
            ARRAY_A
        );

        foreach ( $rows as $r ) {
            $post_id = (int) $r['post_id'];
            $date    = (string) $r['slot_date'];
            $time    = (string) $r['slot_time'];
            $qty     = (int) $r['qty'];

            if ( $post_id > 0 && $qty > 0 ) {
                // Ensure slot inventory row exists before locking (important for FOR UPDATE)
                $inventory_table = $wpdb->prefix . 'iw_ticket_slot_inventory';
                $normalized_time = preg_match('/^\d{2}:\d{2}$/', $time) ? $time . ':00' : $time;

                $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO {$inventory_table} (post_id, slot_date, slot_time, booked, created_at)
                         VALUES (%d, %s, %s, 0, %s)
                         ON DUPLICATE KEY UPDATE post_id = post_id",
                        $post_id,
                        $date,
                        $normalized_time,
                        current_time('mysql')
                    )
                );
                // Lock the slot row to prevent concurrent updates (atomic booking)
                $wpdb->query(
                    $wpdb->prepare(
                        "SELECT id FROM {$inventory_table}
                         WHERE post_id = %d AND slot_date = %s AND slot_time = %s
                         FOR UPDATE",
                        $post_id,
                        $date,
                        $normalized_time
                    )
                );
                self::increment_booked( $post_id, $date, $time, $qty );
            }
        }

        // Remove all holds for the token (expired or not)
        self::release_holds( $token );
        // Commit atomic booking transaction
        $wpdb->query('COMMIT');
    }

    /**
     * Get hold info for the current WC session token + cart item ticket slot.
     * Returns an object with: token, post_id, slot_date, slot_time, qty, created_at, expires_at.
     */
    public static function get_hold_info( array $cart_item ): ?object
    {
        if ( empty( $cart_item['tickets_for_id'] ) || empty( $cart_item['tickets_day'] ) || empty( $cart_item['tickets_time'] ) ) {
            return null;
        }

        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return null;
        }

        $token = (string) WC()->session->get( 'iw_ticket_hold_token', '' );
        if ( $token === '' ) {
            return null;
        }

        $post_id = (int) $cart_item['tickets_for_id'];
        $slot_date = date( 'Y-m-d', strtotime( (string) $cart_item['tickets_day'] ) );

        $slot_time_raw = (string) $cart_item['tickets_time'];
        if ( preg_match( '/^\d{2}:\d{2}$/', $slot_time_raw ) ) {
            $slot_time_raw .= ':00';
        }
        $slot_time = date( 'H:i:s', strtotime( $slot_time_raw ) );

        if ( $post_id <= 0 || $slot_date === '' || $slot_time === '' ) {
            return null;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_slot_holds';

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT token, post_id, slot_date, slot_time, qty, created_at, expires_at
                 FROM {$table}
                 WHERE token = %s AND post_id = %d AND slot_date = %s AND slot_time = %s
                 LIMIT 1",
                $token,
                $post_id,
                $slot_date,
                $slot_time
            )
        );

        return $row ?: null;
    }

    /**
     * Backward-compatible helper: return only the expires_at string for a cart item.
     */
    public static function get_hold_expires_at( array $cart_item ): ?string
    {
        $info = self::get_hold_info( $cart_item );
        return ( $info && ! empty( $info->expires_at ) ) ? (string) $info->expires_at : null;
    }

    /**
     * Generate a v4-like UUID without external deps.
     */
    public static function generate_uuid(): string
    {
        $data = random_bytes( 16 );
        // Set version to 0100
        $data[6] = chr( ( ord( $data[6] ) & 0x0f ) | 0x40 );
        // Set bits 6-7 to 10
        $data[8] = chr( ( ord( $data[8] ) & 0x3f ) | 0x80 );

        $hex = bin2hex( $data );
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr( $hex, 0, 8 ),
            substr( $hex, 8, 4 ),
            substr( $hex, 12, 4 ),
            substr( $hex, 16, 4 ),
            substr( $hex, 20, 12 )
        );
    }

    /**
     * Create a single issued ticket row.
     * Returns inserted ticket ID, or 0 on failure.
     */
    public static function insert_ticket( array $data ): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_tickets';

        $defaults = [
            'ticket_uuid'      => self::generate_uuid(),
            'barcode_hash'     => '',
            'order_id'         => null,
            'order_item_id'    => null,
            'post_id'          => 0,
            'slot_start'       => '',
            'slot_end'         => null,
            'ticket_type'      => null,
            'price_category'   => null,
            'unit_price'       => null,
            'currency'         => null,
            'channel'          => null,
            'attendee_name'    => null,
            'attendee_email'   => null,
            'status'           => 'valid',
            'issued_at'        => current_time( 'mysql' ),
            'used_at'          => null,
            'used_by_user_id'  => null,
            'used_device_id'   => null,
            'void_reason'      => null,
            'meta_json'        => null,
        ];

        $row = array_merge( $defaults, $data );

        if ( empty( $row['barcode_hash'] ) ) {
            // Default barcode hash = sha256(uuid)
            $row['barcode_hash'] = hash( 'sha256', (string) $row['ticket_uuid'] );
        }

        $post_id = (int) $row['post_id'];
        if ( $post_id <= 0 || empty( $row['slot_start'] ) ) {
            return 0;
        }

        $data_to_insert = [
            'ticket_uuid'      => (string) $row['ticket_uuid'],
            'barcode_hash'     => (string) $row['barcode_hash'],
            'order_id'         => $row['order_id'] !== null ? (int) $row['order_id'] : null,
            'order_item_id'    => $row['order_item_id'] !== null ? (int) $row['order_item_id'] : null,
            'post_id'          => $post_id,
            'slot_start'       => (string) $row['slot_start'],
            'slot_end'         => $row['slot_end'] !== null ? (string) $row['slot_end'] : null,
            'ticket_type'      => $row['ticket_type'] !== null ? (string) $row['ticket_type'] : null,
            'price_category'   => $row['price_category'] !== null ? (string) $row['price_category'] : null,
            'unit_price'       => $row['unit_price'] !== null ? (float) $row['unit_price'] : null,
            'currency'         => $row['currency'] !== null ? (string) $row['currency'] : null,
            'channel'          => $row['channel'] !== null ? (string) $row['channel'] : 'online',
            'attendee_name'    => $row['attendee_name'] !== null ? (string) $row['attendee_name'] : null,
            'attendee_email'   => $row['attendee_email'] !== null ? (string) $row['attendee_email'] : null,
            'status'           => (string) $row['status'],
            'issued_at'        => (string) $row['issued_at'],
            'used_at'          => $row['used_at'] !== null ? (string) $row['used_at'] : null,
            'used_by_user_id'  => $row['used_by_user_id'] !== null ? (int) $row['used_by_user_id'] : null,
            'used_device_id'   => $row['used_device_id'] !== null ? (string) $row['used_device_id'] : null,
            'void_reason'      => $row['void_reason'] !== null ? (string) $row['void_reason'] : null,
            'meta_json'        => $row['meta_json'] !== null ? (string) $row['meta_json'] : null,
        ];
        $format = [
            '%s', // ticket_uuid
            '%s', // barcode_hash
            '%d', // order_id
            '%d', // order_item_id
            '%d', // post_id
            '%s', // slot_start
            '%s', // slot_end
            '%s', // ticket_type
            '%s', // price_category
            '%f', // unit_price
            '%s', // currency
            '%s', // channel
            '%s', // attendee_name
            '%s', // attendee_email
            '%s', // status
            '%s', // issued_at
            '%s', // used_at
            '%d', // used_by_user_id
            '%s', // used_device_id
            '%s', // void_reason
            '%s', // meta_json
        ];

        $ok = $wpdb->insert( $table, $data_to_insert, $format );
        return $ok ? (int) $wpdb->insert_id : 0;
    }

    /**
     * Fetch a ticket row by UUID.
     */
    public static function get_ticket_by_uuid( string $ticket_uuid ): ?array
    {
        $ticket_uuid = trim( $ticket_uuid );
        if ( $ticket_uuid === '' ) {
            return null;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_tickets';

        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE ticket_uuid = %s LIMIT 1", $ticket_uuid ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Query iw_tickets by barcode_hash.
     */
    public static function get_ticket_by_barcode_hash( string $barcode_hash ): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_tickets';

        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE barcode_hash = %s LIMIT 1", $barcode_hash ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Mark a ticket as used and write scan log entry.
     * Returns scan result string.
     */
    public static function scan_ticket( string $ticket_uuid, array $context = [] ): string
    {
        $ticket = self::get_ticket_by_uuid( $ticket_uuid );
        if ( ! $ticket ) {
            self::insert_scan_log( 0, [
                'result'   => 'invalid',
                'gate'     => $context['gate'] ?? null,
                'user_id'  => $context['user_id'] ?? null,
                'device_id'=> $context['device_id'] ?? null,
                'note'     => 'Ticket not found',
            ] );
            return 'invalid';
        }

        $status = (string) ( $ticket['status'] ?? '' );
        $ticket_id = (int) ( $ticket['id'] ?? 0 );

        if ( $status === 'used' ) {
            self::insert_scan_log( $ticket_id, [
                'result'   => 'already_used',
                'gate'     => $context['gate'] ?? null,
                'user_id'  => $context['user_id'] ?? null,
                'device_id'=> $context['device_id'] ?? null,
            ] );
            return 'already_used';
        }

        // Backward-compat: older installs may have status stored as 0/''/NULL for "valid".
        $is_valid = ( $status === 'valid' || $status === '' || $status === '0' || $status === 'NULL' );

        if ( ! $is_valid ) {
            self::insert_scan_log( $ticket_id, [
                'result'   => 'not_valid',
                'gate'     => $context['gate'] ?? null,
                'user_id'  => $context['user_id'] ?? null,
                'device_id'=> $context['device_id'] ?? null,
                'note'     => 'Status: ' . $status,
            ] );
            return 'not_valid';
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_tickets';

        $now = current_time( 'mysql' );
        $user_id = isset( $context['user_id'] ) ? (int) $context['user_id'] : null;
        $device_id = isset( $context['device_id'] ) ? (string) $context['device_id'] : null;

        // Atomic update: only if still valid or legacy valid.
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET status = 'used', used_at = %s, used_by_user_id = %s, used_device_id = %s
                 WHERE id = %d AND (status = 'valid' OR status = '0' OR status = '' OR status IS NULL)",
                $now,
                $user_id,
                $device_id,
                $ticket_id
            )
        );

        if ( (int) $updated !== 1 ) {
            self::insert_scan_log( $ticket_id, [
                'result'   => 'race_condition',
                'gate'     => $context['gate'] ?? null,
                'user_id'  => $user_id,
                'device_id'=> $device_id,
            ] );
            return 'already_used';
        }

        self::insert_scan_log( $ticket_id, [
            'result'   => 'ok',
            'gate'     => $context['gate'] ?? null,
            'user_id'  => $user_id,
            'device_id'=> $device_id,
        ] );

        return 'ok';
    }

    /**
     * Undo a same-day scanner consumption and write a scan log entry.
     */
    public static function reset_ticket_scan( string $ticket_uuid, array $context = [] ): string
    {
        $ticket = self::get_ticket_by_uuid( $ticket_uuid );
        if ( ! $ticket ) {
            self::insert_scan_log( 0, [
                'result'   => 'invalid',
                'gate'     => $context['gate'] ?? null,
                'user_id'  => $context['user_id'] ?? null,
                'device_id'=> $context['device_id'] ?? null,
                'note'     => 'Ticket not found for reset',
            ] );
            return 'invalid';
        }

        $status = (string) ( $ticket['status'] ?? '' );
        $ticket_id = (int) ( $ticket['id'] ?? 0 );

        if ( $status !== 'used' ) {
            self::insert_scan_log( $ticket_id, [
                'result'   => 'reset_not_allowed',
                'gate'     => $context['gate'] ?? null,
                'user_id'  => $context['user_id'] ?? null,
                'device_id'=> $context['device_id'] ?? null,
                'note'     => 'Ticket status is not used',
            ] );
            return 'reset_not_allowed';
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_tickets';
        $user_id = isset( $context['user_id'] ) ? (int) $context['user_id'] : null;
        $device_id = isset( $context['device_id'] ) ? (string) $context['device_id'] : null;

        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET status = 'valid', used_at = NULL, used_by_user_id = NULL, used_device_id = NULL
                 WHERE id = %d AND status = 'used'",
                $ticket_id
            )
        );

        if ( (int) $updated !== 1 ) {
            self::insert_scan_log( $ticket_id, [
                'result'   => 'reset_failed',
                'gate'     => $context['gate'] ?? null,
                'user_id'  => $user_id,
                'device_id'=> $device_id,
            ] );
            return 'reset_failed';
        }

        self::insert_scan_log( $ticket_id, [
            'result'   => 'reset',
            'gate'     => $context['gate'] ?? null,
            'user_id'  => $user_id,
            'device_id'=> $device_id,
        ] );

        return 'reset';
    }

    /**
     * Insert a scan log row.
     */
    public static function insert_scan_log( int $ticket_id, array $data ): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_scans';

        $defaults = [
            'ticket_id' => $ticket_id,
            'scan_time' => current_time( 'mysql' ),
            'result'    => 'ok',
            'gate'      => null,
            'user_id'   => null,
            'device_id' => null,
            'note'      => null,
            'meta_json' => null,
        ];

        $row = array_merge( $defaults, $data );

        $ok = $wpdb->insert( $table, [
            'ticket_id' => (int) $row['ticket_id'],
            'scan_time' => (string) $row['scan_time'],
            'result'    => (string) $row['result'],
            'gate'      => $row['gate'] !== null ? (string) $row['gate'] : null,
            'user_id'   => $row['user_id'] !== null ? (int) $row['user_id'] : null,
            'device_id' => $row['device_id'] !== null ? (string) $row['device_id'] : null,
            'note'      => $row['note'] !== null ? (string) $row['note'] : null,
            'meta_json' => $row['meta_json'] !== null ? (string) $row['meta_json'] : null,
        ], [ '%d','%s','%s','%s','%d','%s','%s','%s' ] );

        return $ok ? (int) $wpdb->insert_id : 0;
    }

    /**
     * Idempotency helper: how many issued tickets exist for a Woo order item.
     */
    public static function count_tickets_for_order_item( int $order_item_id ): int
    {
        $order_item_id = (int) $order_item_id;
        if ( $order_item_id <= 0 ) {
            return 0;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_tickets';

        $cnt = $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(1) FROM {$table} WHERE order_item_id = %d", $order_item_id )
        );

        return (int) $cnt;
    }


    /*
     * -------------------------------------------------------------------------
     * Ticket Wallet Device Registrations (Apple Wallet)
     * -------------------------------------------------------------------------
     */

    /**
     * Insert or update a device registration for a given ticket.
     * Returns affected rows (1 for insert, 2 for update in some MySQL configs), or 0 on failure.
     */
    public static function upsert_wallet_device_registration( string $ticket_uuid, string $pass_type_identifier, string $device_library_identifier, string $push_token ): int
    {
        $ticket_uuid = trim( $ticket_uuid );
        $pass_type_identifier = trim( $pass_type_identifier );
        $device_library_identifier = trim( $device_library_identifier );
        $push_token = trim( $push_token );

        if ( $ticket_uuid === '' || $pass_type_identifier === '' || $device_library_identifier === '' || $push_token === '' ) {
            return 0;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_wallet_devices';

        $sql = "INSERT INTO {$table} (ticket_uuid, pass_type_identifier, device_library_identifier, push_token, updated_at)
                VALUES (%s, %s, %s, %s, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE pass_type_identifier = VALUES(pass_type_identifier), push_token = VALUES(push_token), updated_at = CURRENT_TIMESTAMP";
        $res = $wpdb->query( $wpdb->prepare( $sql, $ticket_uuid, $pass_type_identifier, $device_library_identifier, $push_token ) );
        return is_numeric( $res ) ? (int) $res : 0;
    }

    /**
     * Delete a device registration.
     * If $pass_type_identifier is provided, it will be included in the WHERE clause.
     * Returns number of rows deleted.
     */
    public static function delete_wallet_device_registration( string $ticket_uuid, string $device_library_identifier, ?string $pass_type_identifier = null ): int
    {
        global $wpdb;
        $ticket_uuid = trim( $ticket_uuid );
        $device_library_identifier = trim( $device_library_identifier );
        $pass_type_identifier = $pass_type_identifier !== null ? trim( $pass_type_identifier ) : null;
        if ( $ticket_uuid === '' || $device_library_identifier === '' ) return 0;
        $table = $wpdb->prefix . 'iw_ticket_wallet_devices';
        if ( $pass_type_identifier !== null && $pass_type_identifier !== '' ) {
            $wpdb->query( $wpdb->prepare("DELETE FROM {$table} WHERE ticket_uuid = %s AND device_library_identifier = %s AND pass_type_identifier = %s", $ticket_uuid, $device_library_identifier, $pass_type_identifier) );
        } else {
            $wpdb->query( $wpdb->prepare("DELETE FROM {$table} WHERE ticket_uuid = %s AND device_library_identifier = %s", $ticket_uuid, $device_library_identifier ) );
        }

        return (int) $wpdb->rows_affected;
    }

    /**
     * Get all registered devices for a ticket UUID.
     * Returns rows with keys: device_library_identifier, push_token, pass_type_identifier, updated_at.
     */
    public static function get_wallet_devices_for_ticket( string $ticket_uuid ): array
    {
        $ticket_uuid = trim( $ticket_uuid );
        if ( $ticket_uuid === '' ) return [];
        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_wallet_devices';
        $rows = $wpdb->get_results( $wpdb->prepare("SELECT device_library_identifier, push_token, pass_type_identifier, updated_at FROM {$table} WHERE ticket_uuid = %s", $ticket_uuid ), ARRAY_A );
        return is_array( $rows ) ? $rows : [];
    }

    /**
     * Apple endpoint helper: return ticket UUIDs registered to a device since a given timestamp.
     * $since_ts is expected to be a UNIX timestamp (seconds).
     */
    public static function get_ticket_uuids_for_device_since( string $device_library_identifier, string $pass_type_identifier, int $since_ts = 0 ): array
    {
        $device_library_identifier = trim( $device_library_identifier );
        $pass_type_identifier = trim( $pass_type_identifier );

        if ( $device_library_identifier === '' || $pass_type_identifier === '' ) {
            return [];
        }

        $since_ts = max( 0, (int) $since_ts );

        global $wpdb;
        $table = $wpdb->prefix . 'iw_ticket_wallet_devices';

        if ( $since_ts > 0 ) {
            // Compare DATETIME column against unix timestamp.
            $since_mysql = gmdate( 'Y-m-d H:i:s', $since_ts );
            $rows = $wpdb->get_col( $wpdb->prepare( "SELECT ticket_uuid FROM {$table} WHERE device_library_identifier = %s AND pass_type_identifier = %s AND updated_at > %s",  $device_library_identifier, $pass_type_identifier, $since_mysql ) );
        } else {
            $rows = $wpdb->get_col( $wpdb->prepare( "SELECT ticket_uuid FROM {$table} WHERE device_library_identifier = %s AND pass_type_identifier = %s", $device_library_identifier, $pass_type_identifier ) );
        }

        if ( ! is_array( $rows ) ) return [];

        $out = [];
        foreach ( $rows as $uuid ) {
            $uuid = trim( (string) $uuid );
            if ( $uuid !== '' ) {
                $out[ $uuid ] = true;
            }
        }

        return array_keys( $out );
    }


}

// Register cron schedules and event on every request (cheap and safe).
add_action( 'init', [ 'IW_Tickets_DB', 'ensure_cron_scheduled' ] );
add_action( 'admin_init', [ 'IW_Tickets_DB', 'maybe_upgrade_db' ] );

// Cron hook: delete expired holds.
add_action( 'iw_tickets_cleanup_expired_holds', [ 'IW_Tickets_DB', 'cleanup_expired_holds' ] );
