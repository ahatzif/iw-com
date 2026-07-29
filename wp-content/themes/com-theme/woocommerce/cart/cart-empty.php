<?php
defined( 'ABSPATH' ) || exit;

$embedded = ! empty( $args['embedded'] );
$has_notices = ! $embedded && function_exists( 'wc_notice_count' ) && wc_notice_count() > 0;
?>
<?php if ( ! $embedded ) : ?>
<section class="flex min-h-screen flex-col bg-blue pb-120 pt-[16rem] text-ochre">
    <div class="page-wrapper flex flex-1 flex-col">
        <?php if ( $has_notices ) : ?>
            <div class="mb-40">
                <?php wc_print_notices(); ?>
            </div>
        <?php endif; ?>
        <div class="flex flex-1">
<?php endif; ?>
<div class="mx-auto flex min-h-[48rem] w-full max-w-none flex-1 flex-col items-center justify-center rounded-[1.5rem] border border-ochre/40 p-30 text-center md:p-60">
    <div class="mx-auto flex size-60 items-center justify-center rounded-full border border-ochre">
        <svg class="size-30 fill-current" aria-hidden="true"><use xlink:href="#icon-cart"></use></svg>
    </div>
    <h2 class="mt-30 text-[2.8rem] font-normal leading-[1.15] md:text-[4.4rem]"><?= esc_html__( 'Το καλάθι σας είναι άδειο', 'com-theme' ) ?></h2>
    <p class="mx-auto mt-15 max-w-[46rem] text-[1.6rem] leading-[1.4] text-ochre/80"><?= esc_html__( 'Επιλέξτε το μουσείο, την ημερομηνία και τον τύπο εισιτηρίου που σας ενδιαφέρει.', 'com-theme' ) ?></p>
    <a
        href="<?= esc_url( home_url( '/#museums' ) ) ?>"
        class="mt-30 inline-flex min-h-[5.6rem] items-center justify-center rounded-[1rem] bg-white px-30 text-[1.6rem] text-blue transition-colors hover:bg-ochre"
    ><?= esc_html__( 'ΕΠΙΛΟΓΗ ΕΙΣΙΤΗΡΙΩΝ', 'com-theme' ) ?></a>
</div>
<?php if ( ! $embedded ) : ?>
        </div>
    </div>
</section>
<?php endif; ?>
