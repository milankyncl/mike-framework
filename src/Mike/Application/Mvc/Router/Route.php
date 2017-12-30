<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Application\Mvc\Router;

use Mike\Exception;
use Mike\Utilities\Text;
use Mike\Mvc\Router\Group;


class Route {

	protected $_pattern;

	protected $_compiledPattern;

	protected $_paths;

	protected $_methods;

	protected $_hostname;

	protected $_converters;

	protected $_id;

	protected $_name;

	protected $_beforeMatch;

	protected $_match;

	protected $_group;

	protected static $_uniqueId;

	/**
	 * Route constructor.
	 *
	 * @param string $pattern
	 * @param string|null $paths
	 * @param array|null $httpMethods
	 */

	public function __construct($pattern, $paths = null, $httpMethods = null) {

		$this->configure($pattern, $paths);

		$this->_methods = $httpMethods;

		$uniqueId = (self::$_uniqueId === null) ? 0 : self::$_uniqueId;

		$routeId = $uniqueId;
		$this->_id = $routeId;

		self::$_uniqueId = $uniqueId + 1;
	}

	/**
	 * Replaces placeholders from pattern, returns valid PCRE reg. expression
	 *
	 * @param string $pattern
	 *
	 * @return mixed|string
	 */

	public function compilePattern($pattern) {

		if(Text::contains($pattern, ':')) {

			$idPattern = "/([\\w0-9\\_\\-]+)";

			if(Text::contains($pattern, '/:module'))
				$pattern = str_replace('/:module', $idPattern, $pattern);

			if(Text::contains($pattern, '/:controller'))
				$pattern = str_replace('/:controller', $idPattern, $pattern);

			if(Text::contains($pattern, '/:namespace'))
				$pattern = str_replace('/:namespace', $idPattern, $pattern);

			if(Text::contains($pattern, '/:action'))
				$pattern = str_replace('/:action', $idPattern, $pattern);

			if(Text::contains($pattern, '/:params'))
				$pattern = str_replace("/:params", '(/.*)*', $pattern);

			if(Text::contains($pattern, '/:int'))
				$pattern = str_replace("/:int", '/([0-9]+)', $pattern);
		}

		if(Text::contains($pattern, '('))
			return '#^' . $pattern . '$#u';

		if(Text::contains($pattern, '['))
			return '#^' . $pattern . '$#u';

		return $pattern;
	}

	/**
	 * Extracts parameters from a string
	 *
	 * @param string $pattern
	 *
	 * @return array|boolean
	 */

	public function extractNamedParams($pattern) {

		$prevCh = '\0';
		$bracketCount = 0;
		$parenthesesCount = 0;
		$intermediate = 0;
		$numberMatches = 0;
		$marker = null;

		if(strlen($pattern) <= 0)
			return false;

		$matches = [];
		$route = '';

		foreach(str_split($pattern) as $cursor => $ch) {

			if($parenthesesCount == 0) {

				if($ch == '{') {

					if($bracketCount == 0) {
						$marker = $cursor + 1;
						$intermediate = 0;
						$notValid = false;
					}

					$bracketCount++;

				} else {

					if($ch == '}') {

						$bracketCount--;

						if($intermediate > 0) {

							if($bracketCount == 0) {

								$numberMatches++;
								$variable = null;
								$regexp = null;
								$item = (string) substr($pattern, $marker, $cursor - $marker);

								foreach(str_split($item) as $cursorVar => $chItem) {

									if($chItem == '\0')
										break;

									if($cursorVar == 0 && !(($chItem >= 'a' && $chItem <= 'z') || ($chItem >= 'A' && $chItem <= 'Z'))) {
										$notValid = true;
										break;
									}

									if (($chItem >= 'a' && $chItem <= 'z') || ($chItem >= 'A' && $chItem <= 'Z') || ($chItem >= '0' && $chItem <='9') || $chItem == '-' || $chItem == '_' || $chItem ==  ':') {

										if($chItem == ':') {

											$variable = substr($item, 0, $cursorVar);
											$regexp = substr($item, $cursorVar + 1);

											break;
										}

									} else {

										$notValid = true;
										break;

									}

								}

								if(!$notValid) {

									$tmp = $numberMatches;

									if($variable && $regexp) {

										$foundPattern = 0;

										foreach($regexp as $chRegexp) {

											if($chRegexp == '\0')
												break;

											if(!$foundPattern) {

												if($chRegexp == '(')
													$foundPattern = 1;

											} else {

												if($chRegexp == ')') {
													$foundPattern = 2;
													break;
												}
											}
										}

										if($foundPattern != 2)
											$route .= '(' . $regexp . ')';
										else
											$route .= $regexp;

										$matches[$variable] = $tmp;

									} else {

										$route .= '([^/]*)';
										$matches[$item] = $tmp;
									}

								} else {

									$route .= '{' . $item . '}';

								}

								continue;
							}
						}
					}
				}
			}

			if($bracketCount == 0) {

				if($ch == '(') {

					$parenthesesCount++;

				} else {

					if($ch == ')') {

						$parenthesesCount--;

						if($parenthesesCount == 0)
							$numberMatches++;
					}
				}
			}

			if($bracketCount > 0) {

				$intermediate++;

			} else {

				if($parenthesesCount == 0 && $prevCh != '\\') {

					if($ch == '.' || $ch == '+' || $ch == '|' || $ch == '#')
						$route .= '\\';

				}

				$route .= $ch;
				$prevCh = $ch;
			}
		}

		return [$route, $matches];

	}

	/**
	 * Reconfigure the route adding a new pattern and a set of paths
	 *
	 * @param string $pattern
	 * @param null|string $paths
	 */

	public function configure($pattern, $paths = null) {

		$routePaths = self::getRoutePaths($paths);

		/**
		 * If the route starts with '#' we assume that it is a regular expression
		 */
		if(!Text::startsWith($pattern, '#')) {

			if(Text::contains($pattern, '{')) {

				/**
				 * The route has named parameters so we need to extract them
				 */

				$extracted = $this->extractNamedParams($pattern);
				$pcrePattern = $extracted[0];
				$routePaths = array_merge($routePaths, $extracted[1]);

			} else {

				$pcrePattern = $pattern;
			}

			/**
			 * Transform the route's pattern to a regular expression
			 */
			$compiledPattern = $this->compilePattern($pcrePattern);

		} else {

			$compiledPattern = $pattern;
		}

		/**
		 * Update the original pattern
		 */

		$this->_pattern = $pattern;

		/**
		 * Update the compiled pattern
		 */

		$this->_compiledPattern = $compiledPattern;

		/**
		 * Update the route's paths
		 */

		$this->_paths = $routePaths;
	}

	/**
	 * Returns routePaths
	 *
	 * @param string $paths
	 *
	 * @return array
	 */

	public static function getRoutePaths($paths = null) {

		if($paths != null) {

			if(is_string($paths)) {

				$moduleName = null;
				$controllerName = null;
				$actionName = null;

				// Explode the short paths using the :: separator
				$parts = explode('::', $paths);

				// Create the array paths dynamically
				switch(count($parts)) {

					case 3:
						$moduleName = $parts[0];
						$controllerName = $parts[1];
						$actionName = $parts[2];
						break;

					case 2:
						$controllerName = $parts[0];
						$actionName = $parts[1];
						break;

					case 1:
						$controllerName = $parts[0];
						break;
				}

				$routePaths = [];

				// Process module name
				if($moduleName != null)
					$routePaths['module'] = $moduleName;

				// Process controller name
				if($controllerName != null) {

					// Check if we need to obtain the namespace
					if(Text::contains($controllerName, "\\")) {

						// Extract the real class name from the namespaced class
						$realClassName = get_class($controllerName);

						echo 'Problém s NS!';

						exit;

						// Extract the namespace from the namespaced class
						//$namespaceName = $controllerName::class;
						$namespaceName = '';

						// Update the namespace
						if($namespaceName) {
							$routePaths['namespace'] = $namespaceName;
						}

					} else {

						$realClassName = $controllerName;

					}

					// Always pass the controller to lowercase
					$routePaths['controller'] = Text::uncamelize($realClassName);
				}

				// Process action name
				if($actionName != null)
					$routePaths['action'] = $actionName;

			} else {

				$routePaths = $paths;

			}

		} else {

			$routePaths = [];

		}

		if(!is_array($routePaths))
			throw new Exception('The route contains invalid paths.');

		return $routePaths;
	}

	/**
	 * Returns the route's name
	 *
	 * @return string
	 */

	public function getName() {

		return $this->_name;
	}

	/**
	 * Sets the route's name
	 *
	 * @param string $name
	 *
	 * @return Route
	 */

	public function setName($name) {

		$this->_name = $name;

		return this;
	}

	/**
	 * Sets a callback that is called if the route is matched.
	 * The developer can implement any arbitrary conditions here
	 * If the callback returns false the route is treated as not matched
	 *
	 * @param callable $callback
	 *
	 * @return Route
	 */

	public function beforeMatch($callback) {

		$this->_beforeMatch = $callback;

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
	 * Allows to set a callback to handle the request directly in the route
	 *
	 * @param callable $callback
	 *
	 * @return Route
	 */

	public function match($callback) {

		$this->_match = $callback;

		return $this;
	}

	/**
	 * Returns the 'match' callback if any
	 *
	 * @return callable
	 */

	public function getMatch() {

		return $this->_match;
	}

	/**
	 * Returns the route's id
	 *
	 * @return string
	 */

	public function getRouteId() {

		return $this->_id;
	}

	/**
	 * Returns the route's pattern
	 *
	 * @return string
	 */

	public function getPattern() {

		return $this->_pattern;
	}

	/**
	 * Returns the route's compiled pattern
	 *
	 * @return string
	 */

	public function getCompiledPattern() {

		return $this->_compiledPattern;
	}

	/**
	 * Returns the paths
	 *
	 * @return array
	 */

	public function getPaths() {

		return $this->_paths;
	}

	/**
	 * Returns the paths using positions as keys and names as values
	 *
	 * @return array
	 */

	public function getReversedPaths() {

		$reversed = [];

		foreach($this->_paths as $path => $position) {

			$reversed[$position] = $path;
		}

		return $reversed;
	}

	/**
	 * Sets a set of HTTP methods that constraint the matching of the route (alias of via)
	 *
	 * @param array $httpMethods
	 *
	 * @return Route
	 */

	public function setHttpMethods(Array $httpMethods) {

		$this->_methods = $httpMethods;

		return $this;
	}

	/**
	 * Returns the HTTP methods that constraint matching the route
	 *
	 * @return array|string
	 */

	public function getHttpMethods() {

		return $this->_methods;
	}

	/**
	 * Sets a hostname restriction to the route
	 *
	 * @param string $hostname
	 *
	 * @return Route
	 */

	public function setHostname($hostname) {

		$this->_hostname = $hostname;

		return $this;
	}

	/**
	 * Returns the hostname restriction if any
	 *
	 * @return string
	 */

	public function getHostname() {

		return $this->_hostname;
	}

	/**
	 * Sets the group associated with the route
	 *
	 * @param Group $group
	 *
	 * @return Route
	 */

	public function setGroup(Group $group) {

		$this->_group = $group;

		return $this;
	}

	/**
	 * Returns the group associated with the route
	 *
	 * @return Group|null
	 */

	public function getGroup() {

		return $this->_group;
	}

	/**
	 * Adds a converter to perform an additional transformation for certain parameter
	 *
	 * @param string $name
	 * @param $converter
	 *
	 * @return Route
	 */

	public function convert($name, $converter) {

		$this->_converters[$name] = $converter;

		return $this;
	}

	/**
	 * Returns the router converter
	 *
	 * @return array
	 */

	public function getConverters() {

		return $this->_converters;
	}

	/**
	 * Resets the internal route id generator
	 *
	 * @return void
	 */

	public static function reset() {

		self::$_uniqueId = null;
	}
}