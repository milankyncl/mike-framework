<?php

namespace Mike;

/**
 * DependencyContainer class definition
 */

class DependencyContainer {

	/**
	 * Registered services
	 *
	 * @var $_services array
	 */

	protected $_services;

	/**
	 * Magic method for accessing container services
	 *
	 * @param string $name
	 *
	 * @return null
	 * @throws Exception
	 */

	public function __get(string $name) {

		return $this->get($name);

	}

	/**
	 * Set Dependency Service
	 *
	 * @param string $name
	 * @param callable|object $definition
	 */

	public function set($name, $definition) {

		if(is_callable($definition)) {

			$definition = $definition();

		}

		$this->_services[$name] = $definition;

	}

	/**
	 * Get registered service
	 *
	 * @param $name
	 * @param null $parameters
	 *
	 * @return null
	 * @throws Exception
	 */

	public function get($name) {

		if(!isset($this->_services[$name]))
			throw new Exception('Service \'' . $name . '\' wasn\'t found in the dependency container.');

		$instance = $this->_services[$name];

		if(is_object($instance)) {

			if(method_exists($instance, 'setDC'))
				$instance->setDC($this);

		}

		return $instance;
	}

	/**
	 * Check if container has service
	 *
	 * @param $name
	 *
	 * @return bool
	 */

	public function has( $name ) {

		return isset($this->_services[$name]);
	}

	/**
	 * Remove service from container
	 *
	 * @param $name
	 */

	public function remove( $name ) {

		unset($this->_services[$name]);
	}


}