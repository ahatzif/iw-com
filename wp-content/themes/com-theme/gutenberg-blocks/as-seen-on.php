<?php
// Title: As Seen On
$columns = get_field( 'columns' );
if( ! empty( $columns ) ) {
?>
<section class="<?php echo esc_attr( com_theme_block_style_classes() ); ?>">
<div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?>">
    <div class="px-1/12 md:px-2/12">
        <?php if( ! empty( $title = get_field( 'title' ) ) ) { ?>
        <h2 class="text-center text-14 mb-40 font-bold leading-[1.4285714286]"><?php echo $title;?></h2>
        <?php } ?>
        <div class="grid grid-cols-4 gap-40">
        <?php foreach ( $columns as $column ) { ?>
            <div class="col-span-1 space-y-10">
                <div class="h-50 flex items-center">
                    <div class="w-full h-full" style="height: <?php echo ( (int) $column[ 'icon_height' ] ) / 10 ?>rem;">
                        <?php get_template_part('templates/parts/image', false, [ 'id' => $column[ 'icon' ], 'classes' => 'h-full w-full object-contain', 'size' => 'full', 'parallax' => false ] ); ?>
                    </div>
                </div>
                <?php if( ! empty( $column[ 'text' ] ) ) { ?>
                <p class="text-14 leading-[1.5714285714] text-center font-light"><?php echo $column[ 'text' ]; ?></p>
                <?php } ?>
            </div>
        <?php } ?>
        </div>
    </div>
</div>
</section>
<?php }
