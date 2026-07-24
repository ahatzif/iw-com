<?php
/**
 * Title: {{block-name}}
 * {{acf-json-file-path}}
 * {{js-module-file-path}}
 *
 **/
$block_classes = function_exists( 'com_theme_block_style_classes' )
	? com_theme_block_style_classes()
	: trim( apply_filters( 'theme_block_colors', 'text-blue' ) . ' ' . apply_filters( 'theme_block_spacings', [ 'desktop' => [ 'mt' => 'normal', 'mb' => 'normal' ] ] ) );
$wrapper_classes = function_exists( 'com_theme_block_wrapper_classes' )
	? com_theme_block_wrapper_classes()
	: trim( (string) apply_filters( 'theme_block_wrapper', 'page-wrapper' ) );
?>
<section{{data-module-attribute}} class="<?php echo esc_attr( $block_classes ); ?>">
    <div class="<?php echo esc_attr( $wrapper_classes ); ?>">
        <h1 class="font-bold text-[11px] uppercase">{{block-name}}</h1>
        <?php if ( ! empty( $title = get_field( 'title' ) ) ) { ?>
            <div><?php echo wp_kses_post( $title ); ?></div>
        <?php } ?>
    </div>
</section>
