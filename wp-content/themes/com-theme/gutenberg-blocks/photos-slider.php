<?php // Title: Photos Slider

if( ! empty( $photos = get_field( 'gallery' )  ) ) {
    ?>
    <section class="<?php echo esc_attr( com_theme_block_style_classes( '' ) ); ?>">
    <div class="<?php echo esc_attr( com_theme_block_wrapper_classes( 'page-wrapper' ) ); ?> relative" data-module-swiper data-options='{ "loop" : true }'>
        <div class="ml-1/12 md:ml-2/12">
            <div class="swiper relative cursor-pointer" data-swiper="swiper">
                <div class="wrapper flex">
                    <?php foreach ( $photos as $photo ) {  ?>
                        <div class="slide shrink-0 slide mr-20">
                            <?php get_template_part('templates/parts/image', false, [ 'id' => $photo , 'size' => 'com-theme-photo-gallery', 'classes' => 'slide shrink-0 h-[44.444vw] w-auto', 'parallax' => false  ] ); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <!--<div class="absolute top-1/2 -translate-y-1/2 left-0 w-full pointer-events-none select-none z-2">
            <div class="mx-2/12 flex justify-between">
                <div class="w-50 h-50 rounded-full bg-dove flex items-center justify-center -translate-x-1/2 pointer-events-auto cursor-pointer [&.inactive]:cursor-default [&.inactive]:opacity-0" data-arrow-prev>
                    <svg class="w-[1.8rem] h-[12.5rem] rotate-180"><use xlink:href="#icon-swiper-arrow"></use></svg>
                </div>
                <div class="w-50 h-50 rounded-full bg-dove flex items-center justify-center  translate-x-1/2 pointer-events-auto  cursor-pointer [&.inactive]:cursor-default [&.inactive]:opacity-0" data-arrow-next>
                    <svg class="w-[1.8rem] h-[12.5rem]"><use xlink:href="#icon-swiper-arrow"></use></svg>
                </div>
            </div>
        </div>-->
    </div>
    </section>
<?php }
