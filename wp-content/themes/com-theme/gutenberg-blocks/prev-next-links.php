<?php // Title: Prev Next Links

if (is_singular())
{
    $prevPost = get_adjacent_post(false, '', false,);
    $nextPost = get_adjacent_post(false, '', true,);
    if (!empty($prevPost)) $prevPost = get_permalink($prevPost);
    if (!empty($nextPost)) $nextPost = get_permalink($nextPost);

    $custom_post_type = get_post_type();
    $labels = get_post_type_labels(get_post_type_object($custom_post_type));
?>
    <section class="<?php echo esc_attr( com_theme_block_style_classes( 'text-blue', [ 'desktop' => [ 'mt' => '100', 'mb' => '100' ] ] ) ); ?>">
    <div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?>">
        <div class="px-1/12">
            <div class="flex gap-[70px] justify-between md:justify-center text-current leading-[1.42857143] font-semibold text-14">
                <?php if ($prevPost)
                { ?>
                    <a class="hover:underline" href="<?php echo esc_url( $prevPost ); ?>"><?php echo esc_html( com\theme::remove_accents( sprintf( __( 'Previous %s', 'com-theme' ), $labels->singular_name ) ) ); ?></a>
                <?php }
                else
                { ?>
                    <div class="opacity-30"><?php echo esc_html( com\theme::remove_accents( sprintf( __( 'Previous %s', 'com-theme' ), $labels->singular_name ) ) ); ?></div>
                <?php } ?>
                <?php if ($nextPost)
                { ?>
                    <a class="hover:underline" href="<?php echo esc_url( $nextPost ); ?>"><?php echo esc_html( com\theme::remove_accents( sprintf( __( 'Next %s', 'com-theme' ), $labels->singular_name ) ) ); ?></a>
                <?php }
                else
                { ?>
                    <div class="opacity-30"><?php echo esc_html( com\theme::remove_accents( sprintf( __( 'Next %s', 'com-theme' ), $labels->singular_name ) ) ); ?></div>
                <?php } ?>
            </div>
        </div>
    </div>
    </section>
<?php } ?>
