<?php
defined( 'ABSPATH' ) || exit;

$embedded = ! empty( $args['embedded'] );
?>
<?php if ( ! $embedded ) : ?>
<section class="min-h-screen bg-blue pb-120 pt-[16rem] text-ochre">
    <div class="page-wrapper">
        <header class="max-w-[75rem]">
            <p class="m-0 text-[1rem] font-medium uppercase tracking-[.18em]"><?= esc_html__( 'Αγορά εισιτηρίου', 'com-theme' ) ?></p>
            <h1 class="mt-20 text-[4.4rem] font-medium leading-[1.08] md:text-[6rem] md:leading-[7rem]"><?= esc_html__( 'Το καλάθι σας', 'com-theme' ) ?></h1>
            <p class="mt-20 max-w-[56rem] text-[1.8rem] leading-[1.35] text-ochre/80"><?= esc_html__( 'Ελέγξτε τα εισιτήριά σας πριν προχωρήσετε στην ολοκλήρωση της αγοράς.', 'com-theme' ) ?></p>
        </header>
        <div class="mt-60">
<?php endif; ?>
<div class="mx-auto max-w-[64rem] rounded-[1.5rem] border border-ochre/40 p-30 text-center md:p-60">
    <div class="mx-auto flex size-60 items-center justify-center rounded-full border border-ochre">
        <svg class="size-30 fill-current" aria-hidden="true"><use xlink:href="#icon-cart"></use></svg>
    </div>
    <h2 class="mt-30 text-[2.8rem] font-normal leading-[1.15] md:text-[4.4rem]"><?= esc_html__( 'Το καλάθι σας είναι άδειο', 'com-theme' ) ?></h2>
    <p class="mx-auto mt-15 max-w-[46rem] text-[1.6rem] leading-[1.4] text-ochre/80"><?= esc_html__( 'Επιλέξτε το μουσείο, την ημερομηνία και τον τύπο εισιτηρίου που σας ενδιαφέρει.', 'com-theme' ) ?></p>
    <a
        href="<?= esc_url( com_theme_page_url( 'buy-tickets' ) ) ?>"
        class="mt-30 inline-flex min-h-[5.6rem] items-center justify-center rounded-[1rem] bg-white px-30 text-[1.6rem] text-blue transition-colors hover:bg-ochre"
    ><?= esc_html__( 'ΕΠΙΛΟΓΗ ΕΙΣΙΤΗΡΙΩΝ', 'com-theme' ) ?></a>
</div>
<?php if ( ! $embedded ) : ?>
        </div>
    </div>
</section>
<?php endif; ?>
