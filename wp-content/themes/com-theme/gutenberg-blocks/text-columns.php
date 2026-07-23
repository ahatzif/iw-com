<?php
// Title: Text Columns
if( ! empty( $columns = get_field( 'columns' ) ) ) {
    $wrapper = get_field( 'wrapper' );
    $numberOfColumns = get_field( 'number_of_columns' );
    if( $wrapper === 'small' ) {
        $numberOfColumns = "3";
    }
    $gap = "md:gap-30";
    if( $numberOfColumns === "3" && $wrapper === 'default' ){
        $gap = "md:gap-[6.25vw]";
    } else if( $numberOfColumns === "3" && $wrapper === 'small' ){
        $gap = "md:gap-60";
    }
?>
<div class="page-wrapper <?php echo get_field( 'background' ) === 'white' ? 'py-100 bg-white' : 'my-100'; ?> ">
    <div class="mx-1/12 <?php if( $wrapper !== 'default' ) echo 'lg:mx-2/12';  ?>">
        <?php if( ! empty( $title = get_field( 'title' ) ) ){  ?>
        <h3 class="text-21 mb-50"><?php echo $title; ?></h3>
        <?php } ?>
        <div class="grid <?php echo $numberOfColumns === "3" ? 'grid-cols-2 md:grid-cols-3' : 'grid-cols-2 md:grid-cols-4';  ?> gap-20 <?php echo $gap; ?>">
        <?php foreach ( $columns as $column) { ?>
            <?php
            $link = $column[ 'link' ];
            $photo = $column[ 'photo' ];

            ?>
            <div class="<?php echo empty( $photo) ? 'border-t border-t-[1px] border-t-black pt-20' : ''; ?> space-y-20" >
                <?php if( ! empty( $photo ) ) { ?>
                <div class="relative aspect-[0.9523809524]">
                    <?php if( ! empty( $link) ) { ?><a href="<?php echo $link[ 'url' ]; ?>" target="<?php echo $link['target'] ?>"><?php } ?>
                    <?php get_template_part('templates/parts/image', false, [ 'id' => $photo, 'size' => 'full' ] ); ?>
                    <?php if( ! empty( $link) ) { ?></a><?php } ?>
                </div>
                <?php } ?>

                <?php if( ! empty( $column['title'] ) ) { ?>
                <div class="text-21 leading-[1.4285714286]"><?php echo $column['title']; ?></div>
                <?php } ?>
                <?php if( ! empty( $column['text'] ) ) { ?>
                <div class="text-14 font-light leading-[1.5714285714]"><?php echo $column['text']; ?></div>
                <?php } ?>
                <?php if( ! empty( $link = $column[ 'link' ] ) ) { ?>
                <div>
                    <a href="<?php echo $link[ 'url' ]; ?>" target="<?php echo $link['target'] ?>" class="text-blue font-bold text-12 leading-[1.3333333333]"><?php echo com\theme::remove_accents( $link[ 'title' ] ); ?></a>
                </div>
                <?php } ?>
            </div>
        <?php } ?>
        </div>
    </div>
</div>
<?php }
