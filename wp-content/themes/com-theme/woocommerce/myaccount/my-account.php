<?php
/**
 * My Account shell.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

remove_action( 'woocommerce_account_content', 'woocommerce_output_all_notices', 5 );

$current_user = wp_get_current_user();
$current_endpoint = com_theme_account_current_endpoint();
$menu_items = wc_get_account_menu_items();
?>

<main
    class="account-page endpoint <?= esc_attr( $current_endpoint ) ?> px-1/12 pb-100 pt-[14rem] text-blue md:px-1/24 md:pb-120 md:pt-[18rem] lg:px-2/24"
    data-module-account-pages
>
    <header class="mb-50 flex flex-col gap-30 border-b border-blue/50 pb-40 md:mb-60 md:flex-row md:items-end md:justify-between md:pb-50">
        <div class="max-w-[78rem]">
            <p class="mb-15 text-[1rem] font-medium tracking-[.18em] text-blue/60">
                <?= esc_html( com\theme::remove_accents( __( 'Προσωπικός χώρος', 'com-theme' ) ) ) ?>
            </p>
            <h1 class="m-0 text-[4rem] font-medium leading-[1.05] md:text-[6rem]">
                <?= esc_html__( 'Ο λογαριασμός μου', 'com-theme' ) ?>
            </h1>
            <p class="mb-0 mt-20 max-w-[64rem] text-[1.6rem] leading-[1.35] md:text-[2rem]">
                <?= esc_html__( 'Όλα όσα αφορούν τις αγορές και τις επισκέψεις σας, συγκεντρωμένα σε ένα σημείο.', 'com-theme' ) ?>
            </p>
        </div>
    </header>

    <div data-account-pages="notices" aria-live="polite">
        <?php woocommerce_output_all_notices(); ?>
    </div>

    <nav class="-mx-1/12 mb-20 overflow-x-auto px-1/12 md:hidden" aria-label="<?= esc_attr__( 'Σελίδες λογαριασμού', 'com-theme' ) ?>">
        <ul class="flex w-max gap-10 pb-10">
            <?php foreach ( $menu_items as $endpoint => $label ) :
                $is_active = $endpoint === $current_endpoint;
                $is_logout = $endpoint === 'customer-logout';
            ?>
                <li class="woo-navigation-item <?= $is_active ? 'is-active' : '' ?>" data-account-pages="link-li">
                    <a
                        href="<?= esc_url( wc_get_account_endpoint_url( $endpoint ) ) ?>"
                        class="inline-flex min-h-45 items-center gap-8 whitespace-nowrap rounded-[.8rem] border px-15 text-[1.3rem] transition-colors <?= $is_active ? 'border-blue bg-blue text-white' : 'border-blue/30 text-blue' ?>"
                        <?= $is_logout ? 'data-barba-prevent' : 'data-barba-prevent data-account-pages="link"' ?>
                    >
                        <?= esc_html( $label ) ?>
                        <?php $count = com_theme_account_endpoint_count( $endpoint ); ?>
                        <?php if ( $count > 0 ) : ?>
                            <span class="flex min-w-20 items-center justify-center rounded-full bg-current/10 px-5 py-2 text-[1rem]"><?= esc_html( (string) $count ) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="grid items-start gap-20 md:grid-cols-[27rem_minmax(0,1fr)] md:gap-30 lg:grid-cols-[30rem_minmax(0,1fr)] lg:gap-40">
        <nav class="sticky top-[16rem] hidden overflow-hidden rounded-[1.5rem] bg-white text-blue md:block" aria-label="<?= esc_attr__( 'Σελίδες λογαριασμού', 'com-theme' ) ?>">
            <ul>
                <?php foreach ( $menu_items as $endpoint => $label ) :
                    $is_active = $endpoint === $current_endpoint;
                    $is_logout = $endpoint === 'customer-logout';
                ?>
                    <li class="group woo-navigation-item <?= $is_active ? 'is-active' : '' ?>" data-account-pages="link-li">
                        <a
                            href="<?= esc_url( wc_get_account_endpoint_url( $endpoint ) ) ?>"
                            class="flex min-h-[6.2rem] items-center justify-between gap-15 border-b border-blue/10 px-25 text-[1.5rem] transition-colors last:border-0 group-[.is-active]:font-bold  hover:bg-ochre-light"
                            <?= $is_logout ? 'data-barba-prevent' : 'data-barba-prevent data-account-pages="link"' ?>
                        >
                            <span><?= esc_html( $label ) ?></span>
                            <?php $count = com_theme_account_endpoint_count( $endpoint ); ?>
                            <?php if ( $count > 0 ) : ?>
                                <span class="flex min-w-[2.4rem] items-center justify-center rounded-full border border-current px-6 py-3 text-[1rem]"><?= esc_html( (string) $count ) ?></span>
                            <?php else : ?>
                                <span class="opacity-0 transition-opacity group-[.is-active]:opacity-100" aria-hidden="true">→</span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <section
            class="account-page__content min-w-0 rounded-[1.5rem] bg-white p-20 text-blue md:p-40 lg:p-60"
            data-account-pages="content"
            aria-live="polite"
        >
            <?php do_action( 'woocommerce_account_content' ); ?>
        </section>
    </div>
</main>
