<?php
/**
 * Dynamic ticket purchase page.
 *
 * The visual shell follows the approved COM static prototype while availability,
 * prices and cart writes come from IW Tickets.
 */

$ticket_post_id = com_theme_current_ticket_post_id();

get_header( null, [
    'header_theme'    => 'light',
    'active_nav'      => 'tickets',
    'page_background' => 'blue',
    'barba_namespace' => 'tickets',
] );

$tickets_data = (
    $ticket_post_id
    && class_exists( 'IW_Ticketing' )
    && method_exists( 'IW_Ticketing', 'get_tickets_data' )
)
    ? IW_Ticketing::get_tickets_data( $ticket_post_id )
    : null;
?>

<?php if ( ! $tickets_data ) : ?>
    <main class="min-h-[calc(100vh+3rem)] bg-blue text-ochre">
        <section class="page-wrapper flex min-h-[70rem] items-center pb-100 pt-[16rem]" aria-labelledby="tickets-unavailable-title">
            <div class="max-w-[72rem]">
                <p class="text-[1rem] font-medium uppercase leading-none tracking-[.18em] text-white"><?php esc_html_e( 'Αγορά εισιτηρίου', 'com-theme' ); ?></p>
                <h1 id="tickets-unavailable-title" class="mt-20 text-[4.6rem] font-medium leading-[1.05] sm:text-[5.2rem] lg:text-[6rem] lg:leading-[7rem]"><?php esc_html_e( 'Δεν υπάρχουν διαθέσιμα εισιτήρια', 'com-theme' ); ?></h1>
                <p class="mt-30 max-w-[60rem] text-[1.6rem] leading-[1.5] text-ochre/80"><?php esc_html_e( 'Η ηλεκτρονική διάθεση εισιτηρίων δεν έχει ενεργοποιηθεί ακόμη για το συγκεκριμένο μουσείο.', 'com-theme' ); ?></p>
                <a href="<?php echo esc_url( home_url( '/#museums' ) ); ?>" class="mt-40 inline-flex min-h-50 items-center rounded-[.8rem] border border-ochre px-25 text-[1.4rem] transition-colors hover:bg-ochre hover:text-blue"><?php esc_html_e( 'Δείτε τα μουσεία', 'com-theme' ); ?> →</a>
            </div>
        </section>
    </main>
    <?php
    get_footer( null, [
        'footer_theme'            => 'dark',
        'footer_description_font' => 'font-main',
    ] );
    return;
endif;

$ticket_post_id = (int) $tickets_data->post_id;
$ticket_title = get_the_title( $ticket_post_id );
$ticket_permalink = get_permalink( $ticket_post_id );
$ticket_image_id = com_theme_museum_image_id( $ticket_post_id, 'hero_image' );
$ticket_location = com_theme_museum_location_label( $ticket_post_id );
$ticket_language = (string) apply_filters( 'wpml_current_language', '' );
$ticket_locale = $ticket_language === 'en' ? 'en-US' : 'el-GR';

if ( $ticket_location === '' ) {
    $ticket_location = (string) get_field( 'place_label', $ticket_post_id );
}

$categories_config = [];

foreach ( (array) $tickets_data->ticket_categories as $category ) {
    if ( ! is_object( $category ) ) {
        continue;
    }

    $subcategories = [];

    foreach ( (array) ( $category->subcategories ?? [] ) as $subcategory ) {
        if ( ! is_object( $subcategory ) || empty( $subcategory->value ) ) {
            continue;
        }

        $subcategories[] = [
            'value'       => (string) $subcategory->value,
            'label'       => wp_strip_all_tags( (string) $subcategory->label ),
            'description' => wp_strip_all_tags( (string) ( $subcategory->description ?? '' ) ),
            'price'       => (float) ( $subcategory->price ?? 0 ),
        ];
    }

    if ( empty( $subcategories ) ) {
        continue;
    }

    $categories_config[] = [
        'key'             => (string) $category->key,
        'label'           => wp_strip_all_tags( (string) $category->label ),
        'description'     => wp_strip_all_tags( (string) ( $category->description ?? '' ) ),
        'minPrice'        => (float) ( $category->min_price ?? 0 ),
        'maxPrice'        => (float) ( $category->max_price ?? $category->min_price ?? 0 ),
        'minTickets'      => (int) ( $category->min_tickets ?? 0 ),
        'maxTickets'      => (int) ( $category->max_tickets ?? $tickets_data->max_tickets ),
        'requireFullName' => ! empty( $category->require_full_name ),
        'subcategories'   => $subcategories,
    ];
}

$ticket_config = [
    'action'       => (string) $tickets_data->ajax_action,
    'nonce'        => wp_create_nonce( (string) $tickets_data->ajax_action ),
    'ticketId'     => $ticket_post_id,
    'schedule'     => $tickets_data->schedule,
    'selectedDate' => (string) $tickets_data->tickets_date,
    'selectedTime' => (string) $tickets_data->tickets_time,
    'minTickets'   => max( 1, (int) $tickets_data->min_tickets ),
    'maxTickets'   => max( 1, (int) $tickets_data->max_tickets ),
    'categories'   => $categories_config,
    'cartUrl'      => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
    'locale'       => $ticket_locale,
    'currency'     => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'EUR',
    'strings'      => [
        'monthPrevious'    => __( 'Προηγούμενος μήνας', 'com-theme' ),
        'monthNext'        => __( 'Επόμενος μήνας', 'com-theme' ),
        'availableDate'    => __( 'διαθέσιμη', 'com-theme' ),
        'limitedDate'      => __( 'περιορισμένη διαθεσιμότητα', 'com-theme' ),
        'unavailableDate'  => __( 'μη διαθέσιμη', 'com-theme' ),
        'soldOut'          => __( 'εξαντλήθηκαν', 'com-theme' ),
        'remainingOne'     => __( 'απομένει 1 εισιτήριο', 'com-theme' ),
        'remainingMany'    => __( 'απομένουν %s εισιτήρια', 'com-theme' ),
        'ticketSingular'   => __( 'Εισιτήριο', 'com-theme' ),
        'ticketPlural'     => __( 'Εισιτήρια', 'com-theme' ),
        'next'             => __( 'Επόμενο', 'com-theme' ),
        'addToCart'        => __( 'Προσθήκη στο καλάθι', 'com-theme' ),
        'addingToCart'     => __( 'Προσθήκη…', 'com-theme' ),
        'minimumTickets'   => __( 'Πρέπει να επιλέξετε τουλάχιστον %s εισιτήρια.', 'com-theme' ),
        'maximumTickets'   => __( 'Μπορείτε να επιλέξετε έως %s εισιτήρια.', 'com-theme' ),
        'genericError'     => __( 'Δεν ήταν δυνατή η προσθήκη στο καλάθι. Παρακαλούμε δοκιμάστε ξανά.', 'com-theme' ),
        'selectTicketType' => __( 'Επιλέξτε κατηγορία εισιτηρίου.', 'com-theme' ),
        'noDates'          => __( 'Δεν υπάρχουν διαθέσιμες ημερομηνίες.', 'com-theme' ),
        'noTimes'          => __( 'Δεν υπάρχουν διαθέσιμες ώρες για αυτή την ημερομηνία.', 'com-theme' ),
    ],
];
?>

<main class="min-h-[calc(100vh+3rem)] bg-blue text-ochre" data-module-tickets>
    <script type="application/json" data-ticket-config><?php echo wp_json_encode( $ticket_config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>

    <form class="page-wrapper pb-100 pt-[16rem] lg:pb-[14rem]" data-ticket-form aria-labelledby="tickets-title">
        <div class="grid items-start gap-60 lg:grid-cols-[60rem_48rem] lg:justify-between lg:gap-0">
            <div class="min-w-0">
                <div class="flex flex-col gap-20">
                    <p class="text-[1rem] font-medium uppercase leading-none tracking-[.18em] text-white"><?php esc_html_e( 'Αγορά εισιτηρίου', 'com-theme' ); ?></p>
                    <h1 id="tickets-title" class="max-w-[60rem] text-[4.6rem] font-medium leading-[1.05] sm:text-[5.2rem] lg:text-[6rem] lg:leading-[7rem]"><?php esc_html_e( 'Βρείτε το εισιτήριό σας', 'com-theme' ); ?></h1>
                </div>

                <ol class="mt-40 flex items-center lg:mt-[18rem]" aria-label="<?php esc_attr_e( 'Βήματα αγοράς', 'com-theme' ); ?>" data-ticket-steps>
                    <?php
                    $steps = [
                        1 => [ __( 'ΕΠΙΛΟΓΗ', 'com-theme' ), __( 'ΗΜΕΡΟΜΗΝΙΑΣ', 'com-theme' ) ],
                        2 => [ __( 'ΕΠΙΛΟΓΗ', 'com-theme' ), __( 'ΩΡΑΣ', 'com-theme' ) ],
                        3 => [ __( 'ΕΠΙΛΟΓΗ', 'com-theme' ), __( 'ΕΙΣΙΤΗΡΙΩΝ', 'com-theme' ) ],
                    ];
                    foreach ( $steps as $number => $labels ) :
                        ?>
                        <li class="contents">
                            <button type="button" data-ticket-step="<?php echo esc_attr( $number ); ?>" class="group/step flex shrink-0 items-center gap-10 text-left text-blue-soft opacity-60 transition-opacity disabled:cursor-not-allowed [&.is-complete]:opacity-100 [&.is-current]:text-white [&.is-current]:opacity-100">
                                <span class="flex size-40 shrink-0 items-center justify-center rounded-full border border-blue-soft text-[1.4rem] transition-all group-[.is-current]/step:size-60 group-[.is-current]/step:border-white group-[.is-current]/step:bg-white group-[.is-current]/step:text-blue group-[.is-complete]/step:border-white group-[.is-complete]/step:text-white">0<?php echo esc_html( $number ); ?></span>
                                <span class="hidden text-[1.2rem] leading-[1.15] sm:block lg:text-[1.4rem]"><?php echo esc_html( $labels[0] ); ?><br><?php echo esc_html( $labels[1] ); ?></span>
                            </button>
                            <?php if ( $number < count( $steps ) ) : ?>
                                <span class="mx-10 h-px min-w-10 flex-1 border-t border-dashed border-blue-soft" aria-hidden="true"></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>

                <div class="mt-60">
                    <section data-ticket-panel="1" class="rounded-[1.5rem] border border-white p-20 sm:p-40 lg:p-60" aria-labelledby="date-title">
                        <div class="flex items-center justify-between">
                            <h2 id="date-title" class="text-[2rem] font-bold uppercase leading-[1.2]" data-ticket-month-label></h2>
                            <div class="flex items-center gap-15">
                                <button type="button" data-ticket-month-previous class="flex size-30 items-center justify-center disabled:pointer-events-none disabled:opacity-30" aria-label="<?php esc_attr_e( 'Προηγούμενος μήνας', 'com-theme' ); ?>">
                                    <span class="block size-[1.6rem] rotate-45 border-b border-l border-white" aria-hidden="true"></span>
                                </button>
                                <button type="button" data-ticket-month-next class="flex size-30 items-center justify-center disabled:pointer-events-none disabled:opacity-30" aria-label="<?php esc_attr_e( 'Επόμενος μήνας', 'com-theme' ); ?>">
                                    <span class="block size-[1.6rem] rotate-45 border-r border-t border-white" aria-hidden="true"></span>
                                </button>
                            </div>
                        </div>

                        <div class="mt-30 grid grid-cols-7 gap-x-5 gap-y-20 sm:gap-x-15 sm:gap-y-30" data-ticket-calendar>
                            <?php foreach ( [ 'ΔΕΥ', 'ΤΡΙ', 'ΤΕΤ', 'ΠΕΜ', 'ΠΑΡ', 'ΣΑΒ', 'ΚΥΡ' ] as $weekday ) : ?>
                                <span class="flex h-20 items-center justify-center text-[1.2rem] sm:text-[1.6rem]"><?php echo esc_html( $weekday ); ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-30 flex flex-wrap justify-center gap-x-20 gap-y-5 text-[1rem] sm:text-[1.2rem]">
                            <span class="text-blue-soft"><?php esc_html_e( 'ΜΗ ΔΙΑΘΕΣΙΜΕΣ', 'com-theme' ); ?></span>
                            <span class="text-[#ff8686]"><?php esc_html_e( 'ΠΕΡΙΟΡΙΣΜΕΝΟΣ ΑΡΙΘΜΟΣ', 'com-theme' ); ?></span>
                            <span class="text-white"><?php esc_html_e( 'ΔΙΑΘΕΣΙΜΟΣ ΑΡΙΘΜΟΣ', 'com-theme' ); ?></span>
                        </div>
                    </section>

                    <section data-ticket-panel="2" class="hidden" aria-labelledby="time-title">
                        <h2 id="time-title" class="sr-only"><?php esc_html_e( 'Επιλέξτε ώρα επίσκεψης', 'com-theme' ); ?></h2>
                        <div class="grid gap-20 sm:grid-cols-2" data-ticket-time-slots></div>
                        <div class="mt-30 flex flex-wrap justify-center gap-x-20 gap-y-5 text-[1rem] sm:text-[1.2rem]">
                            <span class="text-blue-soft"><?php esc_html_e( 'ΜΗ ΔΙΑΘΕΣΙΜΕΣ', 'com-theme' ); ?></span>
                            <span class="text-[#ff8686]"><?php esc_html_e( 'ΠΕΡΙΟΡΙΣΜΕΝΟΣ ΑΡΙΘΜΟΣ', 'com-theme' ); ?></span>
                            <span class="text-white"><?php esc_html_e( 'ΔΙΑΘΕΣΙΜΟΣ ΑΡΙΘΜΟΣ', 'com-theme' ); ?></span>
                        </div>
                    </section>

                    <section data-ticket-panel="3" class="hidden" aria-labelledby="category-title">
                        <h2 id="category-title" class="sr-only"><?php esc_html_e( 'Επιλέξτε εισιτήρια', 'com-theme' ); ?></h2>
                        <div class="border-t border-blue-soft">
                            <?php foreach ( $categories_config as $category ) : ?>
                                <?php
                                $category_key = sanitize_key( $category['key'] );
                                $has_price_range = $category['maxPrice'] > $category['minPrice'];
                                ?>
                                <div
                                    data-ticket-category
                                    data-category-key="<?php echo esc_attr( $category_key ); ?>"
                                    class="border-b border-blue-soft py-30 sm:pb-[10.5rem] sm:pt-25"
                                >
                                    <div class="grid grid-cols-[1fr_auto_auto] gap-x-15 gap-y-25 sm:grid-cols-[minmax(0,1fr)_7rem_12rem_8rem] sm:items-center sm:gap-15">
                                        <div class="col-span-3 min-w-0 sm:col-span-1">
                                            <h3 class="text-[2rem] font-bold uppercase leading-[1.2]"><?php echo esc_html( $category['label'] ); ?></h3>
                                            <?php if ( $category['description'] ) : ?>
                                                <p class="mt-20 max-w-[32rem] text-[1.4rem] leading-[1.4] text-ochre/80"><?php echo esc_html( $category['description'] ); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-[1.6rem] leading-[1.25] sm:text-right">
                                            <?php if ( $has_price_range ) : ?><span class="block text-[1.1rem]"><?php esc_html_e( 'από', 'com-theme' ); ?></span><?php endif; ?>
                                            <?php echo wp_kses_post( wc_price( $category['minPrice'] ) ); ?>
                                        </p>
                                        <div class="flex h-[5.4rem] w-[12rem] items-center justify-between rounded-[.8rem] border border-white">
                                            <button type="button" data-ticket-quantity="<?php echo esc_attr( $category_key ); ?>" data-delta="-1" class="flex h-full flex-1 items-center justify-center text-[2.4rem] leading-none transition-colors hover:bg-white hover:text-blue disabled:cursor-not-allowed disabled:opacity-30" aria-label="<?php echo esc_attr( sprintf( __( 'Μείωση %s', 'com-theme' ), $category['label'] ) ); ?>">−</button>
                                            <span data-ticket-quantity-value="<?php echo esc_attr( $category_key ); ?>" class="w-30 text-center text-[2.2rem]">0</span>
                                            <button type="button" data-ticket-quantity="<?php echo esc_attr( $category_key ); ?>" data-delta="1" class="flex h-full flex-1 items-center justify-center text-[2.4rem] leading-none transition-colors hover:bg-white hover:text-blue disabled:cursor-not-allowed disabled:opacity-30" aria-label="<?php echo esc_attr( sprintf( __( 'Αύξηση %s', 'com-theme' ), $category['label'] ) ); ?>">+</button>
                                        </div>
                                        <span data-ticket-category-cost="<?php echo esc_attr( $category_key ); ?>" class="text-[2.4rem] font-bold sm:text-right"><?php echo wp_kses_post( wc_price( 0 ) ); ?></span>
                                    </div>

                                    <div data-ticket-visitors="<?php echo esc_attr( $category_key ); ?>" class="space-y-30"></div>

                                    <template data-ticket-visitor-template="<?php echo esc_attr( $category_key ); ?>">
                                        <fieldset data-ticket-visitor data-category-key="<?php echo esc_attr( $category_key ); ?>" class="mt-30 border-t border-dashed border-blue-soft pt-30 text-ochre">
                                            <div class="flex items-center justify-between gap-20 text-[1.2rem] text-blue-soft">
                                                <legend><?php esc_html_e( 'ΕΙΣΙΤΗΡΙΟ', 'com-theme' ); ?> <span data-ticket-visitor-index></span></legend>
                                                <span data-ticket-visitor-price></span>
                                            </div>
                                            <div class="mt-30 grid gap-25 sm:grid-cols-2">
                                                <label class="flex flex-col gap-10 text-[1.1rem] uppercase tracking-[.04em] sm:col-span-2">
                                                    <?php esc_html_e( 'Κατηγορία', 'com-theme' ); ?>*
                                                    <span class="relative block">
                                                        <select data-ticket-visitor-field="category-id" required class="h-[6.4rem] w-full rounded-[.8rem] border border-white bg-blue px-20 pr-50 text-[1.6rem] font-normal normal-case tracking-normal text-ochre">
                                                            <option value=""><?php esc_html_e( 'Επιλέξτε', 'com-theme' ); ?></option>
                                                            <?php foreach ( $category['subcategories'] as $subcategory ) : ?>
                                                                <option value="<?php echo esc_attr( $subcategory['value'] ); ?>" data-price="<?php echo esc_attr( $subcategory['price'] ); ?>"><?php echo esc_html( $subcategory['label'] ); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <span class="pointer-events-none absolute right-20 top-1/2 size-[1.2rem] -translate-y-1/2 rotate-45 border-b border-r border-white" aria-hidden="true"></span>
                                                    </span>
                                                </label>
                                                <label class="flex flex-col gap-10 text-[1.1rem] uppercase tracking-[.04em]">
                                                    <?php esc_html_e( 'Όνομα', 'com-theme' ); ?><?php echo $category['requireFullName'] ? '*' : ''; ?>
                                                    <input type="text" maxlength="80" data-ticket-visitor-field="first" <?php echo $category['requireFullName'] ? 'required' : ''; ?> class="h-[5.6rem] rounded-[.8rem] border border-white bg-transparent px-20 text-[1.6rem] font-normal normal-case tracking-normal text-ochre">
                                                </label>
                                                <label class="flex flex-col gap-10 text-[1.1rem] uppercase tracking-[.04em]">
                                                    <?php esc_html_e( 'Επώνυμο', 'com-theme' ); ?><?php echo $category['requireFullName'] ? '*' : ''; ?>
                                                    <input type="text" maxlength="80" data-ticket-visitor-field="last" <?php echo $category['requireFullName'] ? 'required' : ''; ?> class="h-[5.6rem] rounded-[.8rem] border border-white bg-transparent px-20 text-[1.6rem] font-normal normal-case tracking-normal text-ochre">
                                                </label>
                                            </div>
                                        </fieldset>
                                    </template>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>

            <aside class="relative overflow-hidden rounded-[1.5rem] bg-white text-blue lg:sticky lg:top-[16rem]" aria-label="<?php esc_attr_e( 'Σύνοψη εισιτηρίου', 'com-theme' ); ?>">
                <div class="p-30 sm:p-60">
                    <p class="text-[1.4rem] font-bold text-blue-soft"><?php esc_html_e( 'ΕΙΣΙΤΗΡΙΟ', 'com-theme' ); ?></p>
                    <h2 class="mt-20 text-[2.4rem] font-bold leading-[1.2]"><a href="<?php echo esc_url( $ticket_permalink ); ?>"><?php echo esc_html( $ticket_title ); ?></a></h2>
                    <?php if ( $ticket_location ) : ?><p class="mt-10 text-[1.4rem] text-blue-soft"><?php echo esc_html( $ticket_location ); ?></p><?php endif; ?>
                    <?php if ( $ticket_image_id ) : ?>
                        <div class="mt-20 aspect-[calc(360/201)] overflow-hidden rounded-[1rem]">
                            <?php echo wp_get_attachment_image( $ticket_image_id, 'large', false, [ 'class' => 'size-full object-cover', 'alt' => $ticket_title ] ); ?>
                        </div>
                    <?php endif; ?>
                    <dl class="mt-20 space-y-5 text-[1.4rem]">
                        <div><dt class="inline"><?php esc_html_e( 'ΗΜ/ΝΙΑ:', 'com-theme' ); ?></dt> <dd data-ticket-date-preview class="inline font-bold">—</dd></div>
                        <div data-ticket-time-row class="hidden"><dt class="inline"><?php esc_html_e( 'ΩΡΑ:', 'com-theme' ); ?></dt> <dd data-ticket-time-preview class="inline font-bold">—</dd></div>
                    </dl>
                    <div data-ticket-category-summary class="mt-20 hidden space-y-5 border-t border-blue-soft/40 pt-20 text-[1.2rem]"></div>
                </div>

                <div class="relative border-t border-dashed border-blue-soft px-30 pb-60 pt-20 before:absolute before:-left-15 before:-top-15 before:size-30 before:rounded-full before:bg-blue before:content-[''] after:absolute after:-right-15 after:-top-15 after:size-30 after:rounded-full after:bg-blue after:content-[''] sm:px-60">
                    <div class="flex items-start justify-between gap-20">
                        <div>
                            <p class="text-[1.6rem] font-bold text-blue-soft"><?php esc_html_e( 'ΣΥΝΟΛΟ', 'com-theme' ); ?></p>
                            <p data-ticket-total-count class="text-[1.2rem]">0 <?php esc_html_e( 'Εισιτήρια', 'com-theme' ); ?></p>
                        </div>
                        <p data-ticket-total-price class="text-[2.4rem] font-bold"><?php echo wp_kses_post( wc_price( 0 ) ); ?></p>
                    </div>

                    <div data-ticket-error class="mt-20 hidden rounded-[.8rem] bg-[#ff8686]/20 p-15 text-[1.2rem] leading-[1.4] text-[#a61919]" role="alert" aria-live="assertive"></div>

                    <button type="submit" data-ticket-next class="mt-20 w-full rounded-[1rem] border border-blue px-30 py-20 text-[1.6rem] transition-colors hover:bg-blue hover:text-white disabled:cursor-not-allowed disabled:border-blue-soft disabled:text-blue-soft disabled:opacity-50 [&.is-purchase]:bg-blue [&.is-purchase]:text-white"><?php esc_html_e( 'Επόμενο', 'com-theme' ); ?></button>
                </div>
            </aside>
        </div>
    </form>
</main>

<?php
wp_reset_postdata();
get_footer( null, [
    'footer_theme'            => 'dark',
    'footer_description_font' => 'font-main',
] );
