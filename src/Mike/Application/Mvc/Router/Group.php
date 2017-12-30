<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Application\Mvc\Router;


class Group {

	protected $_prefix;

	protected $_hostname;

	protected $_paths;

	protected $_routes;

	protected $_beforeMatch;

	/**
	 * Group constructor.
	 *
	 * @param null|array|string $paths
	 */

	public function __construct($paths = null) {

		if(is_array($paths) || is_string($paths))
			$this->_paths = $paths;

		if(method_exists($this, 'initialize'))
			$this->{'initialize'}($paths);

	}

	/**
	 * Set a hostname restriction for all the routes in the group
	 *
	 * @param string $hostname
	 *
	 * @return Group
	 */

	public function setHostname($hostname) {

		$this->_hostname = $hostname;

		return $this;
	}

	/**
	 * Returns the hostname restriction
	 *
	 * @return string
	 */

	public function getHostname() {

		return $this->_hostname;
	}

	/**
	 * Set a common uri prefix for all the routes in this group
	 *
	 * @param string $prefix
	 *
	 * @return Group
	 */

	public function setPrefix($prefix) {

		$this->_prefix = $prefix;

		return $this;
	}

	/**
	 * Returns the common prefix for all the routes
	 *
	 * @return string
	 */

	public function getPrefix() {

		return $this->_prefix;
	}

	/**
	 * Sets a callback that is called if the route is matched.
	 * The developer can implement any arbitrary conditions here
	 * If the callback returns false the route is treated as not matched
	 *
	 * @param callable $beforeMatch
	 *
	 * @return Group
	 */

	 public function beforeMatch(callable $beforeMatch) {

		$this->_beforeMatch = $beforeMatch;

		return $this;
	}

	/**
	 * Returns the 'before match' callback if any
	 *
	 * @return callable
	 */

	public function getBeforeMatch() {

		return $this->_beforeMatch;
	}

	/**
	 * Set common paths for all the routes in the group
	 *
	 * @param string $paths
	 *
	 * @return Group
	 */

	public function setPaths($paths) {

		$this->_paths = $paths;

		return $this;
	}

	/**
	 * Returns the common paths defined for this group
	 *
	 * @return array|string
	 */

	public function getPaths() {

		return $this->_paths;
	}

	/**
	 * Returns the routes added to the group
	 *
	 * @return Route[]
	 */

	public function getRoutes() {

		return $this->_routes;
	}

	/**
	 * Adds a route to the router on any HTTP method
	 *
	 * @param string $pattern
	 * @param string $paths
	 * $param array|string $httpMethod
	 *
	 * @return Route
	 */

	public function add($pattern, $paths = null, $httpMethods = null) {

		return $this->_addRoute($pattern, $paths, $httpMethods);
	}

	/**
	 * Adds a route to the router that only match if the HTTP method is GET
	 *
	 * @param string $pattern
	 * @param string|array $paths
	 *
	 * @return Route
	 */

	public function addGet($pattern, $paths = null) {

		return $this->_addRoute($pattern, $paths, 'GET');
	}

	/**
	 * Adds a route to the router that only match if the HTTP method is POST
	 *
	 * @param string $pattern
	 * @param string|array $paths
	 *
	 * @return Route
	 */

	public function addPost($pattern, $paths = null) {

		return $this->_addRoute($pattern, $paths, 'POST');
	}

	/**
	 * Adds a route to the router that only match if the HTTP method is PUT
	 *
	 * @param string $pattern
	 * @param string|array $paths
	 *
	 * @return Route
	 */

	public function addPut($pattern, $paths = null) {

		return $this->_addRoute($pattern, $paths, 'PUT');
	}

	/**
	 * Adds a route to the router that only match if the HTTP method is PATCH
	 *
	 * @param string pattern
	 * @param string/array paths
	 *
	 * @return Route
	 */

	public function addPatch($pattern, $paths = null) {

		return $this->_addRoute($pattern, $paths, 'PATCH');
	}

	/**
	 * Adds a route to the router that only match if the HTTP method is DELETE
	 *
	 * @param string $pattern
	 * @param string|array $paths
	 *
	 * @return Route
	 */

	public function addDelete($pattern, $paths = null) {

		return $this->_addRoute($pattern, $paths, 'DELETE');
	}

	/**
	 * Add a route to the router that only match if the HTTP method is OPTIONS
	 *
	 * @param string $pattern
	 * @param string|array $paths
	 *
	 * @return Route
	 */

	public function addOptions($pattern, $paths = null) {

		return $this->_addRoute($pattern, $paths, 'OPTIONS');

	}

	/**
	 * Adds a route to the router that only match if the HTTP method is HEAD
	 *
	 * @param string $pattern
	 * @param string|array $paths
	 *
	 * @return Route
	 */

	public function addHead($pattern, $paths = null) {

		return $this->_addRoute($pattern, $paths, 'HEAD');
	}

	/**
	 * Removes all the pre-defined routes
	 *
	 * @return void
	 */

	public function clear() {

		$this->_routes = [];
	}

	/**
	 * Adds a route applying the common attributes
	 *
	 * @param string $pattern
	 * @param string $paths
	 * @param string|array $httpMethods
	 *
	 * @return Route
	 */

	protected function _addRoute($pattern, $paths = null, $httpMethods = null) {

		/**
		 * Check if the paths need to be merged with current paths
		 */

		$defaultPaths = $this->_paths;

		if(is_array($defaultPaths)) {

			if(is_string($paths))
				$processedPaths = Route::getRoutePaths($paths);

			else
				$processedPaths = $paths;

			if(is_array($processedPaths))
				$mergedPaths = array_merge($defaultPaths, $processedPaths);

			else
				$mergedPaths = $defaultPaths;

		} else {

			$mergedPaths = $paths;
		}

		/**
		 * Every route is internally stored as a Mike\Application\Mvc\Router\Route
		 */

		$route = new Route($this->_prefix . $pattern, $mergedPaths, $httpMethods);
		$this->_routes[] = $route;

		$route->setGroup($this);

		return $route;
	}
}