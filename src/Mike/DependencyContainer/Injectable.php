<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\DependencyContainer;

use Mike\DependencyContainer;

/**
 * Mike\DependencyContainer\Injectable
 *
 * @property \Mike\Application\Mvc\Router $router
 * @property \Mike\Application\Mvc\Url $url
 */


class Injectable {

	/**
	 * Dependency Container
	 *
	 * @var DependencyContainer $_dependencyContainer
	 */

	protected $_dependencyContainer;

	/**
	 * Sets the dependency container
	 *
	 * @param DependencyContainer $dependencyContainer
	 */

	public function setDC( DependencyContainer $dependencyContainer ) {

		$this->_dependencyContainer = $dependencyContainer;
	}

	/**
	 * Returns the internal dependency container
	 *
	 * @return DependencyContainer
	 */

	public function getDC() {

		return $this->_dependencyContainer;
	}

	/**
	 * Magic method for getting dependencies
	 *
	 * @return mixed
	 */

	public function __get( $name ) {

		return $this->_dependencyContainer->get($name);
	}

}