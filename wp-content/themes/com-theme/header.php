<?php
$config = $args ?? [];

$header_theme = $config['header_theme'] ?? com_theme_header_theme();
$is_light_header = $header_theme === 'light';
$initial_logo = $is_light_header ? 'com-header-logo-light' : 'com-header-logo';
$initial_text = $is_light_header ? 'text-ochre' : 'text-blue';
$active_nav = $config['active_nav'] ?? 'museums';
$page_background = $config['page_background'] ?? com_theme_page_background();
$page_background_class = com_theme_color_class( 'bg', $page_background, 'ochre' );
$page_text_class = com_theme_page_text_class();
$barba_namespace = $config['barba_namespace'] ?? ( is_page() ? get_post_field( 'post_name', get_queried_object_id() ) : 'page' );
$sprite_path = get_theme_file_path( '/assets/images/sprite/sprite.svg' );
$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : com_theme_page_url( 'my-account' );
$tickets_url = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'tickets' ) : $account_url;
$logout_url = function_exists( 'wc_logout_url' ) ? wc_logout_url( home_url( '/' ) ) : wp_logout_url( home_url( '/' ) );
$header_menu_items = com_theme_header_menu_items();
$language_items = com_theme_language_switcher_items();
$barba_prevent_pages = [];

if ( function_exists( 'wc_get_cart_url' ) && function_exists( 'wc_get_checkout_url' ) ) {
    foreach ( [ wc_get_cart_url(), wc_get_checkout_url() ] as $purchase_url ) {
        $purchase_path = wp_parse_url( $purchase_url, PHP_URL_PATH );

        if ( is_string( $purchase_path ) && $purchase_path !== '' ) {
            $barba_prevent_pages[] = $purchase_path;
        }
    }
}
?>
<!doctype html>
<html <?php language_attributes(); ?> class="fixed inset-0 font-main antialiased" data-module-load>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>window.barbaPreventPages = <?= wp_json_encode( array_values( array_unique( $barba_prevent_pages ) ) ) ?>;</script>
    <?php wp_head(); ?>
</head>
<body <?php body_class( trim( 'group fixed inset-0 m-0 ' . $page_background_class . ' font-main ' . $page_text_class . ( is_user_logged_in() ? ' logged-in' : '' ) ) ); ?> data-barba="wrapper">
<?php wp_body_open(); ?>

<div class="hidden" aria-hidden="true">
    <?php if ( file_exists( $sprite_path ) ) { echo file_get_contents( $sprite_path ); } ?>
</div>

<header class="fixed inset-x-0 top-0 z-20 h-100 bg-transparent <?= esc_attr( $initial_text ) ?> transition-colors duration-300 group-[.has-scrolled]:text-blue md:h-[13.841464rem]" data-module-page-header>
    <div class="absolute inset-0 -translate-y-full bg-white shadow-[0_0_30px_rgba(0,0,0,.075)] transition-transform duration-300 group-[.has-scrolled]:translate-y-0" aria-hidden="true"></div>

    <div class="relative mx-auto flex h-full w-full items-center justify-between px-1/12 py-20 md:px-1/24 md:py-[3.3rem] lg:px-2/24">
        <a href="<?= esc_url( home_url( '/' ) ) ?>" class="relative block h-50 w-[11rem] shrink-0 md:h-[7.2415rem] md:w-[15.9rem]" aria-label="<?= esc_attr__( 'Αρχική', 'com-theme' ) ?>">
            <?= com_theme_logo_markup(
                $is_light_header ? 'header_logo_light' : 'header_logo',
                $initial_logo,
                'absolute inset-0 block size-full transition-opacity duration-300 ' . ( $is_light_header ? 'group-[.has-scrolled]:opacity-0' : '' )
            ) ?>
            <?php if ( $is_light_header ) : ?>
                <?= com_theme_logo_markup( 'header_logo', 'com-header-logo', 'absolute inset-0 block size-full opacity-0 transition-opacity duration-300 group-[.has-scrolled]:opacity-100' ) ?>
            <?php endif; ?>
        </a>

        <nav class="flex items-center gap-[.8rem] text-[1.2rem] font-bold leading-[1.2] md:gap-30 md:text-[1.45rem] xl:gap-40 xl:text-[1.6rem]" aria-label="<?= esc_attr__( 'Κύρια πλοήγηση', 'com-theme' ) ?>">
            <?php foreach ( $header_menu_items as $item ) :
                $icon = $item['icon'] ?? '';
                $is_ticket_item = $icon === 'com-ticket';
                $is_museums_item = $icon === 'com-museums';

                // Temporarily hide the separate museums navigation item.
                if ( $is_museums_item ) {
                    continue;
                }

                if ( $is_ticket_item ) {
                    $item['url'] = home_url( '/#museums' );
                }

                $fragment = wp_parse_url( $item['url'], PHP_URL_FRAGMENT );
                $smooth_selector = is_front_page() && $fragment ? '#' . ltrim( $fragment, '#' ) : '';
                $icon_light = $is_ticket_item ? 'com-ticket-light' : ( $is_museums_item ? 'com-museums-light' : $icon );
                $icon_size = $is_ticket_item
                    ? 'h-[1.6rem] w-[1.9rem] md:h-[2.2rem] md:w-[2.6rem]'
                    : 'size-[2.2rem] md:size-25';
                $link_opacity = '';

                if ( ! $is_ticket_item ) {
                    if ( $active_nav === 'tickets' ) {
                        $link_opacity = 'opacity-50';
                    } elseif ( $active_nav === 'museums' && ! $is_museums_item ) {
                        $link_opacity = 'opacity-50';
                    } elseif ( $active_nav === '' ) {
                        $link_opacity = 'opacity-50';
                    }
                }
            ?>
                <a
                    href="<?= esc_url( $item['url'] ) ?>"
                    class="flex items-center gap-10 whitespace-nowrap <?= esc_attr( $link_opacity ) ?> <?= $is_ticket_item ? 'order-2 relative z-1 size-35 shrink-0 select-none justify-center rounded-full border border-current before:pointer-events-none before:absolute before:inset-0 before:rounded-full before:bg-current before:opacity-0 before:transition-opacity before:duration-300 hover:before:opacity-10 md:order-none md:h-auto md:w-auto md:justify-start md:rounded-none md:border-0 md:before:hidden' : '' ?>"
                    <?= $smooth_selector ? 'data-module-scroll-to-anchor data-selector="' . esc_attr( $smooth_selector ) . '" data-barba-prevent="self"' : '' ?>
                    <?= ! empty( $item['target'] ) ? 'target="' . esc_attr( $item['target'] ) . '"' : '' ?>
                >
                    <?php if ( $icon !== '' ) : ?>
                        <span class="relative block shrink-0 <?= esc_attr( $icon_size ) ?>">
                            <svg class="absolute inset-0 block size-full transition-opacity duration-300 <?= $is_light_header ? 'group-[.has-scrolled]:opacity-0' : '' ?>" aria-hidden="true">
                                <use xlink:href="#icon-<?= esc_attr( $is_light_header ? $icon_light : $icon ) ?>"></use>
                            </svg>
                            <?php if ( $is_light_header ) : ?>
                                <svg class="absolute inset-0 block size-full opacity-0 transition-opacity duration-300 group-[.has-scrolled]:opacity-100" aria-hidden="true">
                                    <use xlink:href="#icon-<?= esc_attr( $icon ) ?>"></use>
                                </svg>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                    <span class="hidden md:inline"><?= esc_html( $item['title'] ) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if ( ! empty( $language_items ) ) : ?>
                <div class="order-1 mr-5 flex items-center gap-[.4rem] text-[1.2rem] leading-none md:order-none md:mr-0 md:gap-5 md:text-[1.45rem]" aria-label="<?= esc_attr__( 'Εναλλαγή γλώσσας', 'com-theme' ) ?>">
                    <?php foreach ( $language_items as $index => $language ) : ?>
                        <?php if ( $index > 0 ) : ?>
                            <span class="opacity-50" aria-hidden="true">|</span>
                        <?php endif; ?>
                        <a
                            href="<?= esc_url( $language['url'] ) ?>"
                            data-barba-prevent
                            hreflang="<?= esc_attr( $language['code'] ) ?>"
                            class="<?= esc_attr( ! empty( $language['active'] ) ? 'font-bold' : 'font-normal opacity-70 transition-opacity hover:opacity-100' ) ?>"
                        ><?= com\theme::remove_accents(esc_html( $language['label'] )) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="order-3 flex gap-[.8rem] md:order-none md:gap-20">
                <?php get_template_part( 'templates/com/parts/cart-button', null, [
                    'is_light_header' => $is_light_header,
                ] ); ?>
                <?php if ( is_user_logged_in() ) : ?>
                    <div class="user-menu group relative z-20" data-module-user-menu>
                        <button
                            type="button"
                            data-user-menu="button"
                            class="relative z-1 flex size-35 shrink-0 select-none items-center justify-center rounded-full border border-current before:pointer-events-none before:absolute before:inset-0 before:rounded-full before:bg-current before:opacity-0 before:transition-opacity before:duration-300 hover:before:opacity-10 group-[.user-menu.active]:before:opacity-10 md:size-40"
                            aria-label="<?= esc_attr__( 'Μενού λογαριασμού', 'com-theme' ) ?>"
                            aria-haspopup="menu"
                            aria-expanded="false"
                            aria-controls="com-user-menu"
                        >
                            <svg class="block size-20 fill-current md:size-[2.3rem]" aria-hidden="true">
                                <use xlink:href="#icon-user"></use>
                            </svg>
                        </button>

                        <div
                            id="com-user-menu"
                            data-user-menu="panel"
                            class="invisible pointer-events-none absolute right-0 top-full w-[26rem] max-w-[calc(100vw-3rem)] translate-y-5 pt-15 opacity-0 transition-[opacity,transform,visibility] duration-200 group-[.user-menu.active]:visible group-[.user-menu.active]:pointer-events-auto group-[.user-menu.active]:translate-y-0 group-[.user-menu.active]:opacity-100 md:pt-20"
                            role="menu"
                            aria-hidden="true"
                            aria-label="<?= esc_attr__( 'Λογαριασμός χρήστη', 'com-theme' ) ?>"
                        >
                            <div class="overflow-hidden rounded-[1.5rem] bg-white  text-blue shadow-[0_0_2.4rem_rgba(0,0,0,.12)] space-y-20 p-20 md:p-30">
                                <a href="<?= esc_url( $tickets_url ) ?>" role="menuitem" class="block rounded-[.8rem] text-[1.5rem] font-normal transition-colors hover:underline focus-visible:outline-none">
                                    <?= esc_html__( 'Τα εισιτήριά μου', 'com-theme' ) ?>
                                </a>
                                <a href="<?= esc_url( $account_url ) ?>" role="menuitem" class="block rounded-[.8rem] text-[1.5rem] font-normal transition-colors hover:underline focus-visible:outline-none">
                                    <?= esc_html__( 'Ο λογαριασμός μου', 'com-theme' ) ?>
                                </a>
                                <a href="<?= esc_url( $logout_url ) ?>" data-barba-prevent role="menuitem" class="block rounded-[.8rem] text-[1.5rem] font-normal transition-colors hover:underline focus-visible:outline-none">
                                    <?= esc_html__( 'Αποσύνδεση', 'com-theme' ) ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                    <a
                        href="<?= esc_url( $account_url ) ?>"
                        data-auth-modal-trigger
                        data-barba-prevent="self"
                        class="relative z-1 flex size-35 shrink-0 select-none items-center justify-center rounded-full border border-current before:pointer-events-none before:absolute before:inset-0 before:rounded-full before:bg-current before:opacity-0 before:transition-opacity before:duration-300 hover:before:opacity-10 md:size-40"
                        aria-label="<?= esc_attr__( 'Ο λογαριασμός μου', 'com-theme' ) ?>"
                        aria-haspopup="dialog"
                        aria-controls="com-auth-modal"
                    >
                        <svg class="block size-20 fill-current md:size-[2.3rem]" aria-hidden="true">
                            <use xlink:href="#icon-user"></use>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<div data-barba="container" data-barba-namespace="<?= esc_attr( $barba_namespace ) ?>" class="fixed inset-0 overflow-hidden">
    <div class="relative mx-auto h-full overflow-hidden">
        <div data-module-scroll="main" class="absolute inset-0 z-1 overflow-hidden">
            <div data-scroll="content" class="min-h-full <?= esc_attr( $page_background_class ) ?>">
