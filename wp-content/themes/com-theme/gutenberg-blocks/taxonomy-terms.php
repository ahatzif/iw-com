<?php

/**
 * Title: Taxonomy Terms
 * @see wp-content/themes/com-theme/acf-json/taxonomy-terms_66f4cd8968ca1.json ACF Fields
 * @see wp-content/themes/com-theme/assets/src/modules/terms-accordion.js
 *
 **/
$terms = get_terms(['taxonomy' => get_field('theme_taxonomy'), 'hide_empty' => false]);
if (!empty($terms) && !is_wp_error($terms)) { ?>
  <section class="<?php echo esc_attr( com_theme_block_style_classes( 'bg-blue text-white', [ 'desktop' => [ 'pt' => '100', 'pb' => 'huge' ], 'mobile' => [ 'pt' => 'small', 'pb' => '90' ] ] ) ); ?>" data-module-terms-accordion>
    <div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?>">
      <ul class="">
        <?php foreach ($terms as $key => $term) { ?>
          <li>
            <a href="<?php echo esc_url(get_term_link($term)); ?>" class="pb-40 block text-current hover:opacity-80 group taxonomy-term">
              <span class="px-1/12 md:pl-1/24 md:pr-2/24  border-l-[1px] border-current block text-xl-grotesk-medium font-main transition duration-500"><?php echo esc_html($term->name); ?></span>
              <span data-desc class="block px-1/12 md:pl-1/24 md:pr-2/24  pt-10 border-l-[1px] border-current md:text-m-inter-regular text-s-inter-regular font-secondary md:opacity-0  group-[.taxonomy-term:hover]:opacity-100 transition duration-500"><?php echo $term->description ?></span>
            </a>
          </li>
        <?php } ?>
      </ul>
    </div>
  </section>
<?php }
