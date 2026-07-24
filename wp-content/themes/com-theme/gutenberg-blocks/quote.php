<?php // Title: Quote ?>

<?php if( ! empty( $title = get_field( 'title' ) ) ) { ?>
    <section class="<?php echo esc_attr( com_theme_block_style_classes() ); ?>">
    <div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?>">
        <div>
            <div class="flex">
                <span class="text-[12rem] font-bold leading-none mr-30">"</span>
                <div>
                    <div class="text-[4.2rem] font-heading"><?php echo $title;?></div>
                    <?php if( ! empty( $quoter = get_field( 'quoter' ) )  ) { ?>
                        <div class="tex-[1.6rem] mt-10">- <?php echo $quoter ?></div>
                    <?php } ?>
                </div>
            </div>

        </div>
    </div>
    </section>
<?php } ?>
