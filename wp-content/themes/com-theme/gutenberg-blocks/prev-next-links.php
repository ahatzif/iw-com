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
    <div class="page-wrapper my-100">
        <div class="px-1/12">
            <div class="flex gap-[70px] justify-between md:justify-center text-blue leading-[1.42857143] font-semibold uppercase text-14">
                <?php if ($prevPost)
                { ?>
                    <a class="hover:underline" href="<?php echo $prevPost ?>"><?php _e('Previous ' . $labels->singular_name, 'com-theme'); ?></a>
                <?php }
                else
                { ?>
                    <div class="opacity-30"><?php _e('Previous ' . $labels->singular_name, 'com-theme'); ?></div>
                <?php } ?>
                <?php if ($nextPost)
                { ?>
                    <a class="hover:underline" href="<?php echo $nextPost; ?>"><?php _e('Next ' . $labels->singular_name, 'com-theme'); ?></a>
                <?php }
                else
                { ?>
                    <div class="opacity-30"><?php _e('Next ' . $labels->singular_name, 'com-theme'); ?></div>
                <?php } ?>
            </div>
        </div>
    </div>
<?php } ?>
