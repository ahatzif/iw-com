<?php
require_once 'inc/theme.php';

function com_theme_option( string $name, $default = null ) {
    if ( function_exists( 'get_field' ) ) {
        $value = get_field( $name, 'option' );

        if ( $value !== null && $value !== false && $value !== '' ) {
            return $value;
        }
    }

    return $default;
}

function com_theme_field_value( string $name, $context = null, $default = null ) {
    if ( ! function_exists( 'get_field' ) ) {
        return $default;
    }

    if ( $context === null ) {
        $context = is_tax() ? get_queried_object() : get_queried_object_id();
    }

    $value = get_field( $name, $context );

    return ( $value !== null && $value !== false && $value !== '' ) ? $value : $default;
}

function com_theme_color_class( string $prefix, ?string $color, string $default = '' ): string {
    $color = trim( (string) $color );

    if ( $color === '' || $color === 'default' ) {
        $color = $default;
    }

    if ( $color === '' ) {
        return '';
    }

    if ( str_starts_with( $color, $prefix . '-' ) ) {
        return sanitize_html_class( $color );
    }

    return sanitize_html_class( $prefix . '-' . $color );
}

function com_theme_block_style_classes( string $default_colors = 'text-blue', array $default_spacings = [ 'desktop' => [ 'mt' => 'normal', 'mb' => 'normal' ] ] ): string {
    $block_colors = apply_filters( 'theme_block_colors', $default_colors );
    $block_spacings = apply_filters( 'theme_block_spacings', $default_spacings );

    return trim( implode( ' ', array_filter( [
        is_string( $block_colors ) ? $block_colors : '',
        is_string( $block_spacings ) ? $block_spacings : '',
    ] ) ) );
}

function com_theme_block_wrapper_classes( string $default = 'page-wrapper' ): string {
    return trim( (string) apply_filters( 'theme_block_wrapper', $default ) );
}

function com_theme_page_background( $context = null, string $default = 'ochre' ): string {
    if ( com_theme_is_purchase_flow() ) {
        return 'blue';
    }

    return (string) com_theme_field_value( 'page_background', $context, $default );
}

function com_theme_page_background_class( $context = null, string $default = 'ochre' ): string {
    return com_theme_color_class( 'bg', com_theme_page_background( $context, $default ), $default );
}

function com_theme_page_text_class( $context = null ): string {
    if ( com_theme_is_purchase_flow() ) {
        return 'text-white';
    }

    return com_theme_field_value( 'white_header', $context, false ) ? 'text-white' : 'text-blue';
}

function com_theme_header_theme( $context = null, string $default = 'blue' ): string {
    if ( com_theme_is_purchase_flow() ) {
        return 'light';
    }

    return com_theme_field_value( 'white_header', $context, false ) ? 'light' : $default;
}

function com_theme_is_purchase_flow(): bool {
    if ( ! function_exists( 'is_cart' ) || ! function_exists( 'is_checkout' ) ) {
        return false;
    }

    return is_cart() || is_checkout();
}

function com_theme_hide_breadcrumb( $context = null ): bool {
    return (bool) com_theme_field_value( 'hide_breadcrumb', $context, false );
}

function com_theme_link_url( $value ): string {
    if ( $value instanceof WP_Post ) {
        return get_permalink( $value );
    }

    if ( is_numeric( $value ) ) {
        $url = get_permalink( (int) $value );
        return $url ?: '';
    }

    if ( is_array( $value ) && ! empty( $value['url'] ) ) {
        return (string) $value['url'];
    }

    if ( is_string( $value ) && $value !== '' ) {
        return $value;
    }

    return '';
}

function com_theme_option_page_url( string $option_name, string $fallback_slug = '' ): string {
    $url = com_theme_link_url( com_theme_option( $option_name ) );

    if ( $url !== '' ) {
        return $url;
    }

    $slug = $fallback_slug !== '' ? $fallback_slug : str_replace( '_page', '', $option_name );
    $page = get_page_by_path( $slug );

    if ( $page instanceof WP_Post ) {
        return get_permalink( $page );
    }

    return home_url( '/' . trim( $slug, '/' ) . '/' );
}

function com_theme_page_url( string $slug, string $fallback = '' ): string {
    $option_map = [
        'privacy-policy'     => 'privacy_page',
        'privacy'            => 'privacy_page',
        'terms-of-use'       => 'terms_page',
        'terms'              => 'terms_page',
        'tickets'            => 'tickets_page',
        'buy-tickets'        => 'buy_tickets_page',
        'cookie-declaration' => 'cookies_policy_page',
        'cookies'            => 'cookies_policy_page',
    ];

    if ( isset( $option_map[ $slug ] ) ) {
        return com_theme_option_page_url( $option_map[ $slug ], $slug );
    }

    $page = get_page_by_path( $slug );

    if ( $page instanceof WP_Post ) {
        return get_permalink( $page );
    }

    return home_url( $fallback !== '' ? $fallback : '/' . trim( $slug, '/' ) . '/' );
}

/**
 * Ticketing integration.
 *
 * IW Tickets is intentionally project-agnostic. COM sells museum posts, so make
 * "museum" the project default while still respecting an explicitly saved
 * Ticketing setting.
 */
add_filter( 'acf/load_value/key=field_iw_ticketing_supported_post_types', static function ( $value ) {
    $saved_value = get_option( 'options_iw_ticketing_supported_post_types', null );

    if ( $saved_value === null || $saved_value === '' || $saved_value === false ) {
        return [ 'museum' ];
    }

    return $value;
}, 20 );

function com_theme_buy_tickets_page_ids(): array {
    $page_id = com_theme_attachment_id( com_theme_option( 'buy_tickets_page' ) );

    if ( ! $page_id ) {
        $page = get_page_by_path( 'agora-eisitiriou' );
        $page_id = $page instanceof WP_Post ? (int) $page->ID : 0;
    }

    if ( ! $page_id ) {
        return [];
    }

    $page_ids = [ $page_id ];

    if ( has_filter( 'wpml_active_languages' ) ) {
        $languages = apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] );

        foreach ( (array) $languages as $language ) {
            $language_code = is_array( $language ) ? (string) ( $language['code'] ?? '' ) : '';

            if ( $language_code === '' ) {
                continue;
            }

            $translated_id = (int) apply_filters( 'wpml_object_id', $page_id, 'page', false, $language_code );

            if ( $translated_id ) {
                $page_ids[] = $translated_id;
            }
        }
    }

    return array_values( array_unique( array_filter( array_map( 'absint', $page_ids ) ) ) );
}

function com_theme_register_ticket_routes(): void {
    foreach ( com_theme_buy_tickets_page_ids() as $page_id ) {
        $page_path = trim( get_page_uri( $page_id ), '/' );

        if ( $page_path === '' ) {
            continue;
        }

        $page_path = preg_quote( $page_path, '#' );
        $route_base = 'index.php?page_id=' . $page_id . '&tickets-for=$matches[1]';

        add_rewrite_rule(
            '^' . $page_path . '/([0-9]+)/([0-9]{4}-[0-9]{2}-[0-9]{2})/([0-9]{2}-[0-9]{2})/?$',
            $route_base . '&tickets-date=$matches[2]&tickets-time=$matches[3]',
            'top'
        );
        add_rewrite_rule(
            '^' . $page_path . '/([0-9]+)/([0-9]{4}-[0-9]{2}-[0-9]{2})/?$',
            $route_base . '&tickets-date=$matches[2]',
            'top'
        );
        add_rewrite_rule(
            '^' . $page_path . '/([0-9]+)/?$',
            $route_base,
            'top'
        );
    }
}
add_action( 'init', 'com_theme_register_ticket_routes', 30 );

add_filter( 'query_vars', static function ( array $vars ): array {
    $vars[] = 'tickets-for';
    $vars[] = 'tickets-date';
    $vars[] = 'tickets-time';

    return array_values( array_unique( $vars ) );
} );

add_action( 'wp_loaded', static function (): void {
    $rewrite_version = 'com-ticket-routes-2026-07-24';

    if ( get_option( 'com_theme_ticket_rewrite_version' ) === $rewrite_version ) {
        return;
    }

    flush_rewrite_rules( false );
    update_option( 'com_theme_ticket_rewrite_version', $rewrite_version );
} );

if ( ! function_exists( 'get_tickets_permalink' ) ) {
    function get_tickets_permalink( $id, $extra = '' ) {
        $ticket_id = absint( $id );

        if (
            ! $ticket_id
            || ! class_exists( 'IW_Ticketing' )
            || ! IW_Ticketing::is_sellable( $ticket_id )
        ) {
            return false;
        }

        $page_ids = com_theme_buy_tickets_page_ids();
        $page_id = $page_ids[0] ?? 0;

        if ( ! $page_id ) {
            return false;
        }

        $url = trailingslashit( get_permalink( $page_id ) ) . $ticket_id . '/';
        $extra = trim( (string) $extra, '/' );

        return $extra !== '' ? trailingslashit( $url . $extra ) : $url;
    }
}

function com_theme_default_ticket_post_id(): int {
    if ( ! class_exists( 'IW_Ticketing' ) ) {
        return 0;
    }

    $museum_ids = get_posts( [
        'post_type'      => 'museum',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
        'fields'         => 'ids',
    ] );

    foreach ( $museum_ids as $museum_id ) {
        if ( IW_Ticketing::is_sellable( $museum_id ) ) {
            return (int) $museum_id;
        }
    }

    return 0;
}

function com_theme_current_ticket_post_id(): int {
    $ticket_id = absint( get_query_var( 'tickets-for' ) );

    if ( ! $ticket_id && isset( $_GET['tickets-for'] ) ) {
        $ticket_id = absint( wp_unslash( $_GET['tickets-for'] ) );
    }

    return $ticket_id ?: com_theme_default_ticket_post_id();
}

add_filter( 'template_include', static function ( string $template ): string {
    $page_ids = com_theme_buy_tickets_page_ids();

    if ( ! empty( $page_ids ) && is_page( $page_ids ) ) {
        $ticket_template = locate_template( 'template-buy-tickets.php' );

        if ( $ticket_template ) {
            return $ticket_template;
        }
    }

    return $template;
}, 50 );

function com_theme_attachment_id( $value ): int {
    if ( $value instanceof WP_Post ) {
        return (int) $value->ID;
    }

    if ( is_array( $value ) ) {
        return (int) ( $value['ID'] ?? $value['id'] ?? 0 );
    }

    return is_numeric( $value ) ? (int) $value : 0;
}

function com_theme_museum_image_id( int $museum_id, string $preferred_field = 'card_image' ): int {
	$field_names = array_unique( [ $preferred_field, 'card_image', 'hero_image' ] );

	foreach ( $field_names as $field_name ) {
		$image_id = com_theme_attachment_id( com_theme_field_value( $field_name, $museum_id ) );

		if ( $image_id ) {
			return $image_id;
		}
	}

	return (int) get_post_thumbnail_id( $museum_id );
}

function com_theme_museum_location_label( int $museum_id ): string {
	$terms = get_the_terms( $museum_id, 'museum-location' );

	if ( ! is_array( $terms ) || empty( $terms ) ) {
		return '';
	}

	return implode( ', ', wp_list_pluck( $terms, 'name' ) );
}

function com_theme_logo_markup( string $option_name, string $fallback_symbol, string $classes, string $label = '' ): string {
    $attachment_id = com_theme_attachment_id( com_theme_option( $option_name ) );

    if ( $attachment_id ) {
        $mime_type = get_post_mime_type( $attachment_id );

        if ( $mime_type === 'image/svg+xml' ) {
            $path = get_attached_file( $attachment_id );
            if ( $path && file_exists( $path ) ) {
                $attrs = 'class="' . esc_attr( $classes ) . '"';
                if ( $label !== '' ) {
                    $attrs .= ' role="img" aria-label="' . esc_attr( $label ) . '"';
                } else {
                    $attrs .= ' aria-hidden="true"';
                }

                return preg_replace( '/<svg\b/', '<svg ' . $attrs, (string) file_get_contents( $path ), 1 ) ?: '';
            }
        }

        $image = wp_get_attachment_image( $attachment_id, 'full', false, [
            'class' => $classes,
            'alt'   => $label,
        ] );

        if ( $image ) {
            return $image;
        }
    }

    $label_attrs = $label !== '' ? ' role="img" aria-label="' . esc_attr( $label ) . '"' : ' aria-hidden="true"';

    return '<svg class="' . esc_attr( $classes ) . '"' . $label_attrs . '><use xlink:href="#icon-' . esc_attr( $fallback_symbol ) . '"></use></svg>';
}

function com_theme_get_menu_items( string $location ): array {
    $locations = get_nav_menu_locations();

    if ( empty( $locations[ $location ] ) ) {
        return [];
    }

    $items = wp_get_nav_menu_items( $locations[ $location ] );

    return is_array( $items ) ? $items : [];
}

function com_theme_menu_item_url( WP_Post $item ): string {
    $url = (string) ( $item->url ?? '' );

    if ( $url === '' ) {
        return '#';
    }

    if ( strpos( $url, '#' ) === 0 ) {
        return home_url( '/' . $url );
    }

    if ( strpos( $url, '/' ) === 0 ) {
        $home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/';

        if ( $home_path !== '/' && strpos( $url, $home_path ) !== 0 ) {
            return home_url( $url );
        }
    }

    return $url;
}

function com_theme_menu_item_icon( WP_Post $item ): string {
    $classes = array_filter( (array) ( $item->classes ?? [] ) );

    foreach ( $classes as $class ) {
        if ( strpos( $class, 'icon-' ) === 0 ) {
            return substr( $class, 5 );
        }
    }

    $haystack = mb_strtolower( $item->title . ' ' . $item->url );

    if ( str_contains( $haystack, 'ticket' ) || str_contains( $haystack, 'εισιτ' ) ) {
        return 'com-ticket';
    }

    if ( str_contains( $haystack, 'museum' ) || str_contains( $haystack, 'μουσει' ) ) {
        return 'com-museums';
    }

    return '';
}

function com_theme_header_menu_items(): array {
    $items = com_theme_get_menu_items( 'main' );

    if ( empty( $items ) ) {
        return [
            [
                'title' => __( 'ΤΑ ΜΟΥΣΕΙΑ ΜΑΣ', 'com-theme' ),
                'url'   => home_url( '/#museums' ),
                'icon'  => 'com-museums',
            ],
            [
                'title' => __( 'ΑΓΟΡΑ ΕΙΣΙΤΗΡΙΟΥ', 'com-theme' ),
                'url'   => com_theme_page_url( 'buy-tickets' ),
                'icon'  => 'com-ticket',
            ],
        ];
    }

    return array_map( static function ( WP_Post $item ): array {
        return [
            'title'  => $item->title,
            'url'    => com_theme_menu_item_url( $item ),
            'icon'   => com_theme_menu_item_icon( $item ),
            'target' => $item->target,
        ];
    }, $items );
}

function com_theme_footer_menu_items(): array {
    $items = com_theme_get_menu_items( 'copyright' );

    if ( empty( $items ) ) {
        $items = com_theme_get_menu_items( 'footer' );
    }

    if ( empty( $items ) ) {
        return [
            [
                'title' => __( 'Privacy Policy', 'com-theme' ),
                'url'   => com_theme_page_url( 'privacy-policy' ),
            ],
            [
                'title' => __( 'Terms & Conditions', 'com-theme' ),
                'url'   => com_theme_page_url( 'terms-of-use' ),
            ],
        ];
    }

    return array_map( static function ( WP_Post $item ): array {
        return [
            'title'  => $item->title,
            'url'    => com_theme_menu_item_url( $item ),
            'target' => $item->target,
        ];
    }, $items );
}

function com_theme_footer_info_text(): string {
    return (string) com_theme_option(
        'footer_info_text',
        __( 'Το Μεσολόγγι δεν είναι μια πόλη που απλώς επισκέπτεται κανείς· είναι μια εμπειρία που ζει, μια ταυτότητα που μοιράζεται και μια μνήμη που εμπνέει.', 'com-theme' )
    );
}

function com_theme_language_switcher_items(): array {
    $languages = apply_filters( 'wpml_active_languages', null, 'skip_missing=0&orderby=code' );

    if ( is_array( $languages ) && ! empty( $languages ) ) {
        $items = array_map(
            static function ( array $language ): array {
                $code = strtolower( (string) ( $language['language_code'] ?? $language['code'] ?? '' ) );

                return [
                    'code'   => $code,
                    'label'  => $code === 'el' ? 'ελ' : $code,
                    'url'    => (string) ( $language['url'] ?? home_url( '/' ) ),
                    'active' => ! empty( $language['active'] ),
                ];
            },
            $languages
        );

        usort(
            $items,
            static fn ( array $a, array $b ): int => ( [ 'el' => 0, 'en' => 1 ][ $a['code'] ] ?? 10 ) <=> ( [ 'el' => 0, 'en' => 1 ][ $b['code'] ] ?? 10 )
        );

        return $items;
    }

    $current_language = apply_filters( 'wpml_current_language', null );
    $is_greek = $current_language ? str_starts_with( strtolower( (string) $current_language ), 'el' ) : true;

    return [
        [
            'code'   => 'el',
            'label'  => 'ελ',
            'url'    => home_url( '/' ),
            'active' => $is_greek,
        ],
        [
            'code'   => 'en',
            'label'  => 'en',
            'url'    => home_url( '/en/' ),
            'active' => ! $is_greek,
        ],
    ];
}

function com_theme_asset_version( string $relative_path ): string {
    $path = get_theme_file_path( '/' . ltrim( $relative_path, '/' ) );

    if ( file_exists( $path ) ) {
        return (string) filemtime( $path );
    }

    return wp_get_theme()->get( 'Version' );
}

function com_theme_cart_item_ticket_count( array $cart_item ): int {
    $ticket_count = absint( $cart_item['tickets_total'] ?? 0 );

    if ( $ticket_count > 0 ) {
        return $ticket_count;
    }

    return max( 0, (int) ( $cart_item['quantity'] ?? 0 ) );
}

function com_theme_cart_count(): int {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return 0;
    }

    return array_reduce(
        WC()->cart->get_cart(),
        static fn ( int $count, array $cart_item ): int => $count + com_theme_cart_item_ticket_count( $cart_item ),
        0
    );
}

function com_theme_cart_count_label( ?int $count = null ): string {
    $count = $count ?? com_theme_cart_count();

    return sprintf(
        _n( '%d εισιτήριο', '%d εισιτήρια', $count, 'com-theme' ),
        $count
    );
}

function com_theme_cart_item_details( array $cart_item ): array {
    $product = $cart_item['data'] ?? null;
    $content_id = absint( $cart_item['tickets_for_id'] ?? 0 );

    if ( ! $content_id && class_exists( 'CPT_As_Product' ) && method_exists( 'CPT_As_Product', 'get_cpt_id_from_cart_item' ) ) {
        $content_id = absint( CPT_As_Product::get_cpt_id_from_cart_item( $cart_item ) );
    }

    $title = $content_id ? get_the_title( $content_id ) : '';
    if ( $title === '' && $product && is_callable( [ $product, 'get_name' ] ) ) {
        $title = $product->get_name();
    }

    $image_id = $content_id ? com_theme_museum_image_id( $content_id ) : 0;
    if ( ! $image_id && $product && is_callable( [ $product, 'get_image_id' ] ) ) {
        $image_id = absint( $product->get_image_id() );
    }

    $location = $content_id ? com_theme_museum_location_label( $content_id ) : '';
    $visitors = json_decode( (string) ( $cart_item['tickets_visitors'] ?? '[]' ), true );
    $groups = [];

    if ( is_array( $visitors ) ) {
        foreach ( $visitors as $visitor ) {
            if ( ! is_array( $visitor ) ) {
                continue;
            }

            $label = trim( (string) ( $visitor['category-name'] ?? $visitor['category_name'] ?? __( 'Εισιτήριο', 'com-theme' ) ) );
            $label = $label !== '' ? $label : __( 'Εισιτήριο', 'com-theme' );
            $price = is_numeric( $visitor['price'] ?? null ) ? (float) $visitor['price'] : 0.0;

            if ( ! isset( $groups[ $label ] ) ) {
                $groups[ $label ] = [
                    'label' => $label,
                    'count' => 0,
                    'total' => 0.0,
                ];
            }

            $groups[ $label ]['count']++;
            $groups[ $label ]['total'] += $price;
        }
    }

    $ticket_count = com_theme_cart_item_ticket_count( $cart_item );
    if ( empty( $groups ) && $ticket_count > 0 ) {
        $line_total = 0.0;
        if ( $product && is_callable( [ $product, 'get_price' ] ) && is_numeric( $product->get_price() ) ) {
            $line_total = (float) $product->get_price() * max( 1, (int) ( $cart_item['quantity'] ?? 1 ) );
        }

        $groups[] = [
            'label' => __( 'Εισιτήριο', 'com-theme' ),
            'count' => $ticket_count,
            'total' => $line_total,
        ];
    } else {
        $groups = array_values( $groups );
    }

    $date = trim( (string) ( $cart_item['tickets_day'] ?? '' ) );
    if ( $date !== '' ) {
        $timestamp = strtotime( $date );
        $date = $timestamp ? date_i18n( 'd/m/Y', $timestamp ) : $date;
    }

    $time = str_replace( '-', ':', trim( (string) ( $cart_item['tickets_time'] ?? '' ) ) );

    return [
        'content_id'    => $content_id,
        'title'         => $title,
        'permalink'     => $content_id ? get_permalink( $content_id ) : '',
        'image_id'      => $image_id,
        'location'      => $location,
        'date'          => $date,
        'time'          => $time,
        'ticket_count'  => $ticket_count,
        'groups'        => $groups,
    ];
}

add_filter( 'woocommerce_order_button_text', static function (): string {
    return __( 'Ολοκλήρωση αγοράς', 'com-theme' );
} );

add_filter( 'woocommerce_countries', static function ( array $countries ): array {
    if ( isset( $countries['GR'] ) ) {
        $countries['GR'] = __( 'Ελλάδα', 'com-theme' );
    }

    return $countries;
} );

add_filter( 'woocommerce_get_privacy_policy_text', static function ( string $text, string $type ): string {
    if ( $type !== 'checkout' ) {
        return $text;
    }

    $policy_url = get_privacy_policy_url();
    $policy_link = $policy_url
        ? sprintf(
            '<a href="%s" class="woocommerce-privacy-policy-link" target="_blank">%s</a>',
            esc_url( $policy_url ),
            esc_html__( 'Πολιτική απορρήτου', 'com-theme' )
        )
        : esc_html__( 'Πολιτική απορρήτου', 'com-theme' );

    return sprintf(
        /* translators: %s: privacy policy link */
        __( 'Τα προσωπικά σας δεδομένα χρησιμοποιούνται για την επεξεργασία της παραγγελίας και σύμφωνα με την %s.', 'com-theme' ),
        $policy_link
    );
}, 10, 2 );

/**
 * Keep the customer area focused on the parts that are useful for the COM
 * ticketing experience, and use the same labels on every endpoint.
 */
function com_theme_account_menu_items( array $items ): array {
    $menu = [
        'dashboard'       => __( 'Τα στοιχεία μου', 'com-theme' ),
        'tickets'         => __( 'Τα εισιτήριά μου', 'com-theme' ),
        'orders'          => __( 'Οι αγορές μου', 'com-theme' ),
        'edit-address'    => __( 'Διευθύνσεις', 'com-theme' ),
        'edit-account'    => __( 'Στοιχεία σύνδεσης', 'com-theme' ),
        'payment-methods' => __( 'Μέθοδοι πληρωμής', 'com-theme' ),
        'customer-logout' => __( 'Αποσύνδεση', 'com-theme' ),
    ];

    return apply_filters( 'com_theme_account_menu_items', $menu, $items );
}
add_filter( 'woocommerce_account_menu_items', 'com_theme_account_menu_items', 40 );

function com_theme_account_current_endpoint(): string {
    if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'view-order' ) ) {
        return 'orders';
    }

    if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'add-payment-method' ) ) {
        return 'payment-methods';
    }

    foreach ( wc_get_account_menu_items() as $endpoint => $label ) {
        if ( wc_is_current_account_menu_item( $endpoint ) ) {
            return $endpoint;
        }
    }

    return 'dashboard';
}

function com_theme_account_endpoint_count( string $endpoint ): int {
    $user_id = get_current_user_id();

    if ( ! $user_id ) {
        return 0;
    }

    if ( $endpoint === 'orders' && function_exists( 'wc_get_customer_order_count' ) ) {
        return (int) wc_get_customer_order_count( $user_id );
    }

    if ( $endpoint === 'tickets' && class_exists( 'IW_Ticketing' ) && method_exists( 'IW_Ticketing', 'get_ticket_orders' ) ) {
        $ticket_orders = IW_Ticketing::get_ticket_orders();

        return isset( $ticket_orders['items'] ) && is_array( $ticket_orders['items'] )
            ? count( $ticket_orders['items'] )
            : 0;
    }

    return 0;
}

add_filter( 'body_class', function ( array $classes ): array {
    if ( function_exists( 'is_account_page' ) && is_account_page() ) {
        $classes[] = 'is-account-page';
    }

    return $classes;
} );

add_filter( 'get_per_page', function( $post_type ){
    if( $post_type == 'article' ){
        return 12;
    } else {
        return 10;
    }
} );

add_action('pre_get_posts', function ($query) {
    if ( ! is_admin() && $query->is_main_query() && is_tax() ) {
        $taxonomy = get_queried_object();
        $taxonomySlug = $taxonomy->taxonomy;
        $taxonomyObj = get_taxonomy( $taxonomySlug );
        $query->set('posts_per_page', apply_filters( 'get_per_page',  $taxonomyObj->object_type[0] ) );
    }
});



function get_prose(){
    return  "   text-[1.6rem] leading-[1.5] text-current
                [&_*:first-child]:mt-0
                [&_*:last-child]:mb-0
                
                [&_a]:underline 
                [&_p]:my-[1em]
                    
                    
                [&_h1]:text-[2.986rem] [&_h1]:mt-[2em] [&_h1]:leading-none
                [&_h2]:text-[2.488rem] [&_h2]:mt-[2em] [&_h2]:leading-none
                [&_h3]:text-[2.074rem] [&_h3]:mt-[1em] [&_h3]:leading-none
                [&_h4]:text-[1.728rem] [&_h4]:mt-[1em] [&_h4]:leading-none
                [&_h5]:text-[1.44rem] [&_h5]:mt-[1em] [&_h5]:leading-none
                [&_h6]:text-[1.2rem] [&_h6]:mt-[1em] [&_h6]:leading-none
                
                [&_ul]:list-none [&_ul]:px-0 [&_ul]:my-[3rem]
                [&_ul_li]:pl-0
                [&_ul_li]:relative [&_ul_li]:pl-[0.75em]
                [&_ul_li]:before:absolute [&_ul_li]:before:left-0 [&_ul_li]:before:inline-block [&_ul_li]:before:w-[0.3125em] [&_ul_li]:before:h-[0.3125em] [&_ul_li]:before:mt-[0.6em] [&_ul_li]:before:rounded-full [&_ul_li]:before:bg-current [&_ul_li]:before:shrink-0
                
                [&_ol]:[list-style-position:inside] [&_ol]:[list-style-type:auto]
            ";
}
