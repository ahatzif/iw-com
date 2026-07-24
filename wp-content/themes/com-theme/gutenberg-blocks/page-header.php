<?php // Title: Page Header

if ( ! function_exists( 'com_theme_page_header_eyebrow' ) ) {
    function com_theme_page_header_eyebrow(): string {
        $post = get_post();

        if ( $post instanceof WP_Post && $post->post_parent ) {
            return get_the_title( $post->post_parent );
        }

        return __( 'Αρχική', 'com-theme' );
    }
}

if ( ! function_exists( 'com_theme_page_header_eyebrow_url' ) ) {
    function com_theme_page_header_eyebrow_url(): string {
        $post = get_post();

        if ( $post instanceof WP_Post && $post->post_parent ) {
            return get_permalink( $post->post_parent ) ?: '';
        }

        return home_url( '/' );
    }
}

$title   = trim( (string) get_field( 'title' ) );
$text    = trim( (string) get_field( 'text' ) );
$eyebrow = trim( (string) get_field( 'eyebrow' ) );
$eyebrow_url = '';
$hide_eyebrow = function_exists( 'com_theme_hide_breadcrumb' ) && com_theme_hide_breadcrumb();

$header_classes = trim( 'mx-auto w-full px-1/12 md:px-1/24 lg:px-2/24 ' . com_theme_block_style_classes( 'text-blue', [
    'desktop' => [
        'pt' => '160',
        'pb' => '80',
    ],
] ) );

if ( is_tax() ) {
    $taxonomy = get_queried_object();

    if ( empty( $title ) ) {
        $title = $taxonomy->name;
    }

    if ( empty( $text ) ) {
        $text = $taxonomy->description;
    }

    $taxonomy_obj = get_taxonomy( $taxonomy->taxonomy );

    if ( ! $hide_eyebrow && empty( $eyebrow ) && $taxonomy_obj ) {
        $eyebrow = $taxonomy_obj->labels->singular_name ?? $taxonomy_obj->label;
    }
} else {
    if ( empty( $title ) ) {
        $title = get_the_title();
    }

    if ( empty( $text ) && has_excerpt() ) {
        $text = get_the_excerpt();
    }

    if ( ! $hide_eyebrow && empty( $eyebrow ) ) {
        $eyebrow = com_theme_page_header_eyebrow();
    }

    if ( ! $hide_eyebrow && ! empty( $eyebrow ) ) {
        $eyebrow_url = com_theme_page_header_eyebrow_url();
    }
}
?>
<header class="<?= esc_attr( $header_classes ) ?>">
    <?php if ( ! $hide_eyebrow && ! empty( $eyebrow ) ) : ?>
        <p class="text-[1rem] font-medium leading-none tracking-[.18em] opacity-60">
            <?php if ( ! empty( $eyebrow_url ) ) : ?>
                <a class="transition-opacity hover:opacity-70" href="<?= esc_url( $eyebrow_url ) ?>"><?= esc_html( $eyebrow ) ?></a>
            <?php else : ?>
                <?= esc_html( $eyebrow ) ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if ( ! empty( $title ) ) : ?>
        <h1 class="mt-20 text-[4.6rem] font-medium leading-[1.05] sm:text-[5.2rem] lg:text-[6rem] lg:leading-[7rem]"><?= esc_html( $title ) ?></h1>
    <?php endif; ?>

    <?php if ( ! empty( $text ) ) : ?>
        <div class="mt-25 max-w-[72rem] text-[1.8rem] leading-[1.4] md:text-[2.2rem] md:leading-[1.3]"><?= wp_kses_post( wpautop( $text ) ) ?></div>
    <?php endif; ?>
</header>
