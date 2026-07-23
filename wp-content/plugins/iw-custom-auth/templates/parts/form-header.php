<?php
    $params = wp_parse_args( $args, ['title' => '','description' => '']);
    extract($params);
?>

<div class="mb-16">
    <h1 class="font-bold text-[5rem] mb-[1rem]"><?php echo $title;?></h1>
    <?php if( $description ) { ?>
    <?php echo $description;?>
    <?php } ?>
</div>
