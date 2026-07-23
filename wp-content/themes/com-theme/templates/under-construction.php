<!DOCTYPE html>
<html class="fixed top-0 left-0 right-0 bottom-0 font-main antialiased leading-normal group " <?php language_attributes(); ?> data-module-load>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <?php wp_head(); ?>
</head>
<body <?php body_class("group fixed top-0 left-0 right-0 bottom-0 text-16 "); ?>  data-barba="wrapper" >
    <?php wp_body_open(); ?>
    <div class="fixed inset-0 flex flex-col justify-center items-center gap-20" >
        <?php if( ! empty( $logo_id = get_field( 'uc_logo', 'option' )  ) ) { ?>
        <img src="<?php echo $logo_id[ 'url' ]; ?>" alt="" class="w-[20rem] h-auto">
        <?php } ?>
        <?php if( ! empty( $uc_text = get_field( 'uc_text', 'option' ) ) ) {  ?>
        <h1 class="font-normal text-[2rem] leading-none"><?php echo $uc_text ?></h1>
        <?php } ?>
    </div>
</body>
</html>

