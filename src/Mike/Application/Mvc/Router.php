<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Application\Mvc;

use Mike\Exception;
use Mike\Application\Mvc\Router\Route;
use Mike\Application\Mvc\Router\Group;
use Mike\Utilities\Text;
use Mike\DependencyContainer\Injectable;


class Router extends Injectable {


	protected $_uriSource;

	protected $_namespace = null;

	protected $_module = null;

	protected $_controller = null;

	protected $_action = null;

	protected $_params = [];

	protected $_matchedRoute;

	protected $_matches;

	protected $_wasMatched = false;

	private $_defaultModule = 'Index';

	private $_defaultController = 'Index';

	private $_defaultAction = 'index';

	protected $_defaultParams = [];

	protected $_removeExtraSlashes;

	protected $_notFoundPaths;

	const URI_SOURCE_GET_URL = 0;

	const URI_SOURCE_SERVER_REQUEST_URI = 1;

	/**
	 *
	 */

	/** @var $_routes Route[] */

	protected $_routes = [];

	/**
	 * Mike\Application\Mvc\Router constructor
	 */

	public function __construct() {

		$this->_routes[] = new Route('/:action', [
			'action' => 1
		]);

		$this->_routes[] = new Route('/:controller/:action', [
			'controller' => 1,
			'action' => 2
		]);

		$this->_routes[] = new Route('/:controller/:action/:params',[
			'controller' => 1,
			'action' => 2,
			'params' => 3
		]);

	}

	/**
	 * Get rewrite info. This info is read from $_GET["_url"]. This returns '/' if the rewrite information cannot be read
	 *
	 * @return
	 */

	public function getRewriteUri() {

		/**
		 * By default we use $_GET["url"] to obtain the rewrite information
		 */

		if(!$this->_uriSource) {

			if(isset($_GET['url'])) {

				$url = $_GET['url'];

				if(!empty($url))
					return $url;
			}

		} else {

			/**
			 * Otherwise use the standard $_SERVER["REQUEST_URI"]
			 */

			if(isset($_SERVER['REQUEST_URI'])) {

				$url = $_SERVER['REQUEST_URI'];

				$urlParts = explode('?', $url);
				$realUri = $urlParts[0];

				if(!empty($realUri))
					return $realUri;

			}
		}

		return '/';
	}

	/**
	 * Sets the URI source. One of the URI_SOURCE_* constants
	 *
	 * @param string $uriSource
	 *
	 * @return Router
	 */

	public function setUriSource($uriSource) {

		$this->_uriSource = $uriSource;

		return $this;
	}

	/**
	 * Set whether router must remove the extra slashes in the handled routes
	 *
	 * @param boolean $remove
	 *
	 * @return Router
	 */

	public function removeExtraSlashes($remove) {

		$this->_removeExtraSlashes = $remove;

		return $this;
	}

	/**
	 * Sets the name of the default namespace
	 *
	 * @param string $namespaceName
	 *
	 * @return Router
	 */

	public function setDefaultNamespace($namespaceName) {

		$this->_defaultNamespace = $namespaceName;

		return $this;
	}

	/**
	 * Sets the name of the default module
	 *
	 * @param string $moduleName
	 *
	 * @return Router
	 */

	public function setDefaultModule($moduleName) {

		$this->_defaultModule = $moduleName;

		return $this;
	}

	/**
	 * Sets the default controller name
	 *
	 * @param string $controllerName
	 *
	 * @return Router
	 */
	public function setDefaultController($controllerName) {

		$this->_defaultController = $controllerName;

		return $this;
	}

	/**
	 * Sets the default action name
	 *
	 * @param string $actionName
	 *
	 * @return Router
	 */

	public function setDefaultAction($actionName) {

		$this->_defaultAction = $actionName;

		return $this;
	}

	/**
	 * Sets an array of default paths. If a route is missing a path the router will use the defined here
	 * This method must not be used to set a 404 route
	 *
	 * @param array $defaults
	 *
	 * @return Router
	 */

	public function setDefaults(array $defaults) {

		if(isset($defaults['module']))
			$this->_defaultModule = $defaults['module'];

		if(isset($defaults['controller']))
			$this->_defaultController = $defaults['controller'];

		if(isset($defaults['action']))
			$this->_defaultAction = $defaults['action'];

		if(isset($defaults['params']))
			$this->_defaultParams = $defaults['params'];

		return $this;
	}

	/**
	 * Returns an array of default parameters
	 *
	 * @return array
	 */

	public function getDefaults() {

		return [
			'module'      => $this->_defaultModule,
			'controller'  => $this->_defaultController,
			'action'      => $this->_defaultAction,
			'params'      => $this->_defaultParams
		];
	}

	/**
	 * Handles routing information received from the rewrite engine
	 *
	 * @param string|null $uri
	 *
	 * @return void
	 */

	public function handle($uri = null) {

		if(!$uri)
			$uri = $this->getRewriteUri();

		/**
		 * Remove extra slashes in the route
		 */

		if($this->_removeExtraSlashes && $uri != '/')
			$handledUri = rtrim($uri, '/');
		else
			$handledUri = $uri;

		$request = null;
		$currentHostName = null;
		$routeFound = false;
		$parts = [];
		$params = [];
		$matches = null;
		$this->_wasMatched = false;
		$this->_matchedRoute = null;

		if(!is_object($this->_dependencyContainer))
			throw new Exception('A dependency injection container is required to access the \'request\' service.');

		foreach($this->_routes as $route) {

			$params = [];
			$matches = null;

			$methods = $route->getHttpMethods();

			if(!is_null($methods)) {

				/** @var \Mike\Http\Request $request */

				if(is_null($request))
					$request = $this->_dependencyContainer->get('request');

				if(!$request->isMethod($methods))
					continue;

			}

			$hostname = $route->getHostName();

			if(!is_null($hostname)) {

				if(is_null($currentHostName))
					$currentHostName = $request->getHttpHost();

				if(!$currentHostName)
					continue;

				if(Text::contains($hostname, '(')) {

					if(!Text::contains($hostname, '#')) {

						$regexHostName = '#^' . $hostname;

						if(!Text::contains($hostname, ':'))
							$regexHostName .= '(:[[:digit:]]+)?';

						$regexHostName .= '$#i';

					} else {

						$regexHostName = $hostname;
					}

					$matched = preg_match($regexHostName, $currentHostName);

				} else {

					$matched = ($currentHostName == $hostname);
				}

				if(!$matched)
					continue;

			}

			/**
			 * If the route has parentheses use preg_match
			 */

			$pattern = $route->getCompiledPattern();

			if(Text::contains($pattern, '^'))
				$routeFound = preg_match($pattern, $handledUri, $matches);

			else
				$routeFound = ($pattern == $handledUri);

			/**
			 * Check for beforeMatch conditions
			 */

			if($routeFound) {

				$beforeMatch = $route->getBeforeMatch();

				if(!is_null($beforeMatch)) {

					if(!is_callable($beforeMatch))
						throw new Exception('Before-Match callback is not callable in matched route.');

					$routeFound = call_user_func_array($beforeMatch, [$handledUri, $route, $this]);
				}

			}

			if($routeFound) {

				$paths = $route->getPaths();

				if(is_array($matches)) {

					$converters = $route->getConverters();

					foreach($paths as $part => $position) {

						if(!is_string($part))
							throw new Exception('Wrong key in paths: ' . $part);

						if(!is_string($position) && !is_integer($position))
							continue;

						if(isset($matches[$position])) {

							if(is_array($converters)) {

								if(isset($converters[$part])) {

									$parts[$part] = call_user_func_array($converters[$part], [ $matches[$position] ]);

									continue;
								}
							}

							$parts[$part] = $matches[$position];

						} else {

							if(is_array($converters)) {

								if(isset($converters[$part]))
									$parts[$part] = call_user_func_array($converters[$part], [$position]);

							} else {

								if(is_integer($position))
									unset($parts[$part]);

							}
						}
					}

					$this->_matches = $matches;
				}

				$this->_matchedRoute = $route;

				break;
			}
		}

		if($routeFound)
			$this->_wasMatched = true;
		else
			$this->_wasMatched = false;

		if(!$routeFound) {

			$notFoundPaths = $this->_notFoundPaths;

			if(!is_null($notFoundPaths)) {

				$parts = Route::getRoutePaths($notFoundPaths);
				$routeFound = true;

			}

		}

		$this->_module = $this->_defaultModule;
		$this->_controller = $this->_defaultController;
		$this->_action = $this->_defaultAction;
		$this->_params = $this->_defaultParams;

		if($routeFound) {

			if(isset($parts['module'])) {

				if(!is_numeric($parts['module']))
					$this->_module = $parts['module'];

				unset($parts['module']);
			}


			if(isset($parts['controller'])) {

				if(!is_numeric($parts['controller']))
					$this->_controller = $parts['controller'];

				unset($parts['controller']);
			}


			if(isset($parts['action'])) {

				if(!is_numeric($parts['action']))
					$this->_action = $parts['action'];

				unset($parts['action']);
			}

			if(isset($parts['params'])) {

				if(is_string($parts['params'])) {

					$strParams = trim($parts['params'], '/');

					if($strParams != '')
						$params = explode('/', $strParams);

				}

				unset($parts['params']);
			}

			if(count($params))
				$this->_params = array_merge($params, $parts);
			else
				$this->_params = $parts;

		}
	}

	/**
	 * Add route to Router
	 *
	 * @param string $pattern
	 * @param string $paths
	 * @param array $httpMethods
	 * @param int $position
	 *
	 * @return Route
	 */

	public function add($pattern, $paths = null, $httpMethods = null) {

		$route = new Route($pattern, $paths, $httpMethods);

		$this->_routes[] = $route;

		return $route;
	}

	/**
	 * Add GET Route
	 *
	 * @param string $pattern
	 * @param string $paths
	 * @param array $httpMethods
	 *
	 * @return Route
	 */

	public function get($pattern, $paths = null) {

		return $this->add($pattern, $paths, 'GET');
	}

	/**
	 * Add POST Route
	 *
	 * @param string $pattern
	 * @param string $paths
	 *
	 * @return Route
	 */

	public function post($pattern, $paths = null) {

		return $this->add($pattern, $paths, 'POST');
	}

	/**
	 * Add PUT Route
	 *
	 * @param string $pattern
	 * @param string $paths
	 * @param array $httpMethods
	 *
	 * @return Route
	 */

	public function put($pattern, $paths = null) {

		return $this->add($pattern, $paths, 'PUT');
	}

	/**
	 * Add DELETE Route
	 *
	 * @param string $pattern
	 * @param string $paths
	 * @param array $httpMethods
	 *
	 * @return Route
	 */

	public function delete($pattern, $paths = null) {

		return $this->add($pattern, $paths, 'DELETE');
	}


	/**
	 * Mounts a group of routes in the router
	 *
	 * @param Group $group
	 *
	 * @return Router
	 */

	public function mount(Group $group) {

		$groupRoutes = $group->getRoutes();

		if(!count($groupRoutes))
			throw new Exception('The group of routes does not contain any routes.');

		$beforeMatch = $group->getBeforeMatch();

		if(!is_null($beforeMatch)) {

			foreach($groupRoutes as $route) {

				$route->beforeMatch($beforeMatch);
			}
		}

		$hostname = $group->getHostName();

		if(!is_null($hostname)) {

			foreach($groupRoutes as $route)
				$route->setHostName($hostname);
		}

		$routes = $this->_routes;

		if(is_array($routes))
			$this->_routes = array_merge($routes, $groupRoutes);
		else
			$this->_routes = $groupRoutes;

		return $this;
	}

	/**
	 * Set a group of paths to be returned when none of the defined routes are matched
	 *
	 * @param array|string $paths
	 *
	 * @return Router
	 */

	public function notFound($paths) {

		if(!is_array($paths) && !is_string($paths))
			throw new Exception('The not-found paths must be an array or string.');

		$this->_notFoundPaths = $paths;

		return $this;
	}

	/**
	 * Returns the processed namespace name
	 *
	 * @return string
	 */

	public function getNamespaceName() {

		return $this->_namespace;
	}

	/**
	 * Returns the processed module name
	 *
	 * @return string
	 */

	public function getModuleName() {

		return $this->_module;
	}

	/**
	 * Returns the processed controller name
	 *
	 * @return string
	 */

	public function getControllerName() {

		return $this->_controller;
	}

	/**
	 * Returns the processed action name
	 *
	 * @return string
	 */

	public function getActionName() {

		return $this->_action;
	}

	/**
	 * Returns the processed parameters
	 *
	 * @return array
	 */

	public function getParams() {

		return $this->_params;
	}

	/**
	 * Returns the route that matches the handled URI
	 *
	 * @return Route
	 */
	public function getMatchedRoute() {

		return $this->_matchedRoute;
	}

	/**
	 * Returns the sub expressions in the regular expression matched
	 *
	 * @return array
	 */

	public function getMatches() {

		return $this->_matches;
	}

	/**
	 * Checks if the router matches any of the defined routes
	 *
	 * @return bool
	 */

	public function wasMatched() {

		return $this->_wasMatched;
	}

	/**
	 * Returns all the routes defined in the router
	 *
	 * @return Route[]
	 */

	public function getRoutes() {

		return $this->_routes;
	}

}