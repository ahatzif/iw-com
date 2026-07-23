<?php // Title: Buttons
if (! empty($buttons = get_field('buttons'))) { ?>
    <section class="<?php echo apply_filters('theme_block_colors', ''); ?> <?php echo apply_filters('theme_block_spacings', ["desktop" => ["mt" => "normal", "mb" => "normal"]]); ?>">
        <div class="flex flex-wrap gap-20 <?php echo apply_filters('theme_block_wrapper', 'page-wrapper'); ?>">
            <?php foreach ($buttons as $button) {
                get_template_part('templates/parts/button', false, $button);
            } ?>
        </div>
    </section>
<?php }