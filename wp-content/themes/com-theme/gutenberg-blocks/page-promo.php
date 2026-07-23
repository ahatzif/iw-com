<?php
// Title: Page Promo

$page = get_field( 'page' );
$imagePosition = get_field( 'image_position' );
$background = get_field( 'background' );
$title = get_field( 'title' );

$photoID = get_field( 'select_photo' ) === 'custom_photo' ? get_field( 'custom_photo' ) : get_post_thumbnail_id( $page );

if( ! empty( $page ) ) {
    $icon = get_field( 'page_icon', $page );
    $linkText = get_field( 'link_text' );
    if( empty( $linkText ) ) $linkText = $page->post_title;
    ?>

    <div class="page-wrapper">
        <div class="bg-white py-100">
            <div class="md:flex" style="background-color: <?php echo $background; ?>">
                <div class="mx-1/12 md:mx-0 md:w-5/12 <?php echo $imagePosition === 'right' ? 'order-first md:order-last' : 'md:ml-1/12'; ?>">
                    <div class="aspect-[1.0714285714] bg-white relative min-h-full">
                        <?php get_template_part('templates/parts/image', false, [ 'id' => $photoID, 'size' => 'com-theme-promo-half', 'alt' => $title ]); ?>
                    </div>
                </div>
                <div class="pt-1/12 md:pt-0 md:w-6/12 px-2/12 md:px-1/12 bg-white relative">
                    <div class="flex flex-col items-center justify-center text-center space-y-30 h-full">
                        <?php if( ! empty( $icon ) ) { ?>
                            <img class="h-[5rem] w-auto" src="<?php echo $icon[ 'url' ] ?>" alt="<?php echo $title; ?>">
                        <?php } ?>
                        <?php if( ! empty( $title = get_field( 'title' ) ) ) { ?>
                            <h2 class="text-42 font-heading leading-[1.1904761905]"><a href="<?php echo get_permalink( $page ); ?>"><?php echo $title; ?></a></h2>
                        <?php } ?>
                        <a class="text-blue font-bold hover:underline text-14 leading-[1.4285714286]" href="<?php echo get_permalink( $page ); ?>"><?php echo com\theme::remove_accents( $linkText ); ?></a>
                    </div>
                    <div class="absolute md:top-0 md:h-full md:w-20 <?php echo $imagePosition === 'right' ? 'left-0' : 'right-0'; ?>" style="background-color: <?php echo $background; ?>"></div>
                </div>
            </div>
        </div>
    </div>

<?php }
