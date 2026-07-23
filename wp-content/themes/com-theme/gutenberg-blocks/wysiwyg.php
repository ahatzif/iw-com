<?php // Title: Wysiwyg ?>
<?php if( ! empty( $text = get_field( 'text' ) ) ) { ?>
    <div class="page-wrapper my-100">
        <div class="mx-1/12 md:mx-2/12 md:w-8/12 <?php echo get_prose(); ?>">
            <?php echo $text;?>
        </div>
    </div>
<?php } ?>
