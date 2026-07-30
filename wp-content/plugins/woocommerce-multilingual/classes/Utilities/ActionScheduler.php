<?php

namespace WCML\Utilities;

class ActionScheduler {

	public static function isWcRunningFromAsyncActionScheduler(): bool {
		return function_exists( 'wc_is_running_from_async_action_scheduler' ) && wc_is_running_from_async_action_scheduler();
	}
}
