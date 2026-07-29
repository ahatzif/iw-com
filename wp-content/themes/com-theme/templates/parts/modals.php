<?php

$modals = get_posts( [ 'post_type' => 'modal', 'post_status' => 'publish', 'posts_per_page' => -1 ] );
global $post;
foreach ( $modals as $post ){
    setup_postdata( $post );
?>
<div data-module-modal="<?php echo $post->post_name; ?>" data-module-tabs class="fixed z-20 top-0 left-0 w-full h-full items-center overflow-y-scroll transition duration-500 opacity-0 pointer-events-none group modal [&.active]:opacity-100 [&.active]:pointer-events-auto">
    <div class="min-h-[100vh]" data-modal="close">
        <div class="py-90 relative bg-blue/30 min-h-[100vh] flex items-center pointer-events-none">
            <div class="w-full">
                <div class="mx-1/12">
                    <div class="relative py-90 px-1/12 md:px-[11.3rem] bg-white max-w-[57.6rem] mx-auto items-center group-[.modal.active]:pointer-events-auto" data-modal="content">
                        <div data-tabs="tabs">
                            <?php the_content(); ?>
                        </div>
                        <div class="absolute right-30 top-30 cursor-pointer" data-modal="close">
                            <div class="absolute w-[40px] h-[40px] top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></div>
                            <svg class="relative w-[1.3rem] h-[1.3rem]" width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <line x1="1.09968" y1="0.716233" x2="11.7063" y2="11.3228" stroke="black" stroke-width="2"/>
                                <line x1="11.7064" y1="0.707107" x2="1.09977" y2="11.3137" stroke="black" stroke-width="2"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php } wp_reset_postdata();

