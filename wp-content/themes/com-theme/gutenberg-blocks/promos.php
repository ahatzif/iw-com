<?php // Title: Promos

if( get_field( 'promos_selection' ) === 'custom-selection' ) {
    $promos = get_field( 'promos' );
} else {
    $promos = [];
}

$totalPromos = count( $promos );


?>
<section class="<?php echo esc_attr( com_theme_block_style_classes( '', [] ) ); ?>">
<div class="page-wrapper">
    <div class="flex flex-wrap">
    <?php
    foreach ( $promos as $key => $promo ) {
        $isBig = $totalPromos % 2 !== 0 && $key === $totalPromos - 1;
        $title = $promo[ 'select_title' ] === 'page-title' ? $promo['page']->post_title : $promo[ 'custom_title' ] ;
        $photoID = $promo[ 'select_photo' ] === 'featured-image' ? get_post_thumbnail_id( $promo['page'] ) : $promo[ 'custom_photo' ];
        $link = get_permalink( $promo['page'] );
        $buttonText = empty( $promo[ 'button_text' ] ) ? __( 'VIEW MORE', 'com-theme' ) : $promo[ 'button_text' ];


    ?>
        <div class="w-full  <?php echo $isBig ? '' : 'md:w-6/12'; ?> bg-brown">
            <div class="relative <?php echo $isBig ? 'aspect-[1.2] md:aspect-[2.4]' : 'aspect-[1.2]'; ?>">
                <?php get_template_part('templates/parts/image', false, [ 'id' => $photoID, 'size' => $isBig ? 'com-theme-promo-full' : 'com-theme-promo-half', 'additionalClasses' => 'mix-blend-multiply	', 'alt' => $title ]); ?>

                <div class="absolute inset-0 flex flex-col justify-end pb-90 text-white">
                    <div class="pl-1/12 w-6/12 md:w-4/12">
                        <?php if( ! empty( $title ) ) { ?>
                        <h3 class="font-heading text-42 mb-[3rem]"><a href="<?php echo $link; ?>"><?php echo $title; ?></a></h3>
                        <?php } ?>
                        <?php get_template_part('templates/parts/button', false, [ 'color' => 'white-outlined',    'link' => $link, 'text' => $buttonText ]); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    </div>
</div>
</section>
