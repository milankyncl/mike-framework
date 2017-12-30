<?php
/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Config;

use Mike\Config,
	Mike\Exception,
	Symfony\Component\Yaml\Yaml as YamlParser;


class Yaml extends Config {

	/**
	 * Mike\Config\Adapter\Yaml constructor
	 *
	 * @param string $yamlPath
	 *
	 * @throws \Mike\Exception
	 */

	public function __construct($yamlPath) {

		$yamlConfig = YamlParser::parseFile($yamlPath);

		if(!$yamlConfig)
			throw new Exception('Configuration file ' . basename($yamlPath) . ' can\'t be loaded');

		parent::__construct($yamlConfig);
	}

}