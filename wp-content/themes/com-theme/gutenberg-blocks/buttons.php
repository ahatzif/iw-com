<?php // Title: Buttons
if (! empty($buttons = get_field('buttons'))) { ?>
    <section class="<?php echo esc_attr( com_theme_block_style_classes() ); ?>">
        <div class="flex flex-wrap gap-20 <?php echo esc_attr( com_theme_block_wrapper_classes() ); ?>">
            <?php foreach ($buttons as $button) {
                get_template_part('templates/parts/button', false, $button);
            } ?>
        </div>
    </section>
<?php }
