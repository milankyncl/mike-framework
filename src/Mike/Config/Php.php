<?php
/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Config;

use Mike\Config,
	Mike\Exception;

/**
 * Mike\Config\Adapter\Php
 */

class Php extends Config {

	/**
	 * Php constructor.
	 *
	 * @param string $filePath
	 */

	public function __construct($filePath) {

		if(!file_exists($filePath))
			throw new Exception('Configuration file ' . basename($filePath) . ' doesn\'t exist.');

		$phpConfig = require_once $filePath;

		if(!$phpConfig)
			throw new Exception('Configuration file ' . basename($filePath) . ' can\'t be loaded.');

		parent::__construct($phpConfig);
	}

}