<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike;

/**
 * Class Mike\Bootstrap
 * @package Mike
 */

class Bootstrap {

	const ADAPTER_YAML = \Mike\Config\Yaml::class;

	const ADAPTER_INI = \Mike\Config\Ini::class;

	const ADAPTER_PHP = \Mike\Config\Php::class;

	/**
	 * @var string $_rootPath
	 */

	private $_rootPath;

	/**
	 * @var $_config Config
	 */

	private $_config;

	/**
	 * @var $_dependencyContainer DependencyContainer
	 */

	private $_dependencyContainer;

	public function __construct($entryRoot = null) {

		if(is_null($entryRoot))
			throw new Exception('Entry root is required to create Bootstrap module.');

		$this->_rootPath = $entryRoot;

	}


	/**
	 * Add configuration file to application
	 *
	 * @param $path string
	 *
	 * @throws Exception;
	 */

	public function addConfiguration($path, $adapter = self::ADAPTER_YAML) {

		if(!class_exists($adapter))
			throw new Exception('Unknown configuration adapter.');

		$config = new $adapter($path, true);

		if(isset($this->_config))
			$this->_config = $this->_config->merge($config);

		else
			$this->_config = $config;

	}

	/**
	 * Get configuration
	 *
	 * @return Config
	 */

	protected function getConfiguration() {

		if(!isset($this->_config))
			throw new Exception('No configuration available.');

		return $this->_config;

	}

	/**
	 * Get Root path directory
	 *
	 * @return string
	 */

	public function getRootPath() {

		return $this->_rootPath;

	}

	/**
	 * Create autoloader
	 *
	 * @return Autoloader
	 */

	public function createAutoloader() {

		$autoloader = new Autoloader();

		foreach($this->getRegisterModules() as $module) {

			$autoloader->registerNamespaces([

				$module . '\Controllers' => $this->_rootPath . '/app/modules/' . $module . 'Module/controllers',
				$module . '\Library' => $this->_rootPath . '/app/modules/' . $module . 'Module/library',

			], true);

		}

		return $autoloader;
	}

	/**
	 * Create and resolve dependency injector
	 */

	public function createDependencyContainer() {

		$container = new DependencyContainer();

		$services = $this->_config->get('services');

		if(!is_null($services)) {

			foreach($services as $name => $service) {

				if($service instanceof Config) {

					$service = $service->toArray();

					$class = key($service);

					$container->set($name, new $class($service[$class]));

				} else {

					$container->set($name, new $service());

				}

			}

		}

		$this->_dependencyContainer = $container;

	}

	/**
	 * Get Dependency Container
	 *
	 * @return DependencyContainer
	 */

	public function getDependencyContainer() {

		if(!($this->_dependencyContainer instanceof DependencyContainer))
			throw new Exception('Dependency container is required to access internal services.');

		$this->_dependencyContainer->set('config', $this->getConfiguration());

		return $this->_dependencyContainer;

	}

	/**
	 * Get Dependency Container
	 *
	 * @param $dependencyContainer DependencyContainer
	 */

	public function setDependencyContainer(DependencyContainer $dependencyContainer) {

		return $this->_dependencyContainer = $dependencyContainer;

	}

	/**
	 * Register modules
	 */

	public function getRegisterModules() {

		$application = $this->_config->get('application');

		if(is_null($application))
			throw new Exception('Application configuration is not provided.');

		$modules = $application->get('modules');

		if(is_null($modules))
			throw new Exception('Modules are not registered in application configuration.');

		return $modules;

	}

}