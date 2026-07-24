<?php
// Title: Map Promo

$promo = get_field( 'promo' );

$imagePosition = $promo[ 'iframe_position' ];
?>

<section class="<?php echo esc_attr( com_theme_block_style_classes() ); ?>">
<div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?>">
    <div class="mx-1/12">
        <div class="sm:flex">
            <div class="sm:w-5/12 <?php echo $imagePosition === 'right' ? 'order-first sm:order-last sm:ml-1/12' : 'sm:mr-1/12' ?>">
                <div class="aspect-[1.5] bg-black bg-opacity-10 grayscale">
                    <?php echo str_replace( '<iframe ', '<iframe class="w-full h-full"', $promo[ 'iframe' ]); ?>
                </div>
            </div>
            <div class="sm:w-3/12 flex items-center py-75 mr-1/12 sm:mr-0 <?php if( $imagePosition === 'right' ) echo 'ml-1/12' ?>">
                <div>
                    <?php if( ! empty( $title = $promo[ 'title' ] ) ) { ?>
                    <h3 class="text-21 font-normal"><?php echo $title; ?></h3>
                    <?php } ?>

                    <?php if( ! empty( $text = $promo[ 'text' ] ) ) { ?>
                        <p class="mt-10 text-14 leading-[1.5714285714]"><?php echo $text; ?></p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
