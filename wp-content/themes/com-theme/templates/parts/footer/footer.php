<footer data-scroll-section class="bg-[#111] text-white">
    <div class="px-1/24 py-50">
        <div class="flex justify-between">
            <?php com\theme::menu_class( ['container' => false, 'items_wrap' => '<ul class="text-[1.8rem] font-heading leading-none space-y-20">%3$s</ul>', 'theme_location' => 'main', 'li_class' => "flex"] ); ?>
            <div class="w-6/24 space-y-50 ">
                <?php get_template_part('templates/parts/newsletter-form', false, []); ?>
                <?php if (has_nav_menu('social')) { ?>
                <div class="space-y-30">
                    <?php get_template_part('templates/parts/social-media-menu', false, []); ?>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="border-t border-white border-opacity-10 py-20 bg-black">
        <div class="md:flex justify-between text-[11px] px-1/24">
            <div class="md:flex justify-between w-full">
                <div class="flex flex-col md:flex-row md:space-x-10">
                    <div><?php printf( __( "&copy; %d %s - All rights reserved",  'com-theme'), date('Y' ), get_bloginfo( 'name' ) ); ?></div>
                    <ul class="order-first md:order-last md:flex py-10 md:py-0 md:space-x-10 [&_li]:inline-block [&_li]:transition [&_li]:duration-300 [&_li]:hover:opacity-50 [&_li]:before:inline-block [&_li]:before:content-['|'] [&_li]:before:mr-10">
                    <?php wp_nav_menu( ['container' => false, 'items_wrap' => '%3$s', 'theme_location' => 'copyright' ] ); ?>
                    </ul>
                </div>
                <div><?php printf( __( 'CREATED BY <a href="%s" target="_blank" class="transition duration-300 hover:opacity-50">INTERWEAVE</a>', 'com-theme'), '' ); ?></div>
            </div>
        </div>
    </div>
</footer>
