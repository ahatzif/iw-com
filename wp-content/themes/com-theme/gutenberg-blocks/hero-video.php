<?php
// Title: Hero Video
if( ! empty( $video = get_field( 'video' ) ) ){
?>

<div class="bg-paper text-white relative" data-module-vh data-module-hero-video>
    <video data-hero-video="video" data-src="<?php echo $video; ?>" <?php if( ! empty( $mobileSrc = get_field( 'mobile_video') ) ) echo 'data-mobile-src'; ?> muted autoplay loop class="object-cover w-full h-full"></video>

    <?php if( ! empty( $title = get_field( 'title' ) ) ) { ?>
        <div class="absolute inset-0">
            <div class="absolute bottom-0 w-full" >
                <div class="px-1/12 py-60">
                    <h1 data-hero-video="title" class="text-[min(6.575vw,7.2rem)] leading-[1.238889]  text-center font-heading text-center w-full opacity-0 overflow-hidden"><?php echo $title; ?></h1>
                </div>
            </div>
        </div>
    <?php } ?>
    <div data-main-menu-trigger class="h-header-height absolute bottom-0 left-0 w-full text-base-heading"></div>
</div>
<?php }
