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
$header_menu_items = com_theme_header_menu_items();
$language_items = com_theme_language_switcher_items();
?>
<!doctype html>
<html <?php language_attributes(); ?> class="fixed inset-0 font-main antialiased" data-module-load>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>window.barbaPreventPages = [];</script>
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
        <a href="<?= esc_url( home_url( '/' ) ) ?>" class="relative block h-60 w-[13.2rem] shrink-0 md:h-[7.2415rem] md:w-[15.9rem]" aria-label="<?= esc_attr__( 'Αρχική', 'com-theme' ) ?>">
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
                $fragment = wp_parse_url( $item['url'], PHP_URL_FRAGMENT );
                $smooth_selector = is_front_page() && $fragment ? '#' . ltrim( $fragment, '#' ) : '';
                $is_ticket_item = $icon === 'com-ticket';
                $is_museums_item = $icon === 'com-museums';
                $icon_light = $is_ticket_item ? 'com-ticket-light' : ( $is_museums_item ? 'com-museums-light' : $icon );
                $icon_size = $is_ticket_item
                    ? 'h-20 w-[2.3rem] md:h-[2.2rem] md:w-[2.6rem]'
                    : 'size-[2.2rem] md:size-25';
                $link_opacity = '';

                if ( $active_nav === 'tickets' && ! $is_ticket_item ) {
                    $link_opacity = 'opacity-50';
                } elseif ( $active_nav === 'museums' && ! $is_museums_item ) {
                    $link_opacity = 'opacity-50';
                } elseif ( $active_nav === '' ) {
                    $link_opacity = 'opacity-50';
                }
            ?>
                <a
                    href="<?= esc_url( $item['url'] ) ?>"
                    class="flex items-center gap-10 whitespace-nowrap <?= esc_attr( $link_opacity ) ?>"
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
                <div class="flex items-center gap-[.4rem] text-[1.2rem] leading-none md:gap-5 md:text-[1.45rem]" aria-label="<?= esc_attr__( 'Εναλλαγή γλώσσας', 'com-theme' ) ?>">
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
            <div class="flex gap-20">
                <?php get_template_part( 'templates/com/parts/cart-button' ); ?>
                <a
                    href="<?= esc_url( $account_url ) ?>"
                    data-auth-modal-trigger
                    data-barba-prevent="self"
                    class="flex size-40 shrink-0 items-center justify-center"
                    aria-label="<?= esc_attr__( 'Ο λογαριασμός μου', 'com-theme' ) ?>"
                    aria-haspopup="dialog"
                    aria-controls="com-auth-modal"
                >
                    <svg class="block size-40 fill-current" aria-hidden="true">
                        <use xlink:href="#icon-user"></use>
                    </svg>
                </a>
            </div>
        </nav>
    </div>
</header>

<div data-barba="container" data-barba-namespace="<?= esc_attr( $barba_namespace ) ?>" class="fixed inset-0 overflow-hidden">
    <div class="relative mx-auto h-full overflow-hidden">
        <div data-module-scroll="main" class="absolute inset-0 z-1 overflow-hidden">
            <div data-scroll="content" class="min-h-full <?= esc_attr( $page_background_class ) ?>">
