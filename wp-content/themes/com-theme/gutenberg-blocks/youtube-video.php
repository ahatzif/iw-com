<?php // Title: Youtube Video

$get_youtube_video = get_field('get_youtube_video');


$getVideoFrom = get_field('from');

if ($getVideoFrom !== 'post') {
    $youtube_video_id = VideoUrlParser::get_youtube_id(get_field('youtube_video_url')); // Get ID from YouTube Video URL
} else {
    $youtube_video_id = get_field('youtube_video', get_the_ID());

    if (empty($youtube_video_id)) {
        if (!empty($youtube_short = get_field('youtube_short', get_the_ID()))) {
            $youtube_video_id = VideoUrlParser::get_url_id($youtube_short);
        }
    }
}

if (!empty($youtube_video_id)) {
    if(get_field('youtube_thumbnail',get_the_id())){
        $thumbnail_url = get_field('youtube_thumbnail' ,get_the_id());
    }else{
        $thumbnail_url = "https://img.youtube.com/vi/" . $youtube_video_id . "/maxresdefault.jpg";
    } 
?>
    <section class="group md-max:pt-80 <?php echo esc_attr( com_theme_block_style_classes( '', [ 'desktop' => [ 'mt' => '80', 'mb' => '80' ] ] ) ); ?>" data-module-youtube-player>
        <div class="md:page-wrapper">
            <div class="flex flex-wrap flex-gap-20">
                <div class="w-full md:w-10/12 md:mx-1/12">
                    <div class="relative aspect-[1.777777]">
                        <div class="absolute inset-0 flex items-center transition duration-300 justify-center" data-youtube-player="button">
                            <img data-src="<?php echo $thumbnail_url; ?>" alt="" data-lazy data-parallax class="w-full h-full object-cover absolute top-0 left-0">
                            <svg class='relative w-50 h-50 fill-white cursor-pointer'>
                                <use xlink:href='#icon-play-button'></use>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-wrapper mt-60">
            <?php if (!empty($caption = get_field('caption'))) { ?>
                <p class="w-full md:w-10/12 md:mx-1/12 text-mid-titles"><?php echo $caption; ?></p>
            <?php } ?>
        </div>



        <!-- modal-->
        <div class="z-[100] fixed inset-0 bg-blue/30 py-80 opacity-0 pointer-events-none [&.active]:pointer-events-auto [&.active]:opacity-100 transition duration-300" data-youtube-player="modal">
            <div class="flex items-center justify-center h-full" data-youtube-player="modal-inner">
                <div class="w-full">
                    <div class="page-wrapper">
                        <div class="flex flex-gap-20">
                            <div class="w-full lg:mx-1/12 lg:w-10/12 h-full aspect-[1.77777] [&_iframe]:w-full [&_iframe]:h-full" data-youtube-player="iframe-container">
                                <iframe data-youtube-player="iframe" width="560" height="315" data-src="https://www.youtube.com/embed/<?php echo $youtube_video_id; ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="absolute top-0 right-0">
                    <button class="fancybox-close group" data-youtube-player="close">
                        <svg class="group-[.fancybox-close:hover]:opacity-70 group-[.fancybox-close:hover svg]:fill-white">
                            <use xlink:href="#icon-close-fat"></use>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

    </section>
<?php }
