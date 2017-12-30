<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Application\Mvc;

use \Mike\DependencyContainer\Injectable;


abstract class Controller extends Injectable {

	/**
	 * Forward to another action
	 *
	 * @param string|array $direction
	 *
	 * @throws \Mike\Exception
	 */

	public function forward(array $direction) {

	}

}