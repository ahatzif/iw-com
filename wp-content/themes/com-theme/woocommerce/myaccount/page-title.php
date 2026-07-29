<?php
defined( 'ABSPATH' ) || exit;

$title_args = wp_parse_args( $args ?? [], [
    'title'       => '',
    'eyebrow'     => '',
    'description' => '',
    'classes'     => '',
] );

if ( $title_args['title'] === '' ) {
    $menu_items = wc_get_account_menu_items();
    $title_args['title'] = $menu_items[ com_theme_account_current_endpoint() ] ?? '';
}
?>

<?php if ( $title_args['title'] !== '' ) : ?>
    <header class="<?= esc_attr( trim( 'mb-40 ' . $title_args['classes'] ) ) ?>">
        <?php if ( $title_args['eyebrow'] !== '' ) : ?>
            <p class="mb-10 text-[1rem] font-medium tracking-[.18em] text-blue/50"><?= esc_html( com\theme::remove_accents( $title_args['eyebrow'] ) ) ?></p>
        <?php endif; ?>
        <h2 class="m-0 text-[2.8rem] font-bold leading-[1.1] md:text-[3.6rem]"><?= esc_html( $title_args['title'] ) ?></h2>
        <?php if ( $title_args['description'] !== '' ) : ?>
            <p class="mb-0 mt-15 max-w-[62rem] text-[1.5rem] leading-[1.45] text-blue/70 md:text-[1.6rem]"><?= wp_kses_post( $title_args['description'] ) ?></p>
        <?php endif; ?>
    </header>
<?php endif; ?>
