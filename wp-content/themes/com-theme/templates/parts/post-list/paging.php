<?php

extract(wp_parse_args($args, ['query' => false, 'postType' => 'post']));

add_filter('wp_pagenavi_class_pages', function () {
  return 'hidden';
});
add_filter('wp_pagenavi_class_first', function () {
  return 'hidden';
});
add_filter('wp_pagenavi_class_previouspostslink', function () {
  return 'ab-item w-[4.4rem] h-[4.4rem] flex items-center justify-center bg-white rounded-full';
});
add_filter('wp_pagenavi_class_extend', function () {
  return 'hidden';
});
add_filter('wp_pagenavi_class_smaller', function () {
  return 'smaller';
});
add_filter('wp_pagenavi_class_page', function () {
  return 'ab-item w-[4.4rem] h-[4.4rem] flex items-center justify-center';
});
add_filter('wp_pagenavi_class_current', function () {
  return 'ab-item w-[4.4rem] h-[4.4rem] bg-[#457182] text-white flex items-center justify-center rounded-full';
});
add_filter('wp_pagenavi_class_larger', function () {
  return '';
});
add_filter('wp_pagenavi_class_nextpostslink', function () {
  return 'ab-item w-[4.4rem] h-[4.4rem] flex items-center justify-center bg-white rounded-full';
});
add_filter('wp_pagenavi_class_last', function () {
  return 'hidden';
}); ?>

<div class="not-prose text-black" id="<?php echo "ajax-paging-" . $postType; ?>-container" data-target="#ajax-results-<?php echo $postType; ?>" data-module-paging>
  <div class="flex justify-between">
    <?php
    $pageNaviArgs = [
      'wrapper_class' => 'flex items-center',
      'options' => [
        'prev_text' => '<div class="w-[4.4rem] h-[4.4rem] flex items-center justify-center bg-white rounded-full"><svg width="8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.53033 13.5303C7.23744 13.8232 6.76256 13.8232 6.46967 13.5303L0.46967 7.53033C0.176777 7.23744 0.176777 6.76256 0.46967 6.46967L6.46967 0.46967C6.76256 0.176777 7.23744 0.176777 7.53033 0.46967C7.82322 0.762563 7.82322 1.23744 7.53033 1.53033L2.06066 7L7.53033 12.4697C7.82322 12.7626 7.82322 13.2374 7.53033 13.5303Z" fill="#010101"/></svg></div>',
        'next_text' => '<div class="w-[4.4rem] h-[4.4rem] flex items-center justify-center bg-white rounded-full"><svg width="8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.46967 0.46967C0.762563 0.176777 1.23744 0.176777 1.53033 0.46967L7.53033 6.46967C7.82322 6.76256 7.82322 7.23744 7.53033 7.53033L1.53033 13.5303C1.23744 13.8232 0.762563 13.8232 0.46967 13.5303C0.176777 13.2374 0.176777 12.7626 0.46967 12.4697L5.93934 7L0.46967 1.53033C0.176777 1.23744 0.176777 0.762563 0.46967 0.46967Z" fill="#010101"/></svg></div>'
      ]
    ];

    if (!$query) {
      global $wp_query;
      $pageNaviArgs['query'] = $wp_query;
    } else {
      $pageNaviArgs['query'] = $query;
    }

    $pageNaviArgs['type'] = '';


    $instance = new PageNavi_Call($pageNaviArgs);
    list($posts_per_page, $paged, $total_pages) = $instance->get_pagination_args();

    if ($total_pages > 1) {
      wp_pagenavi($pageNaviArgs);
      // NEXT LINK
      /* if ($paged < $total_pages) {
        $nextLink = $instance->get_url($paged + 1, "next", []);
        get_template_part('templates/parts/button', false, ['link' => $nextLink, 'color' => $color, 'text' => __('ΠΕΡΙΣΣΟΤΕΡΑ', 'com-theme')]);
      } */
    }
    ?>
  </div>
</div>