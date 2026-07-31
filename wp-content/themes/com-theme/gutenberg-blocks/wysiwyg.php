<?php // Title: Wysiwyg

if ( ! function_exists( 'com_theme_prepare_wysiwyg_toc' ) ) {
    function com_theme_prepare_wysiwyg_toc( string $content ): array {
        $toc_items = [];
        $seen_ids  = [];

        $content = preg_replace_callback(
            '/<h2\b([^>]*)>(.*?)<\/h2>/is',
            static function ( array $matches ) use ( &$toc_items, &$seen_ids ): string {
                $attributes   = $matches[1];
                $heading_html = $matches[2];
                $heading_text = trim( wp_strip_all_tags( $heading_html ) );

                if ( $heading_text === '' ) {
                    return $matches[0];
                }

                if ( preg_match( "/\sid=([\"'])(.*?)\\1/i", $attributes, $id_match ) ) {
                    $id = sanitize_html_class( $id_match[2] );
                } else {
                    $id          = sanitize_title( $heading_text );
                    $attributes .= ' id="' . esc_attr( $id ) . '"';
                }

                $base_id = $id;
                $counter = 2;

                while ( isset( $seen_ids[ $id ] ) ) {
                    $id = $base_id . '-' . $counter;
                    ++$counter;
                }

                $seen_ids[ $id ] = true;

                if ( $id !== $base_id ) {
                    if ( preg_match( "/\sid=([\"'])(.*?)\\1/i", $attributes ) ) {
                        $attributes = preg_replace( "/\sid=([\"'])(.*?)\\1/i", ' id="' . esc_attr( $id ) . '"', $attributes, 1 );
                    } else {
                        $attributes .= ' id="' . esc_attr( $id ) . '"';
                    }
                }

                $toc_items[] = [
                    'id'    => $id,
                    'label' => $heading_text,
                ];

                return '<h2' . $attributes . '>' . $heading_html . '</h2>';
            },
            $content
        ) ?? $content;

        return [ $content, $toc_items ];
    }
}

$text = (string) get_field( 'text' );

if ( $text === '' ) {
    return;
}

$has_scroll_sidebar = (bool) get_field( 'has_scroll_sidebar' );
$sidebar_title = trim( (string) get_field( 'scroll_sidebar_title' ) );
$sidebar_title = $sidebar_title !== '' ? $sidebar_title : __( 'ΠΕΡΙΕΧΟΜΕΝΑ', 'com-theme' );
$section_classes = trim( com_theme_block_wrapper_classes( 'mx-auto w-full px-1/12 md:px-1/24 lg:px-2/24' ) . ' ' . com_theme_block_style_classes() );
$content_classes = com_theme_legal_content_classes();

[ $content, $toc_items ] = $has_scroll_sidebar ? com_theme_prepare_wysiwyg_toc( $text ) : [ $text, [] ];
?>
<section class="<?= esc_attr( $section_classes ) ?>">
    <?php if ( $has_scroll_sidebar && ! empty( $toc_items ) ) : ?>
        <div class="grid gap-50 lg:grid-cols-12 lg:gap-0">
            <aside class="lg:col-span-3 lg:pr-40">
                <nav class="border-t border-current pt-20 lg:sticky lg:top-[16rem] lg:max-h-[calc(100vh-18rem)] lg:overflow-y-auto lg:pr-20" aria-label="<?= esc_attr( $sidebar_title ) ?>" data-module-legal-sidebar>
                    <p class="text-[1.2rem] font-bold tracking-[.08em]"><?= esc_html( $sidebar_title ) ?></p>
                    <ol class="mt-20 space-y-10 text-[1.3rem] leading-[1.35]">
                        <?php foreach ( $toc_items as $index => $item ) :
                            $target = '#' . ltrim( $item['id'], '#' );
                        ?>
                            <li>
                                <a
                                    href="<?= esc_url( $target ) ?>"
                                    data-module-scroll-to-anchor
                                    data-legal-sidebar="link"
                                    data-selector="<?= esc_attr( $target ) ?>"
                                    class="group relative flex items-start gap-10 opacity-60 transition-opacity before:absolute before:-left-15 before:top-[.55em] before:size-[.5rem] before:rounded-full before:bg-current before:opacity-0 before:transition-opacity hover:opacity-100 [&.active]:font-bold [&.active]:opacity-100 [&.active]:before:opacity-100"
                                >
                                    <span class="w-20 shrink-0 opacity-50 transition-opacity group-[.active]:opacity-100"><?= esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ) ?></span>
                                    <span><?= esc_html( $item['label'] ) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            </aside>

            <div class="lg:col-span-8 lg:col-start-5">
                <div class="<?= esc_attr( $content_classes ) ?>" data-wysiwyg>
                    <?= wp_kses_post( $content ) ?>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="mx-1/12 md:mx-2/12 md:w-8/12">
            <div class="<?= esc_attr( $content_classes ) ?>" data-wysiwyg>
                <?= wp_kses_post( $content ) ?>
            </div>
        </div>
    <?php endif; ?>
</section>
