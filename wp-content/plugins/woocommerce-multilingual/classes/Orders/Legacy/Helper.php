<?php

namespace WCML\Orders\Legacy;

use WPML\FP\Obj;

class Helper {

	const POST_TYPE_ORDER = 'shop_order';

	const PAGE_ORDER_LIST = 'edit.php';

	const PAGE_ORDER_NEW = 'post-new.php';

	const PAGE_ORDER_EDIT = 'post.php';

	const ACTION_EDIT = 'edit';

	private static function isPostTypeOrder( $postType = null ) {
		if ( null === $postType ) {
			$postType = Obj::prop( 'post_type', $_GET );
		}
		return self::POST_TYPE_ORDER  === $postType;
	}

	private static function isOrderAdminScreen( $page ) {
		global $pagenow;
		return is_admin() && $page === $pagenow;
	}

	public static function isOrderCreateAdminScreen() {
		return self::isOrderAdminScreen( self::PAGE_ORDER_NEW ) && self::isPostTypeOrder();
	}

	public static function isOrderListAdminScreen() {
		return self::isOrderAdminScreen( self::PAGE_ORDER_LIST ) && self::isPostTypeOrder();
	}

	public static function isOrderEditAdminScreen() {
		$isActionEdit = Obj::prop( 'action', $_GET ) === self::ACTION_EDIT;
		$getPost      = Obj::prop( 'post', $_GET );

		return self::isOrderAdminScreen( self::PAGE_ORDER_EDIT ) && $isActionEdit && $getPost && self::isPostTypeOrder( get_post_type( $getPost ) );
	}
}
