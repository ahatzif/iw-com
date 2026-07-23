<?php
function hide_breadcrumb_404()
{
    return false;
}
add_filter('show_breadcrumb', 'hide_breadcrumb_404');
add_filter('header_margin', function () {
    return false;
});
get_header();
remove_filter('show_breadcrumb', 'hide_breadcrumb_404');
$background = get_field('404_background', 'options');


?>


<div class="h-screen relative flex items-center" data-module-vh>
    <?php get_template_part('templates/parts/image', false, ['id' => $background['ID'], 'size' => 'full', 'classes' => 'object-cover absolute top-0 left-0 w-full h-full hidden md:block']); ?>
    <?php get_template_part('templates/parts/image', false, ['id' => $background['original_image']['ID'], 'size' => 'com-theme-full-mobile', 'classes' => 'object-cover absolute top-0 left-0 w-full h-full md:hidden']); ?>
    <div class="absolute inset-0 bg-black" style="opacity: <?php echo get_field('404_overlay', 'options') / 100; ?>;"></div>
    <div class="absolute inset-0">
        <div class="h-[calc(var(--header-height))] mb-[4.2rem]"></div>
        <?php get_template_part('templates/parts/breadcrumbs', false, []); ?>
    </div>
    <div class="w-full relative">
        <div class="page-wrapper text-center space-y-40">
            <h1 class="text-white text-titles-extra-bold text-center"><?php the_field('404_title', 'options'); ?></h1>
            <?php get_template_part('templates/parts/button', null, [
                'tag' => 'a',
                'link' => home_url('/'),
                'outline' => true,
                'color' => 'white',
                'text' => get_field('404_button_text', 'options'),
            ]); ?>
        </div>
    </div>
</div>

<?php get_footer();
