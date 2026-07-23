<?php if( has_nav_menu('social') ) { ?>
<div class="space-y-10">
    <div class="text-[18px] font-heading font-bold"><?php _e('FOLLOW US ON SOCIAL MEDIA', 'com-theme'); ?></div>
    <nav>
        <ul class="flex w-full space-x-10">
        <?php foreach ( wp_get_nav_menu_items( 'Social Media' ) as $item ) {
            $hostParts = explode( '.', strtolower( parse_url($item->url)['host'] ) );
            $icon = $hostParts[ count( $hostParts ) - 2 ];
            $iconExists = trim( @com\theme::svg( $icon, '/assets/images/svg/', false ) ) !== '';
            if( $iconExists ) {
                ?>
                <li>
                    <a href="<?php echo $item->url; ?>" target="_blank" rel="noopener nofollow" class="flex items-center justify-center rounded-full w-40 h-40 border border-current relative" title="<?php echo $item->title; ?>">
                        <svg class="fill-current w-[75%] h-[75%] absolute-center"><use xlink:href="#icon-<?php echo $icon; ?>"></use></svg>
                    </a>
                </li>
            <?php } ?>
        <?php } ?>
        </ul>
    </nav>
</div>
<?php } ?>
