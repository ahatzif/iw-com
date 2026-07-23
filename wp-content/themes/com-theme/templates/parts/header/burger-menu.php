<div class="fixed w-full h-full top-0 left-0 z-[11] bg-black text-white current text-red items-center hidden [&.ready]:flex group-[.admin-bar:not(.hide-admin-bar)]:top-[var(--wp-admin--admin-bar--height)]" data-module-burger-menu>
    <div class="absolute top-0 left-0 w-full flex justify-between items-center h-[var(--header-height)] px-1/24">
        <?php get_template_part('templates/parts/header/logo', false, []); ?>
        <?php get_template_part('templates/parts/header/burger-button', false, []); ?>
    </div>
    <div class="w-full flex justify-between flex-wrap px-1/24">
        <nav><?php wp_nav_menu( ['container' => false, 'items_wrap' => '<ul class="text-[3rem] leading-none z-2 [&_li]:relative font-light [&_li]:py-10 [&_li:first-child]:pt-0 [&_li:last-child]:pb-0 [&:hover_li]:opacity-10 [&_li:hover]:opacity-100 [&_li]:transition [&_li]:duration-300 [&_a]:block">%3$s</ul>', 'theme_location' => 'main' ] ); ?></nav>
        <div class="space-y-30 w-6/24 h-full flex flex-col justify-end">
            <div class="space-y-10">
                <div class="text-[18px] font-bold">CONTACT</div>
                <p class="text-[14px]">Karaiskaki 27-29 & Mikonos 1 GR-10554, Athens <br/>T: 0030 210 3255520 – 521</p>
            </div>
            <div class="border-t border-t-current pt-30 ">
            <?php get_template_part('templates/parts/social-media-menu', false, []); ?>
            </div>
            <div class="space-y-10 border-t border-t-current pt-30 text-[12px]">
                <div class="text-[18px] font-bold"><?php get_template_part('templates/parts/header/language-menu', false, []); ?></div>

            </div>
        </div>
    </div>
</div>
