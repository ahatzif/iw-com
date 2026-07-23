<?php
    $params = wp_parse_args( $args, ['fields' => []]);
    extract($params);
?>
<form action="<?php the_permalink(); ?>"  data-module-form-validation data-module-register data-error-class="border !border-red !outline-none !shadow-none" >
    <fieldset>
    <?php if( ! empty( $fields ) ) { ?>
        <?php foreach( $fields as $key => $value )  { ?>
        <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>">
        <?php } ?>
    <?php } ?>