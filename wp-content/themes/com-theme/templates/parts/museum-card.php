<?php
$museum = wp_parse_args( $args['museum'] ?? [], [
	'is_all'      => false,
	'id'          => 0,
	'place'       => '',
	'title'       => '',
	'description' => '',
	'url'         => '',
	'ticket_url'  => '',
	'price'       => '',
	'image_id'    => 0,
] );
$ticket_label = $args['ticket_label'] ?? __( 'Εισιτήρια', 'com-theme' );
$more_label = $args['more_label'] ?? __( 'Περισσότερα →', 'com-theme' );
$card_background = $museum['is_all'] ? 'bg-ochre-light' : 'bg-white';
?>
<article class="<?php echo esc_attr( $card_background ); ?> flex w-full flex-col overflow-hidden rounded-[1.5rem] md:min-h-[28rem] md:flex-row md:items-center md:px-30">
	<div class="flex min-w-0 flex-1 flex-col md:flex-row md:items-center lg:w-[96rem] lg:flex-none lg:gap-40">
		<div class="flex min-w-0 flex-1 flex-col gap-30 p-20 md:h-[22rem] md:flex-row md:items-center md:gap-30 md:p-0 lg:w-[87rem] lg:flex-none lg:gap-50">
			<a href="<?php echo esc_url( $museum['url'] ); ?>" class="relative aspect-[calc(304/220)] w-full shrink-0 overflow-hidden rounded-[1rem] md:w-[30%] lg:w-[30.4rem]" aria-label="<?php echo esc_attr( $museum['title'] ); ?>">
				<?php if ( $museum['is_all'] ) : ?>
					<span class="absolute inset-0 bg-blue"></span>
					<svg class="absolute inset-0 size-full" aria-hidden="true"><use xlink:href="#icon-com-all-museums-mask"></use></svg>
					<span class="absolute left-[3.5rem] top-[4.2rem] text-[5.2rem] font-light leading-[.895] text-ochre-light lg:text-[7.6rem]">MOY<br>ΣΕΙΑ</span>
				<?php elseif ( $museum['image_id'] ) : ?>
					<?php echo wp_get_attachment_image( $museum['image_id'], 'large', false, [ 'class' => 'size-full object-cover transition-transform duration-500 hover:scale-105', 'alt' => $museum['title'] ] ); ?>
				<?php endif; ?>
			</a>

			<div class="flex min-w-0 flex-1 flex-col self-stretch">
				<div class="flex flex-col gap-20">
					<div class="flex flex-col gap-10">
						<?php if ( $museum['place'] ) : ?>
							<p class="text-[1rem] font-normal uppercase leading-none tracking-[.3em]"><?php echo esc_html( $museum['place'] ); ?></p>
						<?php endif; ?>
						<h3 class="text-[2.4rem] font-bold leading-none lg:text-[2.8rem]"><a href="<?php echo esc_url( $museum['url'] ); ?>"><?php echo esc_html( $museum['title'] ); ?></a></h3>
					</div>
					<?php if ( $museum['description'] ) : ?>
						<div class="text-[1.2rem] font-normal leading-[normal]"><?php echo wp_kses_post( wpautop( $museum['description'] ) ); ?></div>
					<?php endif; ?>
				</div>
				<a href="<?php echo esc_url( $museum['url'] ); ?>" class="mt-20 text-[1.2rem] leading-none opacity-60 transition-opacity hover:opacity-100 md:mt-auto"><?php echo esc_html( $more_label ); ?></a>
			</div>
		</div>

		<svg class="hidden h-[28rem] w-[4.4rem] shrink-0 md:block" aria-hidden="true"><use xlink:href="#icon-com-ticket-divider"></use></svg>
	</div>

	<div class="flex flex-row-reverse items-end justify-between gap-20 border-t border-dashed border-blue-soft/40 p-20 md:h-[20rem] md:w-[13rem] md:flex-col md:items-start md:border-0 md:p-0 lg:ml-auto">
		<?php if ( $museum['ticket_url'] ) : ?>
			<?php
			get_template_part( 'templates/parts/com-button', null, [
				'href'    => $museum['ticket_url'],
				'label'   => $ticket_label,
				'variant' => 'blue-outline',
				'size'    => 'small',
				'icon'    => 'com-ticket-button',
			] );
			?>
		<?php endif; ?>
		<?php if ( $museum['price'] ) : ?>
			<p class="text-[1.4rem] font-normal leading-[1.2]"><?php esc_html_e( 'Τιμή εισιτηρίου:', 'com-theme' ); ?><br><strong class="font-bold"><?php echo esc_html( $museum['price'] ); ?></strong></p>
		<?php endif; ?>
	</div>
</article>
