<?php

namespace WCML\Utilities;

class WpAdminPages {

	public static function isDashboard() {
		global $pagenow;

		return is_admin() && 'index.php' === $pagenow;
	}

	public static function isPermalinksSettings() {
		global $pagenow;

		return is_admin() && 'options-permalink.php' === $pagenow;
	}
}
