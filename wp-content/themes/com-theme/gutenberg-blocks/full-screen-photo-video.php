<?php // Title: Fullscreen Photo / Video
if( ! empty( $video = get_field( 'video' ) ) || ! empty( $photo = get_field( 'photo') ) ){
?>
    <section class="<?php echo esc_attr( com_theme_block_style_classes( 'bg-blue text-white', [] ) ); ?> relative" data-module-vh >
        <?php if( ! empty( $video ) ) { ?>
        <video src="<?php echo $video; ?>" muted autoplay loop class="absolute top-0 left-0 object-cover w-full h-full"></video>
        <?php } else if( ! empty( $photo ) ) {
            get_template_part('templates/parts/image', false, [ 'id' => $photo, 'size' => 'com-theme-featured-image', 'additionalClasses' => 'mix-blend-multiply	' ] );
        } ?>
        <?php if( ! empty( $overlay = get_field( 'overlay' ) ) ) { ?>
            <div class="absolute inset-0" style="background: <?php echo $overlay;?>;"></div>
        <?php } ?>
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="px-1/12 md:px-2/2 py-60">
                <div class="flex flex-col items-center text-center">
                    <?php if( ! empty( $title = get_field( 'title' ) ) ) { ?>
                    <h1 class="text-72 text-center font-heading text-center w-full"><?php echo $title; ?></h1>
                    <?php } ?>
                    <?php if( ! empty( $text = get_field( 'text' ) ) ) { ?>
                        <p class="text-21 mt-20 font-light prose text-current max-w-[780px] mx-auto"><?php echo $text; ?></p>
                    <?php } ?>
                    <?php if( ! empty( $link = get_field( 'link' ) ) ) { ?>
                        <div class="mt-50">
                        <?php get_template_part('templates/parts/button', false, [ 'link' => $link[ 'url' ], 'text' => $link[ 'title' ], 'color' => 'white-outlined', ]); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
<?php }
