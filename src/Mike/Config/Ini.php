<?php

namespace Mike\Config;

use Mike\Config,
	Mike\Exception;

/**
 * Mike\Config\Adapter\Ini
 */

class Ini extends Config {

	/**
	 * Ini constructor.
	 *
	 * @param string $iniPath
	 * @param int|null $mode
	 */

	public function __construct($iniPath, $mode = INI_SCANNER_RAW) {

		if(!file_exists($iniPath))
			throw new Exception('Configuration file ' . basename($iniPath) . ' doesn\'t exist.');

		$iniConfig = parse_ini_file($iniPath, true, $mode);

		if(!$iniConfig)
			throw new Exception('Configuration file ' . basename($iniPath) . ' can\'t be loaded.');


		parent::__construct($iniConfig);
	}

}