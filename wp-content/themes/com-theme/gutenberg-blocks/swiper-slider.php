<?php // Title: Places Slider

if( ! empty( $slides = get_field( 'slides' )  ) ) {
?>
<section class="<?php echo esc_attr( com_theme_block_style_classes() ); ?>">
<div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?>" data-module-swiper>
    <div class="mx-1/12">
    <?php if( ! empty( $title = get_field( 'title' ) ) ) { ?>
        <h2 class="text-21 leading-[1.4285714286] mb-50"><?php echo $title;?></h2>
    <?php } ?>
    </div>
    <div class="swiper relative" data-swiper="swiper">
        <div class="wrapper flex">
            <?php foreach ( $slides as $slide ) { $link = $slide[ 'link' ]; ?>
                <div class="w-10/12 sm:w-8/12 md:w-4/12 slide pl-20 shrink-0 slide">
                    <?php if( ! empty( $imageID = $slide[ 'image' ] ) ) { ?>
                        <a href="<?php echo $link[ 'url' ]; ?>" target="<?php echo $link['target'] ?>" class="block aspect-[0.8] relative mb-20">
                            <?php get_template_part('templates/parts/image', false, [ 'id' => $imageID, 'size' => 'full', 'alt' => $link[ 'title' ] ] ); ?>
                        </a>
                    <?php } ?>
                    <h3 class="text-31 leading-[1.3870967742] font-normal font-heading"><a  target="<?php echo $link['target'] ?>"  href="<?php echo $link[ 'url' ]; ?>"><?php echo $link[ 'title' ];  ?></a></h3>
                    <?php if( ! empty( $text = $slide[ 'text' ] ) ) { ?>
                        <p class="mt-10 text-14 leading-[1.5714285714]"><?php echo $text; ?></p>
                    <?php } ?>
                    <?php if( ! empty( $linkText = $slide[ 'link_text' ] ) ) { ?>
                        <div class="mt-30">
                            <a class="text-current font-bold text-14 leading-none" target="<?php echo $link['target'] ?>"  href="<?php echo $link['url']; ?>"><?php echo $linkText; ?></a>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
        <div class="absolute top-1/2 -translate-y-1/2 left-0 w-full pointer-events-none">
            <div class="mx-2/12 flex justify-between">
                <div class="w-50 h-50 rounded-full bg-dove flex items-center justify-center -translate-x-1/2 pointer-events-auto cursor-pointer [&.inactive]:cursor-default [&.inactive]:opacity-0" data-arrow-prev>
                    <svg class="w-[1.8rem] h-[12.5rem] rotate-180"><use xlink:href="#icon-swiper-arrow"></use></svg>
                </div>
                <div class="w-50 h-50 rounded-full bg-dove flex items-center justify-center  translate-x-1/2 pointer-events-auto  cursor-pointer [&.inactive]:cursor-default [&.inactive]:opacity-0" data-arrow-next>
                    <svg class="w-[1.8rem] h-[12.5rem]"><use xlink:href="#icon-swiper-arrow"></use></svg>
                </div>
            </div>
        </div>
    </div>

</div>
</section>
<?php }
