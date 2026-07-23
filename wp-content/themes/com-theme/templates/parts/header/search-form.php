<form action="<?php echo get_field('search_page', 'options',); ?>" class="h-[min(20px,2rem)] w-[min(20px,2rem)] group-[.search-open]:w-[200px] flex relative overflow-hidden transition-all duration-[500ms] ease-[cubic-bezier(0.190,1.000,0.220,1.000)]" method="get">
    <div data-page-header="toggle-search" class="cursor-pointer shrink-0 relative h-full flex items-center justify-center ">
        <svg class="cursor-pointer w-[min(16px,1.6rem)] h-[min(16px,1.6rem)] fill-current "><use xlink:href='#icon-search'></use></svg>
    </div>
    <label class="block h-full w-full ml-10">
        <input type="text" class="block border-b border-b-current bg-transparent font-bold placeholder-current h-full text-[10px]  outline-0 w-full" value="" name="search" data-page-header="search-input" placeholder="<?php _e('SEARCH HERE', 'com-theme') ?>">
    </label>
</form>
