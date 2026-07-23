<?php

/**
 * Title: Taxonomy Terms
 * @see wp-content/themes/com-theme/acf-json/taxonomy-terms_66f4cd8968ca1.json ACF Fields
 * @see wp-content/themes/com-theme/assets/src/modules/terms-accordion.js
 *
 **/
$terms = get_terms(['taxonomy' => get_field('theme_taxonomy'), 'hide_empty' => false]);
if (!empty($terms) && !is_wp_error($terms)) { ?>
  <section data-module-terms-accordion>
    <div class="page-wrapper bg-blue pt-55 pb-90 md:pb-200 md:pt-100">
      <ul class="">
        <?php foreach ($terms as $key => $term) { ?>
          <li>
            <a href="<?php echo esc_url(get_term_link($term)); ?>" class="pb-40 block text-yellow hover:text-white  group taxonomy-term">
              <span class="px-1/12 md:pl-1/24 md:pr-2/24  border-l-[1px] border-current block text-xl-grotesk-medium font-main transition duration-500"><?php echo esc_html($term->name); ?></span>
              <span data-desc class="block px-1/12 md:pl-1/24 md:pr-2/24  pt-10 border-l-[1px] border-current md:text-m-inter-regular text-s-inter-regular font-secondary md:opacity-0  group-[.taxonomy-term:hover]:opacity-100 transition duration-500"><?php echo $term->description ?></span>
            </a>
          </li>
        <?php } ?>
      </ul>
    </div>
  </section>
<?php }
