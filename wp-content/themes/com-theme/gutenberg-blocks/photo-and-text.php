<?php // Title: Photo And Text ?>

<section class="<?php echo esc_attr( com_theme_block_style_classes() ); ?>">
<div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?>">
    <div class="mx-1/12 md:mx-2/12 md:w-8/12">
        <div class="flex flex-wrap">
            <?php if( ! empty( $image = get_field( 'image' ) ) ) {?>
                <div class="w-full sm:w-5/12 md:w-4/12">
                    <div class="relative">
                        <?php get_template_part('templates/parts/image', false, [ 'id' => $image, 'classes' => 'w-full h-auto' ]); ?>
                    </div>
                </div>

            <?php } ?>
            <?php if( ! empty( $text = get_field( 'text' ) ) ) {?>
            <div class="w-full mt-50 sm:mt-0 sm:ml-1/12 sm:w-4/12 md:w-3/12  <?php echo get_prose(); ?>">
                <?php echo $text; ?>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
</section>

