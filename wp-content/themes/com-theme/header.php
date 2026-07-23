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
        <div class="hidden"><?php com\theme::svg( 'sprite', '/assets/images/sprite/' ); ?></div>
        <?php get_template_part( 'templates/parts/header/header'); ?>
        <div data-barba="container" class="overflow-hidden fixed inset-0 group-[.admin-bar:not(.hide-admin-bar)]:top-[var(--wp-admin--admin-bar--height)]" >
            <div data-module-scroll="main" class="overflow-hidden absolute inset-0 z-1">
                <div data-scroll="content" class="<?php if (apply_filters('header_margin', true) ) { echo 'pt-[var(--header-height)]'; }?>">
                    <?php get_template_part('templates/parts/breadcrumbs/breadcrumbs', false, []); ?>
