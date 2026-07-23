<?php
/**
 * Title: {{block-name}}
 * {{acf-json-file-path}}
 * {{js-module-file-path}}
 *
 **/
$block_colors = apply_filters( 'theme_block_colors', '' );
$block_spacings = apply_filters( 'theme_block_spacings', [ 'desktop' => [ 'mt' => 'normal', 'mb' => 'normal' ] ] );
$block_classes = trim( implode( ' ', array_filter( [
	is_string( $block_colors ) ? $block_colors : '',
	is_string( $block_spacings ) ? $block_spacings : '',
] ) ) );
?>
<section{{data-module-attribute}} class="<?php echo esc_attr( $block_classes ); ?>">
    <div class="<?php echo esc_attr( apply_filters( 'theme_block_wrapper', 'page-wrapper' ) ); ?>">
        <h1 class="font-bold text-[11px] uppercase">{{block-name}}</h1>
        <?php if ( ! empty( $title = get_field( 'title' ) ) ) { ?>
            <div><?php echo wp_kses_post( $title ); ?></div>
        <?php } ?>
    </div>
</section>
