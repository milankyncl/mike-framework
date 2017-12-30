<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Application\Mvc;

use Mike\DependencyContainer\Injectable;


class Url extends Injectable {

	/**
	 * Return destination array
	 *
	 * @param string $destination
	 *
	 * @throws \Mike\Exception
	 */

	public function resolveDestinationCode(string $destinationCode) {

		$destination = [];

		if (!preg_match('~^([\w:]+):(\w*+)(#.*)?()\z~', $destinationCode, $matches))
			throw new \Mike\Exception('Destination link is not valid.');

		list($module, $controller, $action) = $matches;

		echo $module . '<br>';
		echo $controller . '<br>';
		echo $action . '<br>';

		exit;

		return $destination;


	}

}