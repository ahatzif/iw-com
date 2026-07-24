<?php // Title: Video Player
if (! empty($video = get_field("video"))) {
   $meta = wp_get_attachment_metadata($video["file"]["ID"]);
   $blendModes = ["mix-blend-color", "mix-blend-luminosity", "mix-blend-multiply"];
   $mixBlendMode = $video["blend_mode"];
   $backgroundColor  = $video["background_color"];
   $overlay_color = $video["overlay_color"];
   $videoText = get_field("text");
   if (! empty($video["fullscreen"])) {
       $videoSize = "";
       $fullScreen = "data-module-vh";
   } else {
       $videoIndentation = get_field("video_indentation");
       $indentationPosition = get_field("indentation_position");
       $videoSize = empty($videoText) ? "pt-[100%] md:pt-[45.833333%]" : "pt-[100%] md:pt-[44.44444%]";
       if ($videoIndentation) {
           if ($indentationPosition === "left") {
               $videoSize .= " ml-[calc(100vw*0.5/12)] md:ml-1/24";
           } elseif ($indentationPosition === "right") {
               $videoSize .= " mr-[calc(100vw*0.5/12)] md:mr-1/24";
           }
       }
   }
?>
   <section class="<?php echo esc_attr( com_theme_block_style_classes( $backgroundColor ) ); ?>">
       <div data-module-video-player class="overflow-hidden group video-player relative volume-on <?php echo $videoSize ?>  <?php if ($video["autoplay"]) echo "playing"; ?>" <?php if (! empty($video["fullscreen"])) echo "data-module-vh"; ?>>
           <video data-video-player="video" class="absolute top-0 left-0 w-full h-full object-cover " playsinline preload="metadata" width="<?php echo $meta["width"]; ?>" height="<?php echo $meta["height"]; ?>"
               <?php if (empty($video["disable_lazy_load"])) echo "data-lazy data-"; ?>src="<?php echo $video["file"]["url"]; ?>#t=0.1"
               <?php if ($video["autoplay"]) echo 'muted loop data-autoplay="true"'; ?>
               <?php if (! empty($video["file_mobile"])) { ?> data-mobile-src="<?php echo $video["file_mobile"]["url"]; ?>#t=0.1" <?php } ?>></video>
           <?php if ($video["cover"] && ! $video["autoplay"]) { ?>
               <div class="absolute inset-0 transition group-[.video-player.remove-cover]:opacity-0">
                   <?php get_template_part("templates/parts/image", false, ["id" => $video["cover"], "size" => "full"]); ?>
               </div>
           <?php } ?>
           <?php if ($mixBlendMode  !== "normal") { ?>
               <div class="absolute inset-0 pointer-events-none <?php echo $overlay_color; ?> <?php if (in_array($mixBlendMode, $blendModes)) echo $mixBlendMode; ?>"></div>
           <?php } ?>
           <?php if (! $video["autoplay"]) { ?>
               <div class="absolute inset-0 flex items-center justify-center">
                   <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 177.2 211" class="fill-white transition-all opacity-0 animate-pause w-auto h-[22%]">
                       <path d="M117.9,0h36.9c6.1,0.1,11,5,11.1,11.1v188.8c-0.1,6.1-5,11-11.1,11.1h-36.9c-6.1-0.1-11-5-11.1-11.1V11.1 C106.9,5,111.8,0.1,117.9,0z M22.3,0h36.8c6.2,0,11.2,4.9,11.3,11.1v188.8c-0.1,6.2-5.1,11.1-11.3,11.1H22.3 c-6.1-0.1-11-5-11.1-11.1V11.1C11.3,5,16.2,0.1,22.3,0z" />
                   </svg>
               </div>
               <div class="absolute inset-0 flex items-center justify-center cursor-pointer" data-video-player="toggle-play">
                   <svg xmlns="http://www.w3.org/2000/svg" width="177.197" height="211.026" viewBox="0 0 177.197 211.026" class="fill-white transition-all opacity-50 group-[.video-player.remove-cover]:opacity-0 animate-play w-auto h-[22%]">
                       <path d="M230.994,91.543v195.68a7.682,7.682,0,0,0,11.529,6.674l161.865-98.016a7.555,7.555,0,0,0,0-13.114L242.523,85.091a7.516,7.516,0,0,0-11.529,6.452Z" transform="translate(-230.994 -83.915)" fill-rule="evenodd" />
                   </svg>
               </div>
               <div class="absolute bottom-[3.6458333333vw] left-[6.25vw] right-[6.25vw] transition-opacity duration-[800ms] opacity-0 pointer-events-none group-[.video-player.remove-cover.pointer-on]:pointer-events-auto group-[.video-player.remove-cover.pointer-on]:opacity-100">
                   <div class="text-white text-16 mb-[1.3020833333vw]" data-video-player="time">00:00 / 00:00</div>
                   <div class="flex items-center space-x-[10px] md:space-x-40">
                       <div class="h-[2.5rem] relative  cursor-pointer flex-grow mr-20 md:mr-40">
                           <div class="absolute inset-0" data-video-player="set-current-time"></div>
                           <div class="absolute inset-0 pointer-events-none bg-black bg-opacity-50 origin-center  scale-y-[0.2] md:scale-y-100"></div>
                           <div class="absolute inset-0 pointer-events-none overflow-hidden ">
                               <div class="absolute w-full h-full top-0 right-full origin-center scale-y-[0.2] md:scale-y-100 bg-blue" data-video-player="progress-bar"></div>
                           </div>
                           <div class="absolute -left-[1.2rem] -right-[1.2rem] h-full pointer-events-none">
                               <div class="h-[2.5rem] w-[2.5rem] bg-white rounded-full pointer-events-auto" data-video-player="progress-handle">
                               </div>
                           </div>
                       </div>
                       <div data-video-player="toggle-play" class="cursor-pointer relative">
                           <svg xmlns="http://www.w3.org/2000/svg" width="177.197" height="211.026" viewBox="0 0 177.197 211.026" class="h-40 w-auto fill-white transition-all opacity-100 group-[.video-player.playing]:opacity-0  scale-50 md:scale-100">
                               <path d="M230.994,91.543v195.68a7.682,7.682,0,0,0,11.529,6.674l161.865-98.016a7.555,7.555,0,0,0,0-13.114L242.523,85.091a7.516,7.516,0,0,0-11.529,6.452Z" transform="translate(-230.994 -83.915)" fill-rule="evenodd" />
                           </svg>
                           <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 177.2 211" class="absolute top-0 left-0 w-full h-full fill-white transition-all opacity-0 group-[.video-player.playing]:opacity-100  scale-50 md:scale-100">
                               <path d="M117.9,0h36.9c6.1,0.1,11,5,11.1,11.1v188.8c-0.1,6.1-5,11-11.1,11.1h-36.9c-6.1-0.1-11-5-11.1-11.1V11.1 C106.9,5,111.8,0.1,117.9,0z M22.3,0h36.8c6.2,0,11.2,4.9,11.3,11.1v188.8c-0.1,6.2-5.1,11.1-11.3,11.1H22.3 c-6.1-0.1-11-5-11.1-11.1V11.1C11.3,5,16.2,0.1,22.3,0z" />
                           </svg>
                       </div>
                       <div data-video-player="volume" class="cursor-pointer relative group-[.is-mobile]:hidden">
                           <svg xmlns="http://www.w3.org/2000/svg" width="40.272" height="40.272" viewBox="0 0 40.272 40.272" class="w-40 h-40 fill-white transition-all opacity-0 group-[.volume-on]:opacity-100">
                               <path d="M236.313,350.967v21.139a.807.807,0,0,1-1.23.7c-2.454-1.5-4.929-3-7.39-4.488a1.988,1.988,0,0,0-1.167-.329h-5.147a1.566,1.566,0,0,1-1.427-1.672v-9.562a1.551,1.551,0,0,1,1.427-1.658h5.147a2.222,2.222,0,0,0,1.167-.329q3.691-2.273,7.39-4.5a.8.8,0,0,1,1.23.7Zm4.135,2.016,1.821-1.844a.536.536,0,0,1,.8.028,16.246,16.246,0,0,1-.141,20.753.574.574,0,0,1-.808.016l-2.018-2.059a.585.585,0,0,1,.015-.8c4.443-4.1,4.021-10.72.295-15.336a.574.574,0,0,1,.042-.757Zm4.655-4.731,1.645-1.658a.511.511,0,0,1,.815.058c6.835,8.889,7.678,20.7-.211,29.771a.527.527,0,0,1-.787.015l-1.625-1.629a.574.574,0,0,1-.035-.744c5.949-7.69,6.006-17.009.141-25.069a.57.57,0,0,1,.057-.744Zm4.647-4.717,1.934-1.988a.527.527,0,0,1,.787.044c10.793,12.034,9.915,28.457-.14,39.905a.563.563,0,0,1-.788.013l-2.08-2.129a.545.545,0,0,1-.028-.757c8.508-9.947,9.507-22.811.267-34.345a.552.552,0,0,1,.049-.744Z" transform="translate(-219.952 -341.392)" fill-rule="evenodd" />
                           </svg>
                           <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 40.3 40.3" style="enable-background:new 0 0 40.3 40.3;" xml:space="preserve" class="w-40 h-40 fill-white inset-0 absolute transition-all  opacity-100 group-[.volume-on]:opacity-0">
                               <path d="M27.6,5.3c0,0-0.1-0.1-0.1-0.1C27.3,5,27,5,26.8,5.2l-1.6,1.7v0c-0.2,0.2-0.2,0.5-0.1,0.7c0.7,1,1.3,1.9,1.8,2.9L29.4,8 C28.9,7.1,28.3,6.1,27.6,5.3z" />
                               <path d="M16.4,9.6c0-0.2,0-0.3-0.1-0.5c-0.2-0.4-0.7-0.5-1.1-0.2c-2.5,1.5-4.9,3-7.4,4.5c-0.4,0.2-0.8,0.3-1.2,0.3H1.4 c-0.9,0.1-1.5,0.8-1.4,1.7v9.6c-0.1,0.9,0.6,1.6,1.4,1.7h5.1c0.4,0,0.8,0.1,1.2,0.3c0.6,0.3,1.1,0.7,1.7,1l6.9-6.9V9.6z" />
                               <path d="M23.1,9.8C23.1,9.8,23.1,9.7,23.1,9.8c-0.3-0.2-0.6-0.2-0.8,0l-1.8,1.8l0,0c-0.2,0.2-0.2,0.5,0,0.8c0.7,0.9,1.3,1.9,1.8,2.9 l2.7-2.7C24.4,11.6,23.8,10.6,23.1,9.8z" />
                               <path d="M25,32.7c-0.2,0.2-0.2,0.5,0,0.7l1.6,1.6c0,0,0,0,0,0c0.2,0.2,0.6,0.2,0.7-0.1c6.1-7.1,7-15.8,3.8-23.5l-2.7,2.7 C30.5,20.5,29.3,27,25,32.7z" />
                               <path d="M15.1,31.4c0.1,0.1,0.3,0.1,0.4,0.1c0.4,0,0.8-0.4,0.8-0.8v-4.3L12.8,30C13.6,30.5,14.4,30.9,15.1,31.4z" />
                               <path d="M20.2,27.7c-0.2,0.2-0.2,0.6,0,0.8l2,2.1c0.2,0.2,0.6,0.2,0.8,0c3.4-4,4.5-9.3,3.4-14.1l-3,3C23.6,22.5,22.6,25.5,20.2,27.7 z" />
                               <path d="M32.5,0.2C32.5,0.2,32.5,0.2,32.5,0.2c-0.2-0.3-0.6-0.3-0.8,0l-1.9,2l0,0c-0.2,0.2-0.2,0.5,0,0.7c0.7,0.9,1.4,1.9,2,2.8 l2.9-2.9C34,1.9,33.3,1,32.5,0.2z" />
                               <path d="M33.7,9.1c4.7,9.8,2.8,19.9-4.2,28.1c-0.2,0.2-0.2,0.6,0,0.8l2.1,2.1c0.2,0.2,0.6,0.2,0.8,0c8.5-9.7,10.4-22.9,4.3-34 L33.7,9.1z" />
                               <rect x="-6.1" y="18.5" transform="matrix(0.7071 -0.7071 0.7071 0.7071 -8.1971 20.0765)" width="52.4" height="2.8" />
                           </svg>
                       </div>
                       <div data-video-player="full-screen" class="cursor-pointer">
                           <svg class="w-40 h-40 fill-white scale-50 md:scale-100" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 675 675">
                               <path d="M637.5,675h-200c-20.7,0-37.5-16.8-37.5-37.5s16.8-37.5,37.5-37.5H600V437.5c0-20.7,16.8-37.5,37.5-37.5 s37.5,16.8,37.5,37.5v200C675,658.2,658.2,675,637.5,675z M237.5,675h-200C16.8,675,0,658.2,0,637.5v-200C0,416.8,16.8,400,37.5,400 S75,416.8,75,437.5V600h162.5c20.7,0,37.5,16.8,37.5,37.5S258.2,675,237.5,675z M637.5,275c-20.7,0-37.5-16.8-37.5-37.5V75H437.5 C416.8,75,400,58.2,400,37.5S416.8,0,437.5,0h200C658.2,0,675,16.8,675,37.5v200C675,258.2,658.2,275,637.5,275z M37.5,275 C16.8,275,0,258.2,0,237.5v-200C0,16.8,16.8,0,37.5,0h200C258.2,0,275,16.8,275,37.5S258.2,75,237.5,75H75v162.5 C75,258.2,58.2,275,37.5,275z" />
                           </svg>
                       </div>
                   </div>
               </div>
           <?php } ?>
           <?php if (! empty($videoText) ) { ?>
               <span class="absolute bottom-0 md:left-2/24 left-0 <?php echo str_replace("bg-", "text-", $overlay_color); ?> text-xxl-grotesk-ultra font-main -mb-[0.04em]"><?php echo $videoText; ?></span>
           <?php } ?>
       </div>
   </section>
<?php }
