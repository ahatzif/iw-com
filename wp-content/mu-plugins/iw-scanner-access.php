<?php
/**
 * Plugin Name: IW Scanner Access
 * Description: Registers scanner access role/capability and protects scanner REST verification endpoints.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IW_SCANNER_ROLE = 'iw_scanner_operator';
const IW_SCANNER_CAP  = 'iw_use_scanner';
const IW_SCANNER_META_REVOKED = '_iw_scanner_access_revoked';
const IW_SCANNER_META_LAST_SEEN = '_iw_scanner_last_seen';
const IW_SCANNER_META_LAST_BUILDING_ID = '_iw_scanner_last_building_id';
const IW_SCANNER_META_LAST_BUILDING_NAME = '_iw_scanner_last_building_name';
const IW_SCANNER_META_LAST_SCAN_AT = '_iw_scanner_last_scan_at';
const IW_SCANNER_META_LAST_SCAN_TITLE = '_iw_scanner_last_scan_title';
const IW_SCANNER_META_LAST_SCAN_VARIANT = '_iw_scanner_last_scan_variant';
const IW_SCANNER_NOTICES_OPTION = 'iw_scanner_notices';
const IW_SCANNER_NOTIFICATION_POST_TYPE = 'scanner_notification';
const IW_SCANNER_META_NOTIFICATION_PRIORITY = '_iw_scanner_notification_priority';
const IW_SCANNER_META_NOTIFICATION_EXPIRES_AT = '_iw_scanner_notification_expires_at';
const IW_SCANNER_META_READ_NOTIFICATIONS = '_iw_scanner_read_notifications';

function iw_scanner_register_access_role(): void {
	$role = get_role( IW_SCANNER_ROLE );

	if ( ! $role ) {
		$role = add_role(
			IW_SCANNER_ROLE,
			__( 'Scanner Operator', 'iw-scanner' ),
			[
				'read'         => true,
				IW_SCANNER_CAP => true,
			]
		);
	}

	if ( $role instanceof WP_Role ) {
		$role->add_cap( 'read' );
		$role->add_cap( IW_SCANNER_CAP );
	}

	$administrator = get_role( 'administrator' );
	if ( $administrator instanceof WP_Role ) {
		$administrator->add_cap( IW_SCANNER_CAP );
	}
}
add_action( 'init', 'iw_scanner_register_access_role', 5 );

function iw_scanner_register_notification_post_type(): void {
	register_post_type(
		IW_SCANNER_NOTIFICATION_POST_TYPE,
		[
			'labels'              => [
				'name'               => __( 'Scanner Notifications', 'iw-scanner' ),
				'singular_name'      => __( 'Scanner Notification', 'iw-scanner' ),
				'add_new_item'       => __( 'Add Scanner Notification', 'iw-scanner' ),
				'edit_item'          => __( 'Edit Scanner Notification', 'iw-scanner' ),
				'new_item'           => __( 'New Scanner Notification', 'iw-scanner' ),
				'view_item'          => __( 'View Scanner Notification', 'iw-scanner' ),
				'search_items'       => __( 'Search Scanner Notifications', 'iw-scanner' ),
				'not_found'          => __( 'No scanner notifications found.', 'iw-scanner' ),
				'not_found_in_trash' => __( 'No scanner notifications found in Trash.', 'iw-scanner' ),
				'menu_name'          => __( 'Notifications', 'iw-scanner' ),
			],
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-megaphone',
			'supports'            => [ 'title', 'editor', 'excerpt', 'author', 'thumbnail' ],
			'capabilities'        => array_fill_keys(
				[
					'edit_post',
					'read_post',
					'delete_post',
					'edit_posts',
					'edit_others_posts',
					'publish_posts',
					'read_private_posts',
					'delete_posts',
					'delete_private_posts',
					'delete_published_posts',
					'delete_others_posts',
					'edit_private_posts',
					'edit_published_posts',
					'create_posts',
				],
				'manage_options'
			),
			'map_meta_cap'        => false,
		]
	);
}
add_action( 'init', 'iw_scanner_register_notification_post_type', 8 );

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'post-thumbnails', [ IW_SCANNER_NOTIFICATION_POST_TYPE ] );
	}
);

function iw_scanner_user_can_scan(): bool {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	if ( get_user_meta( get_current_user_id(), IW_SCANNER_META_REVOKED, true ) ) {
		return false;
	}

	return current_user_can( IW_SCANNER_CAP );
}

function iw_scanner_rest_route_requires_access( string $route ): bool {
	return in_array(
		$route,
		[
			'/iw/v1/verify-ticket',
			'/iw/v1/verify-member-card',
			'/iw/v1/search-member-card',
		],
		true
	);
}

function iw_scanner_rest_forbidden_response(): WP_Error {
	return new WP_Error(
		is_user_logged_in() ? 'iw_scanner_forbidden' : 'iw_scanner_auth_required',
		__( 'Scanner access is required.', 'iw-scanner' ),
		[ 'status' => is_user_logged_in() ? 403 : 401 ]
	);
}

add_filter(
	'rest_request_before_callbacks',
	function ( $response, $handler, WP_REST_Request $request ) {
		if ( iw_scanner_rest_route_requires_access( $request->get_route() ) && ! iw_scanner_user_can_scan() ) {
			return iw_scanner_rest_forbidden_response();
		}

		return $response;
	},
	10,
	3
);

function iw_scanner_get_active_notices(): array {
	$now     = time();
	$notices = get_option( IW_SCANNER_NOTICES_OPTION, [] );

	if ( ! is_array( $notices ) ) {
		return [];
	}

	return array_values(
		array_filter(
			array_map(
				static function ( $notice ) use ( $now ) {
					if ( ! is_array( $notice ) || empty( $notice['id'] ) || empty( $notice['message'] ) ) {
						return null;
					}

					$expires_at = absint( $notice['expires_at'] ?? 0 );
					if ( $expires_at && $expires_at < $now ) {
						return null;
					}

					$variant = sanitize_key( $notice['variant'] ?? 'info' );
					if ( ! in_array( $variant, [ 'info', 'warning', 'urgent' ], true ) ) {
						$variant = 'info';
					}

					return [
						'id'         => sanitize_key( $notice['id'] ),
						'message'    => wp_strip_all_tags( (string) $notice['message'] ),
						'variant'    => $variant,
						'expires_at' => $expires_at,
					];
				},
				$notices
			)
		)
	);
}

function iw_scanner_rest_permission(): bool {
	return iw_scanner_user_can_scan();
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'iw/v1',
			'/scanner-heartbeat',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => 'iw_scanner_rest_permission',
				'callback'            => static function ( WP_REST_Request $request ) {
					$user_id = get_current_user_id();
					$params  = $request->get_json_params();
					$params  = is_array( $params ) ? $params : [];

					update_user_meta( $user_id, IW_SCANNER_META_LAST_SEEN, time() );

					if ( isset( $params['building_id'] ) ) {
						update_user_meta( $user_id, IW_SCANNER_META_LAST_BUILDING_ID, absint( $params['building_id'] ) );
					}

					if ( isset( $params['building_title'] ) ) {
						update_user_meta( $user_id, IW_SCANNER_META_LAST_BUILDING_NAME, sanitize_text_field( (string) $params['building_title'] ) );
					}

					if ( ! empty( $params['last_scan_title'] ) ) {
						update_user_meta( $user_id, IW_SCANNER_META_LAST_SCAN_AT, time() );
						update_user_meta( $user_id, IW_SCANNER_META_LAST_SCAN_TITLE, sanitize_text_field( (string) $params['last_scan_title'] ) );
						update_user_meta( $user_id, IW_SCANNER_META_LAST_SCAN_VARIANT, sanitize_key( (string) ( $params['last_scan_variant'] ?? 'neutral' ) ) );
					}

					return rest_ensure_response(
						[
							'ok'            => true,
							'notices'       => iw_scanner_get_active_notices(),
							'notifications' => [
								'unread_count' => iw_scanner_get_unread_notification_count( $user_id ),
							],
						]
					);
				},
			]
		);

		register_rest_route(
			'iw/v1',
			'/scanner-notices',
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => 'iw_scanner_rest_permission',
				'callback'            => static function () {
					return rest_ensure_response( [ 'notices' => iw_scanner_get_active_notices() ] );
				},
			]
		);

		register_rest_route(
			'iw/v1',
			'/scanner-notifications',
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => 'iw_scanner_rest_permission',
				'callback'            => static function () {
					$read_ids = iw_scanner_get_read_notification_ids( get_current_user_id() );
					$posts    = get_posts( iw_scanner_notification_query_args() );

					return rest_ensure_response(
						[
							'items'        => array_map(
								static fn( WP_Post $post ) => iw_scanner_format_notification_list_item( $post, $read_ids ),
								$posts
							),
							'unread_count' => iw_scanner_get_unread_notification_count( get_current_user_id() ),
						]
					);
				},
			]
		);

		register_rest_route(
			'iw/v1',
			'/scanner-notifications/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => 'iw_scanner_rest_permission',
				'callback'            => static function ( WP_REST_Request $request ) {
					$post = get_post( absint( $request['id'] ) );
					if ( ! $post instanceof WP_Post || ! iw_scanner_notification_is_active( $post ) ) {
						return new WP_Error( 'iw_scanner_notification_not_found', __( 'Notification not found.', 'iw-scanner' ), [ 'status' => 404 ] );
					}

					return rest_ensure_response(
						iw_scanner_format_notification_detail(
							$post,
							iw_scanner_get_read_notification_ids( get_current_user_id() )
						)
					);
				},
			]
		);

		register_rest_route(
			'iw/v1',
			'/scanner-notifications/(?P<id>\d+)/read',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => 'iw_scanner_rest_permission',
				'callback'            => static function ( WP_REST_Request $request ) {
					$post = get_post( absint( $request['id'] ) );
					if ( ! $post instanceof WP_Post || ! iw_scanner_notification_is_active( $post ) ) {
						return new WP_Error( 'iw_scanner_notification_not_found', __( 'Notification not found.', 'iw-scanner' ), [ 'status' => 404 ] );
					}

					iw_scanner_mark_notification_read( get_current_user_id(), $post->ID );

					return rest_ensure_response(
						[
							'ok'           => true,
							'unread_count' => iw_scanner_get_unread_notification_count( get_current_user_id() ),
						]
					);
				},
			]
		);

		register_rest_route(
			'iw/v1',
			'/scanner-notifications/(?P<id>\d+)/unread',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => 'iw_scanner_rest_permission',
				'callback'            => static function ( WP_REST_Request $request ) {
					$post = get_post( absint( $request['id'] ) );
					if ( ! $post instanceof WP_Post || ! iw_scanner_notification_is_active( $post ) ) {
						return new WP_Error( 'iw_scanner_notification_not_found', __( 'Notification not found.', 'iw-scanner' ), [ 'status' => 404 ] );
					}

					iw_scanner_mark_notification_unread( get_current_user_id(), $post->ID );

					return rest_ensure_response(
						[
							'ok'           => true,
							'unread_count' => iw_scanner_get_unread_notification_count( get_current_user_id() ),
						]
					);
				},
			]
		);
	}
);

function iw_scanner_admin_url( array $args = [] ): string {
	return add_query_arg( $args, admin_url( 'admin.php?page=iw-scanner-operators' ) );
}

function iw_scanner_redirect_admin( string $status ): void {
	wp_safe_redirect( iw_scanner_admin_url( [ 'iw_scanner_status' => $status ] ) );
	exit;
}

function iw_scanner_operator_users(): array {
	$users = [];

	foreach ( get_users( [ 'role' => IW_SCANNER_ROLE, 'fields' => 'all' ] ) as $user ) {
		$users[ $user->ID ] = $user;
	}

	foreach (
		get_users(
			[
				'meta_key'   => IW_SCANNER_META_REVOKED,
				'meta_value' => '1',
				'fields'     => 'all',
			]
		) as $user
	) {
		$users[ $user->ID ] = $user;
	}

	uasort(
		$users,
		static function ( WP_User $a, WP_User $b ) {
			$a_seen = absint( get_user_meta( $a->ID, IW_SCANNER_META_LAST_SEEN, true ) );
			$b_seen = absint( get_user_meta( $b->ID, IW_SCANNER_META_LAST_SEEN, true ) );

			if ( $a_seen === $b_seen ) {
				return strcasecmp( $a->display_name, $b->display_name );
			}

			return $b_seen <=> $a_seen;
		}
	);

	return array_values( $users );
}

function iw_scanner_format_relative_time( int $timestamp ): string {
	if ( ! $timestamp ) {
		return __( 'Never', 'iw-scanner' );
	}

	return sprintf(
		/* translators: %s: human readable time difference. */
		__( '%s ago', 'iw-scanner' ),
		human_time_diff( $timestamp, time() )
	);
}

function iw_scanner_operator_status( WP_User $user ): array {
	$last_seen = absint( get_user_meta( $user->ID, IW_SCANNER_META_LAST_SEEN, true ) );
	$revoked   = (bool) get_user_meta( $user->ID, IW_SCANNER_META_REVOKED, true );
	$online    = ! $revoked && $last_seen && ( time() - $last_seen ) <= 120;

	return [
		'last_seen' => $last_seen,
		'revoked'   => $revoked,
		'online'    => $online,
		'label'     => $revoked ? __( 'Revoked', 'iw-scanner' ) : ( $online ? __( 'Online', 'iw-scanner' ) : __( 'Offline', 'iw-scanner' ) ),
	];
}

function iw_scanner_notification_priorities(): array {
	return [
		'info'    => __( 'Info', 'iw-scanner' ),
		'warning' => __( 'Warning', 'iw-scanner' ),
		'urgent'  => __( 'Urgent', 'iw-scanner' ),
	];
}

function iw_scanner_sanitize_notification_priority( string $priority ): string {
	return array_key_exists( $priority, iw_scanner_notification_priorities() ) ? $priority : 'info';
}

function iw_scanner_notification_is_active( WP_Post $post ): bool {
	if ( $post->post_type !== IW_SCANNER_NOTIFICATION_POST_TYPE || $post->post_status !== 'publish' ) {
		return false;
	}

	$expires_at = absint( get_post_meta( $post->ID, IW_SCANNER_META_NOTIFICATION_EXPIRES_AT, true ) );

	return ! $expires_at || $expires_at >= time();
}

function iw_scanner_notification_query_args( array $overrides = [] ): array {
	return array_merge(
		[
			'post_type'      => IW_SCANNER_NOTIFICATION_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 30,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => [
				'relation' => 'OR',
				[
					'key'     => IW_SCANNER_META_NOTIFICATION_EXPIRES_AT,
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => IW_SCANNER_META_NOTIFICATION_EXPIRES_AT,
					'value'   => 0,
					'compare' => '=',
					'type'    => 'NUMERIC',
				],
				[
					'key'     => IW_SCANNER_META_NOTIFICATION_EXPIRES_AT,
					'value'   => time(),
					'compare' => '>=',
					'type'    => 'NUMERIC',
				],
			],
		],
		$overrides
	);
}

function iw_scanner_get_read_notification_ids( int $user_id ): array {
	$read_ids = get_user_meta( $user_id, IW_SCANNER_META_READ_NOTIFICATIONS, true );

	if ( ! is_array( $read_ids ) ) {
		return [];
	}

	return array_values( array_unique( array_filter( array_map( 'absint', $read_ids ) ) ) );
}

function iw_scanner_mark_notification_read( int $user_id, int $notification_id ): void {
	$read_ids   = iw_scanner_get_read_notification_ids( $user_id );
	$read_ids[] = $notification_id;
	$read_ids   = array_values( array_unique( array_filter( array_map( 'absint', $read_ids ) ) ) );

	update_user_meta( $user_id, IW_SCANNER_META_READ_NOTIFICATIONS, array_slice( $read_ids, -500 ) );
}

function iw_scanner_mark_notification_unread( int $user_id, int $notification_id ): void {
	$read_ids = array_values(
		array_filter(
			iw_scanner_get_read_notification_ids( $user_id ),
			static fn( int $read_id ) => $read_id !== $notification_id
		)
	);

	update_user_meta( $user_id, IW_SCANNER_META_READ_NOTIFICATIONS, $read_ids );
}

function iw_scanner_get_unread_notification_count( int $user_id ): int {
	if ( ! $user_id ) {
		return 0;
	}

	$read_ids = iw_scanner_get_read_notification_ids( $user_id );
	$post_ids = get_posts(
		iw_scanner_notification_query_args(
			[
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			]
		)
	);

	return count( array_diff( array_map( 'absint', $post_ids ), $read_ids ) );
}

function iw_scanner_get_notification_image( int $post_id ): array {
	$thumbnail_id = get_post_thumbnail_id( $post_id );
	if ( ! $thumbnail_id ) {
		return [];
	}

	$medium = wp_get_attachment_image_src( $thumbnail_id, 'medium_large' );
	$large  = wp_get_attachment_image_src( $thumbnail_id, 'large' );
	$thumb  = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );

	$url = $medium[0] ?? ( $large[0] ?? ( $thumb[0] ?? '' ) );
	if ( ! $url ) {
		return [];
	}

	return [
		'url'    => esc_url_raw( $url ),
		'thumb'  => esc_url_raw( $thumb[0] ?? $url ),
		'alt'    => wp_strip_all_tags( get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) ),
		'width'  => absint( $medium[1] ?? 0 ),
		'height' => absint( $medium[2] ?? 0 ),
	];
}

function iw_scanner_format_notification_list_item( WP_Post $post, array $read_ids ): array {
	$priority = iw_scanner_sanitize_notification_priority( (string) get_post_meta( $post->ID, IW_SCANNER_META_NOTIFICATION_PRIORITY, true ) );
	$excerpt  = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 18 );

	return [
		'id'         => $post->ID,
		'title'      => get_the_title( $post ),
		'excerpt'    => $excerpt,
		'priority'   => $priority,
		'date'       => get_the_date( '', $post ),
		'date_gmt'   => get_post_time( 'c', true, $post ),
		'unread'     => ! in_array( $post->ID, $read_ids, true ),
		'expires_at' => absint( get_post_meta( $post->ID, IW_SCANNER_META_NOTIFICATION_EXPIRES_AT, true ) ),
		'image'      => iw_scanner_get_notification_image( $post->ID ),
	];
}

function iw_scanner_format_notification_detail( WP_Post $post, array $read_ids ): array {
	$item = iw_scanner_format_notification_list_item( $post, $read_ids );

	$item['content'] = wp_kses_post( apply_filters( 'the_content', $post->post_content ) );

	return $item;
}

add_action(
	'add_meta_boxes',
	static function () {
		add_meta_box(
			'iw-scanner-notification-settings',
			__( 'Scanner Notification Settings', 'iw-scanner' ),
			'iw_scanner_render_notification_metabox',
			IW_SCANNER_NOTIFICATION_POST_TYPE,
			'side',
			'default'
		);
	}
);

function iw_scanner_render_notification_metabox( WP_Post $post ): void {
	$priority   = iw_scanner_sanitize_notification_priority( (string) get_post_meta( $post->ID, IW_SCANNER_META_NOTIFICATION_PRIORITY, true ) );
	$expires_at = absint( get_post_meta( $post->ID, IW_SCANNER_META_NOTIFICATION_EXPIRES_AT, true ) );
	$expires    = $expires_at ? wp_date( 'Y-m-d\TH:i', $expires_at ) : '';

	wp_nonce_field( 'iw_scanner_save_notification_meta', 'iw_scanner_notification_meta_nonce' );
	?>
	<p>
		<label for="iw-scanner-notification-priority"><strong><?php esc_html_e( 'Priority', 'iw-scanner' ); ?></strong></label>
		<select id="iw-scanner-notification-priority" name="iw_scanner_notification_priority" class="widefat">
			<?php foreach ( iw_scanner_notification_priorities() as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $priority, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="iw-scanner-notification-expires"><strong><?php esc_html_e( 'Expires at', 'iw-scanner' ); ?></strong></label>
		<input id="iw-scanner-notification-expires" class="widefat" type="datetime-local" name="iw_scanner_notification_expires" value="<?php echo esc_attr( $expires ); ?>">
	</p>
	<p class="description"><?php esc_html_e( 'Leave expiry empty to keep the notification visible until unpublished.', 'iw-scanner' ); ?></p>
	<?php
}

add_action(
	'save_post_' . IW_SCANNER_NOTIFICATION_POST_TYPE,
	static function ( int $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if (
			! isset( $_POST['iw_scanner_notification_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['iw_scanner_notification_meta_nonce'] ) ), 'iw_scanner_save_notification_meta' )
		) {
			return;
		}

		$priority = iw_scanner_sanitize_notification_priority( sanitize_key( wp_unslash( $_POST['iw_scanner_notification_priority'] ?? 'info' ) ) );
		update_post_meta( $post_id, IW_SCANNER_META_NOTIFICATION_PRIORITY, $priority );

		$expires = sanitize_text_field( wp_unslash( $_POST['iw_scanner_notification_expires'] ?? '' ) );
		if ( $expires === '' ) {
			update_post_meta( $post_id, IW_SCANNER_META_NOTIFICATION_EXPIRES_AT, 0 );
			return;
		}

		$date      = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $expires, wp_timezone() );
		$timestamp = $date instanceof DateTimeImmutable ? $date->getTimestamp() : strtotime( $expires );
		update_post_meta( $post_id, IW_SCANNER_META_NOTIFICATION_EXPIRES_AT, $timestamp ? $timestamp : 0 );
	}
);

add_action(
	'admin_menu',
	static function () {
		add_menu_page(
			__( 'Scanner Operators', 'iw-scanner' ),
			__( 'Scanner', 'iw-scanner' ),
			'manage_options',
			'iw-scanner-operators',
			'iw_scanner_render_admin_page',
			'dashicons-visibility',
			58
		);

		add_submenu_page(
			'iw-scanner-operators',
			__( 'Scanner Notifications', 'iw-scanner' ),
			__( 'News', 'iw-scanner' ),
			'manage_options',
			'edit.php?post_type=' . IW_SCANNER_NOTIFICATION_POST_TYPE
		);
	}
);

add_action(
	'admin_post_iw_scanner_revoke_operator',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage scanner operators.', 'iw-scanner' ) );
		}

		$user_id = absint( $_POST['user_id'] ?? 0 );
		check_admin_referer( 'iw_scanner_revoke_operator_' . $user_id );

		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof WP_User || user_can( $user, 'manage_options' ) ) {
			iw_scanner_redirect_admin( 'revoke_failed' );
		}

		$user->remove_role( IW_SCANNER_ROLE );
		$user->remove_cap( IW_SCANNER_CAP );
		update_user_meta( $user_id, IW_SCANNER_META_REVOKED, '1' );

		iw_scanner_redirect_admin( 'revoked' );
	}
);

add_action(
	'admin_post_iw_scanner_restore_operator',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage scanner operators.', 'iw-scanner' ) );
		}

		$user_id = absint( $_POST['user_id'] ?? 0 );
		check_admin_referer( 'iw_scanner_restore_operator_' . $user_id );

		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof WP_User || user_can( $user, 'manage_options' ) ) {
			iw_scanner_redirect_admin( 'restore_failed' );
		}

		$user->add_role( IW_SCANNER_ROLE );
		$user->add_cap( IW_SCANNER_CAP );
		delete_user_meta( $user_id, IW_SCANNER_META_REVOKED );

		iw_scanner_redirect_admin( 'restored' );
	}
);

add_action(
	'admin_post_iw_scanner_create_notice',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage scanner notices.', 'iw-scanner' ) );
		}

		check_admin_referer( 'iw_scanner_create_notice' );

		$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
		$variant = sanitize_key( wp_unslash( $_POST['variant'] ?? 'info' ) );
		$ttl     = max( 1, min( 168, absint( $_POST['ttl_hours'] ?? 8 ) ) );

		if ( $message === '' ) {
			iw_scanner_redirect_admin( 'notice_empty' );
		}

		if ( ! in_array( $variant, [ 'info', 'warning', 'urgent' ], true ) ) {
			$variant = 'info';
		}

		$notices   = get_option( IW_SCANNER_NOTICES_OPTION, [] );
		$notices   = is_array( $notices ) ? $notices : [];
		$notices[] = [
			'id'         => 'notice_' . wp_generate_uuid4(),
			'message'    => $message,
			'variant'    => $variant,
			'created_at' => time(),
			'created_by' => get_current_user_id(),
			'expires_at' => time() + ( $ttl * HOUR_IN_SECONDS ),
		];

		update_option( IW_SCANNER_NOTICES_OPTION, array_slice( $notices, -20 ), false );

		iw_scanner_redirect_admin( 'notice_created' );
	}
);

add_action(
	'admin_post_iw_scanner_delete_notice',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage scanner notices.', 'iw-scanner' ) );
		}

		$notice_id = sanitize_key( wp_unslash( $_POST['notice_id'] ?? '' ) );
		check_admin_referer( 'iw_scanner_delete_notice_' . $notice_id );

		$notices = get_option( IW_SCANNER_NOTICES_OPTION, [] );
		$notices = is_array( $notices ) ? $notices : [];
		$notices = array_values(
			array_filter(
				$notices,
				static fn( $notice ) => ! is_array( $notice ) || ( $notice['id'] ?? '' ) !== $notice_id
			)
		);

		update_option( IW_SCANNER_NOTICES_OPTION, $notices, false );

		iw_scanner_redirect_admin( 'notice_deleted' );
	}
);

function iw_scanner_render_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view scanner operators.', 'iw-scanner' ) );
	}

	$status_messages = [
		'revoked'        => __( 'Scanner access was revoked.', 'iw-scanner' ),
		'restored'       => __( 'Scanner access was restored.', 'iw-scanner' ),
		'notice_created' => __( 'Scanner notification was published.', 'iw-scanner' ),
		'notice_deleted' => __( 'Scanner notification was removed.', 'iw-scanner' ),
		'notice_empty'   => __( 'Notification message cannot be empty.', 'iw-scanner' ),
	];
	$status = sanitize_key( $_GET['iw_scanner_status'] ?? '' );
	$operators = iw_scanner_operator_users();
	$notices = iw_scanner_get_active_notices();
	?>
	<div class="wrap iw-scanner-admin">
		<h1><?php esc_html_e( 'Scanner Operators', 'iw-scanner' ); ?></h1>
		<?php if ( isset( $status_messages[ $status ] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $status_messages[ $status ] ); ?></p></div>
		<?php endif; ?>

		<style>
			.iw-scanner-admin-grid { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 24px; align-items: start; }
			.iw-scanner-card { background: #fff; border: 1px solid #dcdcde; border-radius: 10px; padding: 18px; }
			.iw-scanner-status { display: inline-flex; align-items: center; gap: 7px; font-weight: 600; }
			.iw-scanner-status::before { content: ""; width: 8px; height: 8px; border-radius: 999px; background: #8c8f94; }
			.iw-scanner-status--online::before { background: #00a32a; }
			.iw-scanner-status--revoked::before { background: #d63638; }
			.iw-scanner-muted { color: #646970; }
			.iw-scanner-actions { display: flex; gap: 8px; flex-wrap: wrap; }
			.iw-scanner-notice-list { display: grid; gap: 10px; margin-top: 14px; }
			.iw-scanner-notice-item { border: 1px solid #dcdcde; border-radius: 8px; padding: 12px; background: #f6f7f7; }
			.iw-scanner-notice-item strong { display: block; margin-bottom: 4px; text-transform: capitalize; }
			@media (max-width: 960px) { .iw-scanner-admin-grid { grid-template-columns: 1fr; } }
		</style>

		<div class="iw-scanner-admin-grid">
			<div class="iw-scanner-card">
				<h2><?php esc_html_e( 'Operators', 'iw-scanner' ); ?></h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Operator', 'iw-scanner' ); ?></th>
							<th><?php esc_html_e( 'Status', 'iw-scanner' ); ?></th>
							<th><?php esc_html_e( 'Last seen', 'iw-scanner' ); ?></th>
							<th><?php esc_html_e( 'Museum', 'iw-scanner' ); ?></th>
							<th><?php esc_html_e( 'Last scan', 'iw-scanner' ); ?></th>
							<th><?php esc_html_e( 'Access', 'iw-scanner' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! $operators ) : ?>
							<tr><td colspan="6"><?php esc_html_e( 'No scanner operators found yet.', 'iw-scanner' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $operators as $operator ) : ?>
							<?php
							$operator_status = iw_scanner_operator_status( $operator );
							$building_name = (string) get_user_meta( $operator->ID, IW_SCANNER_META_LAST_BUILDING_NAME, true );
							$last_scan_at = absint( get_user_meta( $operator->ID, IW_SCANNER_META_LAST_SCAN_AT, true ) );
							$last_scan_title = (string) get_user_meta( $operator->ID, IW_SCANNER_META_LAST_SCAN_TITLE, true );
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $operator->display_name ?: $operator->user_login ); ?></strong><br>
									<span class="iw-scanner-muted"><?php echo esc_html( $operator->user_email ); ?></span>
								</td>
								<td>
									<span class="iw-scanner-status <?php echo esc_attr( $operator_status['revoked'] ? 'iw-scanner-status--revoked' : ( $operator_status['online'] ? 'iw-scanner-status--online' : '' ) ); ?>">
										<?php echo esc_html( $operator_status['label'] ); ?>
									</span>
								</td>
								<td><?php echo esc_html( iw_scanner_format_relative_time( $operator_status['last_seen'] ) ); ?></td>
								<td><?php echo esc_html( $building_name ?: '-' ); ?></td>
								<td>
									<?php echo esc_html( $last_scan_title ?: '-' ); ?>
									<?php if ( $last_scan_at ) : ?>
										<br><span class="iw-scanner-muted"><?php echo esc_html( iw_scanner_format_relative_time( $last_scan_at ) ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<div class="iw-scanner-actions">
										<?php if ( $operator_status['revoked'] ) : ?>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
												<?php wp_nonce_field( 'iw_scanner_restore_operator_' . $operator->ID ); ?>
												<input type="hidden" name="action" value="iw_scanner_restore_operator">
												<input type="hidden" name="user_id" value="<?php echo esc_attr( $operator->ID ); ?>">
												<button class="button button-primary" type="submit"><?php esc_html_e( 'Restore', 'iw-scanner' ); ?></button>
											</form>
										<?php else : ?>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
												<?php wp_nonce_field( 'iw_scanner_revoke_operator_' . $operator->ID ); ?>
												<input type="hidden" name="action" value="iw_scanner_revoke_operator">
												<input type="hidden" name="user_id" value="<?php echo esc_attr( $operator->ID ); ?>">
												<button class="button button-link-delete" type="submit"><?php esc_html_e( 'Revoke', 'iw-scanner' ); ?></button>
											</form>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="iw-scanner-card">
				<h2><?php esc_html_e( 'Broadcast Ribbon', 'iw-scanner' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'iw_scanner_create_notice' ); ?>
					<input type="hidden" name="action" value="iw_scanner_create_notice">
					<p>
						<label for="iw-scanner-notice-message"><strong><?php esc_html_e( 'Message', 'iw-scanner' ); ?></strong></label>
						<textarea id="iw-scanner-notice-message" class="large-text" name="message" rows="4" required></textarea>
					</p>
					<p>
						<label for="iw-scanner-notice-variant"><strong><?php esc_html_e( 'Priority', 'iw-scanner' ); ?></strong></label>
						<select id="iw-scanner-notice-variant" name="variant">
							<option value="info"><?php esc_html_e( 'Info', 'iw-scanner' ); ?></option>
							<option value="warning"><?php esc_html_e( 'Warning', 'iw-scanner' ); ?></option>
							<option value="urgent"><?php esc_html_e( 'Urgent', 'iw-scanner' ); ?></option>
						</select>
					</p>
					<p>
						<label for="iw-scanner-notice-ttl"><strong><?php esc_html_e( 'Expires in hours', 'iw-scanner' ); ?></strong></label>
						<input id="iw-scanner-notice-ttl" class="small-text" type="number" name="ttl_hours" min="1" max="168" value="8">
					</p>
					<button class="button button-primary" type="submit"><?php esc_html_e( 'Publish ribbon', 'iw-scanner' ); ?></button>
				</form>

				<div class="iw-scanner-notice-list">
					<?php foreach ( $notices as $notice ) : ?>
						<div class="iw-scanner-notice-item">
							<strong><?php echo esc_html( $notice['variant'] ); ?></strong>
							<p><?php echo esc_html( $notice['message'] ); ?></p>
							<p class="iw-scanner-muted">
								<?php
								echo esc_html(
									$notice['expires_at']
										? sprintf( __( 'Expires %s from now', 'iw-scanner' ), human_time_diff( time(), $notice['expires_at'] ) )
										: __( 'No expiry', 'iw-scanner' )
								);
								?>
							</p>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'iw_scanner_delete_notice_' . $notice['id'] ); ?>
								<input type="hidden" name="action" value="iw_scanner_delete_notice">
								<input type="hidden" name="notice_id" value="<?php echo esc_attr( $notice['id'] ); ?>">
								<button class="button" type="submit"><?php esc_html_e( 'Remove', 'iw-scanner' ); ?></button>
							</form>
						</div>
					<?php endforeach; ?>
					<?php if ( ! $notices ) : ?>
						<p class="iw-scanner-muted"><?php esc_html_e( 'No active ribbon notifications.', 'iw-scanner' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	<?php
}
