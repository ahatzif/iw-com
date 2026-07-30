<?php

namespace WCML\Coupons;

use WPML\FP\Obj;

class Helper {

	const POST_TYPE_COUPON = 'shop_coupon';

	const PAGE_COUPON_LIST = 'edit.php';

	const PAGE_COUPON_NEW = 'post-new.php';

	const PAGE_COUPON_EDIT = 'post.php';

	const ACTION_EDIT = 'edit';

	private static function isPostTypeCoupon( $postType = null ) {
		if ( null === $postType ) {
			$postType = Obj::prop( 'post_type', $_GET );
		}
		return self::POST_TYPE_COUPON  === $postType;
	}

	private static function isCouponAdminScreen( $page ) {
		global $pagenow;
		return is_admin() && $page === $pagenow;
	}

	public static function isCouponCreateAdminScreen() {
		return self::isCouponAdminScreen( self::PAGE_COUPON_NEW ) && self::isPostTypeCoupon();
	}

	public static function isCouponListAdminScreen() {
		return self::isCouponAdminScreen( self::PAGE_COUPON_LIST ) && self::isPostTypeCoupon();
	}

	public static function isCouponEditAdminScreen() {
		$isActionEdit = Obj::prop( 'action', $_GET ) === self::ACTION_EDIT;
		$getPost      = Obj::prop( 'post', $_GET );

		return self::isCouponAdminScreen( self::PAGE_COUPON_EDIT ) && $isActionEdit && $getPost && self::isPostTypeCoupon( get_post_type( $getPost ) );
	}
}
