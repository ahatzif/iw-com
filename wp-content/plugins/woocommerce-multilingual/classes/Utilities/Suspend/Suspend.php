<?php

namespace WCML\Utilities\Suspend;

interface Suspend {

	public function resume();

	public function runAndResume( callable $function );
}
