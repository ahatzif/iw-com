<?php // Title: Quote ?>

<?php if( ! empty( $title = get_field( 'title' ) ) ) { ?>
    <div class="page-wrapper my-100">
        <div>
            <div class="flex">
                <span class="text-[12rem] font-bold leading-none mr-30">"</span>
                <div>
                    <div class="text-blue text-[4.2rem] font-heading"><?php echo $title;?></div>
                    <?php if( ! empty( $quoter = get_field( 'quoter' ) )  ) { ?>
                        <div class="text-blue tex-[1.6rem] mt-10">- <?php echo $quoter ?></div>
                    <?php } ?>
                </div>
            </div>

        </div>
    </div>
<?php } ?>
