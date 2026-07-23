<header data-module-page-header class="px-1/24 text-[14px] group-[.white-header:not(.has-scrolled)]:text-white text-black group fixed w-full z-10 h-[var(--header-height)] group-[.admin-bar:not(.hide-admin-bar)]:top-[var(--wp-admin--admin-bar--height)]">
    <div class="bg-white absolute inset-0 transition origin-top duration-300 -translate-y-full group-[.has-scrolled]:translate-y-0 group-[.force-scrolled]:scale-y-100"></div>
    <div class="relative flex justify-between items-center h-full">
        <?php get_template_part('templates/parts/header/logo', false, []); ?>
        <div class="flex items-center space-x-60">
            <?php get_template_part('templates/parts/header/main-menu', false, []); ?>
            <div class="flex space-x-20">
                <?php get_template_part('templates/parts/header/language-menu', false, []); ?>
                <?php get_template_part('templates/parts/header/search-form', false, []); ?>
                <?php get_template_part('templates/parts/header/burger-button', false, []); ?>
            </div>
        </div>
    </div>
</header>
<?php get_template_part('templates/parts/header/burger-menu', false, []); ?>
