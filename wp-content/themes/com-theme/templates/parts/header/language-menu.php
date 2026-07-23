<?php if ( ! empty( $languages = apply_filters( 'wpml_active_languages', NULL ) ) && count( $languages )  ) { ?>
<nav id="lang-menu">
    <ul class="flex  space-x-5 ">
        <?php foreach( $languages as $l ) { ?>
        <li class="relative after:absolute after:left-0 after:-bottom-0 after:block after:h-1 after:w-full after:bg-current after:opacity-0 [&.active:after]:opacity-100  <?php if( $l['active'] ) echo 'active'; ?>" data-language-code="<?php echo esc_attr( $l['language_code'] ); ?>"><a data-barba-prevent href="<?php echo esc_url( $l['url'] ); ?>"><?php echo esc_html( com\theme::remove_accents( $l['code'] ) ); ?></a></li>
        <?php } ?>
    </ul>
</nav>
<?php }
