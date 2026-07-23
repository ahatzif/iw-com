<?php extract(wp_parse_args($args, ['content' => ''])); ?>
<?php if (! empty($content)) { ?>
  <div class="flex">
    <?php echo $content; ?>
  </div>
<?php } ?>