<?php
$config = $args ?? [];

$footer_theme = $config['footer_theme'] ?? 'light';
$is_dark_footer = $footer_theme === 'dark';
$footer_logo = $config['footer_logo'] ?? ( $is_dark_footer ? 'com-header-logo-light' : 'com-footer-logo' );
$footer_description_font = $config['footer_description_font'] ?? 'font-asty';
$footer_classes = $is_dark_footer ? 'bg-blue text-ochre' : 'bg-ochre text-blue';
$footer_border = $is_dark_footer ? 'border-ochre' : 'border-blue';
$footer_logo_option = $is_dark_footer ? 'footer_logo_light' : 'footer_logo';
$footer_links = com_theme_footer_menu_items();
$footer_info_text = com_theme_footer_info_text();
$footer_copyright_text = (string) com_theme_option( 'footer_copyright_text', __( '©Copyright Messolonghi All Right Reserved', 'com-theme' ) );
$footer_credit_text = (string) com_theme_option( 'footer_credit_text', __( 'created by INTERWEAVE', 'com-theme' ) );
$footer_credit_url = (string) com_theme_option( 'footer_credit_url', 'https://www.interweaveagency.com/' );
$footer_credit_label = 'INTERWEAVE';
$footer_credit_position = stripos( $footer_credit_text, $footer_credit_label );
$wrapper_classes = 'mx-auto w-full px-1/12 md:px-1/24 lg:px-2/24';
?>
<footer class="<?= esc_attr( trim( 'flex min-h-[32.5rem] flex-col items-center gap-50 ' . $footer_classes . ' md:gap-100' ) ) ?>">
    <div class="<?= esc_attr( $wrapper_classes ) ?>">
        <div class="<?= esc_attr( trim( 'flex h-full items-start justify-between border-t pb-50 pt-40 ' . $footer_border . ' md:pb-0' ) ) ?>">
            <div class="flex w-full flex-col items-start gap-30 md:w-[60rem] md:flex-row md:gap-60">
                <?= com_theme_logo_markup( $footer_logo_option, $footer_logo, 'h-[10.885rem] w-[23.9rem] shrink-0', __( 'Μεσολόγγι 200 χρόνια', 'com-theme' ) ) ?>
                <div class="<?= esc_attr( trim( 'flex-1 text-[2rem] font-normal leading-[1.2] [&_p]:m-0 ' . $footer_description_font ) ) ?>"><?= wp_kses_post( wpautop( $footer_info_text ) ) ?></div>
            </div>
        </div>
    </div>
    <div class="<?= esc_attr( $wrapper_classes ) ?>">
        <div class="<?= esc_attr( trim( 'flex flex-col items-start justify-between gap-40 border-t py-20 ' . $footer_border . ' md:flex-row md:items-center md:gap-20' ) ) ?>">
            <div class="flex flex-col gap-10 text-[1.2rem] font-normal md:flex-row md:items-center md:gap-20">
                <?php foreach ( $footer_links as $item ) : ?>
                    <a
                        href="<?= esc_url( $item['url'] ) ?>"
                        <?= ! empty( $item['target'] ) ? 'target="' . esc_attr( $item['target'] ) . '"' : '' ?>
                    ><?= esc_html( $item['title'] ) ?></a>
                <?php endforeach; ?>
                <p class="m-0 md:order-first"><?= esc_html( $footer_copyright_text ) ?></p>
            </div>
            <p class="m-0 text-[1.2rem]">
                <?php if ( $footer_credit_position !== false ) : ?>
                    <?= esc_html( substr( $footer_credit_text, 0, $footer_credit_position ) ) ?><a href="<?= esc_url( $footer_credit_url ) ?>" target="_blank" rel="noopener" class="transition-opacity hover:opacity-70"><?= esc_html( substr( $footer_credit_text, $footer_credit_position, strlen( $footer_credit_label ) ) ) ?></a><?= esc_html( substr( $footer_credit_text, $footer_credit_position + strlen( $footer_credit_label ) ) ) ?>
                <?php else : ?>
                    <?= esc_html( $footer_credit_text ) ?> <a href="<?= esc_url( $footer_credit_url ) ?>" target="_blank" rel="noopener" class="transition-opacity hover:opacity-70"><?= esc_html( $footer_credit_label ) ?></a>
                <?php endif; ?>
            </p>
        </div>
    </div>
</footer>
            </div>
        </div>
    </div>
</div>

<?php get_template_part( 'templates/com/parts/auth-modal' ); ?>
<div data-module-page-loading class="page-loading fixed inset-0 z-[1000]" aria-hidden="true"></div>
<?php get_template_part( 'templates/com/parts/layout-grid' ); ?>
<?php wp_footer(); ?>
</body>
</html>
