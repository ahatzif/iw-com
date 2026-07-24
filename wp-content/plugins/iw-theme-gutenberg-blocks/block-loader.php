<?php if( isset( $block['data']['preview_image_help'] )  ) { ?>
    <img src="<?php echo $block['data']['preview_image_help']; ?>" style="width:100%; height:auto;">
<?php } else {
    get_template_part( $block[ 'path' ] );
}
