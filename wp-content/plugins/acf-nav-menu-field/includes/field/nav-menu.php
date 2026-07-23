<?php
/**
* ACF Nav Menu Field
*
* @package ACF Nav Menu Field
*/

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF_Field_Nav_Menu Class
 *
 * This class contains all the custom workings for the Nav Menu Field for ACF
 */
class ACNMF_Menu extends acf_field {

	/**
	 * Sets up some default values and delegats work to the parent constructor.
	 * This function shows nav menu field into the acf field type.
	 */
	public function __construct() {
		$this->name     = 'nav_menu';
		$this->label    = esc_html__( 'Nav Menu','acf-nav-menu' );
		$this->category = 'choice';
		$this->defaults = array(
			'save_format' => 'menu',
			'allow_null'  => 0,
			'container'   => 'div',
		);
		parent::__construct();
	}

	/**
	 * Renders the ACF Nav Menu Field options seen when editing a Nav Menu Field.
	 *
	 * @param array $field The array representation of the current Nav Menu Field.
	 */
	public function render_field_settings( $field ) {
		// Register the Return Value format setting
		acf_render_field_setting( $field, array(
			'label'        => esc_html__( 'Return Value','acf-nav-menu' ),
			'instructions' => esc_html__( 'Specify the returned value on front end','acf-nav-menu' ),
			'type'         => 'radio',
			'name'         => 'save_format',
			'layout'       => 'horizontal',
			'choices'      => array(
				'menu'   => esc_html__( 'Nav Menu HTML','acf-nav-menu' ),
				'object' => esc_html__( 'Nav Menu Object','acf-nav-menu' ),
				'id'     => esc_html__( 'Nav Menu ID','acf-nav-menu' ),
			),
		) );

		// Register the Menu Container setting
		acf_render_field_setting( $field, array(
			'label'        => esc_html__( 'Menu Container','acf-nav-menu' ),
			'instructions' => esc_html__( "What to wrap the Menu's ul with (when returning HTML only)",'acf-nav-menu' ),
			'type'         => 'select',
			'name'         => 'container',
			'choices'      => $this->get_allowed_nav_container_tags(),
		) );

		// Register the Allow Null setting
		acf_render_field_setting( $field, array(
			'label'        => esc_html__( 'Allow Null?','acf-nav-menu' ),
			'type'         => 'radio',
			'name'         => 'allow_null',
			'layout'       => 'horizontal',
			'choices'      => array(
				1 => esc_html__( 'Yes','acf-nav-menu' ),
				0 => esc_html__( 'No','acf-nav-menu' ),
			),
		) );
	}

	/**
	 * Get the allowed wrapper tags for use with wp_nav_menu().
	 *
	 * @return array An array of allowed wrapper tags.
	 */
	private function get_allowed_nav_container_tags() {
		$tags           = apply_filters( 'wp_nav_menu_container_allowedtags', array( 'div', 'nav' ) );
		$formatted_tags = array(
			'0' => 'None',
		);
		foreach ( $tags as $tag ) {
			$formatted_tags[$tag] = ucfirst( $tag );
		}
		return $formatted_tags;
	}

	/**
	 * Renders the ACF Nav Menu Field.
	 *
	 * @param array $field The array representation of the current Nav Menu Field.
	 */
	public function render_field( $field ) {
		$allow_null = $field['allow_null'];
		$nav_menus  = $this->get_nav_menus( $allow_null );
		if ( empty( $nav_menus ) ) {
			return;
		}
		?>
		<div class="custom-acf-nav-menu">
			<select id="<?php esc_attr( $field['id'] ); ?>" class="<?php echo esc_attr( $field['class'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>">
			<?php foreach( $nav_menus as $nav_menu_id => $nav_menu_name ) : ?>
				<option value="<?php echo esc_attr( $nav_menu_id ); ?>" <?php selected( $field['value'], $nav_menu_id ); ?>>
					<?php echo esc_html( $nav_menu_name ); ?>
				</option>
			<?php endforeach; ?>
			</select>
		</div>
		<?php
	}
	/**
	 * Gets a list of ACF Nav Menus indexed by their Nav Menu IDs.
	 *
	 * @param bool $allow_null If true, prepends the null option.
	 *
	 * @return array An array of Nav Menus indexed by their Nav Menu IDs.
	 */
	private function get_nav_menus( $allow_null = false ) {
		$navs = get_terms( 'nav_menu', array( 'hide_empty' => false ) );
		$nav_menus = array();
		if ( $allow_null ) {
			$nav_menus[''] = esc_html__( '- Select -','acf-nav-menu' );
		}
		foreach ( $navs as $nav ) {
			$nav_menus[ $nav->term_id ] = $nav->name;
		}
		return $nav_menus;
	}

	/**
	 * Renders the ACF Nav Menu Field.
	 *
	 * @param int   $value   The Nav Menu ID selected for this Nav Menu Field.
	 * @param int   $post_id The Post ID this $value is associated with.
	 * @param array $field   The array representation of the current Nav Menu Field.
	 *
	 * @return mixed The Nav Menu ID, or the Nav Menu HTML, or the Nav Menu Object, or false.
	 */
	public function format_value( $value, $post_id, $field ) {
		// bail early if no value
		if ( empty( $value ) ) {
			return false;
		}
		// check format
		if ( 'object' == $field['save_format'] ) {
			$wp_menu_object = wp_get_nav_menu_object( $value );
			if( empty( $wp_menu_object ) ) {
				return false;
			}
			$menu_object = new stdClass;
			$menu_object->ID    = $wp_menu_object->term_id;
			$menu_object->name  = $wp_menu_object->name;
			$menu_object->slug  = $wp_menu_object->slug;
			$menu_object->count = $wp_menu_object->count;
			return $menu_object;
		} elseif ( 'menu' == $field['save_format'] ) {
			ob_start();
			wp_nav_menu( array(
				'menu' => $value,
				'container' => 'div',
       			'container_class' => 'acf-nav-menu',
				'container' => $field['container'],
				'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			) );
			return ob_get_clean();
		}
		// Just return the Nav Menu ID
		return $value;
	}
	function load_value( $value, $post_id, $field ) {
		return $value;
	}
}
new ACNMF_Menu();
