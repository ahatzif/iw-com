<?php
$button = wp_parse_args( $args ?? [], [
	'href'       => '#',
	'label'      => '',
	'variant'    => 'light',
	'size'       => 'large',
	'icon'       => '',
	'target'     => '',
	'tag'        => 'a',
	'type'       => 'button',
	'classes'    => '',
	'attributes' => [],
] );

$tag = in_array( $button['tag'], [ 'a', 'button' ], true ) ? $button['tag'] : 'a';
$variant_classes = [
	'light'         => 'border-blue bg-ochre text-blue hover:border-ochre hover:bg-transparent hover:text-ochre',
	'blue-outline'  => 'border-blue bg-transparent text-blue hover:bg-blue hover:text-white',
	'light-outline' => 'border-ochre-light bg-transparent text-ochre-light hover:bg-ochre-light hover:text-blue',
];
$size_classes = $button['size'] === 'small'
	? 'rounded-[.5rem] px-[1.7rem] py-[1.5rem]'
	: 'rounded-[1rem] px-30 py-20';
$icon_classes = $button['variant'] === 'blue-outline'
	? 'transition-[filter] group-hover/button:brightness-0 group-hover/button:invert'
	: '';

$attributes = is_array( $button['attributes'] ) ? $button['attributes'] : [];
$attribute_markup = '';

foreach ( $attributes as $attribute => $value ) {
	$attribute = strtolower( (string) $attribute );
	$is_allowed = in_array( $attribute, [ 'id', 'name', 'value', 'disabled' ], true )
		|| preg_match( '/^(aria|data)-[a-z0-9_-]+$/', $attribute );

	if ( ! $is_allowed || $value === false || $value === null ) {
		continue;
	}

	$attribute_markup .= $value === true
		? ' ' . esc_attr( $attribute )
		: sprintf( ' %s="%s"', esc_attr( $attribute ), esc_attr( (string) $value ) );
}
?>
<<?php echo esc_html( $tag ); ?>
	<?php if ( $tag === 'a' ) : ?>
		href="<?php echo esc_url( $button['href'] ); ?>"
		<?php echo $button['target'] === '_blank' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
	<?php else : ?>
		type="<?php echo esc_attr( in_array( $button['type'], [ 'button', 'submit', 'reset' ], true ) ? $button['type'] : 'button' ); ?>"
	<?php endif; ?>
	class="group/button inline-flex items-center justify-center border text-[1.6rem] font-normal leading-none transition-colors <?php echo esc_attr( $size_classes . ' ' . ( $variant_classes[ $button['variant'] ] ?? $variant_classes['light'] ) . ' ' . $button['classes'] ); ?>"
	<?php echo $attribute_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<?php if ( $button['icon'] ) : ?>
		<svg class="mr-10 size-[1.9rem] shrink-0 <?php echo esc_attr( $icon_classes ); ?>" aria-hidden="true">
			<use xlink:href="#icon-<?php echo esc_attr( $button['icon'] ); ?>"></use>
		</svg>
	<?php endif; ?>
	<span><?php echo esc_html( $button['label'] ); ?></span>
</<?php echo esc_html( $tag ); ?>>
